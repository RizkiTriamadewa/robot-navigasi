<?php
require 'db.php';
require 'auth.php';

// Require login to access dashboard
requireLogin();

// Check session timeout
if (isSessionExpired()) {
    logoutUser($conn);
    header('Location: login.php?timeout=1');
    exit;
}

// Refresh session
refreshSession();

// Get current user
$currentUser = getCurrentUser();

// Ambil data SESI TERBARU (Memungkinkan banyak laporan dalam 1 hari)
$query = $conn->query("SELECT * FROM daily_logs ORDER BY log_date DESC LIMIT 1");
$todayData = $query->fetch_assoc();

$initId = $todayData ? $todayData['id'] : 0; // Mengambil ID untuk sesi saat ini
$initDistance = $todayData ? $todayData['distance_m'] : 0;
$initWaterUsed = $todayData ? $todayData['water_used_ml'] : 0;
$initBattery = ($todayData && isset($todayData['battery_percent'])) ? $todayData['battery_percent'] : 100;
$initPath = $todayData && $todayData['path_data'] ? $todayData['path_data'] : "[]";
$initSpray = ($todayData && isset($todayData['spray_data'])) ? $todayData['spray_data'] : "[]";

// Ambil SELURUH data riwayat untuk tabel
$historyQuery = $conn->query("SELECT * FROM daily_logs ORDER BY log_date DESC");
$historyData = [];
$availableYears = [];
$availableMonths = [];

