<?php

include "main.php";


$sVisibility = "public";
if (isAllowedIp($aAllowedIps)) {
    $sVisibility = "all";
}
if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
    $_SESSION["film"] = array();
}
if (!isset($_SESSION["film"]["gallery"]) || !is_array($_SESSION["film"]["gallery"])) {
    $_SESSION["film"]["gallery"] = array();
}
if (!isset($_SESSION["film"]["gallery"]["cover"]) || !is_bool($_SESSION["film"]["gallery"]["cover"])) {
    $_SESSION["film"]["gallery"]["cover"] = false;
}
if (isset($_GET["cover"])) {
    $_SESSION["film"]["gallery"]["cover"] = $_GET["cover"] ? true : false;
}
if (!isset($_SESSION["film"]["gallery"]["metadata"]) || !is_bool($_SESSION["film"]["gallery"]["metadata"])) {
    $_SESSION["film"]["gallery"]["metadata"] = false;
}
if (isset($_GET["metadata"])) {
    $_SESSION["film"]["gallery"]["metadata"] = $_GET["metadata"] ? true : false;
}
if (!isset($_SESSION["film"]["gallery"]["mode"]) || !is_int($_SESSION["film"]["gallery"]["mode"]) || $_SESSION["film"]["gallery"]["mode"] < 0 || $_SESSION["film"]["gallery"]["mode"] > 5) {
    $_SESSION["film"]["gallery"]["mode"] = 0;
}
if (isset($_GET["mode"])) {
    switch ($_GET["mode"]) {
        case 1:
            $_SESSION["film"]["gallery"]["mode"] = 1;
            break;
        case 2:
            $_SESSION["film"]["gallery"]["mode"] = 2;
            break;
        case 3:
            $_SESSION["film"]["gallery"]["mode"] = 3;
            break;
        case 4:
            $_SESSION["film"]["gallery"]["mode"] = 4;
            break;
        case 5:
            $_SESSION["film"]["gallery"]["mode"] = 5;
            break;
        default:
            $_SESSION["film"]["gallery"]["mode"] = 0;
            break;
    }
}

if (isFilmUaFingerprintRequest()) {
    sendFilmUaFingerprintResponse($oPdo, $aAllowedIps);
}

if (isset($_GET["img"])) {
    $sImgParam = basename($_GET["img"]);
    
    if ($sVisibility == "all" && isset($_GET["set"]) && isset($_GET["img"]) && isset($_GET["dir"]) && isset($_GET["id"])) {
        $sStatus = "internal";
        switch ($_GET["set"]) {
            case 2:
                $sStatus = "ok_public";
                break;
            case 1:
                $sStatus = "ok_private";
                break;
        }
        $oPdoStatement = $oPdo->prepare("UPDATE fs_film_photos SET status = :status WHERE scan_id = :scan_id AND subdir = :subdir AND filename = :filename");
        $oPdoStatement->execute(array("status" => $sStatus, "scan_id" => $_GET["id"], "subdir" => $_GET["dir"], "filename" => $_GET["img"] . $sExtension));
    } else {
        $sSubdir = substr(pathinfo($sImgParam, PATHINFO_FILENAME), 0, 8);
        $sFilePath = $sDirectory . DIRECTORY_SEPARATOR . $sSubdir . DIRECTORY_SEPARATOR . $sImgParam . $sExtension;

        $sStatus = null;
        if ($sVisibility != "all") {
            $oPdoStatement = $oPdo->prepare("SELECT status FROM fs_film_photos WHERE subdir = :subdir AND filename = :filename LIMIT 1");
            $oPdoStatement->execute(array("subdir" => $sSubdir, "filename" => $sImgParam . $sExtension));
            $sStatus = $oPdoStatement->fetchColumn();
            if (!$sStatus) {
                send403AndExit();
            }
            if ($sStatus != "ok_public") {
                send403AndExit();
            }
        }

        if (is_file($sFilePath)) {
            markFilmUaImageRequest($oPdo, $sImgParam, $sExtension, $aAllowedIps);
            session_write_close();
            sendSecurityHeaders();
            header("Content-Type: " . mime_content_type($sFilePath), true);
            header("Content-Length: " . filesize($sFilePath), true);
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($sFilePath)) . " GMT", true);
            header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT", true);
            header("Cache-Control: public, max-age=31536000, immutable", true);
            header("Pragma: public", true);
            readfile($sFilePath);
            exit;
        } else {
            send404AndExit();
        }
    }
}

