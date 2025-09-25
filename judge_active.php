<?php
/**
 * Judge Active Round - Scoring Interface
 * Converted from: components/judge/JudgeActiveRound.tsx
 * Preserves exact Tailwind classes and layout structure
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Simple auth check for demo
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'judge') {
    header('Location: index.php');
    exit;
}

require_once 'classes/Util.php';
require_once 'classes/AuthService.php';
require_once 'classes/PageantService.php';
require_once 'classes/ParticipantService.php';
require_once 'classes/Services.php';

// Initialize services
$authService = new AuthService();
$pageantService = new PageantService();
$participantService = new ParticipantService();
$roundService = new RoundService();
$scoreService = new ScoreService();

// Get current user
$currentUser = $authService->currentUser();

// Handle score submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'save_scores') {
    try {
        $roundId = $_POST['round_id'] ?? null;
        $participantId = $_POST['participant_id'] ?? null;
        $scores = $_POST['scores'] ?? [];
        
        if ($roundId && $participantId && !empty($scores)) {
            foreach ($scores as $criterionId => $score) {
                $scoreService->saveScore($roundId, $criterionId, $participantId, $currentUser['id'], floatval($score));
            }
            $message = 'Scores saved successfully';
        } else {
            $error = 'Invalid score data provided';
        }
    } catch (Exception $e) {
        $error = 'Failed to save scores: ' . $e->getMessage();
    }
}

try {
    // Get pageant data
    $pageantCode = 'NULP2025';
    $pageant = $pageantService->getByCode($pageantCode);
    
    if ($pageant) {
        $pageantId = $pageant['id'];
        
        // Get active round
        $activeRound = $roundService->currentOpen($pageantId);
        
        // Get participants for this pageant
        $participants = $participantService->list($pageantId);
        
        // Get criteria for the active round (stub - implement as needed)
        $criteria = [
            ['id' => 1, 'name' => 'Appearance', 'weight' => 30],
            ['id' => 2, 'name' => 'Poise & Confidence', 'weight' => 35],
            ['id' => 3, 'name' => 'Communication Skills', 'weight' => 35]
        ];
    } else {
        $error = 'No active pageant found';
        $activeRound = null;
        $participants = [];
        $criteria = [];
    }
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
    $activeRound = null;
    $participants = [];
    $criteria = [];
}

$selectedParticipant = $_GET['participant'] ?? ($_POST['participant'] ?? '');
$selectedParticipantData = null;
if ($selectedParticipant) {
    $selectedParticipantData = array_values(array_filter($participants, fn($p) => $p['id'] === $selectedParticipant))[0] ?? null;
}

$pageTitle = 'Judge Portal - Active Round';
include 'partials/head.php';
include 'partials/nav_judge.php';
?>

<div class="p-6 max-w-4xl mx-auto">
    <?php if (!$activeRound || $activeRound['status'] !== 'OPEN'): ?>
        <!-- No Active Round -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-8 text-center">
                <!-- Clock SVG Icon -->
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold mb-2">No Active Round</h3>
                <p class="text-gray-600">
                    There is currently no active round for judging. Please wait for the administrator to open a round.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <!-- Success Message -->
            <?php if (isset($message)): ?>
                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?= Util::escape($message) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Participant Selection -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Select Participant to Score</h3>
                </div>
                <div class="px-6 py-4">
                    <form method="GET" id="participantForm">
                        <select name="participant" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" onchange="document.getElementById('participantForm').submit()">
                            <option value="">Choose a participant to score</option>
                            <?php 
                            // Sort participants by division then number
                            usort($participants, function($a, $b) {
                                if ($a['division'] !== $b['division']) {
                                    return strcmp($a['division'], $b['division']);
                                }
                                return (int)$a['number_label'] - (int)$b['number_label'];
                            });
                            
                            foreach ($participants as $participant): 
                                if (!$participant['is_active']) continue;
                                $selected = $participant['id'] === $selectedParticipant ? 'selected' : '';
                            ?>
                                <option value="<?= Util::escape($participant['id']) ?>" <?= $selected ?>>
                                    <?= Util::escape($participant['division']) ?> - #<?= Util::escape($participant['number_label']) ?> <?= Util::escape($participant['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <?php if ($selectedParticipantData): ?>
                        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                            <h4 class="font-medium">#<?= Util::escape($selectedParticipantData['number_label']) ?> <?= Util::escape($selectedParticipantData['full_name']) ?></h4>
                            <p class="text-sm text-gray-600 mt-1">
                                <strong>Division:</strong> <?= Util::escape($selectedParticipantData['division']) ?>
                            </p>
                            <?php if (!empty($selectedParticipantData['advocacy'])): ?>
                                <p class="text-sm text-gray-600 mt-1">
                                    <strong>Advocacy:</strong> <?= Util::escape($selectedParticipantData['advocacy']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Scoring Interface -->
            <?php if ($selectedParticipant): ?>
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Score Criteria</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                Live Scoring
                            </span>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <form method="POST" id="scoringForm">
                            <input type="hidden" name="action" value="save_scores">
                            <input type="hidden" name="participant" value="<?= Util::escape($selectedParticipant) ?>">
                            
                            <div class="space-y-6">
                                <?php foreach ($criteria as $criterion): ?>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="font-medium"><?= Util::escape($criterion['name']) ?></h4>
                                                <p class="text-sm text-gray-600">Weight: <?= $criterion['weight'] ?>%</p>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-2xl font-bold text-blue-600" id="score-display-<?= $criterion['id'] ?>">5.0</span>
                                                <p class="text-xs text-gray-500">out of 10</p>
                                            </div>
                                        </div>
                                        
                                        <input type="range" 
                                               name="score_<?= Util::escape($criterion['id']) ?>"
                                               min="1" 
                                               max="10" 
                                               step="0.1" 
                                               value="5.0"
                                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                                               onchange="updateScore('<?= $criterion['id'] ?>', this.value)">
                                        
                                        <div class="flex justify-between text-xs text-gray-500">
                                            <span>1 (Poor)</span>
                                            <span>5 (Average)</span>
                                            <span>10 (Excellent)</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="flex gap-3 pt-4">
                                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md flex items-center justify-center">
                                        <!-- Save SVG Icon -->
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                        </svg>
                                        Save Scores
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Round Submission -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                        <!-- CheckCircle SVG Icon -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Submit Round
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium mb-2">Scoring Progress</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">Participants Scored:</p>
                                    <p class="font-semibold">2 / <?= count(array_filter($participants, fn($p) => $p['is_active'])) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Current Round:</p>
                                    <p class="font-semibold"><?= Util::escape($activeRound['name']) ?></p>
                                </div>
                            </div>
                        </div>

                        <button onclick="submitRound()" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-md flex items-center justify-center">
                            <!-- CheckCircle SVG Icon -->
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Submit All Scores for This Round
                        </button>

                        <p class="text-xs text-gray-500 text-center">
                            Once submitted, you cannot modify your scores for this round.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateScore(criterionId, value) {
    const display = document.getElementById('score-display-' + criterionId);
    const score = parseFloat(value);
    display.textContent = score.toFixed(1);
    
    // Update color based on score
    display.className = 'text-2xl font-bold ';
    if (score >= 8) {
        display.className += 'text-green-600';
    } else if (score >= 6) {
        display.className += 'text-yellow-600';
    } else {
        display.className += 'text-red-600';
    }
}

function submitRound() {
    if (confirm('Are you sure you want to submit all scores for this round? You cannot modify them afterwards.')) {
        alert('Round submitted successfully! Thank you for your participation.');
        // In production, this would make an API call
    }
}

// Style the range sliders
document.addEventListener('DOMContentLoaded', function() {
    const sliders = document.querySelectorAll('.slider');
    sliders.forEach(slider => {
        slider.style.background = 'linear-gradient(to right, #fee2e2 0%, #fef3c7 50%, #d1fae5 100%)';
    });
});
</script>

<?php include 'partials/footer.php'; ?>