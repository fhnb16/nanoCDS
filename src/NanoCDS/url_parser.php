<?php
/**
 * Nano CDS - pretty URL parsing (compatibility wrapper).
 *
 * The implementation moved to router.php in 2.0. This file is kept because
 * older installations include it directly; it simply forwards to the router.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

if (!function_exists('parse_friendly_url')) {
    /** @deprecated Use nano_parse_pretty_url(). */
    function parse_friendly_url(): void
    {
        nano_parse_pretty_url();
    }
}
