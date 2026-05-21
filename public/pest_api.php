<?php
// ==========================================================
// API Pest Detection
// Endpoint:
//   POST   pest_api.php                  -> Simpan deteksi hama baru
//   GET    pest_api.php?session_id=X     -> Ambil deteksi per sesi
//   GET    pest_api.php?all=1            -> Ambil semua deteksi (riwayat)
//   DELETE pest_api.php?id=X             -> Hapus deteksi
// ==========================================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require __DIR__ . '/../src/Config/db.php';
require __DIR__ . '/../src/Auth/auth.php';

// Wajib login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ============== POST : Simpan deteksi hama baru ==============
    if ($method === 'POST') {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!$data) throw new Exception('Body JSON tidak valid.');

        $session_id = isset($data['session_id']) && $data['session_id'] > 0 ? intval($data['session_id']) : null;
        $pest_name  = isset($data['pest_name']) ? trim($data['pest_name']) : '';
        $pest_type  = isset($data['pest_type']) ? trim($data['pest_type']) : null;
        $severity   = isset($data['severity']) && in_array($data['severity'], ['low','medium','high']) ? $data['severity'] : 'medium';
        $image_url  = isset($data['image_url']) ? trim($data['image_url']) : null;
        $map_x      = isset($data['map_x']) ? floatval($data['map_x']) : null;
        $map_y      = isset($data['map_y']) ? floatval($data['map_y']) : null;
        $map_z      = isset($data['map_z']) ? floatval($data['map_z']) : 0;
        $latitude   = isset($data['latitude'])  && $data['latitude']  !== '' ? floatval($data['latitude'])  : null;
        $longitude  = isset($data['longitude']) && $data['longitude'] !== '' ? floatval($data['longitude']) : null;
        $notes      = isset($data['notes']) ? trim($data['notes']) : null;

        if ($pest_name === '') throw new Exception('pest_name wajib diisi.');

        $stmt = $conn->prepare("
            INSERT INTO pest_detections
              (session_id, pest_name, pest_type, severity, image_url,
               map_x, map_y, map_z, latitude, longitude, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) throw new Exception("Gagal prepare: " . $conn->error);

        // Bind types: i (session_id), s,s,s,s (pest_name, pest_type, severity, image_url),
        //             d,d,d,d,d (map_x, map_y, map_z, latitude, longitude), s (notes)
        $stmt->bind_param(
            "issssddddds",
            $session_id, $pest_name, $pest_type, $severity, $image_url,
            $map_x, $map_y, $map_z, $latitude, $longitude, $notes
        );

        if (!$stmt->execute()) throw new Exception("Gagal insert: " . $stmt->error);
        $new_id = $conn->insert_id;
        $stmt->close();

        echo json_encode(['status' => 'success', 'id' => $new_id]);
        exit;
    }

    // ============== GET : Ambil daftar ==============
    if ($method === 'GET') {
        if (!hasPermission('view_pest_detection')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden: butuh view_pest_detection']);
            exit;
        }
        if (isset($_GET['all'])) {
            $sql = "SELECT p.*, dl.log_date
                      FROM pest_detections p
                      LEFT JOIN daily_logs dl ON dl.id = p.session_id
                     ORDER BY p.detected_at DESC
                     LIMIT 500";
            $res = $conn->query($sql);
        } else {
            $sid = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
            $stmt = $conn->prepare("SELECT * FROM pest_detections WHERE session_id = ? ORDER BY detected_at DESC");
            $stmt->bind_param("i", $sid);
            $stmt->execute();
            $res = $stmt->get_result();
        }
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    // ============== DELETE : Hapus 1 deteksi ==============
    if ($method === 'DELETE') {
        if (!hasPermission('delete_pest_detection')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden: butuh delete_pest_detection']);
            exit;
        }
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) throw new Exception('id tidak valid.');
        $stmt = $conn->prepare("DELETE FROM pest_detections WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        exit;
    }

    throw new Exception('Method tidak didukung.');

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
