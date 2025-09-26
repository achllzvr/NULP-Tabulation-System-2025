<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo/Brand -->
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <h1 class="text-xl font-bold text-gray-900">NULP Tabulation</h1>
                </div>
            </div>
            
            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="/dashboard.php" class="<?php echo is_current_page('dashboard.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Dashboard
                </a>
                
                <?php if (auth_is_admin()): ?>
                <a href="/participants.php" class="<?php echo is_current_page('participants.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Participants
                </a>
                <a href="/judges.php" class="<?php echo is_current_page('judges.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Judges
                </a>
                <a href="/rounds.php" class="<?php echo is_current_page('rounds.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Rounds
                </a>
                <?php endif; ?>
                
                <a href="/scoring.php" class="<?php echo is_current_page('scoring.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Scoring
                </a>
                <a href="/leaderboard.php" class="<?php echo is_current_page('leaderboard.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Leaderboard
                </a>
                
                <?php if (auth_is_admin()): ?>
                <a href="/advancement.php" class="<?php echo is_current_page('advancement.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Advancement
                </a>
                <a href="/final_round.php" class="<?php echo is_current_page('final_round.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Final Round
                </a>
                <a href="/awards.php" class="<?php echo is_current_page('awards.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Awards
                </a>
                <a href="/tie_resolution.php" class="<?php echo is_current_page('tie_resolution.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Ties
                </a>
                <a href="/settings.php" class="<?php echo is_current_page('settings.php') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'; ?> px-3 py-2 text-sm font-medium">
                    Settings
                </a>
                <?php endif; ?>
            </div>
            
            <!-- User Menu -->
            <div class="flex items-center">
                <span class="text-sm text-gray-700 mr-4">
                    <?php 
                    $user = auth_user();
                    echo $user ? esc($user['username']) : 'User'; 
                    ?>
                </span>
                <a href="/logout.php" class="text-sm text-red-600 hover:text-red-700">Logout</a>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu (hidden by default) -->
    <div class="md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <!-- Mobile navigation links would go here -->
        </div>
    </div>
</nav>