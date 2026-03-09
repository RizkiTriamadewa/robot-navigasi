<?php
require 'db.php';
// Ambil data hari ini
$date = date('Y-m-d');
$query = $conn->query("SELECT * FROM daily_logs WHERE log_date = '$date'");
$todayData = $query->fetch_assoc();

$initDistance = $todayData ? $todayData['distance_m'] : 0;
$initWaterUsed = $todayData ? $todayData['water_used_ml'] : 0;
$initPath = $todayData && $todayData['path_data'] ? $todayData['path_data'] : "[]";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Robot NAV-X Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        body { transition: background-color 0.3s, color 0.3s; }
        .panel { transition: background-color 0.3s, border-color 0.3s; }
        .btn-control:active { transform: scale(0.95); }
        
        /* Custom Scroll & Load Animation Setup */
        .reveal { 
            opacity: 0; 
            transform: translateY(30px); 
            transition: all 0.8s cubic-bezier(0.25, 1, 0.5, 1); 
        }
        .reveal.active { 
            opacity: 1; 
            transform: translateY(0); 
        }
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
        
        /* Custom Select Styling */
        .custom-select-wrapper { position: relative; display: inline-block; }
        .custom-select {
            appearance: none; -webkit-appearance: none; -moz-appearance: none;
            padding-right: 2.5rem;
        }
        .custom-select-arrow {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            pointer-events: none; color: #6b7280;
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8 font-sans bg-gray-100 text-gray-800 dark:bg-[#1a1e29] dark:text-[#a0aec0]">

<div class="max-w-7xl mx-auto space-y-6" id="dashboard-content">
    
    <div class="reveal active flex flex-wrap justify-between items-center panel p-4 rounded-xl bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446] dark:shadow-[0_4px_6px_rgba(0,0,0,0.3)]">
        <h1 class="text-xl font-bold tracking-widest text-gray-900 dark:text-white flex items-center">
            <i class="fa-solid fa-robot text-teal-500 mr-3 text-2xl"></i> NAV-X MONITORING
        </h1>
        <div class="flex items-center space-x-4">
            <div class="text-sm hidden sm:block text-gray-600 dark:text-gray-300">
                <span><i class="fa-solid fa-calendar mr-1"></i> <span id="header-date"></span></span>
                <span id="clock" class="text-teal-500 font-mono ml-2 font-bold">00:00:00</span>
            </div>
            <button onclick="toggleTheme()" class="p-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-[#2a3040] dark:text-yellow-400 dark:hover:bg-[#3b4256] transition shadow-inner">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="reveal delay-100 lg:col-span-2 panel rounded-xl p-4 flex flex-col bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-widest uppercase">Map & Tracking (Auto-Follow Camera)</h2>
                <button onclick="resetData()" class="text-xs bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/20 px-3 py-1.5 rounded-lg transition shadow-sm">
                    <i class="fa-solid fa-rotate-right mr-1"></i> Reset Map
                </button>
            </div>
            <div class="relative flex-grow bg-[#f8fafc] dark:bg-slate-800 rounded-xl overflow-hidden border border-gray-200 dark:border-slate-700 h-80 lg:h-auto flex items-center justify-center shadow-inner">
                <canvas id="minimap" width="800" height="400" class="w-full h-full"></canvas>
            </div>
        </div>

        <div class="reveal delay-200 panel rounded-xl p-4 flex flex-col justify-between bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-widest uppercase">Controls</h2>
                    <div class="custom-select-wrapper shadow-sm rounded-lg">
                        <select id="mode-select" class="custom-select bg-gray-50 border border-gray-200 text-gray-700 font-medium text-sm px-4 py-2 rounded-lg outline-none cursor-pointer hover:bg-gray-100 focus:border-teal-400 focus:ring-2 focus:ring-teal-100 transition dark:bg-slate-700 dark:border-slate-600 dark:text-white dark:hover:bg-slate-600 dark:focus:ring-teal-900">
                            <option value="manual">Manual Mode</option>
                            <option value="auto">Auto Mode</option>
                        </select>
                        <i class="fa-solid fa-chevron-down custom-select-arrow dark:text-gray-300"></i>
                    </div>
                </div>

                <div class="mt-8 mb-6 p-6 rounded-2xl bg-[#f8fafc] border border-gray-100 shadow-inner flex flex-col items-center justify-center space-y-3 dark:bg-[#1a1e29]/50 dark:border-slate-700/50">
                    <button onclick="moveRobot('up')" class="btn-control w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256] dark:hover:bg-[#3b4256]"><i class="fa-solid fa-chevron-up"></i></button>
                    <div class="flex space-x-3">
                        <button onclick="moveRobot('left')" class="btn-control w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256] dark:hover:bg-[#3b4256]"><i class="fa-solid fa-chevron-left"></i></button>
                        <button onclick="moveRobot('down')" class="btn-control w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256] dark:hover:bg-[#3b4256]"><i class="fa-solid fa-chevron-down"></i></button>
                        <button onclick="moveRobot('right')" class="btn-control w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256] dark:hover:bg-[#3b4256]"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
            
            <div class="space-y-3">
                <button onclick="sprayWater()" class="w-full bg-teal-600 hover:bg-teal-500 text-white font-bold py-3 rounded-xl shadow-[0_4px_10px_rgba(13,148,136,0.3)] transition-all">
                    <i class="fa-solid fa-droplet mr-2"></i> Semprot Air (50ml)
                </button>
                <div class="grid grid-cols-2 gap-3">
                    <button onclick="saveData()" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-xl shadow-[0_4px_10px_rgba(22,163,74,0.3)] transition-all">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan
                    </button>
                    <button onclick="generatePDF()" id="btn-print" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl transition-all shadow-[0_4px_10px_rgba(37,99,235,0.3)] opacity-50 cursor-not-allowed">
                        <i class="fa-solid fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="reveal delay-100 panel p-5 rounded-2xl flex flex-col md:flex-row items-center md:items-start text-center md:text-left space-y-3 md:space-y-0 md:space-x-4 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-14 h-14 flex items-center justify-center rounded-2xl bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 shrink-0">
                <i class="fa-solid fa-battery-three-quarters text-2xl"></i>
            </div>
            <div class="flex-grow">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Battery</div>
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white" id="val-battery">100%</div>
            </div>
        </div>
        <div class="reveal delay-200 panel p-5 rounded-2xl flex flex-col md:flex-row items-center md:items-start text-center md:text-left space-y-3 md:space-y-0 md:space-x-4 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-14 h-14 flex items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shrink-0">
                <i class="fa-solid fa-route text-2xl"></i>
            </div>
            <div class="flex-grow">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Jarak Tempuh</div>
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white"><span id="val-distance">0</span> m</div>
            </div>
        </div>
        <div class="reveal delay-300 panel p-5 rounded-2xl flex flex-col md:flex-row items-center md:items-start text-center md:text-left space-y-3 md:space-y-0 md:space-x-4 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-14 h-14 flex items-center justify-center rounded-2xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400 shrink-0">
                <i class="fa-solid fa-faucet-drip text-2xl"></i>
            </div>
            <div class="flex-grow">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Air Keluar (Hari ini)</div>
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white"><span id="val-water-used">0</span> ml</div>
            </div>
        </div>
        <div class="reveal delay-300 panel p-5 rounded-2xl flex flex-col md:flex-row items-center md:items-start text-center md:text-left space-y-3 md:space-y-0 md:space-x-4 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-14 h-14 flex items-center justify-center rounded-2xl bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400 shrink-0">
                <i class="fa-solid fa-prescription-bottle text-2xl"></i>
            </div>
            <div class="flex-grow">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Sisa Tangki Air</div>
                <div class="text-2xl font-extrabold text-gray-900 dark:text-white"><span id="val-water-rem">2000</span> ml</div>
            </div>
        </div>
    </div>

    <div class="reveal panel p-6 rounded-2xl bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446] mt-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 md:mb-0">
                <i class="fa-solid fa-clock-rotate-left mr-2 text-teal-500"></i> Riwayat Progres Harian
            </h2>
            
            <div class="flex space-x-3">
                <div class="custom-select-wrapper border border-gray-300 rounded-lg dark:border-slate-600">
                    <select id="filter-year" onchange="filterTable()" class="custom-select bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg outline-none cursor-pointer dark:bg-slate-700 dark:text-white">
                        <option value="all">Semua Tahun</option>
                        <?php foreach($availableYears as $y): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down custom-select-arrow dark:text-gray-300 text-xs"></i>
                </div>
                
                <div class="custom-select-wrapper border border-gray-300 rounded-lg dark:border-slate-600">
                    <select id="filter-month" onchange="filterTable()" class="custom-select bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg outline-none cursor-pointer dark:bg-slate-700 dark:text-white">
                        <option value="all">Semua Bulan</option>
                        <option value="01">Januari</option>
                        <option value="02">Februari</option>
                        <option value="03">Maret</option>
                        <option value="04">April</option>
                        <option value="05">Mei</option>
                        <option value="06">Juni</option>
                        <option value="07">Juli</option>
                        <option value="08">Agustus</option>
                        <option value="09">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                    <i class="fa-solid fa-chevron-down custom-select-arrow dark:text-gray-300 text-xs"></i>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-700">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-slate-800 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-4 rounded-tl-xl">Tanggal</th>
                        <th class="px-6 py-4">Total Jarak (m)</th>
                        <th class="px-6 py-4">Air Dikeluarkan (ml)</th>
                        <th class="px-6 py-4 rounded-tr-xl">Sisa Tangki Air (ml)</th>
                    </tr>
                </thead>
                <tbody id="history-table-body" class="divide-y divide-gray-200 dark:divide-slate-700">
                    <?php if(empty($historyData)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada data riwayat yang tersimpan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($historyData as $row): 
                            $time = strtotime($row['log_date']);
                            $year = date('Y', $time);
                            $month = date('m', $time);
                            $sisaAir = 2000 - $row['water_used_ml'];
                        ?>
                        <tr class="bg-white hover:bg-gray-50 dark:bg-[#232836] dark:hover:bg-slate-800 transition-colors" data-year="<?= $year ?>" data-month="<?= $month ?>">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                <?= date('d F Y', $time) ?>
                            </td>
                            <td class="px-6 py-4"><?= number_format($row['distance_m'], 1) ?></td>
                            <td class="px-6 py-4"><span class="px-2 py-1 bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400 rounded-md"><?= $row['water_used_ml'] ?></span></td>
                            <td class="px-6 py-4"><span class="px-2 py-1 bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400 rounded-md"><?= max(0, $sisaAir) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div id="pdf-wrapper" style="display: none; position: absolute; top: 0; left: 0; width: 100%; z-index: 9999; background: white; padding: 10px;">
    <div id="pdf-report-template" class="mx-auto w-[700px] bg-white text-black p-8 font-sans border border-gray-200">
        <div class="border-b-2 border-gray-800 pb-4 mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold uppercase"><i class="fa-solid fa-robot mr-2"></i> Report NAV-X Bot</h1>
                <p class="text-sm text-gray-600">Laporan Rekapitulasi Data Robot Monitoring</p>
            </div>
            <div class="text-right">
                <p class="font-bold">Dicetak Pada:</p>
                <p id="pdf-datetime" class="text-sm"><?= date('d F Y') ?></p>
            </div>
        </div>
        
        <h3 class="font-bold text-lg mb-3">Tabel Indikator Harian</h3>
        <table class="w-full border-collapse border border-gray-400 mb-8 text-sm text-left table-fixed">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-gray-400 p-2 w-1/4">Baterai Tersisa</th>
                    <th class="border border-gray-400 p-2 w-1/4">Total Jarak Tempuh</th>
                    <th class="border border-gray-400 p-2 w-1/4">Air yang Dikeluarkan</th>
                    <th class="border border-gray-400 p-2 w-1/4">Sisa Air di Tangki</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-gray-400 p-2" id="pdf-battery">100%</td>
                    <td class="border border-gray-400 p-2"><span id="pdf-distance">0</span> Meter</td>
                    <td class="border border-gray-400 p-2"><span id="pdf-water-used">0</span> ml</td>
                    <td class="border border-gray-400 p-2"><span id="pdf-water-rem">2000</span> ml</td>
                </tr>
            </tbody>
        </table>

        <h3 class="font-bold text-lg mb-3">Peta Jalur Robot Keseluruhan (Full Track Map)</h3>
        <div class="border border-gray-400 p-2 flex justify-center bg-gray-50 rounded">
            <img id="pdf-map-image" src="" alt="Map Track" style="max-width: 100%; height: auto;">
        </div>
    </div>
</div>

<script>
    // --- ANIMASI SCROLL (INTERSECTION OBSERVER) ---
    document.addEventListener("DOMContentLoaded", function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach((el) => {
            observer.observe(el);
        });
    });

    // --- SETUP TANGGAL ---
    const nowInit = new Date();
    document.getElementById('header-date').innerText = nowInit.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

    // --- DARK MODE LOGIC ---
    const htmlTag = document.documentElement;
    const themeIcon = document.getElementById('theme-icon');
    
    if (localStorage.getItem('theme') === 'light') {
        htmlTag.classList.remove('dark');
        themeIcon.classList.replace('fa-moon', 'fa-sun');
    }

    function toggleTheme() {
        if (htmlTag.classList.contains('dark')) {
            htmlTag.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        } else {
            htmlTag.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            themeIcon.classList.replace('fa-sun', 'fa-moon');
        }
        drawMap();
    }

    // --- INISIALISASI DATA ---
    let maxWater = 2000;
    let robotData = {
        distance: <?= $initDistance ?>,
        waterUsed: <?= $initWaterUsed ?>,
        waterRemaining: maxWater - <?= $initWaterUsed ?>,
        battery: 100,
        path: <?= $initPath ?> 
    };

    if(robotData.waterRemaining < 0) robotData.waterRemaining = 0;

    let isDataSaved = true; 
    const btnPrint = document.getElementById('btn-print');

    function markUnsaved() {
        isDataSaved = false;
        btnPrint.classList.add('opacity-50', 'cursor-not-allowed');
        btnPrint.classList.remove('hover:bg-blue-500', 'shadow-[0_4px_10px_rgba(37,99,235,0.3)]');
    }

    function markSaved() {
        isDataSaved = true;
        btnPrint.classList.remove('opacity-50', 'cursor-not-allowed');
        btnPrint.classList.add('hover:bg-blue-500', 'shadow-[0_4px_10px_rgba(37,99,235,0.3)]');
    }

    if(isDataSaved) markSaved();

    // --- SETUP CANVAS MAP ---
    const canvas = document.getElementById('minimap');
    const ctx = canvas.getContext('2d');
    
    let rx = 400; let ry = 200; let step = 10;

    if (robotData.path.length > 0) {
        let lastPos = robotData.path[robotData.path.length - 1];
        rx = lastPos.x; ry = lastPos.y;
    } else {
        robotData.path.push({x: rx, y: ry});
    }

    function updateUI() {
        document.getElementById('val-distance').innerText = robotData.distance.toFixed(1);
        document.getElementById('val-water-used').innerText = robotData.waterUsed;
        document.getElementById('val-water-rem').innerText = robotData.waterRemaining;
        document.getElementById('val-battery').innerText = robotData.battery.toFixed(1) + "%";
        drawMap();
    }

    function drawMap() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let isDark = htmlTag.classList.contains('dark');
        
        let camX = rx - canvas.width / 2;
        let camY = ry - canvas.height / 2;

        ctx.save();
        ctx.translate(-camX, -camY);

        ctx.strokeStyle = isDark ? '#2d3446' : '#e2e8f0';
        ctx.lineWidth = 1;

        let startX = Math.floor(camX / 40) * 40;
        let endX = camX + canvas.width + 40;
        let startY = Math.floor(camY / 40) * 40;
        let endY = camY + canvas.height + 40;

        for(let i = startX; i <= endX; i += 40) { ctx.beginPath(); ctx.moveTo(i, startY); ctx.lineTo(i, endY); ctx.stroke(); }
        for(let i = startY; i <= endY; i += 40) { ctx.beginPath(); ctx.moveTo(startX, i); ctx.lineTo(endX, i); ctx.stroke(); }

        if (robotData.path.length > 1) {
            ctx.beginPath();
            ctx.moveTo(robotData.path[0].x, robotData.path[0].y);
            for (let i = 1; i < robotData.path.length; i++) {
                ctx.lineTo(robotData.path[i].x, robotData.path[i].y);
            }
            ctx.strokeStyle = '#0d9488'; // Teal lebih pekat
            ctx.lineWidth = 4;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            ctx.stroke();
        }

        ctx.fillStyle = isDark ? '#ffffff' : '#1e293b';
        ctx.shadowColor = '#0d9488';
        ctx.shadowBlur = 10;
        ctx.fillRect(rx - 10, ry - 10, 20, 20);
        ctx.shadowBlur = 0;

        ctx.restore();
    }

    // --- ACTIONS ---
    function moveRobot(direction) {
        if(document.getElementById('mode-select').value === 'auto') {
            Swal.fire({ icon: 'warning', title: 'Mode Auto Aktif', text: 'Ubah ke Manual Mode untuk menggunakan kontrol arah.' });
            return;
        }
        
        switch(direction) {
            case 'up': ry -= step; break;
            case 'down': ry += step; break;
            case 'left': rx -= step; break;
            case 'right': rx += step; break;
        }

        robotData.path.push({x: rx, y: ry});
        robotData.distance += 0.5;
        if(robotData.battery > 0) robotData.battery -= 0.1;

        updateUI();
        markUnsaved();
    }

    function sprayWater() {
        if(robotData.waterRemaining >= 50) {
            robotData.waterUsed += 50;
            robotData.waterRemaining -= 50;
            updateUI();
            
            let camX = rx - canvas.width / 2;
            let camY = ry - canvas.height / 2;
            ctx.save();
            ctx.translate(-camX, -camY);
            ctx.beginPath();
            ctx.arc(rx, ry, 25, 0, 2 * Math.PI);
            ctx.fillStyle = 'rgba(20, 184, 166, 0.5)';
            ctx.fill();
            ctx.restore();
            
            setTimeout(drawMap, 300);

            Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Air disemprotkan. Jangan lupa Simpan Data.', showConfirmButton: false, timer: 2000 });
            markUnsaved(); 
        } else {
            Swal.fire({ icon: 'error', title: 'Tangki Kosong!', text: 'Sisa air tidak mencukupi untuk disemprotkan.' });
        }
    }

    function saveData() {
        Swal.fire({
            title: 'Menyimpan Data...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() }
        });

        fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(robotData)
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                markSaved(); 
                Swal.fire({ icon: 'success', title: 'Tersimpan!', text: 'Data harian berhasil diperbarui di database.', timer: 1500, showConfirmButton: false }).then(() => {
                    location.reload(); // Refresh agar tabel history terupdate
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan saat menyimpan data.' });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Error Koneksi!', text: 'Tidak dapat terhubung ke server database.' });
        });
    }

    function resetData() {
        Swal.fire({
            title: 'Reset Map & Data?',
            text: "Data di layar akan di-nol-kan. Tekan 'Simpan Data' setelahnya agar tersimpan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                robotData.distance = 0;
                robotData.waterUsed = 0;
                robotData.waterRemaining = maxWater;
                robotData.battery = 100;
                rx = 400; ry = 200;
                robotData.path = [{x: rx, y: ry}];
                updateUI();
                markUnsaved(); 
                Swal.fire('Di-reset!', 'Map berhasil dibersihkan. Silakan klik Simpan Data.', 'success');
            }
        });
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'ArrowUp') { event.preventDefault(); moveRobot('up'); }
        else if (event.key === 'ArrowDown') { event.preventDefault(); moveRobot('down'); }
        else if (event.key === 'ArrowLeft') { event.preventDefault(); moveRobot('left'); }
        else if (event.key === 'ArrowRight') { event.preventDefault(); moveRobot('right'); }
    });

    // --- FILTER TABEL LOGIC ---
    function filterTable() {
        let selectedYear = document.getElementById('filter-year').value;
        let selectedMonth = document.getElementById('filter-month').value;
        let rows = document.querySelectorAll('#history-table-body tr[data-year]');
        let visibleCount = 0;

        rows.forEach(row => {
            let rowYear = row.getAttribute('data-year');
            let rowMonth = row.getAttribute('data-month');
            
            let showYear = (selectedYear === 'all' || selectedYear === rowYear);
            let showMonth = (selectedMonth === 'all' || selectedMonth === rowMonth);

            if(showYear && showMonth) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Hapus info "tidak ada data" sebelumnya jika ada
        let emptyInfo = document.getElementById('empty-filter-info');
        if(emptyInfo) emptyInfo.remove();

        // Munculkan notifikasi baris kosong jika filter tidak membuahkan hasil
        if(visibleCount === 0 && rows.length > 0) {
            let tr = document.createElement('tr');
            tr.id = 'empty-filter-info';
            tr.innerHTML = '<td colspan="4" class="px-6 py-8 text-center text-gray-500">Tidak ada riwayat pada bulan/tahun yang dipilih.</td>';
            document.getElementById('history-table-body').appendChild(tr);
        }
    }

    // --- PDF LOGIC (Sama persis tidak diubah agar tetap bagus) ---
    function getFullMapBase64() {
        if (robotData.path.length === 0) return canvas.toDataURL("image/png");
        let minX = robotData.path[0].x, maxX = robotData.path[0].x;
        let minY = robotData.path[0].y, maxY = robotData.path[0].y;
        for(let p of robotData.path) {
            if(p.x < minX) minX = p.x; if(p.x > maxX) maxX = p.x;
            if(p.y < minY) minY = p.y; if(p.y > maxY) maxY = p.y;
        }

        let pad = 60; let w = (maxX - minX) + pad * 2; let h = (maxY - minY) + pad * 2;
        w = Math.max(w, 700); h = Math.max(h, 350);

        let offCanvas = document.createElement('canvas'); offCanvas.width = w; offCanvas.height = h;
        let octx = offCanvas.getContext('2d');
        octx.fillStyle = '#f8fafc'; octx.fillRect(0, 0, w, h);
        octx.strokeStyle = '#e2e8f0'; octx.lineWidth = 1;
        for(let i=0; i<=w; i+=40) { octx.beginPath(); octx.moveTo(i,0); octx.lineTo(i,h); octx.stroke(); }
        for(let i=0; i<=h; i+=40) { octx.beginPath(); octx.moveTo(0,i); octx.lineTo(w,i); octx.stroke(); }

        let cx = (w - (maxX - minX)) / 2 - minX; let cy = (h - (maxY - minY)) / 2 - minY;
        octx.save(); octx.translate(cx, cy);

        if(robotData.path.length > 1) {
            octx.beginPath(); octx.moveTo(robotData.path[0].x, robotData.path[0].y);
            for (let i = 1; i < robotData.path.length; i++) octx.lineTo(robotData.path[i].x, robotData.path[i].y);
            octx.strokeStyle = '#0d9488'; octx.lineWidth = 4; octx.lineJoin = 'round'; octx.lineCap = 'round'; octx.stroke();
        }

        octx.fillStyle = '#1e293b'; octx.shadowColor = '#0d9488'; octx.shadowBlur = 10;
        octx.fillRect(rx - 10, ry - 10, 20, 20); octx.restore();
        
        return offCanvas.toDataURL("image/png");
    }

    function generatePDF() {
        if(!isDataSaved) {
            Swal.fire({ icon: 'warning', title: 'Data Belum Disimpan!', text: 'Harap klik "Simpan Data" terlebih dahulu.' }); return;
        }

        let printTime = new Date();
        let fullDateTime = printTime.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' - ' + printTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        document.getElementById('pdf-datetime').innerText = fullDateTime;
        document.getElementById('pdf-battery').innerText = robotData.battery.toFixed(1) + "%";
        document.getElementById('pdf-distance').innerText = robotData.distance.toFixed(1);
        document.getElementById('pdf-water-used').innerText = robotData.waterUsed;
        document.getElementById('pdf-water-rem').innerText = robotData.waterRemaining;
        
        const pdfWrapper = document.getElementById('pdf-wrapper'); pdfWrapper.style.display = 'block';
        let pdfMapImage = document.getElementById('pdf-map-image');
        
        Swal.fire({ title: 'Menyiapkan Laporan...', text: 'Merender peta keseluruhan...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

        pdfMapImage.onload = function() {
            const element = document.getElementById('pdf-report-template');
            const opt = { margin: 0.3, filename: 'Laporan_NavX_' + new Date().toISOString().slice(0,10) + '.pdf', image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true, logging: false }, jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' } };
            html2pdf().set(opt).from(element).save().then(() => {
                pdfWrapper.style.display = 'none'; Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Laporan PDF berhasil di-download.' });
            });
        };
        pdfMapImage.src = getFullMapBase64(); 
    }

    setInterval(() => { document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID'); }, 1000);
    updateUI();
</script>

</body>
</html>