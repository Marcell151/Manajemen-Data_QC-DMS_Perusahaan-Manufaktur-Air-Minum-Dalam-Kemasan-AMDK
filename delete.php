<?php
require 'db.php';
$id = $_GET['id'];

// Cari folder_path dulu untuk redirect setelah delete
$stmt = $pdo->prepare("SELECT folder_path FROM documents WHERE id = ?");
$stmt->execute([$id]);
$path = $stmt->fetchColumn();

// Hapus file
$stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php?path=" . $path);
exit;
?>