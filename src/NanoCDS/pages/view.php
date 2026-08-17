<?php
/**
 * Nano CDS - raw file delivery.
 *
 * This is the endpoint every dependent project talks to, so its behaviour is
 * deliberately conservative: same URL parameters, same headers, same bytes.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$name = nano_param('name');
$dir = nano_param('dir');

if ($name === '') {
    nano_message_page('Messages', 'error', 'File not found', 404);
}

if (nano_is_blocked($name)) {
    nano_message_page('Messages', 'warning', "You can't view files with this extension", 403);
}

$absolute = nano_resolve($dir === '' ? $name : $dir . '/' . $name);

if ($absolute === false || !is_file($absolute)) {
    nano_message_page('Messages', 'error', 'File not found', 404);
}

nano_send_asset($absolute, $name);
