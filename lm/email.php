<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
requireFullAccess($aAllowedIps, "portal", "csrf_token", $blJsonResponse);


$sAction = $_SERVER["REQUEST_METHOD"] == "POST" ? getPostedValue("action") : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("csrf_token", true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "set_email_account_type") {
    emailOverviewSaveAccountType();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "create_email_domain") {
    emailOverviewCreateDomain($oPdo);
}

try {
    $aEmailOverviewData = emailOverviewFetchData($oPdo);
    $sEmailTableHtml = emailOverviewRenderTable($oPdo, $aEmailOverviewData);
    $iEmailOverviewRecordCount = (!$aEmailOverviewData["domains"] || !$aEmailOverviewData["local_parts"]) ? 0 : count($aEmailOverviewData["local_parts"]);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$sFilterValue = getQuickTableFilterValue();
$sEmailAccountType = emailOverviewGetSessionAccountType();

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
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
  <meta name="email-account-type" content="<?php echo html($sEmailAccountType); ?>">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText($aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="email-overview-table" value="<?php echo html($sFilterValue); ?>" autocomplete="off" spellcheck="false">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-add-email-domain">New</button>
    <span class="table-record-count js-table-record-count" data-table-count="email-overview-table" aria-live="polite"><?php echo $iEmailOverviewRecordCount; ?></span>
  </p>
<?php

echo $sEmailTableHtml;

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
