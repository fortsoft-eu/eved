<?php

include "main.php";


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess("portal", "csrf_token", $blJsonResponse);


$sAction = $_SERVER["REQUEST_METHOD"] == "POST" ? getPostedValue("action") : (isset($_GET["action"]) ? (string)$_GET["action"] : "");

if ($_SERVER["REQUEST_METHOD"] == "GET" && $blJsonResponse && $sAction == "check_dashboard_service") {
    dashboardServiceSendCheckJson($oPdo, isset($_GET["service_id"]) ? (int)$_GET["service_id"] : 0, isset($_GET["force"]) && (string)$_GET["force"] == "1");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("csrf_token", true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "create_dashboard_service") {
    dashboardServiceCreateOrUpdate($oPdo, 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "update_dashboard_service") {
    $iServiceId = isset($_POST["service_id"]) ? (int)$_POST["service_id"] : 0;
    if ($iServiceId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid service."), 400);
    }
    dashboardServiceCreateOrUpdate($oPdo, $iServiceId);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "delete_dashboard_service") {
    dashboardServiceDelete($oPdo, isset($_POST["service_id"]) ? (int)$_POST["service_id"] : 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "move_dashboard_service") {
    $iServiceId = isset($_POST["service_id"]) ? (int)$_POST["service_id"] : 0;
    $sDirection = isset($_POST["direction"]) ? (string)$_POST["direction"] : "";
    if ($iServiceId < 1 || ($sDirection != "up" && $sDirection != "down")) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid order change."), 400);
    }
    try {
        $oPdo->beginTransaction();
        dashboardServiceMove($oPdo, $iServiceId, $sDirection);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "service_id" => $iServiceId, "services_html" => dashboardServiceRenderRows($oPdo)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

try {
    $aServiceRows = dashboardServiceFetchRows($oPdo);
    $sServicesHtml = dashboardServiceRenderRows($oPdo, $aServiceRows);
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
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
  <link rel="icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" title="Original">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" title="Graphite">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" title="Midnight">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" title="Slate">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" title="Sepia">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" title="Sand">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" title="Forest">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" title="Moss">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" title="Ocean">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" title="Ice">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" title="Lavender">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" title="Rose">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" title="Copper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" title="Burgundy">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" title="Monochrome">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" title="High Contrast">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" title="Soft">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" title="Paper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" title="Terminal">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" title="Cobalt">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="dashboard-services-table" value="<?php echo html($sFilterValue); ?>" autocomplete="off" spellcheck="false">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-add-dashboard-service">New</button>
    <span class="table-record-count js-table-record-count" data-table-count="dashboard-services-table" aria-live="polite"><?php echo count($aServiceRows); ?></span>
  </p>
<?php

if ($sServicesHtml == "") {
    echo "  <p>No records found.</p>\n";
} else {

?>
  <table id="dashboard-services-table" class="table-filter-target dashboard-services-table<?php echo getCondensedTableClass(); ?>">
    <colgroup><col class="dashboard-service-col-status"><col class="dashboard-service-col-type"><col class="dashboard-service-col-name"><col class="dashboard-service-col-url"><col class="dashboard-service-col-expected"><col class="dashboard-service-col-http"><col class="dashboard-service-col-checked"><col class="dashboard-service-col-detail"><col class="dashboard-service-col-active"><col class="dashboard-service-col-move"><col class="dashboard-service-col-actions"></colgroup>
    <thead>
      <tr>
        <th>Status</th>
        <th>Type</th>
        <th>Name</th>
        <th>Endpoint</th>
        <th>Expected</th>
        <th>Code</th>
        <th>Checked</th>
        <th>Detail</th>
        <th>Active</th>
        <th class="admin-action-column">Order</th>
        <th class="admin-action-column"></th>
      </tr>
    </thead>
    <tbody>
<?php

    echo $sServicesHtml;

?>
    </tbody>
  </table>
<?php

}

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
