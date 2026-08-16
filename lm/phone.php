<?php

include "main.php";


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess("portal", "csrf_token", $blJsonResponse);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("csrf_token", true);
}

$sAction = $_SERVER["REQUEST_METHOD"] == "POST" ? getPostedValue("action") : "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "unlock_lm_encryption") {
    try {
        unlockLmEncryptionFromPostedHash($oPdo);
        session_write_close();
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true));
        }
        sendSecurityHeaders();
        header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
        exit;
    } catch (PDOException $oException) {
        error_log((string)$oException);
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
        }
        send500AndExit("Database error: " . $oException->getMessage());
    } catch (RuntimeException $oException) {
        error_log((string)$oException);
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => false, "message" => $oException->getMessage()), 403);
        }
        send500AndExit($oException->getMessage());
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => false, "message" => "Encryption could not be unlocked."), 500);
        }
        send500AndExit("Encryption could not be unlocked.");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "create_phone_account") {
    phoneAccountsCreateOrUpdate($oPdo, 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "update_phone_account") {
    $iPhoneAccountId = isset($_POST["phone_account_id"]) ? (int)$_POST["phone_account_id"] : 0;
    if ($iPhoneAccountId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid phone account."), 400);
    }
    phoneAccountsCreateOrUpdate($oPdo, $iPhoneAccountId);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "delete_phone_account") {
    phoneAccountsDelete($oPdo, isset($_POST["phone_account_id"]) ? (int)$_POST["phone_account_id"] : 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "move_phone_account") {
    $iPhoneAccountId = isset($_POST["phone_account_id"]) ? (int)$_POST["phone_account_id"] : 0;
    $sDirection = isset($_POST["direction"]) ? (string)$_POST["direction"] : "";
    if ($iPhoneAccountId < 1 || ($sDirection != "up" && $sDirection != "down")) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid order change."), 400);
    }
    try {
        $oPdo->beginTransaction();
        phoneAccountsMove($oPdo, $iPhoneAccountId, $sDirection);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "phone_account_id" => $iPhoneAccountId, "phone_accounts_html" => phoneAccountsRenderRows($oPdo, getVerifiedLmEncryptionSessionKey($oPdo))));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

try {
    $blLmEncryptionConfigured = getLmEncryptionVerifier($oPdo) != "";
    $sLmEncryptionKey = getVerifiedLmEncryptionSessionKey($oPdo);
    $blPhoneAccountsUnlocked = $sLmEncryptionKey != "";
    $aPhoneAccountRows = phoneAccountsFetchRows($oPdo, $sLmEncryptionKey);
    $sPhoneAccountsHtml = phoneAccountsRenderRows($oPdo, $sLmEncryptionKey, $aPhoneAccountRows);
    $aPhoneAccountDefaults = phoneAccountsGetNewDefaults($oPdo);
    $aPhoneAccountCurrencies = getCurrencyOptions($oPdo, $aPhoneAccountDefaults["paid_currency"]);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$sFilterValue = getQuickTableFilterValue();

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
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
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="phone-accounts-table" value="<?php echo html($sFilterValue); ?>" autocomplete="off" spellcheck="false">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-add-phone-account"<?php echo $blPhoneAccountsUnlocked ? "" : " disabled aria-disabled=\"true\" title=\"Unlock encrypted data to add\""; ?>>New</button>
    <span class="table-record-count js-table-record-count" data-table-count="phone-accounts-table" aria-live="polite"><?php echo count($aPhoneAccountRows); ?></span>
  </p>
  <div id="phone-accounts-data" data-default-paid-amount="<?php echo html($aPhoneAccountDefaults["paid_amount"]); ?>" data-default-paid-currency="<?php echo html($aPhoneAccountDefaults["paid_currency"]); ?>" data-currencies="<?php echo html(json_encode($aPhoneAccountCurrencies)); ?>" hidden></div>
<?php

if ($sPhoneAccountsHtml == "") {
    echo "  <p>No records found.</p>\n";
} else {

?>
  <table id="phone-accounts-table" class="table-filter-target phone-accounts-table<?php echo getCondensedTableClass(); ?>">
    <colgroup><col class="phone-account-col-number"><col class="phone-account-col-account"><col class="phone-account-col-token"><col class="phone-account-col-token"><col class="phone-account-col-token"><col class="phone-account-col-sim-id"><col class="phone-account-col-imei"><col class="phone-account-col-date"><col class="phone-account-col-amount"><col class="phone-account-col-note"><col class="phone-account-col-date"><col class="phone-account-col-actions"><col class="phone-account-col-actions"></colgroup>
    <thead>
      <tr>
        <th>Phone</th>
        <th>Telegram Account</th>
        <th>PIN</th>
        <th>PUK</th>
        <th>PUK2</th>
        <th>SIM ID</th>
        <th>IMEI</th>
        <th>Paid</th>
        <th class="numeric">Amount</th>
        <th>Note</th>
        <th>Updated</th>
        <th class="admin-action-column">Order</th>
        <th class="admin-action-column"></th>
      </tr>
    </thead>
    <tbody>
<?php

    echo $sPhoneAccountsHtml;

?>
    </tbody>
  </table>
<?php

}
echo renderEmojiData();

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
<?php

if (!$blPhoneAccountsUnlocked) {

?>
  <div class="confirm-dialog lm-encryption-unlock-dialog js-lm-encryption-unlock-dialog" id="lm-encryption-unlock-dialog" role="dialog" aria-modal="true">
    <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="post" class="confirm-dialog-box login-form lm-encryption-unlock-form js-lm-encryption-unlock-form">
      <input type="hidden" name="action" value="unlock_lm_encryption">
      <input type="hidden" name="csrf_token" value="<?php echo html(getCsrfToken("csrf_token")); ?>">
      <div class="confirm-dialog-header">
        <strong><?php echo $blLmEncryptionConfigured ? "Unlock Encrypted Data" : "Set Encryption Hash"; ?></strong>
      </div>
      <div class="login-fields">
        <label for="lm-encryption-hash">Hash</label>
        <input type="password" id="lm-encryption-hash" name="lm_encryption_hash" autocomplete="current-password" required>
<?php

    if (!$blLmEncryptionConfigured) {

?>
        <label for="lm-encryption-hash-confirm">Confirm Hash</label>
        <input type="password" id="lm-encryption-hash-confirm" name="lm_encryption_hash_confirm" autocomplete="new-password" required>
<?php

    }

?>
      </div>
      <p class="login-message message-error js-lm-encryption-unlock-error" hidden></p>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button"><?php echo $blLmEncryptionConfigured ? "Unlock" : "Set"; ?></button>
      </div>
    </form>
  </div>
<?php

}

?>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
