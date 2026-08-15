<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aRows = array();
try {
    $oStatement = $oPdo->prepare("SELECT f.archive_no, f.folder_name, f.film_stock, f.cartridge, f.exposure_index, f.push_pull, f.scanned_at, o.order_no FROM fs_film_scans AS f
        LEFT JOIN fs_photo_lab_orders AS o ON f.lab_order_id = o.id WHERE f.archive_no <= 990 ORDER BY f.archive_no ASC");
    $oStatement->execute();
    $aRows = $oStatement->fetchAll();
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

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
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/admin-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/admin-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/admin-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/admin-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/admin-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/admin-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/admin-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/admin-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/admin-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/admin-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/admin-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/admin-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/admin-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/admin-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/admin-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/admin-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/admin-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/admin-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/admin-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/admin-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body>
<?php

if ($sError) {
    echo "  <p><strong>Error:</strong> " . html($sError) . "</p>\n";
} elseif (!$aRows) {
    echo "  <p>No records found.</p>\n";
} else {
echo "  <p class=\"admin-controls\">\n";
renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="film-scans-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <span class="table-record-count js-table-record-count" data-table-count="film-scans-table" aria-live="polite"><?php echo count($aRows); ?></span>
  </p>
  <table id="film-scans-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th style="text-align: right; width: 1px;">#</th>
        <th>Archive Folder Name</th>
        <th>Film Stock</th>
        <th>Cartridge</th>
        <th>EI</th>
        <th>Push/Pull</th>
        <th>Scan Date and Time</th>
        <th>Order Number</th>
      </tr>
    </thead>
    <tbody>
<?php

    foreach ($aRows as $aRow) {
        $sFolderName = isset($aRow["folder_name"]) ? trim($aRow["folder_name"]) : "";
        $blArchiveOk = false;
        $blFilmStockOk = false;
        $blCartridgeOk = false;
        if ($sFolderName) {
            $aParts = preg_split("/\s+/", $sFolderName);
            if (is_array($aParts) && count($aParts) >= 4) {
                $sArchiveFromFolder = $aParts[0];
                $sCartridgeFromFolder = $aParts[count($aParts) - 1];
                $aFilmStockParts = array_slice($aParts, 2, -1);
                $sFilmStockFromFolder = implode(" ", $aFilmStockParts);

                $blArchiveOk = ((string)$aRow["archive_no"] == $sArchiveFromFolder);
                $blFilmStockOk = ((string)$aRow["film_stock"] == $sFilmStockFromFolder);
                $blCartridgeOk = ((string)$aRow["cartridge"] == $sCartridgeFromFolder);
            }
        }
        $mExposureValue = $aRow["exposure_index"];
        if (!$mExposureValue) {
            $mExposureValue = "unknown";
        }
        $sScanDateRaw = isset($aRow["scanned_at"]) ? substr((string)$aRow["scanned_at"], 0, 16) : "";
        $sPushPull = formatPushPull($aRow["push_pull"]);
        $blScanError = false;
        $sScanDateDisplay = $sScanDateRaw;
        if (substr($sScanDateRaw, 0, 10) == "0000-00-00") {
            $sScanDateDisplay = "not set";
            $blScanError = true;
        }
        $sScanDateDisplay = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", html($sScanDateDisplay));
        $sOrderNumber = (string)($aRow["order_no"] ?? "");
        echo "      <tr data-order-id=\"" . html($sOrderNumber) . "\" data-order-no=\"" . html($sOrderNumber) . "\">\n",
            "      <td style=\"text-align: right;\">" . html((string)$aRow["archive_no"]) . "</td>\n";
        renderCell($sFolderName, false);
        renderCell($aRow["film_stock"], !$blFilmStockOk);
        renderCell($aRow["cartridge"], !$blCartridgeOk);
        renderCell($mExposureValue, false);
        renderCell($sPushPull, false);
        echo "      <td" . ($blScanError ? " class=\"error-cell\"" : "") . ">" . $sScanDateDisplay . "</td>\n";
        renderCell($aRow["order_no"], false);
        echo "      </tr>\n";
    }

}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
