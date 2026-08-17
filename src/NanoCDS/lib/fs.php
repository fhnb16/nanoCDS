<?php
/**
 * Nano CDS - filesystem access.
 *
 * Pure PHP: no shell, no `du`, no `find`. Works on Linux and Windows alike.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

/** Recursive iterator that never descends into hidden or dot directories. */
function nano_iterator(string $path): RecursiveIteratorIterator
{
    $inner = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
    $filtered = new RecursiveCallbackFilterIterator($inner, static function ($current): bool {
        return !nano_is_hidden_entry($current->getFilename());
    });

    return new RecursiveIteratorIterator($filtered, RecursiveIteratorIterator::SELF_FIRST);
}

/** True for entries that must never appear anywhere (dotfiles, __hidden, NanoCDS). */
function nano_is_hidden_entry(string $name): bool
{
    if ($name === '' || $name[0] === '.') {
        return true;
    }
    return in_array($name, nano_config('hidden'), true);
}

/** Size of a file, or recursive size of a directory, in bytes. */
function nano_size(string|false|null $path): int
{
    if ($path === false || $path === null || !file_exists($path)) {
        return 0;
    }

    if (is_file($path)) {
        $size = @filesize($path);
        return $size === false ? 0 : $size;
    }

    $total = 0;
    try {
        foreach (nano_iterator($path) as $item) {
            if ($item->isFile()) {
                $total += $item->getSize();
            }
        }
    } catch (Throwable) {
        return $total;
    }

    return $total;
}

/**
 * Files, directories and total size below $directory - in a single walk.
 *
 * @return array{files:int, dirs:int, size:int}
 */
function nano_scan(string $directory): array
{
    $result = ['files' => 0, 'dirs' => 0, 'size' => 0];
    $real = realpath($directory);
    if ($real === false) {
        return $result;
    }

    try {
        foreach (nano_iterator($real) as $item) {
            if ($item->isDir()) {
                $result['dirs']++;
            } elseif ($item->isFile()) {
                $result['files']++;
                $result['size'] += $item->getSize();
            }
        }
    } catch (Throwable) {
        return $result;
    }

    return $result;
}

/** Number of files and directories below $directory. */
function nano_count(string $directory): array
{
    return nano_scan($directory);
}

/**
 * One directory level, already filtered and safe to render.
 *
 * @return array<int, array{name:string, rel:string, dir:bool, size:int, mtime:int}>
 */
function nano_list_dir(string $absolute, string $relative): array
{
    $entries = @scandir($absolute, SCANDIR_SORT_NONE);
    if ($entries === false) {
        return [];
    }

    $out = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || nano_is_hidden_entry($entry)) {
            continue;
        }

        $child = $absolute . DIRECTORY_SEPARATOR . $entry;
        $isDir = is_dir($child);

        // Server-side scripts and HTML pages stay out of listings.
        if (!$isDir && nano_is_listing_hidden($entry)) {
            continue;
        }

        // Sub-folder sizes require walking the whole sub-tree; on a large
        // repository that dominates the page, so it can be switched off.
        $size = ($isDir && !nano_config('dir_size', true)) ? -1 : nano_size($child);

        $out[] = [
            'name'  => $entry,
            'rel'   => $relative === '' ? $entry : $relative . '/' . $entry,
            'dir'   => $isDir,
            'size'  => $size,
            'mtime' => (int) @filemtime($child),
        ];
    }

    /*
     * Folders first, newest version on top (5.10.0 above 5.9.9), then files in
     * natural order. scandir()'s plain descending sort put files and folders in
     * one alphabetical pile and got two-digit versions wrong.
     */
    usort($out, static function (array $a, array $b): int {
        if ($a['dir'] !== $b['dir']) {
            return $a['dir'] ? -1 : 1;
        }
        if ($a['dir']) {
            $cmp = nano_version_cmp(
                nano_is_version($b['name']) ? $b['name'] : null,
                nano_is_version($a['name']) ? $a['name'] : null
            );
            if ($cmp !== 0) {
                return $cmp;
            }

            return strnatcasecmp($b['name'], $a['name']);
        }

        return strnatcasecmp($a['name'], $b['name']);
    });

    return $out;
}

