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
    $_SESSION['role'] = 'Pekerja_Lapangan'; // Role simulasi default: Pekerja Lapangan
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
        'kekeruhan' => "TEXT",
        'file_path' => "TEXT",
        'external_link' => "TEXT"
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

    // SEEDING DATA PROFESIONAL: 3 Siklus Lengkap & Realistis
    $countDocs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
    
    if ($countDocs == 0) {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
        $ym = date('ym');

        // --- SIKLUS A: KASUS MUTU AIR (BACKWASH FILTER) ---
        // 01. Sampling
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, folder_path) 
            VALUES ('QC-BTCH-$ym-001', 'Log Sampling Produksi Batch B-202', 'Botol_600ml', 'Catatan_Batch', '$twoDaysAgo', 'Budi Santoso', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', 'QC_AMDK/Botol_600ml/2026/April')");
        $idA1 = $pdo->lastInsertId();

        // 02. Uji Lab (REJECT)
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, ph, tds, kekeruhan, deskripsi, folder_path, external_link) 
            VALUES ('QC-LABS-$ym-001', 'Laporan Analisis Kimia Fisika B-202', 'Botol_600ml', 'Uji_Lab', '$twoDaysAgo', 'Agus Setiawan', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Reject', $idA1, '7.4', '145', '3.1', '<strong>Hasil Uji:</strong> Parameter kekeruhan (Turbidity) melebihi ambang batas SNI 01-3553. Partikel padat terlihat secara visual.', 'QC_AMDK/Botol_600ml/2026/April', 'https://docs.google.com/viewer?url=example_lab_report_b202.pdf')");
        $idA2 = $pdo->lastInsertId();

        // 03. Diagnosis
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, deskripsi, folder_path) 
            VALUES ('QC-DIAG-$ym-001', 'Investigasi Masalah Mutu (Filter Karbon)', '-', 'Diagnosis_Mesin', '$yesterday', 'Indah Permata', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', $idA2, '<strong>Diagnosis:</strong> Filter karbon aktif pada tabung primer sudah jenuh. Ditemukan endapan lumpur halus pada nozzle inlet.', 'QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/2026')");
        $idA3 = $pdo->lastInsertId();

        // 04. Perbaikan
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, deskripsi, folder_path) 
            VALUES ('QC-REPR-$ym-001', 'Laporan Tindakan: Backwash & Sanitasi Nozzle', '-', 'Laporan_Perbaikan', '$yesterday', 'Agus Setiawan', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', $idA3, '<strong>Tindakan:</strong> Dilakukan pembilasan balik (Backwash) selama 60 menit. Pembersihan manual pada nozzle inlet dan sterilisasi tangki penampung.', 'QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/2026')");
        $idA4 = $pdo->lastInsertId();

        // 05. Uji Ulang
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, ph, tds, kekeruhan, deskripsi, folder_path) 
            VALUES ('QC-RETS-$ym-001', 'Verifikasi Uji Ulang Pasca Sanitasi B-202', 'Botol_600ml', 'Uji_Ulang', '$today', 'Budi Santoso', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', $idA4, '7.1', '85', '0.12', '<strong>Verifikasi:</strong> Kualitas air kembali jernih dan sesuai standar mutu internal perusahaan.', 'QC_AMDK/Botol_600ml/2026/April')");
        $idA5 = $pdo->lastInsertId();

        // 06. Approval
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, folder_path, approval_status, approved_by, deskripsi) 
            VALUES ('QC-APPR-$ym-001', 'Sertifikat Pelepasan Produk Batch B-202', 'Botol_600ml', 'Approval_Manager', '$today', 'Agus Setiawan', 'Mesin Filter Ozon #1', 'Admin Data Entry QC', 'Lolos', $idA5, 'QC_AMDK/Botol_600ml/2026/April', 'Approved', 'Manager Produksi (" . date('Y-m-d') . ")', 'Produksi dinyatakan layak edar.')");

        // --- SIKLUS B: KASUS MEKANIK MESIN (KEBOCORAN O-RING) ---
        // 01. Sampling
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, folder_path) 
            VALUES ('QC-BTCH-$ym-002', 'Log Sampling Produksi Batch G-303', 'Galon_19L', 'Catatan_Batch', '$yesterday', 'Indah Permata', 'Mesin Filling Galon B', 'Admin Data Entry QC', 'Lolos', 'QC_AMDK/Galon_19L/2026/April')");
        $idB1 = $pdo->lastInsertId();

        // 02. Uji Lab (REJECT)
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, ph, tds, kekeruhan, deskripsi, folder_path) 
            VALUES ('QC-LABS-$ym-002', 'Uji Tekanan & Mutu Fisik Galon G-303', 'Galon_19L', 'Uji_Lab', '$yesterday', 'Budi Santoso', 'Mesin Filling Galon B', 'Admin Data Entry QC', 'Reject', $idB1, '7.0', '98', '0.2', '<strong>Reject:</strong> Volume pengisian tidak stabil dan ditemukan rembesan air pada tutup galon.', 'QC_AMDK/Galon_19L/2026/April')");
        $idB2 = $pdo->lastInsertId();

        // 03. Diagnosis
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, deskripsi, folder_path) 
            VALUES ('QC-DIAG-$ym-002', 'Investigasi Kebocoran Filling Head', '-', 'Diagnosis_Mesin', '$today', 'Agus Setiawan', 'Mesin Filling Galon B', 'Admin Data Entry QC', 'Lolos', $idB2, '<strong>Diagnosis:</strong> O-Ring pada Filling Head nomor 2 sudah aus dan retak, menyebabkan tekanan pengisian drop.', 'QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/2026')");
        $idB3 = $pdo->lastInsertId();

        // 04. Perbaikan
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, parent_doc_id, deskripsi, folder_path) 
            VALUES ('QC-REPR-$ym-002', 'Penggantian Sparepart O-Ring Head #2', '-', 'Laporan_Perbaikan', '$today', 'Agus Setiawan', 'Mesin Filling Galon B', 'Admin Data Entry QC', 'Lolos', $idB3, '<strong>Perbaikan:</strong> Penggantian O-Ring seal baru (P/N: OR-9902). Kalibrasi ulang tekanan nozzle pengisian.', 'QC_AMDK/Laporan Diagnosis & Perbaikan Mesin/2026')");
        
        // --- SIKLUS C: KASUS NORMAL (LOLOS RUTIN) ---
        $pdo->exec("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, folder_path, ph, tds, kekeruhan) 
            VALUES ('QC-LABS-$ym-003', 'Uji Mutu Berkala Batch BT-505', 'Botol_330ml', 'Uji_Lab', '$today', 'Indah Permata', 'Mesin Filling Botol A', 'Admin Data Entry QC', 'Lolos', 'QC_AMDK/Botol_330ml/2026/April', '7.0', '80', '0.05')");
    }

} catch (PDOException $e) {
    die("Kesalahan Database: " . $e->getMessage());
}
?>