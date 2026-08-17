<?php
/**
 * Nano CDS - JSON API.
 *
 *   /assets/?page=api&q=bootstrap
 *   /assets/api/bootstrap
 *
 * Optional parameters:
 *   type    any|css|js|json|xml|svg|png|gif|jpg|ttf|zip|rar|7z|exe|txt|html
 *   size    0 = any, 1 = minified only, 2 = full (non-minified) only
 *   latest  1 = only files belonging to the newest version of each asset
 *   flat    1 = a plain list of files instead of the asset/version tree
 *
 * The response is CORS-open on purpose: it exists so that other sites can
 * resolve asset URLs without scraping HTML.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$query  = nano_search_pattern(nano_param('q'));
$type   = nano_param('type', 'any');
$size   = nano_param('size', '0');
$latest = nano_param('latest') === '1';
$flat   = nano_param('flat') === '1';

if ($query === '') {
    nano_send_json([
        'nanocds' => nano_config('version'),
        'error'   => 'Parameter `q` is required',
        'usage'   => nano_absolute_url(nano_url(['page' => 'api', 'q' => 'bootstrap'])),
    ], 400);
}

$minPart = $size === '1' ? '.min' : '';
$patterns = [
    $query . $minPart . nano_type_pattern($type),   // bootstrap.min.css
    $query . '*/*' . $minPart . nano_type_pattern($type), // bootstrap/5.3.3/anything.css
];

$found = [];
foreach ($patterns as $pattern) {
    foreach (nano_tree_search($pattern) as $file) {
        $found[$file] = true;
    }
}
$files = array_keys($found);

if ($size === '2') {
    $files = array_filter($files, static fn (string $f): bool => !str_contains(basename($f), '.min'));
}

$infos = [];
foreach (nano_sort_by_version(array_values($files)) as $file) {
    $info = nano_file_info($file);
    if ($info !== null) {
        $infos[] = $info;
    }
}

/* Absolute URLs make the response directly usable from another origin. */
foreach ($infos as &$info) {
    $info['url'] = nano_absolute_url($info['url']);
    $info['pretty'] = nano_absolute_url($info['pretty']);
}
unset($info);

$assets = [];
foreach ($infos as $info) {
    $asset = nano_path_asset($info['rel']);
    $version = $info['version'] ?? '-';

    if (!isset($assets[$asset])) {
        $assets[$asset] = ['name' => $asset, 'latest' => null, 'versions' => []];
    }
    if ($info['version'] !== null && nano_version_cmp($info['version'], $assets[$asset]['latest']) > 0) {
        $assets[$asset]['latest'] = $info['version'];
    }
    $assets[$asset]['versions'][$version][] = $info;
}

if ($latest) {
    foreach ($assets as $name => $asset) {
        $keep = $asset['latest'] ?? array_key_first($asset['versions']);
        $assets[$name]['versions'] = isset($asset['versions'][$keep])
            ? [$keep => $asset['versions'][$keep]]
            : [];
    }
}

/* Normalise the version maps into ordered lists. */
$out = [];
$kept = [];
foreach ($assets as $name => $asset) {
    $versions = [];
    foreach ($asset['versions'] as $version => $versionFiles) {
        $versions[] = [
            'version' => $version === '-' ? null : (string) $version,
            'files'   => $versionFiles,
        ];
        array_push($kept, ...$versionFiles);
    }
    usort($versions, static fn (array $a, array $b): int => nano_version_cmp($b['version'], $a['version']));

    $out[] = [
        'name'     => $name,
        'latest'   => $asset['latest'],
        'versions' => $versions,
    ];
}

$truncated = count($infos) >= (int) nano_config('max_results');

if ($flat) {
    nano_send_json([
        'nanocds'   => nano_config('version'),
        'query'     => $query,
        'count'     => count($kept),
        'truncated' => $truncated,
        'files'     => $kept,
    ]);
}

nano_send_json([
    'nanocds'   => nano_config('version'),
    'query'     => $query,
    'count'     => count($kept),
    'truncated' => $truncated,
    'assets'    => $out,
]);
