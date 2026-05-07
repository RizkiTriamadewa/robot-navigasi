<?php
require 'db.php';

// Ambil data sensor terbaru
$query = $conn->query("SELECT * FROM daily_logs ORDER BY log_date DESC LIMIT 1");
$latestData = $query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sensor Monitor - NAV-X</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="https://img.icons8.com/fluency/48/navigation.png" type="image/png">
    
    <script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.1/firebase-database-compat.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
    </style>
</head>
<body class="h-screen w-screen overflow-hidden flex flex-col p-2 font-sans bg-gray-100 text-gray-800 dark:bg-[#1a1e29] dark:text-[#a0aec0]">

<div class="flex-none flex justify-between items-center panel p-3 rounded-xl bg-white border border-gray-200 shadow-lg mb-2 gap-3 h-16">
    <h1 class="text-base md:text-2xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-sky-600 to-cyan-600 flex items-center shrink-0">
        <i class="fa-solid fa-microchip text-blue-500 mr-3 text-2xl md:text-3xl drop-shadow-lg"></i> Sensor Monitor
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
            <div class="text-2xl font-black text-gray-900 dark:text-white" id="liquid-level">0%</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="liquid-ml">0 ml</div>
        </div>
        
        <!-- Motion Detection -->
        <div class="sensor-card panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
            <div class="flex justify-between items-start mb-3">
                <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400">
                    <i class="fa-solid fa-person-running text-2xl"></i>
                </div>
                <div class="status-indicator status-offline" id="motion-status"></div>
            </div>
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Motion Detection</h3>
            <div class="text-2xl font-black text-gray-900 dark:text-white" id="motion-state">Idle</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Last: <span id="motion-time">--:--</span></div>
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
                X: <span id="pos-x">0</span> | Y: <span id="pos-y">0</span>
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Z: <span id="pos-z">0</span> m</div>
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
            <div class="text-2xl font-black text-gray-900 dark:text-white"><span id="speed-val">0.0</span> m/s</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg: <span id="speed-avg">0.0</span> m/s</div>
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
                    <div class="text-lg font-black text-gray-900 dark:text-white" id="mode-active">Manual</div>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</div>
                    <div class="text-lg font-black text-teal-600 dark:text-teal-400" id="mode-status">Active</div>
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
                    <div class="text-sm font-black text-green-600 dark:text-green-400" id="net-status">Online</div>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ping</div>
                    <div class="text-sm font-black text-gray-900 dark:text-white"><span id="net-ping">--</span> ms</div>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Quality</div>
                    <div class="text-sm font-black text-gray-900 dark:text-white" id="net-quality">Good</div>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Chart Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        
        <!-- Speed Chart -->
        <div class="panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-orange-500"></i> Speed History
            </h3>
            <div class="h-48">
                <canvas id="speedChart"></canvas>
            </div>
        </div>
        
        <!-- Liquid Level Chart -->
        <div class="panel p-4 rounded-xl bg-white border border-gray-200 shadow-lg">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <i class="fa-solid fa-chart-area text-blue-500"></i> Liquid Level History
            </h3>
            <div class="h-48">
                <canvas id="liquidChart"></canvas>
            </div>
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
        updateChartTheme();
    }
    
    // Clock
    setInterval(() => { 
        document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID'); 
    }, 1000);
    
    // Chart Setup
    const isDarkMode = () => htmlTag.classList.contains('dark');
    const chartTextColor = () => isDarkMode() ? '#e5e7eb' : '#374151';
    const chartGridColor = () => isDarkMode() ? '#374151' : '#e5e7eb';
    
    const speedChartCtx = document.getElementById('speedChart').getContext('2d');
    const liquidChartCtx = document.getElementById('liquidChart').getContext('2d');
    
    let speedData = [];
    let liquidData = [];
    let timeLabels = [];
    
    const speedChart = new Chart(speedChartCtx, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Speed (m/s)',
                data: speedData,
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { color: chartTextColor() },
                    grid: { color: chartGridColor() }
                },
                x: { 
                    ticks: { color: chartTextColor() },
                    grid: { color: chartGridColor() }
                }
            }
        }
    });
    
    const liquidChart = new Chart(liquidChartCtx, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Liquid Level (%)',
                data: liquidData,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    max: 100,
                    ticks: { color: chartTextColor() },
                    grid: { color: chartGridColor() }
                },
                x: { 
                    ticks: { color: chartTextColor() },
                    grid: { color: chartGridColor() }
                }
            }
        }
    });
    
    function updateChartTheme() {
        speedChart.options.scales.y.ticks.color = chartTextColor();
        speedChart.options.scales.y.grid.color = chartGridColor();
        speedChart.options.scales.x.ticks.color = chartTextColor();
        speedChart.options.scales.x.grid.color = chartGridColor();
        speedChart.update();
        
        liquidChart.options.scales.y.ticks.color = chartTextColor();
        liquidChart.options.scales.y.grid.color = chartGridColor();
        liquidChart.options.scales.x.ticks.color = chartTextColor();
        liquidChart.options.scales.x.grid.color = chartGridColor();
        liquidChart.update();
    }
    
    // Sensor Data Variables
    let lastPosX = 0, lastPosY = 0, lastPosZ = 0;
    let lastUpdateTime = Date.now();
    let speedHistory = [];
    
    // Firebase Listeners
    database.ref('navx_robot/sensors').on('value', (snapshot) => {
        const data = snapshot.val();
        if (!data) return;
        
        // Liquid Level
        if (data.liquid_level !== undefined) {
            const level = parseFloat(data.liquid_level);
            const ml = (level / 100) * 2000;
            document.getElementById('liquid-level').innerText = level.toFixed(1) + '%';
            document.getElementById('liquid-ml').innerText = ml.toFixed(0) + ' ml';
        }
        
        // Motion Detection
        if (data.motion !== undefined) {
            const isMoving = data.motion === true || data.motion === 'true' || data.motion === 1;
            document.getElementById('motion-state').innerText = isMoving ? 'Moving' : 'Idle';
            const motionStatus = document.getElementById('motion-status');
            motionStatus.className = 'status-indicator ' + (isMoving ? 'status-online' : 'status-offline');
            document.getElementById('motion-time').innerText = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
        }
        
        // Position
        if (data.position) {
            const posX = parseFloat(data.position.x) || 0;
            const posY = parseFloat(data.position.y) || 0;
            const posZ = parseFloat(data.position.z) || 0;
            
            document.getElementById('pos-x').innerText = posX.toFixed(2);
            document.getElementById('pos-y').innerText = posY.toFixed(2);
            document.getElementById('pos-z').innerText = posZ.toFixed(2);
            
            // Calculate speed
            const now = Date.now();
            const timeDiff = (now - lastUpdateTime) / 1000; // seconds
            
            if (timeDiff > 0 && (lastPosX !== 0 || lastPosY !== 0)) {
                const dx = posX - lastPosX;
                const dy = posY - lastPosY;
                const distance = Math.sqrt(dx*dx + dy*dy);
                const speed = distance / timeDiff;
                
                document.getElementById('speed-val').innerText = speed.toFixed(2);
                speedHistory.push(speed);
                if (speedHistory.length > 20) speedHistory.shift();
                
                const avgSpeed = speedHistory.reduce((a, b) => a + b, 0) / speedHistory.length;
                document.getElementById('speed-avg').innerText = avgSpeed.toFixed(2);
            }
            
            lastPosX = posX;
            lastPosY = posY;
            lastPosZ = posZ;
            lastUpdateTime = now;
        }
        
        // Update Charts
        updateCharts();
    });
    
    // Mode Monitor
    database.ref('navx_robot/mode').on('value', (snapshot) => {
        const mode = snapshot.val();
        if (mode) {
            document.getElementById('mode-active').innerText = mode.type || 'Manual';
            document.getElementById('mode-status').innerText = mode.status || 'Active';
        }
    });
    
    // Network Monitor
    function checkNetworkStatus() {
        const online = navigator.onLine;
        document.getElementById('net-status').innerText = online ? 'Online' : 'Offline';
        document.getElementById('net-status').className = online ? 
            'text-sm font-black text-green-600 dark:text-green-400' : 
            'text-sm font-black text-red-600 dark:text-red-400';
        
        if (online) {
            const startTime = Date.now();
            fetch('https://www.google.com/favicon.ico', { mode: 'no-cors' })
                .then(() => {
                    const ping = Date.now() - startTime;
                    document.getElementById('net-ping').innerText = ping;
                    
                    let quality = 'Good';
                    if (ping > 200) quality = 'Fair';
                    if (ping > 500) quality = 'Poor';
                    document.getElementById('net-quality').innerText = quality;
                })
                .catch(() => {
                    document.getElementById('net-ping').innerText = '--';
                    document.getElementById('net-quality').innerText = 'Error';
                });
        } else {
            document.getElementById('net-ping').innerText = '--';
            document.getElementById('net-quality').innerText = 'Offline';
        }
    }
    
    checkNetworkStatus();
    setInterval(checkNetworkStatus, 5000);
    
    window.addEventListener('online', checkNetworkStatus);
    window.addEventListener('offline', checkNetworkStatus);
    
    // Update Charts
    function updateCharts() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
        
        timeLabels.push(timeStr);
        if (timeLabels.length > 20) timeLabels.shift();
        
        const currentSpeed = parseFloat(document.getElementById('speed-val').innerText) || 0;
        speedData.push(currentSpeed);
        if (speedData.length > 20) speedData.shift();
        
        const currentLiquid = parseFloat(document.getElementById('liquid-level').innerText) || 0;
        liquidData.push(currentLiquid);
        if (liquidData.length > 20) liquidData.shift();
        
        speedChart.update();
        liquidChart.update();
    }
    
    setInterval(updateCharts, 2000);
</script>

</body>
</html>
