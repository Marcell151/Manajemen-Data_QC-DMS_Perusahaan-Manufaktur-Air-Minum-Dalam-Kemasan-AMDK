<?php
require 'db.php';

$path = isset($_GET['path']) ? $_GET['path'] : 'QC_AMDK';
$path = rtrim($path, '/');
$filter = $_GET['filter'] ?? null;

// Logika Filtering Berdasarkan Menu Sidebar
$whereClause = "WHERE 1=1";
$params = [];

if ($filter) {
    if ($filter == 'lab') {
        $whereClause .= " AND jenis IN ('Uji_Lab', 'Uji_Ulang', 'Catatan_Batch')";
    } elseif ($filter == 'maintenance') {
        $whereClause .= " AND jenis IN ('Diagnosis_Mesin', 'Laporan_Perbaikan')";
    } elseif ($filter == 'approval') {
        $whereClause .= " AND approval_status = 'Waiting Approval'";
    }
} else {
    // Jika tidak ada filter, tunjukkan file di folder saat ini
    $whereClause .= " AND folder_path = ?";
    $params[] = $path;
}

// Ambil subfolder (Hanya jika tidak sedang memfilter proses)
$subfolders = [];
if (!$filter) {
    $stmt = $pdo->prepare("SELECT DISTINCT folder_path FROM documents WHERE folder_path LIKE ? AND folder_path != ?");
    $stmt->execute([$path . "/%", $path]);
    $subfolders_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($subfolders_raw as $sf) {
        $remainder = substr($sf, strlen($path . '/'));
        $parts = explode('/', $remainder);
        if (!empty($parts[0])) {
            $subfolders[$parts[0]] = $path . '/' . $parts[0];
        }
    }
    $subfolders = array_unique($subfolders);
}

// Ambil file
$stmt = $pdo->prepare("SELECT * FROM documents $whereClause ORDER BY id DESC");
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung Statistik Dashboard
$total_docs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
$bulan_ini = date('Y-m');
$inspeksi_bulan_ini = $pdo->query("SELECT COUNT(*) FROM documents WHERE tanggal LIKE '$bulan_ini%'")->fetchColumn();
$total_reject = $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'Reject'")->fetchColumn();
$waiting_approval = $pdo->query("SELECT COUNT(*) FROM documents WHERE approval_status = 'Waiting Approval'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QC-DMS Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include 'sidebar.php'; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stat Cards: Clean Blue-White Style -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl">💧</div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Dokumen</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight"><?= $total_docs ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl">📅</div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Bulan Ini</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight"><?= $inspeksi_bulan_ini ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center text-xl">❌</div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Reject</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight"><?= $total_reject ?></h3>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl">⏳</div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Approval</p>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight"><?= $waiting_approval ?></h3>
            </div>
        </div>
    </div>

    <!-- Explorer Section -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-8 py-4 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
            <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap text-sm">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mr-2">Navigasi:</span>
                <?php if ($filter): ?>
                    <span class="px-3 py-1 bg-blue-600 text-white rounded text-[9px] font-black uppercase tracking-widest">
                        MODUL <?= strtoupper($filter) ?>
                    </span>
                    <a href="index.php" class="text-[9px] text-slate-400 hover:text-blue-600 font-black tracking-widest ml-4">BATALKAN FILTER</a>
                <?php else: ?>
                    <?php
                    $path_parts = explode('/', $path);
                    foreach ($path_parts as $index => $part) {
                        echo "<a href='?path=".implode('/', array_slice($path_parts, 0, $index+1))."' class='text-xs font-bold text-slate-600 hover:text-blue-600 transition-colors'>$part</a>";
                        if ($index < count($path_parts) - 1) echo "<span class='text-slate-300 mx-1'>/</span>";
                    }
                    ?>
                <?php endif; ?>
            </div>
            
            <div class="flex w-full md:w-auto gap-3">
                <div class="relative flex-grow md:w-64">
                    <input type="text" id="searchInput" placeholder="Cari laporan..." 
                           class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                    <span class="absolute left-3 top-2.5 text-slate-300">🔍</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Nama Dokumen</th>
                        <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Unit</th>
                        <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Jenis</th>
                        <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($subfolders as $name => $fullPath): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-4">
                                <a href="?path=<?= $fullPath ?>" class="flex items-center gap-3">
                                    <span class="text-xl">📁</span>
                                    <span class="font-bold text-slate-700 text-sm"><?= $name ?></span>
                                </a>
                            </td>
                            <td class="px-8 py-4 text-center text-slate-300">-</td>
                            <td class="px-8 py-4"><span class="text-[9px] font-black text-slate-300 uppercase">Folder</span></td>
                            <td class="px-8 py-4 text-right">
                                <a href="?path=<?= $fullPath ?>" class="text-[10px] font-bold text-blue-600">BUKA</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php foreach ($files as $file): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-4">
                                <a href="view.php?id=<?= $file['id'] ?>" class="block group">
                                    <p class="font-bold text-slate-800 text-sm group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($file['nama_dokumen']) ?></p>
                                    <p class="text-[9px] text-slate-400 font-mono tracking-tighter uppercase"><?= htmlspecialchars($file['no_dokumen']) ?></p>
                                </a>
                            </td>
                            <td class="px-8 py-4 text-center text-[10px] font-bold text-slate-500 uppercase"><?= htmlspecialchars($file['machine_id'] ?? '-') ?></td>
                            <td class="px-8 py-4">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded text-[9px] font-black uppercase"><?= htmlspecialchars(str_replace('_', ' ', $file['jenis'])) ?></span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <div class="flex justify-end gap-3 text-[10px] font-black">
                                    <a href="view.php?id=<?= $file['id'] ?>" class="text-blue-600 hover:underline">LIHAT</a>
                                    <?php if ($_SESSION['role'] == 'Admin_Entry'): ?>
                                        <a href="delete.php?id=<?= $file['id'] ?>" class="text-slate-300 hover:text-rose-600" onclick="return confirm('Hapus?')">HAPUS</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($subfolders) && empty($files)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center opacity-30">
                                    <span class="text-6xl mb-4">🧊</span>
                                    <p class="font-black text-xs uppercase tracking-widest">Data Tidak Ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
</div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const rows = document.querySelectorAll('tbody tr');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
        }
    });
</script>
</html>