<?php

include "main.php";


requireViewAccess($aAllowedIps, "kf", "kf_csrf_token");


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


handleSettingsPost();
$aSettings = getSettings();
$blUseEuropeanAmountFormat = (int)$aSettings["use_european_amount_format"] == 1;
$sDisplayCurrency = normalizeCurrency($aSettings["display_currency"]);


$aTypes = getFinanceTypes(false);
$aGroups = array();
$oStatement = $oPdo->query("SELECT id, name FROM kf_fin_types WHERE type_kind = 'group' ORDER BY name ASC, id ASC");
while ($aRow = $oStatement->fetch()) {
    $aGroups[] = $aRow;
}

$aMonths = array();
$aTypeTotals = array();
$aGroupTotals = array();
$aSummaryTotals = array();
$aTypeConversionFailures = array();
$aGroupConversionFailures = array();
$aSummaryConversionFailures = array();
$aMonthMap = array();
$aGroupMemberMap = array();
$oStatement = $oPdo->query("SELECT group_type_id, member_type_id FROM kf_fin_groups ORDER BY group_type_id ASC, member_type_id ASC");
while ($aRow = $oStatement->fetch()) {
    $iMemberTypeId = (int)$aRow["member_type_id"];
    if (!isset($aGroupMemberMap[$iMemberTypeId])) {
        $aGroupMemberMap[$iMemberTypeId] = array();
    }
    $aGroupMemberMap[$iMemberTypeId][] = (int)$aRow["group_type_id"];
}
$oStatement = $oPdo->query("SELECT t.transaction_date, DATE_FORMAT(t.transaction_date, '%Y-%m') AS month_key, t.finance_type_id, t.amount, t.currency, ft.type_kind FROM kf_fin_transactions t JOIN kf_fin_types ft ON ft.id = t.finance_type_id ORDER BY t.transaction_date ASC, t.id ASC");
while ($aRow = $oStatement->fetch()) {
    $sMonth = (string)$aRow["month_key"];
    $iFinanceTypeId = (int)$aRow["finance_type_id"];
    $sStoredCurrency = normalizeStoredCurrency($aRow["currency"]);
    $mDisplayAmount = convertCurrencyAmount($oPdo, $aRow["amount"], $aRow["currency"], $sDisplayCurrency, $aRow["transaction_date"]);
    $blConversionFailed = $sDisplayCurrency != "" && $sStoredCurrency != $sDisplayCurrency && $mDisplayAmount === null;
    $fAmount = $mDisplayAmount === null ? (float)$aRow["amount"] : (float)$mDisplayAmount;
    if (!isset($aMonthMap[$sMonth])) {
        $aMonths[] = $sMonth;
        $aMonthMap[$sMonth] = true;
    }
    if (!isset($aTypeTotals[$sMonth][$iFinanceTypeId])) {
        $aTypeTotals[$sMonth][$iFinanceTypeId] = 0.0;
    }
    $aTypeTotals[$sMonth][$iFinanceTypeId] += $fAmount;
    if ($blConversionFailed) {
        $aTypeConversionFailures[$sMonth][$iFinanceTypeId] = true;
    }
    if (isset($aGroupMemberMap[$iFinanceTypeId])) {
        foreach ($aGroupMemberMap[$iFinanceTypeId] as $iGroupTypeId) {
            if (!isset($aGroupTotals[$sMonth][$iGroupTypeId])) {
                $aGroupTotals[$sMonth][$iGroupTypeId] = 0.0;
            }
            $aGroupTotals[$sMonth][$iGroupTypeId] += $fAmount;
            if ($blConversionFailed) {
                $aGroupConversionFailures[$sMonth][$iGroupTypeId] = true;
            }
        }
    }
    if (!isset($aSummaryTotals[$sMonth][$aRow["type_kind"]])) {
        $aSummaryTotals[$sMonth][$aRow["type_kind"]] = 0.0;
    }
    $aSummaryTotals[$sMonth][$aRow["type_kind"]] += $fAmount;
    if ($blConversionFailed) {
        $aSummaryConversionFailures[$sMonth][$aRow["type_kind"]] = true;
    }
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


$sTitle = getPageTitleText("Income and Expenses", $aAllowedIps);
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
<?php

echo "  <p class=\"admin-controls\">";
renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="monthly-overview-tables" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

echo "    <button type=\"button\" class=\"button-link js-index-settings-open\">Settings</button>\n",
    " </p>\n",
    "  <div id=\"monthly-overview-tables\">\n";

foreach ($aOverviewColumnGroups as $iOverviewColumnGroupIndex => $aOverviewColumnGroup) {
    echo "  <table id=\"monthly-overview-table-" . ($iOverviewColumnGroupIndex + 1) . "\" class=\"table-filter-target monthly-overview-table" . getCondensedTableClass() . "\">\n",
        "    <thead>\n",
        "      <tr>\n",
        "        <th>Month</th>\n";
    foreach ($aOverviewColumnGroup as $aOverviewColumn) {
        echo "        <th class=\"numeric\">" . html($aOverviewColumn["title"]) . "</th>\n";
    }
    echo "      </tr>\n",
        "    </thead>\n",
        "    <tbody>\n";
    foreach ($aMonths as $sMonth) {
        $fIncome = isset($aSummaryTotals[$sMonth]["income"]) ? $aSummaryTotals[$sMonth]["income"] : 0;
        $fExpense = isset($aSummaryTotals[$sMonth]["expense"]) ? $aSummaryTotals[$sMonth]["expense"] : 0;
        $fNet = $fIncome + $fExpense;
        echo "      <tr data-month=\"" . html($sMonth) . "\">\n",
            "        <td class=\"nowrap\">" . html(monthLabel($sMonth)) . "</td>\n";
        foreach ($aOverviewColumnGroup as $aOverviewColumn) {
            $blConversionFailed = false;
            if ($aOverviewColumn["type"] == "type") {
                $fAmount = isset($aTypeTotals[$sMonth][(int)$aOverviewColumn["id"]]) ? $aTypeTotals[$sMonth][(int)$aOverviewColumn["id"]] : 0;
                $blConversionFailed = !empty($aTypeConversionFailures[$sMonth][(int)$aOverviewColumn["id"]]);
            } elseif ($aOverviewColumn["type"] == "group") {
                $fAmount = isset($aGroupTotals[$sMonth][(int)$aOverviewColumn["id"]]) ? $aGroupTotals[$sMonth][(int)$aOverviewColumn["id"]] : 0;
                $blConversionFailed = !empty($aGroupConversionFailures[$sMonth][(int)$aOverviewColumn["id"]]);
            } elseif ($aOverviewColumn["key"] == "income") {
                $fAmount = $fIncome;
                $blConversionFailed = !empty($aSummaryConversionFailures[$sMonth]["income"]);
            } elseif ($aOverviewColumn["key"] == "expense") {
                $fAmount = $fExpense;
                $blConversionFailed = !empty($aSummaryConversionFailures[$sMonth]["expense"]);
            } else {
                $fAmount = $fNet;
                $blConversionFailed = !empty($aSummaryConversionFailures[$sMonth]["income"]) || !empty($aSummaryConversionFailures[$sMonth]["expense"]);
            }
            $sAmountClass = $fAmount < 0 ? "amount-negative" : ($fAmount > 0 ? "amount-positive" : "amount-zero");
            $sFormattedAmount = formatAmount($fAmount, $blUseEuropeanAmountFormat) . ($sDisplayCurrency != "" && !$blConversionFailed ? " " . $sDisplayCurrency : "");
            echo "        <td class=\"numeric " . $sAmountClass . "\">" . html($sFormattedAmount) . "</td>\n";
        }
        echo "      </tr>\n";
    }
    if (!$aMonths) {
        echo "      <tr><td colspan=\"" . (count($aOverviewColumnGroup) + 1) . "\">No transactions found.</td></tr>\n";
    }
    echo "    </tbody>\n",
        "  </table>\n";
}

echo "  </div>\n",
    renderSettingsModal($aSettings);

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
