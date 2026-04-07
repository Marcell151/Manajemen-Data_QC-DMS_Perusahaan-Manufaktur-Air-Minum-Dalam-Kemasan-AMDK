<?php
require 'db.php';

// Validasi ID dokumen yang akan diedit
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Ambil data lama dokumen berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika dokumen tidak ditemukan, kembalikan ke index
if (!$doc) {
    header("Location: index.php");
    exit;
}

// Proses HANYA jika form disubmit (Metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_dokumen'];
    $produk = $_POST['produk'];
    $jenis = $_POST['jenis'];
    $tanggal = $_POST['tanggal'];
    $inspector = $_POST['inspector'];
    $status = $_POST['status'];
    $link = $_POST['link'];
    $deskripsi = $_POST['deskripsi'];

    // LOGIC PENTING: Hitung ulang Folder Path (jika Produk atau Tanggal berubah)
    $timestamp = strtotime($tanggal);
    $tahun = date("Y", $timestamp);
    $bulan = date("F", $timestamp); // Menghasilkan nama bulan
    $new_folder_path = "QC_AMDK/{$produk}/{$tahun}/{$bulan}";

    // Query UPDATE
    $sql = "UPDATE documents SET 
            nama_dokumen = ?, 
            produk = ?, 
            jenis = ?, 
            tanggal = ?, 
            inspector = ?, 
            status = ?, 
            link = ?, 
            deskripsi = ?, 
            folder_path = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $produk, $jenis, $tanggal, $inspector, $status, $link, $deskripsi, $new_folder_path, $id]);

    // Setelah sukses, arahkan kembali ke folder tempat dokumen tersebut berada
    header("Location: index.php?path=" . $new_folder_path);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Dokumen QC: <?= htmlspecialchars($doc['nama_dokumen']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            tinymce.init({
                selector: 'textarea#deskripsi',
                height: 250,
                menubar: false,
                toolbar: 'bold italic | bullist numlist | link | undo redo'
            });
        });
    </script>
</head>

<body>

    <?php
    // Trick agar menu 'Dashboard' tetap aktif di sidebar saat mengedit
    $current_page = 'index.php';
    include 'sidebar.php';
    ?>

    <div class="box-wrapper">
        <div
            style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
            <h3 style="margin:0; color:#2c3e50;">✍️ Edit Dokumen</h3>
            <span style="font-size:12px; color:#7f8c8d;">ID Dokumen: #<?= $doc['id'] ?></span>
        </div>

        <form method="POST" action="">
            <div class="form-grid">

                <div class="form-group full">
                    <label>Judul / Nama Dokumen</label>
                    <input type="text" name="nama_dokumen" class="form-control"
                        value="<?= htmlspecialchars($doc['nama_dokumen']) ?>" required>
                </div>

                <div class="form-group full">
                    <label>Link G-Drive / URL Dokumen Asli (Hanya Link)</label>
                    <input type="url" name="link" class="form-control" value="<?= htmlspecialchars($doc['link']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Produk AMDK</label>
                    <select name="produk" class="form-control" required>
                        <option value="Produk_600ml" <?= $doc['produk'] == 'Produk_600ml' ? 'selected' : '' ?>>Botol 600ml
                        </option>
                        <option value="Produk_1L" <?= $doc['produk'] == 'Produk_1L' ? 'selected' : '' ?>>Botol 1 Liter
                        </option>
                        <option value="Produk_Galon" <?= $doc['produk'] == 'Produk_Galon' ? 'selected' : '' ?>>Galon 19
                            Liter</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tanggal Inspeksi</label>
                    <input type="date" name="tanggal" class="form-control"
                        value="<?= htmlspecialchars($doc['tanggal']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Jenis Dokumen</label>
                    <select name="jenis" class="form-control" required>
                        <option value="Inspection" <?= $doc['jenis'] == 'Inspection' ? 'selected' : '' ?>>Inspection
                            (Inspeksi Visual)</option>
                        <option value="Checklist" <?= $doc['jenis'] == 'Checklist' ? 'selected' : '' ?>>Checklist Harian
                            Mesin</option>
                        <option value="Reject_Report" <?= $doc['jenis'] == 'Reject_Report' ? 'selected' : '' ?>>Laporan
                            Barang Reject</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Akhir</label>
                    <select name="status" class="form-control" required>
                        <option value="Lolos" <?= $doc['status'] == 'Lolos' ? 'selected' : '' ?>>Lolos QC (Passed)</option>
                        <option value="Reject" <?= $doc['status'] == 'Reject' ? 'selected' : '' ?>>Reject / Gagal</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Nama Petugas (Inspector)</label>
                    <input type="text" name="inspector" class="form-control"
                        value="<?= htmlspecialchars($doc['inspector']) ?>" required>
                </div>

                <div class="form-group full">
                    <label>Catatan Tambahan (Rich Text)</label>
                    <textarea id="deskripsi" name="deskripsi"
                        class="form-control"><?= htmlspecialchars($doc['deskripsi']) ?></textarea>
                </div>
            </div>

            <div style="margin-top:30px; text-align:right; padding-top:15px; border-top:1px solid #eee;">
                <a href="index.php?path=<?= $doc['folder_path'] ?>" class="btn btn-secondary">Batal / Kembali</a>
                <button type="submit" class="btn btn-primary"
                    style="margin-left:10px; background-color: #f1c40f; color: #2c3e50;">Update & Simpan
                    Perubahan</button>
            </div>
        </form>
    </div>

    </div>
    </div>
</body>

</html>