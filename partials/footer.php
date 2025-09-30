
<?php
/** footer.php : Closes layout */
// Dynamically determine the correct path to assets based on current directory and project folder
$projectBase = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
	$scriptPath = $_SERVER['SCRIPT_NAME'];
	$parts = explode('/', trim($scriptPath, '/'));
	if (count($parts) > 1) {
		$projectBase = '/' . $parts[0];
	}
}
$assetsPath = $projectBase . '/assets';
?>
</div><!-- /#app -->
<footer class="mt-auto py-6 text-center text-xs text-slate-500">&copy; <?= date('Y') ?> Pageant Tabulation System</footer>
<script src="<?= $assetsPath ?>/js/toast.js"></script>
<script src="<?= $assetsPath ?>/js/scoring.js"></script>
</body>
</html>
