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
    if ($sAction == "mark_subscription_served") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId < 1) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "Invalid subscription."), 400);
            }
            redirect(getCurrentScriptName());
        }
        try {
            $oPdo->beginTransaction();
            $oStatement = $oPdo->prepare("SELECT id, finance_type_id, amount, currency, billing_period, billing_day, next_due_at, counterparty, note FROM kf_subscriptions WHERE id = :id FOR UPDATE");
            $oStatement->execute(array("id" => $iId));
            $aSubscription = $oStatement->fetch(PDO::FETCH_ASSOC);
            if (!$aSubscription) {
                $oPdo->rollBack();
                if ($blJsonResponse) {
                    sendJsonAndExit(array("success" => false, "message" => "Subscription was not found."), 404);
                }
                redirect(getCurrentScriptName());
            }
            $sNextDueAt = trim((string)$aSubscription["next_due_at"]);
            $oNextDueAt = parseSubscriptionDueAt($sNextDueAt);
            if (!$oNextDueAt) {
                $oPdo->rollBack();
                if ($blJsonResponse) {
                    sendJsonAndExit(array("success" => false, "message" => "Subscription does not have a valid next due date and time."), 409);
                }
                redirect(getCurrentScriptName());
            }
            $iBillingDay = getSubscriptionBillingDayForSave($sNextDueAt, $aSubscription["billing_period"], $aSubscription);
            $sNewNextDueAt = getSubscriptionNextDueAt($sNextDueAt, $aSubscription["billing_period"], $iBillingDay);
            if ($sNewNextDueAt == "") {
                $oPdo->rollBack();
                if ($blJsonResponse) {
                    sendJsonAndExit(array("success" => false, "message" => "Subscription period cannot be advanced automatically."), 409);
                }
                redirect(getCurrentScriptName());
            }
            $oStatement = $oPdo->prepare("INSERT INTO kf_fin_transactions (transaction_date, finance_type_id, amount, currency, counterparty, note) VALUES (:transaction_date, :finance_type_id, :amount, :currency, :counterparty, :note)");
            $oStatement->execute(array(
                "transaction_date" => $oNextDueAt->format("Y-m-d"),
                "finance_type_id" => (int)$aSubscription["finance_type_id"],
                "amount" => $aSubscription["amount"],
                "currency" => normalizeStoredCurrency($aSubscription["currency"]),
                "counterparty" => trim((string)$aSubscription["counterparty"]) != "" ? $aSubscription["counterparty"] : null,
                "note" => trim((string)$aSubscription["note"]) != "" ? $aSubscription["note"] : null
            ));
            $oStatement = $oPdo->prepare("UPDATE kf_subscriptions SET billing_day = :billing_day, next_due_at = :next_due_at WHERE id = :id");
            $oStatement->execute(array("billing_day" => $iBillingDay, "next_due_at" => $sNewNextDueAt, "id" => $iId));
            $oPdo->commit();
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => true, "subscription_id" => $iId, "rows_html" => renderSubscriptionAdminRows(fetchSubscriptionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
            }
        } catch (Exception $oException) {
            error_log((string)$oException);
            if ($oPdo->inTransaction()) {
                $oPdo->rollBack();
            }
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "Subscription could not be marked served."), 500);
            }
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "save_subscription") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        $sName = getPostedTrimmedValue("name");
        $iFinanceTypeId = (int)getPostedTrimmedValue("finance_type_id", "0");
        $fAmount = parseAmount(getPostedTrimmedValue("amount"));
        $sCurrency = getPostedCurrency();
        $sBillingPeriod = getPostedTrimmedValue("billing_period", "monthly");
        $sNextDueAt = getPostedTrimmedValue("next_due_at");
        $sCounterparty = getPostedTrimmedValue("counterparty");
        $sNote = getPostedTrimmedValue("note");
        $iIsActive = getPostedTrimmedValue("is_active") == "1" ? 1 : 0;
        $aBillingPeriods = getSubscriptionBillingPeriods();
        $oStatement = $oPdo->prepare("SELECT id, type_kind FROM kf_fin_types WHERE id = :id AND type_kind IN ('income', 'expense')");
        $oStatement->execute(array("id" => $iFinanceTypeId));
        $aType = $oStatement->fetch();
        $aCurrentSubscription = null;
        $blNewSubscription = $iId < 1;
        if (!$blNewSubscription) {
            $oStatement = $oPdo->prepare("SELECT id, billing_period, billing_day, next_due_at FROM kf_subscriptions WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
            $aCurrentSubscription = $oStatement->fetch(PDO::FETCH_ASSOC);
            if (!$aCurrentSubscription) {
                if ($blJsonResponse) {
                    sendJsonAndExit(array("success" => false, "message" => "Subscription was not found."), 404);
                }
                redirect(getCurrentScriptName());
            }
        }
        if ($sName == "" || !$aType || $fAmount === null || $fAmount <= 0 || !isset($aBillingPeriods[$sBillingPeriod])
            || ($sNextDueAt != "" && !parseSubscriptionDueAt($sNextDueAt)) || !isCurrencyAvailable($oPdo, $sCurrency)) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The subscription could not be saved. Check the name, type, amount, period, and next due date and time."), 400);
            }
            redirect(getCurrentScriptName());
        }
        $fSignedAmount = $aType["type_kind"] == "expense" ? -abs($fAmount) : abs($fAmount);
        $sNextDueAtForDatabase = $sNextDueAt != "" ? formatSubscriptionDueForDatabase($sNextDueAt) : null;
        $iBillingDay = getSubscriptionBillingDayForSave($sNextDueAtForDatabase, $sBillingPeriod, $aCurrentSubscription);
        try {
            if (!$blNewSubscription) {
                $oStatement = $oPdo->prepare("UPDATE kf_subscriptions SET name = :name, finance_type_id = :finance_type_id, amount = :amount, currency = :currency, billing_period = :billing_period, billing_day = :billing_day, next_due_at = :next_due_at, counterparty = :counterparty, note = :note, is_active = :is_active WHERE id = :id");
                $oStatement->execute(array(
                    "name" => $sName,
                    "finance_type_id" => $iFinanceTypeId,
                    "amount" => $fSignedAmount,
                    "currency" => $sCurrency,
                    "billing_period" => $sBillingPeriod,
                    "billing_day" => $iBillingDay,
                    "next_due_at" => $sNextDueAtForDatabase,
                    "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                    "note" => $sNote != "" ? $sNote : null,
                    "is_active" => $iIsActive,
                    "id" => $iId
                ));
            } else {
                $oStatement = $oPdo->prepare("INSERT INTO kf_subscriptions (name, finance_type_id, amount, currency, billing_period, billing_day, next_due_at, counterparty, note, is_active) VALUES (:name, :finance_type_id, :amount, :currency, :billing_period, :billing_day, :next_due_at, :counterparty, :note, :is_active)");
                $oStatement->execute(array(
                    "name" => $sName,
                    "finance_type_id" => $iFinanceTypeId,
                    "amount" => $fSignedAmount,
                    "currency" => $sCurrency,
                    "billing_period" => $sBillingPeriod,
                    "billing_day" => $iBillingDay,
                    "next_due_at" => $sNextDueAtForDatabase,
                    "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
                    "note" => $sNote != "" ? $sNote : null,
                    "is_active" => $iIsActive
                ));
                $iId = (int)$oPdo->lastInsertId();
                saveNewSubscriptionDefaults($iFinanceTypeId, $sCurrency, $sBillingPeriod);
            }
            if ($blNewSubscription) {
                session_write_close();
            }
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => true, "subscription_id" => $iId, "rows_html" => renderSubscriptionAdminRows(fetchSubscriptionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
            }
        } catch (PDOException $oException) {
            error_log((string)$oException);
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The subscription could not be saved."), 500);
            }
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "delete_subscription") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            try {
                $oStatement = $oPdo->prepare("DELETE FROM kf_subscriptions WHERE id = :id");
                $oStatement->execute(array("id" => $iId));
                if ($blJsonResponse) {
                    sendJsonAndExit(array("success" => true, "subscription_id" => $iId, "subscription_deleted" => true, "rows_html" => renderSubscriptionAdminRows(fetchSubscriptionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
                }
            } catch (PDOException $oException) {
                error_log((string)$oException);
                if ($blJsonResponse) {
                    sendJsonAndExit(array("success" => false, "message" => "The subscription could not be deleted."), 500);
                }
            }
        }
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true, "subscription_id" => $iId, "subscription_deleted" => true, "rows_html" => renderSubscriptionAdminRows(fetchSubscriptionAdminRows($oPdo), $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency)));
        }
        redirect(getCurrentScriptName());
    }
}


