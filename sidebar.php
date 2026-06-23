<?php
// Tentukan menu aktif berdasarkan nama file
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'Admin_Entry'; // Default Role
}

$role_name_map = [
    'Admin_Entry' => 'Admin Data / QC Lab',
    'Manager' => 'Manajer Produksi',
    'Pekerja_Lapangan' => 'Pekerja Lapangan / Teknisi'
];
$role_name = $role_name_map[$_SESSION['role']] ?? 'User';
$is_technician = ($_SESSION['role'] == 'Pekerja_Lapangan');

// --- SMART COUNTER BADGES LOGIC ---
$pending_counts = [
    'step1' => 0, 'step2' => 0, 'step3' => 0, 'step4' => 0, 'step5' => 0, 'step6' => 0, 'waiting' => 0
];

if (isset($pdo)) {
    if ($_SESSION['role'] == 'Manager') {
        $pending_counts['waiting'] = $pdo->query("SELECT COUNT(*) FROM documents WHERE approval_status = 'Waiting Approval'")->fetchColumn();
        $pending_counts['step6'] = $pdo->query("SELECT COUNT(*) FROM documents d1 WHERE d1.jenis IN ('Uji_Lab', 'Uji_Ulang') AND d1.status_mutu IN ('Passed', 'Lolos') AND d1.status != 'Archived' AND NOT EXISTS (SELECT 1 FROM documents d2 WHERE d2.parent_doc_id = d1.id AND d2.jenis = 'Approval_Manager')")->fetchColumn();
    } elseif ($_SESSION['role'] == 'Admin_Entry') {
        $pending_counts['step2'] = $pdo->query("SELECT COUNT(*) FROM documents d1 WHERE d1.jenis = 'Catatan_Batch' AND d1.status != 'Archived' AND NOT EXISTS (SELECT 1 FROM documents d2 WHERE d2.parent_doc_id = d1.id AND d2.jenis = 'Uji_Lab')")->fetchColumn();
        $pending_counts['step5'] = $pdo->query("SELECT COUNT(*) FROM documents d1 WHERE d1.jenis = 'Laporan_Perbaikan' AND d1.status != 'Archived' AND NOT EXISTS (SELECT 1 FROM documents d2 WHERE d2.parent_doc_id = d1.id AND d2.jenis = 'Uji_Ulang')")->fetchColumn();
    } elseif ($_SESSION['role'] == 'Pekerja_Lapangan') {
        $pending_counts['step3'] = $pdo->query("SELECT COUNT(*) FROM documents d1 WHERE d1.jenis = 'Uji_Lab' AND d1.status_mutu = 'Reject' AND d1.status != 'Archived' AND NOT EXISTS (SELECT 1 FROM documents d2 WHERE d2.parent_doc_id = d1.id AND d2.jenis = 'Diagnosis_Mesin')")->fetchColumn();
        $pending_counts['step4'] = $pdo->query("SELECT COUNT(*) FROM documents d1 WHERE d1.jenis = 'Diagnosis_Mesin' AND d1.approval_status = 'Approved' AND d1.status != 'Archived' AND NOT EXISTS (SELECT 1 FROM documents d2 WHERE d2.parent_doc_id = d1.id AND d2.jenis = 'Laporan_Perbaikan')")->fetchColumn();
    }
}
// ----------------------------------

// Inisialisasi variabel mobile filter untuk mencegah warning undefined
$mob_filter = $_GET['filter'] ?? '';

$steps_config = [
    'step1' => [
        'num' => '01',
        'title' => 'Sampling (Batch)',
        'step_val' => 1,
        'color' => 'indigo',
        'svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>'
    ],
    'step2' => [
        'num' => '02',
        'title' => 'Uji Laboratorium',
        'step_val' => 2,
        'color' => 'amber',
        'svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>'
    ],
    'step3' => [
        'num' => '03',
        'title' => 'Diagnosis Masalah',
        'step_val' => 3,
        'color' => 'rose',
        'svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>'
    ],
    'step4' => [
        'num' => '04',
        'title' => 'Perbaikan Teknik',
        'step_val' => 4,
        'color' => 'orange',
        'svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>'
    ],
    'step5' => [
        'num' => '05',
        'title' => 'Uji Verifikasi',
        'step_val' => 5,
        'color' => 'teal',
        'svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>'
    ],
    'step6' => [
        'num' => '06',
        'title' => 'Approval Final',
        'step_val' => 6,
        'color' => 'emerald',
        'svg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>'
    ]
];
?>

