<?php
require 'db.php';

// Jika sudah login, redirect ke index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Berhasil login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    } catch (PDOException $e) {
        $error = "Kesalahan Database: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QC-DMS Mineral Pure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="bg-sky-700 p-8 text-center relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-sky-500/30 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center text-white font-black text-2xl mx-auto mb-4 border border-sky-400/30 shadow-inner">
                    MP
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">QC-DMS</h1>
                <p class="text-sky-200 text-sm mt-1 font-bold tracking-widest uppercase">Mineral Pure Indonesia</p>
            </div>
        </div>
        
        <div class="p-8">
            <h2 class="text-xl font-bold text-slate-800 mb-6 text-center">Masuk ke Akun Anda</h2>
            
            <?php if ($error): ?>
            <div class="bg-rose-50 text-rose-600 p-4 rounded-xl text-sm font-bold border border-rose-200 mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-5">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 outline-none transition-all text-slate-800 font-semibold bg-slate-50 focus:bg-white" placeholder="Masukkan username">
                </div>
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 outline-none transition-all text-slate-800 font-semibold bg-slate-50 focus:bg-white" placeholder="••••••••">
                </div>
                <button type="submit" class="w-full py-3.5 bg-sky-600 hover:bg-sky-700 text-white font-black rounded-xl transition-all shadow-md hover:shadow-lg flex justify-center items-center gap-2 uppercase tracking-wide text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    Login
                </button>
            </form>
            
            <div class="mt-8 p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 font-semibold">
                <p class="font-black text-slate-800 mb-2 uppercase tracking-wider">Demo Akun (Password: 123456)</p>
                <ul class="space-y-1 list-disc list-inside">
                    <li><span class="text-sky-700">manajer</span> - Manajer Produksi</li>
                    <li><span class="text-sky-700">qc</span> - Admin QC</li>
                    <li><span class="text-sky-700">teknisi</span> - Teknisi Lapangan</li>
                </ul>
            </div>
        </div>
    </div>
    
</body>
</html>
