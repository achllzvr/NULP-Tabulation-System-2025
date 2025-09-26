<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

// Get action from request
$action = $_REQUEST['action'] ?? '';

// Validation helper
function validate_required($fields, $data) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return "Missing required field: $field";
        }
    }
    return null;
}

// Response helper
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Error response helper
function error_response($message, $status = 400) {
    json_response(['error' => $message, 'success' => false], $status);
}

// Success response helper
function success_response($data = [], $message = 'Success') {
    json_response(['success' => true, 'message' => $message, 'data' => $data]);
}

// Main action switch
try {
    switch ($action) {
        case 'login':
            $error = validate_required(['identifier', 'password'], $_POST);
            if ($error) error_response($error);
            
            $success = $auth->login_user($_POST['identifier'], $_POST['password']);
            if ($success) {
                success_response($auth->get_current_user(), 'Login successful');
            } else {
                error_response('Invalid credentials', 401);
            }
            break;

        case 'logout':
            $auth->logout_user();
            success_response([], 'Logout successful');
            break;

        case 'get_current_user':
            $user = $auth->get_current_user();
            if ($user) {
                success_response($user);
            } else {
                error_response('Not logged in', 401);
            }
            break;

        case 'set_pageant':
            ensure_logged_in();
            $error = validate_required(['pageant_id'], $_POST);
            if ($error) error_response($error);
            
            $success = $pageant->set_current_pageant((int)$_POST['pageant_id']);
            if ($success) {
                success_response($pageant->get_current_pageant(), 'Pageant selected');
            } else {
                error_response('Invalid pageant ID');
            }
            break;

        case 'get_pageant_participants':
            ensure_logged_in();
            ensure_pageant_selected();
            $currentPageant = get_current_pageant();
            $participants = $pageant->get_pageant_participants($currentPageant['id']);
            success_response($participants);
            break;

        case 'get_rounds':
            ensure_logged_in();
            ensure_pageant_selected();
            $currentPageant = get_current_pageant();
            $roundList = $pageant->list_pageant_rounds($currentPageant['id']);
            success_response($roundList);
            break;

        case 'open_round':
            ensure_logged_in();
            $error = validate_required(['round_id'], $_POST);
            if ($error) error_response($error);
            
            $success = $rounds->open_round((int)$_POST['round_id']);
            if ($success) {
                success_response([], 'Round opened successfully');
            } else {
                error_response('Failed to open round');
            }
            break;

        case 'close_round':
            ensure_logged_in();
            $error = validate_required(['round_id'], $_POST);
            if ($error) error_response($error);
            
            $success = $rounds->close_round((int)$_POST['round_id']);
            if ($success) {
                success_response([], 'Round closed successfully');
            } else {
                error_response('Failed to close round');
            }
            break;

        case 'submit_score':
            ensure_logged_in();
            $error = validate_required(['round_id', 'criteria_id', 'participant_id', 'score'], $_POST);
            if ($error) error_response($error);
            
            $currentUser = get_current_user();
            $success = $scores->upsert_score(
                (int)$_POST['round_id'],
                (int)$_POST['criteria_id'],
                (int)$_POST['participant_id'],
                (int)$currentUser['id'],
                (float)$_POST['score']
            );
            
            if ($success) {
                success_response([], 'Score submitted successfully');
            } else {
                error_response('Failed to submit score');
            }
            break;

        case 'get_round_scores':
            ensure_logged_in();
            $error = validate_required(['round_id'], $_GET);
            if ($error) error_response($error);
            
            $aggregatedScores = $scores->aggregate_round_scores((int)$_GET['round_id']);
            success_response($aggregatedScores);
            break;

        case 'get_awards':
            ensure_logged_in();
            ensure_pageant_selected();
            $currentPageant = get_current_pageant();
            $awardsList = $awards->list_awards($currentPageant['id']);
            success_response($awardsList);
            break;

        case 'set_award_results':
            ensure_logged_in();
            $error = validate_required(['award_id', 'participant_ids'], $_POST);
            if ($error) error_response($error);
            
            $participantIds = json_decode($_POST['participant_ids'], true);
            if (!is_array($participantIds)) {
                error_response('Invalid participant IDs format');
            }
            
            $success = $awards->set_manual_award_results((int)$_POST['award_id'], $participantIds);
            if ($success) {
                success_response([], 'Award results saved successfully');
            } else {
                error_response('Failed to save award results');
            }
            break;

        default:
            error_response('Unknown action', 404);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_response('Internal server error', 500);
}