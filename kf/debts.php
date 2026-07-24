<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$blCanEdit = isFullAccessAllowed($aAllowedIps, "kf");
requireViewAccess($aAllowedIps, "kf", "kf_csrf_token", true);


handleSettingsPost();
$aSettings = getSettings();
$blUseEuropeanAmountFormat = (int)$aSettings["use_european_amount_format"] == 1;
$sDisplayCurrency = normalizeCurrency($aSettings["display_currency"]);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sAction = getPostedTrimmedValue("action");
    $blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    requireFullAccess($aAllowedIps, "kf", "kf_csrf_token", $blJsonResponse);
    requireNamedCsrfToken("kf_csrf_token", $blJsonResponse);
    if ($sAction == "suggest_subjects") {
        try {
            sendJsonAndExit(array("success" => true, "subjects" => fetchSubjectSuggestions($oPdo, getPostedTrimmedValue("term"), 12)));
        } catch (Exception $oException) {
            error_log((string)$oException);
            sendJsonAndExit(array("success" => false, "message" => "Subjects could not be loaded."), 500);
        }
    } elseif ($sAction == "save_debt") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        $iSubjectId = (int)getPostedTrimmedValue("ex_subjects_id", "0");
        $sNote = getPostedTrimmedValue("note");
        $sMovementDate = getPostedTrimmedValue("movement_date");
        $sNormalizedMovementDate = normalizeInputDate($sMovementDate);
        $fAmount = parseAmount(getPostedTrimmedValue("amount"));
        $sCurrency = getPostedCurrency();
        $sMovementNote = getPostedTrimmedValue("movement_note");
        if ($iSubjectId < 1 || ($iId < 1 && ($sMovementDate == "" || $fAmount === null))) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt could not be saved. Subject, movement date, and amount are required."), 400);
            }
            redirect(getCurrentScriptName());
        }
        if ($iId < 1 && $sNormalizedMovementDate === false) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt could not be saved. Movement date must use YYYY-MM-DD."), 400);
            }
            redirect(getCurrentScriptName());
        }
        $sMovementDate = $sNormalizedMovementDate;
        if ($iId < 1 && !isCurrencyAvailable($oPdo, $sCurrency)) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt could not be saved. Currency is not available."), 400);
            }
            redirect(getCurrentScriptName());
        }
        if ($iSubjectId > 0 && !fetchSubjectNameRow($oPdo, $iSubjectId)) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt could not be saved. Subject was not found."), 404);
            }
            redirect(getCurrentScriptName());
        }
        try {
            if ($iId > 0) {
                $oStatement = $oPdo->prepare("UPDATE kf_debts SET ex_subjects_id = :ex_subjects_id, note = :note WHERE id = :id");
                $oStatement->execute(array(
                    "ex_subjects_id" => $iSubjectId > 0 ? $iSubjectId : null,
                    "note" => $sNote != "" ? $sNote : null,
                    "id" => $iId
                ));
                if ($blJsonResponse) {
                    $aDebtRows = fetchDebtAdminRows($oPdo, $iId);
                    if (!$aDebtRows) {
                        sendJsonAndExit(array("success" => false, "message" => "Debt was not found."), 404);
                    }
                    sendJsonAndExit(array("success" => true, "debt_id" => $iId, "row_html" => renderDebtAdminRow($aDebtRows[0], $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency), "rows_html" => renderDebtAdminRows(fetchDebtAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
                }
            } else {
                $oPdo->beginTransaction();
                $oStatement = $oPdo->prepare("INSERT INTO kf_debts (ex_subjects_id, note) VALUES (:ex_subjects_id, :note)");
                $oStatement->execute(array(
                    "ex_subjects_id" => $iSubjectId > 0 ? $iSubjectId : null,
                    "note" => $sNote != "" ? $sNote : null
                ));
                $iId = (int)$oPdo->lastInsertId();
                $oStatement = $oPdo->prepare("INSERT INTO kf_debt_movements (debt_id, movement_date, amount, currency, note) VALUES (:debt_id, :movement_date, :amount, :currency, :note)");
                $oStatement->execute(array(
                    "debt_id" => $iId,
                    "movement_date" => $sMovementDate,
                    "amount" => $fAmount,
                    "currency" => $sCurrency,
                    "note" => $sMovementNote != "" ? $sMovementNote : null
                ));
                $oPdo->commit();
                if ($blJsonResponse) {
                    $aDebtRows = fetchDebtAdminRows($oPdo, $iId);
                    if (!$aDebtRows) {
                        sendJsonAndExit(array("success" => false, "message" => "Debt was not found."), 404);
                    }
                    sendJsonAndExit(array("success" => true, "debt_id" => $iId, "row_html" => renderDebtAdminRow($aDebtRows[0], $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency), "rows_html" => renderDebtAdminRows(fetchDebtAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
                }
            }
        } catch (Exception $oException) {
            error_log((string)$oException);
            if ($oPdo->inTransaction()) {
                $oPdo->rollBack();
            }
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt could not be saved."), 500);
            }
            send500AndExit("Database error: " . $oException->getMessage());
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "save_debt_movement") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        $iDebtId = (int)getPostedTrimmedValue("debt_id", "0");
        $sMovementDate = getPostedTrimmedValue("movement_date");
        $sNormalizedMovementDate = normalizeInputDate($sMovementDate);
        $fAmount = parseAmount(getPostedTrimmedValue("amount"));
        $sCurrency = getPostedCurrency();
        $sNote = getPostedTrimmedValue("note");
        if ($iDebtId < 1 || $sMovementDate == "" || $fAmount === null) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt movement could not be saved. Debt, date, and amount are required."), 400);
            }
            redirect(getCurrentScriptName());
        }
        if ($sNormalizedMovementDate === false) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt movement could not be saved. Date must use YYYY-MM-DD."), 400);
            }
            redirect(getCurrentScriptName());
        }
        $sMovementDate = $sNormalizedMovementDate;
        if (!isCurrencyAvailable($oPdo, $sCurrency)) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt movement could not be saved. Currency is not available."), 400);
            }
            redirect(getCurrentScriptName());
        }
        try {
            $oStatement = $oPdo->prepare("SELECT id FROM kf_debts WHERE id = :id");
            $oStatement->execute(array("id" => $iDebtId));
            if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
                if ($blJsonResponse) {
                    sendJsonAndExit(array("success" => false, "message" => "Debt was not found."), 404);
                }
                redirect(getCurrentScriptName());
            }
            if ($iId > 0) {
                $oStatement = $oPdo->prepare("SELECT id FROM kf_debt_movements WHERE id = :id AND debt_id = :debt_id");
                $oStatement->execute(array(
                    "id" => $iId,
                    "debt_id" => $iDebtId
                ));
                if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
                    if ($blJsonResponse) {
                        sendJsonAndExit(array("success" => false, "message" => "Debt movement was not found."), 404);
                    }
                    redirect(getCurrentScriptName());
                }
                $oStatement = $oPdo->prepare("UPDATE kf_debt_movements SET movement_date = :movement_date, amount = :amount, currency = :currency, note = :note WHERE id = :id AND debt_id = :debt_id");
                $oStatement->execute(array(
                    "movement_date" => $sMovementDate,
                    "amount" => $fAmount,
                    "currency" => $sCurrency,
                    "note" => $sNote != "" ? $sNote : null,
                    "id" => $iId,
                    "debt_id" => $iDebtId
                ));
            } else {
                $oStatement = $oPdo->prepare("INSERT INTO kf_debt_movements (debt_id, movement_date, amount, currency, note) VALUES (:debt_id, :movement_date, :amount, :currency, :note)");
                $oStatement->execute(array(
                    "debt_id" => $iDebtId,
                    "movement_date" => $sMovementDate,
                    "amount" => $fAmount,
                    "currency" => $sCurrency,
                    "note" => $sNote != "" ? $sNote : null
                ));
            }
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => true, "debt_id" => $iDebtId, "rows_html" => renderDebtAdminRows(fetchDebtAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
            }
        } catch (Exception $oException) {
            error_log((string)$oException);
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The debt movement could not be saved."), 500);
            }
            send500AndExit("Database error: " . $oException->getMessage());
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "delete_debt_movement") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("DELETE FROM kf_debt_movements WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
        }
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true, "debt_movement_id" => $iId, "debt_movement_deleted" => true, "rows_html" => renderDebtAdminRows(fetchDebtAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "delete_debt") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("DELETE FROM kf_debts WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
        }
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true, "debt_id" => $iId, "debt_deleted" => true, "rows_html" => renderDebtAdminRows(fetchDebtAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
        }
        redirect(getCurrentScriptName());
    }
}


