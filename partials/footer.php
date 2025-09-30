<?php
/** footer.php : Closes layout */
// Determine the correct path to assets based on current directory
// Always use absolute path for assets to avoid 404 errors
$assetsPath = '/assets';
?>
</div><!-- /#app -->
<footer class="mt-auto py-6 text-center text-xs text-slate-500">&copy; <?= date('Y') ?> Pageant Tabulation System</footer>
<script src="<?= $assetsPath ?>/js/toast.js"></script>
<script src="<?= $assetsPath ?>/js/scoring.js"></script>
</body>
</html>
