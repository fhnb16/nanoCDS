<?php
/**
 * Nano CDS - index page: the list of projects in the repository.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$projects = nano_projects();

nano_header();
?>
<div class="group">
<?php
nano_crumb('', false,
    '<a href="' . e(nano_pretty_url('tools')) . '" class="btnv1">Tools</a>'
    . '<a href="' . e(nano_pretty_url('about')) . '" class="btnv1">What is it? -&gt;</a>'
);

if ($projects === []) {
    nano_message('info', 'The repository is empty');
}

foreach ($projects as $project) {
    nano_row(
        nano_pretty_url('dir', ['name' => $project]),
        $project,
        '',
        [['href' => nano_url(['page' => 'api', 'q' => $project]), 'title' => 'JSON API for this asset', 'label' => 'api']]
    );
}
?>
</div>
<?php
nano_footer();
