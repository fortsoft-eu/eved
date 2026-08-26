<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

requireFullAccess("film", "csrf_token");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("csrf_token");
}

$sMessage = "";
$sMessageType = "";
$blRedirectAfterPost = false;

if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
    $_SESSION["film"] = array();
}
if (!isset($_SESSION["film"]["link"]) || !is_array($_SESSION["film"]["link"])) {
    $_SESSION["film"]["link"] = array();
}

if ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_SESSION["film"]["link"]["message"])) {
    if (is_string($_SESSION["film"]["link"]["message"])) {
        $sMessage = $_SESSION["film"]["link"]["message"];
    }
    if (isset($_SESSION["film"]["link"]["type"]) && is_string($_SESSION["film"]["link"]["type"])) {
        $sMessageType = $_SESSION["film"]["link"]["type"];
    }
    unset($_SESSION["film"]["link"]["message"], $_SESSION["film"]["link"]["type"]);
} elseif ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_SESSION["film"]["link"]["type"])) {
    unset($_SESSION["film"]["link"]["type"]);
}

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["unassign"])) {
        $blRedirectAfterPost = true;
        $iFilmId = (int)$_POST["unassign"];
        if ($iFilmId > 0) {
            $oStmt = $oPdo->prepare("SELECT f.archive_no, f.folder_name, f.lab_order_id, o.bag_no, o.order_no, o.ordered_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) AS can_unassign FROM fs_film_scans AS f LEFT JOIN fs_photo_lab_orders AS o ON f.lab_order_id = o.id WHERE f.id = :film_id");
            $oStmt->execute(array(
                ":film_id" => $iFilmId
            ));
            $aUnassign = $oStmt->fetch();
            if ($aUnassign && $aUnassign["lab_order_id"] !== null && (int)$aUnassign["can_unassign"] == 1) {
                $oStmt = $oPdo->prepare("UPDATE fs_film_scans SET lab_order_id = NULL, updated_at = NOW(6) WHERE id = :film_id AND lab_order_id IS NOT NULL");
                $oStmt->execute(array(
                    ":film_id" => $iFilmId
                ));
                if ($oStmt->rowCount() > 0) {
                    $sMessage = "The film roll <strong>" . formatFilmOptionLabel($aUnassign) . "</strong> has been unassigned from the lab bag <strong>" . formatOrderOptionLabel($aUnassign) . "</strong>.";
                    $sMessageType = "success";
                } else {
                    $sMessage = "The film roll <strong>" . formatFilmOptionLabel($aUnassign) . "</strong> could not be unassigned from the lab bag <strong>" . formatOrderOptionLabel($aUnassign) . "</strong>.";
                    $sMessageType = "error";
                }
            } elseif ($aUnassign && $aUnassign["lab_order_id"] !== null) {
                $sMessage = "The film roll <strong>" . formatFilmOptionLabel($aUnassign) . "</strong> can no longer be unassigned because the lab order is older than one year.";
                $sMessageType = "warning";
            } elseif ($aUnassign) {
                $sMessage = "The film roll <strong>" . formatFilmOptionLabel($aUnassign) . "</strong> is already unassigned.";
                $sMessageType = "warning";
            } else {
                $sMessage = "The film roll could not be unassigned from the lab bag.";
                $sMessageType = "error";
            }
        }
    }
    if ($blRedirectAfterPost) {
        if ($sMessage) {
            $_SESSION["film"]["link"]["message"] = $sMessage;
            $_SESSION["film"]["link"]["type"] = $sMessageType;
        }
        sendSecurityHeaders();
        header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
        exit;
    }
    $oStmtOrders = $oPdo->query("SELECT id, lab, bag_no, order_no FROM fs_photo_lab_orders WHERE bag_no IS NOT NULL AND bag_no != '' AND ordered_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) ORDER BY order_no ASC");
    $aOrders = $oStmtOrders->fetchAll();
    $iLastBagId = null;
    if (isset($_SESSION["film"]["link"]["bag"])) {
        if (is_int($_SESSION["film"]["link"]["bag"])) {
            foreach ($aOrders as $aOrder) {
                if ((int)$aOrder["id"] == (int)$_SESSION["film"]["link"]["bag"]) {
                    $iLastBagId = (int)$_SESSION["film"]["link"]["bag"];
                    break;
                }
            }
        } else {
            unset($_SESSION["film"]["link"]["bag"]);
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["unassign"])) {
        $blRedirectAfterPost = true;
        $iFilmId = isset($_POST["film_id"]) ? (int)$_POST["film_id"] : 0;
        $iOrderId = isset($_POST["order_id"]) ? (int)$_POST["order_id"] : 0;
        if ($iFilmId > 0 && $iOrderId > 0) {
            $aSelectedFilm = null;
            $aSelectedOrder = null;
            $oStmt = $oPdo->prepare("SELECT id, archive_no, folder_name, lab_order_id FROM fs_film_scans WHERE id = :film_id");
            $oStmt->execute(array(":film_id" => $iFilmId));
            $aSelectedFilm = $oStmt->fetch();
            foreach ($aOrders as $aOrder) {
                if ((int)$aOrder["id"] == $iOrderId) {
                    $aSelectedOrder = $aOrder;
                    break;
                }
            }
            if ($aSelectedFilm && $aSelectedOrder) {
                if ((int)$aSelectedFilm["lab_order_id"] == $iOrderId) {
                    $sMessage = "The film roll <strong>" . formatFilmOptionLabel($aSelectedFilm) . "</strong> is already assigned to the lab bag <strong>" . formatOrderOptionLabel($aSelectedOrder) . "</strong>.";
                    $sMessageType = "warning";
                } else {
                    $oStmt = $oPdo->prepare("UPDATE fs_film_scans SET lab_order_id = :order_id, updated_at = NOW(6) WHERE id = :film_id");
                    $oStmt->execute(array(":order_id" => $iOrderId, ":film_id" => $iFilmId));
                    $iLastBagId = $iOrderId;
                    if ($oStmt->rowCount() > 0) {
                        $_SESSION["film"]["link"]["bag"] = $iOrderId;
                        $sMessage = "The film roll <strong>" . formatFilmOptionLabel($aSelectedFilm) . "</strong> has been assigned to the lab bag <strong>" . formatOrderOptionLabel($aSelectedOrder) . "</strong>.";
                        $sMessageType = "success";
                    } else {
                        $sMessage = "The film roll <strong>" . formatFilmOptionLabel($aSelectedFilm) . "</strong> could not be assigned to the lab bag <strong>" . formatOrderOptionLabel($aSelectedOrder) . "</strong>.";
                        $sMessageType = "error";
                    }
                }
            } else {
                $sMessage = "The film roll could not be assigned to the lab bag.";
                $sMessageType = "error";
            }
        } else {
            $sMessage = "You must select both a film roll and a lab bag.";
            $sMessageType = "error";
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if ($sMessage) {
            $_SESSION["film"]["link"]["message"] = $sMessage;
            $_SESSION["film"]["link"]["type"] = $sMessageType;
        }
        sendSecurityHeaders();
        header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
        exit;
    }
    $oStmtFilms = $oPdo->query("SELECT id, archive_no, folder_name, lab_order_id FROM fs_film_scans WHERE lab_order_id IS NULL AND archive_no <= 990 ORDER BY archive_no ASC");
    $aFilms = $oStmtFilms->fetchAll();
    $oStmtLinks = $oPdo->query("SELECT f.id AS film_id, f.archive_no, f.folder_name, f.scanned_at, f.lab_order_id, o.lab, o.bag_no, o.order_no, o.price_vat, o.currency, o.ordered_at, o.returned_at, o.invoice, o.ordered_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) AS can_unassign, fd.film_scan_dates, sd.scan_dates FROM fs_film_scans AS f LEFT JOIN fs_photo_lab_orders AS o ON f.lab_order_id = o.id LEFT JOIN (SELECT lab_order_id, GROUP_CONCAT(DISTINCT DATE(scanned_at) ORDER BY scanned_at SEPARATOR '\\n') AS film_scan_dates FROM fs_film_scans WHERE lab_order_id IS NOT NULL GROUP BY lab_order_id) AS fd ON fd.lab_order_id = o.id LEFT JOIN (SELECT lab_order_id, GROUP_CONCAT(DISTINCT DATE(scanned_at) ORDER BY scanned_at SEPARATOR '\\n') AS scan_dates FROM fs_film_scans WHERE lab_order_id IS NOT NULL GROUP BY lab_order_id) AS sd ON sd.lab_order_id = o.id WHERE f.archive_no <= 990 ORDER BY f.archive_no ASC");
    $aLinks = $oStmtLinks->fetchAll();
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$sCsrfToken = getCsrfToken("csrf_token");
session_write_close();
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link rel="icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" title="Original">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" title="Graphite">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" title="Midnight">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" title="Slate">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" title="Sepia">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" title="Sand">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" title="Forest">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" title="Moss">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" title="Ocean">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" title="Ice">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" title="Lavender">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" title="Rose">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" title="Copper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" title="Burgundy">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" title="Monochrome">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" title="High Contrast">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" title="Soft">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" title="Paper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" title="Terminal">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" title="Cobalt">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>" integrity="sha384-Y1RNflBlpVdW8pnre87MlysCCOkrIrejOEni6hIeWzTbOmboT6BgObKOeMYSJ5C5"></script>
</head>
<body class="film-link-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
<?php

echo "  <p class=\"admin-controls\">\n";
renderMenu();
echo "    <span class=\"table-record-count js-table-record-count\" data-table-count=\"film-link-table\" aria-live=\"polite\">" . count($aLinks) . "</span>\n";
if ($sMessage) {
    echo "    <span class=\"message-box message-" . html($sMessageType) . "\" id=\"message-box\">" . $sMessage . "</span>\n";
}

?>
  </p>
  <div class="admin-top">
    <form method="post" action="<?php echo $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="csrf_token" value="<?php echo html($sCsrfToken); ?>">
      <label for="film_id">Film Roll</label>
      <select name="film_id" id="film_id" required>
        <option value="">– Choose film roll –</option>
<?php

foreach ($aFilms as $aFilm) {
    echo "        <option value=\"" . html($aFilm["id"]) . "\">" . formatFilmOptionLabel($aFilm) . "</option>\n";
}

?>
      </select>
      <label for="order_id">Bag Number</label>
      <select name="order_id" id="order_id" required>
        <option value="">– Choose bag number –</option>
<?php

foreach ($aOrders as $aOrder) {
    $sLabel = formatOrderOptionLabel($aOrder);
    $blSelected = $iLastBagId !== null && $aOrder["id"] === $iLastBagId;
    echo "        <option value=\"" . html($aOrder["id"]) . "\"" . ($blSelected ? " selected" : "") . ">" . $sLabel . "</option>\n";
}

?>
      </select>
      <button type="submit">Assign</button>
    </form>
    <div class="order-detail" id="order-detail">
      <h2>Order Detail</h2>
      <div class="order-detail-columns">
        <dl class="order-detail-main">
          <dt>Lab</dt><dd data-detail="lab">&mdash;</dd>
          <dt>Bag Number</dt><dd data-detail="bag-no">&mdash;</dd>
          <dt>Order Number</dt><dd data-detail="order-no">&mdash;</dd>
          <dt>Price</dt><dd data-detail="price">&mdash;</dd>
          <dt>Order Date</dt><dd data-detail="order-date">&mdash;</dd>
          <dt>Return Date</dt><dd data-detail="return-date">&mdash;</dd>
        </dl>
        <dl class="order-detail-invoice">
          <dt>Invoice Date</dt><dd data-detail="invoice-date">&mdash;</dd>
        </dl>
        <dl class="order-detail-dates">
          <dt>Film Scan Dates</dt><dd class="multiline" data-detail="film-scan-dates">&mdash;</dd>
        </dl>
        <dl class="order-detail-dates">
          <dt>Scan Dates</dt><dd class="multiline" data-detail="lab-scan-dates">&mdash;</dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="film-link-table-scroll js-film-link-table-scroll">
  <table id="film-link-table" class="<?php echo trim(getCondensedTableClass() . " film-link-table"); ?>">
    <thead>
      <tr>
        <th class="cell-key">#</th>
        <th>Archive Folder Name</th>
        <th>Bag Number</th>
        <th>Order Number</th>
        <th>Order Date and Time</th>
        <th>Scan Date and Time</th>
        <th>Return Date</th>
        <th>Assigned</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aLinks as $aLink) {
    $sOrderDate = substr($aLink["ordered_at"] ?? "", 0, 16);
    $sOrderDate = substr($sOrderDate, 0, 10) == "0000-00-00" ? "" : $sOrderDate;
    $sScanDate = substr($aLink["scanned_at"], 0, 16);
    $sScanDate = substr($sScanDate, 0, 10) == "0000-00-00" ? "" : $sScanDate;
    $sReturnDate = substr($aLink["returned_at"] ?? "", 0, 10);
    $sReturnDate = substr($sReturnDate, 0, 10) == "0000-00-00" ? "" : $sReturnDate;
    $sInvoiceDate = substr($aLink["invoice"] ?? "", 0, 16);
    $sInvoiceDate = substr($sInvoiceDate, 0, 10) == "0000-00-00" ? "" : $sInvoiceDate;
    $sOrderDetailDate = substr($sOrderDate, 0, 10);
    $sReturnDetailDate = substr($sReturnDate, 0, 10);
    $sInvoiceDetailDate = substr($sInvoiceDate, 0, 10);
    $sScanDetailDates = $aLink["scan_dates"] ?? "";
    $sOrderDateDisplay = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", html($sOrderDate));
    $sScanDateDisplay = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", html($sScanDate));
    $sFilmLabel = formatFilmOptionLabel($aLink);
    $sOrderLabel = formatOrderOptionLabel($aLink);
    $sPrice = $aLink["price_vat"] === null ? "" : number_format((float)$aLink["price_vat"], 2, ".", "");

?>
      <tr data-order-id="<?php echo html($aLink["lab_order_id"] ?? ""); ?>" data-order-no="<?php echo html($aLink["order_no"] ?? ""); ?>" data-lab="<?php echo html($aLink["lab"] ?? ""); ?>" data-bag-no="<?php echo html($aLink["bag_no"] ?? ""); ?>" data-price="<?php echo html($sPrice); ?>" data-currency="<?php echo html($aLink["currency"] ?? ""); ?>" data-order-date="<?php echo html($sOrderDetailDate); ?>" data-return-date="<?php echo html($sReturnDetailDate); ?>" data-invoice-date="<?php echo html($sInvoiceDetailDate); ?>" data-film-scan-dates="<?php echo html($aLink["film_scan_dates"] ?? ""); ?>" data-lab-scan-dates="<?php echo html($sScanDetailDates); ?>">
        <td class="cell-number"><?php echo html($aLink["archive_no"]); ?></td>
        <td><?php echo html($aLink["folder_name"]); ?></td>
        <td><?php echo html($aLink["bag_no"] ?? ""); ?></td>
        <td><?php echo html($aLink["order_no"] ?? ""); ?></td>
        <td><?php echo $sOrderDateDisplay; ?></td>
        <td><?php echo $sScanDateDisplay; ?></td>
        <td><?php echo html($sReturnDate); ?></td>
        <td><?php echo $aLink["lab_order_id"] === null ? "No" : (((int)$aLink["can_unassign"] == 1) ? "Yes <button type=\"button\" class=\"button-link js-confirm-unassign\" data-confirm-action=\"" . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]) . "\" data-unassign-id=\"" . (int)$aLink["film_id"] . "\" data-film-roll=\"" . $sFilmLabel . "\" data-lab-bag=\"" . $sOrderLabel . "\">Unassign</button>" : "Yes"); ?></td>
      </tr>
<?php

}

?>
    </tbody>
  </table>
  </div>
  <div class="confirm-dialog" id="film-unassign-confirm-dialog" hidden>
    <form class="confirm-dialog-box" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="csrf_token" value="<?php echo html($sCsrfToken); ?>">
      <input type="hidden" name="unassign" value="">
      <div class="confirm-dialog-header">
        <strong>Confirm Unassignment</strong>
        <button type="button" class="confirm-dialog-close js-film-unassign-close" aria-label="Close">&times;</button>
      </div>
      <p class="confirm-dialog-message">The film roll <strong class="js-film-unassign-roll"></strong><br> will be unassigned from the lab bag <strong class="js-film-unassign-bag"></strong>.</p>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button js-film-unassign-confirm">Yes</button>
        <button type="button" class="confirm-dialog-button js-film-unassign-cancel">No</button>
      </div>
    </form>
  </div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>" integrity="sha384-572SFF7xvjmriDQZZGIRbD2qZc6ohDrtmU7qAT8WiW4/A7XIV32imryKHGDLveoO"></script>
</body>
</html>
