<?php
session_start();
require_once '../config/koneksi.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    redirect('/umkm-keuangan/dashboard/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
            setFlash('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');
            redirect('/umkm-keuangan/dashboard/index.php');
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .login-bg {
            background:
                radial-gradient(circle at 12% 18%, rgba(148, 163, 184, 0.22), transparent 30%),
                radial-gradient(circle at 88% 82%, rgba(71, 85, 105, 0.28), transparent 34%),
                linear-gradient(135deg, #e5e7eb 0%, #cbd5e1 48%, #94a3b8 100%);
        }
        .login-shell {
            box-shadow: 0 28px 90px rgba(15, 23, 42, 0.28);
        }
        .login-panel {
            background:
                linear-gradient(135deg, rgba(100, 116, 139, 0.86), rgba(148, 163, 184, 0.72)),
                linear-gradient(90deg, rgba(15, 23, 42, 0.08), transparent);
        }
        .card-glow {
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.38), inset 0 1px 0 rgba(255, 255, 255, 0.10);
        }
        .input-focus:focus {
            border-color: rgba(96, 165, 250, 0.9);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.22);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .float-anim { animation: float 4s ease-in-out infinite; }
        .bg-grid {
            background-image:
                linear-gradient(rgba(71, 85, 105, 0.12) 1px, transparent 1px),
                linear-gradient(90deg, rgba(71, 85, 105, 0.12) 1px, transparent 1px);
            background-size: 40px 40px;
        }
    </style>
</head>
<body class="login-bg bg-grid min-h-screen flex items-center justify-center p-4 text-slate-100">

    <div class="fixed inset-x-0 top-0 h-48 bg-gradient-to-b from-white/40 to-transparent pointer-events-none"></div>

    <main class="w-full max-w-5xl login-shell rounded-[2rem] overflow-hidden border border-white/50 bg-slate-400/55 backdrop-blur-xl md:grid md:grid-cols-[1fr_440px]">
        <section class="login-panel hidden md:flex min-h-[640px] flex-col justify-between p-10">
            <div>
                <div class="inline-flex items-center gap-3 rounded-full border border-white/35 bg-white/20 px-4 py-2 text-sm font-semibold text-slate-100">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Sistem Keuangan UMKM
                </div>
                <h1 class="mt-8 max-w-md text-4xl font-extrabold leading-tight tracking-tight text-white drop-shadow">
                    Kelola uang usaha dengan tampilan yang lebih rapi.
                </h1>
                <p class="mt-4 max-w-sm text-sm leading-6 font-medium text-slate-100/85">
                    Masuk untuk mencatat pemasukan, pengeluaran, kategori, dan laporan harian dari satu dashboard.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl border border-white/30 bg-white/18 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-200">Cepat</p>
                    <p class="mt-3 text-2xl font-extrabold text-white">CRUD</p>
                    <p class="mt-1 text-xs font-medium text-slate-100/80">Transaksi usaha harian</p>
                </div>
                <div class="rounded-2xl border border-white/30 bg-white/18 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-200">Laporan</p>
                    <p class="mt-3 text-2xl font-extrabold text-white">Grafik</p>
                    <p class="mt-1 text-xs font-medium text-slate-100/80">Ringkasan keuangan</p>
                </div>
            </div>
        </section>

        <section class="w-full px-6 py-10 sm:px-10 md:py-12 bg-slate-300/65">
            <div class="text-center mb-8 float-anim">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-emerald-400 rounded-2xl mb-4 shadow-lg shadow-slate-700/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-800 tracking-tight"><?= APP_NAME ?></h2>
                <p class="text-slate-600 mt-1 text-sm font-medium">Sistem Keuangan Modern</p>
            </div>

            <div class="bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl p-8 card-glow">
                <h2 class="text-xl font-bold text-white mb-6">Masuk ke Akun Anda</h2>

                <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/40 text-red-200 rounded-xl px-4 py-3 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm"><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-5">
                        <label class="block text-slate-200 text-sm font-medium mb-2">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                class="w-full bg-slate-950/60 border border-white/10 text-white placeholder-slate-500 rounded-xl pl-11 pr-4 py-3 focus:outline-none input-focus transition-all"
                                placeholder="Masukkan username" required>
                        </div>
                    </div>

                    <div class="mb-7">
                        <label class="block text-slate-200 text-sm font-medium mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input type="password" name="password" id="passwordInput"
                                class="w-full bg-slate-950/60 border border-white/10 text-white placeholder-slate-500 rounded-xl pl-11 pr-12 py-3 focus:outline-none input-focus transition-all"
                                placeholder="Masukkan password" required>
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-blue-300 hover:text-white transition-colors" aria-label="Tampilkan password">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-emerald-500 hover:from-blue-500 hover:to-emerald-400 text-white font-bold py-3.5 rounded-xl transition-all duration-200 hover:shadow-lg hover:shadow-blue-950/30 hover:-translate-y-0.5 active:translate-y-0">
                        Masuk Sekarang
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-white/10">
                    <p class="text-slate-400 text-xs text-center">Demo: username <span class="text-blue-200 font-mono">admin</span> / password <span class="text-emerald-200 font-mono">password</span></p>
                </div>
            </div>
        </section>
    </main>

    <script>
    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon = document.getElementById('eyeIcon');

        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            return;
        }

        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
    </script>
</body>
</html>
