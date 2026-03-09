<?php
require 'db.php';
$date = date('Y-m-d');
$query = $conn->query("SELECT * FROM daily_logs WHERE log_date = '$date'");
$todayData = $query->fetch_assoc();

$initDistance = $todayData ? $todayData['distance_m'] : 0;
$initWaterUsed = $todayData ? $todayData['water_used_ml'] : 0;
$initPath = $todayData && $todayData['path_data'] ? $todayData['path_data'] : "[]";
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
    </style>
</head>
<body class="min-h-screen p-4 md:p-8 font-sans bg-gray-100 text-gray-800 dark:bg-[#1a1e29] dark:text-[#a0aec0]">

<div class="max-w-7xl mx-auto space-y-6" id="dashboard-content">
    <div class="flex flex-wrap justify-between items-center panel p-4 rounded-xl bg-white border border-gray-200 shadow-sm dark:bg-[#232836] dark:border-[#2d3446] dark:shadow-[0_4px_6px_rgba(0,0,0,0.3)]">
        <h1 class="text-xl font-bold tracking-widest text-gray-900 dark:text-white">
            <i class="fa-solid fa-robot text-teal-500 mr-2"></i> NAV-X MONITORING
        </h1>
        <div class="flex items-center space-x-4">
            <div class="text-sm hidden sm:block text-gray-600 dark:text-gray-300">
                <span><i class="fa-solid fa-calendar mr-1"></i> <span id="header-date"></span></span>
                <span id="clock" class="text-teal-500 font-mono ml-2 font-bold">00:00:00</span>
            </div>
            <button onclick="toggleTheme()" class="p-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-[#2a3040] dark:text-yellow-400 dark:hover:bg-[#3b4256] transition">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 panel rounded-xl p-4 flex flex-col bg-white border border-gray-200 dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-widest">MAP & TRACKING (AUTO-FOLLOW CAMERA)</h2>
                <button onclick="resetData()" class="text-xs bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-500/20 dark:text-red-400 dark:hover:bg-red-500/40 px-3 py-1 rounded transition">
                    <i class="fa-solid fa-rotate-right mr-1"></i> Reset Map
                </button>
            </div>
            <div class="relative flex-grow bg-gray-50 dark:bg-slate-800 rounded-lg overflow-hidden border border-gray-200 dark:border-slate-700 h-80 lg:h-auto flex items-center justify-center">
                <canvas id="minimap" width="800" height="400" class="w-full h-full"></canvas>
            </div>
        </div>

        <div class="panel rounded-xl p-4 flex flex-col justify-between bg-white border border-gray-200 dark:bg-[#232836] dark:border-[#2d3446]">
            <div>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-widest">CONTROLS</h2>
                    <select id="mode-select" class="bg-gray-100 border border-gray-300 text-gray-800 dark:bg-slate-700 dark:border-slate-600 dark:text-white text-xs px-2 py-1 rounded outline-none">
                        <option value="manual">Manual Mode</option>
                        <option value="auto">Auto Mode</option>
                    </select>
                </div>
                <div class="flex flex-col items-center justify-center space-y-2 mb-8">
                    <button onclick="moveRobot('up')" class="btn-control w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 dark:bg-[#2a3040] dark:text-white dark:border-[#3b4256] dark:hover:bg-[#3b4256]"><i class="fa-solid fa-chevron-up"></i></button>
                    <div class="flex space-x-2">
                        <button onclick="moveRobot('left')" class="btn-control w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 dark:bg-[#2a3040] dark:text-white dark:border-[#3b4256] dark:hover:bg-[#3b4256]"><i class="fa-solid fa-chevron-left"></i></button>
                        <button onclick="moveRobot('down')" class="btn-control w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 dark:bg-[#2a3040] dark:text-white dark:border-[#3b4256] dark:hover:bg-[#3b4256]"><i class="fa-solid fa-chevron-down"></i></button>
                        <button onclick="moveRobot('right')" class="btn-control w-14 h-14 rounded-xl flex items-center justify-center text-xl bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 dark:bg-[#2a3040] dark:text-white dark:border-[#3b4256] dark:hover:bg-[#3b4256]"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
            <div class="space-y-3">
                <button onclick="sprayWater()" class="w-full bg-teal-500 hover:bg-teal-600 text-white font-bold py-3 rounded-lg shadow-[0_4px_10px_rgba(20,184,166,0.3)] dark:shadow-[0_0_15px_rgba(20,184,166,0.5)] transition-all">
                    <i class="fa-solid fa-droplet mr-2"></i> Semprot Air (50ml)
                </button>
                <button onclick="saveData()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow-[0_4px_10px_rgba(22,163,74,0.3)] transition-all">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Data
                </button>
                <button onclick="generatePDF()" id="btn-print" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg transition-all opacity-50 cursor-not-allowed">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Cetak Laporan
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="panel p-4 rounded-2xl flex items-center space-x-4 bg-white border border-gray-200 dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                <i class="fa-solid fa-battery-three-quarters text-2xl"></i>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Battery</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white" id="val-battery">100%</div>
            </div>
        </div>
        <div class="panel p-4 rounded-2xl flex items-center space-x-4 bg-white border border-gray-200 dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <i class="fa-solid fa-route text-2xl"></i>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Jarak Tempuh</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white"><span id="val-distance">0</span> m</div>
            </div>
        </div>
        <div class="panel p-4 rounded-2xl flex items-center space-x-4 bg-white border border-gray-200 dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                <i class="fa-solid fa-faucet-drip text-2xl"></i>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Air Keluar (Hari ini)</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white"><span id="val-water-used">0</span> ml</div>
            </div>
        </div>
        <div class="panel p-4 rounded-2xl flex items-center space-x-4 bg-white border border-gray-200 dark:bg-[#232836] dark:border-[#2d3446]">
            <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400">
                <i class="fa-solid fa-prescription-bottle text-2xl"></i>
            </div>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Sisa Tangki Air</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white"><span id="val-water-rem">2000</span> ml</div>
            </div>
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
    // --- Setup Tanggal Header ---
    const nowInit = new Date();
    document.getElementById('header-date').innerText = nowInit.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

    // --- Dark Mode Logic ---
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

    // --- Inisialisasi Data ---
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
        btnPrint.classList.remove('hover:bg-blue-700', 'shadow-[0_4px_10px_rgba(37,99,235,0.3)]');
    }

    function markSaved() {
        isDataSaved = true;
        btnPrint.classList.remove('opacity-50', 'cursor-not-allowed');
        btnPrint.classList.add('hover:bg-blue-700', 'shadow-[0_4px_10px_rgba(37,99,235,0.3)]');
    }

    if(isDataSaved) markSaved();

    // --- Setup Canvas & Variables ---
    const canvas = document.getElementById('minimap');
    const ctx = canvas.getContext('2d');
    
    // Titik pusat imajiner awal
    let rx = 400; 
    let ry = 200; 
    let step = 10;

    if (robotData.path.length > 0) {
        let lastPos = robotData.path[robotData.path.length - 1];
        rx = lastPos.x; 
        ry = lastPos.y;
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

    // ==========================================
    // PERBAIKAN 1: KAMERA INFINITE PADA MAP
    // ==========================================
    function drawMap() {
        // Membersihkan seluruh canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        let isDark = htmlTag.classList.contains('dark');
        
        // Logika Camera Follow: Menghitung offset agar robot selalu di tengah canvas
        let camX = rx - canvas.width / 2;
        let camY = ry - canvas.height / 2;

        ctx.save();
        // Geser seluruh dunia/canvas berlawanan arah dengan pergerakan kamera
        ctx.translate(-camX, -camY);

        // Gambar Grid Tak Terbatas (Hanya menggambar grid yang terlihat di layar kamera)
        ctx.strokeStyle = isDark ? '#2d3446' : '#e5e7eb';
        ctx.lineWidth = 1;

        let startX = Math.floor(camX / 40) * 40;
        let endX = camX + canvas.width + 40;
        let startY = Math.floor(camY / 40) * 40;
        let endY = camY + canvas.height + 40;

        for(let i = startX; i <= endX; i += 40) { ctx.beginPath(); ctx.moveTo(i, startY); ctx.lineTo(i, endY); ctx.stroke(); }
        for(let i = startY; i <= endY; i += 40) { ctx.beginPath(); ctx.moveTo(startX, i); ctx.lineTo(endX, i); ctx.stroke(); }

        // Gambar Rute (Jejak Robot)
        if (robotData.path.length > 1) {
            ctx.beginPath();
            ctx.moveTo(robotData.path[0].x, robotData.path[0].y);
            for (let i = 1; i < robotData.path.length; i++) {
                ctx.lineTo(robotData.path[i].x, robotData.path[i].y);
            }
            ctx.strokeStyle = '#14b8a6'; 
            ctx.lineWidth = 4;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            ctx.stroke();
        }

        // Gambar Posisi Robot Saat Ini
        ctx.fillStyle = isDark ? '#ffffff' : '#1f2937';
        ctx.shadowColor = '#14b8a6';
        ctx.shadowBlur = 10;
        ctx.fillRect(rx - 10, ry - 10, 20, 20);
        ctx.shadowBlur = 0;

        ctx.restore();
    }

    // --- Actions ---
    function moveRobot(direction) {
        if(document.getElementById('mode-select').value === 'auto') {
            Swal.fire({ icon: 'warning', title: 'Mode Auto Aktif', text: 'Ubah ke Manual Mode untuk menggunakan kontrol arah.' });
            return;
        }
        
        // PERBAIKAN: Batasan tepi map dihapus agar bisa berjalan mundur/maju tanpa batas
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
            
            // Efek semprotan air 
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
                Swal.fire({ icon: 'success', title: 'Tersimpan!', text: 'Data harian berhasil diperbarui di database.', timer: 1500, showConfirmButton: false });
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
            text: "Data jarak dan air di layar akan di-nol-kan. Kamu perlu menekan 'Simpan Data' setelahnya agar tersimpan ke Database.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
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

    // ==========================================
    // PERBAIKAN 2: RENDER SELURUH MAP UNTUK PDF
    // ==========================================
    function getFullMapBase64() {
        if (robotData.path.length === 0) return canvas.toDataURL("image/png");

        // Cari titik koordinat paling ujung (Bounding Box)
        let minX = robotData.path[0].x, maxX = robotData.path[0].x;
        let minY = robotData.path[0].y, maxY = robotData.path[0].y;
        for(let p of robotData.path) {
            if(p.x < minX) minX = p.x;
            if(p.x > maxX) maxX = p.x;
            if(p.y < minY) minY = p.y;
            if(p.y > maxY) maxY = p.y;
        }

        let pad = 60; // Padding ruang kosong sekeliling peta
        let w = (maxX - minX) + pad * 2;
        let h = (maxY - minY) + pad * 2;

        // Pastikan ukuran minimal agar tidak terlalu memanjang atau menyempit di PDF
        w = Math.max(w, 700);
        h = Math.max(h, 350);

        // Buat canvas sementara di memori
        let offCanvas = document.createElement('canvas');
        offCanvas.width = w;
        offCanvas.height = h;
        let octx = offCanvas.getContext('2d');

        // Background Peta PDF (Warna terang agar clean saat dicetak)
        octx.fillStyle = '#f8fafc';
        octx.fillRect(0, 0, w, h);

        // Gambar Grid Transparan
        octx.strokeStyle = '#e5e7eb';
        octx.lineWidth = 1;
        for(let i=0; i<=w; i+=40) { octx.beginPath(); octx.moveTo(i,0); octx.lineTo(i,h); octx.stroke(); }
        for(let i=0; i<=h; i+=40) { octx.beginPath(); octx.moveTo(0,i); octx.lineTo(w,i); octx.stroke(); }

        // Posisi menengahkan jalur ke dalam canvas sementara
        let cx = (w - (maxX - minX)) / 2 - minX;
        let cy = (h - (maxY - minY)) / 2 - minY;

        octx.save();
        octx.translate(cx, cy);

        // Gambar Jalur
        if(robotData.path.length > 1) {
            octx.beginPath();
            octx.moveTo(robotData.path[0].x, robotData.path[0].y);
            for (let i = 1; i < robotData.path.length; i++) {
                octx.lineTo(robotData.path[i].x, robotData.path[i].y);
            }
            octx.strokeStyle = '#14b8a6'; 
            octx.lineWidth = 4;
            octx.lineJoin = 'round';
            octx.lineCap = 'round';
            octx.stroke();
        }

        // Gambar Robot
        octx.fillStyle = '#1f2937';
        octx.shadowColor = '#14b8a6';
        octx.shadowBlur = 10;
        octx.fillRect(rx - 10, ry - 10, 20, 20);

        octx.restore();
        
        // Kembalikan sebagai gambar base64
        return offCanvas.toDataURL("image/png");
    }

    function generatePDF() {
        if(!isDataSaved) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Disimpan!',
                text: 'Harap klik tombol "Simpan Data" terlebih dahulu sebelum mencetak laporan.'
            });
            return;
        }

        // PERBAIKAN: Menambahkan Waktu spesifik pada PDF (Tanggal + Jam)
        let printTime = new Date();
        let dateOpts = { day: '2-digit', month: 'short', year: 'numeric' };
        let timeOpts = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
        let fullDateTime = printTime.toLocaleDateString('id-ID', dateOpts) + ' - ' + printTime.toLocaleTimeString('id-ID', timeOpts) + ' WIB';
        
        document.getElementById('pdf-datetime').innerText = fullDateTime;

        document.getElementById('pdf-battery').innerText = robotData.battery.toFixed(1) + "%";
        document.getElementById('pdf-distance').innerText = robotData.distance.toFixed(1);
        document.getElementById('pdf-water-used').innerText = robotData.waterUsed;
        document.getElementById('pdf-water-rem').innerText = robotData.waterRemaining;
        
        const pdfWrapper = document.getElementById('pdf-wrapper');
        pdfWrapper.style.display = 'block';

        // Panggil fungsi pembuat peta yang mengambil FULL area jalur robot
        let pdfMapImage = document.getElementById('pdf-map-image');
        
        Swal.fire({
            title: 'Menyiapkan Laporan...',
            text: 'Merender peta keseluruhan...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() }
        });

        pdfMapImage.onload = function() {
            const element = document.getElementById('pdf-report-template');
            
            const opt = {
                margin:       0.3,
                filename:     'Laporan_NavX_' + new Date().toISOString().slice(0,10) + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                pdfWrapper.style.display = 'none'; 
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Laporan PDF berhasil di-download.' });
            });
        };
        
        // Trigger load image map
        pdfMapImage.src = getFullMapBase64(); 
    }

    // Jam Berjalan di Header Dashboard
    setInterval(() => {
        document.getElementById('clock').innerText = new Date().toLocaleTimeString('id-ID');
    }, 1000);

    updateUI();
</script>

</body>
</html>