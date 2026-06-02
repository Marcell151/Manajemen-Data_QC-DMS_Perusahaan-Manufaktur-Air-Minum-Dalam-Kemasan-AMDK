<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'Manager') {
    $doc_id = $_POST['doc_id'];
    $decision = $_POST['decision']; // 'Approved', 'Rejected', or 'Hold'
    $notes = $_POST['notes'] ?? '';
    
    // Fetch the document details
    $stmt = $pdo->prepare("SELECT jenis, deskripsi FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$doc) {
        header("Location: index.php");
        exit;
    }
    
    $manager_name = "Manager Produksi (" . date('d/m/Y H:i') . ")";
    $now = date('Y-m-d H:i:s');
    
    // Format the updated description to append manager notes
    $orig_desc = $doc['deskripsi'] ?? '';
    $updated_desc = $orig_desc;
    if (!empty($notes)) {
        $updated_desc .= "\n\nCatatan Approval Manajer (" . date('d/m/Y H:i') . "): " . $notes;
    }
    
    if ($doc['jenis'] == 'Diagnosis_Mesin') {
        if ($decision == 'Approved') {
            // Approval 1 - Approve: Status Lolos tetap, approval_status Approved. Terbuka untuk Langkah 04.
            $stmt = $pdo->prepare("UPDATE documents SET approval_status = 'Approved', approved_by = ?, deskripsi = ?, approved_at = ? WHERE id = ?");
            $stmt->execute([$manager_name, $updated_desc, $now, $doc_id]);
        } elseif ($decision == 'Rejected') {
            // Approval 1 - Reject: Status Rejected, archived_at dicatat, siklus berhenti.
            $stmt = $pdo->prepare("UPDATE documents SET status = 'Rejected', approval_status = 'Rejected', approved_by = ?, deskripsi = ?, archived_at = ? WHERE id = ?");
            $stmt->execute([$manager_name, $updated_desc, $now, $doc_id]);
        }
    } elseif ($doc['jenis'] == 'Approval_Manager') {
        if ($decision == 'Approved') {
            // Approval 2 - Approve Final: status Archived, archived_at dicatat.
            $stmt = $pdo->prepare("UPDATE documents SET status = 'Archived', approval_status = 'Approved', approved_by = ?, deskripsi = ?, approved_at = ?, archived_at = ? WHERE id = ?");
            $stmt->execute([$manager_name, $updated_desc, $now, $now, $doc_id]);
        } else {
            // Approval 2 - Hold: status Hold, approval_status Hold, archived_at dicatat.
            $stmt = $pdo->prepare("UPDATE documents SET status = 'Hold', approval_status = 'Hold', approved_by = ?, deskripsi = ?, archived_at = ? WHERE id = ?");
            $stmt->execute([$manager_name, $updated_desc, $now, $doc_id]);
        }
    }
    
    header("Location: view.php?id=" . $doc_id . "&msg=Status Updated to " . $decision);
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>
