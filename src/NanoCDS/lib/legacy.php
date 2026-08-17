<?php
/**
 * Nano CDS - backwards compatible function names.
 *
 * Nano CDS 1.x exposed these helpers as globals. They are kept so that any
 * local modification or third party snippet that calls them keeps working.
 * New code should use the nano_* functions directly.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

/** @deprecated Use nano_format_bytes(). */
function formatBytes(mixed $bytes, int $precision = 2): string
{
    return nano_format_bytes($bytes, $precision);
}

/** @deprecated Use nano_tree_search(). */
function glob_tree_search(string $path, string $pattern, ?string $_base_path = null): array
{
    $absolute = realpath($path);
    if ($absolute === false) {
        return [];
    }

    return nano_tree_search($pattern, $absolute, (string) $_base_path);
}

/** @deprecated Use dirname(). */
function getDirectoryPath(string $file): string
{
    $parts = explode('/', $file);
    array_pop($parts);

    return implode('/', $parts);
}

/** @deprecated Kept for compatibility; no longer used internally. */
function clean_url(string $url): string
{
    return (string) preg_replace('#(/assets/).*?(/index\.php)#', '$1$2', $url);
}

/** @deprecated Removes $substring when it is the trailing part of $string. */
function removeLastOccurrence(string $string, string $substring): string
{
    if ($substring !== '' && str_ends_with($string, $substring)) {
        return substr($string, 0, -strlen($substring));
    }

    return $string;
}

/** @deprecated Use nano_iterator(). */
function getDirContents(string $path): array
{
    $real = realpath($path);
    if ($real === false) {
        return [];
    }

    $dirs = [];
    foreach (nano_iterator($real) as $item) {
        if ($item->isDir()) {
            $dirs[] = $item;
        }
    }

    return $dirs;
}

/** @deprecated Use nano_count(). */
function countFilesAndDirs(string $directory): array
{
    return nano_count($directory);
}
