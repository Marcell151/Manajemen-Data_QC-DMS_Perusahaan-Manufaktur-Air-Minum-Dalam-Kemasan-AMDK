<?php
require 'db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_dokumen'];
    $produk = $_POST['produk'];
    $jenis = $_POST['jenis'];
    $tanggal = $_POST['tanggal'];
    $inspector = $_POST['inspector'];
    $status = $_POST['status'];
    $link = $_POST['link'];
    $deskripsi = $_POST['deskripsi'];

    $timestamp = strtotime($tanggal);
    $tahun = date("Y", $timestamp);
    $bulan = date("F", $timestamp); // Menghasilkan nama bulan
    $folder_path = "QC_AMDK/{$produk}/{$tahun}/{$bulan}";

    $stmt = $pdo->prepare("INSERT INTO documents (nama_dokumen, produk, jenis, tanggal, inspector, status, link, deskripsi, folder_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama, $produk, $jenis, $tanggal, $inspector, $status, $link, $deskripsi, $folder_path]);
    header("Location: index.php?path=" . $folder_path);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Dokumen QC</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            tinymce.init({ selector: 'textarea#deskripsi', height: 250, menubar: false, toolbar: 'bold italic | bullist numlist | link' });
        });
    </script>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="box-wrapper">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Form Input Inspeksi QC</h3>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>Judul / Nama Dokumen</label>
                    <input type="text" name="nama_dokumen" class="form-control" placeholder="Contoh: Laporan QC Shift 1"
                        required>
                </div>
                <div class="form-group">
                    <label>Link G-Drive / URL Dokumen Asli</label>
                    <input type="url" name="link" class="form-control" placeholder="https://..." required>
                </div>

                <div class="form-group">
                    <label>Pilih Produk AMDK</label>
                    <select name="produk" class="form-control" required>
                        <option value="Produk_600ml">Botol 600ml</option>
                        <option value="Produk_1L">Botol 1 Liter</option>
                        <option value="Produk_Galon">Galon 19 Liter</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Inspeksi</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label>Jenis Dokumen</label>
                    <select name="jenis" class="form-control" required>
                        <option value="Inspection">Inspection (Inspeksi Visual)</option>
                        <option value="Checklist">Checklist Harian Mesin</option>
                        <option value="Reject_Report">Laporan Barang Reject</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Akhir</label>
                    <select name="status" class="form-control" required>
                        <option value="Lolos">Lolos QC (Passed)</option>
                        <option value="Reject">Reject / Gagal</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Nama Petugas (Inspector)</label>
                    <input type="text" name="inspector" class="form-control" placeholder="Nama Anda" required>
                </div>

                <div class="form-group full">
                    <label>Catatan Tambahan (Bila ada kerusakan/anomali)</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control"></textarea>
                </div>
            </div>

            <div style="margin-top:20px; text-align:right;">
                <a href="index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" style="margin-left:10px;">Simpan Dokumen</button>
            </div>
        </form>
    </div>
    </div>
    </div>
</body>

</html>