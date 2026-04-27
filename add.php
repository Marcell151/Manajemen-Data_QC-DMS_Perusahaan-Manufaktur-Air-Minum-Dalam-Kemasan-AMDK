<?php
require 'db.php';

// Ambil data mesin dan inspector untuk dropdown
$machines = $pdo->query("SELECT * FROM machines ORDER BY nama_mesin ASC")->fetchAll(PDO::FETCH_ASSOC);
$inspectors = $pdo->query("SELECT * FROM inspectors ORDER BY nama_inspector ASC")->fetchAll(PDO::FETCH_ASSOC);

// Proteksi Role: Hanya Admin yang boleh akses Input Dokumen
if ($_SESSION['role'] === 'Manager') {
    header("Location: index.php");
    exit;
}

// Pre-fill logic for Traceability/Follow-up
$parent_id = $_GET['p_id'] ?? '';
$pre_machine = $_GET['m_id'] ?? '';
$pre_batch = $_GET['prod'] ?? '';
$step = $_GET['step'] ?? '';

// Mapping step ke jenis dokumen
$step_mapping = [
    '1' => 'Catatan_Batch',
    '2' => 'Uji_Lab',
    '3' => 'Diagnosis_Mesin',
    '4' => 'Laporan_Perbaikan',
    '5' => 'Uji_Ulang',
    '6' => 'Approval_Manager'
];
$pre_jenis = $step_mapping[$step] ?? '';

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
    $file_path = '';
    
    // Logika Upload File
    if (isset($_FILES['dokumen_fisik']) && $_FILES['dokumen_fisik']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = time() . '_' . basename($_FILES['dokumen_fisik']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['dokumen_fisik']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        }
    }

    // RUMUS ID DOKUMEN: QC-[CODE]-[YYYYMM]-[SEQ]
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
    if ($jenis == 'Diagnosis_Mesin' || $jenis == 'Laporan_Perbaikan') {
        $folder_path = "QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/{$tahun}";
    } else {
        $bulan = date("F", $timestamp);
        $folder_path = "QC_AMDK/{$produk}/{$tahun}/{$bulan}";
    }

    $approval_status = ($jenis == 'Approval_Manager') ? 'Waiting Approval' : '-';

    $stmt = $pdo->prepare("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, deskripsi, folder_path, parent_doc_id, file_path, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$no_dokumen, $nama, $produk, $jenis, $tanggal, $inspector, $machine_id, 'Admin Data Entry QC', $status, $deskripsi, $folder_path, $parent_doc_id, $file_path, $approval_status]);

    header("Location: index.php?path=" . $folder_path);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input QC - Mineral Pure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Outfit:wght@500;800&display=swap');
        :root { --primary: #0284c7; --bg-main: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); color: #1e293b; }
        h1 { font-family: 'Outfit', sans-serif; }
        .form-card { background: white; border-radius: 32px; border: 1px solid #e2e8f0; padding: 3rem; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        label { display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 0.75rem; }
        input, select, textarea { width: 100%; padding: 1rem 1.25rem; border-radius: 16px; border: 1px solid #cbd5e1; font-size: 1rem; font-weight: 600; color: #1e293b; transition: all 0.2s; background: #fdfdfd; }
        input:focus, select:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.1); background: white; }
        .btn-save { background: #0f172a; color: white; padding: 1.25rem 3rem; border-radius: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.875rem; transition: all 0.2s; cursor: pointer; border: none; }
        .btn-save:hover { background: var(--primary); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(2, 132, 199, 0.2); }
    </style>
</head>
<body class="antialiased">
    <?php include 'sidebar.php'; ?>

    <div class="p-4 max-w-6xl mx-auto">
        <div class="mb-12 flex justify-between items-end">
            <div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">Unggah Laporan Mutu</h1>
                <p class="text-slate-500 font-medium italic">Konversi Form Fisik ke Digital • PT. Mineral Pure Indonesia</p>
            </div>
            <button type="button" onclick="printBlankForm()" class="px-6 py-3 bg-white border-2 border-slate-900 text-slate-900 text-xs font-black uppercase rounded-xl hover:bg-slate-900 hover:text-white transition-all shadow-lg">🖨️ Cetak Form Kosong Lapangan</button>
        </div>

        <?php if ($parent_id): ?>
            <div class="mb-10 p-6 bg-sky-50 border border-sky-100 rounded-3xl flex items-center gap-4">
                <span class="text-3xl">🔗</span>
                <div>
                    <p class="text-[10px] font-black text-sky-400 uppercase tracking-widest">Koneksi Traceability</p>
                    <p class="text-base font-bold text-sky-800 tracking-tight">Dokumen ini adalah tindak lanjut dari Laporan #<?= htmlspecialchars($parent_id) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form action="add.php" method="POST" enctype="multipart/form-data" class="form-card">
            <input type="hidden" name="parent_doc_id" value="<?= $parent_id ?>">
            
            <div class="grid grid-cols-2 gap-16">
                <div class="space-y-10">
                    <div class="bg-sky-50 p-8 rounded-3xl border border-sky-100">
                        <label>Tahapan Alur Kerja</label>
                        <select name="jenis" id="jenisSelect" required>
                            <?php foreach ($step_mapping as $val): ?>
                                <option value="<?= $val ?>" <?= ($pre_jenis == $val) ? 'selected' : '' ?>><?= str_replace('_', ' ', $val) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Nama Laporan (Judul di Kertas)</label>
                        <input type="text" name="nama_dokumen" placeholder="Contoh: Laporan Sampling Air MC-01">
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <label>Tanggal Form Diisi</label>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label>Lini Produk</label>
                            <select name="produk" required>
                                <option value="Mineral_600ml" <?= ($pre_batch == 'Mineral_600ml') ? 'selected' : '' ?>>Mineral 600ml</option>
                                <option value="Mineral_330ml" <?= ($pre_batch == 'Mineral_330ml') ? 'selected' : '' ?>>Mineral 330ml</option>
                                <option value="Cup_240ml" <?= ($pre_batch == 'Cup_240ml') ? 'selected' : '' ?>>Cup 240ml</option>
                                <option value="Galon_19L" <?= ($pre_batch == 'Galon_19L') ? 'selected' : '' ?>>Galon 19L</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <label>Kode Mesin</label>
                            <select name="machine_id" required>
                                <?php foreach ($machines as $m): ?>
                                    <option value="<?= $m['nama_mesin'] ?>" <?= ($pre_machine == $m['nama_mesin']) ? 'selected' : '' ?>><?= $m['nama_mesin'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Petugas Lapangan</label>
                            <select name="inspector" required>
                                <?php foreach ($inspectors as $i): ?>
                                    <option value="<?= $i['nama_inspector'] ?>"><?= $i['nama_inspector'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-10">
                    <div>
                        <label>Keputusan Akhir (Verdict)</label>
                        <div class="flex gap-6">
                            <label class="flex-grow cursor-pointer group">
                                <input type="radio" name="status" value="Passed" checked class="hidden peer">
                                <div class="p-8 border-2 border-slate-100 rounded-3xl text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all group-hover:bg-slate-50">
                                    <span class="block text-4xl mb-2">✓</span>
                                    <span class="text-xs font-black uppercase text-slate-400 peer-checked:text-emerald-700 tracking-widest">LOLOS</span>
                                </div>
                            </label>
                            <label class="flex-grow cursor-pointer group">
                                <input type="radio" name="status" value="Reject" class="hidden peer">
                                <div class="p-8 border-2 border-slate-100 rounded-3xl text-center peer-checked:border-rose-500 peer-checked:bg-rose-50 transition-all group-hover:bg-slate-50">
                                    <span class="block text-4xl mb-2">✗</span>
                                    <span class="text-xs font-black uppercase text-slate-400 peer-checked:text-rose-700 tracking-widest">REJECT</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="bg-slate-100 p-10 rounded-3xl border-2 border-dashed border-slate-300 text-center">
                        <label class="mb-6">Scan Dokumen Fisik (Wajib PDF)</label>
                        <input type="file" name="dokumen_fisik" class="text-xs file:bg-slate-900 file:text-white file:border-none file:px-8 file:py-4 file:rounded-2xl file:mr-6 file:font-black file:cursor-pointer hover:file:bg-sky-600 transition-all">
                        <p class="text-[10px] text-slate-400 mt-6 font-bold leading-relaxed">*Pastikan tanda tangan basah dan stempel terlihat jelas pada hasil scan.</p>
                    </div>

                    <div>
                        <label>Ringkasan Temuan Lapangan</label>
                        <textarea name="deskripsi" rows="5" placeholder="Tuliskan temuan anomali atau catatan khusus di sini..."></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-16 pt-12 border-t border-slate-100 flex justify-end items-center gap-12">
                <a href="index.php" class="text-sm font-black text-slate-400 uppercase tracking-widest hover:text-rose-600 transition-all">Batal & Kembali</a>
                <button type="submit" class="btn-save">Simpan Laporan Mutu</button>
            </div>
        </form>
    </div>

    <div id="blankFormTemplate" class="hidden">
        <div style="padding: 0.5in; font-family: 'Times New Roman', serif; color: black;">
            <table style="width: 100%; border-bottom: 3px solid black; padding-bottom: 10px; margin-bottom: 20px;">
                <tr>
                    <td style="width: 60px;"><div style="width: 50px; height: 50px; background: black; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold;">MP</div></td>
                    <td>
                        <h1 style="margin: 0; font-size: 22px; font-weight: bold; text-transform: uppercase;">PT. MINERAL PURE INDONESIA</h1>
                        <p style="margin: 3px 0 0; font-size: 9px; font-weight: bold; text-transform: uppercase;">Quality Control Department - Field Document</p>
                    </td>
                    <td style="text-align: right; vertical-align: bottom;"><div style="border: 2px solid black; padding: 5px 10px; font-weight: bold; font-size: 10px;">FORMULIR KOSONG</div></td>
                </tr>
            </table>
            <h2 id="printTitle" style="text-align: center; text-decoration: underline; text-transform: uppercase; margin-bottom: 30px;">[JUDUL FORMULIR]</h2>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 12px;">
                <tr><td style="border: 1px solid black; padding: 10px; width: 20%; font-weight: bold; background: #eee;">TANGGAL</td><td style="border: 1px solid black; padding: 10px; width: 30%;">____ / ____ / 202____</td><td style="border: 1px solid black; padding: 10px; width: 20%; font-weight: bold; background: #eee;">KODE MESIN</td><td style="border: 1px solid black; padding: 10px; width: 30%;">________________</td></tr>
                <tr><td style="border: 1px solid black; padding: 10px; font-weight: bold; background: #eee;">PRODUK / BATCH</td><td style="border: 1px solid black; padding: 10px;">________________</td><td style="border: 1px solid black; padding: 10px; font-weight: bold; background: #eee;">INSPECTOR</td><td style="border: 1px solid black; padding: 10px;">________________</td></tr>
            </table>
            <div style="border: 1px solid black; padding: 20px; text-align: center; margin-bottom: 30px;">
                <p style="font-weight: bold; margin-bottom: 15px; font-size: 10px;">HASIL PEMERIKSAAN (VERDICT)</p>
                <div style="display: flex; justify-content: center; gap: 60px;"><div>[ ] LOLOS (PASSED)</div><div>[ ] GAGAL (REJECT)</div></div>
            </div>
            <div style="border: 1px solid black; padding: 10px; min-height: 300px; margin-bottom: 30px;">
                <p style="font-weight: bold; text-decoration: underline; font-size: 10px; margin-bottom: 10px;">CATATAN TEMUAN & ANALISIS LAPANGAN:</p>
            </div>
            <table style="width: 100%; text-align: center; font-size: 11px;">
                <tr><td style="padding-bottom: 60px; font-weight: bold;">INSPECTOR QC</td><td style="padding-bottom: 60px; font-weight: bold;">MANAJER PRODUKSI</td></tr>
                <tr><td>( ____________________ )</td><td>( ____________________ )</td></tr>
            </table>
        </div>
    </div>

    <script>
        function printBlankForm() {
            const jenisSelect = document.getElementById('jenisSelect');
            document.getElementById('printTitle').innerText = jenisSelect.options[jenisSelect.selectedIndex].text;
            const printContent = document.getElementById('blankFormTemplate').innerHTML;
            const originalContent = document.body.innerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }
    </script>
    </main>
    </div>
    </div>
</body>
</html>