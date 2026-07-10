<?php

include "main.php";


requireExFullAccess($aAllowedIps);

$aExternalLibraries = array();
$sLibraryDirectory = __DIR__ . "/lib";

function nxExternalLibraryPermissions($sPath) {
    $iPerms = @fileperms($sPath);
    if ($iPerms === false) {
        return "";
    }

    if (($iPerms & 0xC000) === 0xC000) {
        $sInfo = "s";
    } else if (($iPerms & 0xA000) === 0xA000) {
        $sInfo = "l";
    } else if (($iPerms & 0x8000) === 0x8000) {
        $sInfo = "-";
    } else if (($iPerms & 0x6000) === 0x6000) {
        $sInfo = "b";
    } else if (($iPerms & 0x4000) === 0x4000) {
        $sInfo = "d";
    } else if (($iPerms & 0x2000) === 0x2000) {
        $sInfo = "c";
    } else if (($iPerms & 0x1000) === 0x1000) {
        $sInfo = "p";
    } else {
        $sInfo = "u";
    }

    $sInfo .= ($iPerms & 0x0100) ? "r" : "-";
    $sInfo .= ($iPerms & 0x0080) ? "w" : "-";
    $sInfo .= ($iPerms & 0x0040) ? (($iPerms & 0x0800) ? "s" : "x") : (($iPerms & 0x0800) ? "S" : "-");
    $sInfo .= ($iPerms & 0x0020) ? "r" : "-";
    $sInfo .= ($iPerms & 0x0010) ? "w" : "-";
    $sInfo .= ($iPerms & 0x0008) ? (($iPerms & 0x0400) ? "s" : "x") : (($iPerms & 0x0400) ? "S" : "-");
    $sInfo .= ($iPerms & 0x0004) ? "r" : "-";
    $sInfo .= ($iPerms & 0x0002) ? "w" : "-";
    $sInfo .= ($iPerms & 0x0001) ? (($iPerms & 0x0200) ? "t" : "x") : (($iPerms & 0x0200) ? "T" : "-");
    return $sInfo;
}

function nxExternalLibraryOwner($sPath) {
    $iOwner = @fileowner($sPath);
    if ($iOwner === false) {
        return "";
    }
    if (function_exists("posix_getpwuid")) {
        $aOwner = @posix_getpwuid($iOwner);
        if (is_array($aOwner) && isset($aOwner["name"])) {
            return (string)$aOwner["name"];
        }
    }
    return (string)$iOwner;
}

if (is_dir($sLibraryDirectory)) {
    $oDirectory = new DirectoryIterator($sLibraryDirectory);
    foreach ($oDirectory as $oItem) {
        if (!$oItem->isFile()) {
            continue;
        }
        $sPathname = $oItem->getPathname();
        $aExternalLibraries[] = array(
            "permissions" => nxExternalLibraryPermissions($sPathname),
            "owner" => nxExternalLibraryOwner($sPathname),
            "downloaded_at" => date("Y-m-d H:i:s", $oItem->getMTime()),
            "name" => $oItem->getFilename()
        );
    }
}

usort($aExternalLibraries, function ($aLeft, $aRight) {
    return strcmp((string)$aLeft["name"], (string)$aRight["name"]);
});

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
  <title><?php echo nxHtml(getExPageTitleText("External Libraries", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-external-libraries-table" value="<?php echo nxHtml(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="nx-external-libraries-table" class="table-filter-target consistency-table">
    <thead>
      <tr>
        <th>Permissions</th>
        <th>Owner</th>
        <th>Downloaded At</th>
        <th>Name</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aExternalLibraries as $aExternalLibrary) {
    echo "      <tr>\n"
        . "        <td>" . nxHtmlValue($aExternalLibrary["permissions"]) . "</td>\n"
        . "        <td>" . nxHtmlValue($aExternalLibrary["owner"]) . "</td>\n"
        . "        <td>" . nxHtmlValue($aExternalLibrary["downloaded_at"]) . "</td>\n"
        . "        <td>" . nxHtmlValue($aExternalLibrary["name"]) . "</td>\n"
        . "      </tr>\n";
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter">&#128269; Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
