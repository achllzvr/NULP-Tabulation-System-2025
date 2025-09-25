<?php
/**
 * Partial: Footer
 * Common scripts and closing HTML tags
 * Expected vars: $additionalScripts (optional)
 */

$additionalScripts = $additionalScripts ?? '';
?>

    <!-- Core JavaScript Files -->
    <script src="assets/js/api.js"></script>
    <script src="assets/js/modals.js"></script>
    <script src="assets/js/scoring.js"></script>
    
    <!-- Additional Scripts -->
    <?php if ($additionalScripts): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>

</body>
</html>