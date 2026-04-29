<?php
require 'db.php';

// Ambil data mesin dan inspector untuk dropdown
$machines = $pdo->query("SELECT * FROM machines ORDER BY nama_mesin ASC")->fetchAll(PDO::FETCH_ASSOC);
$inspectors = $pdo->query("SELECT * FROM inspectors ORDER BY nama_inspector ASC")->fetchAll(PDO::FETCH_ASSOC);

// Daftar Produk Standar
$product_list = [
    'Mineral_600ml' => 'Mineral 600ml',
    'Mineral_330ml' => 'Mineral 330ml',
    'Cup_240ml' => 'Cup 240ml',
    'Galon_19L' => 'Galon 19L'
];

// Mapping step ke jenis dokumen
$step_mapping = [
    '1' => 'Catatan_Batch',
    '2' => 'Uji_Lab',
    '3' => 'Diagnosis_Mesin',
    '4' => 'Laporan_Perbaikan',
    '5' => 'Uji_Ulang',
    '6' => 'Approval_Manager'
];

$step = $_GET['step'] ?? '';
$is_fixed_step = !empty($step); // Jika ada parameter step, maka langkah dikunci
$current_step_num = $step ?: '1';
$pre_jenis = $step_mapping[$current_step_num] ?? 'Catatan_Batch';

// PROTEKSI HAK AKSES INPUT (Berdasarkan Role)
$can_access_input = false;
if ($_SESSION['role'] == 'Pekerja_Lapangan') {
    if (!$is_fixed_step || in_array($step, ['1', '3', '4', '5'])) $can_access_input = true;
} elseif ($_SESSION['role'] == 'Admin_Entry') {
    if (!$is_fixed_step || $step == '2') $can_access_input = true;
}

if (!$can_access_input && $_SESSION['role'] !== 'Manager') {
    header("Location: index.php");
    exit;
}

