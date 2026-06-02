<?php
require 'db.php';

// Ambil data mesin dan inspector untuk dropdown
$machines = $pdo->query("SELECT * FROM machines ORDER BY nama_mesin ASC")->fetchAll(PDO::FETCH_ASSOC);
$inspectors = $pdo->query("SELECT * FROM inspectors ORDER BY nama_inspector ASC")->fetchAll(PDO::FETCH_ASSOC);

// Daftar Produk Standar
$product_list = [
    'Mineral_600ml' => 'Mineral 600ml',
    'Mineral_330ml' => 'Mineral 330ml',
    'Cup_240ml' => 'Cup 240ml',
    'Galon_19L' => 'Galon 19L'
];

// Mapping step ke jenis dokumen
$step_mapping = [
    '1' => 'Catatan_Batch',
    '2' => 'Uji_Lab',
    '3' => 'Diagnosis_Mesin',
    '4' => 'Laporan_Perbaikan',
    '5' => 'Uji_Ulang',
    '6' => 'Approval_Manager'
];

$step = $_GET['step'] ?? '';
$is_fixed_step = !empty($step); // Jika ada parameter step, maka langkah dikunci
$current_step_num = $step ?: '1';
$pre_jenis = $step_mapping[$current_step_num] ?? 'Catatan_Batch';

// PROTEKSI HAK AKSES INPUT (Berdasarkan Role)
$can_access_input = false;
if ($_SESSION['role'] == 'Pekerja_Lapangan') {
    if (!$is_fixed_step || in_array($step, ['1', '3', '4', '5'])) $can_access_input = true;
} elseif ($_SESSION['role'] == 'Admin_Entry') {
    if (!$is_fixed_step || $step == '2') $can_access_input = true;
}

if (!$can_access_input && $_SESSION['role'] !== 'Manager') {
    header("Location: index.php");
    exit;
}

