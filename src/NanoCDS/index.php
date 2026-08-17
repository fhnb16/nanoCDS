<?php
/*
Author: Artur `fhnb16` Tkachenko
2020-2026
*/

// Create folder with name `__hidden` to hide files from Nano CDS
$Version = 1.9;
//$rootDir = ""; // root directory, `/assets/` or `/` or anything else..

define('ROOT', __DIR__ . '/../');
define('NANO_DIR', __DIR__);

$start = microtime(true);

include_once __DIR__ . '/url_parser.php';

/* ------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */

function formatBytes($bytes, $precision = 2)
{
    $bytes = is_numeric($bytes) ? (float) $bytes : 0.0;
    if ($bytes <= 0) {
        return "0 B";
    }
    $unit = ["B", "KB", "MB", "GB", "TB"];
    $exp = (int) floor(log($bytes, 1024));
    if ($exp < 0) {
        $exp = 0;
    }
    if ($exp > count($unit) - 1) {
        $exp = count($unit) - 1;
    }
    return round($bytes / pow(1024, $exp), $precision) . " " . $unit[$exp];
}

/** Escape for HTML output (text and attribute values). */
function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute, resolved path of the repository root. */
function nano_root()
{
    static $root = null;
    if ($root === null) {
        $resolved = realpath(ROOT);
        $root = $resolved !== false ? $resolved : rtrim(ROOT, '/\\');
    }
    return $root;
}

/** True when any path segment is hidden or belongs to Nano CDS itself. */
function nano_is_forbidden_segment($relative)
{
    $parts = preg_split('#[\\\\/]+#', (string) $relative, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($parts as $part) {
        if ($part === '__hidden' || $part === 'NanoCDS' || $part === '..') {
            return true;
        }
    }
    return false;
}

/**
 * Resolve a user supplied path against the repository root.
 * Returns the absolute path, or false when it does not exist,
 * escapes the root, or points at hidden / system content.
 */
function nano_resolve($relative)
{
    $relative = str_replace('\\', '/', (string) $relative);
    $relative = trim($relative, '/');
    if ($relative === '' || str_contains($relative, "\0")) {
        return false;
    }
    if (nano_is_forbidden_segment($relative)) {
        return false;
    }

    $root = nano_root();
    $real = realpath($root . DIRECTORY_SEPARATOR . $relative);
    if ($real === false) {
        return false;
    }
    // Containment check: must live strictly inside the root.
    if (strncmp($real, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) !== 0) {
        return false;
    }
    // Re-check after symlink resolution.
    if (nano_is_forbidden_segment(substr($real, strlen($root)))) {
        return false;
    }
    return $real;
}

/** Recursive iterator that never descends into `__hidden` or `NanoCDS`. */
function nano_iterator($path)
{
    $inner = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
    $filtered = new RecursiveCallbackFilterIterator($inner, function ($current) {
        $name = $current->getFilename();
        return $name !== '__hidden' && $name !== 'NanoCDS';
    });
    return new RecursiveIteratorIterator($filtered, RecursiveIteratorIterator::SELF_FIRST);
}

/** Size of a file or, recursively, of a directory. Replaces `du -bcS`. */
function nano_size($path)
{
    if ($path === false || $path === null || !file_exists($path)) {
        return 0;
    }
    if (is_file($path)) {
        $size = @filesize($path);
        return $size === false ? 0 : $size;
    }

    $total = 0;
    try {
        foreach (nano_iterator($path) as $item) {
            if ($item->isFile()) {
                $total += $item->getSize();
            }
        }
    } catch (Throwable $e) {
        return $total;
    }
    return $total;
}

/** Sanitize a search pattern coming from the URL. */
function nano_clean_query($query)
{
    return str_replace(["__hidden", "NanoCDS", "..", "\0"], "", (string) ($query ?? ''));
}

if (empty($_GET) && !isset($_GET["lib"]) && !isset($_GET["page"])) {
    main:
    include_once NANO_DIR . '/header.php';
?>
<div class="group">
    <span class="group-item group-item-action header">
        <a class="color-gray" href="<?= e($rootDir ?? "/assets/") ?>">Nano CDS</a><span style="float:right;"><a href="?page=about" class="btnv1">What is it? -></a></span>
    </span>
    <?php
        $dir = new DirectoryIterator(ROOT);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot() && $fileinfo->getFilename() != "__hidden" && $fileinfo->getFilename() != "NanoCDS") {

    ?>
    <a href="?page=dir&name=<?= e($fileinfo->getFilename()); ?>" class="group-item group-item-action"><span class="uppertext">
            <?= e($fileinfo->getFilename()); ?></span></a>
    <?php
            }
        }
    ?>
    <!--<a href="?page=signin" class="group-item group-item-action footer">Sign In</a>/-->
