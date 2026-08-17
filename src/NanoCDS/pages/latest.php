<?php
/**
 * Nano CDS - "find the latest version" page.
 *
 * Parameters (unchanged since 1.x):
 *   asset  name of the library
 *   type   any|css|js|json|xml|svg|png|gif|jpg|ttf|zip|rar|7z|exe|txt|html
 *   size   0 = any, 1 = minified only, 2 = full (non-minified) only
 *   auto   0 = list matches, 1 = redirect to the newest file, -1 = show its URL
 *
 * Since 2.0 the candidates are ordered with version_compare(), so 1.10 is
 * correctly newer than 1.9. Earlier releases sorted alphabetically.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$asset = nano_search_pattern(nano_param('asset'));
$type  = nano_param('type', 'any');
$size  = nano_param('size', '0');
$auto  = nano_param('auto', '0');

$minPart = $size === '1' ? '.min' : '';
$pattern = $asset . $minPart . nano_type_pattern($type);

$files = $asset === '' ? [] : nano_tree_search($pattern);

if ($size === '2') {
    $files = array_filter($files, static fn (string $f): bool => !str_contains(basename($f), '.min'));
}
$files = array_values(array_filter($files, static fn (string $f): bool => nano_resolve($f) !== false));

/* Newest first: semantic version order, then natural path order. */
$files = nano_sort_by_version($files);

/* auto=1 - jump straight to the newest match. */
if ($auto === '1' && $files !== []) {
    $newest = $files[0];
    nano_redirect(nano_url([
        'page' => 'view',
        'dir'  => trim((string) dirname($newest), './'),
        'name' => basename($newest),
    ]));
}

/* auto=-1 - show the permanent "always latest" URL instead of the file. */
if ($auto === '-1' && $files !== []) {
    $permalink = nano_absolute_url(nano_pretty_url('latest', [
        'asset' => $asset,
        'type'  => $type,
        'size'  => $size,
        'auto'  => '1',
    ]));

    nano_header('Latest: ' . $asset);
    echo '<div class="group">' . PHP_EOL;
    nano_crumb('Latest: `' . e($asset) . '`');
    echo '<a class="group-item group-item-action" id="latestLink" href="' . e($permalink) . '">'
        . '<div class="middleText">' . e($permalink) . '</div>'
        . '<div style="float:right;" class="downloadIcon"></div></a>' . PHP_EOL;
    nano_copy_field('Always latest', $permalink);
    nano_message('info', 'This URL always redirects to the newest matching file');
    echo '</div>' . PHP_EOL;
    nano_footer();
    return;
}

$title = 'Search: ' . $asset;
if ($asset !== '') {
    $title .= ' (Files: ' . count($files) . ')';
}

nano_header($title);
?>
<div class="group">
<?php
nano_crumb('Search: `' . e($asset) . '`');

if ($asset === '') {
    nano_message('info', 'Search Query is empty');
} elseif ($files === []) {
    nano_message('info', 'Nothing found');
}

foreach ($files as $index => $file) {
    $dirPart = trim((string) dirname($file), './');
    $base = basename($file);
    $version = nano_path_version($dirPart);

    $meta = '[ ' . $dirPart . ' ]';
    if ($index === 0 && $version !== null) {
        $meta = 'latest ' . $meta;
    }

    nano_row(
        nano_url(['page' => 'view', 'dir' => $dirPart, 'name' => $base]),
        $base,
        $meta,
        [
            ['href' => nano_pretty_url('preview', ['dir' => $dirPart, 'name' => $base]), 'title' => 'Preview and links', 'label' => 'preview'],
            ['href' => nano_pretty_url('view', ['dir' => $dirPart, 'name' => $base]), 'title' => 'Short link', 'label' => 'link'],
        ]
    );
}
?>
</div>
<?php
nano_footer();
