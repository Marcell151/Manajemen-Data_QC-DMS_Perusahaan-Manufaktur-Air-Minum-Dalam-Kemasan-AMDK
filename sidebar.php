<?php
// Tentukan menu aktif berdasarkan nama file
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'Admin_Entry'; // Default Role
}

$role_name = ($_SESSION['role'] == 'Admin_Entry') ? 'Admin Data Entry QC' : 'Manajer Produksi';
?>

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<div class="flex h-screen bg-slate-50 font-sans">
    <!-- Sidebar: Clean Mineral White Style -->
    <div class="w-64 bg-white border-r border-slate-200 flex flex-col flex-shrink-0 shadow-sm">
        <div class="p-6 bg-white flex items-center gap-3 border-b border-slate-100">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-xl shadow-lg shadow-blue-600/10 text-white">
                💧
            </div>
            <div>
                <h1 class="text-lg font-black tracking-tight text-slate-800">QC-DMS</h1>
                <p class="text-[9px] text-blue-500 uppercase tracking-widest font-black">Mineral Pure</p>
            </div>
        </div>
        
        <nav class="flex-grow py-6 px-3">
            <ul class="space-y-1">
                <li>
                    <a href="index.php" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= ($current_page == 'index.php' && !isset($_GET['filter'])) ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600' ?>">
                        <span class="text-base">📊</span>
                        <span class="font-bold text-sm">Dashboard</span>
                    </a>
                </li>

                <p class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] px-4 mt-8 mb-2">Modul Operasional</p>
                
                <li>
                    <a href="index.php?filter=lab" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= (isset($_GET['filter']) && $_GET['filter'] == 'lab') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600' ?>">
                        <span class="text-base">🧪</span>
                        <span class="font-bold text-sm">Laboratorium</span>
                    </a>
                </li>
                
                <li>
                    <a href="index.php?filter=maintenance" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= (isset($_GET['filter']) && $_GET['filter'] == 'maintenance') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600' ?>">
                        <span class="text-base">🛠️</span>
                        <span class="font-bold text-sm">Maintenance</span>
                    </a>
                </li>

                <li>
                    <a href="index.php?filter=approval" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= (isset($_GET['filter']) && $_GET['filter'] == 'approval') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600' ?>">
                        <span class="text-base">⚖️</span>
                        <span class="font-bold text-sm">Approval</span>
                    </a>
                </li>

                <?php if ($_SESSION['role'] == 'Admin_Entry'): ?>
                <p class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] px-4 mt-8 mb-2">Input Data</p>
                <li>
                    <a href="add.php" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 <?= $current_page == 'add.php' ? 'bg-blue-700 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-blue-600 hover:text-white' ?>">
                        <span class="text-base">➕</span>
                        <span class="font-bold text-sm">Tambah Laporan</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Role Switcher -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 m-3 rounded-2xl">
            <p class="text-[9px] font-black text-slate-400 uppercase mb-3 text-center tracking-widest">Simulasi Role</p>
            <div class="flex flex-col gap-2">
                <a href="?switch_role=Admin_Entry" 
                   class="block py-2 rounded-lg text-[10px] font-bold text-center transition-all <?= $_SESSION['role'] == 'Admin_Entry' ? 'bg-white text-blue-600 shadow-sm border border-blue-100' : 'text-slate-400 hover:text-blue-600' ?>">
                   Entry Admin
                </a>
                <a href="?switch_role=Manager" 
                   class="block py-2 rounded-lg text-[10px] font-bold text-center transition-all <?= $_SESSION['role'] == 'Manager' ? 'bg-white text-blue-600 shadow-sm border border-blue-100' : 'text-slate-400 hover:text-blue-600' ?>">
                   Produksi Manajer
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="flex-grow flex flex-col h-screen overflow-hidden">
        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 flex-shrink-0 z-10">
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-bold text-slate-800">
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
        <main class="flex-grow overflow-y-auto bg-slate-50 p-8">