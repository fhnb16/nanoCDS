<?php
/**
 * Nano CDS - file preview and ready-to-copy links.
 *
 * Everything here is rendered on the server: the text preview, the image tag,
 * the metadata and the links. The page carries no client side logic beyond the
 * shared clipboard button.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$name = nano_param('name');
$dir = nano_param('dir');

if ($name === '' || nano_is_blocked($name)) {
    nano_message_page('Preview', 'error', 'File not found', 404);
}

$absolute = nano_resolve($dir === '' ? $name : $dir . '/' . $name);
if ($absolute === false || !is_file($absolute)) {
    nano_message_page('Preview', 'error', 'File not found', 404);
}

$relative  = nano_relative($absolute);
$dirPart   = trim((string) dirname($relative), './');
$base      = basename($relative);
$ext       = nano_ext($base);
$size      = nano_size($absolute);
$version   = nano_path_version($dirPart);

$directUrl = nano_url(['page' => 'view', 'dir' => $dirPart, 'name' => $base]);
$prettyUrl = nano_pretty_url('view', ['dir' => $dirPart, 'name' => $base]);

nano_header('Preview: ' . $base);
?>
<div class="group">
<?php
// File names keep their original case - for a file name case is significant.
nano_crumb(nano_path_crumb($dirPart) . ' &bull; <span class="nano-nocase">' . e($base) . '</span>');
?>
    <p class="nano-meta">
        <b>Size:</b> <?= e(nano_format_bytes($size)) ?> &nbsp;
        <b>Type:</b> <?= e(nano_mime($absolute, $base)) ?> &nbsp;
        <b>Modified:</b> <?= e(date('Y-m-d H:i', (int) @filemtime($absolute))) ?>
        <?= $version !== null ? '<span class="nano-badge">v' . e($version) . '</span>' : '' ?>
    </p>
<?php
nano_copy_field('Direct URL', nano_absolute_url($directUrl));
nano_copy_field('Short URL', nano_absolute_url($prettyUrl));

if ($ext === 'css') {
    nano_copy_field('HTML tag', '<link rel="stylesheet" href="' . nano_absolute_url($prettyUrl) . '">');
} elseif ($ext === 'js' || $ext === 'mjs') {
    nano_copy_field('HTML tag', '<script src="' . nano_absolute_url($prettyUrl) . '"></script>');
} elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg'], true)) {
    nano_copy_field('HTML tag', '<img src="' . nano_absolute_url($prettyUrl) . '" alt="">');
}

echo '<p><a class="btnv1" href="' . e($directUrl) . '">Open raw file</a> '
    . '<a class="btnv1" href="' . e(nano_pretty_url('dir', ['name' => $dirPart])) . '">Open folder</a> '
    . '<a class="btnv1" href="' . e(nano_url(['page' => 'api', 'q' => nano_path_asset($relative)])) . '">JSON API</a></p>' . PHP_EOL;

$imageExt = nano_config('preview_image_ext');
$textExt  = nano_config('preview_text_ext');
$maxBytes = (int) nano_config('preview_bytes');

if (in_array($ext, $imageExt, true) && $ext !== 'svg') {
    echo '<img class="nano-shot" src="' . e($directUrl) . '" alt="' . e($base) . '">' . PHP_EOL;
} elseif ($ext === 'svg') {
    echo '<img class="nano-shot" src="' . e($directUrl) . '" alt="' . e($base) . '">' . PHP_EOL;
    echo '<p class="nano-meta"><b>Source:</b></p>' . PHP_EOL;
    echo '<pre class="nano-pre">' . e((string) @file_get_contents($absolute, false, null, 0, $maxBytes)) . '</pre>' . PHP_EOL;
} elseif (in_array($ext, $textExt, true)) {
    $content = (string) @file_get_contents($absolute, false, null, 0, $maxBytes);
    echo '<pre class="nano-pre">' . e($content) . '</pre>' . PHP_EOL;
    if ($size > $maxBytes) {
        nano_message('info', 'Preview truncated at ' . nano_format_bytes($maxBytes, 0) . ' - open the raw file for the rest');
    }
} else {
    nano_message('info', 'No preview available for this file type');
}
?>
</div>
<?php
nano_footer();
