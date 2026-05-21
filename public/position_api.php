<?php
// ==========================================================
// API Robot Position (untuk tab Demografi)
// Endpoint:
//   POST   position_api.php                  -> Simpan posisi (start/stop/respawn/dst)
//   GET    position_api.php?all=1            -> Ambil semua posisi (default 500 terakhir)
//   GET    position_api.php?event=start      -> Filter berdasarkan event_type
//   GET    position_api.php?session_id=X     -> Ambil posisi per sesi
//   DELETE position_api.php?id=X             -> Hapus 1 record
// ==========================================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require __DIR__ . '/../src/Config/db.php';
require __DIR__ . '/../src/Auth/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$validEvents = ['start','stop','respawn','pause','manual'];

try {
    // ============== POST : Simpan posisi robot ==============
    if ($method === 'POST') {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!$data) throw new Exception('Body JSON tidak valid.');

        $session_id = isset($data['session_id']) && $data['session_id'] > 0 ? intval($data['session_id']) : null;
        $event_type = isset($data['event_type']) && in_array($data['event_type'], $validEvents) ? $data['event_type'] : 'manual';
        $map_x      = isset($data['map_x']) ? floatval($data['map_x']) : null;
        $map_y      = isset($data['map_y']) ? floatval($data['map_y']) : null;
        $map_z      = isset($data['map_z']) ? floatval($data['map_z']) : 0;
        $latitude   = isset($data['latitude'])  && $data['latitude']  !== '' ? floatval($data['latitude'])  : null;
        $longitude  = isset($data['longitude']) && $data['longitude'] !== '' ? floatval($data['longitude']) : null;
        $battery    = isset($data['battery_percent']) ? floatval($data['battery_percent']) : null;

        $stmt = $conn->prepare("
            INSERT INTO robot_positions
              (session_id, event_type, map_x, map_y, map_z, latitude, longitude, battery_percent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) throw new Exception("Gagal prepare: " . $conn->error);

        // Tipe: i (session) s (event) d d d d d d (6 angka)
        $stmt->bind_param(
            "isdddddd",
            $session_id, $event_type, $map_x, $map_y, $map_z,
            $latitude, $longitude, $battery
        );

        if (!$stmt->execute()) throw new Exception("Gagal insert: " . $stmt->error);
        $new_id = $conn->insert_id;
        $stmt->close();

        echo json_encode(['status' => 'success', 'id' => $new_id]);
        exit;
    }

    // ============== GET : Ambil daftar ==============
    if ($method === 'GET') {
        if (!hasPermission('view_demografi')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden: butuh view_demografi']);
            exit;
        }
        if (isset($_GET['session_id'])) {
            $sid  = intval($_GET['session_id']);
            $stmt = $conn->prepare("SELECT * FROM robot_positions WHERE session_id = ? ORDER BY recorded_at DESC");
            $stmt->bind_param("i", $sid);
            $stmt->execute();
            $res = $stmt->get_result();
        } elseif (isset($_GET['event']) && in_array($_GET['event'], $validEvents)) {
            $ev   = $_GET['event'];
            $stmt = $conn->prepare("
                SELECT p.*, dl.log_date
                  FROM robot_positions p
                  LEFT JOIN daily_logs dl ON dl.id = p.session_id
                 WHERE p.event_type = ?
                 ORDER BY p.recorded_at DESC
                 LIMIT 500
            ");
            $stmt->bind_param("s", $ev);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $sql = "SELECT p.*, dl.log_date
                      FROM robot_positions p
                      LEFT JOIN daily_logs dl ON dl.id = p.session_id
                     ORDER BY p.recorded_at DESC
                     LIMIT 500";
            $res = $conn->query($sql);
        }

        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    // ============== DELETE : Hapus 1 record ==============
    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) throw new Exception('id tidak valid.');
        $stmt = $conn->prepare("DELETE FROM robot_positions WHERE id = ?");
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
