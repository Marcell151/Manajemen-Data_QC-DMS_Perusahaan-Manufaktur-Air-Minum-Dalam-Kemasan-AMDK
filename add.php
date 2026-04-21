<?php
require 'db.php';

// Ambil data mesin dan inspector untuk dropdown
$machines = $pdo->query("SELECT * FROM machines ORDER BY nama_mesin ASC")->fetchAll(PDO::FETCH_ASSOC);
$inspectors = $pdo->query("SELECT * FROM inspectors ORDER BY nama_inspector ASC")->fetchAll(PDO::FETCH_ASSOC);
$reject_docs = $pdo->query("SELECT id, no_dokumen, nama_dokumen, machine_id FROM documents WHERE status = 'Reject' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Proteksi Role: Hanya Admin yang boleh akses Input Dokumen
if ($_SESSION['role'] === 'Manager') {
    header("Location: index.php");
    exit;
}

// Pre-fill logic for Traceability/Follow-up
$parent_id = $_GET['p_id'] ?? '';
$pre_machine = $_GET['m_id'] ?? '';
$pre_batch = $_GET['prod'] ?? '';
$mode = $_GET['mode'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_dokumen'] ?? 'Dokumen Baru';
    $produk = $_POST['produk'] ?? '-';
    $jenis = $_POST['jenis'];
    $tanggal = $_POST['tanggal'];
    $inspector = $_POST['inspector'] ?? 'System';
    $machine_id = $_POST['machine_id'] ?? '-';
    $status = $_POST['status'];
    $deskripsi = $_POST['deskripsi'] ?? '';
    $parent_doc_id = $_POST['parent_doc_id'] ?? null;
    $ph = $_POST['ph'] ?? '-';
    $tds = $_POST['tds'] ?? '-';
    $kekeruhan = $_POST['kekeruhan'] ?? '-';
    $admin_entry_name = 'Admin Data Entry QC';

    // RUMUS ID DOKUMEN: QC-[CODE]-[YYYYMM]-[SEQ]
    $codes = [
        'Catatan_Batch' => 'BTCH',
        'Uji_Lab' => 'LABS',
        'Diagnosis_Mesin' => 'DIAG',
        'Laporan_Perbaikan' => 'REPR',
        'Uji_Ulang' => 'RETS',
        'Approval_Manager' => 'APPR'
    ];
    $prefix = $codes[$jenis] ?? 'MISC';
    $yearMonth = date("ym", strtotime($tanggal));
    
    // Hitung sequence
    $stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE no_dokumen LIKE ?");
    $stmtSeq->execute(["QC-$prefix-$yearMonth-%"]);
    $count = $stmtSeq->fetchColumn() + 1;
    $sequence = str_pad($count, 3, "0", STR_PAD_LEFT);
    $no_dokumen = "QC-$prefix-$yearMonth-$sequence";
    
    // Auto-generate name based on type
    if(empty($nama) || $nama == 'Dokumen Baru') {
        $nama_clean = str_replace('_', ' ', $jenis);
        $nama = "$nama_clean - $no_dokumen";
    }

    // Penentuan Folder Otomatis
    $timestamp = strtotime($tanggal);
    $tahun = date("Y", $timestamp);
    if ($jenis == 'Diagnosis_Mesin' || $jenis == 'Laporan_Perbaikan') {
        $folder_path = "QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/{$tahun}";
    } else {
        $bulan = date("F", $timestamp);
        $folder_path = "QC_AMDK/{$produk}/{$tahun}/{$bulan}";
    }

    $stmt = $pdo->prepare("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, deskripsi, folder_path, parent_doc_id, ph, tds, kekeruhan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$no_dokumen, $nama, $produk, $jenis, $tanggal, $inspector, $machine_id, $admin_entry_name, $status, $deskripsi, $folder_path, $parent_doc_id, $ph, $tds, $kekeruhan]);

    header("Location: index.php?path=" . $folder_path);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Dokumen QC - Mineral Pure</title>
</head>
<body class="bg-slate-50">
    <?php include 'sidebar.php'; ?>

    <div class="max-w-4xl mx-auto py-8">
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-blue-50/30">
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase tracking-tight">Form Input QC</h1>
                    <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest opacity-70">Sistem Pencatatan Mineral Pure</p>
                </div>
                <a href="index.php" class="text-slate-400 hover:text-blue-600 transition-colors">✕</a>
            </div>

            <div class="p-8">
                <form action="add.php" method="POST" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Data Batch -->
                        <div class="space-y-6">
                            <h3 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-50 pb-2">Data Batch & Laporan</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Jenis Laporan</label>
                                    <select name="jenis" id="jenisSelect" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-600 transition-all font-bold text-sm">
                                        <option value="Uji_Lab" <?= $mode == 'LAB' ? 'selected' : '' ?>>🔬 Analisis Laboratorium</option>
                                        <option value="Catatan_Batch">📋 Catatan Produksi</option>
                                        <option value="Diagnosis_Mesin" <?= $mode == 'MAINTENANCE' ? 'selected' : '' ?>>⚙️ Diagnosis Teknik</option>
                                        <option value="Laporan_Perbaikan">🛠️ Tindakan Perbaikan</option>
                                        <option value="Uji_Ulang">🧪 Verifikasi Uji Ulang</option>
                                        <option value="Approval_Manager">⚖️ Otorisasi Manager</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Nama Laporan</label>
                                        <input type="text" name="nama_dokumen" placeholder="Auto-Title if Empty" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-600 text-sm font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Tanggal</label>
                                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-600 text-sm font-bold">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Atribusi -->
                        <div class="space-y-6">
                            <h3 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-50 pb-2">Atribusi Operasional</h3>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Unit Mesin</label>
                                        <select name="machine_id" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-600 text-sm font-bold">
                                            <option value="">- Pilih Mesin -</option>
                                            <?php foreach($machines as $m): ?>
                                                <option value="<?= $m['nama_mesin'] ?>" <?= ($pre_machine == $m['nama_mesin']) ? 'selected' : '' ?>><?= $m['nama_mesin'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Inspector</label>
                                        <select name="inspector" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-600 text-sm font-bold">
                                            <?php foreach($inspectors as $i): ?>
                                                <option value="<?= $i['nama_inspector'] ?>"><?= $i['nama_inspector'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div id="produkGroup">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Varian Produk</label>
                                    <select name="produk" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-600 text-sm font-bold">
                                        <option value="-">- Bukan Produk -</option>
                                        <option value="Botol_600ml" <?= ($pre_batch == 'Botol_600ml') ? 'selected' : '' ?>>Botol 600ml</option>
                                        <option value="Botol_330ml">Botol 330ml</option>
                                        <option value="Galon_19L" <?= ($pre_batch == 'Galon_19L') ? 'selected' : '' ?>>Galon 19L</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Param Lab -->
                        <div id="paramGroup" class="md:col-span-2 hidden bg-blue-50/50 p-6 rounded-2xl border border-blue-100 grid grid-cols-3 gap-6">
                            <div class="col-span-3 text-[10px] font-black text-blue-600 uppercase mb-2">Parameter Hasil Laboratorium</div>
                            <div>
                                <label class="block text-[8px] font-black text-slate-400 uppercase mb-1 text-center">pH</label>
                                <input type="text" name="ph" placeholder="7.0" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-center font-bold text-xl outline-none focus:border-blue-600">
                            </div>
                            <div>
                                <label class="block text-[8px] font-black text-slate-400 uppercase mb-1 text-center">TDS (PPM)</label>
                                <input type="text" name="tds" placeholder="85" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-center font-bold text-xl outline-none focus:border-blue-600">
                            </div>
                            <div>
                                <label class="block text-[8px] font-black text-slate-400 uppercase mb-1 text-center">TURBID (NTU)</label>
                                <input type="text" name="kekeruhan" placeholder="0.1" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-center font-bold text-xl outline-none focus:border-blue-600">
                            </div>
                        </div>

                        <!-- Verdict & Traceability -->
                        <div class="md:col-span-1">
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Verdict (Status Akhir)</label>
                            <select name="status" id="statusSelect" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl outline-none transition-all font-black text-sm uppercase">
                                <option value="Lolos">PASSED (Sesuai)</option>
                                <option value="Reject">REJECT (Gagal)</option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Traceability (Follow-up DR)</label>
                            <select name="parent_doc_id" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs font-bold">
                                <option value="">- Laporan Baru -</option>
                                <?php foreach($reject_docs as $rd): ?>
                                    <option value="<?= $rd['id'] ?>" <?= ($parent_id == $rd['id']) ? 'selected' : '' ?>><?= $rd['no_dokumen'] ?> - <?= $rd['nama_dokumen'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Desc -->
                        <div id="descGroup" class="md:col-span-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Catatan Khusus</label>
                            <textarea name="deskripsi" class="w-full h-32 px-4 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-600 outline-none transition-all font-medium text-sm resize-none" placeholder="Tulis rincian diagnosis atau tindakan perbaikan..."></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-12 pt-8 border-t border-slate-100">
                        <div id="rejectWarning" class="hidden text-rose-500 font-bold text-[10px] uppercase">⚠️ Status REJECT akan memicu peringatan sistem.</div>
                        <div class="flex gap-4 ml-auto">
                            <a href="index.php" class="px-8 py-3 text-slate-400 font-bold uppercase text-[9px]">Batal</a>
                            <button type="submit" class="px-8 py-3 bg-blue-600 text-white font-bold uppercase text-[9px] rounded-xl shadow-lg shadow-blue-600/10 hover:bg-blue-700 transition-all">Simpan Laporan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const statusSelect = document.getElementById('statusSelect');
            const jenisSelect = document.getElementById('jenisSelect');
            const rejectWarning = document.getElementById('rejectWarning');
            const paramGroup = document.getElementById('paramGroup');
            const descGroup = document.getElementById('descGroup');
            const produkGroup = document.getElementById('produkGroup');

            function updateUI() {
                const val = jenisSelect.value;
                paramGroup.classList.toggle('hidden', !(val === 'Uji_Lab' || val === 'Uji_Ulang'));
                descGroup.classList.toggle('hidden', !(val === 'Diagnosis_Mesin' || val === 'Laporan_Perbaikan' || val === 'Approval_Manager'));
                produkGroup.classList.toggle('hidden', (val === 'Diagnosis_Mesin' || val === 'Laporan_Perbaikan'));

                if(statusSelect.value === 'Reject') {
                    statusSelect.className = 'w-full px-4 py-3 bg-rose-50 border border-rose-200 rounded-xl outline-none font-black text-sm text-rose-600 uppercase';
                    rejectWarning.classList.remove('hidden');
                } else {
                    statusSelect.className = 'w-full px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl outline-none font-black text-sm text-emerald-600 uppercase';
                    rejectWarning.classList.add('hidden');
                }
            }

            jenisSelect.addEventListener('change', updateUI);
            statusSelect.addEventListener('change', updateUI);
            updateUI();
        });
    </script>
</body>
</html>