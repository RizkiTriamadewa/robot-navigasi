<?php
// Sembunyikan error dari output HTML, tapi kita tangkap menggunakan try-catch
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require __DIR__ . '/../src/Config/db.php';
require __DIR__ . '/../src/Auth/auth.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // ==========================================
    // DELETE /api.php?id=X  -> Hapus 1 sesi (CASCADE pest_detections + robot_positions)
    // DELETE /api.php?all=1 -> Hapus seluruh riwayat sesi
    // ==========================================
    if ($method === 'DELETE') {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        if (!hasPermission('delete_session')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden: butuh permission delete_session']);
            exit;
        }

        if (isset($_GET['all']) && $_GET['all'] == 1) {
            if (!$conn->query("DELETE FROM daily_logs")) {
                throw new Exception("Gagal hapus semua: " . $conn->error);
            }
            echo json_encode(['status' => 'success', 'deleted' => 'all']);
            exit;
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) throw new Exception('id tidak valid.');
        $stmt = $conn->prepare("DELETE FROM daily_logs WHERE id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) throw new Exception("Gagal hapus: " . $stmt->error);
        $affected = $stmt->affected_rows;
        $stmt->close();
        echo json_encode(['status' => 'success', 'deleted' => $affected]);
        exit;
    }

    // ==========================================
    // POST /api.php  -> INSERT/UPDATE sesi (existing)
    // ==========================================
    $input_data = file_get_contents("php://input");
    $data = json_decode($input_data, true);

    if (!$data) {
        throw new Exception('Data tidak valid atau kosong.');
    }

    // Ambil data dengan fallback 0 agar tidak error null
    $id = isset($data['id']) ? intval($data['id']) : 0;
    $distance = isset($data['distance']) ? floatval($data['distance']) : 0;
    $waterUsed = isset($data['waterUsed']) ? floatval($data['waterUsed']) : 0;
    $battery = isset($data['battery']) ? floatval($data['battery']) : 100;
    
    // Pastikan path dan spray menjadi string JSON
    $pathData = isset($data['path']) ? json_encode($data['path']) : "[]";
    $sprayData = isset($data['sprayPoints']) ? json_encode($data['sprayPoints']) : "[]";

    $today = date('Y-m-d');

    if ($id > 0) {
        // ==========================================
        // 1. UPDATE SESI (Sesi yang sedang berjalan)
        // ==========================================
        $stmt = $conn->prepare("UPDATE daily_logs SET distance_m=?, water_used_ml=?, battery_percent=?, path_data=?, spray_data=? WHERE id=?");
        if (!$stmt) throw new Exception("Gagal prepare update: " . $conn->error);
        
        $stmt->bind_param("dddssi", $distance, $waterUsed, $battery, $pathData, $sprayData, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'id' => $id]);
        } else {
            throw new Exception("Gagal execute update: " . $stmt->error);
        }
        $stmt->close();

    } else {
        // ==========================================
        // 2. INSERT SESI BARU (Maksimal 5 Per Hari)
        // ==========================================
        $checkQuery = $conn->query("SELECT id FROM daily_logs WHERE DATE(log_date) = '$today' ORDER BY log_date ASC");
        if (!$checkQuery) throw new Exception("Gagal check query: " . $conn->error);
        
        // Batasi 5 data per hari. Jika sudah 5, hapus data paling lama HARI INI
        if ($checkQuery->num_rows >= 5) {
            $limitToDelete = $checkQuery->num_rows - 4; 
            $conn->query("DELETE FROM daily_logs WHERE DATE(log_date) = '$today' ORDER BY log_date ASC LIMIT $limitToDelete");
        }

        $stmt = $conn->prepare("INSERT INTO daily_logs (log_date, distance_m, water_used_ml, battery_percent, path_data, spray_data) VALUES (NOW(), ?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Gagal prepare insert: " . $conn->error);
        
        $stmt->bind_param("dddss", $distance, $waterUsed, $battery, $pathData, $sprayData);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            echo json_encode(['status' => 'success', 'new_id' => $new_id]);
        } else {
            throw new Exception("Gagal execute insert: " . $stmt->error);
        }
        $stmt->close();
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>