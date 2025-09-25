<?php
/**
 * Admin Dashboard
 * Converted from: components/admin/AdminDashboard.tsx
 * Preserves exact Tailwind classes and layout structure
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Simple auth check for demo
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'classes/Util.php';
require_once 'classes/AuthService.php';
require_once 'classes/PageantService.php';
require_once 'classes/ParticipantService.php';
require_once 'classes/JudgeService.php';
require_once 'classes/Services.php';

// Initialize services
$authService = new AuthService();
$pageantService = new PageantService();
$participantService = new ParticipantService();
$judgeService = new JudgeService();
$roundService = new RoundService();

// Get current user
$currentUser = $authService->currentUser();

try {
    // For now, use a default pageant - in production this would come from user session or selection
    $pageantCode = 'NULP2025';
    $pageant = $pageantService->getByCode($pageantCode);
    
    if (!$pageant) {
        $pageant = $pageantService->getById($pageantId);
    } else {
        $pageantId = $pageant['id'];
    }
    
    // Get real data from services
    $participants = $participantService->list($pageantId);
    $judges = $judgeService->list($pageantId);
    $rounds = $roundService->list($pageantId);
    
    $state = [
        'currentUser' => $currentUser,
        'pageantCode' => $pageant['code'] ?? $pageantCode,
        'pageant' => $pageant,
        'participants' => $participants,
        'judges' => $judges,
        'rounds' => $rounds
    ];
    
} catch (Exception $e) {
    // Fallback to empty data if database issues
    $error = 'Database error: ' . $e->getMessage();
    $state = [
        'currentUser' => $currentUser,
        'pageantCode' => 'NULP2025',
        'participants' => [],
        'judges' => [],
        'rounds' => []
    ];
}

// Calculate stats
$stats = [
    'participants' => count($state['participants']),
    'activeParticipants' => count(array_filter($state['participants'], fn($p) => $p['is_active'])),
    'judges' => count($state['judges']),
    'roundsCompleted' => count(array_filter($state['rounds'], fn($r) => $r['status'] === 'CLOSED')),
    'totalRounds' => count($state['rounds'])
];

$prelimRound = array_values(array_filter($state['rounds'], fn($r) => $r['type'] === 'PRELIMINARY'))[0] ?? null;
$finalRound = array_values(array_filter($state['rounds'], fn($r) => $r['type'] === 'FINAL'))[0] ?? null;

// Setup progress
$setupProgress = [
    [
        'step' => 'Participants Added',
        'completed' => count($state['participants']) > 0,
        'count' => count($state['participants']),
        'href' => 'participants.php'
    ],
    [
        'step' => 'Judges Assigned',
        'completed' => count($state['judges']) >= 3,
        'count' => count($state['judges']),
        'href' => 'judges.php'
    ],
    [
        'step' => 'Preliminary Round',
        'completed' => $prelimRound && $prelimRound['status'] === 'CLOSED',
        'status' => $prelimRound ? $prelimRound['status'] : 'PENDING',
        'href' => 'live_control.php'
    ],
    [
        'step' => 'Final Round',
        'completed' => $finalRound && $finalRound['status'] === 'CLOSED',
        'status' => $finalRound ? $finalRound['status'] : 'PENDING',
        'href' => 'final_round.php'
    ]
];

$completedSteps = count(array_filter($setupProgress, fn($step) => $step['completed']));
$progressPercentage = ($completedSteps / count($setupProgress)) * 100;

$recentActivity = [
    ['action' => 'Preliminary round closed', 'time' => '2 hours ago', 'type' => 'success'],
    ['action' => '3 judges submitted scores', 'time' => '2 hours ago', 'type' => 'info'],
    ['action' => 'Round opened for judging', 'time' => '3 hours ago', 'type' => 'info'],
    ['action' => '4 participants added', 'time' => '1 day ago', 'type' => 'success']
];

$pageTitle = 'Admin Dashboard';
$currentPage = 'dashboard.php';
include 'partials/head.php';
include 'partials/nav_admin.php';
?>

<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-900">Dashboard</h2>
            <p class="mt-2 text-gray-600">Pageant setup progress and system overview</p>
        </div>

        <div class="space-y-6">
            <!-- Progress Overview -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                        <!-- TrendingUp SVG Icon -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        Setup Progress
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">Complete these steps to run your pageant</p>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span>Overall Progress</span>
                            <span class="font-medium"><?= $completedSteps ?>/<?= count($setupProgress) ?> steps</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: <?= $progressPercentage ?>%"></div>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-4 mt-6">
                            <?php foreach ($setupProgress as $step): ?>
                                <a href="<?= Util::escape($step['href']) ?>" class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <?php if ($step['completed']): ?>
                                            <!-- CheckCircle SVG Icon -->
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        <?php else: ?>
                                            <!-- Clock SVG Icon -->
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-medium"><?= Util::escape($step['step']) ?></p>
                                            <?php if (isset($step['count'])): ?>
                                                <p class="text-sm text-gray-600"><?= $step['count'] ?> added</p>
                                            <?php endif; ?>
                                            <?php if (isset($step['status'])): ?>
                                                <?php 
                                                $badgeClass = $step['status'] === 'CLOSED' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                                    <?= Util::escape($step['status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-500 hover:text-gray-700">
                                        <?= $step['completed'] ? 'View' : 'Setup' ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid md:grid-cols-4 gap-4">
                <!-- Participants -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Participants</p>
                            <div class="text-2xl font-bold text-gray-900"><?= $stats['participants'] ?></div>
                            <p class="text-xs text-gray-500"><?= $stats['activeParticipants'] ?> active</p>
                        </div>
                        <!-- Users SVG Icon -->
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Judges -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Judges</p>
                            <div class="text-2xl font-bold text-gray-900"><?= $stats['judges'] ?></div>
                            <p class="text-xs text-gray-500">Ready to score</p>
                        </div>
                        <!-- UserCheck SVG Icon -->
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Rounds -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Rounds</p>
                            <div class="text-2xl font-bold text-gray-900"><?= $stats['roundsCompleted'] ?>/<?= $stats['totalRounds'] ?></div>
                            <p class="text-xs text-gray-500">Completed</p>
                        </div>
                        <!-- Trophy SVG Icon -->
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Status</p>
                            <div class="text-2xl font-bold text-gray-900">
                                <?= ($finalRound && $finalRound['status'] === 'CLOSED') ? 'Complete' : 'In Progress' ?>
                            </div>
                            <p class="text-xs text-gray-500">Pageant status</p>
                        </div>
                        <!-- Crown SVG Icon -->
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l4 6 4-7 4 7 4-6v18H5V3z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Current Status & Recent Activity -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Current Status -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Current Status</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div>
                                    <p class="font-medium">Preliminary Round</p>
                                    <p class="text-sm text-gray-600">Judging completed</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    CLOSED
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium">Final Round</p>
                                    <p class="text-sm text-gray-600">Awaiting setup</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    PENDING
                                </span>
                            </div>

                            <?php if ($prelimRound && $prelimRound['status'] === 'CLOSED'): ?>
                                <a href="advancement.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-md">
                                    Review Top 5 Advancement
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-3">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="flex items-start gap-3">
                                    <div class="w-2 h-2 rounded-full mt-2 <?= $activity['type'] === 'success' ? 'bg-green-500' : 'bg-blue-500' ?>"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium"><?= Util::escape($activity['action']) ?></p>
                                        <p class="text-xs text-gray-500"><?= Util::escape($activity['time']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                    <p class="mt-1 text-sm text-gray-600">Common administrative tasks</p>
                </div>
                <div class="px-6 py-4">
                    <div class="grid md:grid-cols-3 gap-4">
                        <a href="participants.php" class="flex items-center justify-start px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <!-- Users SVG Icon -->
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                            Manage Participants
                        </a>
                        <a href="live_control.php" class="flex items-center justify-start px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <!-- Trophy SVG Icon -->
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            Control Rounds
                        </a>
                        <a href="leaderboard.php" class="flex items-center justify-start px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <!-- TrendingUp SVG Icon -->
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            View Leaderboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

</div> <!-- Close sidebar flex container -->

<?php include 'partials/footer.php'; ?>