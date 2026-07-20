<?php

include "main.php";


requireFullAccess($aAllowedIps, "ex", "ex_csrf_token", true);


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aExternalLibraries = array();
$sLibraryDirectory = __DIR__ . "/lib";
if (is_dir($sLibraryDirectory)) {
    $oDirectory = new DirectoryIterator($sLibraryDirectory);
    foreach ($oDirectory as $oItem) {
        if (!$oItem->isFile()) {
            continue;
        }
        $sPathname = $oItem->getPathname();
        $aExternalLibraries[] = array(
            "permissions" => externalLibraryPermissions($sPathname),
            "owner" => externalLibraryOwner($sPathname),
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText("External Libraries", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="external-libraries-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="external-libraries-table" class="table-filter-target consistency-table<?php echo getCondensedTableClass(); ?>">
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
    echo "      <tr>\n",
        "        <td>" . htmlValue($aExternalLibrary["permissions"]) . "</td>\n",
        "        <td>" . htmlValue($aExternalLibrary["owner"]) . "</td>\n",
        "        <td>" . htmlValue($aExternalLibrary["downloaded_at"]) . "</td>\n",
        "        <td>" . htmlValue($aExternalLibrary["name"]) . "</td>\n",
        "      </tr>\n";
}
echo "    </tbody>\n",
    "  </table>\n",
    renderEmojiData();

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
