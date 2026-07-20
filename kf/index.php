<?php

include "main.php";

requireViewAccess($aAllowedIps, "kf", "kf_csrf_token");

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aTypes = getFinanceTypes(false);
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

$aOverviewColumns = array();
foreach ($aTypes as $aType) {
    $aOverviewColumns[] = array(
        "type" => "type",
        "id" => (int)$aType["id"],
        "title" => (string)$aType["name"]
    );
}
foreach ($aGroups as $aGroup) {
    $aOverviewColumns[] = array(
        "type" => "group",
        "id" => (int)$aGroup["id"],
        "title" => "Group: " . (string)$aGroup["name"]
    );
}
$aOverviewColumns[] = array("type" => "summary", "key" => "income", "title" => "Income Total");
$aOverviewColumns[] = array("type" => "summary", "key" => "expense", "title" => "Expense Total");
$aOverviewColumns[] = array("type" => "summary", "key" => "net", "title" => "Net Total");

$aOverviewColumnGroups = array();
$aOverviewColumnGroup = array();
foreach ($aOverviewColumns as $aOverviewColumn) {
    if (count($aOverviewColumnGroup) >= 10) {
        $aOverviewColumnGroups[] = $aOverviewColumnGroup;
        $aOverviewColumnGroup = array();
    }
    $aOverviewColumnGroup[] = $aOverviewColumn;
}
if ($aOverviewColumnGroup) {
    $aOverviewColumnGroups[] = $aOverviewColumnGroup;
}

$sTitle = getPageTitle("Income and Expenses");
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("kf_csrf_token")); ?>">
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="kf-monthly-overview-tables" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
<?php renderMessage(); ?>
  <div id="kf-monthly-overview-tables">
<?php

foreach ($aOverviewColumnGroups as $iOverviewColumnGroupIndex => $aOverviewColumnGroup) {
    echo "  <table id=\"kf-monthly-overview-table-" . ($iOverviewColumnGroupIndex + 1) . "\" class=\"table-filter-target kf-monthly-overview-table\">\n"
        . "    <thead>\n"
        . "      <tr>\n"
        . "        <th>Month</th>\n";
    foreach ($aOverviewColumnGroup as $aOverviewColumn) {
        echo "        <th class=\"numeric\">" . html($aOverviewColumn["title"]) . "</th>\n";
    }
    echo "      </tr>\n"
        . "    </thead>\n"
        . "    <tbody>\n";

    foreach ($aMonths as $sMonth) {
        $fIncome = isset($aSummaryTotals[$sMonth]["income"]) ? $aSummaryTotals[$sMonth]["income"] : 0;
        $fExpense = isset($aSummaryTotals[$sMonth]["expense"]) ? $aSummaryTotals[$sMonth]["expense"] : 0;
        $fNet = $fIncome + $fExpense;
        echo "      <tr data-month=\"" . html($sMonth) . "\">\n"
            . "        <td class=\"nowrap\">" . html(monthLabel($sMonth)) . "</td>\n";
        foreach ($aOverviewColumnGroup as $aOverviewColumn) {
            if ($aOverviewColumn["type"] == "type") {
                $fAmount = isset($aTypeTotals[$sMonth][(int)$aOverviewColumn["id"]]) ? $aTypeTotals[$sMonth][(int)$aOverviewColumn["id"]] : 0;
            } elseif ($aOverviewColumn["type"] == "group") {
                $fAmount = isset($aGroupTotals[$sMonth][(int)$aOverviewColumn["id"]]) ? $aGroupTotals[$sMonth][(int)$aOverviewColumn["id"]] : 0;
            } elseif ($aOverviewColumn["key"] == "income") {
                $fAmount = $fIncome;
            } elseif ($aOverviewColumn["key"] == "expense") {
                $fAmount = $fExpense;
            } else {
                $fAmount = $fNet;
            }
            $sAmountClass = $fAmount < 0 ? "kf-amount-negative" : ($fAmount > 0 ? "kf-amount-positive" : "kf-amount-zero");
            echo "        <td class=\"numeric " . $sAmountClass . "\">" . html(formatAmount($fAmount)) . "</td>\n";
        }
        echo "      </tr>\n";
    }

    if (!$aMonths) {
        echo "      <tr><td colspan=\"" . (count($aOverviewColumnGroup) + 1) . "\">No transactions found.</td></tr>\n";
    }

    echo "    </tbody>\n"
        . "  </table>\n";
}

?>
  </div>
<?php

echo "  <button type=\"button\" class=\"filter-focus-button js-filter-focus\" data-filter-input=\"table-filter\" title=\"Focus filter\" aria-label=\"Focus filter\">" . $sFilterFocusEmoji . " Filter</button>\n"
    . "  <script type=\"text/javascript\" src=\"" . html($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n"
    . "</body>\n"
    . "</html>\n";

