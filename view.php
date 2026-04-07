<?php
require 'db.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Dokumen</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="box-wrapper">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h3 style="margin-top:0; color:#2c3e50;"><span class="icon-lg">📄</span>
                    <?= htmlspecialchars($doc['nama_dokumen']) ?></h3>
                <p style="color:#7f8c8d; margin-top:-10px;">Berada di folder:
                    <strong><?= htmlspecialchars($doc['folder_path']) ?></strong></p>
            </div>
            <div>
                <a href="index.php?path=<?= $doc['folder_path'] ?>" class="btn btn-secondary">Kembali ke Folder</a>
                <a href="<?= htmlspecialchars($doc['link']) ?>" target="_blank" class="btn btn-primary"
                    style="margin-left:10px;">Buka Link Dokumen ↗</a>
            </div>
        </div>

        <table class="detail-table" style="margin-top:20px;">
            <tr>
                <th>Produk AMDK</th>
                <td><?= htmlspecialchars($doc['produk']) ?></td>
            </tr>
            <tr>
                <th>Kategori</th>
                <td><?= htmlspecialchars($doc['jenis']) ?></td>
            </tr>
            <tr>
                <th>Tanggal QC</th>
                <td><?= htmlspecialchars($doc['tanggal']) ?></td>
            </tr>
            <tr>
                <th>Petugas (Inspector)</th>
                <td><?= htmlspecialchars($doc['inspector']) ?></td>
            </tr>
            <tr>
                <th>Status QC</th>
                <td>
                    <span class="badge <?= $doc['status'] == 'Lolos' ? 'badge-lolos' : 'badge-reject' ?>">
                        <?= htmlspecialchars($doc['status']) ?>
                    </span>
                </td>
            </tr>
        </table>

        <div style="margin-top:25px;">
            <h4 style="border-bottom:1px solid #eee; padding-bottom:10px;">Catatan Inspector:</h4>
            <div style="background:#f8f9fa; padding:20px; border-radius:5px; border:1px solid #eee;">
                <?= $doc['deskripsi'] ?: '<em style="color:#95a5a6;">Tidak ada catatan tambahan.</em>' ?>
            </div>
        </div>
    </div>
    </div>
    </div>
</body>

</html>