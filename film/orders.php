<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aOrders = array();
try {
    $oStatement = $oPdo->prepare("SELECT o.id, o.lab, o.order_no, o.bag_no, o.price, o.price_vat, o.currency, o.ordered_at, o.returned_at, o.invoice, fs.film_rolls, fs.film_scan_dates, fs.scan_dates FROM fs_photo_lab_orders AS o
        LEFT JOIN (SELECT lab_order_id, GROUP_CONCAT(folder_name ORDER BY archive_no SEPARATOR '<br>') AS film_rolls, GROUP_CONCAT(DATE_FORMAT(scanned_at, '%Y-%m-%d %H:%i') ORDER BY archive_no SEPARATOR '<br>') AS film_scan_dates, GROUP_CONCAT(DISTINCT DATE_FORMAT(scanned_at, '%Y-%m-%d %H:%i') ORDER BY DATE_FORMAT(scanned_at, '%Y-%m-%d %H:%i') SEPARATOR '<br>') AS scan_dates FROM fs_film_scans WHERE lab_order_id IS NOT NULL GROUP BY lab_order_id) AS fs ON fs.lab_order_id = o.id ORDER BY o.ordered_at ASC");
    $oStatement->execute();
    $aOrders = $oStatement->fetchAll();
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
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="orders-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <span class="table-record-count js-table-record-count" data-table-count="orders-table" aria-live="polite"><?php echo count($aOrders); ?></span>
  </p>
  <table id="orders-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Lab</th>
        <th>Order Number</th>
        <th>Bag Number</th>
        <th>Film Rolls</th>
        <th>Scan Dates and Times</th>
        <th class="cell-number">Price</th>
        <th class="cell-number">Price (VAT)</th>
        <th>Currency</th>
        <th>Order Date and Time</th>
        <th>Scan Dates and Times</th>
        <th>Invoice Date and Time</th>
        <th>Return Date</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aOrders as $aRow) {
    $sOrderedAt = substr($aRow["ordered_at"], 0, 16);
    $blReturnedAtError = false;
    $blBagNoEmpty = $aRow["bag_no"] === null || trim((string)$aRow["bag_no"]) == "";
    if ($blBagNoEmpty) {
        $sReturnedAt = "N/A";
    } elseif ($aRow["returned_at"] === null) {
        $sReturnedAt = "Not yet";
        $blReturnedAtError = true;
    } else {
        $sReturnedAt = substr($aRow["returned_at"], 0, 10);
        if ($sReturnedAt == "0000-00-00") {
            $sReturnedAt = "N/A";
        }
    }
    $sInvoice = substr($aRow["invoice"] ?? "", 0, 16);
    $sScans = $aRow["scan_dates"] !== null ? html($aRow["scan_dates"]) : "<em>&mdash;</em>";
    $sScans = str_replace("&lt;br&gt;", "<br>", $sScans);
    $sFilmRolls = $aRow["film_rolls"] !== null ? html($aRow["film_rolls"]) : "<em>&mdash;</em>";
    $sFilmRolls = str_replace("&lt;br&gt;", "<br>", $sFilmRolls);
    $sFilmScanDates = $aRow["film_scan_dates"] !== null ? html($aRow["film_scan_dates"]) : "<em>&mdash;</em>";
    $sFilmScanDates = str_replace("&lt;br&gt;", "<br>", $sFilmScanDates);
    $sFilmScanDates = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $sFilmScanDates);
    $sOrderedAt = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", html($sOrderedAt));
    $sReturnedAt = html($sReturnedAt);
    $sInvoice = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", html($sInvoice));
    $sScans = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $sScans);
    echo "      <tr>\n",
        "        <td>" . html($aRow["lab"]) . "</td>\n",
        "        <td>" . html($aRow["order_no"]) . "</td>\n",
        "        <td>" . html($aRow["bag_no"]) . "</td>\n",
        "        <td>" . $sFilmRolls . "</td>\n",
        "        <td>" . $sFilmScanDates . "</td>\n",
        "        <td class=\"cell-number\">" . html($aRow["price"]) . "</td>\n",
        "        <td class=\"cell-number\">" . html($aRow["price_vat"]) . "</td>\n",
        "        <td>" . html($aRow["currency"]) . "</td>\n",
        "        <td>" . $sOrderedAt . "</td>\n",
        "        <td>" . $sScans . "</td>\n",
        "        <td>" . $sInvoice . "</td>\n",
        "        <td" . ($blReturnedAtError ? " class=\"error-cell\"" : "") . ">" . $sReturnedAt . "</td>\n",
        "      </tr>\n";
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
