<?php

include "main.php";


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "lm_csrf_token", $blJsonResponse);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("lm_csrf_token", true);
}

$sAction = $_SERVER["REQUEST_METHOD"] == "POST" ? getPostedValue("action") : "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "create_issue") {
    issueTrackerCreateOrUpdate($oPdo, 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "update_issue") {
    $iIssueId = isset($_POST["issue_id"]) ? (int)$_POST["issue_id"] : 0;
    if ($iIssueId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid issue."), 400);
    }
    issueTrackerCreateOrUpdate($oPdo, $iIssueId);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "delete_issue") {
    issueTrackerDelete($oPdo, isset($_POST["issue_id"]) ? (int)$_POST["issue_id"] : 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "toggle_issue_status") {
    issueTrackerToggleStatus($oPdo, isset($_POST["issue_id"]) ? (int)$_POST["issue_id"] : 0);
}

try {
    $sIssuesHtml = issueTrackerRenderRows($oPdo);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$sFilterValue = getQuickTableFilterValue();
$sTitle = getPageTitleText("Issue Tracker", $aAllowedIps);

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
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("lm_csrf_token")); ?>">
  <link rel="icon" href="<?php echo html($sBaseUrl . "favicon.ico"); ?>" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo html($sBaseUrl . "favicon.ico"); ?>" type="image/x-icon">
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="issues-table" value="<?php echo html($sFilterValue); ?>" autocomplete="off" spellcheck="false">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-add-issue">New</button>
  </p>
  <table id="issues-table" class="table-filter-target issues-table<?php echo getCondensedTableClass(); ?>">
    <colgroup><col class="issue-col-type"><col class="issue-col-status"><col class="issue-col-priority"><col class="issue-col-title"><col class="issue-col-date"><col class="issue-col-date"><col class="issue-col-actions"></colgroup>
    <thead>
      <tr>
        <th>Type</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Title</th>
        <th>Due</th>
        <th>Updated</th>
        <th class="admin-action-column"></th>
      </tr>
    </thead>
    <tbody>
<?php

echo $sIssuesHtml;

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
