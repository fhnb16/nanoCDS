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
        <p><a href="?page=about" style="color:white;" class="btnv1">About</a> <a href="//fhnb.ru/photos/?page=support" style="color:white;" class="btnv1">Support</a> <a href="?page=tools" style="color:white;" class="btnv1">Tools</a></p>
        <p>Made with <span title="<?echo 'Page generated in '.$total_time.' seconds.';?>&#010;Nano CDS size is <?echo formatBytes(filesize('index.php')+filesize('footer.php')+filesize('header.php'),1);?>&#010;Version: <?echo sprintf("%.1f", $Version);?>">❤️</span> by <a href="//fhnb.ru" class="btnv1 me" style="color:white;">fhnb16</a> <br /> 2020</p>
    </footer>
<script type="text/javascript">
document.title = document.title+'<?echo $SearchCount;?>';
document.getElementById("searchCount").title = "<?echo $SearchCount;?>";
</script>
</body>
</html>