$sJoined = "";
$sJoinedPrev = "";
$sJoinedNext = "";
$sSubDirectory = "";
$aPrevious = null;
$aNext = null;
$iId = 0;
$blPost = false;
$aCurrent = null;

if (isset($_POST["id"])) {
    $iId = (int)$_POST["id"];
    $blPost = true;
} elseif (isset($_GET["id"])) {
    $iId = (int)$_GET["id"];
}

if ($iId > 0) {
    $oPdoStatement = $oPdo->prepare("SELECT * FROM fs_film_scans WHERE id = :id");
    $oPdoStatement->execute(array("id" => $iId));
    $aCurrent = $oPdoStatement->fetch(PDO::FETCH_ASSOC);
    if ($aCurrent) {
        if ($blPost) {
            sendFilmMetadataTxt($oPdo, $aCurrent);
        } else {
            $aParts = preg_split("/\s+/", trim($aCurrent["folder_name"]));
            if (isset($aParts[1])) {
                $sSubDirectory = $aParts[1];
                $sPath = $sDirectory . DIRECTORY_SEPARATOR . $sSubDirectory;
                if (is_dir($sPath)) {
                    if (count($aParts) >= 3) {
                        $sFirst = $aParts[0];
                        $aRest = array_slice($aParts, 2, -1);
                        $sJoined = implode(" ", array_merge(array($sFirst . "."), $aRest, array("(" . $sSubDirectory . ")")));
                    } else {
                        $sJoined = $aParts[0] ?? "";
                    }
                } else {
                    sendSecurityHeaders();
                    header("Location: " . $sBaseUrl, true, 302);
                    exit;
                }
            }
        }
    }

    $oPdoStatement = $oPdo->prepare("SELECT * FROM fs_film_scans WHERE archive_no < (SELECT archive_no FROM fs_film_scans WHERE id = :id) ORDER BY archive_no DESC");
    $oPdoStatement->execute(array("id" => $iId));
    while ($aPrevious = $oPdoStatement->fetch(PDO::FETCH_ASSOC)) {
        $aParts = preg_split("/\s+/", trim($aPrevious["folder_name"]));
        if (isset($aParts[1])) {
            $sSubdir = $aParts[1];
            $sPath = $sDirectory . DIRECTORY_SEPARATOR . $sSubdir;
            if (is_dir($sPath)) {
                if (count($aParts) >= 3) {
                    $sFirst = $aParts[0];
                    $aRest = array_slice($aParts, 2, -1);
                    $sJoinedPrev = implode(" ", array_merge(array($sFirst . "."), $aRest, array("(" . $sSubdir . ")")));
                } else {
                    $sJoinedPrev = $aParts[0] ?? "";
                }
                if ($sJoinedPrev) {
                    break;
                }
            }
        }
    }

    $oPdoStatement = $oPdo->prepare("SELECT * FROM fs_film_scans WHERE archive_no > (SELECT archive_no FROM fs_film_scans WHERE id = :id) ORDER BY archive_no ASC");
    $oPdoStatement->execute(array("id" => $iId));
    while ($aNext = $oPdoStatement->fetch(PDO::FETCH_ASSOC)) {
        $aParts = preg_split("/\s+/", trim($aNext["folder_name"]));
        if (isset($aParts[1])) {
            $sSubdir = $aParts[1];
            $sPath = $sDirectory . DIRECTORY_SEPARATOR . $sSubdir;
            if (is_dir($sPath)) {
                if (count($aParts) >= 3) {
                    $sFirst = $aParts[0];
                    $aRest = array_slice($aParts, 2, -1);
                    $sJoinedNext = implode(" ", array_merge(array($sFirst . "."), $aRest, array("(" . $sSubdir . ")")));
                } else {
                    $sJoinedNext = $aParts[0] ?? "";
                }
                if ($sJoinedNext) {
                    break;
                }
            }
        }
    }
}
if (isAllowedIp($aAllowedIps)) {
    unset($_SESSION["film"]["ua"]);
} else {
    startFilmUaPageRequest($iId > 0 ? $iId : null);
}
session_write_close();
$sTitle = $sError ? "Error" : ($sJoined ? htmlspecialchars($sJoined) : "Film Scans");
$sBaseUrl = htmlspecialchars($sBaseUrl);
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo $sTitle; ?></title>
  <meta name="description" content="High-quality film scans of negatives and slides.">
  <meta name="keywords" content="film, scans, film scans, negatives, slides, digitization, photography, archival">
  <meta name="owner" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="url" content="<?php echo $sBaseUrl; ?>">
  <meta name="identifier-URL" content="<?php echo $sBaseUrl; ?>">
  <meta name="coverage" content="worldwide">
  <meta name="distribution" content="global">
  <meta name="rating" content="general">
  <meta name="target" content="all">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="revised" content="<?php echo date("l, F jS, Y, g:i a", $iTime); ?>">
  <meta name="language" content="en-US">
  <meta name="copyright" content="Petr Červinka <?php echo date("Y", $iTime); ?>">
  <meta name="robots" content="noindex, nofollow">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:domain" content="<?php echo $sHost; ?>">
  <meta name="twitter:title" content="Film Scans">
  <meta name="twitter:description" content="High-quality film scans of negatives and slides.">
  <meta name="twitter:url" content="<?php echo $sBaseUrl; ?>">
  <meta name="twitter:label1" content="Created On">
  <meta name="twitter:data1" content="<?php echo date("F j, Y", $iTime); ?>">
  <meta name="twitter:label2" content="Rating">
  <meta name="twitter:data2" content="General">
  <meta name="twitter:image" content="<?php echo $sBaseUrl; ?>gfx/bg.webp">
  <meta property="og:title" content="Film Scans">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo $sBaseUrl; ?>">
  <meta property="og:description" content="High-quality film scans of negatives and slides.">
  <meta property="og:image" content="<?php echo $sBaseUrl; ?>gfx/bg.webp">
  <meta property="og:image:secure_url" content="<?php echo $sBaseUrl; ?>gfx/bg.webp">
  <meta property="og:image:type" content="image/webp">
  <meta property="og:image:width" content="1600">
  <meta property="og:image:height" content="1280">
  <meta property="og:locale" content="en-US">
  <meta property="og:site_name" content="Film Scans">
  <link href="<?php echo $sBaseUrl; ?>vendors/bootstrap-3.4.1/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="<?php echo $sBaseUrl; ?>vendors/bootstrap-3.4.1/css/ie10-viewport-bug-workaround.css" rel="stylesheet" type="text/css">
  <link href="<?php echo $sBaseUrl; ?>vendors/jstree-3.3.10/themes/default/style.min.css" rel="stylesheet" type="text/css">
  <link href="<?php echo $sBaseUrl; ?>vendors/fancybox-6.1.14/css/fancybox.css" rel="stylesheet" type="text/css">
  <link href="<?php echo $sBaseUrl; ?>css/layout.min.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/layout.min.css")); ?>" rel="stylesheet" type="text/css">
  <link href="<?php echo $sBaseUrl; ?>css/effects.min.css" rel="stylesheet" type="text/css">
  <link href="<?php echo $sBaseUrl; ?>css/theme-light-blue.min.css" rel="stylesheet" type="text/css">
  <link href="<?php echo $sBaseUrl; ?>css/print.min.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/print.min.css")); ?>" rel="stylesheet" type="text/css" media="print">
  <style type="text/css">nav { width: 350px} @media screen and (min-width:769px) { body.md-nav-expanded div#main { margin-left: 350px} body.md-nav-expanded header { padding-left: 364px} }</style>
  <style type="text/css">.navigation #inline-toc { width: auto !important}</style>
</head>
<body itemscope itemtype="https://schema.org/CreativeWork" class="md-nav-expanded">
  <meta itemprop="name" content="Film Scans">
  <meta itemprop="description" content="High-quality film scans of negatives and slides.">
  <meta itemprop="url" content="https://eved.cz/film/">
  <meta itemprop="inLanguage" content="en-US">
  <meta itemprop="dateModified" content="<?php echo gmdate("Y-m-d\TH:i:s\Z", $iTime); ?> GMT">
  <div itemprop="author" itemscope itemtype="https://schema.org/Person">
    <meta itemprop="name" content="Petr Červinka">
    <meta itemprop="email" content="mailto:cervinka@fortsoft.cz">
    <meta itemprop="url" content="https://eved.cz/film/">
  </div>
  <div id="skip-link">
    <a href="#main-content" class="element-invisible">Skip to main content</a>
  </div>
  <header class="headroom">
    <button class="hnd-toggle btn btn-default" title="Show menu">
      <span class="sr-only"></span>
      <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
    </button>
    <h1><?php echo $sTitle; ?></h1>
  </header>
  <nav id="panel-left" class="md-nav-expanded">
    <ul class="tab-tabs nav nav-tabs" role="tablist">
      <li id="nav-close" role="presentation">
        <button class="hnd-toggle btn btn-default" title="Hide menu">
          <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
        </button>
      </li>
      <li><h1></h1></li>
    </ul>
    <div class="tab-content">
      <div role="tabpanel" class="tab-pane active" id="contents">
        <div id="toc" class="tree-container unselectable" data-openlvl="1">
<?php

if ($oPdo) {
    $oPdoStatement = $oPdo->query("SELECT id, archive_no, folder_name FROM fs_film_scans ORDER BY archive_no ASC");
    $aRows = $oPdoStatement->fetchAll(PDO::FETCH_ASSOC);
    echo "         <ul>\n";
    foreach ($aRows as $sRow) {
        $aParts = preg_split("/\s+/", trim($sRow["folder_name"]));
        if (isset($aParts[1])) {
            $sSubdir = $aParts[1];
            $sPath = $sDirectory . DIRECTORY_SEPARATOR . $sSubdir;
            if (is_dir($sPath)) {
                if (count($aParts) >= 3) {
                    $sFirst = $aParts[0];
                    $aRest = array_slice($aParts, 2, -1);
                    $sJoin = implode(" ", array_merge(array($sFirst . "."), $aRest));
                } else {
                    $sJoin = $aParts[0] ?? "";
                }
                echo "           <li id=\"" . htmlspecialchars($sRow["id"]) . "\"><a href=\"" . $sBaseUrl . "?id=" . htmlspecialchars($sRow["id"]) . "\">" . htmlspecialchars($sJoin) . "</a></li>\n";
            }
        }
    }
    echo "         </ul>\n";
}

?>
        </div>
      </div>
      <div role="tabpanel" class="tab-pane" id="index">
        <div id="keywords" class="tree-container unselectable" data-openlvl="1">
          <ul></ul>
        </div>
      </div>
      <div role="tabpanel" class="tab-pane" id="search">
        <div class="search-content">
          <div class="search-input">
            <form id="search-form" method="post" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded">
              <div class="form-group">
                <div class="input-group">
                  <input type="text" class="form-control" id="input-search" name="input-search" placeholder="Search…" aria-label="Search…">
                  <span class="input-group-btn">
                    <button class="btn btn-default" type="submit" aria-label="Search…">
                      <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
                    </button>
                  </span>
                </div>
              </div>
            </form>
          </div>
          <div class="search-result">
            <div id="search-info"></div>
            <div class="tree-container unselectable" id="search-tree"></div>
          </div>
        </div>
      </div>
    </div>
  </nav>
  <div id="main">
    <article>
      <div id="topic-content" class="container-fluid" data-hnd-id="<?php echo $aCurrent ? (int)$aCurrent["id"] : 0; ?>" data-hnd-context="0" data-hnd-title="<?php echo $sTitle; ?>">
<?php

if ($oPdo) {
    if ($aCurrent) {
        echo "        <div class=\"navigation\">\n"
            . "          <div class=\"breadcrumb\">\n";
        $aModes = array("All", "OK", "Public", "Private", "Internal", "Colorized");
        if ($sVisibility == "all") {
            echo "            <form method=\"get\" action=\"" . $sBaseUrl . "\" enctype=\"application/x-www-form-urlencoded\">\n"
                . "              <select class=\"select-like-btn js-gallery-select\" name=\"mode\" style=\"width: 85px;\">\n";
            for ($iI = 0; $iI < 6; $iI++) {
                echo "                <option value=\"" . $iI . "\"" . ($_SESSION["film"]["gallery"]["mode"] == $iI ? " selected" : "") . ">" . $aModes[$iI] . "</option>\n";
            }
            echo "              </select>\n"
                . "              <input type=\"hidden\" name=\"id\" value=\"" . (int)$aCurrent["id"] . "\">\n"
                . "            </form>\n";
        }

?>
            <form method="get" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded">
              <select class="select-like-btn js-gallery-select" name="cover" style="width: 85px;">
                <option value="0"<?php if ($_SESSION["film"]["gallery"]["cover"] == 0) echo " selected"; ?>>Contain</option>
                <option value="1"<?php if ($_SESSION["film"]["gallery"]["cover"] == 1) echo " selected"; ?>>Cover</option>
              </select>
              <input type="hidden" name="id" value="<?php echo (int)$aCurrent["id"]; ?>">
            </form>
            <form class="js-gallery-metadata-form" method="get" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded">
              <input type="hidden" name="metadata" value="<?php echo $_SESSION["film"]["gallery"]["metadata"] ? 0 : 1; ?>">
              <input type="hidden" name="id" value="<?php echo (int)$aCurrent["id"]; ?>">
              <button class="btn btn-default" type="submit" aria-label="<?php echo $_SESSION["film"]["gallery"]["metadata"] ? "Hide Metadata" : "Show Metadata"; ?>"><?php echo $_SESSION["film"]["gallery"]["metadata"] ? "Hide Metadata" : "Show Metadata"; ?></button>
            </form>
<?php

        if ($blIsDesktop) {
            $sFileName = $sSubDirectory . "_" . ($_SESSION["film"]["gallery"]["cover"] ? "cover" : "contain");
            if ($sVisibility == "all") {
                $sFileName .= "_" . strtolower($aModes[$_SESSION["film"]["gallery"]["mode"]]);
            }
            $sFileName .= "_gallery";
            if ($_SESSION["film"]["gallery"]["metadata"]) {
                $sFileName .= "_with_metadata";
            }

?>
            <form method="post" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded">
              <input type="hidden" name="id" value="<?php echo (int)$aCurrent["id"]; ?>">
              <button class="btn btn-default" type="submit" aria-label="Save TXT">Save TXT</button>
            </form>
            <form method="get" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded">
              <button class="btn btn-default js-save-png" type="button" data-file-name="<?php echo htmlspecialchars($sFileName, ENT_QUOTES, "UTF-8"); ?>" aria-label="Save PNG">Save PNG</button>
            </form>
<?php

        }

        echo "          </div>\n"
            . "          <div class=\"nav-arrows\">\n"
            . "            <div class=\"btn-group btn-group\" role=\"group\">\n";

        if ($sJoinedPrev) {
            echo "              <a class=\"btn btn-default\" href=\"" . $sBaseUrl . "?id=" . (int)$aPrevious["id"] . "\" title=\"" . htmlspecialchars($sJoinedPrev)
                . "\" role=\"button\"><span class=\"glyphicon glyphicon-menu-left\" aria-hidden=\"true\"></span></a>\n";
        } else {
            echo "              <a class=\"btn btn-default disabled\" href=\"" . $sBaseUrl . "\" role=\"button\"><span class=\"glyphicon glyphicon-menu-left\" aria-hidden=\"true\"></span></a>\n";
        }
        if ($sJoinedNext) {
            echo "              <a class=\"btn btn-default\" href=\"" . $sBaseUrl . "?id=" . (int)$aNext["id"] . "\" title=\"" . htmlspecialchars($sJoinedNext)
                . "\" role=\"button\"><span class=\"glyphicon glyphicon-menu-right\" aria-hidden=\"true\"></span></a>\n";
        } else {
            echo "              <a class=\"btn btn-default disabled\" href=\"" . $sBaseUrl . "\" role=\"button\"><span class=\"glyphicon glyphicon-menu-right\" aria-hidden=\"true\"></span></a>\n";
        }

        echo "            </div>\n"
            . "          </div>\n"
            . "        </div>\n";
    }
}

echo "        <a id=\"main-content\"></a>\n"
    . "        <div class=\"main-content\" id=\"main-content-gallery\">\n";

if ($sError) {
    echo "<p><strong>Error:</strong> " . htmlspecialchars($sError) . "</p>\n";
}

if ($oPdo) {
    if ($aCurrent) {
        if ($_SESSION["film"]["gallery"]["metadata"]) {
            echo renderFilmScanHtml($oPdo, $aCurrent);
        }
        echo "          <section class=\"gallery\">\n";
        $oPdoStatement = $oPdo->prepare("SELECT filename, status FROM fs_film_photos WHERE subdir = :subdir");
        $oPdoStatement->execute(array("subdir" => $sSubDirectory));
        $aPhotos = $oPdoStatement->fetchAll(PDO::FETCH_KEY_PAIR);
        $sDirectory = $sDirectory . DIRECTORY_SEPARATOR . $sSubDirectory;
        foreach (scandir($sDirectory) as $sFileName) {
            if ($sFileName == "." || $sFileName == "..") {
                continue;
            }
            $sPath = $sDirectory . DIRECTORY_SEPARATOR . $sFileName;
            $sExtensionLen = strlen($sExtension);
            if (!is_file($sPath) || substr_compare($sFileName, $sExtension, -$sExtensionLen, $sExtensionLen, true) !== 0) {
                continue;
            }
            if (!array_key_exists($sFileName, $aPhotos)) {
                $oPdoStatement = $oPdo->prepare("INSERT IGNORE INTO fs_film_photos (scan_id, subdir, filename, status) VALUES (:scan_id, :subdir, :filename, 'internal')");
                $oPdoStatement->execute(array("scan_id" => $aCurrent["id"], "subdir" => $sSubDirectory, "filename" => $sFileName));
                if ($sVisibility != "all") {
                    continue;
                }
            }
            $sStatus = "internal";
            if (array_key_exists($sFileName, $aPhotos)) {
                $sStatus = $aPhotos[$sFileName];
                if ($sVisibility == "public" && $sStatus != "ok_public") {
                    continue;
                }
            }
            switch ($_SESSION["film"]["gallery"]["mode"]) {
                case 1:
                    if ($sStatus == "internal") {
                        continue 2;
                    }
                    break;
                case 2:
                    if ($sStatus != "ok_public") {
                        continue 2;
                    }
                    break;
                case 3:
                    if ($sStatus != "ok_private") {
                        continue 2;
                    }
                    break;
                case 4:
                    if ($sStatus != "internal") {
                        continue 2;
                    }
                    break;
            }
            $sBaseName = htmlspecialchars(pathinfo($sFileName, PATHINFO_FILENAME));
            echo "            <figure>\n"
                . "              <div class=\"thumb";
            if ($_SESSION["film"]["gallery"]["cover"]) {
                echo " thumb-cover";
            } else {
                echo " thumb-contain";
                if ($sVisibility != "public" && $_SESSION["film"]["gallery"]["mode"] == 5) {
                    switch ($sStatus) {
                        case "internal":
                            echo " internal";
                            break;
                        case "ok_private":
                            echo " private";
                            break;
                        case "ok_public":
                            echo " public";
                            break;
                    }
                }
            }
            echo "\">\n"
                . "                <a href=\"" . $sBaseUrl . "?img=" . $sBaseName . "\" data-fancybox=\"gallery\" data-caption=\"" . $sBaseName . "\" title=\"" . $sBaseName . "\">\n"
                . "                  <img src=\"" . $sBaseUrl . "?img=" . $sBaseName . "\" alt=\"\">\n"
                . "                </a>\n"
                . "                <div class=\"overlay-text\">" . intval(substr($sBaseName, -4)) . ".</div>\n";
            if ($sVisibility != "public" && !$_SESSION["film"]["gallery"]["cover"] && $_SESSION["film"]["gallery"]["mode"] == 5) {

?>
                <form class="overlay-form" method="get" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded">
                  <select class="select-like-btn js-gallery-set-select" name="set">
                    <option value="2"<?php if ($sStatus == "ok_public" ) echo " selected"; ?>>Public</option>
                    <option value="1"<?php if ($sStatus == "ok_private") echo " selected"; ?>>Private</option>
                    <option value="0"<?php if ($sStatus == "internal"  ) echo " selected"; ?>>Internal</option>
                  </select>
                  <input type="hidden" name="img" value="<?php echo $sBaseName; ?>">
                  <input type="hidden" name="dir" value="<?php echo $sSubDirectory; ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$aCurrent["id"]; ?>">
                </form>
<?php

            }
            echo "              </div>\n"
                . "            </figure>\n";
        }
        echo "          </section>\n";
    } elseif (isFirefoxWithEnglishLanguage() && isAllowedIp($aAllowedIps)) {
        printPhpFileLinks($sBaseUrl);
        echo "          <pre>"
           . rtrim(getRequestPlainTextInfo())
           . "</pre>\n";
    } else {
        echo "          <div id=\"camera-image\">\n"
           . "            <img src=\"" . $sBaseUrl . "gfx/camera.png\" width=\"1535\" height=\"1025\" alt=\"Praktica MTL 3 — 35 mm single-lens reflex (SLR) film camera by VEB Pentacon Dresden\">\n"
           . "          </div>\n";
    }
}

?>
        </div>
      </div>
    </article>
    <footer></footer>
  </div>
  <div class="mask" data-toggle="sm-nav-expanded"></div>
  <div class="modal fade" id="hndModal" tabindex="-1" role="dialog" aria-labelledby="hndModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="hndModalLabel">&nbsp;</h4>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary modal-btn-close" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  <div id="hnd-splitter" style="left: 350px"></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/jquery-3.5.1/jquery.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/bootstrap-3.4.1/js/bootstrap.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/bootstrap-3.4.1/js/ie10-viewport-bug-workaround.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/uri-1.19.2/uri.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/headroom-0.11.0/headroom.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/jstree-3.3.10/jstree.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/interactjs-1.9.22/interact.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/html2canvas-1.4.1/html2canvas.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/hndsd.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/hndse.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/app.min.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/app.min.js")); ?>"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/fancybox-6.1.14/js/fancybox.umd.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/bowser-2.14.1/es5.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/common.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/common.js")); ?>"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/gallery.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/gallery.js")); ?>"></script>
</body>
</html>
