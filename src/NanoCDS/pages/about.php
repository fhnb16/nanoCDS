<?php
/**
 * Nano CDS - about page.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$selfFiles = 0;
$selfSize = 0;
foreach (['', '/lib', '/pages'] as $sub) {
    foreach (glob(NANO_DIR . $sub . '/*.php') ?: [] as $file) {
        $selfFiles++;
        $selfSize += (int) filesize($file);
    }
}

nano_header('About');
?>
<div class="group">
<?php nano_crumb('<span class="uppertext">About</span>'); ?>
    <p>CDS means Content Delivery System (Repository), this system was developed by <a href="//fhnb.ru" class="btnv1 smol" style="color:white;">fhnb16</a> to simplify the delivery and storage of various CSS frameworks and JS libraries, software or other files which are necessary in work.</p>
    <p>All rights of the frameworks presented in Nano CDS belong to their owners.</p>
    <p>If you want to support me, please visit <a href="//fhnb.ru/photos/?page=support" class="btnv1" style="color:white;">this page</a></p>
    <p>Write me - <a class="btnv1" style="color:white;" href="mailto:artur@fhnb.ru">artur@fhnb.ru</a></p>
    <p>Made by <a href="//fhnb.ru" class="btnv1" style="color:white;">fhnb16</a> in 2020</p>
    <p>Source code on <a href="//github.com/fhnb16/nanoCDS" class="btnv1" style="color:white;">Github</a></p>
    <p>Nano CDS size is <?= e(nano_format_bytes($selfSize, 1)) ?> (<?= e((string) $selfFiles) ?> files)</p>
    <p>Version: <?= e((string) nano_config('version')) ?>, <?= e(date('F d Y H:i:s', (int) filemtime(__FILE__))) ?></p>
    <p>Running on PHP <?= e(PHP_VERSION) ?></p>
</div>
<?php
nano_footer();
