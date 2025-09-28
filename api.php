<?php
/**
 * API Handler for NULP Tabulation System
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

require_once __DIR__ . '/classes/database.php';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = new database();
    $conn = $db->opencon();
    
    switch ($action) {
        case 'ping':
            respond(['success' => true, 'pong' => true]);
            
        case 'public_pageant_meta':
            $pageant_id = (int)($_GET['pageant_id'] ?? 0);
            if (!$pageant_id) {
                respond(['success' => false, 'error' => 'pageant_id required'], 422);
            }
            
            $stmt = $conn->prepare("SELECT id, name, code FROM pageants WHERE id = ?");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                respond(['success' => false, 'error' => 'Pageant not found'], 404);
            }
            
            $pageant = $result->fetch_assoc();
            $stmt->close();
            
            $stmt = $conn->prepare("SELECT id, name, state, sequence FROM rounds WHERE pageant_id = ? ORDER BY sequence");
            $stmt->bind_param("i", $pageant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $rounds = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            respond([
                'success' => true,
                'pageant' => $pageant,
                'rounds' => $rounds
            ]);
            
        default:
            respond(['success' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Exception $e) {
    respond(['success' => false, 'error' => 'Server error', 'detail' => $e->getMessage()], 500);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
