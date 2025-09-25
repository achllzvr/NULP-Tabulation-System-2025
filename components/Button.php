<?php
/**
 * Component: Button
 * Expected vars: $text, $href (optional), $onclick (optional), $variant (optional), $size (optional), $class (optional), $type (optional)
 */

$text = $text ?? 'Button';
$href = $href ?? '';
$onclick = $onclick ?? '';
$variant = $variant ?? 'default';
$size = $size ?? 'default';
$class = $class ?? '';
$type = $type ?? 'button';
$disabled = $disabled ?? false;

$baseClasses = 'inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors';

$variantClasses = [
    'default' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'outline' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500',
    'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-indigo-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500'
];

$sizeClasses = [
    'sm' => 'px-3 py-2 text-sm',
    'default' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base'
];

$finalClass = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['default']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['default']);

if ($class) {
    $finalClass .= ' ' . $class;
}

if ($disabled) {
    $finalClass .= ' opacity-50 cursor-not-allowed';
}

$tag = $href ? 'a' : 'button';
$hrefAttr = $href ? 'href="' . Util::escape($href) . '"' : '';
$onclickAttr = $onclick ? 'onclick="' . Util::escape($onclick) . '"' : '';
$typeAttr = ($tag === 'button') ? 'type="' . Util::escape($type) . '"' : '';
$disabledAttr = ($disabled && $tag === 'button') ? 'disabled' : '';
?>

<<?= $tag ?> class="<?= Util::escape($finalClass) ?>" <?= $hrefAttr ?> <?= $onclickAttr ?> <?= $typeAttr ?> <?= $disabledAttr ?>>
    <?= Util::escape($text) ?>
</<?= $tag ?>>