<?php
require __DIR__ . '/../src/Config/db.php';
require __DIR__ . '/../src/Auth/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $result = loginUser($conn, $username, $password);

        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NAV-X Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://img.icons8.com/fluency/48/navigation.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
            background: #000;
            color: #e5e7eb;
        }
        body { background: transparent; }   /* biar video di belakang terlihat */

        /* === Background video layer === */
        .bg-video {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            filter: brightness(0.85) saturate(1.05);
        }

        /* Overlay tipis untuk kontras teks. Kuat di sekitar card, transparan di tepi. */
        .bg-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                radial-gradient(ellipse at center, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.15) 70%),
                linear-gradient(180deg, rgba(2,6,23,0.10) 0%, rgba(2,6,23,0.35) 100%);
        }

        /* Konten harus di atas video & overlay */
        main { position: relative; z-index: 2; }

        /* === Glassmorphism card === */
        .login-card {
            background: rgba(15, 23, 42, 0.55);   /* slate-900 / 55% */
            backdrop-filter: blur(18px) saturate(140%);
            -webkit-backdrop-filter: blur(18px) saturate(140%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.04) inset;
        }

        /* === Inputs === */
        .input-field {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: #f1f5f9;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .input-field::placeholder { color: rgba(148, 163, 184, 0.6); }
        .input-field:focus {
            outline: none;
            border-color: rgba(56, 189, 248, 0.7);   /* sky-400 */
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        /* === Submit button === */
        .btn-login {
            background: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
            color: #fff;
            transition: filter .2s ease, transform .05s ease;
        }
        .btn-login:hover  { filter: brightness(1.08); }
        .btn-login:active { transform: scale(0.99); }
    </style>
</head>
<body>

    <!-- BACKGROUND VIDEO -->
    <video class="bg-video" autoplay muted loop playsinline preload="auto" poster="">
        <source src="assets/video/14492092_1920_1080_30fps.mp4" type="video/mp4">
    </video>
    <div class="bg-overlay"></div>

    <main class="min-h-screen w-full flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="login-card p-8">
                <!-- Logo & Title -->
                <div class="text-center mb-7">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 mb-4 shadow-lg shadow-sky-500/30">
                        <i class="fa-solid fa-robot text-white text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-black text-white tracking-tight">NAV-X Dashboard</h1>
                    <p class="text-slate-400 text-xs mt-1">Robot Navigation Monitoring System</p>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="mb-5 p-3 rounded-lg flex items-start gap-3 bg-rose-500/10 border border-rose-500/30">
                    <i class="fa-solid fa-circle-exclamation text-rose-400 mt-0.5"></i>
                    <div>
                        <p class="text-rose-300 text-sm font-semibold">Login Gagal</p>
                        <p class="text-rose-400/80 text-xs mt-0.5"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="" class="space-y-4">
                    <!-- Username -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wide">
                            <i class="fa-solid fa-user mr-1.5 text-slate-500"></i>Username
                        </label>
                        <input
                            type="text"
                            name="username"
                            required
                            autocomplete="username"
                            autofocus
                            class="input-field w-full px-4 py-3 rounded-lg text-sm"
                            placeholder="Masukkan username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        >
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase tracking-wide">
                            <i class="fa-solid fa-lock mr-1.5 text-slate-500"></i>Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                required
                                autocomplete="current-password"
                                class="input-field w-full px-4 py-3 pr-11 rounded-lg text-sm"
                                placeholder="Masukkan password"
                            >
                            <button type="button" onclick="togglePassword()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition-colors"
                                    aria-label="Tampilkan password">
                                <i id="pwd-eye" class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-login w-full py-3 mt-2 font-bold rounded-lg shadow-lg shadow-sky-900/40">
                        <i class="fa-solid fa-right-to-bracket mr-2"></i>Login
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-7 pt-5 border-t border-white/10 text-center">
                    <p class="text-[11px] text-slate-400">
                        <i class="fa-solid fa-shield-halved mr-1 text-sky-400/70"></i>
                        Secure RBAC Authentication
                    </p>
                    <p class="text-[10px] text-slate-500 mt-1">
                        Hubungi administrator untuk mendapatkan akses
                    </p>
                </div>
            </div>

            <!-- Copyright -->
            <p class="text-center text-[11px] text-slate-400/70 mt-5">
                &copy; <?= date('Y') ?> Robot Navigation Dashboard
            </p>
        </div>
    </main>

    <script>
        function togglePassword() {
            const inp = document.getElementById('password');
            const eye = document.getElementById('pwd-eye');
            const isPwd = inp.type === 'password';
            inp.type = isPwd ? 'text' : 'password';
            eye.className = isPwd ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
    </script>
</body>
</html>
