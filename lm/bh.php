<?php

include "main.php";


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "csrf_token", $blJsonResponse);


$sAction = $_SERVER["REQUEST_METHOD"] == "POST" ? getPostedValue("action") : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("csrf_token", true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "suggest_business_hours_subjects") {
    try {
        sendJsonAndExit(array("success" => true, "subjects" => businessHoursFetchSubjectSuggestions($oPdo, getPostedTrimmedValue("term"), 12)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Subjects could not be loaded."), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "suggest_business_hours_addresses") {
    try {
        sendJsonAndExit(array("success" => true, "addresses" => businessHoursFetchAddressRows($oPdo, (int)getPostedTrimmedValue("subject_id", "0"), 0, getPostedTrimmedValue("term"), 200)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Addresses could not be loaded."), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "create_business_hours") {
    businessHoursCreateOrUpdate($oPdo, 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "update_business_hours") {
    $iId = isset($_POST["business_hours_id"]) ? (int)$_POST["business_hours_id"] : 0;
    if ($iId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid business hours."), 400);
    }
    businessHoursCreateOrUpdate($oPdo, $iId);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "delete_business_hours") {
    businessHoursDelete($oPdo, isset($_POST["business_hours_id"]) ? (int)$_POST["business_hours_id"] : 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "move_business_hours") {
    $iId = isset($_POST["business_hours_id"]) ? (int)$_POST["business_hours_id"] : 0;
    $sDirection = isset($_POST["direction"]) ? (string)$_POST["direction"] : "";
    if ($iId < 1 || ($sDirection != "up" && $sDirection != "down")) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid order change."), 400);
    }
    try {
        $oPdo->beginTransaction();
        businessHoursMove($oPdo, $iId, $sDirection);
        $oPdo->commit();
        sendJsonAndExit(businessHoursBuildResponse($oPdo, $iId));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

try {
    $aRows = businessHoursFetchRows($oPdo);
    $sCardsHtml = businessHoursRenderCards($aRows);
    $sTabsHtml = businessHoursRenderTabs($aRows);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
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
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText($aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body class="business-hours-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <span id="business-hours-tabs" class="business-hours-tabs" role="tablist" aria-label="Business Hours">
<?php

echo $sTabsHtml;

?>
    </span>
    <button type="button" class="button-link js-add-business-hours">New</button>
  </p>
  <div id="business-hours-cards" class="business-hours-grid">
<?php

echo $sCardsHtml;

?>
  </div>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
