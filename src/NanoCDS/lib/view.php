<?php
/**
 * Nano CDS - rendering helpers.
 *
 * Templates live in header.php / footer.php and in pages/*.php.
 * Everything printed to the page goes through e().
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

/** HTML-escape a value for text content and attribute values alike. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Open the HTML document. */
function nano_header(string $title = ''): void
{
    $GLOBALS['PageTitle'] = $title;
    require NANO_DIR . '/header.php';
}

/** Close the HTML document. */
function nano_footer(): void
{
    require NANO_DIR . '/footer.php';
}

/**
 * The `Nano CDS - <crumb>` bar every page starts with.
 *
 * @param string $crumb Already-escaped markup produced by the calling page.
 * @param bool   $back  Render the "Go Back" button on the right.
 * @param string $right Extra already-escaped markup for the right hand side.
 */
function nano_crumb(string $crumb = '', bool $back = true, string $right = ''): void
{
    echo '<span class="group-item group-item-action header">';
    echo '<a class="color-gray" href="' . e(nano_base()) . '">Nano CDS</a>';

    if ($crumb !== '') {
        echo ' &middot; <span class="uppertext">' . $crumb . '</span>';
    }

    if ($back) {
        $right .= '<a href="javascript:history.back()" class="btnv1">&lt;- Go Back</a>';
    }
    if ($right !== '') {
        echo '<span style="float:right;">' . $right . '</span>';
    }

    echo '</span>' . PHP_EOL;
}

/** Breadcrumb markup for a repository path: bootstrap &bull; 5.3.3 */
function nano_path_crumb(string $relative): string
{
    $parts = preg_split('#[\\\\/]+#', trim($relative, '/'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

    $out = [];
    $walked = '';
    foreach ($parts as $part) {
        $walked = $walked === '' ? $part : $walked . '/' . $part;
        $out[] = '<a href="' . e(nano_pretty_url('dir', ['name' => $walked])) . '">' . e($part) . '</a>';
    }

    return implode(' &bull; ', $out);
}

/**
 * A single row of a listing.
 *
 * The row is one flex line: name on the left, size / path in the middle, the
 * small action buttons on the right. The name link is stretched over the whole
 * row (`.nano-row-main::after`), so clicking anywhere outside the buttons opens
 * the file - while the buttons stay separately clickable. All generated in PHP.
 *
 * @param array<int, array{href:string, title:string, label:string}>|null $actions
 */
function nano_row(string $href, string $label, string $meta = '', ?array $actions = null, bool $upper = true): void
{
    echo '<div class="group-item group-item-action nano-row">';

    echo '<a class="nano-row-main' . ($upper ? ' uppertext' : '') . '"'
        . ' href="' . e($href) . '" title="' . e($label) . '">' . e($label) . '</a>';

    if ($meta !== '') {
        echo '<span class="nano-row-meta">' . e($meta) . '</span>';
    }

    if ($actions !== null && $actions !== []) {
        echo '<span class="row-actions">';
        foreach ($actions as $action) {
            echo '<a class="btnv1 smol" href="' . e($action['href']) . '" title="' . e($action['title']) . '">'
                . e($action['label']) . '</a>';
        }
        echo '</span>';
    }

    echo '</div>' . PHP_EOL;
}

/** Read-only field with the URL plus a copy button (the only JavaScript in the UI). */
function nano_copy_field(string $label, string $url): void
{
    $id = 'u' . substr(hash('crc32b', $url . $label), 0, 8);

    echo '<p class="copy-row"><span class="copy-label">' . e($label) . '</span>';
    echo '<input class="form-control copy-input" id="' . e($id) . '" type="text" readonly value="' . e($url) . '"'
        . ' onfocus="this.select()">';
    echo '<button type="button" class="btn btn-default" data-copy="' . e($id) . '">Copy</button></p>' . PHP_EOL;
}

/** Message block used by the error and info pages. */
function nano_message(string $kind, string $text): void
{
    $class = match ($kind) {
        'error'   => 'color-error',
        'warning' => 'color-warning',
        default   => 'color-info',
    };
    $title = ucfirst($kind);

    echo "<p><span class='" . $class . "'>" . e($title) . "</span>: <span class='color-grey'>" . e($text) . "</span>.</p>" . PHP_EOL;
}

/** Full page consisting of a single message. Terminates the request. */
function nano_message_page(string $title, string $kind, string $text, int $status = 200): never
{
    http_response_code($status);
    nano_header($title);
    echo '<div class="group">' . PHP_EOL;
    nano_crumb('<span class="uppertext">' . e($title) . '</span>');
    nano_message($kind, $text);
    echo '</div>' . PHP_EOL;
    nano_footer();
    exit;
}
