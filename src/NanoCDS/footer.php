<?php
/*
Author: Artur `fhnb16` Tkachenko
2020
*/
$finish = microtime(true);
$total_time = round(($finish - ($start ?? $finish)), 4);

$nanoDir = defined('NANO_DIR') ? NANO_DIR : __DIR__;
$nanoSelfSize = 0;
foreach (array('index.php', 'footer.php', 'header.php', 'url_parser.php') as $nanoFile) {
    $nanoSelfSize += is_file($nanoDir . '/' . $nanoFile) ? filesize($nanoDir . '/' . $nanoFile) : 0;
}
$SearchCount = $SearchCount ?? '';
?>
</div>
    <footer>
        <p><a href="?page=about" style="color:white;" class="btnv1">About</a> <a href="<?= htmlspecialchars((string) ($rootDir ?? "/assets/"), ENT_QUOTES) ?>?page=support" style="color:white;" class="btnv1">Support</a> <a href="?page=tools" style="color:white;" class="btnv1">Tools</a></p>
        <p>Made with <span title="<?= 'Page generated in ' . $total_time . ' seconds.'; ?>&#010;Nano CDS size is <?= function_exists('formatBytes') ? formatBytes($nanoSelfSize, 1) : $nanoSelfSize; ?>&#010;Version: <?= htmlspecialchars((string) ($Version ?? ''), ENT_QUOTES); ?>">❤️</span> by <a href="//fhnb.ru" class="btnv1 smol" style="color:white;">fhnb16</a> <br /> 2020 - <?= date("Y"); ?></p>
    </footer>
<script type="text/javascript">
    var SearchCount = <?= json_encode((string) $SearchCount, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    if(SearchCount != ""){
        document.title = document.title+SearchCount;
        var searchCountEl = document.getElementById("searchCount");
        if (searchCountEl) {
            searchCountEl.setAttribute('title', SearchCount);
        }
    }
</script>
<script type="text/javascript">
function beautifyURL(url) {
    var urlObj = new URL(url);
    var params = new URLSearchParams(urlObj.search);
    var page = params.get('page');

    let newPath = `<?= htmlspecialchars((string) ($rootDir ?? "/assets/"), ENT_QUOTES) ?>${page}`;
    
    params.delete('page');
    
    if (page === 'view') {
        var dir = params.get('dir');
        var name = params.get('name');
        newPath += `/${dir}/f/${name}`;
        /*var tempDir = params.get('dir').split('/');
        if (dir && name) {
            var verCheck = isVersion(tempDir[1]);
            if(verCheck){
                newPath += `/${tempDir[0]}/${tempDir[1]}/f/${name}`;
            } else {
                newPath += `/${tempDir[0]}/${tempDir[1]}/f/${name}`;
            }
        }*/
    } else if (page === 'latest') {
        var asset = params.get('asset');
        var type = params.get('type') || 'any';
        var size = params.get('size') || '0';
        var auto = params.get('auto') || '1';

        if (asset) {
            newPath += `/${asset}/t/${type}/s/${size}/a/${auto}`;
        }
    } else {
        params.forEach((value, key) => {
            newPath += `/${value}`;
        });
    }

    return urlObj.origin + newPath;
}

function isVersion(version) {
    // Регулярное выражение для проверки формата версии
    const versionRegex = /^(\d+(\.\d+)*)([a-zA-Z])?$/;

    // Проверяем, соответствует ли строка регулярному выражению
    return versionRegex.test(version);
}

function updateDownloadLinks() {
    document.querySelectorAll('.downloadIcon').forEach(icon => {
        var parentLink = icon.closest('a');
        if (parentLink) {
            var newLink = document.createElement("a");
            var originalHref = parentLink.getAttribute('href');
            var newHref = beautifyURL('https://dev.fhnb.ru'+originalHref.replace("https", "")).replace("/undefined", "");
            newLink.setAttribute('href', newHref);
            newLink.setAttribute('class', 'btnv1 smol');
            newLink.innerText = "✨";
            newLink.setAttribute('title', 'Beauty Link');
            icon.appendChild(newLink);
        }
    });
}

document.addEventListener('DOMContentLoaded', updateDownloadLinks);

</script>
</body>
</html>