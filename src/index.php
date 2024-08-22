<?
/*
Author: Artur `fhnb16` Tkachenko
2020-2024
*/

// Create folder with name `__hidden` to hide files from Nano CDS
$Version = 1.5;

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

function formatBytes($bytes, $precision = 2) {
    $unit = ["B", "KB", "MB", "GB"];
    $exp = floor(log($bytes, 1024)) | 0;
    return round($bytes / (pow(1024, $exp)), $precision)." ".$unit[$exp];
}

if(empty($_GET) && !isset($_GET["lib"]) && !isset($_GET["page"])){
    main:
    include_once('header.php');
?>
<div class="group">
    <span class="group-item group-item-action header">
        <a class="color-gray" href="<?echo basename(__FILE__);?>">Nano CDS</a><span style="float:right;"><a href="?page=about" class="btnv1">What is it? -></a></span>
    </span>
    <?
        $dir = new DirectoryIterator(__DIR__);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot() && $fileinfo->getFilename() != "__hidden") {
                
    ?>
    <a href="?page=dir&name=<?echo $fileinfo->getFilename();?>" class="group-item group-item-action"><span class="uppertext">
            <?echo $fileinfo->getFilename();?></span></a>
    <?
            }
        }
    ?>
    <!--<a href="?page=signin" class="group-item group-item-action footer">Sign In</a>/-->
