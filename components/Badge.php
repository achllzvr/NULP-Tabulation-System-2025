<?php
/**
 * Component: Badge
 * Expected vars: $text, $variant (optional: 'default', 'secondary', 'outline'), $class (optional)
 */

$text = $text ?? '';
$variant = $variant ?? 'default';
$class = $class ?? '';

$baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';

$variantClasses = [
    'default' => 'bg-blue-100 text-blue-800',
    'secondary' => 'bg-gray-100 text-gray-800',
    'outline' => 'bg-white text-gray-800 border border-gray-300',
    'success' => 'bg-green-100 text-green-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'danger' => 'bg-red-100 text-red-800'
];

$finalClass = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['default']);
if ($class) {
    $finalClass .= ' ' . $class;
}
?>

<span class="<?= Util::escape($finalClass) ?>">
    <?= Util::escape($text) ?>
</span>