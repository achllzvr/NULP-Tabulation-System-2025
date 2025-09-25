<?php
/**
 * Landing Page - Entry point for all users
 * Converted from: components/LandingPage.tsx
 * Preserves exact Tailwind classes and layout structure
 */

require_once 'classes/Util.php';
require_once 'classes/SessionManager.php';
require_once 'classes/PublicService.php';

// Handle role selection and redirects
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'admin':
                // Redirect to login for admin access
                header('Location: login.php');
                exit;
                
            case 'judge':
                // Redirect to login for judge access
                header('Location: login.php');
                exit;
                
            case 'public':
                $publicCode = $_POST['pageant_code'] ?? '';
                if (empty(trim($publicCode))) {
                    $error = 'Please enter a pageant code';
                } else {
                    $publicService = new PublicService();
                    $cleanCode = strtoupper(trim($publicCode));
                    
                    if ($publicService->validatePageantCode($cleanCode)) {
                        header('Location: public_prelim.php?code=' . urlencode($cleanCode));
                        exit;
                    } else {
                        $error = 'Invalid pageant code. Please check and try again.';
                    }
                }
                break;
        }
    }
}

$pageTitle = 'Pageant Tabulation System';
include 'partials/head.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="flex items-center justify-center mb-6">
                <!-- Crown SVG Icon -->
                <svg class="w-12 h-12 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l4 6 4-7 4 7 4-6v18H5V3z"/>
                </svg>
                <h1 class="text-4xl font-bold text-gray-900">Pageant Tabulation System</h1>
            </div>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Professional scoring and management system for beauty pageants, 
                talent competitions, and similar events requiring real-time judging and public displays.
            </p>
        </div>

        <!-- Error Display -->
        <?php if (isset($error)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?= esc($error) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Role Selection Cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <!-- Admin Portal -->
            <form method="POST" class="contents">
                <input type="hidden" name="action" value="admin">
                <button type="submit" class="bg-white hover:shadow-lg transition-shadow border-2 hover:border-blue-300 rounded-lg shadow text-left w-full">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <!-- Shield SVG Icon -->
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Admin Portal</h3>
                        <p class="text-gray-600 text-sm mb-4">
                            Manage participants, judges, rounds, and control the entire pageant flow
                        </p>
                    </div>
                    <div class="px-6 pb-6">
                        <div class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md text-center">
                            Access Admin Dashboard
                        </div>
                    </div>
                </button>
            </form>

            <!-- Judge Login -->
            <form method="POST" class="contents">
                <input type="hidden" name="action" value="judge">
                <button type="submit" class="bg-white hover:shadow-lg transition-shadow border-2 hover:border-amber-300 rounded-lg shadow text-left w-full">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <!-- Users SVG Icon -->
                            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Judge Portal</h3>
                        <p class="text-gray-600 text-sm mb-4">
                            Submit scores for active rounds and view your judging history
                        </p>
                    </div>
                    <div class="px-6 pb-6">
                        <div class="w-full bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-md text-center">
                            Judge Login
                        </div>
                    </div>
                </button>
            </form>

            <!-- Public Results -->
            <div class="bg-white hover:shadow-lg transition-shadow border-2 hover:border-green-300 rounded-lg shadow">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <!-- Eye SVG Icon -->
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Public Results</h3>
                    <p class="text-gray-600 text-sm mb-4">
                        View live leaderboards, final results, and award announcements
                    </p>
                </div>
                <div class="px-6 pb-6">
                    <button onclick="openPublicModal()" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-md text-center">
                        View Public Display
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Info -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- System Features -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">System Features</h3>
                </div>
                <div class="px-6 py-4">
                    <ul class="space-y-2 text-gray-600">
                        <li>• Real-time scoring and leaderboards</li>
                        <li>• Multi-round competition support</li>
                        <li>• Automated tie resolution</li>
                        <li>• Public display screens</li>
                        <li>• Comprehensive audit trails</li>
                    </ul>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">System Status</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-2 text-gray-600">
                        <p><strong>Status:</strong> <span class="text-green-600">Active</span></p>
                        <p><strong>Access:</strong> Login required for all functions</p>
                        <p class="text-sm text-gray-500 mt-3">
                            All features require proper authentication. Contact your administrator for access.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Public Code Modal -->
<div id="publicModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-900">Enter Pageant Code</h3>
            <p class="text-sm text-gray-600 mt-1">
                Please enter the pageant code to view public results and leaderboards.
            </p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="public">
            <div class="mb-4">
                <label for="pageant-code" class="block text-sm font-medium text-gray-700">Pageant Code</label>
                <input type="text" 
                       id="pageant-code" 
                       name="pageant_code"
                       placeholder="Enter pageant code"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm uppercase"
                       style="text-transform: uppercase;">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-md">
                    View Results
                </button>
                <button type="button" onclick="closePublicModal()" class="flex-1 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 py-2 px-4 rounded-md">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPublicModal() {
    document.getElementById('publicModal').classList.remove('hidden');
    document.getElementById('publicModal').classList.add('flex');
}

function closePublicModal() {
    document.getElementById('publicModal').classList.add('hidden');
    document.getElementById('publicModal').classList.remove('flex');
}

// Close modal on outside click
document.getElementById('publicModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePublicModal();
    }
});
</script>

<?php include 'partials/footer.php'; ?>