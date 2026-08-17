<?php
/**
 * Nano CDS - HTTP responses.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

/** Strip anything that could split a header value (CRLF injection). */
function nano_header_safe(string $value): string
{
    return trim(str_replace(["\r", "\n", "\0"], '', $value));
}

/**
 * Cross-origin access.
 *
 * Assets and the JSON API are meant to be consumed by other sites, so they are
 * world readable. HTML pages of the browser UI are not - they do not need it,
 * and a blanket wildcard on every response is needless surface.
 */
function nano_send_cors(): void
{
    if (headers_sent()) {
        return;
    }
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Range');
    header('Access-Control-Expose-Headers: Content-Length, Content-Range');
    header('Timing-Allow-Origin: *');
}

/** Content type for a file: explicit map first, detection second. */
function nano_mime(string $absolute, string $name): string
{
    $map = nano_config('mime');
    $ext = nano_ext($name);
    if (isset($map[$ext])) {
        return $map[$ext];
    }

    $detected = @mime_content_type($absolute);

    return ($detected !== false && $detected !== '') ? $detected : 'application/octet-stream';
}

/** Send a file to the client. Terminates the request. */
function nano_send_asset(string $absolute, string $name): never
{
    $maxAge = (int) nano_config('asset_max_age');

    $mime = nano_mime($absolute, $name);

    http_response_code(200);
    nano_send_cors();
    header('Content-Type: ' . $mime);

    // An SVG opened directly is a document and could carry scripts. Embedding
    // it through <img>, <use> or CSS is unaffected by this header.
    if (str_starts_with($mime, 'image/svg')) {
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
    }

    header('Content-Length: ' . (string) filesize($absolute));
    header('Content-Transfer-Encoding: Binary');
    header('Content-Disposition: inline; filename="' . nano_header_safe(basename($name)) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=' . $maxAge);

    readfile($absolute);
    exit;
}

/** Send a JSON document. Terminates the request. */
function nano_send_json(array $data, int $status = 200): never
{
    http_response_code($status);
    nano_send_cors();
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=300');

    // JSON_HEX_* escapes < > & ' " so the document stays inert even if a
    // client ever renders it into HTML or embeds it in a <script> block.
    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    exit;
}

/** Redirect. Terminates the request. */
function nano_redirect(string $url, int $status = 301): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . nano_header_safe($url), true, $status);
    exit;
}
