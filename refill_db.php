<?php
require 'db.php';

// Reset Table
$pdo->exec("DELETE FROM documents");
$pdo->exec("DELETE FROM sqlite_sequence WHERE name='documents'");

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
$threeDaysAgo = date('Y-m-d', strtotime('-3 days'));

$monthFolder = date('F');
$yearFolder = date('Y');

$scenarios = [
    // --- SIKLUS 1: MINERAL 600ml (LOLOS TOTAL - UPLOAD) ---
    [
        'no' => 'QC-BTCH-2604-001', 'nama' => 'Sampling Produksi - Mineral 600ml', 'produk' => 'Mineral_600ml', 'jenis' => 'Catatan_Batch',
        'status' => 'Passed', 'file' => 'uploads/CATATAN PRODUKSI (SAMPLING).pdf', 'folder' => "QC_AMDK/Mineral_600ml/$yearFolder/$monthFolder",
        'tanggal' => $threeDaysAgo
    ],
    [
        'no' => 'QC-LABS-2604-001', 'nama' => 'Analisis Lab Utama - Mineral 600ml', 'produk' => 'Mineral_600ml', 'jenis' => 'Uji_Lab',
        'status' => 'Passed', 'file' => 'uploads/ANALISIS LABORATORIUM UTAMA.pdf', 'folder' => "QC_AMDK/Mineral_600ml/$yearFolder/$monthFolder", 'p_id' => 1,
        'tanggal' => $threeDaysAgo
    ],
    [
        'no' => 'QC-APPR-2604-001', 'nama' => 'Otorisasi Final - Mineral 600ml', 'produk' => 'Mineral_600ml', 'jenis' => 'Approval_Manager',
        'status' => 'Pending', 'approval_status' => 'Waiting Approval', 'file' => 'uploads/OTORISASI & APPROVAL MANAGER.pdf', 'folder' => "QC_AMDK/Mineral_600ml/$yearFolder/$monthFolder", 'p_id' => 2,
        'tanggal' => $threeDaysAgo
    ],

    // --- SIKLUS 2: GALON 19L (PERBAIKAN MESIN - UPLOAD) ---
    [
        'no' => 'QC-BTCH-2604-002', 'nama' => 'Sampling Produksi - Galon 19L', 'produk' => 'Galon_19L', 'jenis' => 'Catatan_Batch',
        'status' => 'Passed', 'file' => 'uploads/CATATAN PRODUKSI (SAMPLING).pdf', 'folder' => "QC_AMDK/Galon_19L/$yearFolder/$monthFolder",
        'tanggal' => $twoDaysAgo
    ],
    [
        'no' => 'QC-LABS-2604-002', 'nama' => 'Uji Lab - Galon 19L (REJECT)', 'produk' => 'Galon_19L', 'jenis' => 'Uji_Lab',
        'status' => 'Reject', 'file' => 'uploads/ANALISIS LABORATORIUM UTAMA.pdf', 'folder' => "QC_AMDK/Galon_19L/$yearFolder/$monthFolder", 'p_id' => 4, 'deskripsi' => 'Kekeruhan tinggi (2.1 NTU). Lanjut ke Diagnosis.',
        'tanggal' => $twoDaysAgo
    ],
    [
        'no' => 'QC-DIAG-2604-001', 'nama' => 'Investigasi Kerusakan - Filter Karbon', 'produk' => 'Galon_19L', 'jenis' => 'Diagnosis_Mesin',
        'status' => 'Passed', 'approval_status' => 'Approved', 'file' => 'uploads/DIAGNOSIS MASALAH (INVESTIGASI).pdf', 'folder' => "QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/$yearFolder", 'p_id' => 5,
        'tanggal' => $yesterday
    ],
    [
        'no' => 'QC-REPR-2604-001', 'nama' => 'Tindakan Perbaikan - Ganti Media Filter', 'produk' => 'Galon_19L', 'jenis' => 'Laporan_Perbaikan',
        'status' => 'Passed', 'file' => 'uploads/TINDAKAN PERBAIKAN TEKNIK.pdf', 'folder' => "QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/$yearFolder", 'p_id' => 6,
        'tanggal' => $yesterday
    ],
    [
        'no' => 'QC-RETS-2604-001', 'nama' => 'Verifikasi Re-Test - Galon 19L (PASS)', 'produk' => 'Galon_19L', 'jenis' => 'Uji_Ulang',
        'status' => 'Passed', 'file' => 'uploads/VERIFIKASI UJI ULANG (RE-TEST).pdf', 'folder' => "QC_AMDK/Galon_19L/$yearFolder/$monthFolder", 'p_id' => 7,
        'tanggal' => $today
    ],
    [
        'no' => 'QC-APPR-2604-002', 'nama' => 'Otorisasi Final - Galon 19L', 'produk' => 'Galon_19L', 'jenis' => 'Approval_Manager',
        'status' => 'Pending', 'approval_status' => 'Waiting Approval', 'file' => 'uploads/OTORISASI & APPROVAL MANAGER.pdf', 'folder' => "QC_AMDK/Galon_19L/$yearFolder/$monthFolder", 'p_id' => 8,
        'tanggal' => $today
    ],

    // --- SIKLUS 3: CUP 240ml (MASALAH KUALITAS AIR - LINK) ---
    [
        'no' => 'QC-BTCH-2604-003', 'nama' => 'Batch Monitoring - Cup 240ml', 'produk' => 'Cup_240ml', 'jenis' => 'Catatan_Batch',
        'status' => 'Passed', 'link' => 'https://docs.google.com/viewer?url=https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', 'folder' => "QC_AMDK/Cup_240ml/$yearFolder/$monthFolder",
        'tanggal' => $yesterday
    ],
    [
        'no' => 'QC-LABS-2604-003', 'nama' => 'Analisis Kimia - Cup 240ml (FAILED)', 'produk' => 'Cup_240ml', 'jenis' => 'Uji_Lab',
        'status' => 'Reject', 'link' => 'https://docs.google.com/viewer?url=https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', 'folder' => "QC_AMDK/Cup_240ml/$yearFolder/$monthFolder", 'p_id' => 10, 'deskripsi' => 'pH air baku di bawah standar. Produksi dihentikan.',
        'tanggal' => $yesterday
    ],

    // --- SIKLUS 4: MINERAL 330ml (SEDANG BERJALAN - IN PROGRESS) ---
    [
        'no' => 'QC-BTCH-2604-004', 'nama' => 'Batch Active - Mineral 330ml', 'produk' => 'Mineral_330ml', 'jenis' => 'Catatan_Batch',
        'status' => 'Passed', 'file' => 'uploads/CATATAN PRODUKSI (SAMPLING).pdf', 'folder' => "QC_AMDK/Mineral_330ml/$yearFolder/$monthFolder",
        'tanggal' => $twoDaysAgo
    ],

    // --- SIKLUS 5: VARIASI TAHAPAN (DALAM PROSES LAINNYA) ---
    [
        'no' => 'QC-DIAG-2604-002', 'nama' => 'Diagnosis Berjalan - Mesin MC-02', 'produk' => 'Mineral_600ml', 'jenis' => 'Diagnosis_Mesin',
        'status' => 'Passed', 'file' => 'uploads/DIAGNOSIS MASALAH (INVESTIGASI).pdf', 'folder' => "QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/$yearFolder", 'p_id' => null,
        'tanggal' => $yesterday
    ],
    [
        'no' => 'QC-REPR-2604-002', 'nama' => 'Perbaikan Sedang Berlangsung - MC-03', 'produk' => 'Cup_240ml', 'jenis' => 'Laporan_Perbaikan',
        'status' => 'Passed', 'file' => 'uploads/TINDAKAN PERBAIKAN TEKNIK.pdf', 'folder' => "QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/$yearFolder", 'p_id' => 13,
        'tanggal' => $today
    ],
];

$stmt = $pdo->prepare("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, status_mutu, deskripsi, folder_path, parent_doc_id, file_path, external_link, approval_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($scenarios as $s) {
    $tgl = $s['tanggal'];
    $stmt->execute([
        $s['no'], $s['nama'], $s['produk'], $s['jenis'], $tgl, 'Agus Setiawan', 'MC-01', 'Admin Data Entry QC', $s['status'], $s['status'], $s['deskripsi'] ?? '', $s['folder'], $s['p_id'] ?? null, $s['file'] ?? '', $s['link'] ?? '', $s['approval_status'] ?? '-', $tgl . ' 08:00:00'
    ]);
}

// Retroactive update: isi approved_at untuk dokumen Approval Manager yang disetujui
$pdo->exec("UPDATE documents SET approved_at = tanggal || ' 10:00:00' WHERE jenis = 'Approval_Manager' AND approval_status = 'Approved' AND approved_at IS NULL");

echo "Database refilled successfully with distributed dates.";
?>