</div>
<?
include_once('footer.php');
exit;
}else{

    if(isset($_GET["page"])){
        switch($_GET["page"]){
            case "about": goto about;
            break;
            case "dir": goto dir;
            break;
            case "view": goto view;
            break;
            case "search": goto search;
            break;
            case "latest": goto latest;
            break;
            case "support": goto support;
            break;
            case "tools": goto tools;
            break;
            default: goto main;
            break;
            }

about:
        if($_GET["page"] == "about"){
            $PageTitle = "About"; include_once('header.php');
            ?>
<div class="group">
    <span class="group-item group-item-action header">
        <a class="color-gray" href="<?echo basename(__FILE__);?>">Nano CDS</a> &middot; <span class="uppertext">About</span><a href="javascript:history.back()" class="btnv1" style="float:right;">
            <- Go Back</a> </span> <p>CDS means Content Delivery System (Repository), this system was developed by <a href="//fhnb.ru" class="btnv1 me" style="color:white;">fhnb16</a> to simplify the delivery and storage of various CSS frameworks and JS libraries, software or other files which are necessary
                in work.</p>
                <p>All rights of the frameworks presented in Nano CDS belong to their owners.</p>
                <p>If you want to support me, please visit <a href="//fhnb.ru/photos/?page=support" class="btnv1" style="color:white;">this page</a></p>
                <p>Write me - <a class="btnv1" style="color:white;" href="mailto:artur@fhnb.ru">artur@fhnb.ru</a></p>
                <p>Made by <a href="//fhnb.ru" class="btnv1" style="color:white;">fhnb16</a> in 2020</p>
                <p>Nano CDS size is <?echo formatBytes(filesize('index.php')+filesize('footer.php')+filesize('header.php'),1);?> (3 files)</p>
                <p>Version: <?echo $Version;?></p>
</div>
<?
            include_once('footer.php');
            exit;   
        }
        dir:
        if($_GET["page"] == "dir" && isset($_GET["name"]) && ($_GET["name"] != "../.." && $_GET["name"] != "./." && $_GET["name"] != ".." && $_GET["name"] != ".")){

            $PageTitle = "Directory: ".$_GET["name"]; include_once('header.php');
            ?>
<div class="group">
    <span class="group-item group-item-action header">
        <a class="color-gray" href="<?echo basename(__FILE__);?>">Nano CDS</a> &middot;
        <span class="uppertext"><?echo str_replace(DIRECTORY_SEPARATOR , " &bull; ", $_GET["name"]);?></span><a href="javascript:history.back()" class="btnv1" style="float:right;">
            <- Go Back</a> </span> <? 


                            $result = array();

    if(file_exists($_GET["name"])){
                            $cdir = scandir(str_replace("..", "", str_replace("__hidden", "", $_GET["name"])),1);
                            foreach ($cdir as $key => $value)
                            {
                                
    if(strpos($value, '.php') || strpos($value, '.htm')) continue;
                               if (!in_array($value,array(".","..", "__hidden")))
                               {
                                if(is_file($_GET["name"].DIRECTORY_SEPARATOR .$value)){
                                    ?>
        <a href="?page=view&dir=<?echo $_GET["name"];?>&name=<?echo $value;?>" class="group-item group-item-action"><?echo $value;?><span style="float:right;"><?echo formatBytes(exec('du -bcS ' . $_GET["name"].DIRECTORY_SEPARATOR .$value))?></span></a>
                <?
                                }else if(is_dir($_GET["name"].DIRECTORY_SEPARATOR .$value)){
                                    ?>
        <a href="?page=dir&name=<?echo $_GET["name"].DIRECTORY_SEPARATOR .$value;?>" class="group-item group-item-action"><span class="uppertext"><?echo $value;?></span><span style="float:right;"><?echo formatBytes(exec('du -bcS ' . $_GET["name"].DIRECTORY_SEPARATOR .$value))?></span></a>
                <?
                                }
                               }
                            }

                            if(count($cdir) <= 2){
                                echo '<p>Message: <span class="color-grey">Nothing found</span>.</p>';
                             }
    
    }
    else{
        
        echo '<p><span class="color-warning">Warning</span>: <span class="color-grey">Folder not exist</span>.</p>';
    }
                ?>
                <!--<a href="?page=signin" class="group-item group-item-action footer">Sign In</a>/-->
</div>
<?
            include_once('footer.php');
            exit;



            
        }
        view:
        if($_GET["page"] == "view"){

$attachment_location = $_GET["dir"].DIRECTORY_SEPARATOR.$_GET["name"];
        if (file_exists($attachment_location)) {

            header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
            header("Cache-Control: public");
            switch(pathinfo($_GET["name"], PATHINFO_EXTENSION)){
                case "css":
                    header("Content-Type: text/css");
                break;
                case "js":
                    header('Content-Type: application/javascript');
                break;
                case "ttf":
                    header('Content-Type: application/x-font-ttf');
                break;
                case "php":

                    $PageTitle = ""; include_once('Messages.php');
                    ?>
                    <div class="group">
            <span class="group-item group-item-action header">
            <a class="color-gray" href="<?echo basename(__FILE__);?>">Nano CDS</a> &middot; <span class="uppertext">Messages</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
        </span>
            <p><span class='color-warning'>Warning</span>: <span class='color-grey'>You can't view files with this extension</span>.</p>
        </div>
        <?
                    include_once('footer.php');
            exit;     
                break;
                default:
                    header("Content-Type:".mime_content_type($attachment_location));
            break;
            }
            header("Content-Length:".filesize($attachment_location));
            header("Content-Transfer-Encoding: Binary");
            header('Content-Disposition: inline; filename="'.$_GET["name"].'"');

                    header('Cache-Control: max-age=86400');
            //header('Content-Disposition: attachment; filename="'.$_GET["name"].'"');
            //echo mime_content_type($attachment_location);
            
            readfile($attachment_location);
            exit;        
        } else {
            $PageTitle = "Messages"; include_once('header.php');
            ?>
            <div class="group">
    <span class="group-item group-item-action header">
    <a class="color-gray" href="<?echo basename(__FILE__);?>">Nano CDS</a> &middot; <span class="uppertext">Messages</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
</span>
    <p><span class='color-error'>Error</span>: <span class='color-grey'>File not found</span>.</p>
</div>
<?
            include_once('footer.php');
            exit;     
        } 
            
        }
        tools:
                if($_GET["page"] == "tools"){
                    $PageTitle = "Tools"; include_once('header.php');
        ?>
        
<div class="group">
  <span class="group-item group-item-action header">
  <a class="color-gray" href="<?echo basename(__FILE__);?>">Nano CDS</a> &middot; <span class="uppertext">Tools</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
  </span>
  <?
  $count1 = 0;
  $count2 = 0;
  $count3 = 0;
  foreach (new DirectoryIterator(__DIR__) as $fileInfo) {
    if(strpos($fileInfo, "__hidden") !== false) continue;
    if($fileInfo->isDir() && !$fileInfo->isDot()){
        $count1++;
    }
  }

  if( $handle = opendir(__DIR__) ) { 
      
    while( ($file = readdir($handle)) !== false ) { 
        if( !in_array($file, array('.', '..', '__hidden')) && !is_dir($file))  
            $count2++; 
    } 
} 
$dir_iterator = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
// could use CHILD_FIRST if you so wish
foreach ($iterator as $file) {
    if(is_dir($file) && !is_file($file)) continue;
    if(strpos($file, '.php') || strpos($file, '.htm') || strpos($file, '..') || strpos($file, '/.') || strpos($file, "__hidden") !== false) continue;
    //echo $file."<br/>";
    $count2++;
}

function getDirContents($path) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    $files = array(); 
    foreach ($rii as $file)
    //if(strpos($file, '__hidden') || strpos($file, '..')) continue;
    
        if ($file->isDir()){
            if(strpos($file, "__hidden") !== false || strpos($file, "..") !== false) continue;
            $files[] = $file; //->getPathname()
        }
            //var_dump($files);
    return $files;
}
$count3 = count(getDirContents(__DIR__));
  ?>
  <p>Projects in repository: <?echo $count1;?> and size is <?echo formatBytes(exec('du -bcS ' . __DIR__))?>.</p>
  <p>Total Files in repository: <?echo $count2;?> in <?echo $count3;?> folders.</p>
  <p>Fild latest library or framework version:</p>
<form action="<?echo basename(__FILE__);?>" method="GET" class="form-inline">
<input type="hidden" name="page" value="latest" />
  <div class="form-group">
    <input type="text" class="form-control" name="asset" placeholder="Asset Name" required>
  </div>
  <?/*
  <div class="form-group">
    <input type="text" class="form-control" name="file" placeholder="File Name" title="Not required">
  </div>
  */?>
  <div class="form-group">
<select name="type" class="form-control">
      <option value="any">ANY</option>
      <option value="css">CSS</option>
      <option value="js">JS</option>
      <option value="json">JSON</option>
      <option value="xml">XML</option>
      <option value="svg">SVG</option>
      <option value="png">PNG</option>
      <option value="gif">GIF</option>
      <option value="jpg">JPG</option>
      <option value="ttf">TTF</option>
      <option value="zip">ZIP</option>
      <option value="rar">RAR</option>
      <option value="7z">7Z</option>
      <option value="exe">EXE</option>
      <option value="txt">TXT</option>
      <option value="html">HTML</option>
    </select>
  </div>
  <div class="form-group">
<select name="size" class="form-control">
      <option selected value="0">ANY</option>
      <option value="1">MIN</option>
      <option value="2">FULL</option>
    </select>
  </div>
  <div class="form-group">
<select name="auto" class="form-control">
      <option value="1">OPEN</option>
      <option selected value="0">VIEW</option>
      <option value="-1">LINK</option>
    </select>
  </div>
  <div class="form-group">
  <button type="submit" class="btn btn-default">Find Latest</button>
</div>
</form>
<p>Search by File Name:</p>
<form action="<?echo basename(__FILE__);?>" method="GET" class="form-inline">
<input type="hidden" name="page" value="search" />
  <div class="form-group">
    <input type="text" class="form-control" placeholder="File Name" name="query" required>
  </div>
  <div class="form-group">
    <button type="submit" class="btn btn-default">Find File</button>
  </div>
</form>
<p title="Example: `b*r?p` - bootstrap, gr[ae]y - gray/grey, `[0-9]`.">You can use `*`, `?` or `[...]` in search query.</p>
</div>
        
        <?          
                    include_once('footer.php');
                    exit;
                }
        search:
        if($_GET["page"] == "search"){

            $_GET["query"] = str_replace("__hidden", "", $_GET["query"]);

            $PageTitle = "Search: ".$_GET["query"]; include_once('header.php');
        ?>
        <div class="group">
            <span class="group-item group-item-action header">
            <a class="color-gray" href="<?echo basename(__FILE__);?>">Nano CDS</a> &middot; <span id="searchCount" class="uppertext">Search: `<?echo $_GET["query"];?>`</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
            </span>
            <?
                //$dir = glob($_GET["query"].'.*');
 
                //$path = __DIR__ . '/tmp';
                $files = glob_tree_search(__DIR__, $_GET["query"].'.*');
                //print_r($files);
                //$files = glob(__DIR__ . '/*'.$_GET["query"].'*.*');
                //var_dump($files);

                $SearchCount = " (Files: ".count($files).")";
                if(count($files) < 1){
                    ?>
            <p><span class='color-info'>Info</span>: <span class='color-grey'>Nothing found</span>!</p>
        <?   
                } 
                foreach($files as $file) { 
                    
    if(strpos($file, '.php') || strpos($file, '.htm') || strpos($file, "__hidden") !== false) continue;
                        if($_GET["query"] == ""){
                            ?>
                    <p><span class='color-info'>Info</span>: <span class='color-grey'>Search Query is empty</span>!</p>
                <?   
                break;
                        } 
                        if (is_file($file) && !strpos(basename($file), ".php") && strpos($file, "__hidden") !== true && !strpos(basename($file), ".htm") && !strpos(basename($file), ".html")) {
                        
                            //echo $file."<br/>";
            ?>
            <a href="?page=view&dir=<?echo str_replace(basename($file), "", str_replace(__DIR__, "", $file));?>&name=<?echo basename($file);?>" class="group-item group-item-action"><span class="uppertext"><?echo basename($file);?></span> <span style="float:right;">[ <?echo str_replace(basename($file), "", str_replace(__DIR__, "", $file));?> ]</span></a>
            <?
                                    /* title="<?echo formatBytes(exec('du -bcS ' . str_replace(basename($file), "", str_replace(__DIR__, "", $file)).DIRECTORY_SEPARATOR .basename($file)))?>"*/
                    }
                }
            ?>
            <!--<a href="?page=signin" class="group-item group-item-action footer">Sign In</a>/-->
        </div>
        <?
        include_once('footer.php');
        exit;
            
        }
        latest:
        if($_GET["page"] == "latest"){
            ob_start();
            $_GET["asset"] = str_replace("__hidden", "", $_GET["asset"]);
            

            $PageTitle = "Search: ".$_GET["asset"]; include_once('header.php');

$fileType = ".*";
switch($_GET["type"]){
    case "any": $fileType = ".*";   break;
    case "css": $fileType = ".css"; break;
    case "js":  $fileType = ".js";  break;
    case "json":  $fileType = ".json";  break;
    case "xml":  $fileType = ".xml";  break;
    case "svg": $fileType = ".svg"; break;
    case "png": $fileType = ".png"; break;
    case "gif": $fileType = ".gif"; break;
    case "jpg": $fileType = ".jp?g"; break;
    case "ttf": $fileType = ".ttf"; break;
    case "zip": $fileType = ".zip"; break;
    case "rar": $fileType = ".rar"; break;
    case "7z": $fileType = ".7z"; break;
    case "exe": $fileType = ".exe"; break;
    case "txt": $fileType = ".txt"; break;
    case "html": $fileType = ".htm?"; break;
    default:    $fileType = ".*";   break;
}
$MinOrMax = "*";
switch($_GET["size"]){
    case "0": $MinOrMax = "";    break;
    case "1": $MinOrMax = ".min"; break;
    case "2": $MinOrMax = "";    break;
     default: $MinOrMax = "";    break;
}


        ?>
        <div class="group">
            <span class="group-item group-item-action header">
            <a class="color-gray" href="<?echo basename(__FILE__);?>">Nano CDS</a> &middot; <span id="searchCount" class="uppertext">Search: `<?echo $_GET["asset"];?>`</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
            </span>
            <?
                //$dir = glob($_GET["asset"].'.*');
 
                //$path = __DIR__ . '/tmp';
                $files = glob_tree_search(__DIR__, $_GET["asset"].$MinOrMax.$fileType);
                //print_r($files);
                //$files = glob(__DIR__ . '/*'.$_GET["asset"].'*.*');
                //var_dump($files);
                $SearchCount = " (Files: ".count($files).")";
                if(count($files) < 1){
                    ?>
            <p><span class='color-info'>Info</span>: <span class='color-grey'>Nothing found</span>!</p>
        <?   
                } 
                foreach(array_reverse($files) as $file) { // array_reverse($files)
                    
                    //echo $file."<br/>";
                    if(strpos($file, '.php') || strpos($file, '.htm') || strpos($file, "__hidden") !== false) continue;

                    if($_GET["size"] == "2"){
                        if(strpos(basename($file), '.min')){
                        continue;
                        }
                    }


                        if($_GET["asset"] == ""){
                            ?>
                    <p><span class='color-info'>Info</span>: <span class='color-grey'>Search Query is empty</span>!</p>
                <?   
                break;
                        } 
                        if (is_file($file) && !strpos(basename($file), ".php") && !strpos(basename($file), ".htm") && !strpos(basename($file), ".html")) {
                            if($_GET["auto"] == -1){
                                $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                                $fullUrl = str_replace("auto=-1", "auto=1", $fullUrl);
                                echo '<center><a id="latestLink" href="'.$fullUrl.'">'.$fullUrl.'</a></center>';
                                exit();
                            }
                            if($_GET["auto"] == 1){
                                $tempDirReg = getDirectoryPath($file);
                                $tempLink = basename(__FILE__).'?page=view&dir='.$tempDirReg.'&name='.basename($file);
                                //echo '<script>window.location="'.$tempLink.'"</script>';
                                ob_end_clean();
                                header("Location: ".$tempLink."", true, 301);
                                exit();
                            }
            ?>
            <a href="?page=view&dir=<?echo str_replace(basename($file), "", str_replace(__DIR__, "", $file));?>&name=<?echo basename($file);?>" class="group-item group-item-action"><span class="uppertext"><?echo basename($file);?></span> <span style="float:right;">[ <?echo str_replace(basename($file), "", str_replace(__DIR__, "", $file));?> ]</span></a>
            <?
                        /* title="<?echo formatBytes(exec('du -bcS ' . str_replace(basename($file), "", str_replace(__DIR__, "", $file)).DIRECTORY_SEPARATOR .basename($file)))?>"*/
                    }
                }
            ?>
            <!--<a href="?page=signin" class="group-item group-item-action footer">Sign In</a>/-->
        </div>
        <?
        
        include_once('footer.php');
        exit;
            


            
        }
        support:
        if($_GET["page"] == "support"){



            goto main;


            
        }
        signin:
        if($_GET["page"] == "signin"){



            goto main;


            
        }



    }else {
        goto main;
    }



}


function glob_tree_search($path, $pattern, $_base_path = null)
{
	if (is_null($_base_path)) {
		$_base_path = '';
	} else {
		$_base_path .= basename($path) . '/';
	}
 
	$out = array();
	foreach(glob($path . '/' . $pattern, GLOB_BRACE) as $file) {
		$out[] = $_base_path . basename($file);
	}
	
	foreach(glob($path . '/*', GLOB_ONLYDIR) as $file) {
		$out = array_merge($out, glob_tree_search($file, $pattern, $_base_path));
	}
	return $out;
}

function getDirectoryPath($file) {
    // Разбиваем путь по разделителю директорий
    $parts = explode('/', $file);

    // Находим последний элемент массива (имя файла) и удаляем его
    array_pop($parts);

    // Объединяем оставшиеся части пути обратно
    return implode('/', $parts);
  }

 
?> ?>