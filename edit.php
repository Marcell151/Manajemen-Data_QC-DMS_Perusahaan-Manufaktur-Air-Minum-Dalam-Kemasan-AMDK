<?php
require 'db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_dokumen'];
    $produk = $_POST['produk'];
    $jenis = $_POST['jenis'];
    $tanggal = $_POST['tanggal'];
    $inspector = $_POST['inspector'];
    $machine_id = $_POST['machine_id'] ?? '-';
    $status = $_POST['status'];
    $external_link = $_POST['external_link'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    
    $ph = $_POST['ph'] ?? null;
    $tds = $_POST['tds'] ?? null;
    $kekeruhan = $_POST['kekeruhan'] ?? null;

    // Logika Folder Path
    $timestamp = strtotime($tanggal);
    $tahun = date("Y", $timestamp);
    if ($jenis == 'Diagnosis_Mesin' || $jenis == 'Laporan_Perbaikan') {
        $new_folder_path = "QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/{$tahun}";
    } else {
        $bulan = date("F", $timestamp);
        $new_folder_path = "QC_AMDK/{$produk}/{$tahun}/{$bulan}";
    }

    $sql = "UPDATE documents SET 
            nama_dokumen = ?, produk = ?, jenis = ?, tanggal = ?, 
            inspector = ?, machine_id = ?, status = ?, external_link = ?, deskripsi = ?, 
            folder_path = ?, ph = ?, tds = ?, kekeruhan = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $produk, $jenis, $tanggal, $inspector, $machine_id, $status, $external_link, $deskripsi, $new_folder_path, $ph, $tds, $kekeruhan, $id]);

    header("Location: view.php?id=" . $id);
    exit;
}

