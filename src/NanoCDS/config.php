<?php
/**
 * Nano CDS - configuration.
 *
 * Everything that an administrator may want to change lives here.
 * The file returns a plain array; no side effects.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

return [

    /* Version reported on the About page and in the JSON API. */
    'version' => '2.0',

    /*
     * Public URL prefix of the repository, e.g. "/assets/".
     * null  = detect automatically from SCRIPT_NAME (recommended).
     * Set it explicitly only if the automatic value is wrong.
     */
    'base_path' => null,

    /*
     * Canonical host used when Nano CDS has to build an absolute URL.
     * null = take it from the request (sanitised).
     * Set it (e.g. 'cdn.example.com') to be immune to Host header spoofing.
     */
    'canonical_host' => null,

    /* Directory names that are never listed, searched or served. */
    'hidden' => ['__hidden', 'NanoCDS'],

    /*
     * Extensions that must never be sent to a client.
     * Anything executable on the server side belongs here.
     */
    'blocked_ext' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'phtml', 'phar',
        'htaccess', 'htpasswd', 'ini', 'env', 'sh', 'bash', 'bat', 'cmd',
        'cgi', 'pl', 'py', 'rb', 'lua', 'jsp', 'asp', 'aspx',
    ],

    /* Extensions hidden from directory listings and search results. */
    'listing_hidden_ext' => ['php', 'phtml', 'htm', 'html'],

    /* Hard cap on search / API result sets, protects against huge scans. */
    'max_results' => 2000,

    /*
     * Show the total size of sub-folders in a directory listing.
     *
     * Computing it means walking each sub-tree, so on a very large repository
     * this is the slowest thing on the page. Set it to false to show sizes for
     * files only - listings then cost a single readdir().
     */
    'dir_size' => true,

    /* How many bytes of a text file the preview page renders. */
    'preview_bytes' => 262144,

    /* Extensions the preview page renders as text. */
    'preview_text_ext' => [
        'css', 'js', 'mjs', 'cjs', 'json', 'map', 'xml', 'svg', 'txt', 'md',
        'csv', 'yml', 'yaml', 'less', 'scss', 'ts',
    ],

    /* Extensions the preview page renders as an image. */
    'preview_image_ext' => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg', 'ico', 'bmp'],

    /* Explicit MIME map - keeps `nosniff` safe and mime_content_type() honest. */
    'mime' => [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'mjs'   => 'application/javascript',
        'cjs'   => 'application/javascript',
        'json'  => 'application/json',
        'map'   => 'application/json',
        'xml'   => 'application/xml',
        'svg'   => 'image/svg+xml',
        'html'  => 'text/html; charset=utf-8',
        'htm'   => 'text/html; charset=utf-8',
        'txt'   => 'text/plain; charset=utf-8',
        'md'    => 'text/markdown; charset=utf-8',
        'csv'   => 'text/csv; charset=utf-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'avif'  => 'image/avif',
        'ico'   => 'image/x-icon',
        'bmp'   => 'image/bmp',
        'ttf'   => 'application/x-font-ttf',
        'otf'   => 'font/otf',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'eot'   => 'application/vnd.ms-fontobject',
        'wasm'  => 'application/wasm',
        'pdf'   => 'application/pdf',
        'zip'   => 'application/zip',
        'rar'   => 'application/vnd.rar',
        '7z'    => 'application/x-7z-compressed',
        'gz'    => 'application/gzip',
        'exe'   => 'application/vnd.microsoft.portable-executable',
        'mp4'   => 'video/mp4',
        'webm'  => 'video/webm',
        'mp3'   => 'audio/mpeg',
        'wav'   => 'audio/wav',
    ],

    /* Cache lifetime (seconds) sent with every asset. */
    'asset_max_age' => 86400,
];
