<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireViewAccess("kf", "csrf_token");


if ($_SERVER["REQUEST_METHOD"] == "POST" && getPostedTrimmedValue("action") == "save_oc") {
    requireNamedCsrfToken("csrf_token", true);
    $iOverviewColumns = (int)getPostedTrimmedValue("oc", "0");
    if ($iOverviewColumns >= 1 && $iOverviewColumns <= 12) {
        $_SESSION["kf_index_cols"] = $iOverviewColumns;
        session_write_close();
        sendJsonAndExit(array("success" => true));
    }
    sendJsonAndExit(array("success" => false, "message" => "Invalid overview column count."), 400);
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

$iOverviewColumnsPerTable = 10;
if (isset($_SESSION["kf_index_cols"]) && ctype_digit((string)$_SESSION["kf_index_cols"])) {
    $iStoredOverviewColumns = (int)$_SESSION["kf_index_cols"];
    if ($iStoredOverviewColumns >= 1 && $iStoredOverviewColumns <= 12) {
        $iOverviewColumnsPerTable = $iStoredOverviewColumns;
    }
}

$aOverviewColumnGroups = array();
$aOverviewColumnGroup = array();
foreach ($aOverviewColumns as $aOverviewColumn) {
    if (count($aOverviewColumnGroup) >= $iOverviewColumnsPerTable) {
        $aOverviewColumnGroups[] = $aOverviewColumnGroup;
        $aOverviewColumnGroup = array();
    }
    $aOverviewColumnGroup[] = $aOverviewColumn;
}
if ($aOverviewColumnGroup) {
    $aOverviewColumnGroups[] = $aOverviewColumnGroup;
}


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
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/admin-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/admin-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/admin-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/admin-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/admin-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/admin-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/admin-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/admin-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/admin-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/admin-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/admin-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/admin-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/admin-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/admin-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/admin-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/admin-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/admin-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/admin-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/admin-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/admin-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="monthly-overview-tables" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
    <span class="table-record-count js-table-record-count" data-table-count="monthly-overview-tables" aria-live="polite"><?php echo count($aMonths) * count($aOverviewColumnGroups); ?></span>
  </p>
<?php

if (!$aMonths) {
    echo "  <p>No records found.</p>\n";
} else {
    echo "  <div id=\"monthly-overview-tables\" data-overview-columns=\"" . (int)$iOverviewColumnsPerTable . "\">\n";
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
        echo "    </tbody>\n",
            "  </table>\n";
    }
    echo "  </div>\n";
}
echo
    renderSettingsModal($aSettings);

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
