<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "film", "film_csrf_token", true);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("film_csrf_token", true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "mark_scan_processed") {
    $iFilmScanId = isset($_POST["film_scan_id"]) ? (int)$_POST["film_scan_id"] : 0;
    if ($iFilmScanId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid film scan."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM fs_film_scans WHERE id = :film_scan_id FOR UPDATE");
        $oStatement->execute(array("film_scan_id" => $iFilmScanId));
        if (!$oStatement->fetch()) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Film scan was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE fs_film_scans SET `work` = 0 WHERE id = :film_scan_id");
        $oStatement->execute(array("film_scan_id" => $iFilmScanId));
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "film_scan_id" => $iFilmScanId));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

$aRows = array();
try {
    $oStatement = $oPdo->prepare("SELECT f.id, f.archive_no, f.folder_name, f.film_stock, f.cartridge, f.scanned_at, f.scan_format, f.scan_width, f.scan_height FROM fs_film_scans AS f WHERE f.`work` = 1 AND f.archive_no <= 990 ORDER BY f.archive_no ASC");
    $oStatement->execute();
    $aRows = $oStatement->fetchAll();
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$sCsrfToken = getCsrfToken("film_csrf_token");
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
  <title><?php echo html(getPageTitleText($aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html($sCsrfToken); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="film-work-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <span class="message-box message-success js-film-work-message" style="display: none;" hidden></span>
  </p>
<?php

if (!$aRows) {
    echo "  <p>Nothing to do.</p>\n";
} else {

?>
  <table id="film-work-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th style="text-align: right; width: 1px;">#</th>
        <th>Archive Folder Name</th>
        <th>Film Stock</th>
        <th>Cartridge</th>
        <th>Scan Date and Time</th>
        <th>Format</th>
        <th>Resolution</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
<?php

    foreach ($aRows as $aRow) {
        $sScanDate = isset($aRow["scanned_at"]) ? substr((string)$aRow["scanned_at"], 0, 16) : "";
        if ($sScanDate == "" || substr($sScanDate, 0, 10) == "0000-00-00") {
            $sScanDate = "not set";
        }
        $sResolution = (int)$aRow["scan_width"] . " x " . (int)$aRow["scan_height"];
        echo "      <tr data-film-scan-id=\"" . html($aRow["id"]) . "\">\n",
            "        <td style=\"text-align: right;\">" . html($aRow["archive_no"]) . "</td>\n",
            "        <td>" . html($aRow["folder_name"]) . "</td>\n",
            "        <td>" . html($aRow["film_stock"]) . "</td>\n",
            "        <td>" . html($aRow["cartridge"]) . "</td>\n",
            "        <td>" . html($sScanDate) . "</td>\n",
            "        <td>" . html($aRow["scan_format"]) . "</td>\n",
            "        <td>" . html($sResolution) . "</td>\n",
            "        <td><button type=\"button\" class=\"button-link js-film-scan-processed\" data-film-scan-id=\"" . html($aRow["id"]) . "\">Processed</button></td>\n",
            "      </tr>\n";
    }

?>
    </tbody>
  </table>
<?php

}

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
