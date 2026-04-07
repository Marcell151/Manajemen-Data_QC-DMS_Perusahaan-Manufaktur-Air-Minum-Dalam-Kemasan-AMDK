<?php
require 'db.php';

$current_path = isset($_GET['path']) ? $_GET['path'] : 'QC_AMDK';
$current_path = rtrim($current_path, '/');

// Logic Subfolder
$stmt = $pdo->query("SELECT DISTINCT folder_path FROM documents");
$all_paths = $stmt->fetchAll(PDO::FETCH_COLUMN);
$subfolders = [];
foreach ($all_paths as $p) {
    if ($p === $current_path)
        continue; // Skip jika path sama persis
    if (strpos($p, $current_path . '/') === 0) {
        $remainder = substr($p, strlen($current_path . '/'));
        $parts = explode('/', $remainder);
        if (!empty($parts[0]))
            $subfolders[$parts[0]] = true;
    }
}
$subfolders = array_keys($subfolders);

// Ambil file khusus di path saat ini
$stmt = $pdo->prepare("SELECT * FROM documents WHERE folder_path = ? ORDER BY id DESC");
$stmt->execute([$current_path]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrik Dashboard Spesifik QC
$bulan_ini = date('Y-m'); // Format 2026-04
$total_docs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
$inspeksi_bulan_ini = $pdo->query("SELECT COUNT(*) FROM documents WHERE tanggal LIKE '$bulan_ini%'")->fetchColumn();
$total_reject = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'Reject'")->fetchColumn();
$total_checklist = $pdo->query("SELECT COUNT(*) FROM documents WHERE jenis = 'Checklist'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard QC-DMS</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="dashboard-cards">
        <div class="card">
            <h3>Total Dokumen Sistem</h3>
            <div class="value"><?= $total_docs ?></div>
        </div>
        <div class="card green">
            <h3>Inspeksi Bulan Ini</h3>
            <div class="value"><?= $inspeksi_bulan_ini ?></div>
        </div>
        <div class="card yellow">
            <h3>Checklist Harian</h3>
            <div class="value"><?= $total_checklist ?></div>
        </div>
        <div class="card red">
            <h3>Total Reject</h3>
            <div class="value"><?= $total_reject ?></div>
        </div>
    </div>

    <div class="box-wrapper">
        <div
            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap: wrap; gap: 10px;">
            <div class="breadcrumb" style="margin-bottom: 0;">
                <span style="color:#7f8c8d;">📍 Navigasi:</span>
                <?php
                $path_parts = explode('/', $current_path);
                $build_path = '';
                foreach ($path_parts as $index => $part) {
                    $build_path .= $part;
                    echo "<a href='?path=$build_path'>$part</a>";
                    if ($index < count($path_parts) - 1)
                        echo " <span style='color:#bdc3c7;'>/</span> ";
                    $build_path .= '/';
                }
                ?>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <input type="text" id="searchInput" class="form-control" placeholder="🔍 Cari file di sini..."
                    style="width: 250px; padding: 8px 12px; margin: 0;">
                <a href="add.php" class="btn btn-primary">+ Dokumen Baru</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nama File / Folder</th>
                    <th>Kategori</th>
                    <th>Tanggal QC</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($current_path !== 'QC_AMDK'):
                    $up_path = substr($current_path, 0, strrpos($current_path, '/'));
                    ?>
                    <tr>
                        <td colspan="5"><a href="?path=<?= $up_path ?>" class="item-title" style="color:#7f8c8d;"><span
                                    class="icon-lg">🔙</span> Kembali ke atas</a></td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($subfolders as $folder): ?>
                    <tr>
                        <td>
                            <a href="?path=<?= $current_path . '/' . $folder ?>" class="item-title">
                                <span class="icon-lg">📁</span> <?= $folder ?>
                            </a>
                        </td>
                        <td><span class="badge badge-folder">Folder</span></td>
                        <td>-</td>
                        <td>-</td>
                        <td style="text-align:right;"><a href="?path=<?= $current_path . '/' . $folder ?>"
                                class="btn btn-action">Buka Folder</a></td>
                    </tr>
                <?php endforeach; ?>

                <?php foreach ($files as $file): ?>
                    <tr>
                        <td>
                            <a href="view.php?id=<?= $file['id'] ?>" class="item-title">
                                <span class="icon-lg">📄</span>
                                <div>
                                    <?= htmlspecialchars($file['nama_dokumen']) ?><br>
                                    <small style="color:#7f8c8d; font-weight:normal;">Oleh:
                                        <?= htmlspecialchars($file['inspector']) ?></small>
                                </div>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($file['jenis']) ?></td>
                        <td><?= htmlspecialchars($file['tanggal']) ?></td>
                        <td>
                            <span class="badge <?= $file['status'] == 'Lolos' ? 'badge-lolos' : 'badge-reject' ?>">
                                <?= htmlspecialchars($file['status']) ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <a href="view.php?id=<?= $file['id'] ?>" class="btn btn-action">Detail</a>
                            <a href="edit.php?id=<?= $file['id'] ?>" class="btn btn-action">Edit</a>
                            <a href="delete.php?id=<?= $file['id'] ?>" class="btn btn-danger btn-action"
                                onclick="return confirm('Hapus dokumen?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($subfolders) && empty($files)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:40px; color:#95a5a6;">📂 Folder ini masih kosong.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const tableRows = document.querySelectorAll('table tbody tr');

        searchInput.addEventListener('keyup', function () {
            const searchTerm = searchInput.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>

</html>