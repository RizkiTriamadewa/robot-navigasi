<?php
header('Content-Type: application/json');
require 'db.php';

// Ambil method request
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Tambah log baru
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type']) || !isset($input['message'])) {
        echo json_encode(['status' => 'error', 'message' => 'Type dan message wajib diisi']);
        exit;
    }
    
    $type = $conn->real_escape_string($input['type']);
    $message = $conn->real_escape_string($input['message']);
    $details = isset($input['details']) ? $conn->real_escape_string($input['details']) : '';
    
    $sql = "INSERT INTO activity_logs (type, message, details) VALUES ('$type', '$message', '$details')";
    
    if ($conn->query($sql)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Log berhasil ditambahkan',
            'id' => $conn->insert_id
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menambahkan log: ' . $conn->error
        ]);
    }
    
} elseif ($method === 'GET') {
    // Ambil logs
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
    
    $sql = "SELECT * FROM activity_logs";
    if ($type && $type !== 'all') {
        $sql .= " WHERE type = '$type'";
    }
    $sql .= " ORDER BY created_at DESC LIMIT $limit";
    
    $result = $conn->query($sql);
    $logs = [];
    
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'message' => $row['message'],
            'details' => $row['details'],
            'timestamp' => $row['created_at'],
            'timeStr' => date('d M Y - H:i:s', strtotime($row['created_at']))
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $logs,
        'count' => count($logs)
    ]);
    
} elseif ($method === 'DELETE') {
    // Hapus semua logs
    $sql = "TRUNCATE TABLE activity_logs";
    
    if ($conn->query($sql)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Semua log berhasil dihapus'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menghapus log: ' . $conn->error
        ]);
    }
    
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Method tidak didukung'
    ]);
}

$conn->close();
?>
