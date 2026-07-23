<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

requireFullAccess($aAllowedIps, "film", "film_csrf_token");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("film_csrf_token");
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
            $aUnassign = $oStmt->fetch(PDO::FETCH_ASSOC);
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
    $aOrders = $oStmtOrders->fetchAll(PDO::FETCH_ASSOC);
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
            $aSelectedFilm = $oStmt->fetch(PDO::FETCH_ASSOC);
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
    $aFilms = $oStmtFilms->fetchAll(PDO::FETCH_ASSOC);
    $oStmtLinks = $oPdo->query("SELECT f.id AS film_id, f.archive_no, f.folder_name, f.scanned_at, f.lab_order_id, o.lab, o.bag_no, o.order_no, o.price_vat, o.currency, o.ordered_at, o.returned_at, o.invoice, o.ordered_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) AS can_unassign, fd.film_scan_dates, sd.scan_dates FROM fs_film_scans AS f LEFT JOIN fs_photo_lab_orders AS o ON f.lab_order_id = o.id LEFT JOIN (SELECT lab_order_id, GROUP_CONCAT(DISTINCT DATE(scanned_at) ORDER BY scanned_at SEPARATOR '\\n') AS film_scan_dates FROM fs_film_scans WHERE lab_order_id IS NOT NULL GROUP BY lab_order_id) AS fd ON fd.lab_order_id = o.id LEFT JOIN (SELECT lab_order_id, GROUP_CONCAT(DISTINCT DATE(scanned_at) ORDER BY scanned_at SEPARATOR '\\n') AS scan_dates FROM fs_film_scans WHERE lab_order_id IS NOT NULL GROUP BY lab_order_id) AS sd ON sd.lab_order_id = o.id WHERE f.archive_no <= 990 ORDER BY f.archive_no ASC");
    $aLinks = $oStmtLinks->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

