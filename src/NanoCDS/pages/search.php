<?php
/**
 * Nano CDS - search by file name (glob patterns allowed).
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$query = nano_search_pattern(nano_param('query'));

$files = $query === '' ? [] : nano_tree_search($query . '.*');
$files = array_values(array_filter($files, static fn (string $f): bool => nano_resolve($f) !== false));

$title = 'Search: ' . $query;
if ($query !== '') {
    $title .= ' (Files: ' . count($files) . ')';
}

nano_header($title);
?>
<div class="group">
<?php
nano_crumb('Search: `' . e($query) . '`');

if ($query === '') {
    nano_message('info', 'Search Query is empty');
} elseif ($files === []) {
    nano_message('info', 'Nothing found');
}

foreach ($files as $file) {
    $dirPart = trim((string) dirname($file), './');
    $base = basename($file);

    nano_row(
        nano_url(['page' => 'view', 'dir' => $dirPart, 'name' => $base]),
        $base,
        '[ ' . $dirPart . ' ]',
        [
            ['href' => nano_pretty_url('preview', ['dir' => $dirPart, 'name' => $base]), 'title' => 'Preview and links', 'label' => 'preview'],
            ['href' => nano_pretty_url('view', ['dir' => $dirPart, 'name' => $base]), 'title' => 'Short link', 'label' => 'link'],
        ]
    );
}

if (count($files) >= (int) nano_config('max_results')) {
    nano_message('info', 'Result set truncated at ' . (int) nano_config('max_results') . ' files - narrow the query');
}
?>
</div>
<?php
nano_footer();
