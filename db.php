<?php
session_start();

// Logic Switch Role Global (Mencegah error pada halaman detail)
if (isset($_GET['switch_role'])) {
    $_SESSION['role'] = $_GET['switch_role'];
    
    $current_page = basename($_SERVER['PHP_SELF']);
    $params = $_GET;
    unset($params['switch_role']);
    $queryString = http_build_query($params);
    
    $redirectUrl = $current_page . ($queryString ? '?' . $queryString : '');
    header("Location: " . $redirectUrl);
    exit;
}

if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'Admin_Entry'; // Role simulasi default
}

// Tentukan path file SQLite
$db_file = __DIR__ . '/database.sqlite';

try {
    // Koneksi menggunakan PDO
    $pdo = new PDO("sqlite:" . $db_file);
    // Atur mode error PDO menjadi Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat tabel jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS machines (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_mesin TEXT UNIQUE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inspectors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_inspector TEXT UNIQUE
    )");

    $query = "CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        no_dokumen TEXT UNIQUE,
        nama_dokumen TEXT,
        produk TEXT,
        jenis TEXT,
        tanggal TEXT,
        inspector TEXT,
        machine_id TEXT,
        admin_entry_name TEXT,
        status TEXT,
        approval_status TEXT DEFAULT 'Waiting Approval',
        approved_by TEXT,
        link TEXT,
        deskripsi TEXT,
        folder_path TEXT,
        parent_doc_id INTEGER,
        ph TEXT,
        tds TEXT,
        kekeruhan TEXT
    )";
    $pdo->exec($query);

    // MIGRATION: Cek kolom yang hilang jika tabel sudah ada sebelumnya
    $existing_columns = $pdo->query("PRAGMA table_info(documents)")->fetchAll(PDO::FETCH_COLUMN, 1);
    
    $new_cols = [
        'no_dokumen' => "TEXT",
        'machine_id' => "TEXT",
        'admin_entry_name' => "TEXT",
        'approval_status' => "TEXT DEFAULT 'Waiting Approval'",
        'approved_by' => "TEXT",
        'parent_doc_id' => "INTEGER",
        'ph' => "TEXT",
        'tds' => "TEXT",
        'kekeruhan' => "TEXT"
    ];

    foreach ($new_cols as $col => $type) {
        if (!in_array($col, $existing_columns)) {
            $pdo->exec("ALTER TABLE documents ADD COLUMN $col $type");
        }
    }

    // Tambahkan Index jika belum ada (SQLite workaround untuk UNIQUE)
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_no_dokumen ON documents(no_dokumen)");

    // Seed data untuk Mesin dan Inspector jika kosong
    $countMesin = $pdo->query("SELECT COUNT(*) FROM machines")->fetchColumn();
    if ($countMesin == 0) {
        $pdo->exec("INSERT INTO machines (nama_mesin) VALUES ('Mesin Filter Ozon #1'), ('Mesin Filter Ozon #2'), ('Mesin Filling Botol A'), ('Mesin Filling Galon B')");
    }

    $countInspector = $pdo->query("SELECT COUNT(*) FROM inspectors")->fetchColumn();
    if ($countInspector == 0) {
        $pdo->exec("INSERT INTO inspectors (nama_inspector) VALUES ('Budi Santoso'), ('Agus Setiawan'), ('Indah Permata')");
    }

    // SEEDING DATA PROFESIONAL: 1 Siklus Lengkap (6 Dokumen) + Variasi
    // Hanya jalan jika database benar-benar kosong untuk efisiensi
    $countDocs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
    
    if ($countDocs == 0) {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $ym = date('ym');

        // --- SIKLUS 1: BATCH A-101 (BOTOL 600ML - MASALAH KUALITAS AIR) ---
        // 1. Catatan Batch
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, folder_path) 
            VALUES ('QC-BTCH-$ym-001', 'Log Produksi Batch A-101', 'Botol_600ml', 'Catatan_Batch', '$yesterday', 'Budi Santoso', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', 'QC_AMDK/Botol_600ml/2026/April')");
        $id1 = $pdo->lastInsertId();

        // 2. Laporan Hasil Ujian (REJECT)
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, ph, tds, kekeruhan, deskripsi, folder_path) 
            VALUES ('QC-LABS-$ym-001', 'Laporan Uji Fisika-Kimia A-101', 'Botol_600ml', 'Uji_Lab', '$yesterday', 'Agus Setiawan', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Reject', $id1, '7.2', '115', '2.8', '<p>Tingkat kekeruhan (Turbidity) melebihi standar SNI (> 1.5 NTU). Produksi dihentikan sementara.</p>', 'QC_AMDK/Botol_600ml/2026/April')");
        $id2 = $pdo->lastInsertId();

        // 3. Dokumen Diagnosis
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, deskripsi, folder_path) 
            VALUES ('QC-DIAG-$ym-001', 'Investigasi Kualitas Air (Filter Keramik)', '-', 'Diagnosis_Mesin', '$today', 'Indah Permata', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', $id2, '<p>Analisis menunjukkan adanya akumulasi partikel pada filter keramik nomor 3 dan 5. Diperlukan pencucian tabung filter (Backwash).</p>', 'QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/2026')");
        $id3 = $pdo->lastInsertId();

        // 4. Laporan Perbaikan
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, deskripsi, folder_path) 
            VALUES ('QC-REPR-$ym-001', 'Tindakan Koreksi: Backwash & Sterilisasi', '-', 'Laporan_Perbaikan', '$today', 'Agus Setiawan', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', $id3, '<p>Telah dilakukan pembilasan balik (Backwashing) selama 45 menit. Pipa inlet telah disterilisasi menggunakan larutan pembersih standar.</p>', 'QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/2026')");
        $id4 = $pdo->lastInsertId();

        // 5. Laporan Uji Ulang
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, ph, tds, kekeruhan, deskripsi, folder_path) 
            VALUES ('QC-RETS-$ym-001', 'Uji Verifikasi Pasca-Koreksi A-101', 'Botol_600ml', 'Uji_Ulang', '$today', 'Budi Santoso', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', $id4, '7.0', '78', '0.15', '<p>Parameter kekeruhan kembali normal (0.15 NTU). Kelayakan air dinyatakan aman.</p>', 'QC_AMDK/Botol_600ml/2026/April')");
        $id5 = $pdo->lastInsertId();

        // 6. Approval Manager
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, folder_path, approval_status, approved_by, deskripsi) 
            VALUES ('QC-APPR-$ym-001', 'Otorisasi Rilis Produksi Batch A-101', 'Botol_600ml', 'Approval_Manager', '$today', 'Agus Setiawan', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', $id5, 'QC_AMDK/Botol_600ml/2026/April', 'Approved', 'Manager (" . date('Y-m-d') . ")', '<p>Disetujui untuk melanjutkan produksi normal. Pastikan pemantauan filter keramik ditingkatkan.</p>')");

        // --- SIKLUS 2: BATCH G-99 (GALON 19L - PASSED) ---
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, folder_path, ph, tds, kekeruhan, approval_status) 
            VALUES ('QC-LABS-$ym-002', 'Laporan Rutin Mutu Galon G-99', 'Galon_19L', 'Uji_Lab', '$today', 'Budi Santoso', 'Mesin Filling Galon B', 'Admin Data Entry QC', 'Lolos', 'QC_AMDK/Galon_19L/2026/April', '7.1', '82', '0.1', 'Waiting Approval')");
    }

} catch (PDOException $e) {
    die("Kesalahan Database: " . $e->getMessage());
}
?>