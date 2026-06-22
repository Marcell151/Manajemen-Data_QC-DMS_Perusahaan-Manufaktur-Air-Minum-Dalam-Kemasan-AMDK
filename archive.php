<?php
require 'db.php';

// Filter & Search
$search = $_GET['search'] ?? null;

$query = "SELECT * FROM documents WHERE status IN ('Archived', 'Rejected', 'Hold', 'Aborted')";
$params = [];

if ($search) {
    $query .= " AND (nama_dokumen LIKE ? OR no_dokumen LIKE ? OR produk LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$query .= " ORDER BY archived_at DESC, id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_archived = $pdo->query("SELECT COUNT(*) FROM documents WHERE status IN ('Archived', 'Rejected', 'Hold', 'Aborted')")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Arsip - Mineral Pure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #0284c7;
            --success: #059669;
            --bg-main: #f8fafc;
        }

        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: var(--bg-main); color: #1e293b; }
        h1, h2, h3, h4 { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }

        .stat-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 1.25rem; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-2px); border-color: var(--primary); box-shadow: 0 8px 20px -5px rgba(0,0,0,0.04); }

        /* ---- MOBILE DOCUMENT CARD ---- */
        .doc-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 1.1rem;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            transition: all 0.15s;
            text-decoration: none;
            color: inherit;
        }
        .doc-card:active { transform: scale(0.98); background: #f8fafc; }
        .doc-card .card-title { font-size: 0.95rem; font-weight: 700; color: #1e293b; line-height: 1.3; }
        .doc-card .card-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 0.4rem; }
        .doc-card .card-bottom { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
        .doc-card .open-btn { 
            padding: 0.5rem 1.2rem; 
            background: #059669;
            color: white; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-step { background: #f1f5f9; color: #475569; }
        .badge-file { background: #f1f5f9; color: #475569; }
        .badge-link { background: #e0f2fe; color: #0284c7; }
        .badge-passed { background: #d1fae5; color: #065f46; }
        .badge-reject { background: #fee2e2; color: #991b1b; }
        .badge-waiting { background: #fef3c7; color: #92400e; }

        @media (max-width: 767px) {
            .desktop-table-area { display: none !important; }
            .mobile-cards-area { display: flex !important; }
            .stat-grid { grid-template-columns: 1fr !important; }
            .stat-card { padding: 1.25rem !important; border-radius: 18px !important; }
        }
        @media (min-width: 768px) {
            .mobile-cards-area { display: none !important; }
        }
    </style>
</head>
<body class="antialiased">
    <?php include 'sidebar.php'; ?>

    <div class="p-4 max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">Riwayat Arsip</h1>
                <p class="text-slate-600 font-semibold mt-1 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    Dokumen Hasil Uji Mutu yang Telah Diarsipkan (Archived / Rejected / Hold)
                </p>
            </div>
            <div class="flex items-center gap-4 bg-white p-3 pr-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-green-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-green-100">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500 mb-0.5">Status Sistem</p>
                    <p class="text-base font-black text-slate-800 tracking-tight leading-none">Arsip Terkunci</p>
                </div>
            </div>
        </div>

        <!-- Global Status Tabs -->
        <div class="mb-8 flex border-b border-slate-200">
            <a href="index.php" class="px-6 py-4 font-bold text-sm md:text-base uppercase tracking-wider border-b-4 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors">Sedang Diproses</a>
            <a href="archive.php" class="px-6 py-4 font-black text-sm md:text-base uppercase tracking-wider border-b-4 border-emerald-600 text-emerald-700">Selesai / Riwayat</a>
        </div>

        <!-- Stat Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 stat-grid">
            <div class="stat-card border-l-4 border-l-emerald-500 group md:col-span-1">
                <p class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1 group-hover:text-emerald-500 transition-colors">Total Arsip</p>
                <h3 class="text-3xl font-extrabold text-slate-900"><?= $total_archived ?></h3>
                <p class="text-xs text-slate-600 mt-1 font-bold uppercase">Laporan Disahkan</p>
            </div>
            <div class="stat-card border-l-4 border-l-sky-500 group md:col-span-2">
                <p class="text-xs font-black text-slate-600 uppercase tracking-widest mb-1">Catatan Keamanan Arsip</p>
                <p class="text-xs text-slate-600 font-semibold leading-relaxed mt-2">
                    Semua dokumen di bawah ini telah ditandatangani dan disetujui secara digital oleh Manajer Produksi. Perubahan data arsip ini dikunci oleh sistem dan hanya dapat diakses untuk kebutuhan audit operasional.
                </p>
            </div>
        </div>

        <!-- Controls Bar -->
        <div class="mb-6">
            <form action="" method="GET" class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Cari arsip laporan, kode, produk..." class="pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold focus:border-emerald-500 outline-none w-full transition-all">
                <span class="absolute left-3.5 top-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
            </form>
        </div>

        <!-- DESKTOP TABLE VIEW -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden desktop-table-area mb-20">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-8 py-4 text-left text-xs font-black text-slate-600 uppercase tracking-widest">Detail Laporan</th>
                            <th class="px-8 py-4 text-left text-xs font-black text-slate-600 uppercase tracking-widest">Waktu Resolusi (Lead Time)</th>
                            <th class="px-8 py-4 text-center text-xs font-black text-slate-600 uppercase tracking-widest">Status Otorisasi</th>
                            <th class="px-8 py-4 text-right text-xs font-black text-slate-600 uppercase tracking-widest">Opsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($files as $file): ?>
                            <tr class="hover:bg-emerald-50/10 transition-colors">
                                <td class="px-8 py-6">
                                    <a href="view.php?id=<?= $file['id'] ?>" class="block group">
                                        <p class="font-bold text-slate-800 text-base group-hover:text-emerald-600 transition-colors"><?= htmlspecialchars($file['nama_dokumen']) ?></p>
                                        <div class="flex items-center gap-3 mt-1">
                                            <p class="text-xs font-black text-slate-500 tracking-widest uppercase"><?= htmlspecialchars($file['no_dokumen']) ?></p>
                                            <span class="text-xs text-slate-350">•</span>
                                            <p class="text-xs font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded uppercase tracking-tight"><?= str_replace('_', ' ', $file['produk']) ?></p>
                                        </div>
                                    </a>
                                </td>
                                <td class="px-8 py-6">
                                     <div class="flex flex-col gap-1">
                                         <span class="text-sm font-black text-emerald-700 flex items-center gap-1.5">
                                             <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                             <?php if ($file['jenis'] == 'Approval_Manager'): ?>
                                                 Durasi Downtime: <?= getRepairDowntime($file, $pdo) ?>
                                             <?php else: ?>
                                                 Lead Time: <?= formatLeadTime($file['created_at'], $file['approved_at']) ?>
                                             <?php endif; ?>
                                         </span>
                                         <span class="text-xs font-bold text-slate-500 uppercase tracking-tight">
                                             Mulai: <?= $file['created_at'] ?> &bull; Selesai: <?= $file['approved_at'] ?>
                                         </span>
                                     </div>
                                 </td>
                                 <td class="px-8 py-6 text-center">
                                     <div class="flex items-center justify-center gap-2">
                                         <?php if ($file['status'] == 'Archived'): ?>
                                             <span class="px-3 py-1 bg-emerald-600 text-white rounded-full text-xs font-black uppercase tracking-wider shadow-sm">
                                                 ARCHIVED
                                             </span>
                                         <?php elseif ($file['status'] == 'Rejected'): ?>
                                             <span class="px-3 py-1 bg-rose-600 text-white rounded-full text-xs font-black uppercase tracking-wider shadow-sm">
                                                 REJECTED
                                             </span>
                                         <?php elseif ($file['status'] == 'Hold'): ?>
                                             <span class="px-3 py-1 bg-amber-500 text-white rounded-full text-xs font-black uppercase tracking-wider shadow-sm">
                                                 HOLD
                                             </span>
                                         <?php else: ?>
                                             <span class="px-3 py-1 bg-slate-650 text-white rounded-full text-xs font-black uppercase tracking-wider shadow-sm">
                                                 <?= htmlspecialchars(strtoupper($file['status'])) ?>
                                             </span>
                                         <?php endif; ?>

                                         <?php if ($file['jenis'] == 'Uji_Lab' || $file['jenis'] == 'Uji_Ulang'): ?>
                                             <?php 
                                             $is_passed = ($file['status_mutu'] == 'Passed' || $file['status_mutu'] == 'Lolos');
                                             $bg_class = $is_passed ? 'bg-emerald-100 text-emerald-800 border-emerald-250' : 'bg-rose-100 text-rose-800 border-rose-250';
                                             $label = $is_passed ? 'Mutu PASSED' : 'Mutu REJECT';
                                             ?>
                                             <span class="px-3 py-1 border rounded-full text-xs font-black uppercase tracking-wider shadow-sm <?= $bg_class ?>">
                                                 <?= $label ?>
                                             </span>
                                         <?php endif; ?>
                                     </div>
                                 </td>
                                <td class="px-8 py-6 text-right">
                                    <a href="view.php?id=<?= $file['id'] ?>" class="text-xs font-black text-emerald-600 hover:bg-emerald-600 hover:text-white px-4 py-2 border-2 border-emerald-600 rounded-xl transition-all shadow-sm">BUKA</a>
                                </td>
                             </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($files)): ?>
                <div class="py-20 text-center bg-slate-50/50">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5z"></path>
                    </svg>
                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Tidak Ada Arsip Dokumen Yang Ditemukan</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- MOBILE CARD VIEW -->
        <div class="mobile-cards-area flex-col gap-3 mb-20" style="display:none">
            <?php if (empty($files)): ?>
                <div class="py-16 text-center bg-white rounded-2xl border border-slate-200">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h5l2 2h9a2 2 0 012 2v8a2 2 0 01-2 2H5z"></path>
                    </svg>
                    <p class="text-sm font-black text-slate-400 uppercase tracking-widest">Tidak Ada Arsip</p>
                </div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                <a href="view.php?id=<?= $file['id'] ?>" class="doc-card border-l-4 <?= $file['status'] == 'Archived' ? 'border-l-emerald-500' : ($file['status'] == 'Rejected' ? 'border-l-rose-500' : ($file['status'] == 'Hold' ? 'border-l-amber-500' : 'border-l-slate-400')) ?>">
                    <div class="card-title"><?= htmlspecialchars($file['nama_dokumen']) ?></div>
                    <div class="card-meta">
                        <span class="badge-pill badge-step">Arsip</span>
                        <span style="color:#cbd5e1;font-size:10px;">&bull;</span>
                        <span style="font-size:0.8rem;font-weight:700;color:#0284c7;text-transform:uppercase"><?= str_replace('_', ' ', $file['produk']) ?></span>
                    </div>
                    <div class="mt-1 p-2 bg-emerald-50 rounded-lg text-xs font-bold text-emerald-800 border border-emerald-100 flex items-center gap-1">
                          <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                          <span>
                              <?php if ($file['jenis'] == 'Approval_Manager'): ?>
                                  Downtime: <strong><?= getRepairDowntime($file, $pdo) ?></strong>
                              <?php else: ?>
                                  Lead Time: <strong><?= formatLeadTime($file['created_at'], $file['approved_at']) ?></strong>
                              <?php endif; ?>
                          </span>
                    </div>
                    <div class="card-bottom">
                        <div style="display:flex;flex-direction:column;gap:4px">
                            <span style="font-size:0.75rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.06em"><?= htmlspecialchars($file['no_dokumen']) ?></span>
                            <div class="flex flex-wrap gap-1">
                                <?php if ($file['status'] == 'Archived'): ?>
                                    <span class="badge-pill badge-passed">ARCHIVED</span>
                                <?php elseif ($file['status'] == 'Rejected'): ?>
                                    <span class="badge-pill badge-reject">REJECTED</span>
                                <?php elseif ($file['status'] == 'Hold'): ?>
                                    <span class="badge-pill badge-waiting">HOLD</span>
                                <?php else: ?>
                                    <span class="badge-pill bg-slate-100 text-slate-700 border border-slate-200"><?= htmlspecialchars(strtoupper($file['status'])) ?></span>
                                <?php endif; ?>

                                <?php if ($file['jenis'] == 'Uji_Lab' || $file['jenis'] == 'Uji_Ulang'): ?>
                                    <?php $is_passed_mutu = ($file['status_mutu'] == 'Passed' || $file['status_mutu'] == 'Lolos'); ?>
                                    <span class="badge-pill <?= $is_passed_mutu ? 'badge-passed' : 'badge-reject' ?>">
                                        <?= $is_passed_mutu ? 'Mutu Passed' : 'Mutu Reject' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="open-btn">BUKA</span>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
