<?php
$host = "localhost";
$user = "root"; 
$pass = "";     
$db   = "robot_dashboard";

// Hapus peringatan error default bawaan mysqli
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // Jika koneksi db gagal, lemparkan error berupa JSON
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Koneksi database gagal: " . $conn->connect_error]);
    exit;
}
?>