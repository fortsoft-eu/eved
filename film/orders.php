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
    $aOrders = $oStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $oException) {
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
  <meta name="viewport" content="<?php echo htmlspecialchars(getAdminViewportContent(), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>Photo Lab Orders</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderFilmMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="orders-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="orders-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Lab</th>
        <th>Order Number</th>
        <th>Bag Number</th>
        <th>Film Rolls</th>
        <th>Scan Dates and Times</th>
        <th style="text-align: right;">Price</th>
        <th style="text-align: right;">Price (VAT)</th>
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
    $sOrderedAt = substr((string)$aRow["ordered_at"], 0, 16);
    $blReturnedAtError = false;
    if ($aRow["returned_at"] === null) {
        $sReturnedAt = "Not yet";
        $blReturnedAtError = true;
    } else {
        $sReturnedAt = substr((string)$aRow["returned_at"], 0, 10);
        if ($sReturnedAt == "0000-00-00") {
            $sReturnedAt = "N/A";
        }
    }
    $sInvoice = substr((string)$aRow["invoice"], 0, 16);
    $sScans = $aRow["scan_dates"] !== null ? htmlspecialchars($aRow["scan_dates"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : "<em>&mdash;</em>";
    $sScans = str_replace("&lt;br&gt;", "<br>", $sScans);
    $sFilmRolls = $aRow["film_rolls"] !== null ? htmlspecialchars($aRow["film_rolls"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : "<em>&mdash;</em>";
    $sFilmRolls = str_replace("&lt;br&gt;", "<br>", $sFilmRolls);
    $sFilmScanDates = $aRow["film_scan_dates"] !== null ? htmlspecialchars($aRow["film_scan_dates"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : "<em>&mdash;</em>";
    $sFilmScanDates = str_replace("&lt;br&gt;", "<br>", $sFilmScanDates);
    $sFilmScanDates = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $sFilmScanDates);
    $sOrderedAt = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", htmlspecialchars($sOrderedAt, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"));
    $sReturnedAt = htmlspecialchars($sReturnedAt, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sInvoice = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", htmlspecialchars($sInvoice, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"));
    $sScans = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $sScans);
    echo "      <tr>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow["lab"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow["order_no"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars((string)$aRow["bag_no"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . $sFilmRolls . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . $sFilmScanDates . "</td>\n"
        . "        <td style=\"text-align: right; vertical-align: top;\">" . htmlspecialchars((string)$aRow["price"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"text-align: right; vertical-align: top;\">" . htmlspecialchars((string)$aRow["price_vat"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars((string)$aRow["currency"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . $sOrderedAt . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . $sScans . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . $sInvoice . "</td>\n"
        . "        <td" . ($blReturnedAtError ? " class=\"error-cell\"" : "") . " style=\"vertical-align: top;\">" . $sReturnedAt . "</td>\n"
        . "      </tr>\n";
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/common.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/common.js")); ?>"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js"></script>
</body>
</html>
