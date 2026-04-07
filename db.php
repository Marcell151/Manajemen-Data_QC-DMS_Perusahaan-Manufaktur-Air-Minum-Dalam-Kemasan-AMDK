<?php
// Tentukan path file SQLite
$db_file = __DIR__ . '/database.sqlite';

try {
    // Koneksi menggunakan PDO
    $pdo = new PDO("sqlite:" . $db_file);
    // Atur mode error PDO menjadi Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buat tabel jika belum ada
    $query = "CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_dokumen TEXT,
        produk TEXT,
        jenis TEXT,
        tanggal TEXT,
        inspector TEXT,
        status TEXT,
        link TEXT,
        deskripsi TEXT,
        folder_path TEXT
    )";
    $pdo->exec($query);

} catch (PDOException $e) {
    die("Kesalahan Database: " . $e->getMessage());
}
?>