<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['adminID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Include the database class file
require_once('../classes/database.php');

// Create an instance of the database class
$con = new database();
$conn = $con->opencon();

// Set content type to JSON
header('Content-Type: application/json');

if (!isset($_GET['round_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Round ID required']);
    exit();
}

$round_id = intval($_GET['round_id']);

try {
    // Get current criteria assignments for the round
    $stmt = $conn->prepare("
        SELECT 
            rc.criterion_id,
            rc.weight,
            rc.max_score,
            rc.display_order,
            c.name as criterion_name
        FROM round_criteria rc
        JOIN criteria c ON rc.criterion_id = c.id
        WHERE rc.round_id = ?
        ORDER BY rc.display_order
    ");
    $stmt->bind_param("i", $round_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'assignments' => $assignments
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>