// Untuk Step > 01, ambil daftar Laporan Induk (Step 01) yang aktif
$parent_options = [];
if ($current_step_num != '1') {
    $parent_options = $pdo->query("SELECT id, no_dokumen, produk, machine_id FROM documents WHERE jenis = 'Catatan_Batch' ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
}

// Logic Simpan
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
    $external_link = $_POST['external_link'] ?? '';
    $ph = $_POST['ph'] ?? null;
    $tds = $_POST['tds'] ?? null;
    $kekeruhan = $_POST['kekeruhan'] ?? null;
    $file_path = '';
    
    if (isset($_FILES['dokumen_fisik']) && $_FILES['dokumen_fisik']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = time() . '_' . basename($_FILES['dokumen_fisik']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['dokumen_fisik']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        }
    }

    $codes = ['Catatan_Batch' => 'BTCH', 'Uji_Lab' => 'LABS', 'Diagnosis_Mesin' => 'DIAG', 'Laporan_Perbaikan' => 'REPR', 'Uji_Ulang' => 'RETS', 'Approval_Manager' => 'APPR'];
    $prefix = $codes[$jenis] ?? 'MISC';
    $yearMonth = date("ym", strtotime($tanggal));
    $stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE no_dokumen LIKE ?");
    $stmtSeq->execute(["QC-$prefix-$yearMonth-%"]);
    $count = $stmtSeq->fetchColumn() + 1;
    $sequence = str_pad($count, 3, "0", STR_PAD_LEFT);
    $no_dokumen = "QC-$prefix-$yearMonth-$sequence";
    
    if(empty($nama) || $nama == 'Dokumen Baru') {
        $nama_clean = str_replace('_', ' ', $jenis);
        $nama = "$nama_clean - $no_dokumen";
    }

    $timestamp = strtotime($tanggal);
    $tahun = date("Y", $timestamp);
    $bulan = date("F", $timestamp);
    $folder_path = "QC_AMDK/{$produk}/{$tahun}/{$bulan}";

    $approval_status = ($jenis == 'Approval_Manager') ? 'Waiting Approval' : '-';

    $stmt = $pdo->prepare("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, deskripsi, folder_path, parent_doc_id, file_path, approval_status, external_link, ph, tds, kekeruhan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$no_dokumen, $nama, $produk, $jenis, $tanggal, $inspector, $machine_id, $_SESSION['role'], $status, $deskripsi, $folder_path, $parent_doc_id, $file_path, $approval_status, $external_link, $ph, $tds, $kekeruhan]);

    header("Location: index.php");
    exit;
}

$is_mobile_mode = in_array($current_step_num, ['1', '3', '4', '5']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input QC - Mineral Pure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Outfit:wght@500;800&display=swap');
        :root { --primary: #0284c7; --bg-main: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); color: #1e293b; }
        h1 { font-family: 'Outfit', sans-serif; }
        .form-card { background: white; border-radius: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        label { display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 0.75rem; }
        input, select, textarea { width: 100%; padding: 1rem 1.25rem; border-radius: 16px; border: 1px solid #cbd5e1; font-size: 1rem; font-weight: 600; color: #1e293b; transition: all 0.2s; background: #fdfdfd; }
        input:focus, select:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.1); background: white; }
        .btn-save { background: #0f172a; color: white; padding: 1.25rem 3rem; border-radius: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.875rem; transition: all 0.2s; cursor: pointer; border: none; }
        .btn-save:hover { background: var(--primary); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(2, 132, 199, 0.2); }
        .camera-btn { background: #0284c7; color: white; padding: 2rem; border-radius: 24px; text-align: center; cursor: pointer; transition: all 0.2s; border: 4px dashed rgba(255,255,255,0.3); }
        .camera-btn:hover { background: #0369a1; transform: scale(1.02); }
    </style>
</head>
<body class="antialiased">
    <?php include 'sidebar.php'; ?>

    <div class="p-4 max-w-5xl mx-auto">
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <p class="text-[10px] font-black text-sky-600 uppercase tracking-[0.3em] mb-1">Entry Sistem Mutu</p>
                <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">Input Bukti Lapangan</h1>
            </div>
            <button type="button" onclick="printBlankForm()" class="no-print px-5 py-3 bg-white border border-slate-200 text-slate-500 text-[10px] font-black uppercase rounded-xl hover:bg-slate-900 hover:text-white transition-all">🖨️ Cetak Form Kosong</button>
        </div>

        <form action="add.php?step=<?= $step ?>" method="POST" enctype="multipart/form-data" class="form-card p-6 md:p-12">
            
            <div class="<?= $is_mobile_mode ? 'flex flex-col gap-10' : 'grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20' ?>">
                
                <div class="space-y-8">
                    <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                        <label>Langkah Alur Kerja</label>
                        <select name="jenis" id="jenisSelect" onchange="window.location.href='add.php?step=' + this.options[this.selectedIndex].getAttribute('data-step')" <?= $is_fixed_step ? 'readonly class="bg-slate-100 pointer-events-none opacity-70"' : '' ?>>
                            <?php foreach ($step_mapping as $k => $val): 
                                // Filter berdasarkan role untuk transparansi input
                                $show_option = false;
                                if ($_SESSION['role'] == 'Pekerja_Lapangan' && in_array($k, ['1', '3', '4', '5'])) $show_option = true;
                                if ($_SESSION['role'] == 'Admin_Entry' && $k == '2') $show_option = true;
                                if ($_SESSION['role'] == 'Manager' && $k == '6') $show_option = true;
                                
                                if (!$show_option) continue;
                            ?>
                                <option value="<?= $val ?>" data-step="<?= $k ?>" <?= ($current_step_num == $k) ? 'selected' : '' ?>><?= $val[0].$val[1] ?>. <?= str_replace('_', ' ', $val) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($current_step_num != '1'): ?>
                    <div class="bg-sky-50 p-6 rounded-3xl border border-sky-100">
                        <label class="text-sky-700">Pilih Laporan Induk (Batch Sampling)</label>
                        <select name="parent_doc_id" id="parentSelect" required onchange="autoFillMetadata()">
                            <option value="">-- Pilih Batch Yang Sedang Berjalan --</option>
                            <?php foreach ($parent_options as $p): ?>
                                <option value="<?= $p['id'] ?>" data-prod="<?= $p['produk'] ?>" data-machine="<?= $p['machine_id'] ?>">
                                    <?= $p['no_dokumen'] ?> (<?= str_replace('_', ' ', $p['produk']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[9px] text-sky-500 mt-2 font-bold italic">*Pilih ini agar data Batch & Produk terisi otomatis.</p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label>Tanggal Pelaporan</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label>Lini Produk</label>
                            <select name="produk" id="produkSelect" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($product_list as $key => $val): ?>
                                    <option value="<?= $key ?>"><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Kode Mesin</label>
                            <select name="machine_id" id="machineSelect" required>
                                <option value="">-- Pilih Mesin --</option>
                                <?php foreach ($machines as $m): ?>
                                    <option value="<?= $m['nama_mesin'] ?>"><?= $m['nama_mesin'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Petugas Lapangan</label>
                        <select name="inspector" required>
                            <?php foreach ($inspectors as $i): ?>
                                <option value="<?= $i['nama_inspector'] ?>"><?= $i['nama_inspector'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($current_step_num == '2' || $current_step_num == '5'): ?>
                    <div class="p-6 bg-amber-50 rounded-3xl border border-amber-100">
                        <label class="mb-4 text-amber-900 block font-black">Parameter Lab Aktual</label>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="text-[9px]">pH</label><input type="number" step="0.1" name="ph" class="p-2 text-sm"></div>
                            <div><label class="text-[9px]">TDS</label><input type="number" step="1" name="tds" class="p-2 text-sm"></div>
                            <div><label class="text-[9px]">NTU</label><input type="number" step="0.01" name="kekeruhan" class="p-2 text-sm"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="space-y-10">
                    <div>
                        <label>Hasil Pemeriksaan / Bukti</label>
                        <div class="flex gap-4">
                            <label class="flex-grow cursor-pointer group">
                                <input type="radio" name="status" value="Passed" checked class="hidden peer">
                                <div class="py-6 border-2 border-slate-100 rounded-2xl text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all">
                                    <span class="block text-2xl mb-1">✓</span>
                                    <span class="text-[10px] font-black text-slate-400 peer-checked:text-emerald-700 uppercase">Lolos</span>
                                </div>
                            </label>
                            <label class="flex-grow cursor-pointer group">
                                <input type="radio" name="status" value="Reject" class="hidden peer">
                                <div class="py-6 border-2 border-slate-100 rounded-2xl text-center peer-checked:border-rose-500 peer-checked:bg-rose-50 transition-all">
                                    <span class="block text-2xl mb-1">✗</span>
                                    <span class="text-[10px] font-black text-slate-400 peer-checked:text-rose-700 uppercase">Reject</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <label>Lampiran Bukti (Foto/PDF)</label>
                        <div class="camera-btn" onclick="document.getElementById('fileInput').click()">
                            <span class="text-4xl block mb-2">📸</span>
                            <span class="text-sm font-black uppercase tracking-widest">Ambil Foto / Unggah Bukti</span>
                            <p class="text-[10px] opacity-60 mt-2">Gunakan kamera tablet untuk bukti lapangan</p>
                        </div>
                        <input type="file" name="dokumen_fisik" id="fileInput" accept="image/*,application/pdf" capture="environment" class="hidden" onchange="updateFileName(this)">
                        <div id="fileStatus" class="text-center text-xs font-bold text-emerald-600 hidden">✅ File Siap Diunggah</div>
                        
                        <div class="pt-4 border-t border-slate-100">
                            <label class="text-[9px]">Atau Gunakan Tautan Cloud (G-Drive)</label>
                            <input type="url" name="external_link" placeholder="https://..." class="p-3 text-sm">
                        </div>
                    </div>

                    <div>
                        <label>Catatan Temuan Lapangan</label>
                        <textarea name="deskripsi" rows="4" placeholder="Tuliskan catatan atau kendala di sini..."></textarea>
                    </div>
                </div>

            </div>

            <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-[10px] text-slate-400 font-bold italic">Pastikan data & foto sudah benar sebelum menyimpan.</p>
                <div class="flex gap-4 w-full md:w-auto">
                    <a href="index.php" class="flex-grow md:flex-grow-0 px-8 py-4 text-center text-xs font-black text-slate-400 uppercase tracking-widest hover:text-rose-600 transition-all">Batal</a>
                    <button type="submit" class="btn-save flex-grow md:flex-grow-0">Kirim Laporan</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function autoFillMetadata() {
            const select = document.getElementById('parentSelect');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                const prod = selectedOption.getAttribute('data-prod');
                const machine = selectedOption.getAttribute('data-machine');
                
                // Update Selects
                const prodSelect = document.getElementById('produkSelect');
                const machineSelect = document.getElementById('machineSelect');
                
                prodSelect.value = prod;
                machineSelect.value = machine;
                
                // Beri efek highlight visual bahwa data berubah
                prodSelect.classList.add('bg-sky-50');
                machineSelect.classList.add('bg-sky-50');
                setTimeout(() => {
                    prodSelect.classList.remove('bg-sky-50');
                    machineSelect.classList.remove('bg-sky-50');
                }, 1000);
            }
        }

        function updateFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileStatus').classList.remove('hidden');
                document.getElementById('fileStatus').innerText = "✅ Berhasil Memuat: " + input.files[0].name;
            }
        }

        function printBlankForm() {
            const jenisSelect = document.getElementById('jenisSelect');
            alert('Membuka Template Cetak Formal untuk ' + jenisSelect.options[jenisSelect.selectedIndex].text);
            window.print();
        }
    </script>
</body>
</html>