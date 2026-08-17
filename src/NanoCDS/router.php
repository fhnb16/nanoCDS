<?php
/**
 * Nano CDS - routing.
 *
 * Two URL styles are supported and both produce the same $_GET array:
 *
 *   classic  /assets/?page=view&dir=bootstrap/5.3.3&name=bootstrap.min.css
 *   pretty   /assets/view/bootstrap/5.3.3/f/bootstrap.min.css
 *
 * The pretty form is what .htaccess rewrites to index.php; the classic form is
 * what every existing integration uses, so it must keep working unchanged.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

/** Pages the front controller is allowed to dispatch to. */
const NANO_PAGES = [
    'main', 'about', 'dir', 'view', 'preview', 'tools', 'search', 'latest', 'api', 'support',
];

/** Pages that are only reachable through the pretty URL alias table. */
const NANO_PAGE_ALIASES = [
    'signin' => 'main',
    'index'  => 'main',
    'home'   => 'main',
];

/**
 * Fill $_GET from a pretty URL.
 *
 * Does nothing when the request already carries a query string, so the classic
 * URLs always win and never get re-interpreted.
 */
function nano_parse_pretty_url(): void
{
    if (!empty($_GET)) {
        return;
    }

    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    if ($requestUri === '') {
        return;
    }

    $path = (string) parse_url($requestUri, PHP_URL_PATH);
    $base = nano_base();

    // Strip the repository prefix, e.g. "/assets/".
    if ($base !== '/' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    } elseif ($base !== '/' && str_starts_with($path . '/', $base)) {
        $path = '';
    }

    $segments = preg_split('#/+#', trim($path, '/'), -1, PREG_SPLIT_NO_EMPTY);
    if ($segments === false || $segments === []) {
        return;
    }

    $segments = array_map(static fn (string $s): string => rawurldecode($s), $segments);

    $page = array_shift($segments);
    $page = NANO_PAGE_ALIASES[$page] ?? $page;
    if (!in_array($page, NANO_PAGES, true)) {
        return; // Unknown prefix: fall through to the index page.
    }

    $_GET['page'] = $page;

    switch ($page) {
        case 'view':
        case 'preview':
            // <dir...>/f/<name>, with optional "/v/<version>/" markers in <dir>.
            $marker = array_search('f', $segments, true);
            if ($marker === false) {
                break;
            }
            $dirParts = array_slice($segments, 0, $marker);
            $nameParts = array_slice($segments, $marker + 1);
            $dirParts = array_values(array_filter($dirParts, static fn (string $s): bool => $s !== 'v'));

            $_GET['dir'] = implode('/', $dirParts);
            $_GET['name'] = implode('/', $nameParts);
            break;

        case 'dir':
            $_GET['name'] = implode('/', $segments);
            break;

        case 'search':
            $_GET['query'] = $segments[0] ?? '';
            break;

        case 'api':
            $_GET['q'] = $segments[0] ?? '';
            while (count($segments) > 1) {
                array_shift($segments);
                $key = array_shift($segments);
                $value = array_shift($segments) ?? '';
                nano_route_option((string) $key, (string) $value);
            }
            break;

        case 'latest':
            $_GET['asset'] = array_shift($segments) ?? '';
            while ($segments !== []) {
                $key = array_shift($segments);
                $value = array_shift($segments) ?? '';
                nano_route_option((string) $key, (string) $value);
            }
            break;
    }
}

/** Map the short /t/ /s/ /a/ markers of a pretty URL onto query parameters. */
function nano_route_option(string $key, string $value): void
{
    switch ($key) {
        case 't':
            $_GET['type'] = $value !== '' ? $value : 'any';
            break;
        case 's':
            $_GET['size'] = $value !== '' ? $value : '0';
            break;
        case 'a':
            $_GET['auto'] = $value !== '' ? $value : '1';
            break;
        case 'l':
            $_GET['latest'] = $value !== '' ? $value : '1';
            break;
    }
}

/** Resolve the request to one of NANO_PAGES. */
function nano_route(): string
{
    nano_parse_pretty_url();

    $page = (string) ($_GET['page'] ?? '');
    $page = NANO_PAGE_ALIASES[$page] ?? $page;

    return in_array($page, NANO_PAGES, true) ? $page : 'main';
}

/** Read a request parameter as a trimmed string. */
function nano_param(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    if (is_array($value)) {
        return $default;
    }

    return trim(str_replace("\0", '', (string) $value));
}
