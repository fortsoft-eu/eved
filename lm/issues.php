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

function issueTrackerTypeLabels() {
    return array(
        "task" => "Task",
        "bug" => "Bug"
    );
}

function issueTrackerStatusLabels() {
    return array(
        "open" => "Open",
        "in_progress" => "In Progress",
        "waiting" => "Waiting",
        "done" => "Done"
    );
}

function issueTrackerPriorityLabels() {
    return array(
        "low" => "Low",
        "normal" => "Normal",
        "high" => "High",
        "urgent" => "Urgent"
    );
}

function issueTrackerNormalizeOption($sValue, $aLabels, $sDefault) {
    $sValue = trim((string)$sValue);
    return isset($aLabels[$sValue]) ? $sValue : $sDefault;
}

function issueTrackerLabel($sValue, $aLabels) {
    return isset($aLabels[$sValue]) ? $aLabels[$sValue] : $sValue;
}

function issueTrackerGetPostedDueDate() {
    $sDueDate = getPostedTrimmedValue("due_date");
    if ($sDueDate == "") {
        return null;
    }
    if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDueDate)) {
        sendJsonAndExit(array("success" => false, "message" => "Due date is invalid."), 400);
    }
    $aParts = explode("-", $sDueDate);
    if (!checkdate((int)$aParts[1], (int)$aParts[2], (int)$aParts[0])) {
        sendJsonAndExit(array("success" => false, "message" => "Due date is invalid."), 400);
    }
    return $sDueDate;
}

function issueTrackerRenderBadge($sClass, $sText) {
    return "<span class=\"issue-badge " . html($sClass) . "\">" . html($sText) . "</span>";
}

function issueTrackerFetchRows($oPdo) {
    $aRows = array();
    $oStatement = $oPdo->query("SELECT id, issue_type, status, priority, title, description, DATE_FORMAT(due_date, '%Y-%m-%d') AS due_date_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at_text, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') AS updated_at_text, DATE_FORMAT(closed_at, '%Y-%m-%d %H:%i') AS closed_at_text FROM fs_issues ORDER BY status = 'done' ASC, FIELD(status, 'open', 'in_progress', 'waiting', 'done'), due_date IS NULL ASC, due_date ASC, FIELD(priority, 'urgent', 'high', 'normal', 'low'), updated_at DESC, id DESC");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $aRows[] = array(
            "id" => (int)$aRow["id"],
            "issue_type" => (string)$aRow["issue_type"],
            "status" => (string)$aRow["status"],
            "priority" => (string)$aRow["priority"],
            "title" => (string)$aRow["title"],
            "description" => (string)$aRow["description"],
            "due_date" => (string)$aRow["due_date_text"],
            "created_at" => (string)$aRow["created_at_text"],
            "updated_at" => (string)$aRow["updated_at_text"],
            "closed_at" => (string)$aRow["closed_at_text"]
        );
    }
    return $aRows;
}

function issueTrackerRenderRow($aRow) {
    global $sEditEmoji, $sDeleteEmoji;

    $aTypes = issueTrackerTypeLabels();
    $aStatuses = issueTrackerStatusLabels();
    $aPriorities = issueTrackerPriorityLabels();
    $sDescription = trim((string)$aRow["description"]);
    $sDueDate = (string)$aRow["due_date"];
    $sDueClass = "";
    $sToggleTitle = $aRow["status"] == "done" ? "Reopen" : "Mark done";
    $sToggleEmoji = $aRow["status"] == "done" ? "&#8634;" : "&#10004;";
    if ($aRow["status"] != "done" && $sDueDate != "" && $sDueDate < date("Y-m-d")) {
        $sDueClass = " issue-due-overdue";
    }
    return "      <tr data-issue-id=\"" . (int)$aRow["id"] . "\""
        . " data-issue-type=\"" . html($aRow["issue_type"]) . "\""
        . " data-issue-status=\"" . html($aRow["status"]) . "\""
        . " data-issue-priority=\"" . html($aRow["priority"]) . "\""
        . " data-issue-title=\"" . html($aRow["title"]) . "\""
        . " data-issue-description=\"" . html($aRow["description"]) . "\""
        . " data-issue-due-date=\"" . html($sDueDate) . "\""
        . ">"
        . "<td>" . issueTrackerRenderBadge("issue-type-" . $aRow["issue_type"], issueTrackerLabel($aRow["issue_type"], $aTypes)) . "</td>"
        . "<td>" . issueTrackerRenderBadge("issue-status-" . $aRow["status"], issueTrackerLabel($aRow["status"], $aStatuses)) . "</td>"
        . "<td>" . issueTrackerRenderBadge("issue-priority-" . $aRow["priority"], issueTrackerLabel($aRow["priority"], $aPriorities)) . "</td>"
        . "<td class=\"issue-title-cell\"><strong>" . html($aRow["title"]) . "</strong>" . ($sDescription != "" ? "<div class=\"issue-description\">" . nl2br(html($sDescription), false) . "</div>" : "") . "</td>"
        . "<td class=\"issue-date" . $sDueClass . "\">" . ($sDueDate != "" ? html($sDueDate) : "&mdash;") . "</td>"
        . "<td class=\"issue-date\">" . html($aRow["updated_at"]) . "</td>"
        . "<td class=\"admin-action-column\"><a href=\"#\" class=\"item-action js-toggle-issue\" title=\"" . html($sToggleTitle) . "\" aria-label=\"" . html($sToggleTitle) . "\">" . $sToggleEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-edit-issue\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-issue\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a></td>"
        . "</tr>\n";
}

