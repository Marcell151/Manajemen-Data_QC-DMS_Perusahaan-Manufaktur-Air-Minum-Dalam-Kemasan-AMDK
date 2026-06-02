<?php
require 'db.php';

// Filter & Search
$search = $_GET['search'] ?? null;

$query = "SELECT * FROM documents WHERE status = 'Archived'";
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
$total_archived = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'Archived'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Arsip - Mineral Pure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Outfit:wght@500;800&display=swap');
        
        :root {
            --primary: #0284c7;
            --success: #059669;
            --bg-main: #f8fafc;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); color: #1e293b; }
        h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; }

        .stat-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; padding: 1.5rem; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-3px); border-color: var(--primary); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }

        /* ---- MOBILE DOCUMENT CARD ---- */
        .doc-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 1rem 1.1rem;
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
            background: #0284c7;
            color: white; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-pill {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-step { background: #f1f5f9; color: #64748b; }
        .badge-file { background: #f1f5f9; color: #64748b; }
        .badge-link { background: #e0f2fe; color: #0284c7; }
        .badge-passed { background: #d1fae5; color: #047857; }
        .badge-reject { background: #fee2e2; color: #b91c1c; }
        .badge-waiting { background: #fef3c7; color: #b45309; }

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
        <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 drop-shadow-sm">Riwayat Arsip</h1>
                <p class="text-slate-500 font-medium mt-1 flex items-center gap-2">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    Dokumen Hasil Uji Mutu yang Dinyatakan Selesai (Archived)
                </p>
            </div>
            <div class="flex items-center gap-4 bg-white/80 backdrop-blur-md p-3 pr-8 rounded-3xl border border-white shadow-xl shadow-slate-200/50">
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-400 to-green-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg shadow-green-200">
                    📁
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-0.5">Status Sistem</p>
                    <p class="text-lg font-black text-slate-800 tracking-tight leading-none">Arsip Terkunci</p>
                </div>
            </div>
        </div>

        <!-- Stat Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 md:mb-12 stat-grid">
            <div class="stat-card border-l-4 border-l-emerald-500 group md:col-span-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 group-hover:text-emerald-500 transition-colors">Total Arsip</p>
                <h3 class="text-4xl font-extrabold text-slate-900"><?= $total_archived ?></h3>
                <p class="text-[9px] text-slate-400 mt-1 font-bold uppercase tracking-tighter">Laporan Disahkan</p>
            </div>
            <div class="stat-card border-l-4 border-l-sky-500 group md:col-span-2">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Catatan Keamanan Arsip</p>
                <p class="text-xs text-slate-500 font-medium leading-relaxed mt-2">
                    Semua dokumen di bawah ini telah ditandatangani dan disetujui secara digital oleh Manajer Produksi. Perubahan data arsip ini dikunci oleh sistem dan hanya dapat diakses untuk kebutuhan audit operasional.
                </p>
            </div>
        </div>

        <!-- Controls Bar -->
        <div class="mb-6">
            <form action="" method="GET" class="relative">
                <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Cari arsip laporan, kode, produk..." class="pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold focus:border-emerald-500 outline-none w-full transition-all">
                <span class="absolute left-3 top-3 text-slate-300 text-base">&#128269;</span>
            </form>
        </div>

        <!-- DESKTOP TABLE VIEW -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden desktop-table-area">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Detail Laporan</th>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Waktu Resolusi (Lead Time)</th>
                            <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Status Otorisasi</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Opsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($files as $file): ?>
                            <tr class="hover:bg-emerald-50/20 transition-colors">
                                <td class="px-8 py-6">
                                    <a href="view.php?id=<?= $file['id'] ?>" class="block group">
                                        <p class="font-bold text-slate-800 text-base group-hover:text-emerald-600 transition-colors"><?= htmlspecialchars($file['nama_dokumen']) ?></p>
                                        <div class="flex items-center gap-3 mt-1">
                                            <p class="text-[10px] font-black text-slate-400 tracking-widest uppercase"><?= htmlspecialchars($file['no_dokumen']) ?></p>
                                            <span class="text-[10px] text-slate-300">•</span>
                                            <p class="text-[10px] font-bold text-sky-600 uppercase tracking-tighter"><?= str_replace('_', ' ', $file['produk']) ?></p>
                                        </div>
                                    </a>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm font-black text-emerald-600">
                                            <?= formatLeadTime($file['created_at'], $file['approved_at']) ?>
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">
                                            Mulai: <?= $file['created_at'] ?> &bull; Selesai: <?= $file['approved_at'] ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span class="px-3 py-1.5 bg-emerald-600 text-white rounded-full text-[9px] font-black uppercase tracking-widest italic shadow-sm">
                                        APPROVED
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <a href="view.php?id=<?= $file['id'] ?>" class="text-[10px] font-black text-emerald-600 hover:bg-emerald-600 hover:text-white px-4 py-2 border-2 border-emerald-600 rounded-xl transition-all">BUKA</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($files)): ?>
                <div class="py-20 text-center bg-slate-50/50">
                    <p class="text-4xl mb-4">📁</p>
                    <p class="text-xs font-black text-slate-300 uppercase tracking-widest">Tidak Ada Arsip Dokumen Yang Ditemukan</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- MOBILE CARD VIEW -->
        <div class="mobile-cards-area flex-col gap-3" style="display:none">
            <?php if (empty($files)): ?>
                <div class="py-16 text-center bg-white rounded-3xl border border-slate-200">
                    <p class="text-5xl mb-3">📁</p>
                    <p class="text-sm font-black text-slate-300 uppercase tracking-widest">Tidak Ada Arsip</p>
                </div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                <a href="view.php?id=<?= $file['id'] ?>" class="doc-card border-l-4 border-l-emerald-500">
                    <div class="card-title"><?= htmlspecialchars($file['nama_dokumen']) ?></div>
                    <div class="card-meta">
                        <span class="badge-pill badge-step">Arsip</span>
                        <span style="color:#cbd5e1;font-size:10px;">•</span>
                        <span style="font-size:0.7rem;font-weight:700;color:#0284c7;text-transform:uppercase"><?= str_replace('_', ' ', $file['produk']) ?></span>
                    </div>
                    <div class="mt-1 p-2 bg-emerald-50 rounded-lg text-[10px] font-semibold text-emerald-800">
                         Waktu Resolusi: <strong><?= formatLeadTime($file['created_at'], $file['approved_at']) ?></strong>
                    </div>
                    <div class="card-bottom">
                        <div style="display:flex;flex-direction:column;gap:2px">
                            <span style="font-size:0.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em"><?= htmlspecialchars($file['no_dokumen']) ?></span>
                            <span class="badge-pill badge-passed">✓ APPROVED</span>
                        </div>
                        <span class="open-btn" style="background-color:#059669">BUKA</span>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
