<?php

include "main.php";


$blCanEdit = isFullAccessAllowed($aAllowedIps, "kf");
requireViewAccess($aAllowedIps, "kf", "kf_csrf_token", true);


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


handleSettingsPost();
$aSettings = getSettings();
$blUseEuropeanAmountFormat = (int)$aSettings["use_european_amount_format"] == 1;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sAction = getPostedTrimmedValue("action");
    $blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    requireFullAccess($aAllowedIps, "kf", "kf_csrf_token", $blJsonResponse);
    requireNamedCsrfToken("kf_csrf_token", $blJsonResponse);
    if ($sAction == "save_transaction") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        $sDate = getPostedTrimmedValue("transaction_date");
        $iFinanceTypeId = (int)getPostedTrimmedValue("finance_type_id", "0");
        $fAmount = parseAmount(getPostedTrimmedValue("amount"));
        $sCounterparty = getPostedTrimmedValue("counterparty");
        $sNote = getPostedTrimmedValue("note");
        $oStatement = $oPdo->prepare("SELECT id, type_kind FROM kf_fin_types WHERE id = :id AND type_kind IN ('income', 'expense')");
        $oStatement->execute(array("id" => $iFinanceTypeId));
        $aType = $oStatement->fetch();
        if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDate) || !$aType || $fAmount === null || $fAmount <= 0) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The transaction could not be saved. Check the date, type, and amount."), 400);
            }
            redirect(getCurrentScriptName());
        }
        $fSignedAmount = $aType["type_kind"] == "expense" ? -abs($fAmount) : abs($fAmount);
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("UPDATE kf_fin_transactions SET transaction_date = :transaction_date, finance_type_id = :finance_type_id, amount = :amount, counterparty = :counterparty, note = :note WHERE id = :id");
            $oStatement->execute(array(
                "transaction_date" => $sDate,
                "finance_type_id" => $iFinanceTypeId,
                "amount" => $fSignedAmount,
                "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                "note" => $sNote != "" ? $sNote : null,
                "id" => $iId
            ));
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => true, "transaction_id" => $iId, "rows_html" => renderTransactionAdminRows(fetchTransactionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat)));
            }
        } else {
            $oStatement = $oPdo->prepare("INSERT INTO kf_fin_transactions (transaction_date, finance_type_id, amount, counterparty, note) VALUES (:transaction_date, :finance_type_id, :amount, :counterparty, :note)");
            $oStatement->execute(array(
                "transaction_date" => $sDate,
                "finance_type_id" => $iFinanceTypeId,
                "amount" => $fSignedAmount,
                "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                "note" => $sNote != "" ? $sNote : null
            ));
            if ($blJsonResponse) {
                $iId = (int)$oPdo->lastInsertId();
                sendJsonAndExit(array("success" => true, "transaction_id" => $iId, "rows_html" => renderTransactionAdminRows(fetchTransactionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat)));
            }
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "delete_transaction") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("DELETE FROM kf_fin_transactions WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
        }
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true, "transaction_id" => $iId, "transaction_deleted" => true, "rows_html" => renderTransactionAdminRows(fetchTransactionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat)));
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


$sTitle = getPageTitleText("Transactions", $aAllowedIps);
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
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="transactions-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

echo "    <button type=\"button\" class=\"button-link js-index-settings-open\">Settings</button>\n",
    $sToolbarHtml,
    "  </p>\n";

?>
  <table id="transactions-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>" data-finance-types="<?php echo htmlspecialchars(json_encode($aFinanceTypes), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
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
    "    <tbody>\n";

echo renderTransactionAdminRows($aRows, $blCanEdit, $blUseEuropeanAmountFormat),
    "    </tbody>\n",
    "  </table>\n";

?>
<?php echo renderSettingsModal($aSettings); ?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
