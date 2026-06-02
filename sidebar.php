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
?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;700;800&display=swap');

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
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        background: #f0f9ff;
        color: #0284c7;
        border: 1px solid #bae6fd;
    }
    .mobile-topbar .role-badge.technician { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .mobile-topbar .role-badge.manager { background: #fff1f2; color: #e11d48; border-color: #fecdd3; }

    /* ---- MOBILE BOTTOM NAV (Technician Only) ---- */
    .mobile-bottom-nav {
        display: none;
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: #fff;
        border-top: 1.5px solid #e2e8f0;
        padding: 6px 0 env(safe-area-inset-bottom, 6px);
        z-index: 100;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.06);
    }
    .mobile-bottom-nav .nav-items {
        display: flex;
        justify-content: space-around;
        align-items: center;
    }
    .mobile-bottom-nav .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
        padding: 6px 8px;
        border-radius: 12px;
        text-decoration: none;
        color: #94a3b8;
        transition: all 0.15s;
        min-width: 52px;
    }
    .mobile-bottom-nav .nav-item.active {
        color: #0284c7;
        background: #f0f9ff;
    }
    .mobile-bottom-nav .nav-item .nav-icon {
        font-size: 22px;
        line-height: 1;
    }
    .mobile-bottom-nav .nav-item .nav-label {
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        text-align: center;
        line-height: 1.1;
    }
    .mobile-bottom-nav .nav-item.add-btn {
        color: #fff;
        background: #0284c7;
        box-shadow: 0 4px 12px rgba(2,132,199,0.35);
        padding: 8px 12px;
        transform: translateY(-6px);
        border-radius: 16px;
    }
    .mobile-bottom-nav .nav-item.add-btn .nav-label { color: #fff; }

    /* ---- MOBILE CONTENT PADDING ---- */
    @media (max-width: 767px) {
        .mobile-topbar { display: flex; }
        .mobile-bottom-nav { display: block; }
        /* Push content below fixed topbar and above bottom nav */
        .mobile-content-area {
            padding-top: 68px !important;
            padding-bottom: 90px !important;
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
        .mobile-bottom-nav { display: none !important; }
    }
</style>

<!-- MOBILE TOPBAR (Visible only on mobile) -->
<div class="mobile-topbar no-print">
    <div class="logo-mark">
        <div class="mp-badge">MP</div>
        <span>QC-DMS</span>
    </div>
    <div class="flex items-center gap-2">
        <?php if ($is_technician): ?>
            <span class="role-badge technician">👷 Teknisi</span>
        <?php elseif ($_SESSION['role'] == 'Manager'): ?>
            <span class="role-badge manager">👑 Manajer</span>
        <?php else: ?>
            <span class="role-badge">👤 Admin QC</span>
        <?php endif; ?>
        <button onclick="toggleMobileSidebar()" class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 font-bold text-lg">
            ☰
        </button>
    </div>
</div>

<!-- MOBILE BOTTOM NAVIGATION (Technician role) -->
<?php
$mob_filter = $_GET['filter'] ?? '';
$mob_page   = $current_page;
?>
<nav class="mobile-bottom-nav no-print">
    <div class="nav-items">
        <!-- Dashboard -->
        <a href="index.php" class="nav-item <?= ($mob_page == 'index.php' && !$mob_filter) ? 'active' : '' ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Dashboard</span>
        </a>
        <?php if ($is_technician): ?>
        <!-- Sampling (Step 1) -->
        <a href="index.php?filter=step1" class="nav-item <?= ($mob_filter == 'step1') ? 'active' : '' ?>">
            <span class="nav-icon">📄</span>
            <span class="nav-label">Sampling</span>
        </a>
        <!-- Tambah Laporan Baru (Center CTA) -->
        <a href="add.php" class="nav-item add-btn <?= ($mob_page == 'add.php') ? 'active' : '' ?>">
            <span class="nav-icon">＋</span>
            <span class="nav-label">Laporan</span>
        </a>
        <!-- Perbaikan (Step 4) -->
        <a href="index.php?filter=step4" class="nav-item <?= ($mob_filter == 'step4') ? 'active' : '' ?>">
            <span class="nav-icon">🔧</span>
            <span class="nav-label">Perbaikan</span>
        </a>
        <?php else: ?>
        <!-- Laporan Baru (Center) -->
        <a href="add.php" class="nav-item add-btn <?= ($mob_page == 'add.php') ? 'active' : '' ?>">
            <span class="nav-icon">＋</span>
            <span class="nav-label">Baru</span>
        </a>
        <?php endif; ?>
        <!-- Semua Laporan -->
        <a href="index.php?filter=all" class="nav-item <?= ($mob_filter == 'all') ? 'active' : '' ?>">
            <span class="nav-icon">📂</span>
            <span class="nav-label">Arsip</span>
        </a>
    </div>
</nav>

<!-- MOBILE SIDEBAR DRAWER (Full menu, opened via hamburger) -->
<div id="mobileSidebarOverlay" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/50 z-[150] hidden no-print"></div>
<div id="mobileSidebarDrawer" class="fixed top-0 left-0 bottom-0 w-72 bg-white z-[200] flex flex-col shadow-2xl transform -translate-x-full transition-transform duration-300 overflow-y-auto no-print md:hidden">
    <!-- Drawer Header -->
    <div class="p-5 bg-sky-600 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white font-black text-sm">MP</div>
            <div>
                <p class="text-white font-black text-base leading-none">QC-DMS</p>
                <p class="text-sky-200 text-[10px] uppercase tracking-widest font-bold mt-0.5">Mineral Pure</p>
            </div>
        </div>
        <button onclick="toggleMobileSidebar()" class="w-9 h-9 bg-white/20 rounded-xl text-white flex items-center justify-center font-black text-lg">✕</button>
    </div>

    <!-- Role Info -->
    <div class="mx-4 mt-4 p-4 rounded-2xl <?= $is_technician ? 'bg-emerald-50 border border-emerald-200' : ($_SESSION['role']=='Manager' ? 'bg-rose-50 border border-rose-200' : 'bg-sky-50 border border-sky-200') ?>">
        <p class="text-[10px] font-black uppercase tracking-widest <?= $is_technician ? 'text-emerald-600' : ($_SESSION['role']=='Manager' ? 'text-rose-600' : 'text-sky-600') ?> mb-1">Login Sebagai</p>
        <p class="font-black text-slate-800 text-sm"><?= $role_name ?></p>
    </div>

    <!-- Navigation -->
    <nav class="flex-grow py-4 px-3">
        <a href="index.php" class="flex items-center gap-4 px-4 py-4 rounded-2xl mb-1 <?= ($current_page == 'index.php' && !isset($_GET['filter'])) ? 'bg-sky-600 text-white' : 'text-slate-600 hover:bg-sky-50' ?>">
            <span class="text-xl">📊</span>
            <span class="font-bold text-sm">Dashboard Utama</span>
        </a>

        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest px-4 mt-6 mb-3">Alur Kerja Mutu</p>
        <?php
        $mob_steps = [
            'step1' => ['01', 'Sampling (Batch)', '📄', [1,3,4,5]],
            'step2' => ['02', 'Uji Laboratorium', '🧪', [2]],
            'step3' => ['03', 'Diagnosis Masalah', '⚙️', [1,3,4,5]],
            'step4' => ['04', 'Perbaikan Teknik', '🔧', [1,3,4,5]],
            'step5' => ['05', 'Uji Verifikasi', '🔬', [1,3,4,5]],
            'step6' => ['06', 'Approval Final', '⚖️', [6]],
        ];
        foreach ($mob_steps as $mk => $mv):
            $mob_is_active = ($mob_filter == $mk);
            $mob_step_num = (int)substr($mk, 4);
            $mob_can_add = false;
            if ($_SESSION['role'] == 'Pekerja_Lapangan' && in_array($mob_step_num, [1, 3, 4, 5])) $mob_can_add = true;
            if ($_SESSION['role'] == 'Admin_Entry' && $mob_step_num == 2) $mob_can_add = true;
        ?>
        <div class="flex items-center gap-1 mb-1">
            <a href="index.php?filter=<?= $mk ?>" onclick="toggleMobileSidebar()" class="flex-grow flex items-center gap-4 px-4 py-3.5 rounded-2xl <?= $mob_is_active ? 'bg-sky-600 text-white' : 'text-slate-600 hover:bg-sky-50' ?>">
                <span class="text-base font-black <?= $mob_is_active ? 'text-sky-200' : 'text-slate-300' ?>"><?= $mv[0] ?></span>
                <span class="text-xl"><?= $mv[2] ?></span>
                <span class="font-bold text-sm"><?= $mv[1] ?></span>
            </a>
            <?php if ($mob_can_add): ?>
            <a href="add.php?step=<?= $mob_step_num ?>" onclick="toggleMobileSidebar()" class="w-10 h-10 flex items-center justify-center bg-sky-100 text-sky-600 rounded-xl font-black text-xl hover:bg-sky-600 hover:text-white transition-all flex-shrink-0">+</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if ($_SESSION['role'] == 'Manager'): ?>
        <p class="text-[10px] font-black text-rose-400 uppercase tracking-widest px-4 mt-6 mb-3">Otorisasi</p>
        <a href="index.php?filter=waiting" onclick="toggleMobileSidebar()" class="flex items-center gap-4 px-4 py-4 rounded-2xl mb-1 <?= ($mob_filter == 'waiting') ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-rose-50' ?>">
            <span class="text-xl">⚖️</span>
            <span class="font-bold text-sm">Butuh Approval</span>
        </a>
        <?php endif; ?>

        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest px-4 mt-6 mb-3">Administrasi</p>
        <a href="add.php" onclick="toggleMobileSidebar()" class="flex items-center gap-4 px-4 py-4 rounded-2xl mb-1 <?= ($current_page == 'add.php') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-sky-600 hover:text-white' ?>">
            <span class="text-xl">➕</span>
            <span class="font-bold text-sm">Laporan Baru</span>
        </a>
    </nav>

    <!-- Role Switcher -->
    <div class="p-4 border-t border-slate-100">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 text-center">Simulasi Role</p>
        <div class="grid grid-cols-3 gap-2">
            <a href="?switch_role=Pekerja_Lapangan" class="py-3 rounded-xl text-[10px] font-black text-center <?= $_SESSION['role'] == 'Pekerja_Lapangan' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500' ?>">👷<br>Teknisi</a>
            <a href="?switch_role=Admin_Entry" class="py-3 rounded-xl text-[10px] font-black text-center <?= $_SESSION['role'] == 'Admin_Entry' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-500' ?>">👤<br>Admin</a>
            <a href="?switch_role=Manager" class="py-3 rounded-xl text-[10px] font-black text-center <?= $_SESSION['role'] == 'Manager' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-500' ?>">👑<br>Manajer</a>
        </div>
    </div>
</div>

<div class="flex h-screen bg-slate-50 font-sans overflow-hidden main-layout">
    <!-- Overlay for desktop sidebar -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-40 hidden md:hidden no-print"></div>

    <!-- Desktop Sidebar: Clean Mineral White Style -->
    <div id="mobileSidebar" class="w-64 bg-white border-r border-slate-200 flex-col flex-shrink-0 shadow-sm no-print sidebar-container hidden md:flex relative z-50 h-full transition-transform duration-300">
        <div class="p-8 bg-white flex items-center gap-4 border-b border-slate-100">
            <div class="w-12 h-12 bg-sky-600 rounded-2xl flex items-center justify-center text-2xl shadow-lg shadow-sky-600/20 text-white font-black">
                MP
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-800 leading-none">QC-DMS</h1>
                <p class="text-[10px] text-sky-500 uppercase tracking-[0.2em] font-black mt-1">Mineral Pure</p>
            </div>
        </div>
        
        <nav class="flex-grow py-8 px-4 overflow-y-auto">
            <ul class="space-y-2">
                <li>
                    <a href="index.php" 
                       class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-200 <?= ($current_page == 'index.php' && !isset($_GET['filter'])) ? 'bg-sky-600 text-white shadow-xl shadow-sky-600/20' : 'text-slate-500 hover:bg-sky-50 hover:text-sky-600' ?>">
                        <span class="text-lg">📊</span>
                        <span class="font-bold text-sm uppercase tracking-wide">Ringkasan Utama</span>
                    </a>
                </li>

                <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] px-5 mt-10 mb-4">ALUR KERJA MUTU</p>
                
                <?php
                $steps = [
                    'step1' => ['01', 'Sampling (Batch)', '📄'],
                    'step2' => ['02', 'Uji Laboratorium', '🧪'],
                    'step3' => ['03', 'Diagnosis Masalah', '⚙️'],
                    'step4' => ['04', 'Perbaikan Teknik', '🔧'],
                    'step5' => ['05', 'Uji Verifikasi', '🔬'],
                    'step6' => ['06', 'Approval Final', '⚖️'],
                ];
                foreach ($steps as $key => $val):
                    $is_active = (isset($_GET['filter']) && $_GET['filter'] == $key);
                    $step_num = (int)substr($key, 4);
                    
                    $can_add = false;
                    if ($_SESSION['role'] == 'Pekerja_Lapangan' && in_array($step_num, [1, 3, 4, 5])) $can_add = true;
                    if ($_SESSION['role'] == 'Admin_Entry' && $step_num == 2) $can_add = true;
                ?>
                <li>
                    <div class="flex items-center gap-1 group">
                        <a href="index.php?filter=<?= $key ?>" 
                           class="flex-grow flex items-center gap-5 px-5 py-4 rounded-2xl transition-all duration-200 <?= $is_active ? 'bg-sky-600 text-white shadow-lg' : 'text-slate-500 hover:bg-sky-50 hover:text-sky-600' ?>">
                            <span class="text-lg font-black <?= $is_active ? 'text-white' : 'text-sky-200' ?>"><?= $val[0] ?></span>
                            <span class="font-bold text-xs uppercase tracking-tight"><?= $val[1] ?></span>
                        </a>
                        <?php if ($can_add): ?>
                        <a href="add.php?step=<?= $step_num ?>" class="w-10 h-10 flex items-center justify-center text-slate-300 hover:text-sky-600 font-bold transition-all text-xl" title="Input Baru">+</a>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>

                <?php if ($_SESSION['role'] == 'Manager'): ?>
                <p class="text-[10px] font-black text-rose-400 uppercase tracking-[0.3em] px-5 mt-10 mb-4">OTORISASI</p>
                <li>
                    <a href="index.php?filter=waiting" 
                       class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-200 <?= (isset($_GET['filter']) && $_GET['filter'] == 'waiting') ? 'bg-rose-600 text-white shadow-xl shadow-rose-600/20' : 'text-slate-500 hover:bg-rose-50 hover:text-rose-600' ?>">
                        <span class="text-lg">⚖️</span>
                        <span class="font-bold text-sm uppercase tracking-wide">Butuh Approval</span>
                    </a>
                </li>
                <?php endif; ?>

                <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em] px-5 mt-10 mb-4">ADMINISTRASI</p>
                <li>
                    <a href="add.php" 
                       class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-200 <?= $current_page == 'add.php' ? 'bg-slate-900 text-white shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-sky-600 hover:text-white' ?>">
                        <span class="text-lg">➕</span>
                        <span class="font-bold text-sm uppercase tracking-wide">Laporan Baru</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Role Switcher (Desktop Enhanced) -->
        <div class="p-6 bg-slate-100 border-t border-slate-200 m-4 rounded-3xl">
            <p class="text-[11px] font-black text-slate-500 uppercase mb-4 text-center tracking-[0.2em]">Pindah Simulasi Role</p>
            <div class="flex flex-col gap-3">
                <a href="?switch_role=Pekerja_Lapangan" 
                   class="block py-4 rounded-2xl text-xs font-black text-center transition-all shadow-sm <?= $_SESSION['role'] == 'Pekerja_Lapangan' ? 'bg-emerald-600 text-white shadow-emerald-600/20' : 'bg-white text-slate-400 border border-slate-200 hover:text-emerald-600 hover:border-emerald-200' ?>">
                   👷 TEKNISI LAPANGAN
                </a>
                <a href="?switch_role=Admin_Entry" 
                   class="block py-4 rounded-2xl text-xs font-black text-center transition-all shadow-sm <?= $_SESSION['role'] == 'Admin_Entry' ? 'bg-sky-600 text-white shadow-sky-600/20' : 'bg-white text-slate-400 border border-slate-200 hover:text-sky-600 hover:border-sky-200' ?>">
                   👤 ADMIN QC / LAB
                </a>
                <a href="?switch_role=Manager" 
                   class="block py-4 rounded-2xl text-xs font-black text-center transition-all shadow-sm <?= $_SESSION['role'] == 'Manager' ? 'bg-rose-600 text-white shadow-rose-600/20' : 'bg-white text-slate-400 border border-slate-200 hover:text-rose-600 hover:border-rose-200' ?>">
                   👑 PRODUKSI MANAJER
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="flex-grow flex flex-col h-screen overflow-hidden w-full">
        <!-- Desktop Topbar -->
        <header class="h-16 bg-white border-b border-gray-200 items-center justify-between px-4 md:px-8 flex-shrink-0 z-10 no-print desktop-topbar hidden md:flex">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold text-slate-800 truncate">
                    <?php 
                        if($current_page == 'index.php') echo "File & Report Manager";
                        elseif($current_page == 'add.php') echo "Upload Quality Control";
                        else echo "Quality Analysis Detail";
                    ?>
                </h2>
                <?php if ($_SESSION['role'] == 'Manager'): ?>
                    <span class="bg-orange-100 text-orange-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-orange-200">MANAGER ACCESS</span>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-right border-r border-gray-200 pr-4">
                    <p class="text-sm font-bold text-slate-900"><?= $role_name ?></p>
                    <p class="text-[10px] text-gray-500">QC Department • Manufacturing Unit</p>
                </div>
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-600 font-bold">
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