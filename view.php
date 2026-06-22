<?php
require 'db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    echo "Dokumen tidak ditemukan.";
    exit;
}

// Cek relasi ketergantungan (Traceability)
$parent_doc = null;
if ($doc['parent_doc_id']) {
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$doc['parent_doc_id']]);
    $parent_doc = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Cek jika ada dokumen turunan (Tindak Lanjut)
$stmt = $pdo->prepare("SELECT * FROM documents WHERE parent_doc_id = ?");
$stmt->execute([$id]);
$child_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$role_name_map = [
    'Admin_Entry' => 'Admin Data / QC Lab',
    'Manager' => 'Manajer Produksi',
    'Pekerja_Lapangan' => 'Pekerja Lapangan / Teknisi'
];
$role_name = $role_name_map[$_SESSION['role']] ?? 'User';

// Ekstraksi Metadata File Fisik
$file_size_formatted = '0 KB';
$file_ext = 'UNKNOWN';
$file_mime = 'application/octet-stream';
$file_hash = '-';
$file_upload_time = $doc['tanggal'];
$has_physical_file = false;

if (!empty($doc['file_path']) && file_exists($doc['file_path'])) {
    $has_physical_file = true;
    $file_size = filesize($doc['file_path']);
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($file_size, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    $file_size_formatted = round($bytes, 2) . ' ' . $units[$pow];

    $file_ext = strtoupper(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
    $file_mime = mime_content_type($doc['file_path']);
    $file_hash = md5_file($doc['file_path']);
    $file_upload_time = date("d F Y, H:i:s", filemtime($doc['file_path']));
} elseif (!empty($doc['external_link'])) {
    $file_ext = 'CLOUD LINK';
    $file_mime = 'URI/Hyperlink';
    $file_size_formatted = 'External Storage';
    $file_hash = 'N/A (Cloud Data)';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QC-DMS: <?= htmlspecialchars($doc['no_dokumen']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        
        @media print {
            @page { 
                margin: 0.5cm 1cm; 
            }
            .no-print, .no-print *, nav, aside, header, .action-area, .sidebar-container, .mobile-topbar, .mobile-bottom-nav, .mobile-action-bar { 
                display: none !important; 
                visibility: hidden !important; 
                opacity: 0 !important; 
                height: 0 !important; 
                margin: 0 !important; 
                padding: 0 !important; 
            }
            body, html, main, .main-layout, .view-container, .mb-16, #metadataSection {
                background: white !important; 
                padding: 0 !important; 
                margin: 0 !important; 
                height: auto !important;
                min-height: auto !important;
                overflow: visible !important;
                max-width: none !important;
                width: 100% !important;
            }
            #reportContent { 
                display: block !important; 
                border: none !important; 
                box-shadow: none !important; 
                margin: 0 !important; 
                width: 100% !important; 
                padding: 0 !important; 
                transform: none !important; 
            }
            
            /* Print Footer styling */
            .print-footer-container {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                display: flex !important;
                justify-content: space-between;
                font-size: 8pt;
                font-family: 'Times New Roman', serif;
                border-top: 1px solid #000;
                padding-top: 4px;
                background: white;
            }
            .print-footer-left {
                text-align: left;
            }
            .print-footer-right {
                text-align: right;
            }
            .print-page-number::after {
                content: counter(page);
            }
            .print-kop-line {
                border-bottom: 4px double #000 !important;
            }
        }
        .metadata-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .metadata-content.open { max-height: 2000px; }

        /* Mobile sticky bottom action bar */
        .mobile-action-bar {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: white;
            padding: 0.75rem 1rem env(safe-area-inset-bottom, 0.75rem);
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
            z-index: 90;
            gap: 0.75rem;
        }
        @media (max-width: 767px) {
            .mobile-action-bar { display: flex !important; }
            .desktop-action-toolbar { display: none !important; }
            .doc-preview-box { height: 320px !important; }
            .view-container { padding: 1rem !important; padding-bottom: 100px !important; }
            .meta-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen antialiased text-slate-800">
    <?php include 'sidebar.php'; ?>

    <div class="max-w-5xl mx-auto py-4 md:py-8 px-4 md:px-8 view-container">
        <!-- Mobile top back button -->
        <div class="mb-4 flex items-center gap-3 no-print">
            <a href="javascript:history.back()" class="flex items-center gap-2 font-bold text-slate-500 hover:text-sky-600 transition-all text-sm md:text-xs md:uppercase md:tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Kembali</span>
            </a>
        </div>

        <!-- MANAGER APPROVAL PANEL DIHAPUS (Disesuaikan dengan Pivot ke Step 06) -->

        <!-- DOCUMENT PREVIEW HERO (Prioritas Dokumen Asli) -->
        <?php if (!empty($doc['file_path']) || !empty($doc['external_link'])): ?>
        <div class="mb-8 no-print">
            <div class="bg-slate-900 rounded-2xl overflow-hidden shadow-xl border-2 border-slate-800">
                <div class="p-4 bg-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <h4 class="text-xs font-black text-white uppercase tracking-widest">Foto / Dokumen Bukti Asli</h4>
                    </div>
                    <?php if (!empty($doc['external_link'])): ?>
                        <a href="<?= htmlspecialchars($doc['external_link']) ?>" target="_blank" class="text-xs font-black text-sky-400 hover:text-white transition-all uppercase flex items-center gap-1">
                            Buka Link 
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="bg-slate-700 doc-preview-box" style="height:500px">
                    <?php if (!empty($doc['file_path'])): ?>
                        <?php 
                        $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])): ?>
                            <img src="<?= htmlspecialchars($doc['file_path']) ?>" class="w-full h-full object-contain" style="display:block">
                        <?php else: ?>
                            <iframe src="<?= htmlspecialchars($doc['file_path']) ?>" class="w-full h-full border-none"></iframe>
                        <?php endif; ?>
                    <?php elseif (!empty($doc['external_link'])): ?>
                        <div class="flex flex-col items-center justify-center h-full gap-4 p-6 text-center">
                            <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                            </svg>
                            <p class="text-white font-bold text-sm">Dokumen disimpan di Cloud Storage</p>
                            <a href="<?= htmlspecialchars($doc['external_link']) ?>" target="_blank" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-black text-xs rounded-xl uppercase transition-colors">Buka Dokumen</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ACTION TOOLBAR - Desktop (hidden on mobile) -->
        <div class="mb-8 flex-col sm:flex-row justify-center items-center gap-4 sm:gap-6 no-print action-area desktop-action-toolbar hidden md:flex">
            <?php if ($doc['approval_status'] == 'Approved'): ?>
                <button onclick="window.print()" class="w-full sm:w-auto justify-center px-10 py-4 bg-emerald-600 text-white text-xs font-black uppercase rounded-2xl hover:bg-emerald-700 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak Dokumen Persetujuan Resmi
                </button>
            <?php endif; ?>
            <?php if (!empty($doc['file_path'])): ?>
                <a href="<?= htmlspecialchars($doc['file_path']) ?>" download class="w-full sm:w-auto justify-center px-10 py-4 bg-blue-600 text-white text-xs font-black uppercase rounded-2xl hover:bg-blue-700 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Unduh Dokumen Bukti (Asli)
                </a>
            <?php else: ?>
                <?php if ($doc['approval_status'] != 'Approved'): ?>
                    <button onclick="window.print()" class="w-full sm:w-auto justify-center px-10 py-4 bg-white border border-slate-300 text-slate-700 text-xs font-black uppercase rounded-2xl hover:bg-slate-50 transition-all shadow-sm flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Cetak Ringkasan Digital
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- MOBILE STICKY ACTION BAR (visible only on mobile) -->
        <div class="mobile-action-bar no-print">
            <a href="javascript:history.back()" class="flex-1 flex items-center justify-center gap-1.5 px-4 py-3 border border-slate-300 rounded-xl text-slate-700 font-black text-xs uppercase bg-white">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali
            </a>
            <?php if ($doc['approval_status'] == 'Approved'): ?>
                <button onclick="window.print()" class="flex-1 flex items-center justify-center gap-1.5 px-4 py-3 bg-emerald-600 text-white rounded-xl font-black text-xs uppercase shadow-md">
                    Cetak
                </button>
            <?php elseif (!empty($doc['file_path'])): ?>
                <a href="<?= htmlspecialchars($doc['file_path']) ?>" download class="flex-1 flex items-center justify-center gap-1.5 px-4 py-3 bg-blue-600 text-white rounded-xl font-black text-xs uppercase shadow-md">
                    Unduh
                </a>
            <?php elseif (!empty($doc['external_link'])): ?>
                <a href="<?= htmlspecialchars($doc['external_link']) ?>" target="_blank" class="flex-1 flex items-center justify-center gap-1.5 px-4 py-3 bg-sky-600 text-white rounded-xl font-black text-xs uppercase shadow-md">
                    Link
                </a>
            <?php else: ?>
                <button onclick="window.print()" class="flex-1 flex items-center justify-center gap-1.5 px-4 py-3 bg-slate-800 text-white rounded-xl font-black text-xs uppercase">
                    Cetak
                </button>
            <?php endif; ?>
        </div>

        <!-- COLLAPSIBLE METADATA (Digital Summary) -->
        <div class="mb-16">
            <div class="no-print">
                <button onclick="toggleMetadata()" class="w-full py-4 px-5 bg-white hover:bg-slate-50 rounded-xl flex justify-between items-center transition-all border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h4 class="text-xs font-black text-slate-650 uppercase tracking-widest">Metadata Sistem & Audit Trail</h4>
                    </div>
                    <span id="metaArrow" class="transform transition-transform duration-300 text-slate-500" style="transform: rotate(180deg);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                    </span>
                </button>
            </div>

            <div id="metadataSection" class="metadata-content open mt-4">
                
                <!-- METADATA UNTUK TAMPILAN LAYAR (UI MODERN) -->
                <div class="no-print grid grid-cols-1 md:grid-cols-2 gap-6 p-1 mb-8 meta-grid">
                    
                    <!-- KARTU 1: PROPERTI FILE -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-5 pb-3 border-b border-slate-100">
                            <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Informasi Berkas</h4>
                        </div>
                        <ul class="space-y-4">
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tipe Format</span>
                                <span class="text-xs font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-lg"><?= $file_ext ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ukuran Berkas</span>
                                <span class="text-xs font-black text-sky-700"><?= $file_size_formatted ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Unggah</span>
                                <span class="text-xs font-bold text-slate-700"><?= $file_upload_time ?></span>
                            </li>
                            <li class="flex flex-col gap-1.5 pt-2 border-t border-slate-50">
                                <span class="text-xs font-black text-slate-500 uppercase tracking-wider">MIME Type</span>
                                <span class="text-xs font-mono text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-200"><?= $file_mime ?></span>
                            </li>
                            <li class="flex flex-col gap-1.5">
                                <span class="text-xs font-black text-slate-500 uppercase tracking-wider">MD5 Checksum (Integritas)</span>
                                <span class="text-xs font-mono text-emerald-700 bg-emerald-50 p-2 rounded-lg border border-emerald-100 break-all"><?= $file_hash ?></span>
                            </li>
                        </ul>
                    </div>

                    <!-- KARTU 2: AUDIT & AKSES -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-5 pb-3 border-b border-slate-100">
                            <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Audit Trail & Otoritas</h4>
                        </div>
                        <ul class="space-y-4">
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">ID Laporan</span>
                                <span class="text-xs font-black text-slate-700">#<?= str_pad($doc['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Diinput Oleh</span>
                                <span class="text-xs font-black text-slate-700"><?= htmlspecialchars(str_replace('_', ' ', $doc['admin_entry_name'] ?? 'Pekerja Lapangan')) ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Inspektur Fisik</span>
                                <span class="text-xs font-black text-slate-700"><?= htmlspecialchars($doc['inspector'] ?? '-') ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Disetujui Manajer</span>
                                <span class="text-xs font-black text-slate-700"><?= htmlspecialchars(explode('(', $doc['approved_by'] ?? '-')[0]) ?></span>
                            </li>
                            <li class="flex flex-col gap-1.5 pt-2 border-t border-slate-50">
                                <span class="text-xs font-black text-slate-500 uppercase tracking-wider">Hak Akses Baca</span>
                                <div class="flex gap-2">
                                    <span class="text-xs font-bold text-blue-750 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">AdminQC</span>
                                    <span class="text-xs font-bold text-purple-750 bg-purple-50 px-2 py-0.5 rounded border border-purple-100">Manajer</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- KARTU 3: DATA PRODUKSI -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-5 pb-3 border-b border-slate-100">
                            <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Parameter Lini Produksi</h4>
                        </div>
                        <ul class="space-y-4">
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Lini Produk</span>
                                <span class="text-xs font-black text-slate-700 uppercase"><?= htmlspecialchars(str_replace('_', ' ', $doc['produk'])) ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kode Mesin</span>
                                <span class="text-xs font-black text-slate-700"><?= htmlspecialchars($doc['machine_id'] ?? '-') ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal Laporan</span>
                                <span class="text-xs font-bold text-slate-700"><?= htmlspecialchars($doc['tanggal']) ?></span>
                            </li>
                            <?php if ($doc['jenis'] == 'Uji_Lab' || $doc['jenis'] == 'Uji_Ulang'): ?>
                                <li class="flex justify-between items-center pt-2 border-t border-slate-50">
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Hasil Mutu Fisik</span>
                                    <?php $is_passed_mutu = ($doc['status_mutu'] == 'Passed' || $doc['status_mutu'] == 'Lolos'); ?>
                                    <span class="text-xs font-black <?= $is_passed_mutu ? 'text-emerald-800 bg-emerald-100 border-emerald-200' : 'text-rose-800 bg-rose-100 border-rose-200' ?> border px-2.5 py-0.5 rounded-lg">
                                        <?= $is_passed_mutu ? 'Mutu PASSED' : 'Mutu REJECT' ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                            <?php if ($doc['jenis'] == 'Diagnosis_Mesin' || $doc['jenis'] == 'Approval_Manager'): ?>
                                <li class="flex justify-between items-center pt-2 border-t border-slate-50">
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Otorisasi</span>
                                    <?php 
                                    $status_app = $doc['approval_status'] ?? 'Waiting Approval';
                                    $bg = 'text-amber-800 bg-amber-50 border-amber-100';
                                    if ($status_app == 'Approved') $bg = 'text-emerald-800 bg-emerald-100 border-emerald-200';
                                    elseif ($status_app == 'Rejected') $bg = 'text-rose-800 bg-rose-100 border-rose-200';
                                    elseif ($status_app == 'Hold') $bg = 'text-amber-900 bg-amber-100 border-amber-200';
                                    ?>
                                    <span class="text-xs font-black <?= $bg ?> border px-2.5 py-0.5 rounded-lg">
                                        <?= htmlspecialchars($status_app) ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                            <?php if (($doc['jenis'] == 'Uji_Ulang' || $doc['jenis'] == 'Approval_Manager') && !empty($doc['approved_at'])): ?>
                                <li class="flex flex-col gap-1.5 pt-2 border-t border-slate-50">
                                    <span class="text-xs font-black text-slate-500 uppercase tracking-wider">Total Durasi Downtime Mesin</span>
                                    <span class="text-base font-black text-rose-700 bg-rose-50 px-3 py-2 rounded-xl border border-rose-200">
                                        <?= getRepairDowntime($doc, $pdo) ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- KARTU 4: HASIL LAB & CATATAN -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-5 pb-3 border-b border-slate-100">
                            <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Pengujian Lab & Temuan</h4>
                        </div>
                        
                        <?php if ($doc['jenis'] === 'Uji_Lab' || $doc['jenis'] === 'Uji_Ulang'): ?>
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150 text-center">
                                <p class="text-xs font-black text-slate-500 uppercase tracking-wider mb-1">pH Air</p>
                                <p class="text-sm font-black text-slate-800"><?= htmlspecialchars($doc['ph'] ?? '-') ?></p>
                            </div>
                            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150 text-center">
                                <p class="text-xs font-black text-slate-500 uppercase tracking-wider mb-1">TDS (PPM)</p>
                                <p class="text-sm font-black text-slate-800"><?= htmlspecialchars($doc['tds'] ?? '-') ?></p>
                            </div>
                            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-150 text-center">
                                <p class="text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Kekeruhan</p>
                                <p class="text-sm font-black text-slate-800"><?= htmlspecialchars($doc['kekeruhan'] ?? '-') ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs font-black text-slate-500 uppercase tracking-wider">Catatan Lapangan</span>
                            <div class="text-xs font-semibold text-slate-700 bg-amber-50/40 p-3.5 rounded-xl border border-amber-100 leading-relaxed min-h-[80px]">
                                <?= !empty($doc['deskripsi']) ? $doc['deskripsi'] : '<span class="italic text-slate-400">Tidak ada catatan lapangan.</span>' ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- METADATA UNTUK CETAK FISIK (Disembunyikan di layar, muncul saat print) -->
                <div id="reportContent" class="hidden print:block bg-white p-2 print:p-0 mx-auto border-none" style="color: black; font-family: 'Times New Roman', serif; overflow: hidden;">
                    <div class="text-xs font-black text-blue-600 mb-4 no-print border border-blue-100 p-2 bg-blue-50/50 rounded-lg italic text-center">
                        ℹ️ RINGKASAN DATA DIGITAL UNTUK AUDIT & BASIS DATA
                    </div>
                    <table class="w-full border-b-2 border-black print-kop-line pb-2 mb-4">
                        <tr>
                            <td class="w-20 pb-2">
                                <div class="w-12 h-12 bg-blue-600 text-white flex items-center justify-center text-2xl font-black rounded-xl">MP</div>
                            </td>
                            <td class="pb-2 pl-2">
                                <h1 class="text-2xl font-bold uppercase leading-tight text-slate-900">PT. MINERAL PURE INDONESIA</h1>
                                <p class="text-xs font-bold uppercase mt-1 text-slate-500">Kawasan Industri Jababeka, Blok C-14, Bekasi - Indonesia</p>
                                <p class="text-xs mt-0.5 italic text-sky-600">Quality Control & Assurance Management System</p>
                            </td>
                            <td class="text-right pb-2">
                                <h2 class="text-sm font-bold uppercase tracking-widest border-b border-black inline-block mb-1">FORMULIR PERSETUJUAN MUTU</h2>
                                <p class="text-sm font-bold mt-1">No: <?= htmlspecialchars($doc['no_dokumen'] ?? "NEW-DOC") ?></p>
                            </td>
                        </tr>
                    </table>

                    <!-- DOCUMENT TITLE -->
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold uppercase underline decoration-2 underline-offset-4">
                            <?php if ($doc['jenis'] == 'Approval_Manager'): ?>
                                CERTIFICATE OF FINAL APPROVAL
                            <?php else: ?>
                                <?= htmlspecialchars(str_replace('_', ' ', $doc['jenis'])) ?>
                            <?php endif; ?>
                        </h3>
                        <?php if ($doc['jenis'] == 'Approval_Manager'): ?>
                            <p class="text-xs font-bold mt-2 uppercase text-slate-600">Dokumen Otorisasi Rilis Produk (Overall Final Approval)</p>
                        <?php endif; ?>
                    </div>

                    <!-- PRIMARY DATA TABLE -->
                    <table class="w-full text-sm mb-6 border-collapse">
                        <tr>
                            <td class="border border-black p-1.5 font-bold w-1/4 bg-gray-50 uppercase">TANGGAL</td>
                            <td class="border border-black p-1.5 w-1/4"><?= htmlspecialchars($doc['tanggal']) ?></td>
                            <td class="border border-black p-1.5 font-bold w-1/4 bg-gray-50 uppercase">KODE MESIN</td>
                            <td class="border border-black p-1.5 w-1/4"><?= htmlspecialchars($doc['machine_id'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="border border-black p-1.5 font-bold bg-gray-50 uppercase">BATCH / PRODUK</td>
                            <td class="border border-black p-1.5"><?= htmlspecialchars($doc['produk']) ?></td>
                            <td class="border border-black p-1.5 font-bold bg-gray-50 uppercase">INSPECTOR</td>
                            <td class="border border-black p-1.5"><?= htmlspecialchars($doc['inspector'] ?? '-') ?></td>
                        </tr>
                    </table>

                    <!-- VERDICT / STATUS BOX -->
                    <div class="border border-black p-4 mb-6 text-center">
                        <p class="text-xs font-bold uppercase mb-3">KESIMPULAN PEMERIKSAAN / VERDICT</p>
                        <div class="flex justify-center gap-10">
                            <?php if ($doc['jenis'] == 'Uji_Lab' || $doc['jenis'] == 'Uji_Ulang'): ?>
                                <?php 
                                $is_passed = ($doc['status_mutu'] == 'Passed' || $doc['status_mutu'] == 'Lolos');
                                $is_reject = ($doc['status_mutu'] == 'Reject');
                                ?>
                                <label class="flex items-center gap-2 text-xs font-bold">
                                    <div class="w-4 h-4 border border-black flex items-center justify-center <?= $is_passed ? 'bg-black text-white' : '' ?>">
                                        <?= $is_passed ? '✓' : '' ?>
                                    </div>
                                    PASSED / LOLOS (MUTU)
                                </label>
                                <label class="flex items-center gap-2 text-xs font-bold">
                                    <div class="w-4 h-4 border border-black flex items-center justify-center <?= $is_reject ? 'bg-black text-white' : '' ?>">
                                        <?= $is_reject ? '✓' : '' ?>
                                    </div>
                                    REJECT / GAGAL (MUTU)
                                </label>
                            <?php elseif ($doc['jenis'] == 'Diagnosis_Mesin' || $doc['jenis'] == 'Approval_Manager'): ?>
                                <?php
                                $is_approved = ($doc['approval_status'] == 'Approved');
                                $is_rejected = ($doc['approval_status'] == 'Rejected');
                                $is_hold = ($doc['approval_status'] == 'Hold' || $doc['status'] == 'Hold');
                                ?>
                                <label class="flex items-center gap-2 text-xs font-bold">
                                    <div class="w-4 h-4 border border-black flex items-center justify-center <?= $is_approved ? 'bg-black text-white' : '' ?>">
                                        <?= $is_approved ? '✓' : '' ?>
                                    </div>
                                    APPROVED / DISETUJUI
                                </label>
                                <label class="flex items-center gap-2 text-xs font-bold">
                                    <div class="w-4 h-4 border border-black flex items-center justify-center <?= $is_rejected ? 'bg-black text-white' : '' ?>">
                                        <?= $is_rejected ? '✓' : '' ?>
                                    </div>
                                    REJECTED / DITOLAK
                                </label>
                                <label class="flex items-center gap-2 text-xs font-bold">
                                    <div class="w-4 h-4 border border-black flex items-center justify-center <?= $is_hold ? 'bg-black text-white' : '' ?>">
                                        <?= $is_hold ? '✓' : '' ?>
                                    </div>
                                    HOLD / DITANGGUHKAN
                                </label>
                            <?php else: ?>
                                <!-- Record Only / Sampling / Perbaikan -->
                                <label class="flex items-center gap-2 text-xs font-bold uppercase">
                                    <div class="w-4 h-4 border border-black flex items-center justify-center bg-black text-white">✓</div>
                                    RECORD ONLY / CATATAN ALUR KERJA
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- TECHNICAL PARAMETERS -->
                    <?php if ($doc['jenis'] === 'Uji_Lab' || $doc['jenis'] === 'Uji_Ulang'): ?>
                    <table class="w-full text-sm mb-6 border-collapse text-center">
                        <thead>
                            <tr class="bg-gray-100 font-bold">
                                <td class="border border-black p-1.5">PARAMETER</td>
                                <td class="border border-black p-1.5">STANDAR / TARGET</td>
                                <td class="border border-black p-1.5">HASIL AKTUAL</td>
                                <td class="border border-black p-1.5">KETERANGAN</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-black p-2 font-bold text-left">Potential of Hydrogen (pH)</td>
                                <td class="border border-black p-2 italic text-gray-500">6.5 - 8.5</td>
                                <td class="border border-black p-2 font-bold text-base"><?= $doc['ph'] ?? '-' ?></td>
                                <td class="border border-black p-2"></td>
                            </tr>
                            <tr>
                                <td class="border border-black p-2 font-bold text-left">Total Dissolved Solids (TDS)</td>
                                <td class="border border-black p-2 italic text-gray-500">&lt; 500 PPM</td>
                                <td class="border border-black p-2 font-bold text-base"><?= $doc['tds'] ?? '-' ?></td>
                                <td class="border border-black p-2"></td>
                            </tr>
                            <tr>
                                <td class="border border-black p-2 font-bold text-left">Kekeruhan (Turbidity)</td>
                                <td class="border border-black p-2 italic text-gray-500">&lt; 1.5 NTU</td>
                                <td class="border border-black p-2 font-bold text-base"><?= $doc['kekeruhan'] ?? '-' ?></td>
                                <td class="border border-black p-2"></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <!-- DESCRIPTION AREA -->
                    <div class="border border-black p-4 mb-8 min-h-[150px]">
                        <?php if ($doc['jenis'] == 'Approval_Manager'): ?>
                            <p class="text-xs font-bold uppercase mb-3 border-b border-black inline-block">CATATAN KEPUTUSAN FINAL (FINAL DECISION NOTES):</p>
                        <?php else: ?>
                            <p class="text-xs font-bold uppercase mb-3 border-b border-black inline-block">TEMUAN & ANALISIS (FINDINGS & ANALYSIS):</p>
                        <?php endif; ?>
                        <div class="text-sm italic leading-relaxed">
                            <?= $doc['deskripsi'] ?: '<p class="mt-2 text-gray-200">__________________________________________________________________________________________</p>' ?>
                        </div>
                    </div>

                    <!-- SIGNATURE AREA -->
                    <table class="w-full text-xs text-center border-collapse mt-auto">
                        <tr>
                            <td class="w-1/3 pb-20 font-bold">DIBUAT OLEH (INSPECTOR)</td>
                            <td class="w-1/3 pb-20 font-bold">DIVERIFIKASI (ADMIN)</td>
                            <td class="w-1/3 pb-20 font-bold italic">OTORISASI (MANAGER)</td>
                        </tr>
                        <tr>
                            <td class="border-t border-black pt-2 font-bold uppercase">( <?= htmlspecialchars($doc['inspector'] ?? '________________') ?> )</td>
                            <td class="border-t border-black pt-2 font-bold uppercase">( <?= htmlspecialchars($doc['admin_entry_name'] ?? '________________') ?> )</td>
                            <td class="border-t border-black pt-2 font-bold relative uppercase">
                                ( <?= htmlspecialchars(explode('(', $doc['approved_by'] ?? '________________')[0]) ?> )
                                <?php if ($doc['approval_status'] == 'Approved'): ?>
                                    <div class="absolute -top-14 left-1/2 -translate-x-1/2 border-2 border-emerald-600 text-emerald-600 p-1.5 rotate-[-10deg] font-black text-xs uppercase tracking-wider bg-white/90">VERIFIED APPROVED</div>
                                <?php elseif ($doc['approval_status'] == 'Rejected' || $doc['status'] == 'Rejected'): ?>
                                    <div class="absolute -top-14 left-1/2 -translate-x-1/2 border-2 border-rose-600 text-rose-600 p-1.5 rotate-[-10deg] font-black text-xs uppercase tracking-wider bg-white/90">VERIFIED REJECTED</div>
                                <?php elseif ($doc['approval_status'] == 'Hold' || $doc['status'] == 'Hold'): ?>
                                    <div class="absolute -top-12 left-1/2 -translate-x-1/2 border-2 border-amber-500 text-amber-500 p-1 rotate-[-10deg] font-black text-[8px] uppercase tracking-wider bg-white/90">STATUS HOLD</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <!-- FOOTER -->
                    <div class="mt-6 pt-2 border-t border-gray-200 text-[7px] text-gray-400 flex justify-between uppercase font-bold italic print:hidden">
                        <span>QC-DMS Digital Integration System &bull; Mineral Pure</span>
                        <span>Audit Metadata Sheet &bull; Non-Othentic Reference</span>
                    </div>

                    <!-- FOOTER CETAK DINAMIS -->
                    <div class="hidden print:flex print-footer-container">
                        <div class="print-footer-left">
                            <div>Dicetak oleh: <?= htmlspecialchars($role_name_map[$_SESSION['role']] ?? $_SESSION['role']) ?></div>
                            <div>Dibuat oleh: <?= htmlspecialchars($doc['inspector'] ?? '-') ?></div>
                        </div>
                        <div class="print-footer-right">
                            <div>Waktu Pengesahan: <?= htmlspecialchars($doc['approved_at'] ?? '-') ?></div>
                            <div>Halaman: <span class="print-page-number"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 mb-20 no-print text-center border-t border-slate-200 pt-8">
            <p class="text-[10px] font-bold text-slate-400 uppercase mb-4 tracking-widest">Akhir dari Detail Laporan</p>
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 px-8 py-3 bg-slate-200 hover:bg-slate-355 text-slate-700 font-black text-xs uppercase rounded-xl transition-all border border-slate-300">
                <svg class="w-4 h-4 text-slate-650" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali
            </a>
        </div>

        <!-- Smart Trigger: Next Step Guidance (Digital Only) -->
        <?php if (empty($child_docs)): ?>
            <?php 
            $next_step_label = ""; $next_step_url = ""; $next_step_icon = ""; $next_step_color = "bg-blue-600";
            $next_step_role = ""; $next_step_role_name = ""; $is_waiting_approval = false;
            
            if ($doc['jenis'] == 'Catatan_Batch') {
                $next_step_label = "Lakukan Uji Laboratorium";
                $next_step_url = "add.php?step=2&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                $next_step_role = "Admin_Entry";
                $next_step_role_name = "Admin QC / Laboratorium";
                $next_step_icon = '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>';
            } elseif ($doc['jenis'] == 'Uji_Lab' || $doc['jenis'] == 'Uji_Ulang') {
                if ($doc['status'] == 'Reject') {
                    $next_step_label = "Lakukan Diagnosis Masalah";
                    $next_step_url = "add.php?step=3&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                    $next_step_role = "Pekerja_Lapangan";
                    $next_step_role_name = "Pekerja Lapangan / Teknisi";
                    $next_step_icon = '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>';
                    $next_step_color = "bg-rose-600";
                } elseif ($doc['approval_status'] == 'Waiting Approval') {
                    $next_step_label = "Buat Laporan Approval Final (Langkah 06)";
                    $next_step_url = "add.php?step=6&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                    $next_step_role = "Manager";
                    $next_step_role_name = "Manajer Produksi";
                    $next_step_icon = '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>';
                    $next_step_color = "bg-amber-500";
                }
            } elseif ($doc['jenis'] == 'Diagnosis_Mesin') {
                if ($doc['approval_status'] == 'Approved') {
                    $next_step_label = "Buat Laporan Perbaikan";
                    $next_step_url = "add.php?step=4&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                    $next_step_role = "Pekerja_Lapangan";
                    $next_step_role_name = "Pekerja Lapangan / Teknisi";
                    $next_step_icon = '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>';
                } else {
                    $is_waiting_approval = true;
                    $next_step_label = "Menunggu Approval Manajer";
                    $next_step_role = "Manager";
                    $next_step_role_name = "Manajer Produksi";
                    $next_step_color = "bg-amber-500";
                    $next_step_icon = '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                }
            } elseif ($doc['jenis'] == 'Laporan_Perbaikan') {
                $next_step_label = "Lakukan Uji Verifikasi (Re-test)";
                $next_step_url = "add.php?step=5&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                $next_step_role = "Pekerja_Lapangan";
                $next_step_role_name = "Pekerja Lapangan / Teknisi";
                $next_step_icon = '<svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>';
            }
            ?>

            <?php if ($next_step_label): ?>
                <div class="<?= $next_step_color ?> rounded-3xl p-6 text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6 shadow-lg no-print mt-6">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-white/10 rounded-2xl">
                            <?= $next_step_icon ?>
                        </div>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-tight opacity-75">Langkah Selanjutnya:</h4>
                            <p class="text-lg font-black leading-tight mt-0.5"><?= $next_step_label ?></p>
                        </div>
                    </div>
                    <?php if ($_SESSION['role'] == $next_step_role && !$is_waiting_approval): ?>
                        <a href="<?= $next_step_url ?>" class="w-full md:w-auto px-6 py-3 text-center bg-white <?= str_replace('bg-', 'text-', $next_step_color) ?> text-xs font-black uppercase rounded-xl shadow hover:opacity-90 transition-all">Lanjutkan Alur</a>
                    <?php else: ?>
                        <div class="w-full md:w-auto px-6 py-3 text-center bg-white/20 border border-white/30 text-white text-xs font-black uppercase rounded-xl">
                            ℹ️ Tahap selanjutnya menunggu proses <?= $next_step_role_name ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <script>
        function toggleMetadata() {
            const content = document.getElementById('metadataSection');
            const arrow = document.getElementById('metaArrow');
            content.classList.toggle('open');
            arrow.style.transform = content.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    </script>
    </main>
    </div>
    </div>
</body>
</html>