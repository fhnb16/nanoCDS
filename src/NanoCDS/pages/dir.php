<?php
/**
 * Nano CDS - directory listing.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$name = nano_param('name');

if ($name === '') {
    require NANO_DIR . '/pages/main.php';
    return;
}

$absolute = nano_resolve($name);

if ($absolute === false || !is_dir($absolute)) {
    nano_header('Directory: ' . $name);
    echo '<div class="group">' . PHP_EOL;
    nano_crumb('<span class="uppertext">' . e($name) . '</span>');
    nano_message('warning', 'Folder not exist');
    echo '</div>' . PHP_EOL;
    nano_footer();
    return;
}

$relative = nano_relative($absolute);
$entries = nano_list_dir($absolute, $relative);

nano_header('Directory: ' . $relative);
?>
<div class="group">
<?php
nano_crumb(nano_path_crumb($relative), true,
    '<a class="btnv1" href="' . e(nano_url(['page' => 'api', 'q' => nano_path_asset($relative)])) . '">api</a>'
);

if ($entries === []) {
    nano_message('info', 'Nothing found');
}

foreach ($entries as $entry) {
    if ($entry['dir']) {
        nano_row(
            nano_pretty_url('dir', ['name' => $entry['rel']]),
            $entry['name'],
            $entry['size'] < 0 ? '' : nano_format_bytes($entry['size'])
        );
        continue;
    }

    nano_row(
        nano_url(['page' => 'view', 'dir' => $relative, 'name' => $entry['name']]),
        $entry['name'],
        nano_format_bytes($entry['size']),
        [
            ['href' => nano_pretty_url('preview', ['dir' => $relative, 'name' => $entry['name']]), 'title' => 'Preview and links', 'label' => 'preview'],
            ['href' => nano_pretty_url('view', ['dir' => $relative, 'name' => $entry['name']]), 'title' => 'Short link', 'label' => 'link'],
        ],
        false
    );
}
?>
</div>
<?php
nano_footer();
