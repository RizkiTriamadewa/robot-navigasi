<?php
require 'db.php';

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
    
    <style>
        /* FIT ONE PAGE CSS */
        body, html { height: 100%; width: 100%; margin: 0; overflow: hidden; }
        body { transition: background-color 0.3s, color 0.3s; }
        .panel { transition: background-color 0.3s, border-color 0.3s; }
        .btn-control:active { transform: scale(0.95); }
        
        select { -webkit-appearance: none; -moz-appearance: none; appearance: none; }
        select::-ms-expand { display: none; }

        .blinking-record { animation: blinker 1.5s linear infinite; }
        @keyframes blinker { 50% { opacity: 0; } }
        
        .tab-content { display: none; }
        .tab-content.active { display: flex; }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden flex flex-col p-2 font-sans bg-gray-100 text-gray-800 dark:bg-[#1a1e29] dark:text-[#a0aec0]">

<div class="flex-none flex justify-between items-center panel p-2 rounded-lg bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446] mb-2 gap-2 h-14">
    <h1 class="text-sm md:text-lg font-bold tracking-widest text-gray-900 dark:text-white flex items-center shrink-0">
        <i class="fa-solid fa-robot text-teal-500 mr-2 text-xl"></i> NAV-X
    </h1>
    
    <div class="flex space-x-1 bg-gray-100 dark:bg-[#1a1e29] p-1 rounded border border-gray-200 dark:border-slate-700 mx-auto">
        <button onclick="switchTab('monitoring')" id="btn-tab-monitoring" class="px-3 py-1 rounded bg-white dark:bg-slate-600 shadow text-teal-600 dark:text-teal-400 font-bold text-xs transition-all">
            <i class="fa-solid fa-display"></i> <span class="hidden md:inline ml-1">Monitoring</span>
        </button>
        <button onclick="switchTab('riwayat')" id="btn-tab-riwayat" class="px-3 py-1 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700 font-bold text-xs transition-all">
            <i class="fa-solid fa-clock-rotate-left"></i> <span class="hidden md:inline ml-1">Riwayat</span>
        </button>
        <button onclick="switchTab('laporan')" id="btn-tab-laporan" class="px-3 py-1 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-700 font-bold text-xs transition-all">
            <i class="fa-solid fa-file-pdf"></i> <span class="hidden md:inline ml-1">Laporan</span>
        </button>
    </div>

    <div class="flex items-center space-x-3 shrink-0">
        <div class="text-[10px] md:text-xs hidden md:block text-gray-600 dark:text-gray-300">
            <span id="clock" class="text-teal-500 font-mono font-bold">00:00:00</span>
        </div>
        <button onclick="toggleTheme()" class="p-1.5 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-[#2a3040] dark:text-yellow-400 dark:hover:bg-[#3b4256] transition shadow-inner">
            <i id="theme-icon" class="fa-solid fa-moon"></i>
        </button>
    </div>
</div>

<div id="tab-monitoring" class="tab-content active flex-1 flex-col space-y-2 min-h-0 overflow-hidden">
    
    <div class="flex-none grid grid-cols-4 gap-2">
        <div class="panel p-2 rounded-lg flex items-center space-x-2 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-6 h-6 md:w-8 md:h-8 flex items-center justify-center rounded bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 shrink-0"><i class="fa-solid fa-battery-three-quarters text-[10px] md:text-sm"></i></div>
            <div>
                <div class="text-[8px] md:text-[10px] text-gray-500 leading-none mb-1">Battery</div>
                <div class="text-xs md:text-sm font-extrabold text-gray-900 dark:text-white leading-none" id="val-battery">--%</div>
            </div>
        </div>
        <div class="panel p-2 rounded-lg flex items-center space-x-2 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-6 h-6 md:w-8 md:h-8 flex items-center justify-center rounded bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shrink-0"><i class="fa-solid fa-route text-[10px] md:text-sm"></i></div>
            <div>
                <div class="text-[8px] md:text-[10px] text-gray-500 leading-none mb-1">Jarak</div>
                <div class="text-xs md:text-sm font-extrabold text-gray-900 dark:text-white leading-none"><span id="val-distance">0</span> m</div>
            </div>
        </div>
        <div class="panel p-2 rounded-lg flex items-center space-x-2 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-6 h-6 md:w-8 md:h-8 flex items-center justify-center rounded bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400 shrink-0"><i class="fa-solid fa-faucet-drip text-[10px] md:text-sm"></i></div>
            <div>
                <div class="text-[8px] md:text-[10px] text-gray-500 leading-none mb-1">Air Keluar</div>
                <div class="text-xs md:text-sm font-extrabold text-gray-900 dark:text-white leading-none"><span id="val-water-used">0</span> ml</div>
            </div>
        </div>
        <div class="panel p-2 rounded-lg flex items-center space-x-2 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-6 h-6 md:w-8 md:h-8 flex items-center justify-center rounded bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400 shrink-0"><i class="fa-solid fa-prescription-bottle text-[10px] md:text-sm"></i></div>
            <div>
                <div class="text-[8px] md:text-[10px] text-gray-500 leading-none mb-1">Sisa Tangki</div>
                <div class="text-xs md:text-sm font-extrabold text-gray-900 dark:text-white leading-none"><span id="val-water-rem">2000</span> ml</div>
            </div>
        </div>
    </div>

    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-2 min-h-0">
        
        <div class="flex flex-col gap-2 min-h-0 h-full">
            
            <div class="flex-1 flex flex-col panel rounded-lg p-2 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446] min-h-0">
                <div class="flex-none flex justify-between items-center mb-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-[10px] font-bold text-gray-500 tracking-widest uppercase"><i class="fa-solid fa-video mr-1"></i> Live FPV</h2>
                        <select id="camera-select" onchange="switchCamera(this.value)" class="bg-gray-50 text-gray-700 text-[8px] px-1 py-0.5 rounded border border-gray-300 dark:bg-slate-700 dark:border-slate-600 dark:text-white outline-none cursor-pointer max-w-[100px]"></select>
                    </div>
                    
                    <div class="flex items-center gap-1.5">
                        <button onclick="takePhoto()" class="text-[9px] font-bold text-white bg-blue-500 hover:bg-blue-600 px-2 py-0.5 rounded transition flex items-center gap-1">
                            <i class="fa-solid fa-camera"></i> FOTO
                        </button>                  
                        <button id="btn-record" onclick="toggleRecording()" class="text-[9px] font-bold text-white bg-gray-400 hover:bg-red-500 px-2 py-0.5 rounded transition flex items-center gap-1">
                            <span id="record-dot" class="w-1.5 h-1.5 rounded-full bg-white"></span> <span id="record-text">REKAM 30s</span>
                        </button>
                    </div>
                </div>
                <div class="flex-1 relative bg-black rounded overflow-hidden border border-gray-300 dark:border-gray-800 flex items-center justify-center min-h-0">
                    <video id="webcam-video" autoplay playsinline class="absolute inset-0 w-full h-full object-cover scale-x-[-1] transition-opacity duration-100"></video>
                    
                    <div class="absolute inset-0 pointer-events-none border-[rgba(20,184,166,0.3)] border m-2 rounded z-10"></div>
                    <div class="absolute top-1/2 left-0 w-full h-[1px] bg-teal-500/30 pointer-events-none z-10"></div>
                    <div class="absolute left-1/2 top-0 h-full w-[1px] bg-teal-500/30 pointer-events-none z-10"></div>
                    
                    <div id="cam-status-text" class="text-center z-20 bg-black/60 px-3 py-2 rounded-lg backdrop-blur-sm border border-gray-700">
                        <i class="fa-solid fa-camera text-xl text-gray-400 mb-1 animate-pulse"></i>
                        <p class="text-gray-300 font-mono text-[10px] tracking-widest">MEMINTA AKSES KAMERA...</p>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col panel rounded-lg p-3 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446] min-h-0">
                <div class="flex-none flex flex-wrap justify-between items-center mb-2 gap-1">
                    <h2 class="text-[10px] font-bold text-gray-500 tracking-widest uppercase">Controls</h2>
                    
                    <div class="flex gap-1.5 items-center">
                        <div class="relative inline-block border border-gray-200 dark:border-slate-600 rounded">
                            <select id="autosave-select" onchange="updateIdleSetting(true)" class="bg-gray-50 text-gray-700 text-[9px] pl-2 pr-5 py-1 rounded outline-none cursor-pointer hover:bg-gray-100 dark:bg-slate-700 dark:text-white appearance-none w-full">
                                <option value="0">Auto Save: OFF</option>
                                <option value="30000">Idle 30 Detik</option>
                                <option value="60000">Idle 1 Menit</option>
                                <option value="180000">Idle 3 Menit</option>
                                <option value="300000">Idle 5 Menit</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-chevron-down text-[7px]"></i>
                            </div>
                        </div>
                        <div class="relative inline-block border border-gray-200 dark:border-slate-600 rounded">
                            <select id="mode-select" class="bg-gray-50 text-gray-700 text-[9px] pl-2 pr-5 py-1 rounded outline-none cursor-pointer hover:bg-gray-100 dark:bg-slate-700 dark:text-white appearance-none w-full">
                                <option value="manual">Manual</option>
                                <option value="auto">Auto (GPS)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-chevron-down text-[7px]"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 flex flex-row items-center justify-center gap-4 sm:gap-6 min-h-0 h-full">
                    
                    <div class="flex-none flex flex-col items-center gap-1.5 p-3 bg-[#f8fafc] dark:bg-[#1a1e29]/50 rounded-2xl border border-gray-100 dark:border-slate-700/50 shadow-sm">
                        <button onclick="moveRobot('up')" class="btn-control w-12 h-12 md:w-14 md:h-14 rounded-xl shrink-0 flex items-center justify-center text-lg md:text-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256]"><i class="fa-solid fa-chevron-up"></i></button>
                        <div class="flex gap-1.5">
                            <button onclick="moveRobot('left')" class="btn-control w-12 h-12 md:w-14 md:h-14 rounded-xl shrink-0 flex items-center justify-center text-lg md:text-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256]"><i class="fa-solid fa-chevron-left"></i></button>
                            <button onclick="moveRobot('down')" class="btn-control w-12 h-12 md:w-14 md:h-14 rounded-xl shrink-0 flex items-center justify-center text-lg md:text-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256]"><i class="fa-solid fa-chevron-down"></i></button>
                            <button onclick="moveRobot('right')" class="btn-control w-12 h-12 md:w-14 md:h-14 rounded-xl shrink-0 flex items-center justify-center text-lg md:text-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm dark:bg-[#2a3040] dark:text-gray-200 dark:border-[#3b4256]"><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>
                    
                    <div class="flex-1 flex flex-col gap-2 h-full py-1">
                        <button onclick="sprayWater()" class="flex-1 min-h-0 bg-teal-600 hover:bg-teal-500 text-white font-bold rounded-xl shadow-sm transition-all flex flex-col justify-center items-center gap-1.5 text-xs sm:text-sm">
                            <i class="fa-solid fa-droplet text-2xl sm:text-3xl mb-1"></i> Semprot
                        </button>
                        <div class="flex-1 min-h-0 flex gap-2">
                            <button onclick="saveData(false)" class="flex-1 bg-green-600 hover:bg-green-500 text-white font-bold rounded-xl shadow-sm transition-all flex flex-col justify-center items-center gap-1 text-[10px] sm:text-xs">
                                <i class="fa-solid fa-floppy-disk text-lg sm:text-xl mb-0.5"></i> Simpan
                            </button>
                            <button onclick="resetData()" class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 dark:bg-red-500/10 dark:border-red-500/30 dark:text-red-400 rounded-xl transition shadow-sm flex flex-col justify-center items-center gap-1 text-[10px] sm:text-xs">
                                <i class="fa-solid fa-rotate-right text-lg sm:text-xl mb-0.5"></i> Sesi Baru
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="flex flex-col panel rounded-lg p-2 bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446] min-h-0 h-full relative cursor-crosshair">
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
    <div class="panel h-full flex flex-col p-3 rounded-lg bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446]">
        <div class="flex-none flex justify-between items-center mb-3">
            <h2 class="text-sm md:text-base font-bold text-gray-900 dark:text-white"><i class="fa-solid fa-clock-rotate-left mr-2 text-teal-500"></i> Riwayat Sesi</h2>
            <div class="flex space-x-2">
                <select id="filter-year" onchange="filterTable()" class="bg-gray-50 text-gray-700 text-xs px-2.5 py-1.5 rounded border border-gray-300 dark:bg-slate-700 dark:border-slate-600 dark:text-white font-medium shadow-sm cursor-pointer outline-none hover:bg-gray-100 dark:hover:bg-slate-600">
                    <option value="all">Semua Thn</option>
                    <?php foreach($availableYears as $y): ?><option value="<?= $y ?>"><?= $y ?></option><?php endforeach; ?>
                </select>
                <select id="filter-month" onchange="filterTable()" class="bg-gray-50 text-gray-700 text-xs px-2.5 py-1.5 rounded border border-gray-300 dark:bg-slate-700 dark:border-slate-600 dark:text-white font-medium shadow-sm cursor-pointer outline-none hover:bg-gray-100 dark:hover:bg-slate-600">
                    <option value="all">Semua Bln</option>
                    <option value="01">Jan</option><option value="02">Feb</option><option value="03">Mar</option><option value="04">Apr</option>
                    <option value="05">Mei</option><option value="06">Jun</option><option value="07">Jul</option><option value="08">Agu</option>
                    <option value="09">Sep</option><option value="10">Okt</option><option value="11">Nov</option><option value="12">Des</option>
                </select>
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
                            $sisaAir = 2000 - $row['water_used_ml'];
                            $btr = isset($row['battery_percent']) ? number_format($row['battery_percent'], 1) : 100.0;
                        ?>
                        <tr class="bg-white hover:bg-teal-50 dark:bg-[#232836] dark:hover:bg-slate-800 transition-colors" data-year="<?= $year ?>" data-month="<?= $month ?>">
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
    <div class="panel w-full max-w-sm p-4 rounded-lg bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446] text-center">
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
        if(!video.srcObject) return Swal.fire('Error', 'Kamera belum aktif!', 'error');

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
        if(!stream) return Swal.fire('Error', 'Kamera belum aktif!', 'error');
        
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
        ['monitoring', 'riwayat', 'laporan'].forEach(id => {
            document.getElementById('tab-' + id).classList.remove('active');
            let btn = document.getElementById('btn-tab-' + id);
            btn.classList.remove('bg-white', 'dark:bg-slate-600', 'shadow', 'text-teal-600', 'dark:text-teal-400');
            btn.classList.add('text-gray-600', 'dark:text-gray-400');
        });
        document.getElementById('tab-' + tabId).classList.add('active');
        let activeBtn = document.getElementById('btn-tab-' + tabId);
        activeBtn.classList.add('bg-white', 'dark:bg-slate-600', 'shadow', 'text-teal-600', 'dark:text-teal-400');
        if(tabId === 'monitoring') setTimeout(resizeAndDrawMap, 50);
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
            allowOutsideClick: false, allowEscapeKey: false
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
        if(document.getElementById('mode-select').value === 'auto') return Swal.fire({ icon: 'warning', text: 'Ubah ke Manual Mode untuk mengontrol manual.' });
        if(pathInterval) clearInterval(pathInterval); 
        switch(dir) { case 'up': ry -= step; break; case 'down': ry += step; break; case 'left': rx -= step; break; case 'right': rx += step; break; }
        robotData.path.push({x: rx, y: ry}); robotData.distance += 0.15; updateUI(); markUnsaved();
    }

    function sprayWater() {
        if(robotData.waterRemaining >= 50) {
            robotData.waterUsed += 50; robotData.waterRemaining -= 50; robotData.sprayPoints.push({x: rx, y: ry});
            updateUI(); markUnsaved(); Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Air disemprotkan.', showConfirmButton: false, timer: 1000 });
        } else Swal.fire({ icon: 'error', title: 'Tangki Kosong!' });
    }

    // UPDATE PENTING: Update fungsi simpan agar bisa menangani pembuatan sesi baru
    function saveData(isAutoPrompt = false) {
        Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

        fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(robotData) })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') { 
                if(data.new_id) robotData.id = data.new_id; // Setel ID jika ini sesi baru!
                markSaved(); 
                if (isAutoPrompt) {
                    Swal.fire({ icon: 'success', title: 'Berhasil Disimpan!', showConfirmButton: false, timer: 1500 });
                } else {
                    Swal.fire({ icon: 'success', title: 'Tersimpan!', timer: 1000, showConfirmButton: false }).then(()=>location.reload()); 
                }
            } else {
                Swal.fire('Gagal!', 'Terjadi kesalahan di server', 'error');
            }
        }).catch(err => {
            Swal.fire('Error!', 'Gagal koneksi ke server', 'error');
        });
    }

    // UPDATE PENTING: Reset sekarang akan menjadikan ini sesi baru!
    function resetData() {
        Swal.fire({ title: 'Sesi Baru?', text: "Peta akan di-reset dan data akan disimpan sebagai sesi terpisah (Maksimal 5/hari).", icon: 'warning', showCancelButton: true, confirmButtonText: 'Mulai Sesi Baru' }).then(r => {
            if (r.isConfirmed) {
                robotData.id = 0; // Kunci penting: atur ID jadi 0 agar menjadi INSERT baru di database
                robotData.distance = 0; robotData.waterUsed = 0; robotData.waterRemaining = maxWater; rx=400; ry=200;
                robotData.path = [{x: rx, y: ry}]; robotData.sprayPoints = []; updateUI(); markUnsaved();
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
        let sy = document.getElementById('filter-year').value, sm = document.getElementById('filter-month').value;
        document.querySelectorAll('#history-table-body tr[data-year]').forEach(row => {
            let ry = row.getAttribute('data-year'), rm = row.getAttribute('data-month');
            row.style.display = ((sy==='all' || sy===ry) && (sm==='all' || sm===rm)) ? '' : 'none';
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
        if(!isDataSaved) return Swal.fire({ icon: 'warning', text: 'Klik Simpan Data dulu!' });
        let pt = new Date(); document.getElementById('pdf-datetime').innerText = pt.toLocaleDateString('id-ID') + ' ' + pt.toLocaleTimeString('id-ID');
        document.getElementById('pdf-wrapper').style.display = 'block';
        let img = document.getElementById('pdf-map-image');
        Swal.fire({ title: 'Menyiapkan PDF...', didOpen: () => Swal.showLoading() });
        img.onload = () => {
            html2pdf().set({ margin: 0.4, filename: 'Laporan_NavX.pdf', jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' } })
            .from(document.getElementById('pdf-report-template')).save().then(() => {
                document.getElementById('pdf-wrapper').style.display = 'none'; Swal.fire('Berhasil!', 'PDF di-download', 'success');
            });
        };
        img.src = getFullMapBase64();
    }

    updateIdleSetting(false);
    setInterval(() => { document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID'); }, 1000);
    getCameras(); initBatteryStatus(); setTimeout(resizeAndDrawMap, 100); updateUI();
</script>
</body>
</html>