function issueTrackerRenderRows($oPdo) {
    $aRows = issueTrackerFetchRows($oPdo);
    $sHtml = "";
    if (!$aRows) {
        return "      <tr><td colspan=\"7\" class=\"empty-table-message\">No issues found.</td></tr>\n";
    }
    foreach ($aRows as $aRow) {
        $sHtml .= issueTrackerRenderRow($aRow);
    }
    return $sHtml;
}

function issueTrackerCreateOrUpdate($oPdo, $iIssueId) {
    $sIssueType = issueTrackerNormalizeOption(getPostedValue("issue_type"), issueTrackerTypeLabels(), "task");
    $sStatus = issueTrackerNormalizeOption(getPostedValue("status"), issueTrackerStatusLabels(), "open");
    $sPriority = issueTrackerNormalizeOption(getPostedValue("priority"), issueTrackerPriorityLabels(), "normal");
    $sTitle = getPostedTrimmedValue("title");
    $sDescription = getPostedValue("description");
    $mDueDate = issueTrackerGetPostedDueDate();
    $sClosedSql = $sStatus == "done" ? "COALESCE(closed_at, CURRENT_TIMESTAMP)" : "NULL";

    if ($sTitle == "") {
        sendJsonAndExit(array("success" => false, "message" => "Title is required."), 400);
    }
    if (strlen($sTitle) > 255) {
        sendJsonAndExit(array("success" => false, "message" => "Title is too long."), 400);
    }
    try {
        $oPdo->beginTransaction();
        if ($iIssueId > 0) {
            $oStatement = $oPdo->prepare("SELECT id FROM fs_issues WHERE id = :id FOR UPDATE");
            $oStatement->execute(array("id" => $iIssueId));
            if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
                $oPdo->rollBack();
                sendJsonAndExit(array("success" => false, "message" => "Issue was not found."), 404);
            }
            $oStatement = $oPdo->prepare("UPDATE fs_issues SET issue_type = :issue_type, status = :status, priority = :priority, title = :title, description = :description, due_date = :due_date, closed_at = " . $sClosedSql . " WHERE id = :id");
            $oStatement->execute(array(
                "issue_type" => $sIssueType,
                "status" => $sStatus,
                "priority" => $sPriority,
                "title" => $sTitle,
                "description" => $sDescription,
                "due_date" => $mDueDate,
                "id" => $iIssueId
            ));
        } else {
            $sClosedSql = $sStatus == "done" ? "CURRENT_TIMESTAMP" : "NULL";
            $oStatement = $oPdo->prepare("INSERT INTO fs_issues (issue_type, status, priority, title, description, due_date, closed_at) VALUES (:issue_type, :status, :priority, :title, :description, :due_date, " . $sClosedSql . ")");
            $oStatement->execute(array(
                "issue_type" => $sIssueType,
                "status" => $sStatus,
                "priority" => $sPriority,
                "title" => $sTitle,
                "description" => $sDescription,
                "due_date" => $mDueDate
            ));
            $iIssueId = (int)$oPdo->lastInsertId();
        }
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "issue_id" => $iIssueId, "issues_html" => issueTrackerRenderRows($oPdo)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

function issueTrackerDelete($oPdo, $iIssueId) {
    if ($iIssueId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid issue."), 400);
    }
    try {
        $oStatement = $oPdo->prepare("DELETE FROM fs_issues WHERE id = :id");
        $oStatement->execute(array("id" => $iIssueId));
        if ($oStatement->rowCount() < 1) {
            sendJsonAndExit(array("success" => false, "message" => "Issue was not found."), 404);
        }
        sendJsonAndExit(array("success" => true, "issue_id" => $iIssueId, "issues_html" => issueTrackerRenderRows($oPdo)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

function issueTrackerToggleStatus($oPdo, $iIssueId) {
    if ($iIssueId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid issue."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT status FROM fs_issues WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iIssueId));
        $aRow = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aRow) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Issue was not found."), 404);
        }
        $sNewStatus = (string)$aRow["status"] == "done" ? "open" : "done";
        $sClosedSql = $sNewStatus == "done" ? "COALESCE(closed_at, CURRENT_TIMESTAMP)" : "NULL";
        $oStatement = $oPdo->prepare("UPDATE fs_issues SET status = :status, closed_at = " . $sClosedSql . " WHERE id = :id");
        $oStatement->execute(array(
            "status" => $sNewStatus,
            "id" => $iIssueId
        ));
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "issue_id" => $iIssueId, "issues_html" => issueTrackerRenderRows($oPdo)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
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

$sStyleToken = dechex(filemtime(__DIR__ . "/css/admin.css"));
$sScriptToken = dechex(filemtime(__DIR__ . "/js/admin.js"));
$sFilterValue = getQuickTableFilterValue();
$sTitle = getPageTitleText("Issue Tracker", $aAllowedIps);

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Cervinka">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("lm_csrf_token")); ?>">
  <link rel="icon" href="<?php echo html($sBaseUrl . "favicon.ico"); ?>" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo html($sBaseUrl . "favicon.ico"); ?>" type="image/x-icon">
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . $sStyleToken); ?>" rel="stylesheet" type="text/css">
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
<?php echo $sIssuesHtml; ?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo html($sBaseUrl . "js/admin.js?sToken=" . $sScriptToken); ?>"></script>
</body>
</html>
