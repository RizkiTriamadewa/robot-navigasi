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
    $date = date('Y-m-d');
    $distance = floatval($data['distance']);
    // FIXED: Mengubah 'water_used' menjadi 'waterUsed' agar sama dengan variabel JavaScript
    $water_used = floatval($data['waterUsed']); 
    $path_data = $conn->real_escape_string(json_encode($data['path']));

    // Cek apakah data hari ini sudah ada
    $check = $conn->query("SELECT id FROM daily_logs WHERE log_date = '$date'");
    
    if ($check->num_rows > 0) {
        // Update data hari ini
        $sql = "UPDATE daily_logs SET 
                distance_m = $distance, 
                water_used_ml = $water_used, 
                path_data = '$path_data' 
                WHERE log_date = '$date'";
    } else {
        // Insert data baru untuk hari ini
        $sql = "INSERT INTO daily_logs (log_date, distance_m, water_used_ml, path_data) 
                VALUES ('$date', $distance, $water_used, '$path_data')";
    }

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "Data harian tersimpan"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Tidak ada data yang diterima dari client"]);
}

$conn->close();
?>