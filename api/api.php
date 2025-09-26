<?php
/**
 * Main API endpoint for NULP Tabulation System
 * Single switch on action parameter with JSON responses
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Set JSON content type
header('Content-Type: application/json');

// CORS headers (adjust as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get action parameter
$action = $_REQUEST['action'] ?? '';

// Response helper function
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Error response helper
function json_error($message, $status = 400) {
    json_response(['error' => $message], $status);
}

// Require authentication for most actions
$public_actions = ['login']; // Add actions that don't require auth
if (!in_array($action, $public_actions)) {
    if (!isset($_SESSION['user_id'])) {
        json_error('Authentication required', 401);
    }
}

// Main action switch
switch ($action) {
    case 'open_round':
        auth_require_role(['admin', 'organizer']);
        $round_id = $_POST['round_id'] ?? 0;
        
        if (empty($round_id)) {
            json_error('Round ID is required');
        }
        
        $success = rounds_open($round_id);
        json_response(['success' => $success]);
        break;
        
    case 'close_round':
        auth_require_role(['admin', 'organizer']);
        $round_id = $_POST['round_id'] ?? 0;
        
        if (empty($round_id)) {
            json_error('Round ID is required');
        }
        
        $success = rounds_close($round_id);
        json_response(['success' => $success]);
        break;
        
    case 'submit_score':
        auth_require_role(['judge']);
        $participant_id = $_POST['participant_id'] ?? 0;
        $criteria_id = $_POST['criteria_id'] ?? 0;
        $round_id = $_POST['round_id'] ?? 0;
        $score = $_POST['score'] ?? 0;
        
        if (empty($participant_id) || empty($criteria_id) || empty($round_id) || empty($score)) {
            json_error('All score fields are required');
        }
        
        $judge_id = $_SESSION['user_id'];
        $success = scores_save($judge_id, $participant_id, $criteria_id, $round_id, $score);
        json_response(['success' => $success]);
        break;
        
    case 'leaderboard':
        $round_id = $_GET['round_id'] ?? 0;
        
        if (empty($round_id)) {
            json_error('Round ID is required');
        }
        
        $leaderboard = scores_aggregate_round($round_id);
        json_response(['leaderboard' => $leaderboard]);
        break;
        
    // TODO: Add more actions as needed
    case 'get_participants':
        // TODO: Implement participant listing
        json_response(['participants' => pageant_list_participants()]);
        break;
        
    case 'get_judges':
        // TODO: Implement judge listing
        json_error('Not implemented yet');
        break;
        
    case 'set_award_winner':
        // TODO: Implement award winner setting
        auth_require_role(['admin', 'organizer']);
        json_error('Not implemented yet');
        break;
        
    case 'get_scoring_progress':
        // TODO: Implement scoring progress
        $round_id = $_GET['round_id'] ?? 0;
        if (!empty($round_id)) {
            $progress = scores_get_progress($round_id);
            json_response(['progress' => $progress]);
        } else {
            json_error('Round ID is required');
        }
        break;
        
    default:
        json_error('Invalid action', 404);
}