<?php
require 'db.php';
// $current_page sudah di-set di db.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Teknikal - QC-DMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <!-- Mermaid JS untuk Diagram Alur -->
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({ startOnLoad: true, theme: 'neutral' });
    </script>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="antialiased">
    
    <?php include 'sidebar.php'; ?>

    <!-- Layout Layar (Web) -->
    <div class="max-w-5xl mx-auto bg-white rounded-3xl shadow-sm border border-slate-200 p-8 md:p-12 mb-10 mt-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 pb-6 border-b border-slate-200 gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center shadow-inner">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight">Dokumen Teknikal</h1>
                    <p class="text-sm font-bold text-indigo-600 uppercase tracking-[0.2em] mt-1">Sistem QC-DMS &bull; v1.0.0</p>
                </div>
            </div>
            <a href="dokumen/PRD_QC-DMS.pdf" target="_blank" download class="flex items-center gap-2 bg-slate-900 hover:bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg hover:shadow-indigo-500/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Unduh Dokumen Resmi (PDF)
            </a>
        </div>

        <!-- Konten Web -->
        <article class="prose prose-slate prose-lg max-w-none prose-headings:font-black prose-h1:text-4xl prose-h2:text-2xl prose-h2:border-b-2 prose-h2:border-slate-100 prose-h2:pb-2 prose-a:text-indigo-600 prose-table:border-collapse prose-th:bg-slate-50 prose-th:p-4 prose-td:p-4 prose-img:rounded-xl">
            <p class="lead font-semibold text-slate-600 border-l-4 border-indigo-500 pl-4 py-1 bg-indigo-50/50">
                Ini adalah halaman rangkuman dari <strong>Dokumen Teknikal dan Spesifikasi Sistem (Product Requirement Document)</strong>. Klik tombol <strong>Cetak Laporan PDF Resmi</strong> di atas untuk mengunduh laporan multi-halaman formal dengan format standar akademik/industri.
            </p>

            <h2>Bab I: Pendahuluan & Teknologi Utama</h2>
            <h3>1.1 Latar Belakang Sistem</h3>
            <p>Quality Control - Document Management System (QC-DMS) dikembangkan sebagai solusi digitalisasi proses pengawasan mutu (Quality Assurance) di fasilitas manufaktur Air Minum Dalam Kemasan (AMDK). Sistem ini bertujuan untuk menghilangkan penggunaan dokumen fisik (paperless), mempercepat pelaporan kerusakan mesin, dan menciptakan rekam jejak audit (audit trail) yang tidak dapat dimanipulasi.</p>
            
            <h3>1.2 Teknologi yang Digunakan (Tech Stack)</h3>
            <p>Sistem ini sengaja didesain menggunakan arsitektur monolitik yang sangat efisien dan portabel, tanpa ketergantungan pada kerangka kerja (framework) pihak ketiga. Hal ini memastikan sistem dapat di-hosting pada peladen (server) dengan spesifikasi rendah sekalipun.</p>
            <ul>
                <li><strong>Bahasa Pemrograman Inti:</strong> PHP (Hypertext Preprocessor) versi 8.x dengan penulisan pola Procedural murni. Tidak memerlukan Composer atau build tools.</li>
                <li><strong>Mesin Basis Data:</strong> SQLite3 (Diakses menggunakan driver PDO PHP). File basis data tersimpan secara lokal dan tunggal pada <code>database.sqlite</code>, meniadakan kebutuhan terhadap server database terpisah seperti MySQL.</li>
                <li><strong>Keamanan Siber:</strong> Algoritma Hashing Bcrypt yang digunakan melalui fungsi bawaan PHP <code>password_hash()</code> dan <code>password_verify()</code> untuk mengamankan data sandi otorisasi pekerja.</li>
                <li><strong>Pemrosesan Dokumen Cetak:</strong> Pustaka pihak ketiga TCPDF yang difungsikan untuk mencetak dokumen Sertifikat Mutu (Certificate of Analysis) dalam format portabel (.pdf) berstandar ISO.</li>
            </ul>

            <h2>Bab II: Skema Basis Data Terstruktur</h2>
            <h3>2.1 Mekanisme Migrasi & Penyemaian (Auto-Migration & Seeding)</h3>
            <p>Sistem ini dirancang tanpa memerlukan proses "Import SQL" manual melalui utilitas seperti phpMyAdmin. Saat peramban web memuat sistem untuk pertama kali, serangkaian instruksi DDL (Data Definition Language) di dalam inti program akan mengevaluasi ketiadaan entitas tabel dan secara independen memicu perintah <code>CREATE TABLE IF NOT EXISTS</code>.</p>
            
            <h3>2.2 Tabel Entitas "users" (Hak Akses)</h3>
            <table class="w-full text-sm border border-slate-200">
                <thead><tr><th>Nama Kolom</th><th>Tipe Data</th><th>Fungsi</th></tr></thead>
                <tbody>
                    <tr><td><code>id</code></td><td>INTEGER (PK)</td><td>Kunci primer.</td></tr>
                    <tr><td><code>username</code></td><td>TEXT (UNIQUE)</td><td>Identitas log masuk pengguna.</td></tr>
                    <tr><td><code>password</code></td><td>TEXT</td><td>Kata sandi tersandi (Hash Bcrypt).</td></tr>
                    <tr><td><code>role</code></td><td>TEXT</td><td>Tingkat akses (Manager, Admin_Entry, Pekerja_Lapangan).</td></tr>
                    <tr><td><code>nama_lengkap</code></td><td>TEXT</td><td>Nama tampilan.</td></tr>
                </tbody>
            </table>

            <h3>2.3 Tabel Entitas "documents" (Pusat Relasional)</h3>
            <table class="w-full text-sm border border-slate-200">
                <thead><tr><th>Nama Kolom</th><th>Tipe Data</th><th>Fungsi Utama</th></tr></thead>
                <tbody>
                    <tr><td><code>no_dokumen</code></td><td>TEXT (UNIQUE)</td><td>Kode dokumen auto-generate.</td></tr>
                    <tr><td><code>jenis</code></td><td>TEXT</td><td>Fase tahapan inspeksi mutu (Langkah 01 s/d 06).</td></tr>
                    <tr><td><code>status_mutu</code></td><td>TEXT</td><td>Hasil pengecekan akhir lab (Passed/Reject).</td></tr>
                    <tr><td><code>approval_status</code></td><td>TEXT</td><td>Sikap Manajer Produksi atas tindakan (Waiting / Approved / Rejected).</td></tr>
                    <tr><td><code>parent_doc_id</code></td><td>INTEGER</td><td>ID penaut antar fase yang memastikan rantai dokumen (<em>Traceability</em>) tidak terputus.</td></tr>
                    <tr><td><code>ph, tds, kekeruhan</code></td><td>TEXT</td><td>Metrik fisika kimia.</td></tr>
                </tbody>
            </table>

            <h2>Bab III: Desain Antarmuka & UX</h2>
            <h3>3.1 Pendekatan Mobile-First & Glassmorphism</h3>
            <p>Lingkungan kerja manufaktur mewajibkan pergerakan dinamis. Oleh karena itu, antarmuka QC-DMS difokuskan untuk pengguna Tablet dan perangkat seluler di lapangan.</p>
            <ul>
                <li><strong>Tailwind CSS (Utility-First):</strong> Meminimalisasi ukuran berkas gaya bawaan dan mempercepat waktu muat halaman.</li>
                <li><strong>Navigasi Laci (Drawer Navigation):</strong> Menghindari menu navigasi bawah (bottom-bar) yang dapat tertekan secara tidak sengaja.</li>
                <li><strong>Estetika Glassmorphism:</strong> Penggunaan latar belakang semi-transparan yang dibaurkan dengan bayangan lembut menciptakan ilusi kedalaman visual.</li>
            </ul>

            <h3>3.2 Pencegahan Anomali Logika Antarmuka (Poka-Yoke)</h3>
            <ul>
                <li><strong>Pemblokiran Kalender Masa Depan:</strong> Mencegah pemalsuan kronologis pelaporan QA.</li>
                <li><strong>Kamera Terintegrasi Langsung:</strong> Memaksa pengambilan bukti secara aktual pada waktu yang sebenarnya (Real-Time Forensic) dengan <code>capture="environment"</code>.</li>
                <li><strong>Anotasi Visual (Auto-Fill):</strong> Meneruskan otomatis pengisian data dari dokumen sebelumnya untuk mencegah galat ketik (typo).</li>
            </ul>

            <h2>Bab IV: Otoritas & Tanggung Jawab Peran (RBAC)</h2>
            <p>Sistem ini menerapkan <em>Role-Based Access Control</em> secara ketat. Berikut rincian peran operasional:</p>
            <ul>
                <li><strong>Manajer Produksi:</strong> Pemegang keputusan tertinggi. Menyetujui tindakan mesin (Langkah 03), merilis Approval Final (Langkah 06), dan mencetak Sertifikat CoA PDF.</li>
                <li><strong>Admin Quality Control:</strong> Bertanggung jawab penuh pada pengujian metrik kimia-fisika di laboratorium (Langkah 02 dan Langkah 05). Berhak menolak (Reject) kualitas.</li>
                <li><strong>Teknisi Lapangan:</strong> Operator mesin. Mendaftarkan sampling baru (Langkah 01), melaporkan kerusakan mesin (Langkah 03), dan memasukkan bukti perbaikan (Langkah 04).</li>
            </ul>

            <h2>Bab V: Arsitektur Arus Kerja (Workflow)</h2>
            <p>Sistem beroperasi dengan rute berantai tertutup. Tidak ada entitas yang diizinkan untuk melewati (bypass) satu tahapan wajib ke tahapan berikutnya tanpa jejak validasi.</p>
            
            <div class="my-8 p-6 bg-slate-50 border border-slate-200 rounded-2xl flex justify-center">
                <div class="mermaid">
                graph TD
                    classDef default fill:#f9fafb,stroke:#d1d5db,stroke-width:2px;
                    classDef start fill:#dbeafe,stroke:#3b82f6,stroke-width:2px,color:#1e40af,font-weight:bold;
                    classDef lab fill:#fef3c7,stroke:#f59e0b,stroke-width:2px,color:#92400e,font-weight:bold;
                    classDef manager fill:#fce7f3,stroke:#ec4899,stroke-width:2px,color:#9d174d,font-weight:bold;
                    classDef error fill:#fee2e2,stroke:#ef4444,stroke-width:2px,color:#991b1b,font-weight:bold;
                    classDef success fill:#dcfce3,stroke:#22c55e,stroke-width:2px,color:#166534,font-weight:bold;

                    A((Mulai Batch Produksi)):::start --> B
                    B[LANGKAH 01: Sampling]:::default --> C
                    
                    C{LANGKAH 02: Uji Lab}:::lab
                    
                    C -- "JIKA PASSED" --> G
                    C -- "JIKA REJECT" --> D
                    
                    D[LANGKAH 03: Diagnosis Mesin]:::error --> E
                    
                    E{Otorisasi Manajer}:::manager
                    
                    E -- "Reject" --> C
                    E -- "Approve" --> F
                    
                    F[LANGKAH 04: Perbaikan Mesin]:::error --> H
                    
                    H[LANGKAH 05: Uji Verifikasi Lab]:::lab --> I
                    
                    I{Hasil Uji Ulang}:::lab
                    I -- "Gagal" --> D
                    I -- "Passed" --> G
                    
                    G[LANGKAH 06: Approval Final]:::manager --> J
                    
                    J(((Selesai & Archived))):::success
                </div>
            </div>

            <h2>Bab VI: Fitur Kompetitif Lanjutan</h2>
            <ul>
                <li><strong>Algoritma Downtime:</strong> Penggunaan fungsi rekursif PHP <code>getRepairDowntime()</code> yang menelusuri rantai <code>parent_doc_id</code> dari Langkah 3 hingga Langkah 6 untuk menghitung durasi perbaikan dalam Hari, Jam, Menit.</li>
                <li><strong>Smart SQL Badges:</strong> Indikator angka cerdas di sidebar yang menyesuaikan dengan peran, memastikan respons <em>Pull-Workflow</em> yang cepat.</li>
                <li><strong>Mesin Pembuat PDF (TCPDF):</strong> Sistem sanggup merakit Sertifikat CoA yang utuh dengan kop surat resmi PT Mineral Pure Indonesia.</li>
                <li><strong>Zero Configuration Deployment:</strong> Cukup unggah direktori repositori ke dalam folder <code>public_html</code> pada layanan <em>Shared Hosting cPanel</em> tanpa perlu mengimpor berkas SQL.</li>
            </ul>

        </article>
    </div>
    
    <!-- Penutup tag main, div, body yang dibuka di sidebar.php -->
    </main>
    </div>
</div>
</body>
</html>
