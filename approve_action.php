<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'Manager') {
    $doc_id = $_POST['doc_id'];
    $decision = $_POST['decision']; // 'Approved' atau 'Hold'
    $notes = $_POST['notes'] ?? '';
    
    $manager_name = "Manager Produksi (" . date('d/m/Y H:i') . ")";
    $now = date('Y-m-d H:i:s');
    
    if ($decision == 'Approved') {
        // Task 1: Status menjadi Archived. Tambahkan timestamp approved_at & archived_at. Overwrite deskripsi dengan catatan.
        $stmt = $pdo->prepare("UPDATE documents SET status = 'Archived', approval_status = 'Approved', approved_by = ?, deskripsi = ?, approved_at = ?, archived_at = ? WHERE id = ?");
        $stmt->execute([$manager_name, $notes, $now, $now, $doc_id]);
    } else {
        // If decision is 'Hold'
        $stmt = $pdo->prepare("UPDATE documents SET status = 'Hold', approval_status = 'Hold', approved_by = ?, deskripsi = ? WHERE id = ?");
        $stmt->execute([$manager_name, $notes, $doc_id]);
    }
    
    header("Location: view.php?id=" . $doc_id . "&msg=Status Updated to " . $decision);
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>