while($row = $historyQuery->fetch_assoc()) {
    $historyData[] = $row;
    $time = strtotime($row['log_date']);
    $y = date('Y', $time);
    $m = date('m', $time);
    if(!in_array($y, $availableYears)) $availableYears[] = $y;
    if(!in_array($m, $availableMonths)) $availableMonths[] = $m;
}
sort($availableYears);
sort($availableMonths);
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Robot Navigasi Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://img.icons8.com/fluency/48/navigation.png" type="image/png">
    
    <script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-database-compat.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* MODERN CLEAN DESIGN - CONSISTENT COLOR SCHEME */
        * { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body, html { 
            height: 100%; 
            width: 100%; 
            overflow: hidden; 
        }
        
        /* LIGHT MODE - Single Clean White/Gray Theme */
        body { 
            background: #f8fafc;
            color: #1e293b;
        }
        
        /* DARK MODE - Single Consistent Dark Theme */
        body.dark {
            background: #0f172a;
            color: #e2e8f0;
        }

        /* MENCEGAH SWEETALERT MERUSAK LAYOUT */
        body.swal2-height-auto, html.swal2-height-auto {
            height: 100% !important;
        }
        
        /* PANELS - Consistent Design */
        .panel { 
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .dark .panel {
            background: #1e293b !important;
            border: 1px solid #334155 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        /* STAT CARDS */
        .stat-card {
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .dark .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        
        /* CONTROL BUTTONS */
        .btn-control {
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        
        .btn-control:hover {
            transform: translateY(-1px);
        }
        
        .btn-control:active { 
            transform: scale(0.98);
        }
        
        /* ACTION BUTTONS */
        .btn-action {
            background: #0ea5e9;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            background: #0284c7;
        }
        
        .dark .btn-action {
            background: #0284c7;
        }
        
        .dark .btn-action:hover {
            background: #0369a1;
        }
        
        .btn-action:active {
            transform: scale(0.98);
        }
        
        /* TAB BUTTONS - Consistent Blue */
        .tab-btn {
            position: relative;
            transition: all 0.2s ease;
        }
        
        .tab-btn.active {
            background: #0ea5e9 !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
        }

        .dark .tab-btn.active {
            background: #0284c7 !important;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.4);
        }
        
        /* MAP CANVAS */
        #minimap {
            background: #f8fafc;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
        }
        
        .dark #minimap {
            background: #0f172a;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        /* SELECT DROPDOWN - Clean Style */
        select { 
            -webkit-appearance: none; 
            -moz-appearance: none; 
            appearance: none;
            background: #ffffff;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            font-weight: 500;
            color: #1e293b !important;
            transition: all 0.2s ease;
        }
        
        .dark select {
            background: #1e293b;
            border: 1px solid #334155 !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            color: #e2e8f0 !important;
        }
        
        select option {
            background-color: #ffffff;
            color: #1e293b;
        }
        
        .dark select option {
            background-color: #1e293b;
            color: #e2e8f0;
        }
        
        select::-ms-expand { display: none; }
        
        select:hover {
            border-color: #0ea5e9 !important;
            box-shadow: 0 2px 4px rgba(14, 165, 233, 0.2);
        }
        
        .dark select:hover {
            border-color: #0284c7 !important;
            box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3);
        }
        
        select:focus {
            outline: none;
            border-color: #0ea5e9 !important;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .dark select:focus {
            border-color: #0284c7 !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
        }
        
        /* TAB CONTENT */
        .tab-content { 
            display: none;
            opacity: 0;
        }
        
        .tab-content.active { 
            display: flex;
            animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(10px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }
        
        /* TABLE STYLING - Consistent */
        table thead {
            background: #0ea5e9;
            color: white;
        }
        
        .dark table thead {
            background: #0284c7;
        }
        
        table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        table tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        .dark table tbody tr:hover {
            background-color: #334155;
        }
        
        /* SCROLLBAR - Consistent */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .dark ::-webkit-scrollbar-track {
            background: #1e293b;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
            transition: background 0.2s ease;
        }
        
        .dark ::-webkit-scrollbar-thumb {
            background: #475569;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Consistent accent color throughout */
        .text-teal-500, .text-teal-600 {
            color: #0ea5e9 !important;
        }
        
        .dark .text-teal-400, .dark .text-teal-500 {
            color: #38bdf8 !important;
        }
        
        .bg-teal-50 {
            background-color: #f0f9ff !important;
        }
        
        .dark .bg-teal-50 {
            background-color: rgba(14, 165, 233, 0.1) !important;
        }
        
        /* SENSOR CARDS */
        .sensor-card {
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .sensor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .dark .sensor-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        
        /* STATUS INDICATORS */
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .status-online { background: #10b981; }
        .status-offline { background: #ef4444; }
        .status-warning { background: #f59e0b; }
        
        /* LIVE INDICATOR */
        .live-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            animation: pulseLive 2s infinite;
        }
        
        @keyframes pulseLive {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            50% { opacity: 0.8; box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        }
        
        /* LOG ENTRY STYLES */
        .log-entry {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .log-entry:hover {
            border-left-color: #14b8a6;
            background-color: rgba(20, 184, 166, 0.05);
        }
        
        .dark .log-entry:hover {
            background-color: rgba(20, 184, 166, 0.1);
        }
        
        .log-info { border-left-color: #3b82f6; }
        .log-success { border-left-color: #10b981; }
        .log-warning { border-left-color: #f59e0b; }
        .log-error { border-left-color: #ef4444; }
        
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        
        .dark .badge-info { background: #1e3a8a; color: #93c5fd; }
        .dark .badge-success { background: #064e3b; color: #6ee7b7; }
        .dark .badge-warning { background: #78350f; color: #fcd34d; }
        .dark .badge-error { background: #7f1d1d; color: #fca5a5; }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden flex flex-col p-2 font-sans bg-gray-100 text-gray-800 dark:bg-[#1a1e29] dark:text-[#a0aec0]">

<div class="flex-none flex justify-between items-center panel p-3 rounded-xl bg-white border border-gray-200 shadow-lg mb-2 gap-3 h-16">
    <h1 class="text-base md:text-2xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-sky-600 to-cyan-600 flex items-center shrink-0">
        <i class="fa-solid fa-robot text-blue-500 mr-3 text-2xl md:text-3xl drop-shadow-lg"></i> NAV-X
    </h1>
    
    <div class="flex space-x-1 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-800 dark:to-slate-900 p-1.5 rounded-xl border border-gray-200 dark:border-slate-700 mx-auto shadow-inner">
        <button onclick="switchTab('monitoring')" id="btn-tab-monitoring" class="tab-btn active px-3 py-2 rounded-lg font-bold text-xs transition-all">
            <i class="fa-solid fa-display mr-1"></i> <span class="hidden md:inline">Monitoring</span>
        </button>
        <button onclick="switchTab('sensors')" id="btn-tab-sensors" class="tab-btn px-3 py-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700 font-bold text-xs transition-all">
            <i class="fa-solid fa-microchip mr-1"></i> <span class="hidden md:inline">Sensors</span>
        </button>
        <button onclick="switchTab('logbook')" id="btn-tab-logbook" class="tab-btn px-3 py-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700 font-bold text-xs transition-all">
            <i class="fa-solid fa-book mr-1"></i> <span class="hidden md:inline">Logbook</span>
        </button>
        <button onclick="switchTab('riwayat')" id="btn-tab-riwayat" class="tab-btn px-3 py-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700 font-bold text-xs transition-all">
            <i class="fa-solid fa-clock-rotate-left mr-1"></i> <span class="hidden md:inline">Riwayat</span>
        </button>
        <button onclick="switchTab('laporan')" id="btn-tab-laporan" class="tab-btn px-3 py-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700 font-bold text-xs transition-all">
            <i class="fa-solid fa-file-pdf mr-1"></i> <span class="hidden md:inline">Laporan</span>
        </button>
    </div>

    <div class="flex items-center space-x-2 shrink-0">
        <!-- User Info -->
        <div class="hidden md:flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800">
            <i class="fa-solid fa-user-circle text-blue-500 text-lg"></i>
            <div class="text-left">
                <p class="text-[10px] font-bold text-blue-700 dark:text-blue-400 leading-none"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                <p class="text-[9px] text-blue-600 dark:text-blue-500 leading-none"><?= htmlspecialchars($currentUser['role_name']) ?></p>
            </div>
        </div>
        
        <!-- Clock -->
        <div class="text-[10px] md:text-xs hidden md:flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 border border-teal-200 dark:border-teal-800">
            <i class="fa-solid fa-clock text-teal-500 text-sm"></i>
            <span id="clock" class="text-teal-600 dark:text-teal-400 font-mono font-bold">00:00:00</span>
        </div>
        
        <!-- Theme Toggle -->
        <button onclick="toggleTheme()" class="px-3 py-2 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 text-gray-700 hover:from-gray-200 hover:to-gray-300 dark:from-slate-700 dark:to-slate-800 dark:text-yellow-400 dark:hover:from-slate-600 dark:hover:to-slate-700 transition-all shadow-md hover:shadow-lg">
            <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
        </button>
        
        <!-- Logout Button -->
        <button onclick="confirmLogout()" class="px-3 py-2 rounded-lg bg-gradient-to-br from-red-100 to-red-200 text-red-700 hover:from-red-200 hover:to-red-300 dark:from-red-900/30 dark:to-red-800/30 dark:text-red-400 dark:hover:from-red-900/50 dark:hover:to-red-800/50 transition-all shadow-md hover:shadow-lg" title="Logout">
            <i class="fa-solid fa-right-from-bracket text-sm"></i>
        </button>
    </div>
</div>

<div id="tab-monitoring" class="tab-content active flex-1 flex-col space-y-2 min-h-0 overflow-hidden">
    
    <div class="flex-none grid grid-cols-4 gap-3">
        <!-- Card 1: Baterai -->
        <div class="stat-card panel p-3 rounded-xl flex items-center space-x-3 bg-white border border-gray-200 shadow-lg">
            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-xl shrink-0 bg-teal-50 text-teal-600 dark:bg-transparent dark:text-teal-400 text-sm md:text-lg">
                <i class="fa-solid fa-battery-three-quarters"></i>
            </div>
            <div>
                <div class="text-[9px] md:text-[11px] text-gray-500 dark:text-gray-400 leading-none mb-1.5 font-semibold uppercase tracking-wide">Battery</div>
                <div class="text-sm md:text-lg font-black text-gray-900 dark:text-white leading-none" id="val-battery">--%</div>
            </div>
        </div>

        <!-- Card 2: Jarak -->
        <div class="stat-card panel p-3 rounded-xl flex items-center space-x-3 bg-white border border-gray-200 shadow-lg">
            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-xl shrink-0 bg-teal-50 text-teal-600 dark:bg-transparent dark:text-teal-400 text-sm md:text-lg">
                <i class="fa-solid fa-route"></i>
            </div>
            <div>
                <div class="text-[9px] md:text-[11px] text-gray-500 dark:text-gray-400 leading-none mb-1.5 font-semibold uppercase tracking-wide">Jarak</div>
                <div class="text-sm md:text-lg font-black text-gray-900 dark:text-white leading-none"><span id="val-distance">0</span> m</div>
            </div>
        </div>

        <!-- Card 3: Air Keluar -->
        <div class="stat-card panel p-3 rounded-xl flex items-center space-x-3 bg-white border border-gray-200 shadow-lg">
            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-xl shrink-0 bg-teal-50 text-teal-600 dark:bg-transparent dark:text-teal-400 text-sm md:text-lg">
                <i class="fa-solid fa-faucet-drip"></i>
            </div>
            <div>
                <div class="text-[9px] md:text-[11px] text-gray-500 dark:text-gray-400 leading-none mb-1.5 font-semibold uppercase tracking-wide">Air Keluar</div>
                <div class="text-sm md:text-lg font-black text-gray-900 dark:text-white leading-none"><span id="val-water-used">0</span> ml</div>
            </div>
        </div>

        <!-- Card 4: Sisa Tangki -->
        <div class="stat-card panel p-3 rounded-xl flex items-center space-x-3 bg-white border border-gray-200 shadow-lg">
            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center rounded-xl shrink-0 bg-teal-50 text-teal-600 dark:bg-transparent dark:text-teal-400 text-sm md:text-lg">
                <i class="fa-solid fa-prescription-bottle"></i>
            </div>
            <div>
                <div class="text-[9px] md:text-[11px] text-gray-500 dark:text-gray-400 leading-none mb-1.5 font-semibold uppercase tracking-wide">Sisa Tangki</div>
                <div class="text-sm md:text-lg font-black text-gray-900 dark:text-white leading-none"><span id="val-water-rem">2000</span> ml</div>
            </div>
        </div>
    </div>

    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-2 min-h-0">
        
        <div class="flex flex-col gap-2 min-h-0 h-full">
            
            <div class="flex-1 flex flex-col panel rounded-xl p-3 bg-white border border-gray-200 shadow-lg min-h-0">
                <div class="flex-none flex justify-between items-center mb-2">
                    <div class="flex items-center gap-2">
                        <h2 class="text-[11px] font-bold text-gray-600 dark:text-gray-300 tracking-widest uppercase flex items-center gap-2">
                            <i class="fa-solid fa-video text-blue-500"></i> Live FPV
                        </h2>
                        <div class="relative inline-block">
                            <select id="camera-select" onchange="switchCamera(this.value)" class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 text-[9px] pl-2 pr-6 py-1.5 rounded-lg border border-gray-300 dark:from-slate-700 dark:to-slate-800 dark:border-slate-600 dark:text-white outline-none cursor-pointer max-w-[140px] font-semibold shadow-sm hover:shadow-md transition-all appearance-none"></select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-1.5 text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-chevron-down text-[7px]"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button onclick="takePhoto()" class="text-[10px] font-bold text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 dark:from-blue-800 dark:to-blue-900 dark:hover:from-blue-700 dark:hover:to-blue-800 px-3 py-1.5 rounded-lg transition-all shadow-md hover:shadow-lg flex items-center gap-1.5">
                            <i class="fa-solid fa-camera"></i> FOTO
                        </button>                  
                        <button id="btn-record" onclick="toggleRecording()" class="text-[10px] font-bold text-white bg-gradient-to-r from-gray-400 to-gray-500 hover:from-red-500 hover:to-red-600 px-3 py-1.5 rounded-lg transition-all shadow-md hover:shadow-lg flex items-center gap-1.5">
                            <span id="record-dot" class="w-2 h-2 rounded-full bg-white shadow-sm"></span> <span id="record-text">REKAM 30s</span>
                        </button>
                    </div>
                </div>
                <div class="flex-1 relative bg-gradient-to-br from-gray-900 to-black rounded-xl overflow-hidden border-2 border-gray-300 dark:border-gray-700 flex items-center justify-center min-h-0 shadow-inner">
                    <video id="webcam-video" autoplay playsinline class="absolute inset-0 w-full h-full object-cover scale-x-[-1] transition-opacity duration-100"></video>
                    
                    <div class="absolute inset-0 pointer-events-none border-2 border-teal-500/40 m-3 rounded-lg z-10"></div>
                    <div class="absolute top-1/2 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-teal-500/50 to-transparent pointer-events-none z-10"></div>
                    <div class="absolute left-1/2 top-0 h-full w-[2px] bg-gradient-to-b from-transparent via-teal-500/50 to-transparent pointer-events-none z-10"></div>
                    
                    <div id="cam-status-text" class="text-center z-20 bg-black/70 px-4 py-3 rounded-xl backdrop-blur-md border border-gray-600 shadow-2xl">
                        <i class="fa-solid fa-camera text-2xl text-teal-400 mb-2 animate-pulse"></i>
                        <p class="text-gray-200 font-mono text-[11px] tracking-widest font-semibold">MEMINTA AKSES KAMERA...</p>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col panel rounded-lg p-3 bg-white border border-gray-200 shadow-sm min-h-0">
                <div class="flex-none flex flex-wrap justify-between items-center mb-2 gap-1">
                    <h2 class="text-[10px] font-bold text-gray-500 tracking-widest uppercase">Controls</h2>
                    
                    <div class="flex gap-1.5 items-center">
                        <div class="relative inline-block">
                            <select id="autosave-select" onchange="updateIdleSetting(true)" class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 text-[9px] pl-2.5 pr-6 py-1.5 rounded-lg outline-none cursor-pointer hover:from-gray-100 hover:to-gray-200 dark:from-slate-700 dark:to-slate-800 dark:text-white dark:hover:from-slate-600 dark:hover:to-slate-700 appearance-none font-semibold shadow-sm hover:shadow-md transition-all border border-gray-300 dark:border-slate-600">
                                <option value="0">Auto Save: OFF</option>
                                <option value="30000">Idle 30 Detik</option>
                                <option value="60000">Idle 1 Menit</option>
                                <option value="180000">Idle 3 Menit</option>
                                <option value="300000">Idle 5 Menit</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-1.5 text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-chevron-down text-[7px]"></i>
                            </div>
                        </div>
                        <div class="relative inline-block">
                            <select id="mode-select" class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 text-[9px] pl-2.5 pr-6 py-1.5 rounded-lg outline-none cursor-pointer hover:from-gray-100 hover:to-gray-200 dark:from-slate-700 dark:to-slate-800 dark:text-white dark:hover:from-slate-600 dark:hover:to-slate-700 appearance-none font-semibold shadow-sm hover:shadow-md transition-all border border-gray-300 dark:border-slate-600">
                                <option value="manual">Manual</option>
                                <option value="auto">Auto (GPS)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-1.5 text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-chevron-down text-[7px]"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 flex flex-row items-stretch justify-center gap-3 sm:gap-6 min-h-0 h-full">
                    <div class="flex-1 flex flex-col items-center justify-center gap-2 md:gap-4 p-2 md:p-4 bg-[#f8fafc] dark:bg-[#1a1e29]/50 rounded-2xl border border-gray-100 dark:border-slate-700/50 shadow-sm">
                        <button onclick="moveRobot('up')" class="btn-control w-14 h-14 md:w-24 md:h-24 rounded-xl shrink-0 flex items-center justify-center text-2xl md:text-4xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256] <?= !hasPermission('control_robot') ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !hasPermission('control_robot') ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-chevron-up"></i>
                        </button>
                        <div class="flex gap-2 md:gap-4">
                            <button onclick="moveRobot('left')" class="btn-control w-14 h-14 md:w-24 md:h-24 rounded-xl shrink-0 flex items-center justify-center text-2xl md:text-4xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256] <?= !hasPermission('control_robot') ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !hasPermission('control_robot') ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <button onclick="moveRobot('down')" class="btn-control w-14 h-14 md:w-24 md:h-24 rounded-xl shrink-0 flex items-center justify-center text-2xl md:text-4xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256] <?= !hasPermission('control_robot') ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !hasPermission('control_robot') ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <button onclick="moveRobot('right')" class="btn-control w-14 h-14 md:w-24 md:h-24 rounded-xl shrink-0 flex items-center justify-center text-2xl md:text-4xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256] <?= !hasPermission('control_robot') ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !hasPermission('control_robot') ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex-1 flex flex-col gap-2 h-full py-1">
                        <button onclick="sprayWater()" class="flex-1 min-h-0 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl shadow-sm transition-all flex flex-col justify-center items-center gap-1.5 text-xs sm:text-sm <?= !hasPermission('spray_water') ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !hasPermission('spray_water') ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-droplet text-2xl sm:text-3xl mb-1"></i> Semprot
                        </button>
                        <div class="flex-1 min-h-0 flex gap-2">
                            <button onclick="saveData(false)" class="flex-1 bg-green-600 hover:bg-green-500 text-white font-bold rounded-xl shadow-sm transition-all flex flex-col justify-center items-center gap-1 text-[10px] sm:text-xs <?= !hasPermission('save_session') ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !hasPermission('save_session') ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-floppy-disk text-lg sm:text-xl mb-0.5"></i> Simpan
                            </button>
                            <button onclick="resetData()" class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-400 rounded-xl transition shadow-sm flex flex-col justify-center items-center gap-1 text-[10px] sm:text-xs <?= !hasPermission('reset_session') ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= !hasPermission('reset_session') ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-rotate-right text-lg sm:text-xl mb-0.5"></i> Sesi Baru
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="flex flex-col panel rounded-lg p-2 bg-white border border-gray-200 shadow-sm min-h-0 h-full relative cursor-crosshair">
            <div class="flex-none flex justify-between items-center mb-1">
                <h2 class="text-[10px] font-bold text-gray-500 tracking-widest uppercase">Map & Tracking</h2>
                <div class="flex gap-2 items-center">
                    <span class="text-[8px] text-gray-400 italic">Tahan & Gambar rute</span>
                    <span id="gps-status" class="text-[8px] font-bold px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 dark:bg-slate-700 dark:text-gray-400">GPS OFFLINE</span>
                </div>
            </div>
            <div class="flex-1 relative bg-[#f8fafc] dark:bg-slate-800 rounded border border-gray-200 dark:border-slate-700 shadow-inner w-full h-full min-h-0">
                <canvas id="minimap" class="absolute top-0 left-0 w-full h-full touch-none"></canvas>
            </div>
        </div>

    </div>
</div>

<div id="tab-riwayat" class="tab-content flex-1 flex-col space-y-2 overflow-hidden min-h-0">
    <div class="panel h-full flex flex-col p-3 rounded-lg bg-white border border-gray-200 shadow-sm">
        <div class="flex-none flex justify-between items-center mb-3">
            <h2 class="text-sm md:text-base font-bold text-gray-900 dark:text-white"><i class="fa-solid fa-clock-rotate-left mr-2 text-teal-500"></i> Riwayat Sesi</h2>
            <div class="flex space-x-2 overflow-x-auto pb-1 md:pb-0">
                <div class="relative inline-block">
                    <select id="filter-day" onchange="filterTable()" class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 text-xs pl-2.5 pr-7 py-2 rounded-lg border border-gray-300 dark:from-slate-700 dark:to-slate-800 dark:border-slate-600 dark:text-white font-semibold shadow-sm cursor-pointer outline-none hover:from-gray-100 hover:to-gray-200 dark:hover:from-slate-600 dark:hover:to-slate-700 transition-all appearance-none">
                        <option value="all">Semua Hari</option>
                        <?php for($i=1; $i<=31; $i++): $d = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-gray-500 dark:text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[8px]"></i>
                    </div>
                </div>
                <div class="relative inline-block">
                    <select id="filter-month" onchange="filterTable()" class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 text-xs pl-2.5 pr-7 py-2 rounded-lg border border-gray-300 dark:from-slate-700 dark:to-slate-800 dark:border-slate-600 dark:text-white font-semibold shadow-sm cursor-pointer outline-none hover:from-gray-100 hover:to-gray-200 dark:hover:from-slate-600 dark:hover:to-slate-700 transition-all appearance-none">
                        <option value="all">Semua Bln</option>
                        <option value="01">Jan</option><option value="02">Feb</option><option value="03">Mar</option><option value="04">Apr</option>
                        <option value="05">Mei</option><option value="06">Jun</option><option value="07">Jul</option><option value="08">Agu</option>
                        <option value="09">Sep</option><option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-gray-500 dark:text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[8px]"></i>
                    </div>
                </div>
                <div class="relative inline-block">
                    <select id="filter-year" onchange="filterTable()" class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 text-xs pl-2.5 pr-7 py-2 rounded-lg border border-gray-300 dark:from-slate-700 dark:to-slate-800 dark:border-slate-600 dark:text-white font-semibold shadow-sm cursor-pointer outline-none hover:from-gray-100 hover:to-gray-200 dark:hover:from-slate-600 dark:hover:to-slate-700 transition-all appearance-none">
                        <option value="all">Semua Thn</option>
                        <?php foreach($availableYears as $y): ?><option value="<?= $y ?>"><?= $y ?></option><?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-gray-500 dark:text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[8px]"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-auto rounded-lg border border-gray-200 dark:border-slate-700 relative shadow-inner bg-[#f8fafc] dark:bg-slate-800/50">
            <table class="w-full text-xs sm:text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="sticky top-0 bg-gray-200 dark:bg-slate-900 z-10 shadow-sm uppercase tracking-wider font-semibold text-[10px] sm:text-xs">
                    <tr>
                        <th class="px-3 py-3 whitespace-nowrap">Waktu (Sesi)</th>
                        <th class="px-3 py-3 whitespace-nowrap">Baterai</th>
                        <th class="px-3 py-3 whitespace-nowrap">Jarak</th>
                        <th class="px-3 py-3 whitespace-nowrap">Air Keluar</th>
                        <th class="px-3 py-3 whitespace-nowrap">Sisa Air</th>
                    </tr>
                </thead>
                <tbody id="history-table-body" class="divide-y divide-gray-200 dark:divide-slate-700/80">
                    <?php if(empty($historyData)): ?>
                        <tr><td colspan="5" class="p-6 text-center text-gray-500 font-medium italic">Belum ada riwayat terekam.</td></tr>
                    <?php else: ?>
                        <?php foreach($historyData as $row): 
                            $time = strtotime($row['log_date']);
                            $year = date('Y', $time);
                            $month = date('m', $time);
                            $day = date('d', $time);
                            $sisaAir = 2000 - $row['water_used_ml'];
                            $btr = isset($row['battery_percent']) ? number_format($row['battery_percent'], 1) : 100.0;
                        ?>
                        <tr class="bg-white hover:bg-teal-50 dark:bg-[#232836] dark:hover:bg-slate-800 transition-colors" data-year="<?= $year ?>" data-month="<?= $month ?>" data-day="<?= $day ?>">
                            <td class="px-3 py-3 whitespace-nowrap font-semibold text-gray-800 dark:text-gray-200"><?= date('d M Y - H:i', $time) ?></td>
                            <td class="px-3 py-3 whitespace-nowrap font-bold text-green-600 dark:text-green-400"><?= $btr ?>%</td>
                            <td class="px-3 py-3 whitespace-nowrap font-bold text-teal-600 dark:text-teal-400"><?= number_format($row['distance_m'], 1) ?>m</td>
                            <td class="px-3 py-3 whitespace-nowrap font-medium"><?= $row['water_used_ml'] ?>ml</td>
                            <td class="px-3 py-3 whitespace-nowrap font-bold text-cyan-600 dark:text-cyan-400"><?= max(0, $sisaAir) ?>ml</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="tab-laporan" class="tab-content flex-1 flex-col items-center justify-center min-h-0 overflow-y-auto">
    <div class="panel w-full max-w-sm p-4 rounded-lg bg-white border border-gray-200 shadow-sm text-center">
        <i class="fa-solid fa-file-pdf text-4xl text-red-500 mb-2"></i>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Cetak Laporan</h2>
        <p class="text-[10px] text-gray-600 dark:text-gray-400 mb-4">Merangkum jalur navigasi sesi saat ini.</p>
        
        <div class="bg-[#f8fafc] dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded p-2 mb-4 grid grid-cols-2 gap-2 text-xs">
            <div><span class="block text-[9px] text-gray-500">Jarak Sesi:</span><strong class="text-gray-800 dark:text-white" id="lap-jarak">0 m</strong></div>
            <div><span class="block text-[9px] text-gray-500">Air Digunakan:</span><strong class="text-gray-800 dark:text-white" id="lap-air">0 ml</strong></div>
        </div>

        <button onclick="generatePDF()" id="btn-print" class="w-full px-4 bg-red-600 hover:bg-red-500 text-white font-bold py-2 rounded shadow transition-all opacity-50 cursor-not-allowed text-xs">
            <i class="fa-solid fa-print mr-1"></i> Download PDF
        </button>
        <p class="text-[9px] text-red-500 mt-2 font-semibold" id="print-warning">⚠️ Klik "Simpan Data" di Tab Monitoring terlebih dahulu.</p>
    </div>
</div>

<!-- TAB SENSORS -->
<div id="tab-sensors" class="tab-content flex-1 flex-col space-y-3 min-h-0 overflow-hidden">
    <div class="flex-1 overflow-y-auto space-y-3 min-h-0">
        
        <!-- Sensor Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            
            <!-- Level Cairan -->
            <div class="sensor-card panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
                <div class="flex justify-between items-start mb-3">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                        <i class="fa-solid fa-flask text-2xl"></i>
                    </div>
                    <div class="status-indicator status-online"></div>
                </div>
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Level Cairan</h3>
                <div class="text-2xl font-black text-gray-900 dark:text-white" id="sensor-liquid-level">0%</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="sensor-liquid-ml">0 ml</div>
            </div>
            
            <!-- Motion Detection -->
            <div class="sensor-card panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
                <div class="flex justify-between items-start mb-3">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">
                        <i class="fa-solid fa-person-running text-2xl"></i>
                    </div>
                    <div class="status-indicator status-offline" id="sensor-motion-status"></div>
                </div>
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Motion Detection</h3>
                <div class="text-2xl font-black text-gray-900 dark:text-white" id="sensor-motion-state">Idle</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Last: <span id="sensor-motion-time">--:--</span></div>
            </div>
            
            <!-- Posisi (X, Y, Z) -->
            <div class="sensor-card panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
                <div class="flex justify-between items-start mb-3">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400">
                        <i class="fa-solid fa-location-crosshairs text-2xl"></i>
                    </div>
                    <div class="status-indicator status-online"></div>
                </div>
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Posisi (X, Y, Z)</h3>
                <div class="text-sm font-bold text-gray-900 dark:text-white">
                    X: <span id="sensor-pos-x">0</span> | Y: <span id="sensor-pos-y">0</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Z: <span id="sensor-pos-z">0</span> m</div>
            </div>
            
            <!-- Speed -->
            <div class="sensor-card panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
                <div class="flex justify-between items-start mb-3">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400">
                        <i class="fa-solid fa-gauge-high text-2xl"></i>
                    </div>
                    <div class="status-indicator status-online"></div>
                </div>
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Speed</h3>
                <div class="text-2xl font-black text-gray-900 dark:text-white"><span id="sensor-speed-val">0.0</span> m/s</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg: <span id="sensor-speed-avg">0.0</span> m/s</div>
            </div>
            
        </div>
        
        <!-- Mode & Koneksi Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            
            <!-- Mode Monitor -->
            <div class="panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400">
                        <i class="fa-solid fa-sliders text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Mode Operasi</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Status mode robot saat ini</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Mode Aktif</div>
                        <div class="text-lg font-black text-gray-900 dark:text-white" id="sensor-mode-active">Manual</div>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</div>
                        <div class="text-lg font-black text-teal-600 dark:text-teal-400" id="sensor-mode-status">Active</div>
                    </div>
                </div>
            </div>
            
            <!-- Koneksi Internet -->
            <div class="panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 dark:bg-cyan-900/20 dark:text-cyan-400">
                        <i class="fa-solid fa-wifi text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Koneksi Internet</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Status jaringan dan latency</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</div>
                        <div class="text-sm font-black text-green-600 dark:text-green-400" id="sensor-net-status">Online</div>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ping</div>
                        <div class="text-sm font-black text-gray-900 dark:text-white"><span id="sensor-net-ping">--</span> ms</div>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Quality</div>
                        <div class="text-sm font-black text-gray-900 dark:text-white" id="sensor-net-quality">Good</div>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<!-- TAB LOGBOOK -->
<div id="tab-logbook" class="tab-content flex-1 flex-col min-h-0 overflow-hidden">
    <div class="flex-1 flex flex-col panel rounded-xl p-4 bg-white border border-gray-200 shadow-lg overflow-hidden">
        
        <!-- Header & Filters -->
        <div class="flex-none flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <div class="live-indicator"></div>
                    Activity Logs
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Real-time monitoring aktivitas robot</p>
            </div>
            
            <div class="flex flex-wrap gap-2">
                <div class="relative inline-block">
                    <select id="logbook-filter-type" onchange="filterLogbookLogs()" class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 text-xs pl-2.5 pr-7 py-2 rounded-lg border border-gray-300 dark:from-slate-700 dark:to-slate-800 dark:border-slate-600 dark:text-white font-semibold shadow-sm cursor-pointer outline-none hover:from-gray-100 hover:to-gray-200 dark:hover:from-slate-600 dark:hover:to-slate-700 transition-all appearance-none">
                        <option value="all">Semua Tipe</option>
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-gray-500 dark:text-gray-400">
                        <i class="fa-solid fa-chevron-down text-[8px]"></i>
                    </div>
                </div>
                
                <button onclick="clearLogbookLogs()" class="px-4 py-2 rounded-lg bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold text-xs transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                    <i class="fa-solid fa-trash"></i> Clear
                </button>
                
                <button onclick="exportLogbookLogs()" class="px-4 py-2 rounded-lg bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold text-xs transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                    <i class="fa-solid fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="flex-none grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold mb-1">Total Logs</div>
                <div class="text-2xl font-black text-blue-700 dark:text-blue-300" id="logbook-stat-total">0</div>
            </div>
            <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <div class="text-xs text-green-600 dark:text-green-400 font-semibold mb-1">Success</div>
                <div class="text-2xl font-black text-green-700 dark:text-green-300" id="logbook-stat-success">0</div>
            </div>
            <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                <div class="text-xs text-yellow-600 dark:text-yellow-400 font-semibold mb-1">Warnings</div>
                <div class="text-2xl font-black text-yellow-700 dark:text-yellow-300" id="logbook-stat-warning">0</div>
            </div>
            <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="text-xs text-red-600 dark:text-red-400 font-semibold mb-1">Errors</div>
                <div class="text-2xl font-black text-red-700 dark:text-red-300" id="logbook-stat-error">0</div>
            </div>
        </div>
        
        <!-- Log Entries -->
        <div class="flex-1 overflow-y-auto space-y-2 bg-gray-50 dark:bg-slate-800/50 rounded-lg p-3 border border-gray-200 dark:border-slate-700">
            <div id="logbook-container">
                <!-- Logs will be inserted here -->
            </div>
        </div>
        
    </div>
</div>

<div id="pdf-wrapper" style="display: none; position: absolute; top: 0; left: 0; width: 100%; z-index: -9999; background: white; padding: 10px;">
    <div id="pdf-report-template" class="mx-auto w-[700px] bg-white text-black p-8 font-sans border border-gray-200">
        <div class="border-b-2 border-gray-800 pb-4 mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold uppercase"><i class="fa-solid fa-robot mr-2"></i> Report NAV-X</h1>
                <p class="text-sm text-gray-600">Laporan Rekapitulasi Data Robot Monitoring</p>
            </div>
            <div class="text-right">
                <p class="font-bold">Waktu Cetak:</p>
                <p id="pdf-datetime" class="text-sm"></p>
            </div>
        </div>
        <table class="w-full border-collapse border border-gray-400 mb-8 text-sm text-left table-fixed">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-gray-400 p-2">Baterai</th>
                    <th class="border border-gray-400 p-2">Jarak Tempuh</th>
                    <th class="border border-gray-400 p-2">Air Keluar</th>
                    <th class="border border-gray-400 p-2">Sisa Tangki</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-gray-400 p-2" id="pdf-battery">100%</td>
                    <td class="border border-gray-400 p-2"><span id="pdf-distance">0</span> m</td>
                    <td class="border border-gray-400 p-2"><span id="pdf-water-used">0</span> ml</td>
                    <td class="border border-gray-400 p-2"><span id="pdf-water-rem">2000</span> ml</td>
                </tr>
            </tbody>
        </table>
        <h3 class="font-bold text-lg mb-3">Peta Jalur Robot</h3>
        <div class="border border-gray-400 p-2 flex justify-center bg-gray-50 rounded"><img id="pdf-map-image" src="" style="max-width: 100%;"></div>
    </div>
</div>

<script>
    // --- FIREBASE INIT ---
    const firebaseConfig = { databaseURL: "https://nav-track-36e9f-default-rtdb.firebaseio.com" };
    firebase.initializeApp(firebaseConfig);
    const database = firebase.database();

    // --- RECORD VIDEO VARIABLES ---
    let mediaRecorder;
    let recordInterval;
    let isRecording = false;

    // --- CAMERA ---
    let currentStream = null; 

    async function getCameras() {
        try {
            const initialStream = await navigator.mediaDevices.getUserMedia({ video: true });
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            const cameraSelect = document.getElementById('camera-select');
            cameraSelect.innerHTML = ''; 
            
            if (videoDevices.length === 0) {
                cameraSelect.innerHTML = '<option value="">Tidak ada kamera</option>';
                return;
            }

            videoDevices.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.text = camera.label || `Kamera ${index + 1}`;
                cameraSelect.appendChild(option);
            });

            initialStream.getTracks().forEach(track => track.stop());
            if (videoDevices.length > 0) {
                startWebcam(videoDevices[0].deviceId);
            }
        } catch (err) {
            console.error('Error in getCameras:', err);
            const statusText = document.getElementById('cam-status-text');
            statusText.style.display = 'block';
            statusText.querySelector('p').innerText = "AKSES KAMERA DITOLAK";
            statusText.querySelector('p').classList.replace('text-gray-300', 'text-red-500');
        }
    }

    async function startWebcam(deviceId = null) {
        if (currentStream) { currentStream.getTracks().forEach(track => track.stop()); }

        const constraints = { video: deviceId ? { deviceId: { exact: deviceId } } : true };

        try {
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            currentStream = stream;
            document.getElementById('webcam-video').srcObject = stream;
            document.getElementById('cam-status-text').style.display = 'none';
        } catch (err) {
            console.error('Error in startWebcam:', err);
            const statusText = document.getElementById('cam-status-text');
            statusText.style.display = 'block';
            statusText.querySelector('p').innerText = "GAGAL MEMUAT KAMERA";
            statusText.querySelector('p').classList.replace('text-gray-300', 'text-red-500');
        }
    }

    function switchCamera(deviceId) { if (deviceId) startWebcam(deviceId); }

    // --- FUNGSI AMBIL FOTO ---
    function takePhoto() {
        const video = document.getElementById('webcam-video');
        if(!video.srcObject) return Swal.fire({icon: 'error', title: 'Error', text: 'Kamera belum aktif!', heightAuto: false});

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');

        ctx.translate(canvas.width, 0); ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const a = document.createElement('a');
        a.href = canvas.toDataURL('image/png');
        a.download = `NavX_Snapshot_${Date.now()}.png`;
        a.click();
        
        video.style.opacity = 0;
        setTimeout(() => video.style.opacity = 1, 150);
    }

    // --- FUNGSI RECORD 30 DETIK ---
    function toggleRecording() { if(isRecording) stopRecording(); else startRecordingCycle(); }

    function startRecordingCycle() {
        const stream = document.getElementById('webcam-video').srcObject;
        if(!stream) return Swal.fire({icon: 'error', title: 'Error', text: 'Kamera belum aktif!', heightAuto: false});
        
        isRecording = true;
        const btnRec = document.getElementById('btn-record');
        btnRec.classList.replace('bg-gray-400', 'bg-red-600');
        btnRec.classList.add('blinking-record');
        document.getElementById('record-text').innerText = "STOP REC";

        let chunks = [];
        mediaRecorder = new MediaRecorder(stream, { mimeType: 'video/webm' });
        mediaRecorder.ondataavailable = e => { if(e.data.size > 0) chunks.push(e.data); };
        
        mediaRecorder.onstop = () => {
            if(chunks.length > 0) {
                const blob = new Blob(chunks, { type: 'video/webm' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `NavX_Record_${Date.now()}.webm`;
                a.click();
                URL.revokeObjectURL(url);
            }
            if(isRecording) startRecordingCycle();
        };
        
        mediaRecorder.start();
        recordInterval = setTimeout(() => {
            if(mediaRecorder.state === 'recording') mediaRecorder.stop();
        }, 30000);
    }

    function stopRecording() {
        isRecording = false;
        clearTimeout(recordInterval);
        if(mediaRecorder && mediaRecorder.state === 'recording') mediaRecorder.stop();
        
        const btnRec = document.getElementById('btn-record');
        btnRec.classList.replace('bg-red-600', 'bg-gray-400');
        btnRec.classList.remove('blinking-record');
        document.getElementById('record-text').innerText = "REKAM 30s";
    }

    // --- BATTERY ---
    function initBatteryStatus() {
        if ('getBattery' in navigator) {
            navigator.getBattery().then(bat => {
                function updateBat() {
                    const level = (bat.level * 100).toFixed(1);
                    robotData.battery = parseFloat(level);
                    document.getElementById('val-battery').innerText = level + "%";
                    document.getElementById('pdf-battery').innerText = level + "%";
                    if (bat.charging) document.getElementById('val-battery').classList.add('text-green-500');
                    else document.getElementById('val-battery').classList.remove('text-green-500');
                }
                updateBat();
                bat.addEventListener('levelchange', updateBat);
                bat.addEventListener('chargingchange', updateBat);
            });
        }
    }

    // --- GPS & MAP VARIABLES ---
    let lastLat = null; let lastLng = null;
    const PIXELS_PER_METER = 10; 

    function calculateGPSDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; 
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) * Math.sin(dLon/2);
        return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
    }

    database.ref('navx_robot/location').on('value', (snapshot) => {
        const data = snapshot.val();
        const gpsBadge = document.getElementById('gps-status');

        let currentStatus = "";
        if (data && data.status) currentStatus = data.status.replace(/['"]+/g, ''); 

        if (data && currentStatus === "ON") {
            gpsBadge.innerText = "GPS ONLINE (TRACKING)";
            gpsBadge.className = "text-[8px] font-bold px-1.5 py-0.5 rounded bg-teal-100 text-teal-700 animate-pulse";
            
            if (document.getElementById('mode-select').value === 'auto') {
                let curLat = parseFloat(data.lat), curLng = parseFloat(data.lng);
                if (lastLat === null || lastLng === null) { lastLat = curLat; lastLng = curLng; return; }

                let dist = calculateGPSDistance(lastLat, lastLng, curLat, curLng);
                if (dist >= 0.2) {
                    let dY = (lastLat - curLat) * 111320; 
                    let dX = (curLng - lastLng) * 111320 * Math.cos(lastLat * Math.PI / 180);
                    rx += (dX * PIXELS_PER_METER); ry += (dY * PIXELS_PER_METER);
                    robotData.distance += dist;
                    robotData.path.push({x: rx, y: ry});
                    lastLat = curLat; lastLng = curLng;
                    updateUI(); markUnsaved();
                }
            }
        } else {
            gpsBadge.innerText = "GPS OFFLINE";
            gpsBadge.className = "text-[8px] font-bold px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 dark:bg-slate-700 dark:text-gray-400";
            lastLat = null; lastLng = null;
        }
    });

    // --- TAB & UI LOGIC ---
    function switchTab(tabId) {
        ['monitoring', 'riwayat', 'laporan', 'sensors', 'logbook'].forEach(id => {
            document.getElementById('tab-' + id).classList.remove('active');
            let btn = document.getElementById('btn-tab-' + id);
            btn.classList.remove('active', 'tab-btn');
            btn.classList.add('tab-btn', 'text-gray-600', 'dark:text-gray-400', 'hover:bg-gray-200', 'dark:hover:bg-slate-700');
        });
        document.getElementById('tab-' + tabId).classList.add('active');
        let activeBtn = document.getElementById('btn-tab-' + tabId);
        activeBtn.classList.remove('text-gray-600', 'dark:text-gray-400', 'hover:bg-gray-200', 'dark:hover:bg-slate-700');
        activeBtn.classList.add('active');
        if(tabId === 'monitoring') setTimeout(resizeAndDrawMap, 50);
        if(tabId === 'sensors') initSensorsTab();
        if(tabId === 'logbook') initLogbookTab();
    }

    const htmlTag = document.documentElement;
    if (localStorage.getItem('theme') === 'light') { htmlTag.classList.remove('dark'); document.getElementById('theme-icon').classList.replace('fa-moon', 'fa-sun'); }

    function toggleTheme() {
        htmlTag.classList.toggle('dark');
        let isDark = htmlTag.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.getElementById('theme-icon').className = isDark ? "fa-solid fa-moon" : "fa-solid fa-sun";
        drawMap();
    }

    // --- STATE DATA & AUTO-SAVE LOGIC ---
    let maxWater = 2000;
    // UPDATE PENTING: Sertakan ID ke dalam robotData.
    let robotData = { 
        id: <?= $initId ?>, 
        distance: <?= $initDistance ?>, 
        waterUsed: <?= $initWaterUsed ?>, 
        waterRemaining: maxWater - <?= $initWaterUsed ?>, 
        battery: <?= $initBattery ?>, 
        path: <?= $initPath ?>, 
        sprayPoints: <?= $initSpray ?> 
    };
    if(robotData.waterRemaining < 0) robotData.waterRemaining = 0;
    
    let isDataSaved = true;
    let idleSettingMs = 0;
    let idleTimerId = null;
    let saveCount = 0;
    const MAX_SAVES = 5;    

    function updateIdleSetting(showToast = false) {
        let val = document.getElementById('autosave-select').value;
        idleSettingMs = parseInt(val);
        if(showToast && idleSettingMs > 0) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: `Auto Save: ${idleSettingMs / 60000} Menit`, text: 'Akan aktif ketika ada data baru.', showConfirmButton: false, timer: 2000 });
        }
        resetIdleTimer();
    }

    function promptAutoSave() {
        Swal.fire({
            title: 'Waktu Idle Tercapai',
            text: 'Terdapat data sesi yang belum disimpan. Apakah Anda ingin menyimpannya sekarang?',
            icon: 'question', showCancelButton: true, confirmButtonColor: '#0f766e', cancelButtonColor: '#ef4444',
            confirmButtonText: '<i class="fa-solid fa-floppy-disk"></i> Ya, Simpan',
            cancelButtonText: '<i class="fa-solid fa-xmark"></i> Tidak',
            allowOutsideClick: false, allowEscapeKey: false,
            heightAuto: false // FIX LAYOUT
        }).then((result) => {
            if (result.isConfirmed) saveData(true); 
            else resetIdleTimer();
        });
    }

    function resetIdleTimer() {
        if (idleTimerId) { clearTimeout(idleTimerId); idleTimerId = null; }
        if (idleSettingMs > 0 && !isDataSaved) idleTimerId = setTimeout(promptAutoSave, idleSettingMs);
    }

    function markUnsaved() { 
        isDataSaved = false; 
        document.getElementById('btn-print').classList.add('opacity-50', 'cursor-not-allowed'); 
        document.getElementById('print-warning').style.display = 'block'; 
        resetIdleTimer(); 
    }

    function markSaved() { 
        isDataSaved = true; 
        document.getElementById('btn-print').classList.remove('opacity-50', 'cursor-not-allowed'); 
        document.getElementById('print-warning').style.display = 'none'; 
        if (idleTimerId) { clearTimeout(idleTimerId); idleTimerId = null; }
    }

    if(isDataSaved) markSaved();

    window.addEventListener('beforeunload', function (e) {
        if (!isDataSaved) { e.preventDefault(); e.returnValue = ''; return ''; }
    });

    // --- MAP RENDER & DRAWING ---
    const canvas = document.getElementById('minimap');
    const ctx = canvas.getContext('2d');
    let rx = 400, ry = 200, step = 10;
    if (robotData.path.length > 0) { let lp = robotData.path[robotData.path.length-1]; rx = lp.x; ry = lp.y; } else { robotData.path.push({x: rx, y: ry}); }

    function updateUI() {
        document.getElementById('val-distance').innerText = robotData.distance.toFixed(1);
        document.getElementById('val-water-used').innerText = robotData.waterUsed;
        document.getElementById('val-water-rem').innerText = robotData.waterRemaining;
        document.getElementById('lap-jarak').innerText = robotData.distance.toFixed(1) + " m";
        document.getElementById('lap-air').innerText = robotData.waterUsed + " ml";
        drawMap();
    }

    function resizeAndDrawMap() { canvas.width = canvas.parentElement.clientWidth; canvas.height = canvas.parentElement.clientHeight; drawMap(); }
    window.addEventListener('resize', resizeAndDrawMap);

    let isDrawingPath = false;
    let tempDrawPath = [];
    let pathInterval = null;

    function getWorldCoords(e) {
        let rect = canvas.getBoundingClientRect();
        let clientX = e.clientX; let clientY = e.clientY;
        if(e.touches && e.touches.length > 0) { clientX = e.touches[0].clientX; clientY = e.touches[0].clientY; }
        let x = clientX - rect.left; let y = clientY - rect.top;
        return { x: x + (rx - canvas.width / 2), y: y + (ry - canvas.height / 2) };
    }

    function startDrawing(e) {
        if(document.getElementById('mode-select').value === 'auto') return;
        isDrawingPath = true; tempDrawPath = [getWorldCoords(e)]; drawMap();
    }
    function drawMovement(e) {
        if(!isDrawingPath) return; e.preventDefault(); tempDrawPath.push(getWorldCoords(e)); drawMap();
    }
    function stopDrawing() {
        if(!isDrawingPath) return; isDrawingPath = false; executeDrawnPath();
    }

    canvas.addEventListener('mousedown', startDrawing); canvas.addEventListener('mousemove', drawMovement); canvas.addEventListener('mouseup', stopDrawing); canvas.addEventListener('mouseleave', stopDrawing);
    canvas.addEventListener('touchstart', startDrawing); canvas.addEventListener('touchmove', drawMovement); canvas.addEventListener('touchend', stopDrawing);

    function executeDrawnPath() {
        if(tempDrawPath.length === 0) return;
        if(pathInterval) clearInterval(pathInterval);
        
        let pIdx = 0;
        pathInterval = setInterval(() => {
            if(pIdx >= tempDrawPath.length) { clearInterval(pathInterval); tempDrawPath = []; drawMap(); return; }
            let target = tempDrawPath[pIdx];
            let dx = target.x - rx; let dy = target.y - ry;
            let distToTarget = Math.sqrt(dx*dx + dy*dy);
            
            if(distToTarget < 10) { pIdx += 3; } 
            else {
                rx += (dx / distToTarget) * 10; ry += (dy / distToTarget) * 10; 
                robotData.path.push({x: rx, y: ry});
                robotData.distance += 0.1; 
                updateUI(); markUnsaved();
            }
        }, 30);
    }

    function drawMap() {
        if(canvas.width === 0) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let isDark = htmlTag.classList.contains('dark');
        let camX = rx - canvas.width / 2, camY = ry - canvas.height / 2;

        ctx.save(); ctx.translate(-camX, -camY);

        ctx.strokeStyle = isDark ? '#2d3446' : '#e2e8f0'; ctx.lineWidth = 1;
        let sX = Math.floor(camX/40)*40, eX = camX+canvas.width+40;
        let sY = Math.floor(camY/40)*40, eY = camY+canvas.height+40;
        for(let i=sX; i<=eX; i+=40){ ctx.beginPath(); ctx.moveTo(i,sY); ctx.lineTo(i,eY); ctx.stroke(); }
        for(let i=sY; i<=eY; i+=40){ ctx.beginPath(); ctx.moveTo(sX,i); ctx.lineTo(eX,i); ctx.stroke(); }

        if (robotData.path.length > 1) {
            ctx.beginPath(); ctx.moveTo(robotData.path[0].x, robotData.path[0].y);
            for (let i = 1; i < robotData.path.length; i++) ctx.lineTo(robotData.path[i].x, robotData.path[i].y);
            ctx.strokeStyle = '#0d9488'; ctx.lineWidth = 4; ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.stroke();
        }

        if (tempDrawPath.length > 1) {
            ctx.beginPath(); ctx.moveTo(tempDrawPath[0].x, tempDrawPath[0].y);
            for (let i = 1; i < tempDrawPath.length; i++) ctx.lineTo(tempDrawPath[i].x, tempDrawPath[i].y);
            ctx.strokeStyle = '#f59e0b'; ctx.lineWidth = 2; ctx.setLineDash([5, 5]); ctx.stroke(); ctx.setLineDash([]);
        }

        if (robotData.sprayPoints) {
            ctx.fillStyle = 'rgba(59, 130, 246, 0.9)'; ctx.strokeStyle = 'white';
            for (let pt of robotData.sprayPoints) { ctx.beginPath(); ctx.arc(pt.x, pt.y, 6, 0, 2*Math.PI); ctx.fill(); ctx.stroke(); }
        }

        ctx.fillStyle = isDark ? '#ffffff' : '#1e293b'; ctx.shadowColor = '#0d9488'; ctx.shadowBlur = 10;
        ctx.fillRect(rx - 8, ry - 8, 16, 16); ctx.shadowBlur = 0;
        ctx.restore();
    }

    function moveRobot(dir) {
        if(document.getElementById('mode-select').value === 'auto') return Swal.fire({ icon: 'warning', text: 'Ubah ke Manual Mode untuk mengontrol manual.', heightAuto: false });
        if(pathInterval) clearInterval(pathInterval); 
        switch(dir) { case 'up': ry -= step; break; case 'down': ry += step; break; case 'left': rx -= step; break; case 'right': rx += step; break; }
        robotData.path.push({x: rx, y: ry}); robotData.distance += 0.15; updateUI(); markUnsaved();
    }

    function sprayWater() {
        if(robotData.waterRemaining >= 50) {
            robotData.waterUsed += 50; robotData.waterRemaining -= 50; robotData.sprayPoints.push({x: rx, y: ry});
            updateUI(); markUnsaved(); Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Air disemprotkan.', showConfirmButton: false, timer: 1000, heightAuto: false });
        } else Swal.fire({ icon: 'error', title: 'Tangki Kosong!', heightAuto: false });
    }

    // UPDATE PENTING: Update fungsi simpan agar bisa menangani pembuatan sesi baru
    function saveData(isAutoPrompt = false) {
        if (saveCount >= MAX_SAVES) {
            Swal.fire({ 
                icon: 'warning', 
                title: 'Batas Tercapai', 
                text: `Kamu sudah menyimpan ${MAX_SAVES} data pada sesi ini. Silakan klik "Sesi Baru" jika ingin mulai merekam log baru.`,
                heightAuto: false // FIX LAYOUT
            });
            return;
        }

        Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading(), allowOutsideClick: false, heightAuto: false });

        // Salin robotData dan paksa id = 0 agar selalu menjadi INSERT (Data Baru) di database backend
        let payload = { ...robotData };
        payload.id = 0; 

        fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                saveCount++; // Tambah jumlah simpanan
                markSaved(); 
                
                // Generate waktu saat ini untuk tabel riwayat
                let now = new Date();
                let y = now.getFullYear();
                let m = String(now.getMonth() + 1).padStart(2, '0');
                let d = String(now.getDate()).padStart(2, '0');
                let timeStr = now.toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'}) + ' - ' + 
                            now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});

                // Tambahkan data ke tabel riwayat secara instan (tanpa reload)
                let tr = document.createElement('tr');
                tr.className = "bg-white hover:bg-teal-50 dark:bg-[#232836] dark:hover:bg-slate-800 transition-colors";
                tr.setAttribute('data-year', y);
                tr.setAttribute('data-month', m);
                tr.setAttribute('data-day', d);
                tr.innerHTML = `
                    <td class="px-3 py-3 whitespace-nowrap font-semibold text-gray-800 dark:text-gray-200">${timeStr}</td>
                    <td class="px-3 py-3 whitespace-nowrap font-bold text-green-600 dark:text-green-400">${robotData.battery}%</td>
                    <td class="px-3 py-3 whitespace-nowrap font-bold text-teal-600 dark:text-teal-400">${robotData.distance.toFixed(1)}m</td>
                    <td class="px-3 py-3 whitespace-nowrap font-medium">${robotData.waterUsed}ml</td>
                    <td class="px-3 py-3 whitespace-nowrap font-bold text-cyan-600 dark:text-cyan-400">${robotData.waterRemaining}ml</td>
                `;
                let tbody = document.getElementById('history-table-body');
                
                // Hapus teks "Belum ada riwayat terekam" jika itu baris pertama
                if (tbody.querySelector('td[colspan="5"]')) tbody.innerHTML = ''; 
                tbody.insertBefore(tr, tbody.firstChild);

                if (isAutoPrompt) {
                    Swal.fire({ icon: 'success', title: 'Berhasil Disimpan!', text: `Log ke-${saveCount} dari ${MAX_SAVES}`, showConfirmButton: false, timer: 1500, heightAuto: false });
                } else {
                    Swal.fire({ icon: 'success', title: 'Tersimpan!', text: `Data log ke-${saveCount} berhasil ditambahkan.`, timer: 1500, showConfirmButton: false, heightAuto: false }); 
                }
            } else {
                Swal.fire({icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan di server', heightAuto: false});
            }
        }).catch(err => {
            Swal.fire({icon: 'error', title: 'Error!', text: 'Gagal koneksi ke server', heightAuto: false});
        });
    }

    // UPDATE PENTING: Reset sekarang akan menjadikan ini sesi baru!
    function resetData() {
        Swal.fire({ 
            title: 'Sesi Baru?', 
            text: `Peta navigasi akan di-reset dan kamu bisa mengirim hingga ${MAX_SAVES} data baru lagi.`, 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonText: 'Mulai Sesi Baru',
            heightAuto: false // FIX LAYOUT
        }).then(r => {
            if (r.isConfirmed) {
                robotData.id = 0; 
                robotData.distance = 0; 
                robotData.waterUsed = 0; 
                robotData.waterRemaining = maxWater; 
                rx = 400; ry = 200;
                robotData.path = [{x: rx, y: ry}]; 
                robotData.sprayPoints = []; 
                
                saveCount = 0; // Reset counter simpanan ke 0
                
                updateUI(); 
                markUnsaved();
            }
        });
    }

    document.addEventListener('keydown', e => {
        if(document.getElementById('tab-monitoring').classList.contains('active')) {
            if(['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)) e.preventDefault();
            if(e.key==='ArrowUp') moveRobot('up'); if(e.key==='ArrowDown') moveRobot('down');
            if(e.key==='ArrowLeft') moveRobot('left'); if(e.key==='ArrowRight') moveRobot('right');
        }
    });

    function filterTable() {
        let sy = document.getElementById('filter-year').value;
        let sm = document.getElementById('filter-month').value;
        let sd = document.getElementById('filter-day').value; // Ambil nilai hari

        let visibleCount = 0;

        document.querySelectorAll('#history-table-body tr[data-year]').forEach(row => {
            let ry = row.getAttribute('data-year');
            let rm = row.getAttribute('data-month');
            let rd = row.getAttribute('data-day');

            let matchYear = (sy === 'all' || sy === ry);
            let matchMonth = (sm === 'all' || sm === rm);
            let matchDay = (sd === 'all' || sd === rd);

            if (matchYear && matchMonth && matchDay) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    }

    function getFullMapBase64() {
        if (robotData.path.length === 0) return canvas.toDataURL("image/png");
        let minX = rx, maxX = rx, minY = ry, maxY = ry;
        for(let p of robotData.path) { minX=Math.min(minX,p.x); maxX=Math.max(maxX,p.x); minY=Math.min(minY,p.y); maxY=Math.max(maxY,p.y); }
        let pad = 60, w = Math.max((maxX-minX)+pad*2, 700), h = Math.max((maxY-minY)+pad*2, 350);
        let oc = document.createElement('canvas'); oc.width = w; oc.height = h; let octx = oc.getContext('2d');
        octx.fillStyle = '#f8fafc'; octx.fillRect(0,0,w,h); octx.strokeStyle = '#e5e7eb'; octx.lineWidth = 1;
        for(let i=0; i<=w; i+=40) { octx.beginPath(); octx.moveTo(i,0); octx.lineTo(i,h); octx.stroke(); }
        for(let i=0; i<=h; i+=40) { octx.beginPath(); octx.moveTo(0,i); octx.lineTo(w,i); octx.stroke(); }
        let cx = (w - (maxX-minX))/2 - minX, cy = (h - (maxY-minY))/2 - minY;
        octx.save(); octx.translate(cx, cy);
        if(robotData.path.length > 1) {
            octx.beginPath(); octx.moveTo(robotData.path[0].x, robotData.path[0].y);
            for (let i=1; i<robotData.path.length; i++) octx.lineTo(robotData.path[i].x, robotData.path[i].y);
            octx.strokeStyle = '#0d9488'; octx.lineWidth=4; octx.stroke();
        }
        if(robotData.sprayPoints) { octx.fillStyle='rgba(59,130,246,0.9)'; for(let p of robotData.sprayPoints){ octx.beginPath(); octx.arc(p.x,p.y,6,0,2*Math.PI); octx.fill(); } }
        octx.fillStyle = '#1e293b'; octx.fillRect(rx-10, ry-10, 20, 20); octx.restore();
        return oc.toDataURL("image/png");
    }

    function generatePDF() {
        if(!isDataSaved) return Swal.fire({ icon: 'warning', text: 'Klik Simpan Data dulu!', heightAuto: false });
        let pt = new Date(); document.getElementById('pdf-datetime').innerText = pt.toLocaleDateString('id-ID') + ' ' + pt.toLocaleTimeString('id-ID');
        document.getElementById('pdf-wrapper').style.display = 'block';
        let img = document.getElementById('pdf-map-image');
        Swal.fire({ title: 'Menyiapkan PDF...', didOpen: () => Swal.showLoading(), heightAuto: false });
        img.onload = () => {
            html2pdf().set({ margin: 0.4, filename: 'Laporan_NavX.pdf', jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' } })
            .from(document.getElementById('pdf-report-template')).save().then(() => {
                document.getElementById('pdf-wrapper').style.display = 'none'; Swal.fire({icon: 'success', title: 'Berhasil!', text: 'PDF di-download', heightAuto: false});
            });
        };
        img.src = getFullMapBase64();
    }

    updateIdleSetting(false);
    setInterval(() => { document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID'); }, 1000);
    getCameras(); initBatteryStatus(); setTimeout(resizeAndDrawMap, 100); updateUI();

    // --- SENSORS TAB FUNCTIONS ---
    let sensorsInitialized = false;
    let sensorSpeedHistory = [];
    let lastSensorPosX = 0, lastSensorPosY = 0, lastSensorPosZ = 0;
    let lastSensorUpdateTime = Date.now();

    function initSensorsTab() {
        if (sensorsInitialized) return;
        sensorsInitialized = true;
        
        // Listen to sensor data from Firebase
        database.ref('navx_robot/sensors').on('value', (snapshot) => {
            const data = snapshot.val();
            if (!data) return;
            
            // Liquid Level
            if (data.liquid_level !== undefined) {
                const level = parseFloat(data.liquid_level);
                const ml = (level / 100) * 2000;
                document.getElementById('sensor-liquid-level').innerText = level.toFixed(1) + '%';
                document.getElementById('sensor-liquid-ml').innerText = ml.toFixed(0) + ' ml';
            }
            
            // Motion Detection
            if (data.motion !== undefined) {
                const isMoving = data.motion === true || data.motion === 'true' || data.motion === 1;
                document.getElementById('sensor-motion-state').innerText = isMoving ? 'Moving' : 'Idle';
                const motionStatus = document.getElementById('sensor-motion-status');
                motionStatus.className = 'status-indicator ' + (isMoving ? 'status-online' : 'status-offline');
                document.getElementById('sensor-motion-time').innerText = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
            }
            
            // Position
            if (data.position) {
                const posX = parseFloat(data.position.x) || 0;
                const posY = parseFloat(data.position.y) || 0;
                const posZ = parseFloat(data.position.z) || 0;
                
                document.getElementById('sensor-pos-x').innerText = posX.toFixed(2);
                document.getElementById('sensor-pos-y').innerText = posY.toFixed(2);
                document.getElementById('sensor-pos-z').innerText = posZ.toFixed(2);
                
                // Calculate speed
                const now = Date.now();
                const timeDiff = (now - lastSensorUpdateTime) / 1000;
                
                if (timeDiff > 0 && (lastSensorPosX !== 0 || lastSensorPosY !== 0)) {
                    const dx = posX - lastSensorPosX;
                    const dy = posY - lastSensorPosY;
                    const distance = Math.sqrt(dx*dx + dy*dy);
                    const speed = distance / timeDiff;
                    
                    document.getElementById('sensor-speed-val').innerText = speed.toFixed(2);
                    sensorSpeedHistory.push(speed);
                    if (sensorSpeedHistory.length > 20) sensorSpeedHistory.shift();
                    
                    const avgSpeed = sensorSpeedHistory.reduce((a, b) => a + b, 0) / sensorSpeedHistory.length;
                    document.getElementById('sensor-speed-avg').innerText = avgSpeed.toFixed(2);
                }
                
                lastSensorPosX = posX;
                lastSensorPosY = posY;
                lastSensorPosZ = posZ;
                lastSensorUpdateTime = now;
            }
        });
        
        // Mode Monitor
        database.ref('navx_robot/mode').on('value', (snapshot) => {
            const mode = snapshot.val();
            if (mode) {
                document.getElementById('sensor-mode-active').innerText = mode.type || 'Manual';
                document.getElementById('sensor-mode-status').innerText = mode.status || 'Active';
            }
        });
        
        // Network Monitor
        checkSensorNetworkStatus();
        setInterval(checkSensorNetworkStatus, 5000);
    }

    function checkSensorNetworkStatus() {
        const online = navigator.onLine;
        document.getElementById('sensor-net-status').innerText = online ? 'Online' : 'Offline';
        document.getElementById('sensor-net-status').className = online ? 
            'text-sm font-black text-green-600 dark:text-green-400' : 
            'text-sm font-black text-red-600 dark:text-red-400';
        
        if (online) {
            const startTime = Date.now();
            fetch('https://www.google.com/favicon.ico', { mode: 'no-cors' })
                .then(() => {
                    const ping = Date.now() - startTime;
                    document.getElementById('sensor-net-ping').innerText = ping;
                    
                    let quality = 'Good';
                    if (ping > 200) quality = 'Fair';
                    if (ping > 500) quality = 'Poor';
                    document.getElementById('sensor-net-quality').innerText = quality;
                })
                .catch(() => {
                    document.getElementById('sensor-net-ping').innerText = '--';
                    document.getElementById('sensor-net-quality').innerText = 'Error';
                });
        } else {
            document.getElementById('sensor-net-ping').innerText = '--';
            document.getElementById('sensor-net-quality').innerText = 'Offline';
        }
    }

    // --- LOGBOOK TAB FUNCTIONS ---
    let logbookInitialized = false;
    let allLogbookLogs = [];
    
    const logbookIcons = {
        info: 'fa-circle-info',
        success: 'fa-circle-check',
        warning: 'fa-triangle-exclamation',
        error: 'fa-circle-xmark'
    };

    function initLogbookTab() {
        if (logbookInitialized) return;
        logbookInitialized = true;
        
        // Add initial sample logs
        addLogbookLog('info', 'Sistem dimulai', 'Logbook monitoring aktif');
        addLogbookLog('success', 'Koneksi Firebase berhasil', 'Real-time sync enabled');
        
        // Listen to robot events
        database.ref('navx_robot/events').on('value', (snapshot) => {
            const event = snapshot.val();
            if (!event) return;
            
            if (event.type === 'movement') {
                addLogbookLog('info', 'Robot bergerak', `Arah: ${event.direction || 'unknown'}`);
            } else if (event.type === 'spray') {
                addLogbookLog('success', 'Air disemprotkan', `Volume: ${event.volume || 50}ml`);
            } else if (event.type === 'battery_low') {
                addLogbookLog('warning', 'Baterai rendah', `Level: ${event.level || 0}%`);
            } else if (event.type === 'error') {
                addLogbookLog('error', 'Terjadi kesalahan', event.message || 'Unknown error');
            } else if (event.type === 'gps_connected') {
                addLogbookLog('success', 'GPS terhubung', 'Tracking aktif');
            } else if (event.type === 'gps_disconnected') {
                addLogbookLog('warning', 'GPS terputus', 'Tracking tidak aktif');
            }
        });
    }

    function addLogbookLog(type, message, details = '') {
        const timestamp = new Date();
        const log = {
            id: Date.now(),
            type: type,
            message: message,
            details: details,
            timestamp: timestamp.toISOString(),
            timeStr: timestamp.toLocaleString('id-ID')
        };
        
        allLogbookLogs.unshift(log);
        if (allLogbookLogs.length > 500) allLogbookLogs.pop();
        
        renderLogbookLogs();
        updateLogbookStats();
    }

    function renderLogbookLogs() {
        const container = document.getElementById('logbook-container');
        const filterType = document.getElementById('logbook-filter-type').value;
        
        const filteredLogs = filterType === 'all' ? allLogbookLogs : allLogbookLogs.filter(log => log.type === filterType);
        
        if (filteredLogs.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-gray-500 dark:text-gray-400 italic">Belum ada log aktivitas</div>';
            return;
        }
        
        container.innerHTML = filteredLogs.map(log => `
            <div class="log-entry log-${log.type} p-3 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 flex items-center justify-center rounded-lg badge-${log.type} shrink-0">
                        <i class="fa-solid ${logbookIcons[log.type]}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <div class="font-semibold text-sm text-gray-900 dark:text-white">${log.message}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">${log.timeStr}</div>
                        </div>
                        ${log.details ? `<div class="text-xs text-gray-600 dark:text-gray-400 mt-1">${log.details}</div>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    function updateLogbookStats() {
        document.getElementById('logbook-stat-total').innerText = allLogbookLogs.length;
        document.getElementById('logbook-stat-success').innerText = allLogbookLogs.filter(l => l.type === 'success').length;
        document.getElementById('logbook-stat-warning').innerText = allLogbookLogs.filter(l => l.type === 'warning').length;
        document.getElementById('logbook-stat-error').innerText = allLogbookLogs.filter(l => l.type === 'error').length;
    }

    function filterLogbookLogs() {
        renderLogbookLogs();
    }

    function clearLogbookLogs() {
        if (confirm('Hapus semua log?')) {
            allLogbookLogs = [];
            renderLogbookLogs();
            updateLogbookStats();
        }
    }

    function exportLogbookLogs() {
        if (allLogbookLogs.length === 0) {
            alert('Tidak ada log untuk di-export');
            return;
        }
        
        const csvContent = "data:text/csv;charset=utf-8," 
            + "Timestamp,Type,Message,Details\n"
            + allLogbookLogs.map(log => 
                `"${log.timeStr}","${log.type}","${log.message}","${log.details}"`
            ).join("\n");
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `logbook_${Date.now()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Logout confirmation
    function confirmLogout() {
        Swal.fire({
            title: 'Logout?',
            text: 'Apakah Anda yakin ingin keluar dari sistem?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fa-solid fa-right-from-bracket mr-2"></i>Ya, Logout',
            cancelButtonText: 'Batal',
            background: document.body.classList.contains('dark') ? '#1e293b' : '#ffffff',
            color: document.body.classList.contains('dark') ? '#e2e8f0' : '#1e293b'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }
</script>
</body>
</html>