<!-- Google Fonts: Nunito -->
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body, h1, h2, h3, h4, h5, h6, p, a, span, div, button, input, select, textarea {
        font-family: 'Nunito', sans-serif !important;
    }

    @media print {
        .no-print { display: none !important; }
        aside, nav, header, .sidebar-container, .mobile-topbar, .mobile-bottom-nav { display: none !important; }
        main { padding: 0 !important; margin: 0 !important; width: 100% !important; }
        .flex { display: block !important; }
    }

    /* ---- MOBILE TOPBAR ---- */
    .mobile-topbar {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 60px;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        align-items: center;
        justify-content: space-between;
        padding: 0 1rem;
        z-index: 100;
        box-shadow: 0 1px 8px rgba(0,0,0,0.06);
    }
    .mobile-topbar .logo-mark {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }
    .mobile-topbar .logo-mark .mp-badge {
        width: 36px; height: 36px;
        background: #0284c7;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 900;
        font-size: 13px;
    }
    .mobile-topbar .logo-mark span {
        font-weight: 800;
        color: #1e293b;
        font-size: 15px;
    }
    .mobile-topbar .role-badge {
        font-size: 12px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        background: #f0f9ff;
        color: #0284c7;
        border: 1px solid #bae6fd;
    }
    .mobile-topbar .role-badge.technician { background: #f0dfa2; color: #854d0e; border-color: #fef08a; }
    .mobile-topbar .role-badge.manager { background: #fff1f2; color: #e11d48; border-color: #fecdd3; }

    /* ---- MOBILE CONTENT PADDING ---- */
    @media (max-width: 767px) {
        .mobile-topbar { display: flex; }
        /* Push content below fixed topbar */
        .mobile-content-area {
            padding-top: 76px !important;
            padding-bottom: 2rem !important;
        }
        /* Hide desktop sidebar on mobile */
        .sidebar-container { display: none !important; }
        /* Hide desktop topbar on mobile */
        .desktop-topbar { display: none !important; }
        /* Full width main on mobile */
        .main-layout { display: block !important; }
    }
    @media (min-width: 768px) {
        /* Hide mobile-only elements on desktop */
        .mobile-topbar { display: none !important; }
    }
</style>

<!-- MOBILE TOPBAR (Visible only on mobile) -->
<div class="mobile-topbar no-print">
    <div class="flex items-center gap-2">
        <button onclick="toggleMobileSidebar()" class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 font-bold hover:bg-slate-200 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <div class="logo-mark">
            <div class="mp-badge">MP</div>
            <span>QC-DMS</span>
        </div>
    </div>
    <div>
        <?php if ($is_technician): ?>
            <span class="role-badge technician">Teknisi</span>
        <?php elseif ($_SESSION['role'] == 'Manager'): ?>
            <span class="role-badge manager">Manajer</span>
        <?php else: ?>
            <span class="role-badge">Admin QC</span>
        <?php endif; ?>
    </div>
</div>

<!-- MOBILE SIDEBAR DRAWER (Full menu, opened via hamburger) -->
<div id="mobileSidebarOverlay" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/60 z-[150] hidden no-print transition-opacity duration-300"></div>
<div id="mobileSidebarDrawer" class="fixed top-0 left-0 bottom-0 w-72 bg-white z-[200] flex flex-col shadow-2xl transform -translate-x-full transition-transform duration-300 overflow-y-auto no-print md:hidden">
    <!-- Drawer Header -->
    <div class="p-5 bg-sky-700 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white font-black text-sm">MP</div>
            <div>
                <p class="text-white font-black text-base leading-none">QC-DMS</p>
                <p class="text-sky-200 text-xs uppercase tracking-widest font-bold mt-0.5">Mineral Pure</p>
            </div>
        </div>
        <button onclick="toggleMobileSidebar()" class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-xl text-white flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Role Info -->
    <div class="mx-4 mt-4 p-4 rounded-2xl <?= $is_technician ? 'bg-amber-50 border border-amber-200' : ($_SESSION['role']=='Manager' ? 'bg-rose-50 border border-rose-200' : 'bg-sky-50 border border-sky-200') ?>">
        <p class="text-xs font-black uppercase tracking-widest <?= $is_technician ? 'text-amber-800' : ($_SESSION['role']=='Manager' ? 'text-rose-700' : 'text-sky-700') ?> mb-1">Login Sebagai</p>
        <p class="font-black text-slate-800 text-sm"><?= $role_name ?></p>
    </div>

    <!-- Navigation -->
    <nav class="flex-grow py-4 px-3">
        <a href="index.php" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl mb-1 <?= ($current_page == 'index.php' && !isset($_GET['filter'])) ? 'bg-sky-600 text-white' : 'text-slate-700 hover:bg-slate-50' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            <span class="font-bold text-sm">Dashboard Utama</span>
        </a>

        <p class="text-xs font-black text-slate-500 uppercase tracking-widest px-4 mt-8 mb-4">Alur Kerja Mutu</p>
        
        <?php foreach ($steps_config as $mk => $mv):
            $mob_is_active = ($mob_filter == $mk);
            $mob_step_num = $mv['step_val'];
            $mob_can_add = false;
            if ($_SESSION['role'] == 'Pekerja_Lapangan' && in_array($mob_step_num, [1, 3, 4, 5])) $mob_can_add = true;
            if ($_SESSION['role'] == 'Admin_Entry' && $mob_step_num == 2) $mob_can_add = true;
            if ($_SESSION['role'] == 'Manager' && $mob_step_num == 6) $mob_can_add = true;
        ?>
        <div class="flex items-center gap-1 mb-2">
            <a href="index.php?filter=<?= $mk ?>" onclick="toggleMobileSidebar()" class="flex-grow flex items-center gap-3 px-4 py-3 rounded-2xl <?= $mob_is_active ? 'bg-sky-600 text-white' : 'text-slate-700 hover:bg-slate-50' ?>">
                <span class="text-xs font-black px-2 py-0.5 rounded <?= $mob_is_active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>"><?= $mv['num'] ?></span>
                <svg class="w-5 h-5 flex-shrink-0 <?= $mob_is_active ? 'text-white' : 'text-sky-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <?= $mv['svg'] ?>
                </svg>
                <span class="font-bold text-sm"><?= $mv['title'] ?></span>
                <?php if ($pending_counts[$mk] > 0): ?>
                    <span class="bg-rose-500 text-white rounded-full px-2 py-0.5 text-[10px] font-black ml-auto shadow-sm shadow-rose-500/30"><?= $pending_counts[$mk] ?></span>
                <?php endif; ?>
            </a>
            <?php if ($mob_can_add): ?>
            <a href="add.php?step=<?= $mob_step_num ?>" onclick="toggleMobileSidebar()" class="w-10 h-10 flex items-center justify-center bg-sky-50 text-sky-600 hover:bg-sky-600 hover:text-white rounded-xl font-black text-lg transition-all flex-shrink-0 border border-sky-100">+</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if ($_SESSION['role'] == 'Manager'): ?>
        <p class="text-xs font-black text-rose-600 uppercase tracking-widest px-4 mt-8 mb-4">Otorisasi</p>
        <a href="index.php?filter=waiting" onclick="toggleMobileSidebar()" class="flex items-center gap-3 px-4 py-3.5 rounded-2xl mb-2 <?= ($mob_filter == 'waiting') ? 'bg-rose-600 text-white' : 'text-slate-700 hover:bg-rose-50' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            <span class="font-bold text-sm">Butuh Approval</span>
            <?php if ($pending_counts['waiting'] > 0): ?>
                <span class="bg-rose-500 text-white rounded-full px-2 py-0.5 text-[10px] font-black ml-auto shadow-sm shadow-rose-500/30"><?= $pending_counts['waiting'] ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <p class="text-xs font-black text-slate-500 uppercase tracking-widest px-4 mt-8 mb-4">Administrasi</p>
        <?php if ($is_technician): ?>
        <a href="add.php?step=1" onclick="toggleMobileSidebar()" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl mb-2 <?= ($current_page == 'add.php') ? 'bg-slate-950 text-white shadow-lg' : 'text-slate-700 hover:bg-slate-50' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-bold text-sm">Buat Sampling Baru (Tahap 01)</span>
        </a>
        <?php elseif ($_SESSION['role'] == 'Admin_Entry'): ?>
        <a href="add.php?step=2" onclick="toggleMobileSidebar()" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl mb-2 <?= ($current_page == 'add.php') ? 'bg-slate-950 text-white shadow-lg' : 'text-slate-700 hover:bg-slate-50' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-bold text-sm">Buat Uji Lab (Tahap 02)</span>
        </a>
        <?php elseif ($_SESSION['role'] == 'Manager'): ?>
        <a href="add.php?step=6" onclick="toggleMobileSidebar()" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl mb-2 <?= ($current_page == 'add.php') ? 'bg-slate-950 text-white shadow-lg' : 'text-slate-700 hover:bg-slate-50' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-bold text-sm">Buat Approval</span>
        </a>
        <?php endif; ?>
        <a href="archive.php" onclick="toggleMobileSidebar()" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl mb-2 <?= ($current_page == 'archive.php') ? 'bg-sky-600 text-white' : 'text-slate-700 hover:bg-slate-50' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
            </svg>
            <span class="font-bold text-sm">Riwayat Arsip</span>
        </a>
        <a href="documentation.php" onclick="toggleMobileSidebar()" class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl mb-2 <?= ($current_page == 'documentation.php') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-700 hover:bg-indigo-50 hover:text-indigo-700' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            <span class="font-bold text-sm">Dokumen Teknikal</span>
        </a>
    </nav>

    <!-- Logout Button -->
    <div class="p-4 border-t border-slate-100 mt-auto">
        <a href="logout.php" onclick="return confirm('Keluar dari aplikasi?')" class="flex items-center justify-center gap-2 w-full py-3 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-xl font-bold transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Logout
        </a>
    </div>
</div>

<div class="flex h-screen bg-slate-50 font-sans overflow-hidden main-layout">
    <!-- Overlay for desktop sidebar -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-40 hidden md:hidden no-print"></div>

    <!-- Desktop Sidebar: Clean Mineral White Style -->
    <div id="mobileSidebar" class="w-64 bg-white border-r border-slate-200 flex-col flex-shrink-0 shadow-sm no-print sidebar-container hidden md:flex relative z-50 h-full transition-transform duration-300">
        <div class="p-6 bg-white flex items-center gap-3.5 border-b border-slate-100">
            <div class="w-10 h-10 bg-sky-600 rounded-xl flex items-center justify-center text-lg shadow-lg shadow-sky-600/20 text-white font-black">
                MP
            </div>
            <div>
                <h1 class="text-lg font-black tracking-tight text-slate-800 leading-none">QC-DMS</h1>
                <p class="text-xs text-sky-500 uppercase tracking-[0.2em] font-black mt-1">Mineral Pure</p>
            </div>
        </div>
        
        <nav class="flex-grow py-6 px-4 overflow-y-auto">
            <ul class="space-y-2">
                <li>
                    <a href="index.php" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all duration-200 <?= ($current_page == 'index.php' && !isset($_GET['filter'])) ? 'bg-sky-600 text-white shadow-md shadow-sky-600/10' : 'text-slate-600 hover:bg-slate-50 hover:text-sky-600' ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        <span class="font-bold text-xs uppercase tracking-wide">Ringkasan Utama</span>
                    </a>
                </li>

                <li class="pt-6 pb-2 px-4 text-xs font-black text-slate-500 uppercase tracking-[0.2em]">ALUR KERJA MUTU</li>
                
                <?php foreach ($steps_config as $key => $val):
                    $is_active = (isset($_GET['filter']) && $_GET['filter'] == $key);
                    $step_num = $val['step_val'];
                    
                    $can_add = false;
                    if ($_SESSION['role'] == 'Pekerja_Lapangan' && in_array($step_num, [1, 3, 4, 5])) $can_add = true;
                    if ($_SESSION['role'] == 'Admin_Entry' && $step_num == 2) $can_add = true;
                    if ($_SESSION['role'] == 'Manager' && $step_num == 6) $can_add = true;
                ?>
                <li>
                    <div class="flex items-center gap-1 group">
                        <a href="index.php?filter=<?= $key ?>" 
                           class="flex-grow flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-all duration-200 <?= $is_active ? 'bg-sky-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50 hover:text-sky-600' ?>">
                            <span class="text-[10px] font-black px-1.5 py-0.5 rounded <?= $is_active ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>"><?= $val['num'] ?></span>
                            <span class="font-bold text-xs uppercase tracking-tight"><?= $val['title'] ?></span>
                            <?php if ($pending_counts[$key] > 0): ?>
                                <span class="bg-rose-500 text-white rounded-full px-2 py-0.5 text-[10px] font-black ml-auto shadow-sm shadow-rose-500/30"><?= $pending_counts[$key] ?></span>
                            <?php endif; ?>
                        </a>
                        <?php if ($can_add): ?>
                        <a href="add.php?step=<?= $step_num ?>" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-sky-600 font-bold transition-all text-lg rounded-lg hover:bg-slate-100" title="Input Baru">+</a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>

                <?php if ($_SESSION['role'] == 'Manager'): ?>
                <li class="pt-6 pb-2 px-4 text-xs font-black text-rose-600 uppercase tracking-[0.2em]">OTORISASI</li>
                <li>
                    <a href="index.php?filter=waiting" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all duration-200 <?= (isset($_GET['filter']) && $_GET['filter'] == 'waiting') ? 'bg-rose-600 text-white shadow-md shadow-rose-600/10' : 'text-slate-600 hover:bg-rose-50 hover:text-rose-600' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span class="font-bold text-xs uppercase tracking-wide">Butuh Approval</span>
                        <?php if ($pending_counts['waiting'] > 0): ?>
                            <span class="bg-rose-500 text-white rounded-full px-2 py-0.5 text-[10px] font-black ml-auto shadow-sm shadow-rose-500/30"><?= $pending_counts['waiting'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>

                <li class="pt-6 pb-2 px-4 text-xs font-black text-slate-500 uppercase tracking-[0.2em]">ADMINISTRASI</li>
                <?php if ($is_technician): ?>
                <li>
                    <a href="add.php?step=1" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all duration-200 <?= $current_page == 'add.php' ? 'bg-slate-950 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-bold text-xs uppercase tracking-wide">Buat Sampling Baru</span>
                    </a>
                </li>
                <?php elseif ($_SESSION['role'] == 'Admin_Entry'): ?>
                <li>
                    <a href="add.php?step=2" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all duration-200 <?= $current_page == 'add.php' ? 'bg-slate-950 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-bold text-xs uppercase tracking-wide">Buat Uji Lab</span>
                    </a>
                </li>
                <?php elseif ($_SESSION['role'] == 'Manager'): ?>
                <li>
                    <a href="add.php?step=6" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all duration-200 <?= $current_page == 'add.php' ? 'bg-slate-950 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-bold text-xs uppercase tracking-wide">Buat Approval</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="archive.php" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all duration-200 <?= $current_page == 'archive.php' ? 'bg-sky-600 text-white shadow-md shadow-sky-600/10' : 'text-slate-600 hover:bg-slate-50 hover:text-sky-600' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <span class="font-bold text-xs uppercase tracking-wide">Riwayat Arsip</span>
                    </a>
                </li>
                <li>
                    <a href="documentation.php" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-2xl transition-all duration-200 <?= $current_page == 'documentation.php' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-700' ?>">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <span class="font-bold text-xs uppercase tracking-wide">Dokumen Teknikal</span>
                    </a>
                </li>
            </ul>

            <!-- Logout Button (Desktop) -->
            <div class="mt-8 mb-4">
                <a href="logout.php" onclick="return confirm('Keluar dari aplikasi?')" class="flex items-center justify-center gap-2 w-full py-3 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-xl font-bold transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content Wrapper -->
    <div class="flex-grow flex flex-col h-screen overflow-hidden w-full">
        <!-- Desktop Topbar -->
        <header class="h-16 bg-white border-b border-slate-200 items-center justify-between px-4 md:px-8 flex-shrink-0 z-10 no-print desktop-topbar hidden md:flex">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-black text-slate-800 truncate">
                    <?php 
                        if($current_page == 'index.php') echo "File & Report Manager";
                        elseif($current_page == 'add.php') echo "Upload Quality Control";
                        elseif($current_page == 'documentation.php') echo "Technical Documentation";
                        else echo "Quality Analysis Detail";
                    ?>
                </h2>
                <?php if ($_SESSION['role'] == 'Manager'): ?>
                    <span class="bg-orange-50 text-orange-800 text-xs font-black px-2 py-0.5 rounded-full border border-orange-100">MANAGER ACCESS</span>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-right border-r border-slate-200 pr-4">
                    <p class="text-sm font-black text-slate-900 leading-none"><?= $role_name ?></p>
                    <p class="text-xs text-slate-500 font-bold mt-1 uppercase tracking-wider">QC Department &bull; Manufacturing</p>
                </div>
                <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 font-black border border-slate-200 text-sm">
                    <?= substr($role_name, 0, 1) ?>
                </div>
            </div>
        </header>

        <!-- Scrollable Content Area -->
        <main class="flex-grow overflow-y-auto bg-slate-50 p-4 md:p-8 relative mobile-content-area">
            <script>
                function toggleSidebar() {
                    const sidebar = document.getElementById('mobileSidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    sidebar.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                }
                function toggleMobileSidebar() {
                    const drawer = document.getElementById('mobileSidebarDrawer');
                    const overlay = document.getElementById('mobileSidebarOverlay');
                    drawer.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                }
            </script>