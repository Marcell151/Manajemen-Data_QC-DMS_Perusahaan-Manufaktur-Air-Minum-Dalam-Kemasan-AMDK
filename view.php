<?php
require 'db.php';

// Validasi Parameter ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// Handle Approval (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_doc'])) {
    if ($_SESSION['role'] == 'Manager') {
        $approved_by = $_SESSION['role'] . " (" . date('Y-m-d H:i') . ")";
        $stmt = $pdo->prepare("UPDATE documents SET approval_status = 'Approved', approved_by = ? WHERE id = ?");
        $stmt->execute([$approved_by, $id]);
        $msg = "Dokumen #{$id} telah disetujui secara digital.";
    }
}

// Fetch Document Detail
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika dokumen tidak ditemukan, redirect atau berikan pesan error
if (!$doc) {
    header("Location: index.php");
    exit;
}

// Fetch Parent Doc
$parent_doc = null;
if (!empty($doc['parent_doc_id'])) {
    $stmt = $pdo->prepare("SELECT id, no_dokumen, nama_dokumen FROM documents WHERE id = ?");
    $stmt->execute([$doc['parent_doc_id']]);
    $parent_doc = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch Child Docs
$stmt = $pdo->prepare("SELECT id, no_dokumen, nama_dokumen, jenis FROM documents WHERE parent_doc_id = ?");
$stmt->execute([$id]);
$child_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan QC - <?= htmlspecialchars($doc['no_dokumen'] ?? $doc['id']) ?></title>
    <!-- PDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body class="bg-slate-100/50">
    <?php include 'sidebar.php'; ?>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Action Bar: Clean Blue-White -->
        <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-slate-200">
            <div class="flex gap-2">
                <a href="index.php?path=<?= $doc['folder_path'] ?>" class="px-5 py-2 bg-slate-50 border border-slate-100 text-slate-500 text-[10px] font-bold uppercase tracking-widest rounded-xl transition-all">🔙 Kembali</a>
            </div>
            <div class="flex gap-3">
                <button onclick="downloadPDF()" class="px-6 py-2 bg-blue-600 text-white text-[10px] font-bold uppercase tracking-widest rounded-xl shadow-sm transition-all flex items-center gap-2">
                    📥 Simpan PDF
                </button>
                <?php if ($_SESSION['role'] == 'Manager' && $doc['approval_status'] == 'Waiting Approval'): ?>
                    <form method="POST" class="inline">
                        <button type="submit" name="approve_doc" class="px-6 py-2 bg-emerald-600 text-white text-[10px] font-bold uppercase tracking-widest rounded-xl shadow-sm transition-all">
                            ✅ Approve
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($msg)): ?>
            <div class="bg-blue-50 border border-blue-100 text-blue-700 px-6 py-3 rounded-2xl text-xs font-bold flex items-center gap-3">
                <span>💧</span> <?= $msg ?>
            </div>
        <?php endif; ?>

        <!-- Report Content -->
        <div id="reportContent" class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
            <!-- Header: Simple Blue-White -->
            <div class="p-12 border-b border-slate-100 flex justify-between items-center bg-blue-50/30">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-3xl font-black">💧</div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 leading-tight uppercase">Dokumen Mutu Air</h1>
                        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest opacity-80">Quality Control Unit • Mineral Pure</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-bold text-slate-400 uppercase mb-1">Nomor Laporan:</p>
                    <p class="text-xl font-mono font-bold text-slate-800"><?= htmlspecialchars($doc['no_dokumen'] ?? "REF-000") ?></p>
                </div>
            </div>

            <div class="p-12">
                <!-- Status Box: Simplified -->
                <div class="mb-12 p-6 rounded-2xl border-2 <?= $doc['status'] == 'Lolos' ? 'border-emerald-100 bg-emerald-50/30' : 'border-rose-100 bg-rose-50/30' ?> flex justify-between items-center">
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Hasil Verifikasi Lapangan:</p>
                        <p class="text-2xl font-black <?= $doc['status'] == 'Lolos' ? 'text-emerald-600' : 'text-rose-600' ?> uppercase italic tracking-tight"><?= $doc['status'] ?></p>
                    </div>
                    <div class="text-right">
                         <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Status Approval:</p>
                         <p class="text-xs font-bold text-slate-600 uppercase italic opacity-70"><?= $doc['approval_status'] ?></p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-16 mb-12">
                    <div class="space-y-6">
                        <h3 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-50 pb-2">Informasi Umum</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Unit Mesin</span>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['machine_id'] ?? '-') ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Produk</span>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['produk']) ?></span>
                            </div>
                            <div class="flex justify-between text-sm border-t border-slate-50 pt-2">
                                <span class="text-slate-400">Tanggal</span>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($doc['tanggal']) ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($doc['jenis'] === 'Uji_Lab' || $doc['jenis'] === 'Uji_Ulang'): ?>
                    <div class="space-y-6">
                        <h3 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-50 pb-2">Parameter Fisik</h3>
                        <div class="grid grid-cols-3 gap-2">
                             <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100">
                                <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">pH</p>
                                <p class="text-base font-bold text-slate-800"><?= $doc['ph'] ?? '-' ?></p>
                             </div>
                             <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100">
                                <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">TDS</p>
                                <p class="text-base font-bold text-slate-800"><?= $doc['tds'] ?? '-' ?></p>
                             </div>
                             <div class="p-3 bg-slate-50 rounded-xl text-center border border-slate-100">
                                <p class="text-[8px] font-bold text-slate-400 uppercase mb-1">Tur.</p>
                                <p class="text-base font-bold text-slate-800"><?= $doc['kekeruhan'] ?? '-' ?></p>
                             </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="space-y-6">
                        <h3 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-50 pb-2">Kategori Laporan</h3>
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-xs font-bold text-blue-600 bg-blue-100 px-3 py-1 rounded-full uppercase tracking-tighter"><?= htmlspecialchars(str_replace('_', ' ', $doc['jenis'])) ?></span>
                            <p class="text-[9px] text-slate-400 mt-2 font-medium">Laporan terdokumentasi dalam sistem log digital.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content Area -->
                <div class="space-y-6 mb-12">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em] border-b-2 border-slate-100 pb-2">Detail Analisis & Diagnosis</h3>
                    <div class="p-8 bg-slate-50 rounded-2xl text-sm leading-relaxed text-slate-700 min-h-[150px] border border-slate-100">
                        <?= $doc['deskripsi'] ?: '<em class="text-slate-400 tracking-widest uppercase text-[10px] font-bold">Tidak ada catatan diagnosis terperinci.</em>' ?>
                    </div>
                </div>

                <!-- Traceability Timeline -->
                <?php if ($parent_doc || !empty($child_docs)): ?>
                <div class="mb-12 no-print">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">🔗 Traceability & Alur Siklus</h3>
                    <div class="flex items-center gap-4">
                        <?php if ($parent_doc): ?>
                            <a href="view.php?id=<?= $parent_doc['id'] ?>" class="flex-1 p-4 bg-white border-2 border-dashed border-slate-200 rounded-2xl flex items-center justify-between group hover:border-blue-600 transition-all">
                                <div>
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Dipicu oleh:</p>
                                    <p class="text-xs font-bold text-slate-800 group-hover:text-blue-600"><?= $parent_doc['no_dokumen'] ?></p>
                                </div>
                                <span class="text-slate-300 group-hover:text-blue-600 transition-all">↗</span>
                            </a>
                        <?php endif; ?>

                        <div class="w-10 flex items-center justify-center opacity-20">➡️</div>

                        <div class="flex-1 p-4 bg-blue-50 border-2 border-blue-600 rounded-2xl flex items-center justify-between">
                            <div>
                                <p class="text-[8px] font-black text-blue-600 uppercase mb-1">Dokumen Sekarang:</p>
                                <p class="text-xs font-bold text-blue-800"><?= $doc['no_dokumen'] ?></p>
                            </div>
                        </div>

                        <?php if (!empty($child_docs)): ?>
                            <div class="w-10 flex items-center justify-center opacity-20">➡️</div>
                            <?php foreach($child_docs as $cd): ?>
                                <a href="view.php?id=<?= $cd['id'] ?>" class="flex-1 p-4 bg-white border-2 border-dashed border-slate-200 rounded-2xl flex items-center justify-between group hover:border-emerald-600 transition-all">
                                    <div>
                                        <p class="text-[8px] font-black text-emerald-600 uppercase mb-1">Tindak Lanjut:</p>
                                        <p class="text-xs font-bold text-slate-800 group-hover:text-emerald-600"><?= $cd['no_dokumen'] ?></p>
                                    </div>
                                    <span class="text-slate-300 group-hover:text-emerald-600 transition-all">↗</span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Signature Section -->
                <div class="grid grid-cols-3 gap-10 mt-20">
                    <div class="text-center space-y-16">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Inspector Lapangan</p>
                        <div class="border-b border-slate-900 w-3/4 mx-auto"></div>
                        <p class="text-[11px] font-black text-slate-800 uppercase px-2 py-1 bg-slate-100 inline-block rounded font-mono tracking-tight"><?= htmlspecialchars($doc['inspector'] ?? '-') ?></p>
                    </div>
                    <div class="text-center space-y-16">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Admin Data Entry</p>
                        <div class="border-b border-slate-900 w-3/4 mx-auto"></div>
                        <p class="text-[11px] font-black text-slate-800 uppercase px-2 py-1 bg-slate-100 inline-block rounded font-mono tracking-tight"><?= htmlspecialchars($doc['admin_entry_name'] ?? '-') ?></p>
                    </div>
                    <div class="text-center space-y-16">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Manajer Produksi</p>
                        <div class="border-b border-slate-900 w-3/4 mx-auto relative">
                             <?php if ($doc['approval_status'] == 'Approved'): ?>
                                <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 border-4 border-blue-600/30 rounded-full flex items-center justify-center rotate-12">
                                    <span class="text-[10px] font-black text-blue-600 uppercase tracking-tighter">DIGITAL<br>APPROVED</span>
                                </div>
                             <?php endif; ?>
                        </div>
                        <p class="text-[11px] font-black text-slate-800 uppercase px-2 py-1 bg-slate-100 inline-block rounded font-mono tracking-tight"><?= htmlspecialchars(explode('(', $doc['approved_by'] ?? '-')[0]) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Smart Trigger: Diagnosis Button -->
        <?php if ($doc['status'] == 'Reject' && empty($child_docs)): ?>
            <div class="p-8 bg-rose-600 rounded-3xl shadow-xl shadow-rose-600/20 text-white flex justify-between items-center no-print">
                <div class="flex items-center gap-4">
                    <span class="text-4xl">🔬</span>
                    <div>
                        <h4 class="text-lg font-black leading-none mb-1 uppercase tracking-tighter">Butuh Tindak Lanjut Diagnosis!</h4>
                        <p class="text-xs text-rose-100 font-medium opacity-80">Dokumen berstatus **Reject**. Perusahaan mewajibkan investigasi segera.</p>
                    </div>
                </div>
                <button onclick="triggerDiagnosis()" class="px-6 py-3 bg-white text-rose-600 text-xs font-black uppercase tracking-widest rounded-xl hover:bg-rose-50 transition-all shadow-lg">Lakukan Diagnosis Sekarang</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hidden form for Diagnosis Trigger -->
    <form id="diagnosisForm" action="add.php" method="GET" class="hidden">
        <input type="hidden" name="parent_id" value="<?= $id ?>">
        <input type="hidden" name="machine" value="<?= htmlspecialchars($doc['machine_id']) ?>">
        <input type="hidden" name="batch" value="<?= htmlspecialchars($doc['produk']) ?>">
    </form>

    <script>
        function downloadPDF() {
            const element = document.getElementById('reportContent');
            const options = {
                margin:       0.5,
                filename:     'QC_REPORT_<?= $doc['no_dokumen'] ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(options).from(element).save();
        }

        function triggerDiagnosis() {
            // Dalam simulasi ini, kita redirect ke add.php dengan parameter
            window.location.href = "add.php?p_id=<?= $id ?>&p_no=<?= $doc['no_dokumen'] ?>&m_id=<?= urlencode($doc['machine_id']) ?>&prod=<?= urlencode($doc['produk']) ?>";
        }
    </script>

    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</body>
</html>