/**
 * Sanitise a user supplied glob pattern.
 *
 * Wildcards (* ? [] {}) stay, everything that could walk the filesystem or
 * name a hidden folder is removed. The pattern is only ever used as the file
 * name part of a glob, never as a directory.
 */
function nano_search_pattern(string $pattern): string
{
    $pattern = str_replace(["\0", '/', '\\'], '', $pattern);
    $pattern = str_replace(nano_config('hidden'), '', $pattern);
    $pattern = str_replace('..', '', $pattern);

    return trim($pattern);
}

/** Glob suffix for a requested file type ("css" -> ".css", "any" -> ".*"). */
function nano_type_pattern(string $type): string
{
    return match (strtolower($type)) {
        'css'  => '.css',
        'js'   => '.js',
        'json' => '.json',
        'xml'  => '.xml',
        'svg'  => '.svg',
        'png'  => '.png',
        'gif'  => '.gif',
        'jpg'  => '.jp?g',
        'ttf'  => '.ttf',
        'zip'  => '.zip',
        'rar'  => '.rar',
        '7z'   => '.7z',
        'exe'  => '.exe',
        'txt'  => '.txt',
        'html' => '.htm?',
        default => '.*',
    };
}

/**
 * Recursive glob across the repository.
 *
 * @return array<int, string> Paths relative to the repository root.
 */
function nano_tree_search(string $pattern, ?string $absolute = null, string $prefix = ''): array
{
    $absolute ??= nano_root();

    $out = [];
    $limit = (int) nano_config('max_results');

    $matches = glob($absolute . DIRECTORY_SEPARATOR . $pattern, GLOB_BRACE);
    if ($matches !== false) {
        foreach ($matches as $match) {
            $name = basename($match);
            if (nano_is_listing_hidden($name) || !is_file($match)) {
                continue;
            }
            $out[] = $prefix . $name;
            if (count($out) >= $limit) {
                return $out;
            }
        }
    }

    $dirs = glob($absolute . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    if ($dirs !== false) {
        foreach ($dirs as $dir) {
            $name = basename($dir);
            if (nano_is_hidden_entry($name)) {
                continue;
            }
            $out = array_merge($out, nano_tree_search($pattern, $dir, $prefix . $name . '/'));
            if (count($out) >= $limit) {
                return array_slice($out, 0, $limit);
            }
        }
    }

    return $out;
}

/** Top-level project directories of the repository. */
function nano_projects(): array
{
    $out = [];
    foreach (new DirectoryIterator(nano_root()) as $info) {
        if (!$info->isDir() || $info->isDot() || nano_is_hidden_entry($info->getFilename())) {
            continue;
        }
        $out[] = $info->getFilename();
    }
    sort($out, SORT_NATURAL | SORT_FLAG_CASE);

    return $out;
}

/**
 * Describe a repository-relative file for listings and the JSON API.
 *
 * @return array{name:string, dir:string, rel:string, version:?string, size:int, modified:string, url:string, pretty:string}|null
 */
function nano_file_info(string $relative): ?array
{
    $absolute = nano_resolve($relative);
    if ($absolute === false || !is_file($absolute) || nano_is_blocked($relative)) {
        return null;
    }

    $rel = nano_relative($absolute);
    $name = basename($rel);
    $dir = trim((string) dirname($rel), './');

    return [
        'name'     => $name,
        'dir'      => $dir,
        'rel'      => $rel,
        'version'  => nano_path_version($dir),
        'size'     => nano_size($absolute),
        'modified' => gmdate('c', (int) @filemtime($absolute)),
        'url'      => nano_url(['page' => 'view', 'dir' => $dir, 'name' => $name]),
        'pretty'   => nano_pretty_url('view', ['dir' => $dir, 'name' => $name]),
    ];
}

/** Human readable byte count. Safe for 0, negative and non-numeric input. */
function nano_format_bytes(mixed $bytes, int $precision = 2): string
{
    $bytes = is_numeric($bytes) ? (float) $bytes : 0.0;
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $exp = (int) floor(log($bytes, 1024));
    $exp = max(0, min($exp, count($units) - 1));

    return round($bytes / 1024 ** $exp, $precision) . ' ' . $units[$exp];
}
