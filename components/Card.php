<?php
/**
 * Component: Card
 * Expected vars: $title (optional), $description (optional), $headerClass (optional), $contentClass (optional)
 * Usage: Include this file and the content will be between the card tags
 */

$title = $title ?? '';
$description = $description ?? '';
$headerClass = $headerClass ?? '';
$contentClass = $contentClass ?? '';
$showHeader = !empty($title) || !empty($description);
?>

<div class="bg-white shadow rounded-lg border border-gray-200">
    <?php if ($showHeader): ?>
    <div class="px-6 py-4 border-b border-gray-200 <?= esc($headerClass) ?>">
            <?php if ($title): ?>
                <h3 class="text-lg font-medium text-gray-900"><?= esc($title) ?></h3>
            <?php endif; ?>
            <?php if ($description): ?>
                <p class="mt-1 text-sm text-gray-600"><?= esc($description) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="px-6 py-4 <?= esc($contentClass) ?>">
        <?php if (isset($cardContent)): ?>
            <?= $cardContent ?>
        <?php endif; ?>
    </div>
</div>