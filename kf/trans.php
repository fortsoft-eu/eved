<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$blCanEdit = isFullAccessAllowed("kf");
requireViewAccess("kf", "csrf_token", true);


handleSettingsPost();
$aSettings = getSettings();
$blUseEuropeanAmountFormat = (int)$aSettings["use_european_amount_format"] == 1;
$sDisplayCurrency = normalizeCurrency($aSettings["display_currency"]);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sAction = getPostedTrimmedValue("action");
    $blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    requireFullAccess("kf", "csrf_token", $blJsonResponse);
    requireNamedCsrfToken("csrf_token", $blJsonResponse);
    if ($sAction == "save_transaction") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        $sDate = normalizeInputDate(getPostedTrimmedValue("transaction_date"));
        $iFinanceTypeId = (int)getPostedTrimmedValue("finance_type_id", "0");
        $fAmount = parseAmount(getPostedTrimmedValue("amount"));
        $sCurrency = getPostedCurrency();
        $sCounterparty = getPostedTrimmedValue("counterparty");
        $sNote = getPostedTrimmedValue("note");
        $oStatement = $oPdo->prepare("SELECT id, type_kind FROM kf_fin_types WHERE id = :id AND type_kind IN ('income', 'expense')");
        $oStatement->execute(array("id" => $iFinanceTypeId));
        $aType = $oStatement->fetch();
        if ($sDate === false || $sDate == "" || !$aType || $fAmount === null || $fAmount <= 0 || !isCurrencyAvailable($oPdo, $sCurrency)) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The transaction could not be saved. Check the date, type, and amount."), 400);
            }
            redirect(getCurrentScriptName());
        }
        $aAdditionalTransactions = validateAdditionalTransactions($oPdo, getPostedAdditionalTransactions(), $iFinanceTypeId, $aType["type_kind"], $fAmount, $sCurrency, $sDate);
        if ($aAdditionalTransactions === false) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "Subtracted transactions could not be saved."), 400);
            }
            redirect(getCurrentScriptName());
        }
        $fSignedAmount = $aType["type_kind"] == "expense" ? -abs($fAmount - getAdditionalTransactionsTotal($aAdditionalTransactions)) : abs($fAmount - getAdditionalTransactionsTotal($aAdditionalTransactions));
        try {
            $oPdo->beginTransaction();
            if ($iId > 0) {
                $oStatement = $oPdo->prepare("SELECT id FROM kf_fin_transactions WHERE id = :id FOR UPDATE");
                $oStatement->execute(array("id" => $iId));
                if (!$oStatement->fetch()) {
                    $oPdo->rollBack();
                    if ($blJsonResponse) {
                        sendJsonAndExit(array("success" => false, "message" => "Transaction was not found."), 404);
                    }
                    redirect(getCurrentScriptName());
                }
                $oStatement = $oPdo->prepare("UPDATE kf_fin_transactions SET transaction_date = :transaction_date, finance_type_id = :finance_type_id, amount = :amount, currency = :currency, counterparty = :counterparty, note = :note WHERE id = :id");
                $oStatement->execute(array(
                    "transaction_date" => $sDate,
                    "finance_type_id" => $iFinanceTypeId,
                    "amount" => $fSignedAmount,
                    "currency" => $sCurrency,
                    "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                    "note" => $sNote != "" ? $sNote : null,
                    "id" => $iId
                ));
            } else {
                $oStatement = $oPdo->prepare("INSERT INTO kf_fin_transactions (transaction_date, finance_type_id, amount, currency, counterparty, note) VALUES (:transaction_date, :finance_type_id, :amount, :currency, :counterparty, :note)");
                $oStatement->execute(array(
                    "transaction_date" => $sDate,
                    "finance_type_id" => $iFinanceTypeId,
                    "amount" => $fSignedAmount,
                    "currency" => $sCurrency,
                    "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                    "note" => $sNote != "" ? $sNote : null
                ));
                $iId = (int)$oPdo->lastInsertId();
            }
            insertAdditionalTransactions($oPdo, $aAdditionalTransactions, $aType["type_kind"], $sDate, $sCounterparty, $sNote);
            $oPdo->commit();
        } catch (Exception $oException) {
            if ($oPdo->inTransaction()) {
                $oPdo->rollBack();
            }
            error_log((string)$oException);
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The transaction could not be saved."), 500);
            }
            redirect(getCurrentScriptName());
        }
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true, "transaction_id" => $iId, "rows_html" => renderTransactionAdminRows(fetchTransactionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "delete_transaction") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("DELETE FROM kf_fin_transactions WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
        }
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true, "transaction_id" => $iId, "transaction_deleted" => true, "rows_html" => renderTransactionAdminRows(fetchTransactionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
        }
        redirect(getCurrentScriptName());
    }
}


$aRows = fetchTransactionAdminRows($oPdo);
$aFinanceTypes = $blCanEdit ? getFinanceTypes(false) : array();


$sToolbarHtml = "";
if ($blCanEdit) {
    $sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-transaction\">New</button>\n";
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
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
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
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="transactions-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
<?php

echo $sToolbarHtml,
    "    <span class=\"table-record-count js-table-record-count\" data-table-count=\"transactions-table\" aria-live=\"polite\">" . count($aRows) . "</span>\n";

?>
  </p>
  <div id="transactions-data" data-display-currency="<?php echo html($sDisplayCurrency != "" ? $sDisplayCurrency : $sDefaultCurrency); ?>" data-finance-types="<?php echo html(json_encode($aFinanceTypes)); ?>" data-currencies="<?php echo html(json_encode(getCurrencyOptions($oPdo, $sDefaultCurrency))); ?>" hidden></div>
<?php

if (!$aRows) {
    echo "  <p>No records found.</p>\n";
} else {

?>
  <table id="transactions-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>" data-display-currency="<?php echo html($sDisplayCurrency != "" ? $sDisplayCurrency : $sDefaultCurrency); ?>" data-finance-types="<?php echo html(json_encode($aFinanceTypes)); ?>" data-currencies="<?php echo html(json_encode(getCurrencyOptions($oPdo, $sDefaultCurrency))); ?>">
    <thead>
      <tr>
        <th>Date</th>
        <th>Type</th>
        <th class="numeric">Amount</th>
        <th>Counterparty</th>
        <th>Note</th>
<?php

    if ($blCanEdit) {
        echo "        <th class=\"admin-action-column\"></th>\n";
    }
    echo "      </tr>\n",
        "    </thead>\n",
        "    <tbody>\n",
        renderTransactionAdminRows($aRows, $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency),
        "    </tbody>\n",
        "  </table>\n";
}
echo
    renderSettingsModal($aSettings),
    renderEmojiData();

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
