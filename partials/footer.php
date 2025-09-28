<?php
/** footer.php : Closes layout */
// Determine the correct path to assets based on current directory
$currentDir = dirname($_SERVER['PHP_SELF']);
$assetsPath = '';
if (strpos($currentDir, '/admin') !== false) {
    $assetsPath = '../assets';
} else {
    $assetsPath = 'assets';
}
?>
</div><!-- /#app -->
<footer class="mt-auto py-6 text-center text-xs text-slate-500">&copy; <?= date('Y') ?> Pageant Tabulation System</footer>
<script src="<?= $assetsPath ?>/js/toast.js"></script>
<script src="<?= $assetsPath ?>/js/scoring.js"></script>
</body>
</html>