$aRows = fetchSubscriptionAdminRows($oPdo);
$aFinanceTypes = $blCanEdit ? getFinanceTypes(false) : array();
$aNewSubscriptionDefaults = $blCanEdit ? getNewSubscriptionDefaults($oPdo) : array("finance_type_id" => 0, "currency" => getDefaultCurrency(), "billing_period" => "monthly");


$sToolbarHtml = "";
if ($blCanEdit) {
    $sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-subscription\">New</button>\n";
}


$sTitle = getPageTitleText("Subscriptions", $aAllowedIps);
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
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="subscriptions-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

echo "    <button type=\"button\" class=\"button-link js-index-settings-open\">Settings</button>\n",
    $sToolbarHtml,
    "  </p>\n";

?>
  <table id="subscriptions-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>" data-finance-types="<?php echo htmlspecialchars(json_encode($aFinanceTypes), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" data-currencies="<?php echo htmlspecialchars(getCurrencyOptionsJson($oPdo, getDefaultCurrency()), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" data-default-finance-type-id="<?php echo html((int)$aNewSubscriptionDefaults["finance_type_id"] > 0 ? $aNewSubscriptionDefaults["finance_type_id"] : ""); ?>" data-default-currency="<?php echo html($aNewSubscriptionDefaults["currency"]); ?>" data-default-billing-period="<?php echo html($aNewSubscriptionDefaults["billing_period"]); ?>">
    <thead>
      <tr>
        <th class="subscription-in-column">In</th>
        <th>Name</th>
        <th>Type</th>
        <th class="numeric">Amount</th>
        <th>Period</th>
        <th>Next Due</th>
        <th>Counterparty</th>
        <th>Note</th>
        <th>Status</th>
<?php

if ($blCanEdit) {
    echo "        <th class=\"admin-action-column\"></th>\n";
}

echo "      </tr>\n",
    "    </thead>\n",
    "    <tbody>\n";

echo renderSubscriptionAdminRows($aRows, $blCanEdit, $blUseEuropeanAmountFormat, $sDisplayCurrency),
    "    </tbody>\n",
    "  </table>\n";

?>
<?php echo renderSettingsModal($aSettings); ?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
