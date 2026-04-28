<?php
require 'db.php';

// Filter & Search
$filter = $_GET['filter'] ?? null;
$search = $_GET['search'] ?? null;
$path = $_GET['path'] ?? 'QC_AMDK';

// Switch Role Simulation
if (isset($_GET['switch_role'])) {
    $_SESSION['role'] = $_GET['switch_role'];
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'Admin_Entry';
}

$query = "SELECT * FROM documents WHERE 1=1";
$params = [];

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

$query .= " ORDER BY tanggal DESC, id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_docs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
$total_reject = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'Reject'")->fetchColumn();
$inspeksi_bulan_ini = $pdo->query("SELECT COUNT(*) FROM documents WHERE strftime('%m', tanggal) = strftime('%m', 'now')")->fetchColumn();
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
                        <?= $_SESSION['role'] == 'Admin_Entry' ? 'Admin Entry' : 'Manajer Produksi' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Stat Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="stat-card border-l-4 border-l-sky-500 group">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 group-hover:text-sky-500 transition-colors">Total Laporan</p>
                <h3 class="text-4xl font-extrabold text-slate-900"><?= $total_docs ?></h3>
                <p class="text-[9px] text-slate-400 mt-2 font-bold uppercase tracking-tighter">Arsip Keseluruhan</p>
            </div>
            <div class="stat-card border-emerald-100 bg-emerald-50/20 border-l-4 border-l-emerald-500 group">
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1 group-hover:translate-x-1 transition-transform">Lolos Bulan Ini</p>
                <h3 class="text-4xl font-extrabold text-emerald-700"><?= $inspeksi_bulan_ini ?></h3>
                <p class="text-[9px] text-emerald-600/50 mt-2 font-bold uppercase tracking-tighter">Kualitas Terjaga</p>
            </div>
            <div class="stat-card border-rose-100 bg-rose-50/20 border-l-4 border-l-rose-500 group">
                <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1 group-hover:translate-x-1 transition-transform">Temuan Reject</p>
                <h3 class="text-4xl font-extrabold text-rose-700"><?= $total_reject ?></h3>
                <p class="text-[9px] text-rose-600/50 mt-2 font-bold uppercase tracking-tighter">Butuh Tindak Lanjut</p>
            </div>
            <div class="stat-card border-amber-100 bg-amber-50/20 border-l-4 border-l-amber-500 group">
                <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1 group-hover:translate-x-1 transition-transform">Butuh Approval</p>
                <h3 class="text-4xl font-extrabold text-amber-700"><?= $waiting_approval ?></h3>
                <p class="text-[9px] text-amber-600/50 mt-2 font-bold uppercase tracking-tighter">Otorisasi Manajer</p>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 md:p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/50">
                <div class="flex flex-wrap justify-center gap-2">
                    <a href="index.php" class="btn-filter <?= !$filter ? 'active' : '' ?>">Semua Data</a>
                    <a href="index.php?filter=waiting" class="btn-filter <?= $filter == 'waiting' ? 'active' : '' ?>">Butuh Approval</a>
                </div>
                <form action="" method="GET" class="relative w-full md:w-auto">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Kode / Produk..." class="pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold focus:border-sky-500 outline-none w-full md:w-64 transition-all">
                    <span class="absolute left-3 top-2.5 opacity-30">🔍</span>
                </form>
            </div>

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
                                    </div>
                                </a>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-[9px] font-black text-slate-500 uppercase tracking-tighter">
                                        <?= str_replace('_', ' ', $file['jenis']) ?>
                                    </span>
                                    
                                    <!-- SOURCE INDICATOR -->
                                    <?php if (!empty($file['file_path'])): ?>
                                        <span class="flex items-center gap-1 px-2 py-1 bg-slate-100 rounded text-[8px] font-black text-slate-500 uppercase tracking-tighter" title="Dokumen Fisik (Upload)">
                                            📄 FILE
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($file['external_link'])): ?>
                                        <span class="flex items-center gap-1 px-2 py-1 bg-sky-100 rounded text-[8px] font-black text-sky-600 uppercase tracking-tighter" title="Dokumen Cloud (Link)">
                                            ☁️ LINK
                                        </span>
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
                    <p class="text-4xl mb-4">🧊</p>
                    <p class="text-xs font-black text-slate-300 uppercase tracking-widest">Tidak Ada Laporan Yang Ditemukan</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    </div>
    </div>
</body>
</html>