<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageant_code = $_GET['code'] ?? '';
$title = 'Awards - Public View';
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen bg-gradient-to-br from-yellow-50 to-orange-100">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">🏆 Awards Ceremony</h1>
            <p class="text-xl text-gray-600">Pageant Code: <?= esc($pageant_code) ?></p>
        </div>
        
        <div class="card text-center py-12">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Awards and Recognition</h3>
            <p class="text-gray-600">Award winners and special recognitions will be displayed here.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>