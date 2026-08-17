<?php
/**
 * Nano CDS - semantic version handling.
 *
 * The repository layout is `<asset>/<version>/<files>`, so the version of a
 * file is taken from its directory path. Sorting uses version_compare(), which
 * knows that 1.10 is newer than 1.9 - plain string sorting does not.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

/** True when a path segment looks like a version number ("5.3.3", "v2", "1.0.0-beta"). */
function nano_is_version(string $segment): bool
{
    return (bool) preg_match('/^v?\d+(\.\d+)*([.\-+][A-Za-z0-9.\-+]+)?$/', $segment);
}

/** Version taken from a directory path, or null when there is none. */
function nano_path_version(string $path): ?string
{
    $segments = preg_split('#[\\\\/]+#', trim($path, '/\\'), -1, PREG_SPLIT_NO_EMPTY);
    if ($segments === false) {
        return null;
    }

    // The deepest version-looking segment wins: bootstrap/5.3.3/js -> 5.3.3
    for ($i = count($segments) - 1; $i >= 0; $i--) {
        if (nano_is_version($segments[$i])) {
            return $segments[$i];
        }
    }

    return null;
}

/** Asset name: the first path segment ("bootstrap/5.3.3/css" -> "bootstrap"). */
function nano_path_asset(string $path): string
{
    $segments = preg_split('#[\\\\/]+#', trim($path, '/\\'), -1, PREG_SPLIT_NO_EMPTY);

    return ($segments === false || $segments === []) ? '' : $segments[0];
}

/** version_compare() wrapper: files without a version sort last. */
function nano_version_cmp(?string $a, ?string $b): int
{
    if ($a === $b) {
        return 0;
    }
    if ($a === null) {
        return -1;
    }
    if ($b === null) {
        return 1;
    }

    return version_compare(ltrim($a, 'vV'), ltrim($b, 'vV'));
}

/**
 * Sort repository-relative file paths newest first.
 *
 * Primary key: version taken from the path (semantic, not alphabetic).
 * Secondary key: natural order of the whole path, so results stay stable.
 *
 * @param  array<int, string> $files
 * @return array<int, string>
 */
function nano_sort_by_version(array $files): array
{
    $keyed = [];
    foreach ($files as $file) {
        $keyed[] = ['path' => $file, 'version' => nano_path_version((string) dirname($file))];
    }

    usort($keyed, static function (array $a, array $b): int {
        $cmp = nano_version_cmp($b['version'], $a['version']); // descending
        if ($cmp !== 0) {
            return $cmp;
        }

        return strnatcasecmp($b['path'], $a['path']);
    });

    return array_column($keyed, 'path');
}

/**
 * Group files by asset and version, newest version first.
 *
 * @param  array<int, string> $files
 * @return array<string, array{latest:?string, versions:array<string, array<int, string>>}>
 */
function nano_group_by_asset(array $files): array
{
    $assets = [];

    foreach (nano_sort_by_version($files) as $file) {
        $dir = (string) dirname($file);
        $asset = nano_path_asset($file);
        $version = nano_path_version($dir);
        $key = $version ?? '-';

        if (!isset($assets[$asset])) {
            $assets[$asset] = ['latest' => null, 'versions' => []];
        }
        $assets[$asset]['versions'][$key][] = $file;

        if ($version !== null && nano_version_cmp($version, $assets[$asset]['latest']) > 0) {
            $assets[$asset]['latest'] = $version;
        }
    }

    ksort($assets, SORT_NATURAL | SORT_FLAG_CASE);

    return $assets;
}
