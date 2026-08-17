<?php
/**
 * Nano CDS - path resolution and URL building.
 *
 * Every path that originates from a request must pass through nano_resolve()
 * before it touches the filesystem. Nothing else in the codebase is allowed
 * to concatenate user input into a path.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

/** Absolute, symlink-resolved path of the repository root. */
function nano_root(): string
{
    static $root = null;
    if ($root === null) {
        $resolved = realpath(NANO_ROOT_DIR);
        $root = $resolved !== false ? $resolved : rtrim(NANO_ROOT_DIR, '/\\');
    }
    return $root;
}

/**
 * Split a relative path into clean segments.
 * Returns null when the path contains anything suspicious.
 */
function nano_segments(string $relative): ?array
{
    if ($relative === '' || str_contains($relative, "\0")) {
        return null;
    }

    $relative = str_replace('\\', '/', $relative);
    $parts = preg_split('#/+#', trim($relative, '/'), -1, PREG_SPLIT_NO_EMPTY);
    if ($parts === false || $parts === []) {
        return null;
    }

    $hidden = nano_config('hidden');
    foreach ($parts as $part) {
        // "." and ".." never appear in a legitimate repository path, and a
        // leading dot means a dotfile (.htaccess, .git, .env) - always denied.
        if ($part[0] === '.') {
            return null;
        }
        if (in_array($part, $hidden, true)) {
            return null;
        }
    }

    return $parts;
}

/**
 * Resolve a request-supplied path against the repository root.
 *
 * @return string|false Absolute path, or false when the path does not exist,
 *                      escapes the root, or points at hidden content.
 */
function nano_resolve(string $relative): string|false
{
    $parts = nano_segments($relative);
    if ($parts === null) {
        return false;
    }

    $root = nano_root();
    $real = realpath($root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts));
    if ($real === false) {
        return false;
    }

    // Containment: the resolved path must live strictly inside the root.
    // realpath() has already followed every symlink, so this also stops
    // symlinks that point outside the repository.
    if (strncmp($real, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) !== 0) {
        return false;
    }

    // A symlink may have landed us on a hidden or dot path - check again.
    if (nano_segments(substr($real, strlen($root))) === null) {
        return false;
    }

    return $real;
}

/** Convert an absolute path inside the root into a forward-slash relative path. */
function nano_relative(string $absolute): string
{
    $root = nano_root();
    if (strncmp($absolute, $root, strlen($root)) === 0) {
        $absolute = substr($absolute, strlen($root));
    }
    return trim(str_replace('\\', '/', $absolute), '/');
}

/** Lower-case extension of a file name. */
function nano_ext(string $name): string
{
    return strtolower(pathinfo($name, PATHINFO_EXTENSION));
}

/** True when the file must never be sent to a client. */
function nano_is_blocked(string $name): bool
{
    $base = basename(str_replace('\\', '/', $name));
    if ($base === '' || $base[0] === '.') {
        return true;
    }
    return in_array(nano_ext($base), nano_config('blocked_ext'), true);
}

/** True when the entry should be omitted from listings and search results. */
function nano_is_listing_hidden(string $name): bool
{
    $base = basename(str_replace('\\', '/', $name));
    if ($base === '' || $base[0] === '.') {
        return true;
    }
    if (in_array($base, nano_config('hidden'), true)) {
        return true;
    }
    return in_array(nano_ext($base), nano_config('listing_hidden_ext'), true);
}

/**
 * Public URL prefix of the repository, always with a trailing slash.
 *
 * Detected from SCRIPT_NAME so that Nano CDS works in any folder;
 * override with `base_path` in config.php, or with the legacy $rootDir global.
 */
function nano_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configured = nano_config('base_path');
    if (is_string($configured) && $configured !== '') {
        return $base = '/' . trim($configured, '/') . '/';
    }

    // Legacy override kept for backwards compatibility.
    if (isset($GLOBALS['rootDir']) && is_string($GLOBALS['rootDir']) && $GLOBALS['rootDir'] !== '') {
        return $base = '/' . trim($GLOBALS['rootDir'], '/') . '/';
    }

    foreach (nano_script_paths() as $script) {
        if ($script === '' || !str_ends_with($script, '.php')) {
            continue;
        }
        // /assets/NanoCDS/index.php -> /assets/NanoCDS -> /assets
        $parent = rtrim(dirname(rtrim(dirname($script), '/')), '/');

        return $base = ($parent === '' || $parent === '.' || $parent === '/') ? '/' : $parent . '/';
    }

    return $base = '/assets/';
}