// Ambil daftar Laporan Induk sesuai langkah aktif (chaining)
$parent_options = [];
if ($current_step_num != '1') {
    if ($current_step_num == '2') {
        $parent_options = $pdo->query("SELECT id, no_dokumen, produk, machine_id FROM documents WHERE jenis = 'Catatan_Batch' ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($current_step_num == '3') {
        $parent_options = $pdo->query("SELECT id, no_dokumen, produk, machine_id FROM documents WHERE jenis = 'Uji_Lab' AND status = 'Reject' ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($current_step_num == '4') {
        $parent_options = $pdo->query("SELECT id, no_dokumen, produk, machine_id FROM documents WHERE jenis = 'Diagnosis_Mesin' AND approval_status = 'Approved' ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($current_step_num == '5') {
        $parent_options = $pdo->query("SELECT id, no_dokumen, produk, machine_id FROM documents WHERE jenis = 'Laporan_Perbaikan' ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($current_step_num == '6') {
        $parent_options = $pdo->query("SELECT id, no_dokumen, produk, machine_id FROM documents WHERE jenis = 'Uji_Ulang' AND (status = 'Passed' OR status = 'Lolos') ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Logic Simpan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_dokumen'] ?? 'Dokumen Baru';
    $produk = $_POST['produk'] ?? '-';
    $jenis = $_POST['jenis'];
    $tanggal = $_POST['tanggal'];
    $inspector = $_POST['inspector'] ?? 'System';
    $machine_id = $_POST['machine_id'] ?? '-';
    $status_mutu = $_POST['status_mutu'] ?? 'Passed';
    if ($jenis !== 'Uji_Lab' && $jenis !== 'Uji_Ulang') {
        $status_mutu = 'Passed';
    }
    $status = $status_mutu;
    $deskripsi = $_POST['deskripsi'] ?? '';
    $parent_doc_id = $_POST['parent_doc_id'] ?? null;
    
    // Blocker Langkah 04: Harus memiliki parent Langkah 03 yang Approved
    if ($jenis == 'Laporan_Perbaikan') {
        if (empty($parent_doc_id)) {
            die("Error: Dokumen Perbaikan harus memiliki Laporan Diagnosis Masalah sebagai induk.");
        }
        $stmt_check = $pdo->prepare("SELECT jenis, approval_status FROM documents WHERE id = ?");
        $stmt_check->execute([$parent_doc_id]);
        $parent_doc_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$parent_doc_check || $parent_doc_check['jenis'] !== 'Diagnosis_Mesin' || $parent_doc_check['approval_status'] !== 'Approved') {
            die("Error: Langkah 04 hanya dapat dibuat jika Laporan Diagnosis Masalah (Langkah 03) telah disetujui (Approved) oleh Manajer Produksi.");
        }
    }
    
    $external_link = $_POST['external_link'] ?? '';
    $ph = $_POST['ph'] ?? null;
    $tds = $_POST['tds'] ?? null;
    $kekeruhan = $_POST['kekeruhan'] ?? null;
    $file_path = '';
    
    if (isset($_FILES['dokumen_fisik']) && $_FILES['dokumen_fisik']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = time() . '_' . basename($_FILES['dokumen_fisik']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['dokumen_fisik']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        }
    }

    $codes = ['Catatan_Batch' => 'BTCH', 'Uji_Lab' => 'LABS', 'Diagnosis_Mesin' => 'DIAG', 'Laporan_Perbaikan' => 'REPR', 'Uji_Ulang' => 'RETS', 'Approval_Manager' => 'APPR'];
    $prefix = $codes[$jenis] ?? 'MISC';
    $yearMonth = date("ym", strtotime($tanggal));
    $stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE no_dokumen LIKE ?");
    $stmtSeq->execute(["QC-$prefix-$yearMonth-%"]);
    $count = $stmtSeq->fetchColumn() + 1;
    $sequence = str_pad($count, 3, "0", STR_PAD_LEFT);
    $no_dokumen = "QC-$prefix-$yearMonth-$sequence";
    
    if(empty($nama) || $nama == 'Dokumen Baru') {
        $nama_clean = str_replace('_', ' ', $jenis);
        $nama = "$nama_clean - $no_dokumen";
    }

    $timestamp = strtotime($tanggal);
    $tahun = date("Y", $timestamp);
    $bulan = date("F", $timestamp);
    $folder_path = "QC_AMDK/{$produk}/{$tahun}/{$bulan}";

    $approval_status = ($jenis == 'Approval_Manager' || $jenis == 'Diagnosis_Mesin') ? 'Waiting Approval' : '-';

    $stmt = $pdo->prepare("INSERT INTO documents (no_dokumen, nama_dokumen, produk, jenis, tanggal, inspector, machine_id, admin_entry_name, status, status_mutu, deskripsi, folder_path, parent_doc_id, file_path, approval_status, external_link, ph, tds, kekeruhan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$no_dokumen, $nama, $produk, $jenis, $tanggal, $inspector, $machine_id, $_SESSION['role'], $status, $status_mutu, $deskripsi, $folder_path, $parent_doc_id, $file_path, $approval_status, $external_link, $ph, $tds, $kekeruhan]);

    header("Location: index.php");
    exit;
}

$is_mobile_mode = in_array($current_step_num, ['1', '3', '4', '5']);

// Mapping step ke file template form kosong PDF
$template_pdf_map = [
    '1' => 'uploads/CATATAN PRODUKSI (SAMPLING).pdf',
    '2' => 'uploads/ANALISIS LABORATORIUM UTAMA.pdf',
    '3' => 'uploads/DIAGNOSIS MASALAH (INVESTIGASI).pdf',
    '4' => 'uploads/TINDAKAN PERBAIKAN TEKNIK.pdf',
    '5' => 'uploads/VERIFIKASI UJI ULANG (RE-TEST).pdf',
    '6' => 'uploads/OTORISASI & APPROVAL MANAGER.pdf',
];
$template_label_map = [
    '1' => 'Catatan Produksi (Sampling)',
    '2' => 'Analisis Laboratorium Utama',
    '3' => 'Diagnosis Masalah (Investigasi)',
    '4' => 'Tindakan Perbaikan Teknik',
    '5' => 'Verifikasi Uji Ulang (Re-Test)',
    '6' => 'Otorisasi & Approval Manager',
];
$template_pdf     = $template_pdf_map[$current_step_num] ?? null;
$template_label   = $template_label_map[$current_step_num] ?? 'Form Kosong';
$template_exists  = $template_pdf && file_exists($template_pdf);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input QC - Mineral Pure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        :root { --primary: #0284c7; --bg-main: #f8fafc; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-main); color: #1e293b; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .form-card { background: white; border-radius: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        label { display: block; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 0.75rem; }
        input, select, textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            transition: all 0.2s;
            background: #fdfdfd;
            -webkit-appearance: none;
            appearance: none;
        }
        input:focus, select:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.1); background: white; }
        .btn-save {
            background: #0f172a;
            color: white;
            padding: 1.25rem 3rem;
            border-radius: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.875rem;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            width: 100%;
        }
        .btn-save:hover { background: var(--primary); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(2, 132, 199, 0.2); }
        
        .camera-btn {
            background: #0284c7;
            color: white;
            padding: 2rem;
            border-radius: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 3px dashed rgba(255,255,255,0.3);
            min-height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .camera-btn:active { transform: scale(0.97); background: #0369a1; }
        
        /* ---- MOBILE SPECIFIC ---- */
        @media (max-width: 767px) {
            .form-card { border-radius: 20px !important; padding: 1.25rem !important; }
            .form-grid { display: flex !important; flex-direction: column !important; gap: 1.5rem !important; }
            label { font-size: 0.8rem !important; }
            input, select, textarea { font-size: 1rem !important; padding: 0.9rem 1rem !important; border-radius: 14px !important; }
            .camera-btn { min-height: 100px !important; border-radius: 20px !important; padding: 1.5rem !important; }
            .camera-btn .cam-icon { font-size: 3rem !important; }
            .camera-btn .cam-label { font-size: 1rem !important; }
            .verdict-grid { grid-template-columns: 1fr 1fr !important; gap: 0.75rem !important; }
            .verdict-box { padding: 1.2rem 0.5rem !important; border-radius: 16px !important; }
            .btn-save { font-size: 1rem !important; padding: 1.1rem !important; border-radius: 16px !important; }
            .section-card { border-radius: 16px !important; padding: 1rem !important; }
        }
    </style>
</head>
<body class="antialiased">
    <?php include 'sidebar.php'; ?>

    <div class="p-4 max-w-5xl mx-auto">
        <div class="mb-6 md:mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-3 md:gap-4">
            <div>
                <!-- Mobile: Back link -->
                <a href="index.php" class="md:hidden mb-3 flex items-center gap-1 text-slate-500 hover:text-sky-600 text-sm font-black transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali
                </a>
                <p class="text-xs font-black text-sky-600 uppercase tracking-[0.3em] mb-1">
                    Step <?= $current_step_num ?> &bull; <?= htmlspecialchars($template_label) ?>
                </p>
                <h1 class="text-2xl md:text-4xl font-extrabold text-slate-900 tracking-tight">Input Bukti Lapangan</h1>

                <!-- Mobile: Cetak Form Kosong button (below title) -->
                <?php if ($template_exists): ?>
                <a href="<?= htmlspecialchars($template_pdf) ?>" target="_blank"
                   class="md:hidden mt-3 inline-flex items-center gap-2 px-4 py-2 bg-slate-100 border border-slate-300 text-slate-700 text-xs font-black uppercase rounded-xl no-print">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak Form Kosong &rarr;
                </a>
                <?php endif; ?>
            </div>

            <!-- Desktop: Cetak Form Kosong button -->
            <?php if ($template_exists): ?>
            <a href="<?= htmlspecialchars($template_pdf) ?>" target="_blank"
               class="no-print hidden md:flex items-center gap-2 px-5 py-3 bg-white border border-slate-200 text-slate-700 text-xs font-black uppercase rounded-xl hover:bg-slate-900 hover:text-white transition-all group">
                <svg class="w-4 h-4 text-slate-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak Form Kosong</span>
                <span class="text-slate-500 group-hover:text-slate-300 font-bold normal-case tracking-normal">
                    &mdash; <?= htmlspecialchars($template_label) ?>
                </span>
            </a>
            <?php else: ?>
            <span class="no-print hidden md:flex items-center gap-2 px-5 py-3 bg-slate-50 border border-slate-200 text-slate-400 text-xs font-black uppercase rounded-xl cursor-not-allowed"
                  title="Template PDF tidak ditemukan di folder uploads/">
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak Form Kosong (Tidak Tersedia)
            </span>
            <?php endif; ?>
        </div>

        <form action="add.php?step=<?= $step ?>" method="POST" enctype="multipart/form-data" class="form-card p-5 md:p-12 mb-32">
            
            <!-- Top Action Header -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 pb-6 mb-6 border-b border-slate-100 no-print">
                <p class="text-sm font-black text-slate-500 uppercase tracking-wider hidden md:block">Input Data Lapangan</p>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto justify-end">
                    <a href="index.php" class="w-full sm:w-auto px-5 py-3 text-center text-xs font-black text-slate-500 uppercase tracking-widest hover:text-rose-600 transition-colors border border-slate-200 rounded-xl">Batal</a>
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-slate-950 hover:bg-sky-600 text-white text-xs font-black uppercase rounded-xl transition-all shadow-sm">Kirim Laporan</button>
                </div>
            </div>

            <div class="form-grid <?= $is_mobile_mode ? 'flex flex-col gap-8' : 'grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20' ?>">
                
                <div class="space-y-6 md:space-y-8">
                    <div class="bg-slate-50 p-4 md:p-6 rounded-2xl md:rounded-3xl border border-slate-200 section-card">
                        <label>Langkah Alur Kerja</label>
                        <select name="jenis" id="jenisSelect" onchange="window.location.href='add.php?step=' + this.options[this.selectedIndex].getAttribute('data-step')" <?= $is_fixed_step ? 'readonly class="bg-slate-100 pointer-events-none opacity-70"' : '' ?>>
                            <?php foreach ($step_mapping as $k => $val): 
                                $show_option = false;
                                if ($_SESSION['role'] == 'Pekerja_Lapangan' && in_array($k, ['1', '3', '4', '5'])) $show_option = true;
                                if ($_SESSION['role'] == 'Admin_Entry' && $k == '2') $show_option = true;
                                if ($_SESSION['role'] == 'Manager' && $k == '6') $show_option = true;
                                if (!$show_option) continue;
                            ?>
                                <option value="<?= $val ?>" data-step="<?= $k ?>" <?= ($current_step_num == $k) ? 'selected' : '' ?>><?= $val[0].$val[1] ?>. <?= str_replace('_', ' ', $val) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($current_step_num != '1'): ?>
                    <div class="bg-sky-50/50 p-4 md:p-6 rounded-2xl md:rounded-3xl border border-sky-100 section-card">
                        <label class="text-sky-800">Pilih Laporan Induk</label>
                        <select name="parent_doc_id" id="parentSelect" required onchange="autoFillMetadata()">
                            <option value="">-- Pilih Laporan Induk --</option>
                            <?php foreach ($parent_options as $p): 
                                $selected = (isset($_GET['p_id']) && $_GET['p_id'] == $p['id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $p['id'] ?>" data-prod="<?= $p['produk'] ?>" data-machine="<?= $p['machine_id'] ?>" <?= $selected ?>>
                                    <?= $p['no_dokumen'] ?> (<?= str_replace('_', ' ', $p['produk']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-sky-850 mt-2 font-bold italic">*Pilih ini agar data Lini Produk & Kode Mesin terisi otomatis.</p>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label>Tanggal Pelaporan</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                        <div>
                            <label>Lini Produk</label>
                            <select name="produk" id="produkSelect" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($product_list as $key => $val): 
                                    $selected = (isset($_GET['prod']) && $_GET['prod'] == $key) ? 'selected' : '';
                                ?>
                                    <option value="<?= $key ?>" <?= $selected ?>><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Kode Mesin</label>
                            <select name="machine_id" id="machineSelect" required>
                                <option value="">-- Pilih Mesin --</option>
                                <?php foreach ($machines as $m): 
                                    $selected = (isset($_GET['m_id']) && $_GET['m_id'] == $m['nama_mesin']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $m['nama_mesin'] ?>" <?= $selected ?>><?= $m['nama_mesin'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Petugas Lapangan</label>
                        <select name="inspector" required>
                            <?php foreach ($inspectors as $i): ?>
                                <option value="<?= $i['nama_inspector'] ?>"><?= $i['nama_inspector'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($current_step_num == '2' || $current_step_num == '5'): ?>
                    <div class="p-4 md:p-6 bg-slate-50 rounded-2xl md:rounded-3xl border border-slate-200 section-card">
                        <label class="mb-4 text-slate-800 block font-black">Parameter Lab Aktual</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div><label class="text-xs">pH</label><input type="number" step="0.1" name="ph" class="p-2.5 text-base bg-white"></div>
                            <div><label class="text-xs">TDS</label><input type="number" step="1" name="tds" class="p-2.5 text-base bg-white"></div>
                            <div><label class="text-xs">NTU</label><input type="number" step="0.01" name="kekeruhan" class="p-2.5 text-base bg-white"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="space-y-6 md:space-y-10">
                    <!-- Verdict Selection (Hanya untuk Langkah 02 & 05) -->
                    <?php if ($current_step_num == '2' || $current_step_num == '5'): ?>
                    <div>
                        <label>Hasil Uji Kualitas (Fisik)</label>
                        <div class="verdict-grid grid grid-cols-2 gap-3 md:gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="status_mutu" value="Passed" checked class="hidden peer">
                                <div class="verdict-box py-5 border-2 border-slate-200 rounded-2xl text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all">
                                    <div class="flex items-center justify-center mb-1 text-slate-400 peer-checked:text-emerald-700">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <span class="text-xs font-black text-slate-500 peer-checked:text-emerald-800 uppercase block">PASSED / LOLOS</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="status_mutu" value="Reject" class="hidden peer">
                                <div class="verdict-box py-5 border-2 border-slate-200 rounded-2xl text-center peer-checked:border-rose-500 peer-checked:bg-rose-50 transition-all">
                                    <div class="flex items-center justify-center mb-1 text-slate-400 peer-checked:text-rose-700">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </div>
                                    <span class="text-xs font-black text-slate-500 peer-checked:text-rose-800 uppercase block">REJECT / GAGAL</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="status_mutu" value="Passed">
                    <?php endif; ?>

                    <!-- File Upload -->
                    <div class="space-y-4">
                        <label>Lampiran Bukti (Foto Kamera)</label>
                        <div class="camera-btn" onclick="document.getElementById('fileInput').click()" style="min-height:90px">
                            <svg class="w-8 h-8 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="cam-label text-sm font-black uppercase tracking-widest mt-1">Unggah Bukti / Ambil Foto</span>
                            <p class="text-xs opacity-80">Kamera langsung aktif di Tablet/HP</p>
                        </div>
                        <input type="file" name="dokumen_fisik" id="fileInput" accept="image/*" capture="environment" class="hidden" onchange="updateFileName(this)">
                        <div id="fileStatus" class="text-center text-xs font-black text-emerald-800 py-3 bg-emerald-50 rounded-xl border border-emerald-250 hidden">File Siap Diunggah</div>
                        
                        <div class="pt-3 border-t border-slate-100">
                            <label class="text-xs">Atau Gunakan Tautan Cloud (G-Drive, dll)</label>
                            <input type="url" name="external_link" placeholder="https://drive.google.com/..." class="p-3 text-sm bg-white">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label>Catatan Temuan Lapangan</label>
                        <textarea name="deskripsi" rows="3" placeholder="Tuliskan catatan atau kendala di sini..." style="min-height:80px" class="bg-white"></textarea>
                    </div>
                </div>

            </div>

            <div class="mt-8 md:mt-12 pt-6 md:pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-slate-500 font-bold italic hidden md:block">Pastikan data & foto sudah benar sebelum menyimpan.</p>
                <div class="flex gap-3 w-full md:w-auto">
                    <a href="index.php" class="flex-grow md:flex-grow-0 px-6 py-4 text-center text-sm font-black text-slate-500 hover:text-rose-600 transition-colors border border-slate-200 rounded-2xl bg-white">Batal</a>
                    <button type="submit" class="btn-save flex-grow md:flex-grow-0 md:w-auto">Kirim Laporan</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function autoFillMetadata() {
            const select = document.getElementById('parentSelect');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                const prod = selectedOption.getAttribute('data-prod');
                const machine = selectedOption.getAttribute('data-machine');
                
                // Update Selects
                const prodSelect = document.getElementById('produkSelect');
                const machineSelect = document.getElementById('machineSelect');
                
                prodSelect.value = prod;
                machineSelect.value = machine;
                
                // Beri efek highlight visual bahwa data berubah
                prodSelect.classList.add('bg-sky-50');
                machineSelect.classList.add('bg-sky-50');
                setTimeout(() => {
                    prodSelect.classList.remove('bg-sky-50');
                    machineSelect.classList.remove('bg-sky-50');
                }, 1000);
            }
        }

        function updateFileName(input) {
            if (input.files && input.files[0]) {
                const fileStatus = document.getElementById('fileStatus');
                fileStatus.classList.remove('hidden');
                fileStatus.innerText = "✓ Berhasil Memuat: " + input.files[0].name;
            }
        }
    </script>
</body>
</html>