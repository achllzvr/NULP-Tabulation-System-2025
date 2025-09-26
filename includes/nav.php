<?php
$current_user = get_current_app_user();
$current_pageant = get_current_app_pageant();
?>

<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <a href="/dashboard.php" class="text-2xl font-bold text-blue-600">
                        🏆 NULP Tabulation
                    </a>
                </div>
                
                <?php if ($current_pageant): ?>
                <div class="ml-6">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?= esc($current_pageant['name'] ?? 'Unknown Pageant') ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($current_user): ?>
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-600">
                    Welcome, <?= esc($current_user['full_name']) ?>
                    <?php if (isset($current_user['role'])): ?>
                    <span class="text-xs text-gray-500">(<?= esc(ucfirst($current_user['role'])) ?>)</span>
                    <?php endif; ?>
                </div>
                
                <!-- Navigation Menu based on role -->
                <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                <div class="hidden md:flex space-x-4">
                    <a href="/dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="/participants.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Participants</a>
                    <a href="/judges.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Judges</a>
                    <a href="/rounds.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Rounds</a>
                    <a href="/live_control.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Live Control</a>
                    <a href="/awards.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Awards</a>
                </div>
                <?php elseif (($current_user['role'] ?? '') === 'judge'): ?>
                <div class="hidden md:flex space-x-4">
                    <a href="/dashboard.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                    <a href="/rounds.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Active Round</a>
                </div>
                <?php endif; ?>
                
                <a href="/logout.php" class="btn-secondary">Logout</a>
            </div>
            <?php else: ?>
            <div class="flex items-center">
                <a href="/login.php" class="btn-primary">Login</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>