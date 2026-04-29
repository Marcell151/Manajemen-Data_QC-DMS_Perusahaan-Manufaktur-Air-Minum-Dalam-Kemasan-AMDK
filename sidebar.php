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
?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<style>
    @media print {
        .no-print { display: none !important; }
        aside, nav, header, .sidebar-container { display: none !important; }
        main { padding: 0 !important; margin: 0 !important; width: 100% !important; }
        .flex { display: block !important; }
    }
</style>

<div class="flex h-screen bg-slate-50 font-sans overflow-hidden">
    <!-- Overlay for mobile -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-40 hidden md:hidden no-print"></div>

    <!-- Sidebar: Clean Mineral White Style -->
    <div id="mobileSidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col flex-shrink-0 shadow-sm no-print sidebar-container absolute md:relative z-50 h-full -translate-x-full md:translate-x-0 transition-transform duration-300">
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
                    
                    // Logika hak akses input (+)
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

        <!-- Role Switcher (Enhanced) -->
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
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-8 flex-shrink-0 z-10 no-print">
            <div class="flex items-center gap-2 md:gap-4">
                <button onclick="toggleSidebar()" class="md:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-lg text-xl">
                    ☰
                </button>
                <h2 class="text-lg md:text-xl font-bold text-slate-800 truncate">
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
        <main class="flex-grow overflow-y-auto bg-slate-50 p-4 md:p-8 relative">
            <script>
                function toggleSidebar() {
                    const sidebar = document.getElementById('mobileSidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    sidebar.classList.toggle('-translate-x-full');
                    overlay.classList.toggle('hidden');
                }
            </script>