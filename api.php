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
    $distance = $data['distance'];
    $waterUsed = $data['waterUsed'];
    $battery = $data['battery'];
    
    // Ubah array path dan sprayPoints menjadi string JSON
    $pathData = json_encode($data['path']);
    $sprayData = json_encode($data['sprayPoints']); // <--- TAMBAHAN BARU

    // Cek apakah data hari ini sudah ada
    $check = $conn->query("SELECT id FROM daily_logs WHERE log_date = '$date'");
    
    if ($check->num_rows > 0) {
        // Jika sudah ada, lakukan UPDATE (Tambahkan spray_data di sini)
        $query = "UPDATE daily_logs SET 
                    distance_m = '$distance', 
                    water_used_ml = '$waterUsed', 
                    battery_percent = '$battery', 
                    path_data = '$pathData',
                    spray_data = '$sprayData' 
                  WHERE log_date = '$date'";
    } else {
        // Jika belum ada, lakukan INSERT (Tambahkan spray_data di sini)
        $query = "INSERT INTO daily_logs (log_date, distance_m, water_used_ml, battery_percent, path_data, spray_data) 
                  VALUES ('$date', '$distance', '$waterUsed', '$battery', '$pathData', '$sprayData')";
    }

    if ($conn->query($query) === TRUE) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
}
?>