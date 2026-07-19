<?php

include "main.php";

requireExFullAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    kfRequireCsrfToken();
    $sAction = kfPostedValue("action");

    if ($sAction == "save_transaction") {
        $iId = (int)kfPostedValue("id", "0");
        $sDate = kfPostedValue("transaction_date");
        $iFinanceTypeId = (int)kfPostedValue("finance_type_id", "0");
        $fAmount = kfParseAmount(kfPostedValue("amount"));
        $sCounterparty = kfPostedValue("counterparty");
        $sNote = kfPostedValue("note");

        $oStatement = $oPdo->prepare("SELECT id, type_kind FROM kf_fin_types WHERE id = :id AND type_kind IN ('income', 'expense')");
        $oStatement->execute(array("id" => $iFinanceTypeId));
        $aType = $oStatement->fetch();

        if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDate) || !$aType || $fAmount === null || $fAmount <= 0) {
            kfSetMessage("The transaction could not be saved. Check the date, type, and amount.", "error");
            kfRedirect("transactions.php");
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
            kfSetMessage("Transaction updated.");
        } else {
            $oStatement = $oPdo->prepare("INSERT INTO kf_fin_trans (transaction_date, finance_type_id, amount, counterparty, note) VALUES (:transaction_date, :finance_type_id, :amount, :counterparty, :note)");
            $oStatement->execute(array(
                "transaction_date" => $sDate,
                "finance_type_id" => $iFinanceTypeId,
                "amount" => $fSignedAmount,
                "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                "note" => $sNote != "" ? $sNote : null
            ));
            kfSetMessage("Transaction added.");
        }
        kfRedirect("transactions.php");
    } elseif ($sAction == "delete_transaction") {
        $iId = (int)kfPostedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("DELETE FROM kf_fin_trans WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
            kfSetMessage("Transaction deleted.");
        }
        kfRedirect("transactions.php");
    }
}

$aRows = array();
$oStatement = $oPdo->query("SELECT t.id, t.transaction_date, t.amount, t.counterparty, t.note, ft.id AS finance_type_id, ft.name AS type_name, ft.type_kind FROM kf_fin_trans t JOIN kf_fin_types ft ON ft.id = t.finance_type_id ORDER BY t.transaction_date DESC, t.id DESC");
while ($aRow = $oStatement->fetch()) {
    $aRows[] = $aRow;
}

$sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-transaction\" data-modal-target=\"transaction-modal\" data-modal-title=\"New Transaction\" data-field-id=\"\" data-field-transaction_date=\"" . kfHtml(date("Y-m-d")) . "\" data-field-finance_type_id=\"\" data-field-amount=\"\" data-field-counterparty=\"\" data-field-note=\"\">New</button>\n";

$sTitle = kfGetPageTitle("Transactions");
$iTime = kfSendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo kfHtml(kfGetCsrfToken()); ?>">
  <title><?php echo kfHtml($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo kfHtml($sBaseUrl . "../ex/css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/../ex/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
  <link href="<?php echo kfHtml($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php kfRenderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="kf-transactions-table" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php echo $sToolbarHtml; ?>
  </p>
<?php kfRenderMessage(); ?>
  <table id="kf-transactions-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Date</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Counterparty</th>
        <th>Note</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aRows as $aRow) {
    $sAmountClass = $aRow["amount"] < 0 ? "kf-amount-negative" : ($aRow["amount"] > 0 ? "kf-amount-positive" : "kf-amount-zero");
    echo "      <tr>\n"
        . "        <td class=\"nowrap\">" . kfHtml(kfFormatDate($aRow["transaction_date"])) . "</td>\n"
        . "        <td>" . kfHtml($aRow["type_name"]) . "</td>\n"
        . "        <td class=\"numeric " . $sAmountClass . "\">" . kfHtml(kfFormatAmount($aRow["amount"])) . "</td>\n"
        . "        <td>" . kfHtmlValue($aRow["counterparty"]) . "</td>\n"
        . "        <td>" . kfHtmlValue($aRow["note"]) . "</td>\n"
        . "        <td class=\"nowrap\"><button type=\"button\" class=\"button-link\" data-modal-target=\"transaction-modal\" data-modal-title=\"Edit Transaction\" data-field-id=\"" . (int)$aRow["id"] . "\" data-field-transaction_date=\"" . kfHtml(kfFormatDate($aRow["transaction_date"])) . "\" data-field-finance_type_id=\"" . (int)$aRow["finance_type_id"] . "\" data-field-amount=\"" . kfHtml(kfFormatAmount(abs($aRow["amount"]))) . "\" data-field-counterparty=\"" . kfHtml($aRow["counterparty"]) . "\" data-field-note=\"" . kfHtml($aRow["note"]) . "\">Edit</button></td>\n"
        . "      </tr>\n";
}

if (!$aRows) {
    echo "      <tr><td colspan=\"6\">No transactions found.</td></tr>\n";
}

?>
    </tbody>
  </table>

  <div id="transaction-modal" class="modal-dialog" hidden>
    <div class="modal-box">
      <div class="modal-header"><strong data-modal-heading>Transaction</strong><button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button></div>
      <form method="post" class="modal-form">
        <input type="hidden" name="kf_csrf_token" value="<?php echo kfHtml(kfGetCsrfToken()); ?>">
        <input type="hidden" name="action" value="save_transaction">
        <input type="hidden" name="id" value="">
        <label for="transaction-date">Date</label>
        <input type="date" id="transaction-date" name="transaction_date" required>
        <label for="finance-type-id">Type</label>
        <select id="finance-type-id" name="finance_type_id" required>
<?php echo kfGetFinanceTypeOptionsHtml(); ?>
        </select>
        <label for="amount">Amount</label>
        <input type="text" id="amount" name="amount" required>
        <label for="counterparty">Counterparty</label>
        <input type="text" id="counterparty" name="counterparty">
        <label for="note">Note</label>
        <textarea id="note" name="note"></textarea>
        <div class="modal-actions">
          <button type="submit">Save</button>
          <button type="submit" name="action" value="delete_transaction">Delete</button>
          <button type="button" data-modal-close>Cancel</button>
        </div>
      </form>
    </div>
  </div>
<?php

echo "  <button type=\"button\" class=\"filter-focus-button js-filter-focus\" data-filter-input=\"table-filter\" title=\"Focus filter\" aria-label=\"Focus filter\">" . $sFilterFocusEmoji . " Filter</button>\n"
    . "  <script type=\"text/javascript\" src=\"" . kfHtml($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n"
    . "</body>\n"
    . "</html>\n";

