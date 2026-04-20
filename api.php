<?php
// Matikan tampilan error HTML bawaan PHP agar tidak merusak format JSON
error_reporting(0);
ini_set('display_errors', 0);

require 'db.php';

// Pastikan response selalu dalam format JSON
header('Content-Type: application/json');

// Ambil data dari request POST (JSON)
$input_data = file_get_contents("php://input");
$data = json_decode($input_data, true);

if ($data) {
    // Ambil data dengan fallback default
    $id = isset($data['id']) ? intval($data['id']) : 0;
    $distance = floatval($data['distance']);
    $waterUsed = floatval($data['waterUsed']);
    $battery = floatval($data['battery']);
    
    // Ubah array menjadi string JSON
    $pathData = json_encode($data['path']);
    $sprayData = json_encode($data['sprayPoints']);

    if ($id > 0) {
        // ==========================================
        // 1. UPDATE SESI (Jika tombol "Simpan" ditekan pada sesi yang sedang berjalan)
        // ==========================================
        $stmt = $conn->prepare("UPDATE daily_logs SET distance_m=?, water_used_ml=?, battery_percent=?, path_data=?, spray_data=? WHERE id=?");
        $stmt->bind_param("dddssi", $distance, $waterUsed, $battery, $pathData, $sprayData, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal update: ' . $stmt->error]);
        }
        $stmt->close();

    } else {
        // ==========================================
        // 2. INSERT SESI BARU (Jika tombol "Sesi Baru" ditekan)
        // ==========================================
        $today = date('Y-m-d');
        
        // Cek jumlah data yang sudah tersimpan HARI INI
        $checkQuery = $conn->query("SELECT id FROM daily_logs WHERE DATE(log_date) = '$today' ORDER BY log_date ASC");
        
        // Jika data hari ini sudah ada 5 (atau lebih)
        if ($checkQuery->num_rows >= 5) {
            // Hitung berapa banyak data terlama yang harus dihapus agar sisa 4, lalu kita insert 1 jadi pas 5
            $limitToDelete = $checkQuery->num_rows - 4; 
            $conn->query("DELETE FROM daily_logs WHERE DATE(log_date) = '$today' ORDER BY log_date ASC LIMIT $limitToDelete");
        }

        // Insert data baru menggunakan waktu jam & menit saat ini (NOW())
        $stmt = $conn->prepare("INSERT INTO daily_logs (log_date, distance_m, water_used_ml, battery_percent, path_data, spray_data) VALUES (NOW(), ?, ?, ?, ?, ?)");
        $stmt->bind_param("dddss", $distance, $waterUsed, $battery, $pathData, $sprayData);
        
        if ($stmt->execute()) {
            // Ambil ID dari baris yang baru saja ditambahkan
            $new_id = $conn->insert_id;
            // Kirim ID baru kembali ke Javascript agar sesi ini langsung bisa di-update selanjutnya
            echo json_encode(['status' => 'success', 'new_id' => $new_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal insert: ' . $stmt->error]);
        }
        $stmt->close();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
}
?>