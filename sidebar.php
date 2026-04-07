<?php
// Tentukan menu aktif berdasarkan nama file
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <span class="icon-lg">🔬</span> QC-DMS
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="index.php"
                class="<?= ($current_page == 'index.php' || $current_page == 'edit.php' || $current_page == 'view.php') ? 'active' : '' ?>">
                📊 Dashboard & Explorer
            </a>
        </li>
        <li>
            <a href="add.php" class="<?= $current_page == 'add.php' ? 'active' : '' ?>">
                📝 Tambah Dokumen
            </a>
        </li>
    </ul>
</div>

<div class="main-content">
    <div class="topbar">
        <h2 style="margin:0; font-size:20px; color:#2c3e50;">
            <?= $current_page == 'index.php' ? 'File Manager' : ($current_page == 'add.php' ? 'Upload Dokumen' : 'Detail/Edit') ?>
        </h2>
        <div style="font-weight:600; color:#7f8c8d; font-size:14px;">Log in: Staff Pabrik (QC)</div>
    </div>
    <div class="content-area">