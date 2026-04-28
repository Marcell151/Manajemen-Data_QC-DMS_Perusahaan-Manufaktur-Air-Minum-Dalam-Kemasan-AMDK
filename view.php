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

$role_name = ($_SESSION['role'] == 'Admin_Entry') ? 'Admin Data Entry QC' : 'Manajer Produksi';

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
    <title>QC-DMS: <?= htmlspecialchars($doc['no_dokumen']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { margin: 0; size: auto; }
            .no-print, .no-print *, nav, aside, header, .action-area, .sidebar-container { display: none !important; visibility: hidden !important; opacity: 0 !important; height: 0 !important; margin: 0 !important; padding: 0 !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
            #reportContent { display: block !important; border: none !important; box-shadow: none !important; margin: 0 !important; width: 100% !important; padding: 0.4in !important; transform: none !important; }
            main { padding: 0 !important; margin: 0 !important; }
        }
        .metadata-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .metadata-content.open { max-height: 2000px; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'sidebar.php'; ?>

    <div class="max-w-5xl mx-auto py-8 px-8">
        <!-- Navigation Top (Digital Only) -->
        <div class="mb-8 no-print">
            <a href="index.php" class="text-slate-400 hover:text-blue-600 transition-all flex items-center gap-2 font-black text-[10px] uppercase tracking-widest">
                <span>←</span> Kembali ke Dashboard
            </a>
        </div>

        <!-- MANAGER APPROVAL PANEL (Pindah Ke Atas - Digital Only) -->
        <?php if ($_SESSION['role'] == 'Manager' && $doc['jenis'] == 'Approval_Manager' && ($doc['approval_status'] == 'Waiting Approval' || $doc['status'] == 'Pending')): ?>
            <div class="mb-12 bg-white rounded-3xl border-4 border-slate-900 overflow-hidden shadow-2xl no-print animate-pulse hover:animate-none transition-all">
                <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <span class="text-3xl">⚖️</span>
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-tight">Otorisasi Manajer Produksi</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tinjau Scan Di Bawah Sebelum Memberikan Keputusan</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <form method="POST" action="approve_action.php" class="inline">
                            <input type="hidden" name="doc_id" value="<?= $id ?>">
                            <input type="hidden" name="decision" value="Approved">
                            <button type="submit" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase rounded-xl shadow-lg transition-all">✅ Approve Laporan</button>
                        </form>
                        <form method="POST" action="approve_action.php" class="inline">
                            <input type="hidden" name="doc_id" value="<?= $id ?>">
                            <input type="hidden" name="decision" value="Hold">
                            <button type="submit" class="px-8 py-3 bg-rose-600 hover:bg-rose-700 text-white text-xs font-black uppercase rounded-xl shadow-lg transition-all">✋ Hold / Tolak</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- DOCUMENT PREVIEW HERO (Prioritas Dokumen Asli) -->
        <?php if (!empty($doc['file_path']) || !empty($doc['external_link'])): ?>
        <div class="mb-12 no-print">
            <div class="bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border-4 border-slate-800">
                <div class="p-4 bg-slate-800 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📄</span>
                        <h4 class="text-xs font-black text-white uppercase tracking-widest">Pratinjau Dokumen Asli (Scan/Link)</h4>
                    </div>
                    <?php if (!empty($doc['external_link'])): ?>
                        <a href="<?= htmlspecialchars($doc['external_link']) ?>" target="_blank" class="text-[10px] font-black text-blue-400 hover:text-white transition-all uppercase">Buka di Tab Baru ↗</a>
                    <?php endif; ?>
                </div>
                <div class="bg-slate-700 h-[600px] flex items-center justify-center overflow-hidden">
                    <?php if (!empty($doc['file_path'])): ?>
                        <?php 
                        $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])): ?>
                            <img src="<?= htmlspecialchars($doc['file_path']) ?>" class="max-w-full max-h-full object-contain">
                        <?php else: ?>
                            <iframe src="<?= htmlspecialchars($doc['file_path']) ?>" class="w-full h-full border-none"></iframe>
                        <?php endif; ?>
                    <?php elseif (!empty($doc['external_link'])): ?>
                        <iframe src="<?= htmlspecialchars($doc['external_link']) ?>" class="w-full h-full border-none bg-white"></iframe>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ACTION TOOLBAR (Central Position) -->
        <div class="mb-12 flex flex-col sm:flex-row justify-center items-center gap-4 sm:gap-6 no-print action-area">
            <?php if (!empty($doc['file_path'])): ?>
                <a href="<?= htmlspecialchars($doc['file_path']) ?>" download class="w-full sm:w-auto justify-center px-8 md:px-12 py-4 md:py-5 bg-blue-600 text-white text-xs md:text-sm font-black uppercase rounded-2xl hover:bg-blue-700 transition-all shadow-xl shadow-blue-600/30 flex items-center gap-3">
                    <span class="text-2xl">📥</span> Unduh Dokumen Asli (Scan)
                </a>
            <?php else: ?>
                <button onclick="window.print()" class="w-full sm:w-auto justify-center px-8 md:px-10 py-4 bg-white border-2 border-slate-200 text-slate-700 text-xs font-black uppercase rounded-2xl hover:bg-slate-50 transition-all shadow-sm flex items-center gap-3">
                    <span class="text-xl">🖨️</span> Cetak Ringkasan Digital
                </button>
            <?php endif; ?>
        </div>

        <!-- COLLAPSIBLE METADATA (Digital Summary) -->
        <div class="no-print mb-24">
            <button onclick="toggleMetadata()" class="w-full py-4 px-6 bg-slate-100 hover:bg-slate-200 rounded-2xl flex justify-between items-center transition-all border border-slate-200">
                <div class="flex items-center gap-4">
                    <span class="text-lg">📋</span>
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Ringkasan Metadata Sistem (Klik untuk Detail)</h4>
                </div>
                <span id="metaArrow" class="transform transition-transform duration-300">▼</span>
            </button>

            <div id="metadataSection" class="metadata-content mt-6">
                
                <!-- METADATA UNTUK TAMPILAN LAYAR (UI MODERN) -->
                <div class="no-print grid grid-cols-1 md:grid-cols-2 gap-6 p-2 mb-8">
                    
                    <!-- KARTU 1: PROPERTI FILE -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                            <span class="text-2xl">💾</span>
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Informasi Berkas (Sistem)</h4>
                        </div>
                        <ul class="space-y-4">
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tipe Format</span>
                                <span class="text-sm font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-lg"><?= $file_ext ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Ukuran Berkas</span>
                                <span class="text-sm font-black text-sky-600"><?= $file_size_formatted ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Waktu Unggah</span>
                                <span class="text-[11px] font-bold text-slate-700"><?= $file_upload_time ?></span>
                            </li>
                            <li class="flex flex-col gap-2 pt-2 border-t border-slate-50">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">MIME Type</span>
                                <span class="text-[11px] font-mono text-slate-500 bg-slate-50 p-2 rounded-lg border border-slate-100"><?= $file_mime ?></span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">MD5 Checksum (Integritas Data)</span>
                                <span class="text-[11px] font-mono text-emerald-600 bg-emerald-50 p-2 rounded-lg border border-emerald-100 break-all"><?= $file_hash ?></span>
                            </li>
                        </ul>
                    </div>

                    <!-- KARTU 2: AUDIT & AKSES -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                            <span class="text-2xl">🛡️</span>
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Kontrol Akses & Audit Trail</h4>
                        </div>
                        <ul class="space-y-4">
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">ID Database</span>
                                <span class="text-xs font-black text-slate-700">#<?= str_pad($doc['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Diunggah Oleh</span>
                                <span class="text-sm font-black text-slate-700"><?= htmlspecialchars($doc['admin_entry_name'] ?? 'System Admin') ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Inspektur (Fisik)</span>
                                <span class="text-sm font-black text-slate-700"><?= htmlspecialchars($doc['inspector'] ?? '-') ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Di-Approve Oleh (Manajer)</span>
                                <span class="text-sm font-black text-slate-700"><?= htmlspecialchars(explode('(', $doc['approved_by'] ?? '-')[0]) ?></span>
                            </li>
                            <li class="flex flex-col gap-2 pt-2 border-t border-slate-50">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Hak Akses Baca (Read)</span>
                                <div class="flex gap-2">
                                    <span class="text-[10px] font-bold text-blue-700 bg-blue-50 px-2 py-1 rounded-md border border-blue-100">Admin Entry</span>
                                    <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-1 rounded-md border border-purple-100">Manajer Produksi</span>
                                </div>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Otoritas Keputusan (Approval)</span>
                                <div class="flex gap-2">
                                    <span class="text-[10px] font-bold text-rose-700 bg-rose-50 px-2 py-1 rounded-md border border-rose-100">Hanya Manajer Produksi</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- KARTU 3: DATA PRODUKSI -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                            <span class="text-2xl">🏭</span>
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Parameter Produksi</h4>
                        </div>
                        <ul class="space-y-4">
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Jenis Lini Produk</span>
                                <span class="text-sm font-black text-slate-700"><?= htmlspecialchars(str_replace('_', ' ', $doc['produk'])) ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Kode Mesin</span>
                                <span class="text-sm font-black text-slate-700"><?= htmlspecialchars($doc['machine_id'] ?? '-') ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tanggal Laporan</span>
                                <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($doc['tanggal']) ?></span>
                            </li>
                            <li class="flex justify-between items-center pt-2 border-t border-slate-50">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Keputusan Akhir (Status)</span>
                                <?php if ($doc['status'] == 'Lolos' || $doc['status'] == 'Passed'): ?>
                                    <span class="text-xs font-black text-emerald-700 bg-emerald-100 px-3 py-1 rounded-lg">✓ LOLOS QC</span>
                                <?php else: ?>
                                    <span class="text-xs font-black text-rose-700 bg-rose-100 px-3 py-1 rounded-lg">✗ REJECT</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>

                    <!-- KARTU 4: HASIL LAB & CATATAN -->
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                            <span class="text-2xl">🔬</span>
                            <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest">Hasil Lab & Temuan</h4>
                        </div>
                        
                        <?php if ($doc['jenis'] === 'Uji_Lab' || $doc['jenis'] === 'Uji_Ulang'): ?>
                        <div class="grid grid-cols-3 gap-2 mb-6">
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">pH Air</p>
                                <p class="text-lg font-black text-slate-800"><?= htmlspecialchars($doc['ph'] ?? '-') ?></p>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">TDS (PPM)</p>
                                <p class="text-lg font-black text-slate-800"><?= htmlspecialchars($doc['tds'] ?? '-') ?></p>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Kekeruhan</p>
                                <p class="text-lg font-black text-slate-800"><?= htmlspecialchars($doc['kekeruhan'] ?? '-') ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="flex flex-col gap-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Catatan / Deskripsi Temuan</span>
                            <div class="text-xs font-medium text-slate-600 bg-amber-50/50 p-4 rounded-xl border border-amber-100/50 leading-relaxed min-h-[80px]">
                                <?= !empty($doc['deskripsi']) ? nl2br(htmlspecialchars($doc['deskripsi'])) : '<span class="italic opacity-50">Tidak ada catatan lapangan.</span>' ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- METADATA UNTUK CETAK FISIK (Disembunyikan di layar, muncul saat print) -->
                <div id="reportContent" class="hidden print:block bg-white p-[0.5in] mx-auto border border-slate-200 shadow-sm" style="width: 210mm; min-height: 260mm; color: black; font-family: 'Times New Roman', serif; overflow: hidden;">
                    <div class="text-[8px] font-black text-blue-600 mb-4 no-print border border-blue-100 p-2 bg-blue-50/50 rounded-lg italic text-center">
                        ℹ️ RINGKASAN DATA DIGITAL UNTUK AUDIT & BASIS DATA
                    </div>
            <table class="w-full border-b-2 border-black pb-2 mb-4">
                <tr>
                    <td class="w-20 pb-2">
                        <div class="w-12 h-12 bg-black text-white flex items-center justify-center text-2xl font-bold">MP</div>
                    </td>
                    <td class="pb-2">
                        <h1 class="text-xl font-bold uppercase leading-none">PT. MINERAL PURE INDONESIA</h1>
                        <p class="text-[9px] font-bold uppercase mt-1">Kawasan Industri Jababeka, Blok C-14, Bekasi - Indonesia</p>
                        <p class="text-[8px] mt-0.5 italic">Quality Control & Assurance Management System</p>
                    </td>
                    <td class="text-right pb-2">
                        <h2 class="text-[10px] font-bold uppercase tracking-widest border-b border-black inline-block mb-1">FORMULIR MUTU</h2>
                        <p class="text-[9px] font-bold mt-1">No: <?= htmlspecialchars($doc['no_dokumen'] ?? "NEW-DOC") ?></p>
                    </td>
                </tr>
            </table>

            <!-- DOCUMENT TITLE -->
            <div class="text-center mb-4">
                <h3 class="text-lg font-bold uppercase underline decoration-2 underline-offset-4">
                    <?= htmlspecialchars(str_replace('_', ' ', $doc['jenis'])) ?>
                </h3>
            </div>

            <!-- PRIMARY DATA TABLE -->
            <table class="w-full text-[10px] mb-4 border-collapse">
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
            <div class="border border-black p-2 mb-4 text-center">
                <p class="text-[8px] font-bold uppercase mb-1">KESIMPULAN PEMERIKSAAN / VERDICT</p>
                <div class="flex justify-center gap-10">
                    <label class="flex items-center gap-2 text-xs font-bold">
                        <div class="w-4 h-4 border border-black flex items-center justify-center <?= $doc['status'] == 'Lolos' ? 'bg-black text-white' : '' ?>">
                            <?= $doc['status'] == 'Lolos' ? '✓' : '' ?>
                        </div>
                        PASSED / LOLOS
                    </label>
                    <label class="flex items-center gap-2 text-xs font-bold">
                        <div class="w-4 h-4 border border-black flex items-center justify-center <?= $doc['status'] == 'Reject' ? 'bg-black text-white' : '' ?>">
                            <?= $doc['status'] == 'Reject' ? '✓' : '' ?>
                        </div>
                        REJECT / GAGAL
                    </label>
                </div>
            </div>

            <!-- TECHNICAL PARAMETERS -->
            <?php if ($doc['jenis'] === 'Uji_Lab' || $doc['jenis'] === 'Uji_Ulang'): ?>
            <table class="w-full text-[10px] mb-4 border-collapse text-center">
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
                        <td class="border border-black p-1.5 font-bold text-left">Potential of Hydrogen (pH)</td>
                        <td class="border border-black p-1.5 italic text-gray-500">6.5 - 8.5</td>
                        <td class="border border-black p-1.5 font-bold text-sm"><?= $doc['ph'] ?? '-' ?></td>
                        <td class="border border-black p-1.5"></td>
                    </tr>
                    <tr>
                        <td class="border border-black p-1.5 font-bold text-left">Total Dissolved Solids (TDS)</td>
                        <td class="border border-black p-1.5 italic text-gray-500">< 500 PPM</td>
                        <td class="border border-black p-1.5 font-bold text-sm"><?= $doc['tds'] ?? '-' ?></td>
                        <td class="border border-black p-1.5"></td>
                    </tr>
                    <tr>
                        <td class="border border-black p-1.5 font-bold text-left">Kekeruhan (Turbidity)</td>
                        <td class="border border-black p-1.5 italic text-gray-500">< 1.5 NTU</td>
                        <td class="border border-black p-1.5 font-bold text-sm"><?= $doc['kekeruhan'] ?? '-' ?></td>
                        <td class="border border-black p-1.5"></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- DESCRIPTION AREA -->
            <div class="border border-black p-3 mb-6 min-h-[120px]">
                <p class="text-[8px] font-bold uppercase mb-2 border-b border-black inline-block">TEMUAN & ANALISIS (FINDINGS & ANALYSIS):</p>
                <div class="text-[10px] italic leading-tight">
                    <?= $doc['deskripsi'] ?: '<p class="mt-2 text-gray-200">__________________________________________________________________________________________</p>' ?>
                </div>
            </div>

            <!-- SIGNATURE AREA -->
            <table class="w-full text-[9px] text-center border-collapse mt-auto">
                <tr>
                    <td class="w-1/3 pb-16 font-bold">DIBUAT OLEH (INSPECTOR)</td>
                    <td class="w-1/3 pb-16 font-bold">DIVERIFIKASI (ADMIN)</td>
                    <td class="w-1/3 pb-16 font-bold italic">OTORISASI (MANAGER)</td>
                </tr>
                <tr>
                    <td class="border-t border-black pt-1 font-bold uppercase">( <?= htmlspecialchars($doc['inspector'] ?? '________________') ?> )</td>
                    <td class="border-t border-black pt-1 font-bold uppercase">( <?= htmlspecialchars($doc['admin_entry_name'] ?? '________________') ?> )</td>
                    <td class="border-t border-black pt-1 font-bold relative uppercase">
                        ( <?= htmlspecialchars(explode('(', $doc['approved_by'] ?? '________________')[0]) ?> )
                        <?php if ($doc['approval_status'] == 'Approved'): ?>
                            <div class="absolute -top-12 left-1/2 -translate-x-1/2 border border-black p-0.5 rotate-[-10deg] font-black text-[8px] uppercase opacity-60">VERIFIED APPROVED</div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <!-- FOOTER -->
            <div class="mt-6 pt-2 border-t border-gray-200 text-[7px] text-gray-400 flex justify-between uppercase font-bold italic">
                <span>QC-DMS Digital Integration System • Mineral Pure</span>
                <span>Audit Metadata Sheet • Non-Othentic Reference</span>
            </div>
        </div>



        <div class="mt-12 mb-24 no-print text-center border-t border-slate-100 pt-12">
            <p class="text-[10px] font-bold text-slate-300 uppercase mb-4 tracking-widest">Akhir dari Detail Laporan</p>
            <a href="index.php" class="inline-flex items-center gap-2 px-10 py-4 bg-slate-200 text-slate-600 font-black text-[11px] uppercase rounded-2xl hover:bg-slate-300 transition-all">
                ← Selesai & Kembali ke Dashboard
            </a>
        </div>

        <!-- Smart Trigger: Next Step Guidance (Digital Only) -->
        <?php if ($_SESSION['role'] == 'Admin_Entry' && empty($child_docs)): ?>
            <?php 
            $next_step_label = ""; $next_step_url = ""; $next_step_icon = ""; $next_step_color = "bg-blue-600";
            
            if ($doc['jenis'] == 'Catatan_Batch') {
                $next_step_label = "Lakukan Uji Laboratorium";
                $next_step_url = "add.php?step=2&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                $next_step_icon = "🔬";
            } elseif ($doc['jenis'] == 'Uji_Lab' || $doc['jenis'] == 'Uji_Ulang') {
                if ($doc['status'] == 'Reject') {
                    $next_step_label = "Lakukan Diagnosis Masalah";
                    $next_step_url = "add.php?step=3&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                    $next_step_icon = "⚙️"; $next_step_color = "bg-rose-600";
                } else {
                    $next_step_label = "Minta Approval Manager";
                    $next_step_url = "add.php?step=6&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                    $next_step_icon = "⚖️"; $next_step_color = "bg-emerald-600";
                }
            } elseif ($doc['jenis'] == 'Diagnosis_Mesin') {
                $next_step_label = "Buat Laporan Perbaikan";
                $next_step_url = "add.php?step=4&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                $next_step_icon = "🛠️";
            } elseif ($doc['jenis'] == 'Laporan_Perbaikan') {
                $next_step_label = "Lakukan Uji Verifikasi (Re-test)";
                $next_step_url = "add.php?step=5&m_id=".urlencode($doc['machine_id'])."&prod=".urlencode($doc['produk'])."&p_id=".$id;
                $next_step_icon = "🧪";
            }
            ?>

            <?php if ($next_step_label): ?>
                <div class="<?= $next_step_color ?> rounded-3xl p-8 text-white flex justify-between items-center shadow-xl no-print mt-12">
                    <div class="flex items-center gap-4">
                        <span class="text-4xl"><?= $next_step_icon ?></span>
                        <div>
                            <h4 class="text-sm font-black uppercase tracking-tight">Saran Langkah Selanjutnya:</h4>
                            <p class="text-lg font-black leading-tight"><?= $next_step_label ?></p>
                        </div>
                    </div>
                    <a href="<?= $next_step_url ?>" class="px-8 py-3 bg-white <?= str_replace('bg-', 'text-', $next_step_color) ?> text-xs font-black uppercase rounded-xl shadow-lg hover:opacity-90 transition-all">Lanjutkan Alur</a>
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