<?php
/*
Author: Artur `fhnb16` Tkachenko
2020
*/
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);
?>
</div>
    <footer>
        <p><a href="?page=about" style="color:white;" class="btnv1">About</a> <a href="<?echo $rootDir ?? "/assets/" ?>?page=support" style="color:white;" class="btnv1">Support</a> <a href="?page=tools" style="color:white;" class="btnv1">Tools</a></p>
        <p>Made with <span title="<?echo 'Page generated in '.$total_time.' seconds.';?>&#010;Nano CDS size is <?echo formatBytes(filesize('index.php')+filesize('footer.php')+filesize('header.php')+filesize('url_parser.php'),1);?>&#010;Version: <?echo $Version;?>">❤️</span> by <a href="//fhnb.ru" class="btnv1 smol" style="color:white;">fhnb16</a> <br /> 2020 - <? echo date("Y"); ?></p>
    </footer>
<script type="text/javascript">
document.title = document.title+'<?echo $SearchCount;?>';
document.getElementById("searchCount").title = "<?echo $SearchCount;?>";
</script>
<script type="text/javascript">
function beautifyURL(url) {
    var urlObj = new URL(url);
    var params = new URLSearchParams(urlObj.search);
    var page = params.get('page');

    let newPath = `<?echo $rootDir ?? "/assets/" ?>${page}`;
    
    params.delete('page');
    
    if (page === 'view') {
        var dir = params.get('dir');
        var name = params.get('name');
        var tempDir = params.get('dir').split('/');
        if (dir && name) {
            newPath += `/${tempDir[0]}/v/${tempDir[1]}/f/${name}`;
        }
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

function updateDownloadLinks() {
    document.querySelectorAll('.downloadIcon').forEach(icon => {
        const parentLink = icon.closest('a');
        if (parentLink) {
            const newLink = document.createElement("a");
            const originalHref = parentLink.getAttribute('href');
            const newHref = beautifyURL('https://dev.fhnb.ru'+originalHref.replace("https", ""));
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