$aRows = fetchDebtAdminRows($oPdo);


$sToolbarHtml = "";
if ($blCanEdit) {
    $sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-debt\">New</button>\n";
}


$sTitle = getPageTitleText("Debts", $aAllowedIps);
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
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("kf_csrf_token")); ?>">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="debts-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

echo "    <button type=\"button\" class=\"button-link js-index-settings-open\">Settings</button>\n",
    $sToolbarHtml,
    "  </p>\n";

?>
  <table id="debts-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>" data-currencies="<?php echo htmlspecialchars(getCurrencyOptionsJson($oPdo, getDefaultCurrency()), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
    <thead>
      <tr>
        <th>Subject</th>
        <th class="numeric">Amount</th>
        <th class="debt-movements-heading">Movements</th>
        <th>Account Number</th>
        <th>E-mail</th>
        <th>Phone</th>
        <th>Note</th>
<?php

if ($blCanEdit) {
    echo "        <th class=\"admin-action-column\"></th>\n";
}

echo "      </tr>\n",
    "    </thead>\n",
    "    <tbody>\n";

echo renderDebtAdminRows($aRows, $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency),
    "    </tbody>\n",
    "  </table>\n";

?>
<?php echo renderSettingsModal($aSettings); ?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
<?php echo renderEmojiData(); ?>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
