<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'Manager') {
    $doc_id = $_POST['doc_id'];
    $decision = $_POST['decision']; // 'Approved' atau 'Hold'
    
    // Update status dokumen ke 'Approved' atau 'Hold'
    // Dan update metadata approval_status untuk visual di form
    $stmt = $pdo->prepare("UPDATE documents SET status = ?, approval_status = ?, approved_by = ? WHERE id = ?");
    $manager_name = "Manager Produksi (" . date('d/m/Y H:i') . ")";
    $stmt->execute([$decision, $decision, $manager_name, $doc_id]);
    
    header("Location: view.php?id=" . $doc_id . "&msg=Status Updated to " . $decision);
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>
