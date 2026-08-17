<?php
/**
 * Nano CDS - page closing.
 *
 * Author: Artur `fhnb16` Tkachenko, 2020-2026
 *
 * Rendered through nano_footer(). The only JavaScript Nano CDS ships is the
 * clipboard handler below; every link on every page is built in PHP.
 */
declare(strict_types=1);

defined('NANO_BOOT') or exit('Direct access is not allowed.');

$nanoElapsed = round(microtime(true) - NANO_START, 4);

$nanoSelfSize = 0;
foreach (['index.php', 'config.php', 'router.php', 'url_parser.php', 'header.php', 'footer.php'] as $nanoFile) {
    $nanoSelfSize += is_file(NANO_DIR . '/' . $nanoFile) ? (int) filesize(NANO_DIR . '/' . $nanoFile) : 0;
}
foreach (['lib', 'pages'] as $nanoSub) {
    foreach (glob(NANO_DIR . '/' . $nanoSub . '/*.php') ?: [] as $nanoFile) {
        $nanoSelfSize += (int) filesize($nanoFile);
    }
}
?>
</div>
    <footer>
        <p><a href="<?= e(nano_pretty_url('about')) ?>" style="color:white;" class="btnv1">About</a>
           <a href="<?= e(nano_pretty_url('support')) ?>" style="color:white;" class="btnv1">Support</a>
           <a href="<?= e(nano_pretty_url('tools')) ?>" style="color:white;" class="btnv1">Tools</a></p>
        <p>Made with <span title="Page generated in <?= e((string) $nanoElapsed) ?> seconds.&#010;Nano CDS size is <?= e(nano_format_bytes($nanoSelfSize, 1)) ?>&#010;Version: <?= e((string) nano_config('version')) ?>">&#10084;&#65039;</span> by <a href="//fhnb.ru" class="btnv1 smol" style="color:white;">fhnb16</a> <br /> 2020 - <?= date('Y') ?></p>
    </footer>
<script>
document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-copy]');
    if (!button) { return; }
    var field = document.getElementById(button.getAttribute('data-copy'));
    if (!field) { return; }
    field.focus();
    field.select();
    var done = function () { button.textContent = 'Copied'; setTimeout(function () { button.textContent = 'Copy'; }, 1200); };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(field.value).then(done, function () {});
    } else if (document.execCommand) {
        document.execCommand('copy');
        done();
    }
});
</script>
</body>
</html>
