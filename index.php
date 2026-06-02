<?php
require 'db.php';

$filter = $_GET['filter'] ?? null;
$search = $_GET['search'] ?? null;
$status_filter = $_GET['status_filter'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

$query = "SELECT * FROM documents WHERE 1=1";
$params = [];

// Apply status filter or default (Active/Pending only)
if ($status_filter === 'Approved') {
    $query .= " AND (approval_status = 'Approved' OR status = 'Archived')";
} elseif ($status_filter === 'Pending') {
    $query .= " AND (status = 'Pending' OR approval_status = 'Waiting Approval')";
} elseif ($status_filter === 'Hold') {
    $query .= " AND (status = 'Hold' OR approval_status = 'Hold')";
} else {
    // Default: only display Pending/Aktif (non-archived)
    $query .= " AND status != 'Archived'";
}

if ($filter == 'waiting') {
    $query .= " AND approval_status = 'Waiting Approval'";
} elseif ($filter == 'step1') {
    $query .= " AND jenis = 'Catatan_Batch'";
} elseif ($filter == 'step2') {
    $query .= " AND jenis = 'Uji_Lab'";
} elseif ($filter == 'step3') {
    $query .= " AND jenis = 'Diagnosis_Mesin'";
} elseif ($filter == 'step4') {
    $query .= " AND jenis = 'Laporan_Perbaikan'";
} elseif ($filter == 'step5') {
    $query .= " AND jenis = 'Uji_Ulang'";
} elseif ($filter == 'step6') {
    $query .= " AND jenis = 'Approval_Manager'";
}

if ($search) {
    $query .= " AND (nama_dokumen LIKE ? OR no_dokumen LIKE ? OR produk LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if (!empty($start_date)) {
    $query .= " AND tanggal >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $query .= " AND tanggal <= ?";
    $params[] = $end_date;
}

$query .= " ORDER BY tanggal DESC, id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats (Only count active/pending documents, i.e., non-archived)
$total_docs = $pdo->query("SELECT COUNT(*) FROM documents WHERE status != 'Archived'")->fetchColumn();
$total_reject = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'Reject'")->fetchColumn();
$inspeksi_bulan_ini = $pdo->query("SELECT COUNT(*) FROM documents WHERE strftime('%m', tanggal) = strftime('%m', 'now') AND status != 'Archived'")->fetchColumn();
$waiting_approval = $pdo->query("SELECT COUNT(*) FROM documents WHERE approval_status = 'Waiting Approval'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Mutu - Mineral Pure</title>
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
        
        .btn-filter { padding: 0.5rem 1rem; border-radius: 12px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; transition: all 0.2s; }
        .btn-filter.active { background: var(--primary); color: white; }
        .btn-filter:not(.active) { background: white; color: #64748b; border: 1px solid #e2e8f0; }
        .btn-filter:hover:not(.active) { border-color: var(--primary); color: var(--primary); }

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

        /* Hide/show based on screen size */
        @media (max-width: 767px) {
            .desktop-table-area { display: none !important; }
            .mobile-cards-area { display: flex !important; }
            /* Compact stat cards on mobile */
            .stat-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 0.75rem !important; }
            .stat-card { padding: 1rem !important; border-radius: 18px !important; }
            .stat-card h3 { font-size: 2rem !important; }
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
                <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 drop-shadow-sm">Ringkasan Mutu</h1>
                <p class="text-slate-500 font-medium mt-1 flex items-center gap-2">
                    <span class="w-2 h-2 bg-sky-500 rounded-full animate-pulse"></span>
                    PT. Mineral Pure Indonesia • Unit Manufaktur
                </p>
            </div>
            <div class="flex items-center gap-4 bg-white/80 backdrop-blur-md p-3 pr-8 rounded-3xl border border-white shadow-xl shadow-slate-200/50">
                <div class="w-14 h-14 bg-gradient-to-br from-sky-400 to-blue-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-lg shadow-blue-200">
                    💧
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-0.5">Aktif Sebagai</p>
                    <p class="text-lg font-black text-slate-800 tracking-tight leading-none">
                        <?php
                            if($_SESSION['role'] == 'Pekerja_Lapangan') echo "Teknisi Lapangan";
                            elseif($_SESSION['role'] == 'Admin_Entry') echo "Admin QC / Lab";
                            else echo "Manajer Produksi";
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Stat Grid -->
        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 md:mb-12 stat-grid">
            <div class="stat-card border-l-4 border-l-sky-500 group">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 group-hover:text-sky-500 transition-colors">Total</p>
                <h3 class="text-4xl font-extrabold text-slate-900"><?= $total_docs ?></h3>
                <p class="text-[9px] text-slate-400 mt-1 font-bold uppercase tracking-tighter">Laporan</p>
            </div>
            <div class="stat-card border-emerald-100 bg-emerald-50/20 border-l-4 border-l-emerald-500 group">
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Lolos</p>
                <h3 class="text-4xl font-extrabold text-emerald-700"><?= $inspeksi_bulan_ini ?></h3>
                <p class="text-[9px] text-emerald-600/50 mt-1 font-bold uppercase tracking-tighter">Bulan Ini</p>
            </div>
            <div class="stat-card border-rose-100 bg-rose-50/20 border-l-4 border-l-rose-500 group">
                <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1">Reject</p>
                <h3 class="text-4xl font-extrabold text-rose-700"><?= $total_reject ?></h3>
                <p class="text-[9px] text-rose-600/50 mt-1 font-bold uppercase tracking-tighter">Tindak Lanjut</p>
            </div>
            <div class="stat-card border-amber-100 bg-amber-50/20 border-l-4 border-l-amber-500 group">
                <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1">Approval</p>
                <h3 class="text-4xl font-extrabold text-amber-700"><?= $waiting_approval ?></h3>
                <p class="text-[9px] text-amber-600/50 mt-1 font-bold uppercase tracking-tighter">Menunggu</p>
            </div>
        </div>

        <!-- Controls Bar (Search + Filter) -->
        <form action="index.php" method="GET" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm mb-6 flex flex-col md:flex-row gap-4 items-end">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter ?? '') ?>">
            
            <div class="flex-grow grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Cari Kata Kunci</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Cari laporan, kode, produk..." class="w-full px-4 py-2.5 text-sm font-semibold bg-slate-50 border border-slate-200 rounded-xl focus:border-sky-500 focus:bg-white outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>" class="w-full px-4 py-2.5 text-sm font-semibold bg-slate-50 border border-slate-200 rounded-xl focus:border-sky-500 focus:bg-white outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>" class="w-full px-4 py-2.5 text-sm font-semibold bg-slate-50 border border-slate-200 rounded-xl focus:border-sky-500 focus:bg-white outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2">Status Dokumen</label>
                    <select name="status_filter" class="w-full px-4 py-2.5 text-sm font-semibold bg-slate-50 border border-slate-200 rounded-xl focus:border-sky-500 focus:bg-white outline-none transition-all">
                        <option value="">Semua Status</option>
                        <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $status_filter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Hold" <?= $status_filter == 'Hold' ? 'selected' : '' ?>>Hold</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-black uppercase rounded-xl transition-all shadow-md">Filter</button>
                <a href="index.php" class="w-full md:w-auto px-6 py-2.5 text-center bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-black uppercase rounded-xl transition-all">Reset</a>
            </div>
        </form>

        <div class="mb-4 flex flex-wrap gap-2">
            <a href="index.php?status_filter=<?= htmlspecialchars($status_filter ?? '') ?>&start_date=<?= htmlspecialchars($start_date ?? '') ?>&end_date=<?= htmlspecialchars($end_date ?? '') ?>&search=<?= htmlspecialchars($search ?? '') ?>" class="btn-filter <?= !$filter ? 'active' : '' ?>">Semua</a>
            <a href="index.php?filter=waiting&status_filter=<?= htmlspecialchars($status_filter ?? '') ?>&start_date=<?= htmlspecialchars($start_date ?? '') ?>&end_date=<?= htmlspecialchars($end_date ?? '') ?>&search=<?= htmlspecialchars($search ?? '') ?>" class="btn-filter <?= $filter == 'waiting' ? 'active' : '' ?>">Perlu Approval</a>
        </div>

        <!-- ============================================================ -->
        <!-- DESKTOP TABLE VIEW (hidden on mobile) -->
        <!-- ============================================================ -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden desktop-table-area">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Detail Laporan</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Tahapan</th>
                        <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($files as $file): ?>
                        <tr class="hover:bg-sky-50/30 transition-colors">
                            <td class="px-8 py-6">
                                <a href="view.php?id=<?= $file['id'] ?>" class="block group">
                                    <p class="font-bold text-slate-800 text-base group-hover:text-sky-600 transition-colors"><?= htmlspecialchars($file['nama_dokumen']) ?></p>
                                    <div class="flex items-center gap-3 mt-1">
                                        <p class="text-[10px] font-black text-slate-400 tracking-widest uppercase"><?= htmlspecialchars($file['no_dokumen']) ?></p>
                                        <span class="text-[10px] text-slate-300">•</span>
                                        <p class="text-[10px] font-bold text-sky-600 uppercase tracking-tighter"><?= str_replace('_', ' ', $file['produk']) ?></p>
                                        <?php if (!empty($file['approved_at'])): ?>
                                            <span class="text-[10px] text-slate-300">•</span>
                                            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-tighter">Waktu Resolusi: <?= formatLeadTime($file['created_at'], $file['approved_at']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-[9px] font-black text-slate-500 uppercase tracking-tighter">
                                        <?= str_replace('_', ' ', $file['jenis']) ?>
                                    </span>
                                    <?php if (!empty($file['file_path'])): ?>
                                        <span class="flex items-center gap-1 px-2 py-1 bg-slate-100 rounded text-[8px] font-black text-slate-500 uppercase tracking-tighter">&#128196; FILE</span>
                                    <?php endif; ?>
                                    <?php if (!empty($file['external_link'])): ?>
                                        <span class="flex items-center gap-1 px-2 py-1 bg-sky-100 rounded text-[8px] font-black text-sky-600 uppercase tracking-tighter">&#9729;&#65039; LINK</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <?php if ($file['jenis'] == 'Approval_Manager'): ?>
                                    <span class="px-3 py-1.5 <?= $file['approval_status'] == 'Approved' ? 'bg-emerald-600' : 'bg-amber-500' ?> text-white rounded-full text-[9px] font-black uppercase tracking-widest italic shadow-sm">
                                        <?= $file['approval_status'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-4 py-1.5 <?= ($file['status'] == 'Lolos' || $file['status'] == 'Passed') ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200' ?> border rounded-full text-[10px] font-black uppercase">
                                        <?= ($file['status'] == 'Lolos' || $file['status'] == 'Passed') ? '✓ LOLOS' : '✗ REJECT' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <a href="view.php?id=<?= $file['id'] ?>" class="text-[10px] font-black text-sky-600 hover:bg-sky-600 hover:text-white px-4 py-2 border-2 border-sky-600 rounded-xl transition-all">BUKA</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <?php if (empty($files)): ?>
                <div class="py-20 text-center bg-slate-50/50">
                    <p class="text-4xl mb-4">&#129416;</p>
                    <p class="text-xs font-black text-slate-300 uppercase tracking-widest">Tidak Ada Laporan Yang Ditemukan</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================ -->
        <!-- MOBILE CARD VIEW (hidden on desktop) -->
        <!-- ============================================================ -->
        <div class="mobile-cards-area flex-col gap-3" style="display:none">
            <?php if (empty($files)): ?>
                <div class="py-16 text-center bg-white rounded-3xl border border-slate-200">
                    <p class="text-5xl mb-3">&#129416;</p>
                    <p class="text-sm font-black text-slate-300 uppercase tracking-widest">Tidak Ada Laporan</p>
                </div>
            <?php else: ?>
                <?php foreach ($files as $file): 
                    $is_passed = ($file['status'] == 'Lolos' || $file['status'] == 'Passed');
                    $is_approval = ($file['jenis'] == 'Approval_Manager');
                    $is_approved = ($file['approval_status'] == 'Approved');
                    $is_waiting  = ($file['approval_status'] == 'Waiting Approval');
                ?>
                <a href="view.php?id=<?= $file['id'] ?>" class="doc-card">
                    <!-- Top Row: Document Name -->
                    <div class="card-title"><?= htmlspecialchars($file['nama_dokumen']) ?></div>
                    
                    <!-- Meta Row: Code + Type -->
                    <div class="card-meta">
                        <span class="badge-pill badge-step"><?= str_replace('_', ' ', $file['jenis']) ?></span>
                        <span style="color:#cbd5e1;font-size:10px;">•</span>
                        <span style="font-size:0.7rem;font-weight:700;color:#0284c7;text-transform:uppercase"><?= str_replace('_', ' ', $file['produk']) ?></span>
                        <?php if (!empty($file['file_path'])): ?>
                            <span class="badge-pill badge-file">&#128196; File</span>
                        <?php endif; ?>
                        <?php if (!empty($file['external_link'])): ?>
                            <span class="badge-pill badge-link">&#9729; Link</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($file['approved_at'])): ?>
                        <div class="p-2 bg-emerald-50 rounded-lg text-[10px] font-semibold text-emerald-800">
                             Waktu Resolusi: <strong><?= formatLeadTime($file['created_at'], $file['approved_at']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <!-- Bottom Row: Status + Action -->
                    <div class="card-bottom">
                        <div style="display:flex;flex-direction:column;gap:2px">
                            <span style="font-size:0.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em"><?= htmlspecialchars($file['no_dokumen']) ?></span>
                            <?php if ($is_approval): ?>
                                <span class="badge-pill <?= $is_approved ? 'badge-passed' : ($is_waiting ? 'badge-waiting' : 'badge-reject') ?>">
                                    <?= $is_approved ? '✓ Approved' : ($is_waiting ? '⏳ Waiting' : $file['approval_status']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge-pill <?= $is_passed ? 'badge-passed' : 'badge-reject' ?>">
                                    <?= $is_passed ? '✓ LOLOS' : '✗ REJECT' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="open-btn">BUKA</span>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    </div>
    </div>
</body>
</html>