// Data Master
$machines = $pdo->query("SELECT nama_mesin FROM machines ORDER BY nama_mesin")->fetchAll(PDO::FETCH_ASSOC);
$inspectors = $pdo->query("SELECT nama_inspector FROM inspectors ORDER BY nama_inspector")->fetchAll(PDO::FETCH_ASSOC);
$step_mapping = ['Catatan_Batch', 'Uji_Lab', 'Diagnosis_Mesin', 'Laporan_Perbaikan', 'Uji_Ulang', 'Approval_Manager'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dokumen QC - <?= htmlspecialchars($doc['nama_dokumen']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-container { height: 100vh; position: sticky; top: 0; }
        .form-card { background: white; border-radius: 24px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; }
        .btn-save { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; padding: 16px 32px; border-radius: 16px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease; box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.4); }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 15px 35px -5px rgba(2, 132, 199, 0.5); }
        label { display: block; font-size: 11px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
        input[type="text"], input[type="date"], select, textarea, input[type="url"], input[type="number"] { width: 100%; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 16px; padding: 16px 20px; font-size: 14px; font-weight: 700; color: #334155; transition: all 0.3s ease; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: #0ea5e9; background: white; box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); }
    </style>
</head>
<body class="text-slate-800">

    <?php $current_page = 'index.php'; include 'sidebar.php'; ?>

    <div class="p-4 md:p-8 max-w-6xl mx-auto w-full">
        <div class="mb-10">
            <div class="inline-block px-4 py-2 bg-amber-100 text-amber-700 rounded-xl text-[10px] font-black uppercase tracking-widest mb-4 border border-amber-200">Mode Koreksi Data</div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900 drop-shadow-sm">Edit Laporan: <?= htmlspecialchars($doc['no_dokumen']) ?></h1>
            <p class="text-sm font-bold text-slate-500 mt-2">Hati-hati, perubahan data di sini akan tercatat dalam audit log sistem.</p>
        </div>

        <form action="" method="POST" class="form-card">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16">
                <div class="space-y-10">
                    <div class="bg-amber-50 p-6 md:p-8 rounded-3xl border border-amber-100">
                        <label class="text-amber-700">Tahapan Alur Kerja</label>
                        <select name="jenis" id="jenisSelect" required class="border-amber-200 focus:border-amber-500">
                            <?php foreach ($step_mapping as $val): ?>
                                <option value="<?= $val ?>" <?= ($doc['jenis'] == $val) ? 'selected' : '' ?>><?= str_replace('_', ' ', $val) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Nama Laporan (Judul di Kertas)</label>
                        <input type="text" name="nama_dokumen" value="<?= htmlspecialchars($doc['nama_dokumen']) ?>" required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 md:gap-8">
                        <div>
                            <label>Tanggal Laporan</label>
                            <input type="date" name="tanggal" value="<?= htmlspecialchars($doc['tanggal']) ?>" required>
                        </div>
                        <div>
                            <label>Lini Produk</label>
                            <select name="produk" required>
                                <option value="Mineral_600ml" <?= ($doc['produk'] == 'Mineral_600ml') ? 'selected' : '' ?>>Mineral 600ml</option>
                                <option value="Mineral_330ml" <?= ($doc['produk'] == 'Mineral_330ml') ? 'selected' : '' ?>>Mineral 330ml</option>
                                <option value="Cup_240ml" <?= ($doc['produk'] == 'Cup_240ml') ? 'selected' : '' ?>>Cup 240ml</option>
                                <option value="Galon_19L" <?= ($doc['produk'] == 'Galon_19L') ? 'selected' : '' ?>>Galon 19L</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 md:gap-8">
                        <div>
                            <label>Kode Mesin</label>
                            <select name="machine_id" required>
                                <?php foreach ($machines as $m): ?>
                                    <option value="<?= $m['nama_mesin'] ?>" <?= ($doc['machine_id'] == $m['nama_mesin']) ? 'selected' : '' ?>><?= $m['nama_mesin'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Petugas Lapangan</label>
                            <select name="inspector" required>
                                <?php foreach ($inspectors as $i): ?>
                                    <option value="<?= $i['nama_inspector'] ?>" <?= ($doc['inspector'] == $i['nama_inspector']) ? 'selected' : '' ?>><?= $i['nama_inspector'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Lab Parameters -->
                    <div id="labParametersSection" class="hidden">
                        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-200">
                            <label class="mb-4 text-slate-800 block font-black">Parameter Uji Laboratorium</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                <div>
                                    <label class="text-[10px] text-slate-500">pH Air</label>
                                    <input type="number" step="0.01" name="ph" value="<?= htmlspecialchars($doc['ph'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-500">TDS (PPM)</label>
                                    <input type="number" step="0.01" name="tds" value="<?= htmlspecialchars($doc['tds'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-500">Kekeruhan (NTU)</label>
                                    <input type="number" step="0.01" name="kekeruhan" value="<?= htmlspecialchars($doc['kekeruhan'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-10">
                    <div>
                        <label>Keputusan Akhir (Verdict)</label>
                        <div class="flex gap-6">
                            <label class="flex-grow cursor-pointer group">
                                <input type="radio" name="status" value="Passed" <?= ($doc['status'] == 'Passed' || $doc['status'] == 'Lolos') ? 'checked' : '' ?> class="hidden peer">
                                <div class="p-6 md:p-8 border-2 border-slate-100 rounded-3xl text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all group-hover:bg-slate-50">
                                    <span class="block text-4xl mb-2">✓</span>
                                    <span class="text-xs font-black uppercase text-slate-400 peer-checked:text-emerald-700 tracking-widest">LOLOS</span>
                                </div>
                            </label>
                            <label class="flex-grow cursor-pointer group">
                                <input type="radio" name="status" value="Reject" <?= ($doc['status'] == 'Reject') ? 'checked' : '' ?> class="hidden peer">
                                <div class="p-6 md:p-8 border-2 border-slate-100 rounded-3xl text-center peer-checked:border-rose-500 peer-checked:bg-rose-50 transition-all group-hover:bg-slate-50">
                                    <span class="block text-4xl mb-2">✗</span>
                                    <span class="text-xs font-black uppercase text-slate-400 peer-checked:text-rose-700 tracking-widest">REJECT</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-6 md:p-8 rounded-3xl border border-slate-200">
                        <label class="mb-3 text-slate-700">Ubah Tautan Dokumen Cloud (G-Drive / OneDrive)</label>
                        <input type="url" name="external_link" value="<?= htmlspecialchars($doc['external_link'] ?? '') ?>" placeholder="https://..." class="w-full">
                        <p class="text-[9px] text-slate-400 mt-3 font-bold leading-relaxed">*Catatan: Untuk mengubah file scan fisik, Anda harus menghapus laporan ini dan membuat yang baru.</p>
                    </div>

                    <div>
                        <label>Catatan Temuan Lapangan</label>
                        <textarea name="deskripsi" rows="5" placeholder="Tuliskan temuan anomali..."><?= htmlspecialchars(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $doc['deskripsi'] ?? ''))) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-16 pt-12 border-t border-slate-100 flex flex-col sm:flex-row justify-end items-center gap-6 md:gap-12">
                <a href="view.php?id=<?= $id ?>" class="text-sm font-black text-slate-400 uppercase tracking-widest hover:text-rose-600 transition-all">Batal & Kembali</a>
                <button type="submit" class="btn-save w-full sm:w-auto bg-amber-500 hover:bg-amber-600 shadow-amber-500/30">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const jenisSelect = document.getElementById('jenisSelect');
            const labSection = document.getElementById('labParametersSection');
            
            function toggleLabParams() {
                if(jenisSelect.value === 'Uji_Lab' || jenisSelect.value === 'Uji_Ulang') {
                    labSection.classList.remove('hidden');
                } else {
                    labSection.classList.add('hidden');
                }
            }
            
            jenisSelect.addEventListener('change', toggleLabParams);
            toggleLabParams(); // Execute on load
        });
    </script>
    </main>
    </div>
    </div>
</body>
</html>