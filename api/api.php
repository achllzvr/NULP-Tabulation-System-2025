<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

// Set JSON content type
header('Content-Type: application/json');

// Handle CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Function to send JSON response
function json_response(bool $success, $data = null, string $error = null, int $status = 200): void {
    http_response_code($status);
    $response = ['success' => $success];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    echo json_encode($response);
    exit;
}

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

// Get action from POST data or GET parameter
$action = $data['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                json_response(false, null, 'Username and password are required', 400);
            }
            
            $result = auth_login($username, $password);
            
            if ($result['success']) {
                json_response(true, ['user' => $result['user']], null, 200);
            } else {
                json_response(false, null, $result['error'], 401);
            }
            break;

        case 'logout':
            auth_logout();
            json_response(true, ['message' => 'Logged out successfully']);
            break;

        case 'open_round':
            if (!isset($_SESSION['user_id']) || !auth_is_admin()) {
                json_response(false, null, 'Admin access required', 403);
            }
            
            $round_id = (int)($data['round_id'] ?? 0);
            if ($round_id <= 0) {
                json_response(false, null, 'Invalid round ID', 400);
            }
            
            $result = rounds_open($round_id);
            json_response($result['success'], $result['success'] ? ['message' => $result['message']] : null, $result['error'] ?? null);
            break;

        case 'close_round':
            if (!isset($_SESSION['user_id']) || !auth_is_admin()) {
                json_response(false, null, 'Admin access required', 403);
            }
            
            $round_id = (int)($data['round_id'] ?? 0);
            if ($round_id <= 0) {
                json_response(false, null, 'Invalid round ID', 400);
            }
            
            $result = rounds_close($round_id);
            json_response($result['success'], $result['success'] ? ['message' => $result['message']] : null, $result['error'] ?? null);
            break;

        case 'submit_score':
            if (!isset($_SESSION['user_id'])) {
                json_response(false, null, 'Login required', 401);
            }
            
            $judge_id = (int)$_SESSION['user_id'];
            $participant_id = (int)($data['participant_id'] ?? 0);
            $round_id = (int)($data['round_id'] ?? 0);
            $criteria_id = (int)($data['criteria_id'] ?? 0);
            $score = (float)($data['score'] ?? 0);
            
            if ($participant_id <= 0 || $round_id <= 0 || $criteria_id <= 0 || $score <= 0) {
                json_response(false, null, 'Invalid score data', 400);
            }
            
            $result = scores_save($judge_id, $participant_id, $round_id, $criteria_id, $score);
            json_response($result['success'], $result['success'] ? ['message' => $result['message']] : null, $result['error'] ?? null);
            break;

        case 'leaderboard':
            $round_id = (int)($data['round_id'] ?? $_GET['round_id'] ?? 0);
            
            if ($round_id <= 0) {
                json_response(false, null, 'Round ID required', 400);
            }
            
            $leaderboard = scores_get_leaderboard($round_id);
            json_response(true, ['leaderboard' => $leaderboard]);
            break;

        case 'set_advancements_top5':
            if (!isset($_SESSION['user_id']) || !auth_is_admin()) {
                json_response(false, null, 'Admin access required', 403);
            }
            
            // TODO: Implement advancement logic
            json_response(false, null, 'Advancement feature not yet implemented', 501);
            break;

        case 'set_award_result_manual':
            if (!isset($_SESSION['user_id']) || !auth_is_admin()) {
                json_response(false, null, 'Admin access required', 403);
            }
            
            $pageant_id = (int)($_SESSION['pageant_id'] ?? 0);
            $awards_data = $data['awards'] ?? [];
            
            if ($pageant_id <= 0) {
                json_response(false, null, 'No pageant selected', 400);
            }
            
            if (empty($awards_data)) {
                json_response(false, null, 'Awards data required', 400);
            }
            
            $result = awards_set_manual_winners($pageant_id, $awards_data);
            json_response($result['success'], $result['success'] ? ['message' => $result['message']] : null, $result['error'] ?? null);
            break;

        case 'resolve_tie_group':
            if (!isset($_SESSION['user_id']) || !auth_is_admin()) {
                json_response(false, null, 'Admin access required', 403);
            }
            
            // TODO: Implement tie resolution logic
            json_response(false, null, 'Tie resolution feature not yet implemented', 501);
            break;

        case 'health':
            // Simple health check endpoint
            json_response(true, [
                'status' => 'healthy',
                'timestamp' => date('c'),
                'version' => '1.0.0'
            ]);
            break;

        default:
            json_response(false, null, 'Invalid action specified', 400);
    }

} catch (Exception $e) {
    // Log error in production
    error_log("API Error: " . $e->getMessage());
    
    // Return generic error message
    json_response(false, null, 'An internal error occurred', 500);
}