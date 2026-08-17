<?php
/**
 * Nano CDS - front controller.
 *
 * Author: Artur `fhnb16` Tkachenko, 2020-2026
 * Source: https://github.com/fhnb16/nanoCDS
 *
 * This file only bootstraps and dispatches. All logic lives in lib/,
 * all output in pages/ and in header.php / footer.php.
 *
 * Create a folder named `__hidden` to hide files from Nano CDS.
 */
declare(strict_types=1);

define('NANO_BOOT', true);
define('NANO_DIR', __DIR__);
define('NANO_ROOT_DIR', __DIR__ . '/../');
define('NANO_START', microtime(true));

/* Legacy constant: earlier versions exposed the repository root as ROOT. */
if (!defined('ROOT')) {
    define('ROOT', NANO_ROOT_DIR);
}

/**
 * Configuration accessor.
 *
 * @param string $key   Configuration key.
 * @param mixed  $fallback Returned when the key is absent.
 */
function nano_config(string $key, mixed $fallback = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require NANO_DIR . '/config.php';
    }

    return $config[$key] ?? $fallback;
}

require NANO_DIR . '/lib/path.php';
require NANO_DIR . '/lib/fs.php';
require NANO_DIR . '/lib/version.php';
require NANO_DIR . '/lib/http.php';
require NANO_DIR . '/lib/view.php';
require NANO_DIR . '/lib/legacy.php';
require NANO_DIR . '/router.php';
require NANO_DIR . '/url_parser.php';

$nanoPage = nano_route();

$nanoPageFile = NANO_DIR . '/pages/' . $nanoPage . '.php';
if (!is_file($nanoPageFile)) {
    $nanoPageFile = NANO_DIR . '/pages/main.php';
}

require $nanoPageFile;
