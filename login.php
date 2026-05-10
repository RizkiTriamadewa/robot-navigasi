<?php
require 'db.php';
require 'auth.php';

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
    <title>Login - Robot Dashboard</title>
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
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }
        
        .input-field {
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin: 2px;
        }
        
        .role-admin { background: #fee2e2; color: #991b1b; }
        .role-operator { background: #dbeafe; color: #1e40af; }
        .role-viewer { background: #d1fae5; color: #065f46; }
        .role-tech { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="w-full max-w-md px-4">
        <div class="login-card p-8 animate-fade-in-up">
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-4 shadow-lg">
                    <i class="fa-solid fa-robot text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-black text-gray-800 mb-2">NAV-X Dashboard</h1>
                <p class="text-gray-600 text-sm">Robot Navigation Monitoring System</p>
            </div>
            
            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5"></i>
                <div>
                    <p class="text-red-800 text-sm font-semibold">Login Gagal</p>
                    <p class="text-red-600 text-xs mt-1"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" class="space-y-5">
                <!-- Username -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fa-solid fa-user mr-2 text-gray-400"></i>Username
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        required
                        autocomplete="username"
                        class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-gray-800"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    >
                </div>
                
                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fa-solid fa-lock mr-2 text-gray-400"></i>Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                        class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-purple-500 text-gray-800"
                        placeholder="Masukkan password"
                    >
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="btn-login w-full py-3 text-white font-bold rounded-lg shadow-lg"
                >
                    <i class="fa-solid fa-right-to-bracket mr-2"></i>Login
                </button>
            </form>
            
            <!-- Demo Accounts Info -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center mb-3 font-semibold">Demo Accounts:</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-gray-700">Admin</span>
                            <span class="role-badge role-admin">Super Admin</span>
                        </div>
                        <p class="text-gray-600 font-mono">admin / admin123</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-gray-700">Operator</span>
                            <span class="role-badge role-operator">Operator</span>
                        </div>
                        <p class="text-gray-600 font-mono">operator / operator123</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-gray-700">Viewer</span>
                            <span class="role-badge role-viewer">Viewer</span>
                        </div>
                        <p class="text-gray-600 font-mono">viewer / viewer123</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-gray-700">Tech</span>
                            <span class="role-badge role-tech">Technician</span>
                        </div>
                        <p class="text-gray-600 font-mono">technician / tech123</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    <i class="fa-solid fa-shield-halved mr-1"></i>
                    Secure RBAC Authentication System
                </p>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="text-center mt-6">
            <p class="text-white text-sm opacity-90">
                © 2026 Robot Navigation Dashboard
            </p>
        </div>
    </div>
</body>
</html>