</div>
<?php
include_once NANO_DIR . '/footer.php';
exit;
} else {

    if (isset($_GET["page"])) {
        switch ($_GET["page"]) {
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
        if ($_GET["page"] == "about") {
            $PageTitle = "About"; include_once NANO_DIR . '/header.php';
            ?>
<div class="group">
    <span class="group-item group-item-action header">
        <a class="color-gray" href="<?= e($rootDir ?? "/assets/") ?>">Nano CDS</a> &middot; <span class="uppertext">About</span><a href="javascript:history.back()" class="btnv1" style="float:right;">
            <- Go Back</a> </span> <p>CDS means Content Delivery System (Repository), this system was developed by <a href="//fhnb.ru" class="btnv1 smol" style="color:white;">fhnb16</a> to simplify the delivery and storage of various CSS frameworks and JS libraries, software or other files which are necessary
                in work.</p>
                <p>All rights of the frameworks presented in Nano CDS belong to their owners.</p>
                <p>If you want to support me, please visit <a href="//fhnb.ru/photos/?page=support" class="btnv1" style="color:white;">this page</a></p>
                <p>Write me - <a class="btnv1" style="color:white;" href="mailto:artur@fhnb.ru">artur@fhnb.ru</a></p>
                <p>Made by <a href="//fhnb.ru" class="btnv1" style="color:white;">fhnb16</a> in 2020</p>
                <p>Source code on <a href="//github.com/fhnb16/nanoCDS" class="btnv1" style="color:white;">Github</a></p>
                <p>Nano CDS size is <?= formatBytes(filesize(NANO_DIR . '/index.php') + filesize(NANO_DIR . '/footer.php') + filesize(NANO_DIR . '/header.php') + filesize(NANO_DIR . '/url_parser.php'), 1); ?> (4 files)</p>
                <p>Version: <?= e($Version); ?>, <?= date("F d Y H:i:s", filemtime(__FILE__)) ?></p>
</div>
<?php
            include_once NANO_DIR . '/footer.php';
            exit;
        }
        dir:
        if ($_GET["page"] == "dir" && isset($_GET["name"]) && ($_GET["name"] != "../.." && $_GET["name"] != "./." && $_GET["name"] != ".." && $_GET["name"] != ".")) {

            $dirName = (string) $_GET["name"];
            $PageTitle = "Directory: " . $dirName; include_once NANO_DIR . '/header.php';
            ?>
<div class="group">
    <span class="group-item group-item-action header">
        <a class="color-gray" href="<?= e($rootDir ?? "/assets/") ?>">Nano CDS</a> &middot;
        <span class="uppertext"><?= str_replace(DIRECTORY_SEPARATOR, " &bull; ", e($dirName)); ?></span><a href="javascript:history.back()" class="btnv1" style="float:right;">
            <- Go Back</a> </span> <?php

    $absDir = nano_resolve($dirName);

    if ($absDir !== false && is_dir($absDir)) {
                            $cdir = scandir($absDir, 1);
                            if ($cdir === false) {
                                $cdir = array();
                            }
                            foreach ($cdir as $key => $value)
                            {

    if (str_contains($value, '.php') || str_contains($value, '.htm')) continue;
                               if (!in_array($value, array(".", "..", "__hidden", "NanoCDS")))
                               {
                                $child = $absDir . DIRECTORY_SEPARATOR . $value;
                                if (is_file($child)) {
                                    ?>
        <a href="<?= e($rootDir ?? "/assets/") ?>?page=view&dir=<?= e($dirName); ?>&name=<?= e($value); ?>" class="group-item group-item-action"><?= e($value); ?><span style="float:right;"><?= formatBytes(nano_size($child)) ?><div class="downloadIcon"></div></span></a>
                <?php
                                } else if (is_dir($child)) {
                                    ?>
        <a href="?page=dir&name=<?= e($dirName . DIRECTORY_SEPARATOR . $value); ?>" class="group-item group-item-action"><span class="uppertext"><?= e($value); ?></span><span style="float:right;"><?= formatBytes(nano_size($child)) ?><div class="downloadIcon"></div></span></a>
                <?php
                                }
                               }
                            }

                            if (count($cdir) <= 2) {
                                echo '<p>Message: <span class="color-grey">Nothing found</span>.</p>';
                             }

    }
    else {

        echo '<p><span class="color-warning">Warning</span>: <span class="color-grey">Folder not exist</span>.</p>';
    }
                ?>
                <!--<a href="?page=signin" class="group-item group-item-action footer">Sign In</a>/-->
</div>
<?php
            include_once NANO_DIR . '/footer.php';
            exit;
        }
        view:
        if ($_GET["page"] == "view") {

            $viewName = (string) ($_GET["name"] ?? '');
            $viewDir  = (string) ($_GET["dir"] ?? '');

            $attachment_location = ($viewName === '')
                ? false
                : nano_resolve($viewDir . '/' . $viewName);

        if ($attachment_location !== false && is_file($attachment_location)) {

            switch (strtolower(pathinfo($viewName, PATHINFO_EXTENSION))) {
                case "php":
                case "phtml":
                case "phar":

                    $PageTitle = "Messages";
                    include_once NANO_DIR . '/header.php';
                    ?>
                    <div class="group">
            <span class="group-item group-item-action header">
            <a class="color-gray" href="<?= e($rootDir ?? "/assets/") ?>">Nano CDS</a> &middot; <span class="uppertext">Messages</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
        </span>
            <p><span class='color-warning'>Warning</span>: <span class='color-grey'>You can't view files with this extension</span>.</p>
        </div>
        <?php
                    include_once NANO_DIR . '/footer.php';
            exit;
                break;
                case "css":
                    $contentType = "text/css";
                break;
                case "js":
                    $contentType = "application/javascript";
                break;
                case "ttf":
                    $contentType = "application/x-font-ttf";
                break;
                default:
                    $detected = @mime_content_type($attachment_location);
                    $contentType = $detected !== false ? $detected : "application/octet-stream";
            break;
            }

            header((isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.1") . " 200 OK");
            header("Cache-Control: public");
            header("Content-Type:" . $contentType);
            header("Content-Length:" . filesize($attachment_location));
            header("Content-Transfer-Encoding: Binary");
            header('Content-Disposition: inline; filename="' . str_replace(array('"', "\r", "\n"), '', basename($viewName)) . '"');

            header('Cache-Control: max-age=86400');
            //header('Content-Disposition: attachment; filename="'.$_GET["name"].'"');

            readfile($attachment_location);
            exit;
        } else {
            $PageTitle = "Messages"; include_once NANO_DIR . '/header.php';
            ?>
            <div class="group">
    <span class="group-item group-item-action header">
    <a class="color-gray" href="<?= e($rootDir ?? "/assets/") ?>">Nano CDS</a> &middot; <span class="uppertext">Messages</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
</span>
    <p><span class='color-error'>Error</span>: <span class='color-grey'>File not found</span>.</p>
</div>
<?php
            include_once NANO_DIR . '/footer.php';
            exit;
        }

        }
        tools:
                if ($_GET["page"] == "tools") {
                    $PageTitle = "Tools"; include_once NANO_DIR . '/header.php';
        ?>

<div class="group">
  <span class="group-item group-item-action header">
  <a class="color-gray" href="<?= e($rootDir ?? "/assets/") ?>">Nano CDS</a> &middot; <span class="uppertext">Tools</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
  </span>
  <?php
  $count1 = 0;
  foreach (new DirectoryIterator(nano_root()) as $fileInfo) {
    $name = $fileInfo->getFilename();
    if (str_contains($name, "__hidden")) continue;
    if (str_contains($name, "NanoCDS")) continue;
    if ($fileInfo->isDir() && !$fileInfo->isDot()) {
        $count1++;
    }
  }
  $counter = countFilesAndDirs(nano_root());
  ?>
  <p>Projects in repository: <?= (int) $count1; ?> and size is <?= formatBytes(nano_size(nano_root())) ?>.</p>
  <p>Total Files in repository: <?= (int) $counter['files']; ?> in <?= (int) $counter['dirs']; ?> folders.</p>
  <p>Fild latest library or framework version:</p>
<form action="<?= e($rootDir ?? "/assets/") ?>" method="GET" class="form-inline">
<input type="hidden" name="page" value="latest" />
  <div class="form-group">
    <input type="text" class="form-control" name="asset" placeholder="Asset Name" required>
  </div>
  <?php /*
  <div class="form-group">
    <input type="text" class="form-control" name="file" placeholder="File Name" title="Not required">
  </div>
  */ ?>
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
<form action="<?= e($rootDir ?? "/assets/") ?>" method="GET" class="form-inline">
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

        <?php
                    include_once NANO_DIR . '/footer.php';
                    exit;
                }
        search:
        if ($_GET["page"] == "search") {

            $_GET["query"] = nano_clean_query($_GET["query"] ?? '');
            $query = $_GET["query"];

            $PageTitle = "Search: " . $query; include_once NANO_DIR . '/header.php';
        ?>
        <div class="group">
            <span class="group-item group-item-action header">
            <a class="color-gray" href="<?= e($rootDir ?? "/assets/") ?>">Nano CDS</a> &middot; <span id="searchCount" class="uppertext">Search: `<?= e($query); ?>`</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
            </span>
            <?php
                $files = ($query === '') ? array() : glob_tree_search(nano_root(), $query . '.*');

                $SearchCount = " (Files: " . count($files) . ")";
                if ($query === "") {
                    ?>
                    <p><span class='color-info'>Info</span>: <span class='color-grey'>Search Query is empty</span>!</p>
                <?php
                } elseif (count($files) < 1) {
                    ?>
            <p><span class='color-info'>Info</span>: <span class='color-grey'>Nothing found</span>!</p>
        <?php
                }
                foreach ($files as $file) {

                        $abs = nano_resolve($file);
                        if ($abs !== false && is_file($abs)
                            && !str_contains(basename($file), ".php")
                            && !str_contains(basename($file), ".htm")) {

            ?>
            <?php
                $filePath = rtrim(removeLastOccurrence($file, basename($file)), '/');
            ?>
            <a href="<?= e($rootDir ?? "/assets/") ?>?page=view&dir=<?= e($filePath); ?>&name=<?= e(basename($file)); ?>" class="group-item group-item-action"><span class="uppertext"><?= e(basename($file)); ?></span> <span style="float:right;">[ <?= e($filePath); ?> ] <div class="downloadIcon"></div></span></a>
            <?php
                    }
        }
            ?>
            <!--<a href="?page=signin" class="group-item group-item-action footer">Sign In</a>/-->
        </div>
        <?php
        include_once NANO_DIR . '/footer.php';
        exit;

        }
        latest:
        if ($_GET["page"] == "latest") {
            ob_start();
            $_GET["asset"] = nano_clean_query($_GET["asset"] ?? '');
            $asset = $_GET["asset"];
            $sizeOpt = (string) ($_GET["size"] ?? '0');
            $autoOpt = (string) ($_GET["auto"] ?? '0');

            $PageTitle = "Search: " . $asset; include_once NANO_DIR . '/header.php';

$fileType = ".*";
switch ((string) ($_GET["type"] ?? 'any')) {
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
switch ($sizeOpt) {
    case "0": $MinOrMax = "";    break;
    case "1": $MinOrMax = ".min"; break;
    case "2": $MinOrMax = "";    break;
     default: $MinOrMax = "";    break;
}


        ?>
        <div class="group">
            <span class="group-item group-item-action header">
            <a class="color-gray" href="<?= e($rootDir ?? "/assets/") ?>">Nano CDS</a> &middot; <span id="searchCount" class="uppertext">Search: `<?= e($asset); ?>`</span><a href="javascript:history.back()" class="btnv1" style="float:right;"><- Go Back</a>
            </span>
            <?php
                $files = ($asset === '') ? array() : glob_tree_search(nano_root(), $asset . $MinOrMax . $fileType);

                $SearchCount = " (Files: " . count($files) . ")";
                if ($asset === "") {
                    ?>
                        <p><span class='color-info'>Info</span>: <span class='color-grey'>Search Query is empty</span>!</p>
                    <?php
                } elseif (count($files) < 1) {
                    ?>
            <p><span class='color-info'>Info</span>: <span class='color-grey'>Nothing found</span>!</p>
        <?php
                }
                foreach (array_reverse($files) as $file) {

                    if ($sizeOpt == "2") {
                        if (str_contains(basename($file), '.min')) {
                        continue;
                        }
                    }

                        $abs = nano_resolve($file);
                        if ($abs !== false && is_file($abs)
                            && !str_contains(basename($file), ".php")
                            && !str_contains(basename($file), ".htm")) {
                            if ($autoOpt == "-1") {
                                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                                $fullUrl = $scheme . "://" . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
                                $fullUrl = str_replace("auto=-1", "auto=1", $fullUrl);
                                $fullUrl = str_replace("/a/-1", "/a/1", $fullUrl);
                                echo '<a class="group-item group-item-action" id="latestLink" href="' . e($fullUrl) . '"><div class="middleText">' . e($fullUrl) . '</div> <div style="float:right;" class="downloadIcon"></div></a>';
                                include_once NANO_DIR . '/footer.php';
                                exit();
                            }
                            if ($autoOpt == "1") {
                                $tempDirReg = getDirectoryPath($file);
                                $tempLink = ($rootDir ?? "/assets/") . '?page=view&dir=' . $tempDirReg . '&name=' . basename($file);
                                ob_end_clean();
                                header("Location: " . str_replace(array("\r", "\n"), '', $tempLink), true, 301);
                                exit();
                            }
            ?>

            <?php
                $filePath = rtrim(removeLastOccurrence($file, basename($file)), '/');
            ?>
            <a href="<?= e($rootDir ?? "/assets/") ?>?page=view&dir=<?= e($filePath); ?>&name=<?= e(basename($file)); ?>" class="group-item group-item-action"><span class="uppertext"><?= e(basename($file)); ?></span> <span style="float:right;">[ <?= e($filePath); ?> ] <div class="downloadIcon"></div></span></a>
            <?php
                    }
                }
            ?>
            <!--<a href="?page=signin" class="group-item group-item-action footer">Sign In</a>/-->
        </div>
        <?php

        include_once NANO_DIR . '/footer.php';
        exit;

        }
        support:
        if ($_GET["page"] == "support") {

            header("Location: https://fhnb.ru/photos/?page=support", true, 301);
            exit();

        }
        signin:
        if ($_GET["page"] == "signin") {

            goto main;

        }

        // Nothing matched (e.g. `?page=dir` without `name`) - fall back to the index.
        goto main;

    } else {
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
	foreach (glob($path . '/' . $pattern, GLOB_BRACE) as $file) {
        $name = basename($file);
        if (str_contains($name, '.php') || str_contains($name, '.htm') || $name === "__hidden" || $name === "NanoCDS") continue;
        $out[] = $_base_path . $name;
	}

	foreach (glob($path . '/*', GLOB_ONLYDIR) as $file) {
        $name = basename($file);
		if (str_contains($name, '.php') || str_contains($name, '.htm') || $name === "__hidden" || $name === "NanoCDS") continue;
        $out = array_merge($out, glob_tree_search($file, $pattern, $_base_path));
	}
	return $out;
}

function getDirectoryPath($file)
{
    $parts = explode('/', (string) $file);

    array_pop($parts);

    return implode('/', $parts);
}

function clean_url($url)
{
    $pattern = '#(/assets/).*?(/index\.php)#';
    $replacement = '$1$2';

    return preg_replace($pattern, $replacement, (string) $url);
}

/** Remove $substring when it is the trailing part of $string. */
function removeLastOccurrence($string, $substring)
{
    $string = (string) $string;
    $substring = (string) $substring;
    if ($substring !== '' && str_ends_with($string, $substring)) {
        return substr($string, 0, -strlen($substring));
    }
    return $string;
}

function getDirContents($path)
{
    $real = realpath($path);
    if ($real === false) {
        return array();
    }
    $files = array();
    foreach (nano_iterator($real) as $file) {
        if ($file->isDir()) {
            $files[] = $file;
        }
    }
    return $files;
}

/** Count files and directories without shelling out to `find`. */
function countFilesAndDirs($directory)
{
    $result = array('files' => 0, 'dirs' => 0);
    $real = realpath($directory);
    if ($real === false) {
        return $result;
    }

    try {
        foreach (nano_iterator($real) as $item) {
            if ($item->isDir()) {
                $result['dirs']++;
            } elseif ($item->isFile()) {
                $result['files']++;
            }
        }
    } catch (Throwable $e) {
        return $result;
    }

    return $result;
}
