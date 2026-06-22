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
    // Default: only display active documents (not Archived or Rejected)
    if ($filter == 'step6') {
        $query .= " AND status NOT IN ('Rejected')"; // Allow Archived for step 6
    } else {
        $query .= " AND status NOT IN ('Archived', 'Rejected')";
    }
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

// --- PENDING TASKS / INBOX LOGIC ---
$pending_tasks = [];
if ($filter && $filter !== 'waiting') {
    $inbox_query = "";
    if ($filter == 'step2') {
        $inbox_query = "SELECT * FROM documents WHERE jenis = 'Catatan_Batch' AND status != 'Rejected' AND id NOT IN (SELECT parent_doc_id FROM documents WHERE parent_doc_id IS NOT NULL)";
    } elseif ($filter == 'step3') {
        $inbox_query = "SELECT * FROM documents WHERE jenis = 'Uji_Lab' AND status_mutu = 'Reject' AND id NOT IN (SELECT parent_doc_id FROM documents WHERE parent_doc_id IS NOT NULL)";
    } elseif ($filter == 'step4') {
        $inbox_query = "SELECT * FROM documents WHERE jenis = 'Diagnosis_Mesin' AND approval_status = 'Approved' AND id NOT IN (SELECT parent_doc_id FROM documents WHERE parent_doc_id IS NOT NULL)";
    } elseif ($filter == 'step5') {
        $inbox_query = "SELECT * FROM documents WHERE jenis = 'Laporan_Perbaikan' AND status != 'Rejected' AND id NOT IN (SELECT parent_doc_id FROM documents WHERE parent_doc_id IS NOT NULL)";
    } elseif ($filter == 'step6') {
        $inbox_query = "SELECT * FROM documents WHERE ((jenis = 'Uji_Lab' AND (status_mutu = 'Passed' OR status_mutu = 'Lolos')) OR (jenis = 'Uji_Ulang' AND (status_mutu = 'Passed' OR status_mutu = 'Lolos'))) AND id NOT IN (SELECT parent_doc_id FROM documents WHERE parent_doc_id IS NOT NULL AND jenis = 'Approval_Manager')";
    }
    
    if (!empty($inbox_query)) {
        $stmt_inbox = $pdo->query($inbox_query);
        $pending_tasks = $stmt_inbox->fetchAll(PDO::FETCH_ASSOC);
    }
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

// Stats (Only count active/pending documents, i.e., non-archived and non-rejected)
$total_docs = $pdo->query("SELECT COUNT(*) FROM documents WHERE status NOT IN ('Archived', 'Rejected')")->fetchColumn();
$total_reject = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'Reject'")->fetchColumn();
$inspeksi_bulan_ini = $pdo->query("SELECT COUNT(*) FROM documents WHERE strftime('%m', tanggal) = strftime('%m', 'now') AND status NOT IN ('Archived', 'Rejected')")->fetchColumn();
$waiting_approval = $pdo->query("SELECT COUNT(*) FROM documents WHERE approval_status = 'Waiting Approval'")->fetchColumn();

// Grouping documents by step type for the pipeline view (only used when filter is null/empty)
$jenis_to_step = [
    'Catatan_Batch' => 'step1',
    'Uji_Lab' => 'step2',
    'Diagnosis_Mesin' => 'step3',
    'Laporan_Perbaikan' => 'step4',
    'Uji_Ulang' => 'step5',
    'Approval_Manager' => 'step6'
];

$pipeline_data = [
    'step1' => [],
    'step2' => [],
    'step3' => [],
    'step4' => [],
    'step5' => [],
    'step6' => []
];

foreach ($files as $file) {
    $s_key = $jenis_to_step[$file['jenis']] ?? null;
    if ($s_key) {
        $pipeline_data[$s_key][] = $file;
    }
}

// Clean water color scheme mapping
$theme_classes = [
    'sky' => [
        'header' => 'bg-sky-50/80 border-sky-100 text-sky-900',
        'icon' => 'text-sky-600',
        'border' => 'border-sky-200',
        'badge' => 'bg-sky-100 text-sky-800'
    ],
    'blue' => [
        'header' => 'bg-blue-50/80 border-blue-100 text-blue-900',
        'icon' => 'text-blue-600',
        'border' => 'border-blue-200',
        'badge' => 'bg-blue-100 text-blue-800'
    ],
    'slate' => [
        'header' => 'bg-slate-100/80 border-slate-200 text-slate-900',
        'icon' => 'text-slate-600',
        'border' => 'border-slate-300',
        'badge' => 'bg-slate-200 text-slate-850'
    ],
    'cyan' => [
        'header' => 'bg-cyan-50/80 border-cyan-100 text-cyan-900',
        'icon' => 'text-cyan-600',
        'border' => 'border-cyan-200',
        'badge' => 'bg-cyan-100 text-cyan-800'
    ],
    'teal' => [
        'header' => 'bg-teal-50/80 border-teal-100 text-teal-900',
        'icon' => 'text-teal-600',
        'border' => 'border-teal-200',
        'badge' => 'bg-teal-100 text-teal-800'
    ],
    'emerald' => [
        'header' => 'bg-emerald-50/80 border-emerald-100 text-emerald-900',
        'icon' => 'text-emerald-600',
        'border' => 'border-emerald-200',
        'badge' => 'bg-emerald-100 text-emerald-800'
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mutu - Mineral Pure</title>
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
        
        .btn-filter { padding: 0.5rem 1rem; border-radius: 12px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; transition: all 0.2s; }
        .btn-filter.active { background: var(--primary); color: white; }
        .btn-filter:not(.active) { background: white; color: #475569; border: 1px solid #cbd5e1; }
        .btn-filter:hover:not(.active) { border-color: var(--primary); color: var(--primary); }

        .stage-box {
            background: #fff;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            min-height: 250px;
            max-height: 600px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);
            transition: border-color 0.2s;
        }
        .stage-box:hover {
            border-color: #cbd5e1;
        }
        
        .doc-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #f1f5f9;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: all 0.15s;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        }
        .doc-card:hover { 
            transform: translateY(-2px); 
            border-color: #0284c7; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
        }
        .doc-card:active { 
            transform: scale(0.98); 
        }

        .badge-pill {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
    </style>
</head>
<body class="antialiased">
    <?php include 'sidebar.php'; ?>

    <div class="p-4 max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">Ringkasan Mutu</h1>
                <p class="text-slate-600 font-semibold mt-1 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 bg-sky-500 rounded-full animate-pulse"></span>
                    PT. Mineral Pure Indonesia &bull; Unit Manufaktur Air Minum Dalam Kemasan
                </p>
            </div>
            <div class="flex items-center gap-4 bg-white p-3 pr-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-12 h-12 bg-gradient-to-br from-sky-400 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-blue-100">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500 mb-0.5">Aktif Sebagai</p>
                    <p class="text-base font-black text-slate-800 tracking-tight leading-none">
                        <?php
                            if($_SESSION['role'] == 'Pekerja_Lapangan') echo "Teknisi Lapangan";
                            elseif($_SESSION['role'] == 'Admin_Entry') echo "Admin QC / Lab";
                            else echo "Manajer Produksi";
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Global Status Tabs -->
        <div class="mb-8 flex border-b border-slate-200">
            <a href="index.php" class="px-6 py-4 font-black text-sm md:text-base uppercase tracking-wider border-b-4 border-sky-600 text-sky-700">Sedang Diproses</a>
            <a href="archive.php" class="px-6 py-4 font-bold text-sm md:text-base uppercase tracking-wider border-b-4 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-colors">Selesai / Riwayat</a>
        </div>

        <!-- Stat Grid -->
        <?php if (!$filter): ?>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="stat-card border-l-4 border-l-sky-500">
                <p class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Total Aktif</p>
                <h3 class="text-3xl font-extrabold text-slate-900"><?= $total_docs ?></h3>
                <p class="text-xs text-slate-650 mt-1 font-bold uppercase">Dokumen Proses</p>
            </div>
            <div class="stat-card border-l-4 border-l-emerald-500 bg-emerald-50/10">
                <p class="text-xs font-black text-emerald-800 uppercase tracking-widest mb-1">Lolos Uji</p>
                <h3 class="text-3xl font-extrabold text-emerald-700"><?= $inspeksi_bulan_ini ?></h3>
                <p class="text-xs text-emerald-750 mt-1 font-bold uppercase">Bulan Ini</p>
            </div>
            <div class="stat-card border-l-4 border-l-rose-500 bg-rose-50/10">
                <p class="text-xs font-black text-rose-800 uppercase tracking-widest mb-1">Reject Lab</p>
                <h3 class="text-3xl font-extrabold text-rose-700"><?= $total_reject ?></h3>
                <p class="text-xs text-rose-750 mt-1 font-bold uppercase">Butuh Tindak Lanjut</p>
            </div>
            <div class="stat-card border-l-4 border-l-amber-500 bg-amber-50/10">
                <p class="text-xs font-black text-amber-800 uppercase tracking-widest mb-1">Approval</p>
                <h3 class="text-3xl font-extrabold text-amber-700"><?= $waiting_approval ?></h3>
                <p class="text-xs text-amber-800 mt-1 font-bold uppercase">Menunggu Otorisasi</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Controls Bar (Search + Filter) -->
        <form action="index.php" method="GET" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-col md:flex-row gap-4 items-end">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter ?? '') ?>">
            
            <div class="flex-grow grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">Cari Kata Kunci</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Nama, kode, produk..." class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold focus:border-sky-500 focus:bg-white outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>" class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold focus:border-sky-500 focus:bg-white outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>" class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold focus:border-sky-500 focus:bg-white outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">Status Dokumen</label>
                    <select name="status_filter" class="w-full px-4 py-2 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold focus:border-sky-500 focus:bg-white outline-none transition-all">
                        <option value="">Semua Status</option>
                        <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $status_filter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Hold" <?= $status_filter == 'Hold' ? 'selected' : '' ?>>Hold</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto px-6 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-black uppercase rounded-xl transition-all shadow-sm">Filter</button>
                <a href="index.php" class="w-full md:w-auto px-6 py-2 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black uppercase rounded-xl transition-all border border-slate-200">Reset</a>
            </div>
        </form>

        <!-- Sub Filter Bar -->
        <?php if (!$filter || $filter == 'waiting'): ?>
        <div class="mb-6 flex flex-wrap gap-2">
            <a href="index.php?status_filter=<?= htmlspecialchars($status_filter ?? '') ?>&start_date=<?= htmlspecialchars($start_date ?? '') ?>&end_date=<?= htmlspecialchars($end_date ?? '') ?>&search=<?= htmlspecialchars($search ?? '') ?>" class="btn-filter <?= !$filter ? 'active' : '' ?>">Semua Tahap</a>
            <a href="index.php?filter=waiting&status_filter=<?= htmlspecialchars($status_filter ?? '') ?>&start_date=<?= htmlspecialchars($start_date ?? '') ?>&end_date=<?= htmlspecialchars($end_date ?? '') ?>&search=<?= htmlspecialchars($search ?? '') ?>" class="btn-filter <?= $filter == 'waiting' ? 'active' : '' ?>">Butuh Approval</a>
        </div>
        <?php endif; ?>

        <!-- ============================================================ -->
        <!-- PIPELINE / STAGE BOXES VIEW (100% Responsive Grid) -->
        <!-- ============================================================ -->
        <?php if ($filter && $filter !== 'waiting'): 
            // Focused step view
            $step_info = $steps_config[$filter] ?? null;
            $step_files = $pipeline_data[$filter] ?? [];
            $theme = $theme_classes[$step_info['color']] ?? $theme_classes['sky'];
        ?>
            <!-- Focused Header with clear return button -->
            <div class="mb-6 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-black px-2.5 py-1 rounded bg-slate-100 text-slate-700"><?= $step_info['num'] ?></span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $theme['header'] ?>">
                        <svg class="w-5 h-5 <?= $theme['icon'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <?= $step_info['svg'] ?>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-800"><?= $step_info['title'] ?></h2>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5">Menampilkan dokumen pada tahap ini saja</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <?php 
                    $can_add = false;
                    if ($_SESSION['role'] == 'Pekerja_Lapangan' && in_array($filter, ['step1', 'step3', 'step4', 'step5'])) $can_add = true;
                    if ($_SESSION['role'] == 'Admin_Entry' && $filter == 'step2') $can_add = true;
                    if ($_SESSION['role'] == 'Manager' && $filter == 'step6') $can_add = true;
                    
                    if ($can_add): 
                        $step_num = str_replace('step', '', $filter);
                    ?>
                        <a href="add.php?step=<?= $step_num ?>" class="px-5 py-2.5 bg-slate-900 hover:bg-black text-white text-xs font-black uppercase rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Laporan Tanpa Induk
                        </a>
                    <?php endif; ?>
                    <a href="index.php" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black uppercase rounded-xl border border-slate-200 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Semua Tahapan
                    </a>
                </div>
            </div>

            <!-- PENDING TASKS / INBOX SECTION -->
            <?php if (!empty($pending_tasks)): ?>
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Inbox / Perlu Dikerjakan (<?= count($pending_tasks) ?>)</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($pending_tasks as $ptask): 
                        $can_process = false;
                        if ($_SESSION['role'] == 'Pekerja_Lapangan' && in_array($filter, ['step1', 'step3', 'step4', 'step5'])) $can_process = true;
                        if ($_SESSION['role'] == 'Admin_Entry' && $filter == 'step2') $can_process = true;
                        if ($_SESSION['role'] == 'Manager' && $filter == 'step6') $can_process = true;
                        
                        $step_num = str_replace('step', '', $filter);
                    ?>
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 p-5 rounded-2xl shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                            <div>
                                <div class="flex justify-between items-start mb-3">
                                    <span class="text-[10px] font-black px-2.5 py-1 bg-white border border-amber-200 text-amber-700 rounded-lg uppercase tracking-wider"><?= htmlspecialchars(str_replace('_', ' ', $ptask['jenis'])) ?></span>
                                    <span class="text-[10px] font-bold text-slate-500 bg-white px-2 py-1 rounded border border-slate-100"><?= date('d M', strtotime($ptask['tanggal'])) ?></span>
                                </div>
                                <h4 class="font-bold text-slate-800 text-sm mb-1.5"><?= htmlspecialchars($ptask['no_dokumen']) ?></h4>
                                <p class="text-xs text-slate-600 font-semibold mb-1">Batch: <span class="text-sky-700"><?= htmlspecialchars($ptask['produk']) ?></span></p>
                                <p class="text-[10px] text-slate-500 uppercase">Mesin: <?= htmlspecialchars($ptask['machine_id'] ?? '-') ?></p>
                            </div>
                            <?php if ($can_process): ?>
                                <a href="add.php?step=<?= $step_num ?>&parent_doc_id=<?= $ptask['id'] ?>" class="mt-5 w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black uppercase text-center rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm shadow-amber-500/20">
                                    Proses Langkah <?= $step_num ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            <?php else: ?>
                                <div class="mt-5 w-full py-2.5 bg-white text-slate-400 text-xs font-black uppercase text-center rounded-xl border border-slate-200">
                                    Menunggu Tim Terkait
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="flex items-center gap-2 mb-4 mt-8">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Riwayat Selesai</h3>
            </div>
            <?php endif; ?>

            <!-- List of documents for this specific step -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-16">
                <?php if (empty($step_files)): ?>
                    <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-200 p-8">
                        <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm font-black text-slate-400 uppercase tracking-wider">Tidak ada dokumen di tahap ini</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($step_files as $file): ?>
                        <?= renderDocumentCard($file, $pdo) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($filter === 'waiting'): 
            // Focused waiting approval view
        ?>
            <div class="mb-6 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-800">Menunggu Otorisasi Manajer</h2>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5">Menampilkan dokumen Diagnosis Mesin (03) dan Approval Final (06) yang menunggu approval</p>
                    </div>
                </div>
                <a href="index.php" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black uppercase rounded-xl border border-slate-200 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Tampilkan Semua Tahapan
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-16">
                <?php if (empty($files)): ?>
                    <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-slate-200 p-8">
                        <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <p class="text-sm font-black text-slate-400 uppercase tracking-wider">Tidak ada dokumen yang butuh approval saat ini</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                        <?= renderDocumentCard($file, $pdo) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php else: 
            // Default Overview: Grid of 6 Pipeline stages (3x2 on desktop, vertical list on mobile)
        ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-20">
                <?php foreach ($steps_config as $s_id => $step): 
                    $step_files = $pipeline_data[$s_id] ?? [];
                    $theme = $theme_classes[$step['color']] ?? $theme_classes['sky'];
                    $count = count($step_files);
                ?>
                    <div class="stage-box p-4 flex flex-col gap-4">
                        <!-- Stage Box Header -->
                        <div class="p-3.5 rounded-2xl flex items-center justify-between border <?= $theme['header'] ?>">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-black px-1.5 py-0.5 rounded bg-white/40"><?= $step['num'] ?></span>
                                <svg class="w-4 h-4 flex-shrink-0 <?= $theme['icon'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <?= $step['svg'] ?>
                                </svg>
                                <span class="font-bold text-[13px] uppercase tracking-wide leading-none"><?= $step['title'] ?></span>
                            </div>
                            <span class="text-xs font-black px-2 py-0.5 rounded-full <?= $theme['badge'] ?>">
                                <?= $count ?>
                            </span>
                        </div>

                        <!-- Document List in Stage (Scrollable if overflowing) -->
                        <div class="flex-grow overflow-y-auto space-y-3 pr-1" style="max-height: 420px;">
                            <?php if (empty($step_files)): ?>
                                <div class="py-10 text-center text-slate-500 flex flex-col items-center justify-center h-full">
                                    <svg class="w-8 h-8 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Kosong</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($step_files as $file): ?>
                                    <?= renderDocumentCard($file, $pdo) ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    </div>
    </div>
</body>
</html>

<?php
/**
 * Helper function to render a document card with clean AMDK styling and high-contrast text.
 */
function renderDocumentCard($file, $pdo) {
    ob_start();
    ?>
    <a href="view.php?id=<?= $file['id'] ?>" class="doc-card">
        <!-- Document Title -->
        <p class="font-bold text-slate-800 text-sm leading-snug hover:text-sky-600 transition-colors">
            <?= htmlspecialchars($file['nama_dokumen']) ?>
        </p>
        
        <!-- Metadata row -->
        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
            <span class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                <?= htmlspecialchars($file['no_dokumen']) ?>
            </span>
            <span class="text-xs font-black text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded uppercase border border-sky-100">
                <?= str_replace('_', ' ', $file['produk']) ?>
            </span>
            <?php if (!empty($file['file_path'])): ?>
                <span class="text-xs font-bold bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    File
                </span>
            <?php endif; ?>
            <?php if (!empty($file['external_link'])): ?>
                <span class="text-xs font-bold bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                    Link
                </span>
            <?php endif; ?>
        </div>

        <!-- Status and details -->
        <div class="flex items-center justify-between gap-2 mt-2 pt-2 border-t border-slate-100">
            <!-- Left: status badge -->
            <div>
                <?php 
                $badge_shown = false;
                if ($file['jenis'] == 'Uji_Lab' || $file['jenis'] == 'Uji_Ulang') {
                    $is_passed = ($file['status_mutu'] == 'Passed' || $file['status_mutu'] == 'Lolos');
                    $bg_class = $is_passed ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-rose-100 text-rose-800 border-rose-200';
                    $label = $is_passed ? 'Mutu Passed' : 'Mutu Reject';
                    echo "<span class='px-2 py-0.5 border rounded-full text-xs font-bold uppercase tracking-wider $bg_class'>$label</span>";
                    $badge_shown = true;
                }

                if ($file['jenis'] == 'Diagnosis_Mesin' || $file['jenis'] == 'Approval_Manager') {
                    $status_app = $file['approval_status'] ?? 'Waiting Approval';
                    if ($status_app == 'Approved') {
                        $bg_class = 'bg-emerald-600 text-white border-emerald-700';
                    } elseif ($status_app == 'Rejected') {
                        $bg_class = 'bg-rose-600 text-white border-rose-700';
                    } elseif ($status_app == 'Hold') {
                        $bg_class = 'bg-amber-500 text-white border-amber-600';
                    } else {
                        $bg_class = 'bg-amber-100 text-amber-900 border-amber-200';
                    }
                    echo "<span class='px-2 py-0.5 border rounded-full text-xs font-bold uppercase tracking-wider $bg_class'>$status_app</span>";
                    $badge_shown = true;
                }
                
                if (!$badge_shown) {
                    echo "<span class='px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded-full text-xs font-bold uppercase tracking-wider'>Logged</span>";
                }
                ?>
            </div>

            <!-- Right: date -->
            <span class="text-xs font-bold text-slate-500">
                <?= htmlspecialchars($file['tanggal']) ?>
            </span>
        </div>

        <!-- Downtime or Lead Time if approved -->
        <?php if ($file['jenis'] == 'Approval_Manager' && !empty($file['approved_at'])): ?>
            <div class="mt-1.5 p-1.5 bg-emerald-50/50 rounded-lg text-xs font-bold text-emerald-800 border border-emerald-100 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Downtime: <?= getRepairDowntime($file, $pdo) ?></span>
            </div>
        <?php elseif (!empty($file['approved_at'])): ?>
            <div class="mt-1.5 p-1.5 bg-emerald-50/50 rounded-lg text-xs font-bold text-emerald-800 border border-emerald-100 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Lead Time: <?= formatLeadTime($file['created_at'], $file['approved_at']) ?></span>
            </div>
        <?php endif; ?>
    </a>
    <?php
    return ob_get_clean();
}
?>