/** Candidate values for the script path, most reliable first. */
function nano_script_paths(): array
{
    $candidates = [
        (string) ($_SERVER['SCRIPT_NAME'] ?? ''),
        (string) ($_SERVER['PHP_SELF'] ?? ''),
    ];

    // Derive it from the filesystem when the SAPI does not provide a usable one.
    $docRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $scriptFile = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? __FILE__));
    if ($docRoot !== '' && str_starts_with($scriptFile, rtrim($docRoot, '/') . '/')) {
        $candidates[] = substr($scriptFile, strlen(rtrim($docRoot, '/')));
    }

    return array_map(static fn (string $p): string => str_replace('\\', '/', $p), $candidates);
}

/** Host name for absolute URLs, immune to Host header spoofing when configured. */
function nano_host(): string
{
    $configured = nano_config('canonical_host');
    if (is_string($configured) && $configured !== '') {
        return $configured;
    }

    $host = (string) ($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost');
    // Keep only what a host name may legally contain.
    $host = preg_replace('/[^A-Za-z0-9.\-\[\]:]/', '', $host) ?? 'localhost';
    $port = (string) ($_SERVER['SERVER_PORT'] ?? '');
    if ($port !== '' && $port !== '80' && $port !== '443' && !str_contains($host, ':')) {
        $host .= ':' . $port;
    }
    return $host === '' ? 'localhost' : $host;
}

/** True when the current request arrived over HTTPS. */
function nano_is_https(): bool
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

/**
 * Classic query-string URL: /assets/?page=view&dir=...&name=...
 *
 * Slashes are left unencoded so that the URLs are byte-for-byte what
 * Nano CDS 1.x produced - existing integrations copy these strings around.
 */
function nano_url(array $params): string
{
    if ($params === []) {
        return nano_base();
    }

    return nano_base() . '?' . str_replace('%2F', '/', http_build_query($params));
}

/**
 * Human friendly URL for a page, e.g. /assets/view/bootstrap/5.3.3/f/bootstrap.min.css
 * Falls back to the query-string form for pages without a pretty variant.
 */
function nano_pretty_url(string $page, array $params = []): string
{
    $enc = static fn (string $v): string => implode('/', array_map('rawurlencode', explode('/', trim($v, '/'))));

    switch ($page) {
        case 'view':
        case 'preview':
            $dir = (string) ($params['dir'] ?? '');
            $name = (string) ($params['name'] ?? '');
            if ($name === '') {
                break;
            }
            $prefix = nano_base() . $page;
            return $dir === ''
                ? $prefix . '/f/' . rawurlencode($name)
                : $prefix . '/' . $enc($dir) . '/f/' . rawurlencode($name);

        case 'dir':
            $name = (string) ($params['name'] ?? '');
            if ($name === '') {
                break;
            }
            return nano_base() . 'dir/' . $enc($name);

        case 'search':
            $query = (string) ($params['query'] ?? '');
            if ($query === '') {
                break;
            }
            return nano_base() . 'search/' . rawurlencode($query);

        case 'api':
            $q = (string) ($params['q'] ?? '');
            if ($q === '') {
                break;
            }
            return nano_base() . 'api/' . rawurlencode($q);

        case 'latest':
            $asset = (string) ($params['asset'] ?? '');
            if ($asset === '') {
                break;
            }
            return nano_base() . 'latest/' . rawurlencode($asset)
                . '/t/' . rawurlencode((string) ($params['type'] ?? 'any'))
                . '/s/' . rawurlencode((string) ($params['size'] ?? '0'))
                . '/a/' . rawurlencode((string) ($params['auto'] ?? '0'));

        case 'about':
        case 'tools':
        case 'support':
            return nano_base() . $page;
    }

    return nano_url(['page' => $page] + $params);
}

/** Absolute version of any of the URLs above. */
function nano_absolute_url(string $url): string
{
    return (nano_is_https() ? 'https' : 'http') . '://' . nano_host() . $url;
}
