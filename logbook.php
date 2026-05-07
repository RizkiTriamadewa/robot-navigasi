<?php
require 'db.php';

// Ambil semua log aktivitas
$query = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 500");
$logs = [];
while($row = $query->fetch_assoc()) {
    $logs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Logbook - NAV-X</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://img.icons8.com/fluency/48/navigation.png" type="image/png">
    
    <script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-database-compat.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
        
        .live-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            50% { opacity: 0.8; box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden flex flex-col p-2 font-sans bg-gray-100 text-gray-800 dark:bg-[#1a1e29] dark:text-[#a0aec0]">

<div class="flex-none flex justify-between items-center panel p-3 rounded-xl bg-white border border-gray-200 shadow-lg mb-2 gap-3 h-16">
    <h1 class="text-base md:text-2xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-sky-600 to-cyan-600 flex items-center shrink-0">
        <i class="fa-solid fa-book text-blue-500 mr-3 text-2xl md:text-3xl drop-shadow-lg"></i> Logbook
    </h1>
    
    <div class="flex items-center space-x-3 shrink-0">
        <a href="index.php" class="px-4 py-2 rounded-lg bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-bold text-xs transition-all shadow-md hover:shadow-lg flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
        <div class="text-[10px] md:text-xs hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gradient-to-r from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 border border-teal-200 dark:border-teal-800">
            <i class="fa-solid fa-clock text-teal-500"></i>
            <span id="clock" class="text-teal-600 dark:text-teal-400 font-mono font-bold">00:00:00</span>
        </div>
        <button onclick="toggleTheme()" class="p-2.5 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 text-gray-700 hover:from-gray-200 hover:to-gray-300 dark:from-slate-700 dark:to-slate-800 dark:text-yellow-400 dark:hover:from-slate-600 dark:hover:to-slate-700 transition-all shadow-md hover:shadow-lg">
            <i id="theme-icon" class="fa-solid fa-moon text-lg"></i>
        </button>
    </div>
</div>

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
            <select id="filter-type" onchange="filterLogs()" class="bg-gradient-to-r from-gray-50 to-gray-100 text-gray-700 text-xs pl-3 pr-8 py-2 rounded-lg border border-gray-300 dark:from-slate-700 dark:to-slate-800 dark:border-slate-600 dark:text-white font-semibold shadow-sm cursor-pointer outline-none">
                <option value="all">Semua Tipe</option>
                <option value="info">Info</option>
                <option value="success">Success</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
            </select>
            
            <button onclick="clearLogs()" class="px-4 py-2 rounded-lg bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold text-xs transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-trash"></i> Clear Logs
            </button>
            
            <button onclick="exportLogs()" class="px-4 py-2 rounded-lg bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold text-xs transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                <i class="fa-solid fa-download"></i> Export
            </button>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="flex-none grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold mb-1">Total Logs</div>
            <div class="text-2xl font-black text-blue-700 dark:text-blue-300" id="stat-total">0</div>
        </div>
        <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
            <div class="text-xs text-green-600 dark:text-green-400 font-semibold mb-1">Success</div>
            <div class="text-2xl font-black text-green-700 dark:text-green-300" id="stat-success">0</div>
        </div>
        <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
            <div class="text-xs text-yellow-600 dark:text-yellow-400 font-semibold mb-1">Warnings</div>
            <div class="text-2xl font-black text-yellow-700 dark:text-yellow-300" id="stat-warning">0</div>
        </div>
        <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <div class="text-xs text-red-600 dark:text-red-400 font-semibold mb-1">Errors</div>
            <div class="text-2xl font-black text-red-700 dark:text-red-300" id="stat-error">0</div>
        </div>
    </div>
    
    <!-- Log Entries -->
    <div class="flex-1 overflow-y-auto space-y-2 bg-gray-50 dark:bg-slate-800/50 rounded-lg p-3 border border-gray-200 dark:border-slate-700">
        <div id="log-container">
            <!-- Logs will be inserted here -->
        </div>
    </div>
    
</div>

<script>
    // Firebase Init
    const firebaseConfig = { databaseURL: "https://nav-track-36e9f-default-rtdb.firebaseio.com" };
    firebase.initializeApp(firebaseConfig);
    const database = firebase.database();
    
    // Theme Toggle
    const htmlTag = document.documentElement;
    if (localStorage.getItem('theme') === 'light') { 
        htmlTag.classList.remove('dark'); 
        document.getElementById('theme-icon').classList.replace('fa-moon', 'fa-sun'); 
    }
    
    function toggleTheme() {
        htmlTag.classList.toggle('dark');
        let isDark = htmlTag.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.getElementById('theme-icon').className = isDark ? "fa-solid fa-moon" : "fa-solid fa-sun";
    }
    
    // Clock
    setInterval(() => { 
        document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID'); 
    }, 1000);
    
    // Log Management
    let allLogs = [];
    
    const logIcons = {
        info: 'fa-circle-info',
        success: 'fa-circle-check',
        warning: 'fa-triangle-exclamation',
        error: 'fa-circle-xmark'
    };
    
    function addLog(type, message, details = '') {
        const timestamp = new Date();
        const log = {
            id: Date.now(),
            type: type,
            message: message,
            details: details,
            timestamp: timestamp.toISOString(),
            timeStr: timestamp.toLocaleString('id-ID')
        };
        
        allLogs.unshift(log);
        if (allLogs.length > 500) allLogs.pop();
        
        renderLogs();
        updateStats();
        
        // Save to Firebase
        database.ref('navx_robot/logs').push(log);
    }
    
    function renderLogs() {
        const container = document.getElementById('log-container');
        const filterType = document.getElementById('filter-type').value;
        
        const filteredLogs = filterType === 'all' ? allLogs : allLogs.filter(log => log.type === filterType);
        
        if (filteredLogs.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-gray-500 dark:text-gray-400 italic">Belum ada log aktivitas</div>';
            return;
        }
        
        container.innerHTML = filteredLogs.map(log => `
            <div class="log-entry log-${log.type} p-3 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 flex items-center justify-center rounded-lg badge-${log.type} shrink-0">
                        <i class="fa-solid ${logIcons[log.type]}"></i>
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
    
    function updateStats() {
        document.getElementById('stat-total').innerText = allLogs.length;
        document.getElementById('stat-success').innerText = allLogs.filter(l => l.type === 'success').length;
        document.getElementById('stat-warning').innerText = allLogs.filter(l => l.type === 'warning').length;
        document.getElementById('stat-error').innerText = allLogs.filter(l => l.type === 'error').length;
    }
    
    function filterLogs() {
        renderLogs();
    }
    
    function clearLogs() {
        Swal.fire({
            title: 'Hapus Semua Log?',
            text: 'Tindakan ini tidak dapat dibatalkan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            heightAuto: false
        }).then((result) => {
            if (result.isConfirmed) {
                allLogs = [];
                renderLogs();
                updateStats();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Semua log telah dihapus',
                    timer: 1500,
                    showConfirmButton: false,
                    heightAuto: false
                });
            }
        });
    }
    
    function exportLogs() {
        if (allLogs.length === 0) {
            return Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Data',
                text: 'Belum ada log untuk di-export',
                heightAuto: false
            });
        }
        
        const csvContent = "data:text/csv;charset=utf-8," 
            + "Timestamp,Type,Message,Details\n"
            + allLogs.map(log => 
                `"${log.timeStr}","${log.type}","${log.message}","${log.details}"`
            ).join("\n");
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `logbook_${Date.now()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Log berhasil di-export',
            timer: 1500,
            showConfirmButton: false,
            heightAuto: false
        });
    }
    
    // Firebase Listener for Real-time Logs
    database.ref('navx_robot/logs').limitToLast(100).on('child_added', (snapshot) => {
        const log = snapshot.val();
        if (log && !allLogs.find(l => l.id === log.id)) {
            allLogs.unshift(log);
            if (allLogs.length > 500) allLogs.pop();
            renderLogs();
            updateStats();
        }
    });
    
    // Monitor Robot Events
    database.ref('navx_robot/events').on('value', (snapshot) => {
        const event = snapshot.val();
        if (!event) return;
        
        if (event.type === 'movement') {
            addLog('info', 'Robot bergerak', `Arah: ${event.direction || 'unknown'}`);
        } else if (event.type === 'spray') {
            addLog('success', 'Air disemprotkan', `Volume: ${event.volume || 50}ml`);
        } else if (event.type === 'battery_low') {
            addLog('warning', 'Baterai rendah', `Level: ${event.level || 0}%`);
        } else if (event.type === 'error') {
            addLog('error', 'Terjadi kesalahan', event.message || 'Unknown error');
        } else if (event.type === 'gps_connected') {
            addLog('success', 'GPS terhubung', 'Tracking aktif');
        } else if (event.type === 'gps_disconnected') {
            addLog('warning', 'GPS terputus', 'Tracking tidak aktif');
        }
    });
    
    // Initial sample logs
    addLog('info', 'Sistem dimulai', 'Logbook monitoring aktif');
    addLog('success', 'Koneksi Firebase berhasil', 'Real-time sync enabled');
    
    renderLogs();
    updateStats();
</script>

</body>
</html>