session_write_close();
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
  <title><?php echo htmlspecialchars(getPageTitleText("Assign Film to Lab Bag", $aAllowedIps), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php

if ($sMessage) {
    echo "  <div class=\"message-box message-" . htmlspecialchars($sMessageType, ENT_QUOTES, "UTF-8") . "\" id=\"message-box\">" . $sMessage . "</div>\n";
}
echo "  <p class=\"admin-controls\">\n";
renderFilmMenu();
echo "  </p>\n";

?>
  <div class="admin-top">
    <form method="post" action="<?php echo $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="film_csrf_token" value="<?php echo htmlspecialchars(getCsrfToken("film_csrf_token"), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
      <label for="film_id">Film Roll:</label>
      <select name="film_id" id="film_id" required>
        <option value="">– Choose film roll –</option>
<?php

foreach ($aFilms as $aFilm) {
    echo "        <option value=\"" . htmlspecialchars((string)$aFilm["id"], ENT_QUOTES, "UTF-8") . "\">" . formatFilmOptionLabel($aFilm) . "</option>\n";

}
echo "      </select>\n";

?>

      <label for="order_id">Bag Number:</label>
      <select name="order_id" id="order_id" required>
        <option value="">– Choose bag number –</option>
<?php

foreach ($aOrders as $aOrder) {
    $sLabel = formatOrderOptionLabel($aOrder);
    $blSelected = $iLastBagId !== null && $aOrder["id"] === $iLastBagId;
    echo "        <option value=\"" . htmlspecialchars((string)$aOrder["id"], ENT_QUOTES, "UTF-8") . "\"" . ($blSelected ? " selected" : "") . ">" . $sLabel . "</option>\n";

}
echo "      </select>\n";

?>

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

  <table class="<?php echo trim(getCondensedTableClass()); ?>">
    <thead>
      <tr>
        <th style="text-align: right; width: 1px;">#</th>
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
    $sOrderDate = substr((string)($aLink["ordered_at"] ?? ""), 0, 16);
    $sOrderDate = substr($sOrderDate, 0, 10) == "0000-00-00" ? "" : $sOrderDate;
    $sScanDate = substr((string)$aLink["scanned_at"], 0, 16);
    $sScanDate = substr($sScanDate, 0, 10) == "0000-00-00" ? "" : $sScanDate;
    $sReturnDate = substr((string)($aLink["returned_at"] ?? ""), 0, 10);
    $sReturnDate = substr($sReturnDate, 0, 10) == "0000-00-00" ? "" : $sReturnDate;
    $sInvoiceDate = substr((string)($aLink["invoice"] ?? ""), 0, 16);
    $sInvoiceDate = substr($sInvoiceDate, 0, 10) == "0000-00-00" ? "" : $sInvoiceDate;
    $sOrderDetailDate = substr($sOrderDate, 0, 10);
    $sReturnDetailDate = substr($sReturnDate, 0, 10);
    $sInvoiceDetailDate = substr($sInvoiceDate, 0, 10);
    $sScanDetailDates = (string)($aLink["scan_dates"] ?? "");
    $sOrderDateDisplay = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", htmlspecialchars($sOrderDate, ENT_QUOTES, "UTF-8"));
    $sScanDateDisplay = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", htmlspecialchars($sScanDate, ENT_QUOTES, "UTF-8"));
    $sFilmLabel = formatFilmOptionLabel($aLink);
    $sOrderLabel = formatOrderOptionLabel($aLink);
    $sPrice = $aLink["price_vat"] === null ? "" : number_format((float)$aLink["price_vat"], 2, ".", "");

?>
      <tr data-order-id="<?php echo htmlspecialchars((string)($aLink["lab_order_id"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" data-order-no="<?php echo htmlspecialchars((string)($aLink["order_no"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" data-lab="<?php echo htmlspecialchars((string)($aLink["lab"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" data-bag-no="<?php echo htmlspecialchars((string)($aLink["bag_no"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" data-price="<?php echo htmlspecialchars($sPrice, ENT_QUOTES, "UTF-8"); ?>" data-currency="<?php echo htmlspecialchars((string)($aLink["currency"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" data-order-date="<?php echo htmlspecialchars($sOrderDetailDate, ENT_QUOTES, "UTF-8"); ?>" data-return-date="<?php echo htmlspecialchars($sReturnDetailDate, ENT_QUOTES, "UTF-8"); ?>" data-invoice-date="<?php echo htmlspecialchars($sInvoiceDetailDate, ENT_QUOTES, "UTF-8"); ?>" data-film-scan-dates="<?php echo htmlspecialchars((string)($aLink["film_scan_dates"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" data-lab-scan-dates="<?php echo htmlspecialchars($sScanDetailDates, ENT_QUOTES, "UTF-8"); ?>">
        <td style="text-align: right;"><?php echo htmlspecialchars((string)$aLink["archive_no"], ENT_QUOTES, "UTF-8"); ?></td>
        <td><?php echo htmlspecialchars((string)$aLink["folder_name"], ENT_QUOTES, "UTF-8"); ?></td>
        <td><?php echo htmlspecialchars((string)($aLink["bag_no"] ?? ""), ENT_QUOTES, "UTF-8"); ?></td>
        <td><?php echo htmlspecialchars((string)($aLink["order_no"] ?? ""), ENT_QUOTES, "UTF-8"); ?></td>
        <td><?php echo $sOrderDateDisplay; ?></td>
        <td><?php echo $sScanDateDisplay; ?></td>
        <td><?php echo htmlspecialchars($sReturnDate, ENT_QUOTES, "UTF-8"); ?></td>
        <td><?php echo $aLink["lab_order_id"] === null ? "No" : (((int)$aLink["can_unassign"] == 1) ? "Yes <button type=\"button\" class=\"button-link js-confirm-unassign\" data-confirm-action=\"" . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]) . "\" data-unassign-id=\"" . (int)$aLink["film_id"] . "\" data-film-roll=\"" . $sFilmLabel . "\" data-lab-bag=\"" . $sOrderLabel . "\">Unassign</button>" : "Yes"); ?></td>
      </tr>
<?php

}

?>
    </tbody>
  </table>
  <div class="confirm-dialog" id="film-unassign-confirm-dialog" hidden>
    <form class="confirm-dialog-box" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="film_csrf_token" value="<?php echo htmlspecialchars(getCsrfToken("film_csrf_token"), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
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
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js"></script>
</body>
</html>
