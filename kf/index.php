<?php

include "main.php";

requireExFullAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aTypes = kfGetFinanceTypes(false);
$aGroups = array();
$oStatement = $oPdo->query("SELECT id, name FROM kf_fin_types WHERE type_kind = 'group' ORDER BY name ASC, id ASC");
while ($aRow = $oStatement->fetch()) {
    $aGroups[] = $aRow;
}

$aMonths = array();
$oStatement = $oPdo->query("SELECT DISTINCT DATE_FORMAT(transaction_date, '%Y-%m') AS month_key FROM kf_fin_trans ORDER BY month_key ASC");
while ($aRow = $oStatement->fetch()) {
    $aMonths[] = $aRow["month_key"];
}

$aTypeTotals = array();
$oStatement = $oPdo->query("SELECT DATE_FORMAT(t.transaction_date, '%Y-%m') AS month_key, t.finance_type_id, SUM(t.amount) AS amount_sum FROM kf_fin_trans t GROUP BY month_key, t.finance_type_id");
while ($aRow = $oStatement->fetch()) {
    $aTypeTotals[$aRow["month_key"]][(int)$aRow["finance_type_id"]] = (float)$aRow["amount_sum"];
}

$aGroupTotals = array();
$oStatement = $oPdo->query("SELECT DATE_FORMAT(t.transaction_date, '%Y-%m') AS month_key, g.group_type_id, SUM(t.amount) AS amount_sum FROM kf_fin_trans t JOIN kf_fin_groups g ON g.member_type_id = t.finance_type_id GROUP BY month_key, g.group_type_id");
while ($aRow = $oStatement->fetch()) {
    $aGroupTotals[$aRow["month_key"]][(int)$aRow["group_type_id"]] = (float)$aRow["amount_sum"];
}

$aSummaryTotals = array();
$oStatement = $oPdo->query("SELECT DATE_FORMAT(t.transaction_date, '%Y-%m') AS month_key, ft.type_kind, SUM(t.amount) AS amount_sum FROM kf_fin_trans t JOIN kf_fin_types ft ON ft.id = t.finance_type_id GROUP BY month_key, ft.type_kind");
while ($aRow = $oStatement->fetch()) {
    $aSummaryTotals[$aRow["month_key"]][$aRow["type_kind"]] = (float)$aRow["amount_sum"];
}

$sTitle = kfGetPageTitle("Income and Expenses");
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
  <link href="<?php echo kfHtml($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php kfRenderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="kf-monthly-overview-table" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
<?php kfRenderMessage(); ?>
  <table id="kf-monthly-overview-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Month</th>
<?php

foreach ($aTypes as $aType) {
    echo "        <th class=\"numeric\">" . kfHtml($aType["name"]) . "</th>\n";
}
foreach ($aGroups as $aGroup) {
    echo "        <th class=\"numeric\">Group: " . kfHtml($aGroup["name"]) . "</th>\n";
}

?>
        <th class="numeric">Income Total</th>
        <th class="numeric">Expense Total</th>
        <th class="numeric">Net Total</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aMonths as $sMonth) {
    $fIncome = isset($aSummaryTotals[$sMonth]["income"]) ? $aSummaryTotals[$sMonth]["income"] : 0;
    $fExpense = isset($aSummaryTotals[$sMonth]["expense"]) ? $aSummaryTotals[$sMonth]["expense"] : 0;
    $fNet = $fIncome + $fExpense;
    echo "      <tr>\n"
        . "        <td class=\"nowrap\">" . kfHtml(kfMonthLabel($sMonth)) . "</td>\n";
    foreach ($aTypes as $aType) {
        $fAmount = isset($aTypeTotals[$sMonth][(int)$aType["id"]]) ? $aTypeTotals[$sMonth][(int)$aType["id"]] : 0;
        $sAmountClass = $fAmount < 0 ? "kf-amount-negative" : ($fAmount > 0 ? "kf-amount-positive" : "kf-amount-zero");
        echo "        <td class=\"numeric " . $sAmountClass . "\">" . kfHtml(kfFormatAmount($fAmount)) . "</td>\n";
    }
    foreach ($aGroups as $aGroup) {
        $fAmount = isset($aGroupTotals[$sMonth][(int)$aGroup["id"]]) ? $aGroupTotals[$sMonth][(int)$aGroup["id"]] : 0;
        $sAmountClass = $fAmount < 0 ? "kf-amount-negative" : ($fAmount > 0 ? "kf-amount-positive" : "kf-amount-zero");
        echo "        <td class=\"numeric " . $sAmountClass . "\">" . kfHtml(kfFormatAmount($fAmount)) . "</td>\n";
    }
    $sIncomeClass = $fIncome < 0 ? "kf-amount-negative" : ($fIncome > 0 ? "kf-amount-positive" : "kf-amount-zero");
    $sExpenseClass = $fExpense < 0 ? "kf-amount-negative" : ($fExpense > 0 ? "kf-amount-positive" : "kf-amount-zero");
    $sNetClass = $fNet < 0 ? "kf-amount-negative" : ($fNet > 0 ? "kf-amount-positive" : "kf-amount-zero");
    echo "        <td class=\"numeric " . $sIncomeClass . "\">" . kfHtml(kfFormatAmount($fIncome)) . "</td>\n"
        . "        <td class=\"numeric " . $sExpenseClass . "\">" . kfHtml(kfFormatAmount($fExpense)) . "</td>\n"
        . "        <td class=\"numeric " . $sNetClass . "\">" . kfHtml(kfFormatAmount($fNet)) . "</td>\n"
        . "      </tr>\n";
}

if (!$aMonths) {
    echo "      <tr><td colspan=\"" . (count($aTypes) + count($aGroups) + 4) . "\">No transactions found.</td></tr>\n";
}

?>
    </tbody>
  </table>
<?php

echo "  <button type=\"button\" class=\"filter-focus-button js-filter-focus\" data-filter-input=\"table-filter\" title=\"Focus filter\" aria-label=\"Focus filter\">" . $sFilterFocusEmoji . " Filter</button>\n"
    . "  <script type=\"text/javascript\" src=\"" . kfHtml($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n"
    . "</body>\n"
    . "</html>\n";

