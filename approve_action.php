<?php
session_start();
require 'db.php';

if ($_SESSION['role'] != 'Manager' || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit;
}

$id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$id || !in_array($action, ['approve', 'reject'])) {
    die("Invalid request");
}

$status = ($action === 'approve') ? 'Approved' : 'Rejected';
// Update status to Hold if rejected, to prevent it from showing up in pending lists
$doc_status = ($action === 'approve') ? 'Pending' : 'Hold';

$manager_name = $_SESSION['nama'] ?? 'Manager';

$stmt = $pdo->prepare("UPDATE documents SET approval_status = ?, status = ?, approved_by = ? WHERE id = ?");
if ($stmt->execute([$status, $doc_status, $manager_name, $id])) {
    header("Location: view.php?id=" . $id);
    exit;
} else {
    die("Gagal memproses persetujuan.");
}
?>
