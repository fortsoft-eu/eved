<?php

include "main.php";


$blCanEdit = isFullAccessAllowed($aAllowedIps, "kf");
requireViewAccess($aAllowedIps, "kf", "kf_csrf_token");


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireFullAccess($aAllowedIps, "kf", "kf_csrf_token");
    requireNamedCsrfToken("kf_csrf_token");
    $sAction = getPostedTrimmedValue("action");
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
            setMessage("The transaction could not be saved. Check the date, type, and amount.", "error");
            redirect(getCurrentScriptName());
        }
        $fSignedAmount = $aType["type_kind"] == "expense" ? -abs($fAmount) : abs($fAmount);
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("UPDATE kf_fin_trans SET transaction_date = :transaction_date, finance_type_id = :finance_type_id, amount = :amount, counterparty = :counterparty, note = :note WHERE id = :id");
            $oStatement->execute(array(
                "transaction_date" => $sDate,
                "finance_type_id" => $iFinanceTypeId,
                "amount" => $fSignedAmount,
                "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                "note" => $sNote != "" ? $sNote : null,
                "id" => $iId
            ));
            setMessage("Transaction updated.");
        } else {
            $oStatement = $oPdo->prepare("INSERT INTO kf_fin_trans (transaction_date, finance_type_id, amount, counterparty, note) VALUES (:transaction_date, :finance_type_id, :amount, :counterparty, :note)");
            $oStatement->execute(array(
                "transaction_date" => $sDate,
                "finance_type_id" => $iFinanceTypeId,
                "amount" => $fSignedAmount,
                "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                "note" => $sNote != "" ? $sNote : null
            ));
            setMessage("Transaction added.");
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "delete_transaction") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("DELETE FROM kf_fin_trans WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
            setMessage("Transaction deleted.");
        }
        redirect(getCurrentScriptName());
    }
}


$aRows = array();
$oStatement = $oPdo->query("SELECT t.id, t.transaction_date, t.amount, t.counterparty, t.note, ft.id AS finance_type_id, ft.name AS type_name, ft.type_kind FROM kf_fin_trans t JOIN kf_fin_types ft ON ft.id = t.finance_type_id ORDER BY t.transaction_date DESC, t.id DESC");
while ($aRow = $oStatement->fetch()) {
    $aRows[] = $aRow;
}


$sToolbarHtml = "";
if ($blCanEdit) {
    $sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-transaction\" data-modal-target=\"transaction-modal\" data-modal-title=\"New Transaction\" data-field-id=\"\" data-field-transaction_date=\"" . html(date("Y-m-d")) . "\" data-field-finance_type_id=\"\" data-field-amount=\"\" data-field-counterparty=\"\" data-field-note=\"\">New</button>\n";
}


$sTitle = getPageTitle("Transactions");
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
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="transactions-table" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

echo $sToolbarHtml,
    "  </p>\n";
renderMessage();

?>
  <table id="transactions-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Date</th>
        <th>Type</th>
        <th class="numeric">Amount</th>
        <th>Counterparty</th>
        <th>Note</th>
<?php

if ($blCanEdit) {
    echo "        <th></th>\n";
}

echo "      </tr>\n",
    "    </thead>\n",
    "    <tbody>\n";

foreach ($aRows as $aRow) {
    $sAmountClass = $aRow["amount"] < 0 ? "amount-negative" : ($aRow["amount"] > 0 ? "amount-positive" : "amount-zero");
    $sActionCell = "";
    if ($blCanEdit) {
        $sActionCell = "        <td class=\"nowrap\"><button type=\"button\" class=\"button-link\" data-modal-target=\"transaction-modal\" data-modal-title=\"Edit Transaction\" data-field-id=\"" . (int)$aRow["id"] . "\" data-field-transaction_date=\"" . html(formatDate($aRow["transaction_date"])) . "\" data-field-finance_type_id=\"" . (int)$aRow["finance_type_id"] . "\" data-field-amount=\"" . html(formatAmount(abs($aRow["amount"]))) . "\" data-field-counterparty=\"" . html($aRow["counterparty"]) . "\" data-field-note=\"" . html($aRow["note"]) . "\">Edit</button></td>\n";
    }
    echo "      <tr>\n",
        "        <td class=\"nowrap\">" . html(formatDate($aRow["transaction_date"])) . "</td>\n",
        "        <td>" . html($aRow["type_name"]) . "</td>\n",
        "        <td class=\"numeric " . $sAmountClass . "\">" . html(formatAmount($aRow["amount"])) . "</td>\n",
        "        <td>" . htmlValue($aRow["counterparty"], "&mdash;") . "</td>\n",
        "        <td>" . htmlValue($aRow["note"], "&mdash;") . "</td>\n",
        $sActionCell,
        "      </tr>\n";
}

if (!$aRows) {
    echo "      <tr><td colspan=\"" . ($blCanEdit ? 6 : 5) . "\">No transactions found.</td></tr>\n";
}

echo "    </tbody>\n",
    "  </table>\n";

if ($blCanEdit) {

?>
  <div id="transaction-modal" class="confirm-dialog" hidden>
    <form method="post" class="confirm-dialog-box edit-dialog">
      <div class="confirm-dialog-header"><strong data-modal-heading>Transaction</strong><button type="button" class="confirm-dialog-close" data-modal-close aria-label="Close">&times;</button></div>
        <input type="hidden" name="kf_csrf_token" value="<?php echo html(getCsrfToken("kf_csrf_token")); ?>">
        <input type="hidden" name="action" value="save_transaction">
        <input type="hidden" name="id" value="">
        <label for="transaction-date">Date</label>
        <input type="date" id="transaction-date" name="transaction_date" required>
        <label for="finance-type-id">Type</label>
        <select id="finance-type-id" name="finance_type_id" required>
<?php

echo getFinanceTypeOptionsHtml();

?>
        </select>
        <label for="amount">Amount</label>
        <input type="text" id="amount" name="amount" required>
        <label for="counterparty">Counterparty</label>
        <input type="text" id="counterparty" name="counterparty">
        <label for="note">Note</label>
        <input type="text" id="note" name="note">
        <div class="confirm-dialog-actions">
          <button type="submit" class="confirm-dialog-button">Save</button>
          <button type="submit" name="action" value="delete_transaction" class="confirm-dialog-button">Delete</button>
          <button type="button" class="confirm-dialog-button" data-modal-close>Cancel</button>
        </div>
    </form>
  </div>
<?php

}

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
