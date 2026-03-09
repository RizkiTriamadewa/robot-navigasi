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
    $water_used = floatval($data['waterUsed']); 
    $battery = floatval($data['battery']); 
    $path_data = $conn->real_escape_string(json_encode($data['path']));

    $check = $conn->query("SELECT id FROM daily_logs WHERE log_date = '$date'");
    
    if ($check->num_rows > 0) {
        $sql = "UPDATE daily_logs SET 
                distance_m = $distance, 
                water_used_ml = $water_used, 
                battery_percent = $battery,
                path_data = '$path_data' 
                WHERE log_date = '$date'";
    } else {
        $sql = "INSERT INTO daily_logs (log_date, distance_m, water_used_ml, battery_percent, path_data) 
                VALUES ('$date', $distance, $water_used, $battery, '$path_data')";
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