<?php

function menuAdminDisplayPath($sPath) {
    $sPath = normalizeMenuPath($sPath);
    return $sPath == "" ? "/" : "/" . $sPath;
}

function menuAdminPathIsValid($sPath) {
    $sPath = normalizeMenuPath($sPath);
    if ($sPath == "") {
        return false;
    }
    if (strpos($sPath, "..") !== false || preg_match("#(^|/)\\.#", $sPath) || preg_match("#[^A-Za-z0-9/_\\.\\-]#", $sPath)) {
        return false;
    }
    return true;
}

function menuAdminTargetIsValid($sTarget) {
    $sTarget = trim((string)$sTarget);
    return $sTarget == "" || preg_match("/^(_blank|_self|_parent|_top|[A-Za-z][A-Za-z0-9_\\-]*)$/", $sTarget);
}

function menuAdminGetScriptPath($sPath) {
    $sPath = normalizeMenuPath($sPath);
    if ($sPath == "") {
        return "index.php";
    }
    if (strtolower(substr($sPath, -4)) == ".php") {
        return $sPath;
    }
    $sTrimmedPath = rtrim($sPath, "/");
    $iLastSlash = strrpos($sTrimmedPath, "/");
    $sLastPart = $iLastSlash === false ? $sTrimmedPath : substr($sTrimmedPath, $iLastSlash + 1);
    if (substr($sPath, -1) == "/" || strpos($sLastPart, ".") === false) {
        return $sTrimmedPath == "" ? "index.php" : $sTrimmedPath . "/index.php";
    }
    return "";
}

function menuAdminStripPhpComments($sSource) {
    $sCode = "";
    foreach (token_get_all($sSource) as $mToken) {
        if (is_array($mToken)) {
            if ($mToken[0] == T_COMMENT || $mToken[0] == T_DOC_COMMENT) {
                continue;
            }
            $sCode .= $mToken[1];
        } else {
            $sCode .= $mToken;
        }
    }
    return $sCode;
}

function menuAdminAppendUniqueValue(&$aValues, $sValue) {
    if ($sValue == "" || in_array($sValue, $aValues, true)) {
        return;
    }
    $aValues[] = $sValue;
}

function menuAdminFindAccessProjects($sCode, $sFunctionName) {
    $aProjects = array();
    if (preg_match_all("/\\b" . preg_quote($sFunctionName, "/") . "\\s*\\(\\s*\\\$aAllowedIps\\s*,\\s*([\"'])((?:\\\\\\\\.|(?!\\1).)*)\\1/s", $sCode, $aMatches, PREG_SET_ORDER)) {
        foreach ($aMatches as $aMatch) {
            menuAdminAppendUniqueValue($aProjects, stripcslashes($aMatch[2]));
        }
    }
    return $aProjects;
}

function menuAdminResolveScriptFile($sPath) {
    $sPath = menuAdminGetScriptPath($sPath);
    if ($sPath == "" || !menuAdminPathIsValid($sPath)) {
        return "";
    }
    $sRootPath = realpath(dirname(__DIR__));
    $sScriptPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $sPath);
    $sRealPath = realpath($sScriptPath);
    if (!$sRootPath || !$sRealPath || !is_file($sRealPath)) {
        return "";
    }
    $sRootPath = rtrim(str_replace("\\", "/", $sRootPath), "/") . "/";
    $sRealPathForCheck = str_replace("\\", "/", $sRealPath);
    if (stripos($sRealPathForCheck, $sRootPath) !== 0) {
        return "";
    }
    return $sRealPath;
}

function menuAdminGetScriptAccess($sPath) {
    $aAccess = array(
        "view" => array(),
        "full" => array(),
        "full_flag" => array(),
        "message" => ""
    );
    $sPath = normalizeMenuPath($sPath);
    if ($sPath != "" && !menuAdminPathIsValid($sPath)) {
        $aAccess["message"] = "Invalid path";
        return $aAccess;
    }
    $sScriptPath = menuAdminGetScriptPath($sPath);
    if ($sScriptPath == "") {
        $aAccess["message"] = "Not PHP";
        return $aAccess;
    }
    if (!menuAdminPathIsValid($sScriptPath)) {
        $aAccess["message"] = "Invalid path";
        return $aAccess;
    }
    $sScriptFile = menuAdminResolveScriptFile($sScriptPath);
    if ($sScriptFile == "") {
        $aAccess["message"] = "File not found";
        return $aAccess;
    }
    $sSource = file_get_contents($sScriptFile);
    if ($sSource === false) {
        $aAccess["message"] = "Unreadable";
        return $aAccess;
    }
    $sCode = menuAdminStripPhpComments($sSource);
    $aAccess["view"] = menuAdminFindAccessProjects($sCode, "requireViewAccess");
    $aAccess["full"] = menuAdminFindAccessProjects($sCode, "requireFullAccess");
    $aAccess["full_flag"] = menuAdminFindAccessProjects($sCode, "isFullAccessAllowed");
    if (!$aAccess["view"] && !$aAccess["full"] && !$aAccess["full_flag"]) {
        $aAccess["message"] = "No access check found";
    }
    return $aAccess;
}

function menuAdminRenderScriptAccess($aRow) {
    global $sEmptyValueEmoji;

    if (!$aRow || $aRow["separator"]) {
        return $sEmptyValueEmoji;
    }
    $aAccess = menuAdminGetScriptAccess($aRow["path"]);
    if ($aAccess["message"] != "") {
        return html($aAccess["message"]);
    }
    $aLabels = array();
    if ($aAccess["view"]) {
        $aLabels[] = "View: " . implode(", ", $aAccess["view"]);
    }
    if ($aAccess["full"]) {
        $aLabels[] = "Full: " . implode(", ", $aAccess["full"]);
    }
    foreach ($aAccess["full_flag"] as $sProject) {
        if (!in_array($sProject, $aAccess["full"], true)) {
            $aLabels[] = "Full UI: " . $sProject;
        }
    }
    return $aLabels ? html(implode("; ", $aLabels)) : html("No access check found");
}

function menuAdminGroupKey($sPath) {
    $sPath = normalizeMenuPath($sPath);
    if ($sPath == "") {
        return "";
    }
    $aParts = explode("/", $sPath);
    if (count($aParts) > 1 || strpos($aParts[0], ".") === false) {
        return $aParts[0];
    }
    return "";
}

function menuAdminCompareRows($aLeft, $aRight) {
    if ($aLeft["group_key"] != $aRight["group_key"]) {
        if ($aLeft["group_key"] == "") {
            return -1;
        }
        if ($aRight["group_key"] == "") {
            return 1;
        }
        return strcmp($aLeft["group_key"], $aRight["group_key"]);
    }
    if ((int)$aLeft["order"] !== (int)$aRight["order"]) {
        return (int)$aLeft["order"] < (int)$aRight["order"] ? -1 : 1;
    }
    if ((int)$aLeft["id"] === (int)$aRight["id"]) {
        return 0;
    }
    return (int)$aLeft["id"] < (int)$aRight["id"] ? -1 : 1;
}

function menuAdminFetchRows($oPdo, $iMenuId = 0) {
    $aRows = array();
    if ($iMenuId > 0) {
        $oStatement = $oPdo->prepare("SELECT id, path, icon, name, title, target, is_active, `order` AS menu_order FROM fs_menu WHERE id = :id");
        $oStatement->execute(array("id" => $iMenuId));
    } else {
        $oStatement = $oPdo->query("SELECT id, path, icon, name, title, target, is_active, `order` AS menu_order FROM fs_menu");
    }
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $sPath = menuAdminDisplayPath($aRow["path"]);
        $sGroupKey = menuAdminGroupKey($sPath);
        $aRows[] = array(
            "id" => (int)$aRow["id"],
            "path" => $sPath,
            "icon" => $aRow["icon"] === null ? null : (string)$aRow["icon"],
            "name" => $aRow["name"] === null ? null : (string)$aRow["name"],
            "title" => $aRow["title"] === null ? null : (string)$aRow["title"],
            "target" => $aRow["target"] === null ? null : (string)$aRow["target"],
            "is_active" => (int)$aRow["is_active"],
            "order" => (int)$aRow["menu_order"],
            "group_key" => $sGroupKey,
            "group_label" => $sGroupKey == "" ? "/" : "/" . $sGroupKey . "/",
            "separator" => $aRow["icon"] === null || $aRow["name"] === null || $aRow["title"] === null
        );
    }
    usort($aRows, "menuAdminCompareRows");
    return $aRows;
}

function menuAdminFetchLockedRows($oPdo) {
    $aRows = array();
    $oStatement = $oPdo->query("SELECT id, path, `order` AS menu_order FROM fs_menu ORDER BY `order` ASC, id ASC FOR UPDATE");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $sPath = menuAdminDisplayPath($aRow["path"]);
        $aRows[] = array(
            "id" => (int)$aRow["id"],
            "path" => $sPath,
            "order" => (int)$aRow["menu_order"],
            "group_key" => menuAdminGroupKey($sPath)
        );
    }
    usort($aRows, "menuAdminCompareRows");
    return $aRows;
}

function menuAdminNormalizeGroupOrder($oPdo, $sGroupKey, $aRows = null) {
    if ($aRows === null) {
        $aRows = menuAdminFetchLockedRows($oPdo);
    }
    $iOrder = 10;
    $oStatement = $oPdo->prepare("UPDATE fs_menu SET `order` = :order WHERE id = :id");
    foreach ($aRows as $aRow) {
        if ($aRow["group_key"] != $sGroupKey) {
            continue;
        }
        $oStatement->execute(array("order" => $iOrder, "id" => (int)$aRow["id"]));
        $iOrder += 10;
    }
}

function menuAdminNextGroupOrder($oPdo, $sGroupKey) {
    $iMaxOrder = 0;
    $aRows = menuAdminFetchLockedRows($oPdo);
    foreach ($aRows as $aRow) {
        if ($aRow["group_key"] == $sGroupKey && (int)$aRow["order"] > $iMaxOrder) {
            $iMaxOrder = (int)$aRow["order"];
        }
    }
    return $iMaxOrder + 10;
}

function menuAdminMoveItem($oPdo, $iMenuId, $sDirection) {
    $aRows = menuAdminFetchLockedRows($oPdo);
    $aGroupRows = array();
    $sGroupKey = null;
    foreach ($aRows as $aRow) {
        if ((int)$aRow["id"] === $iMenuId) {
            $sGroupKey = $aRow["group_key"];
            break;
        }
    }
    if ($sGroupKey === null) {
        throw new RuntimeException("Menu item was not found.");
    }
    foreach ($aRows as $aRow) {
        if ($aRow["group_key"] == $sGroupKey) {
            $aGroupRows[] = $aRow;
        }
    }
    $iOrder = 10;
    foreach ($aGroupRows as $iIndex => $aRow) {
        $aGroupRows[$iIndex]["order"] = $iOrder;
        $iOrder += 10;
    }
    $iCurrentIndex = -1;
    foreach ($aGroupRows as $iIndex => $aRow) {
        if ((int)$aRow["id"] === $iMenuId) {
            $iCurrentIndex = $iIndex;
            break;
        }
    }
    $iTargetIndex = $sDirection == "up" ? $iCurrentIndex - 1 : $iCurrentIndex + 1;
    if ($iCurrentIndex < 0 || !isset($aGroupRows[$iTargetIndex])) {
        menuAdminNormalizeGroupOrder($oPdo, $sGroupKey, $aRows);
        return;
    }
    $iCurrentOrder = (int)$aGroupRows[$iCurrentIndex]["order"];
    $iTargetOrder = (int)$aGroupRows[$iTargetIndex]["order"];
    $oStatement = $oPdo->prepare("UPDATE fs_menu SET `order` = :order WHERE id = :id");
    foreach ($aGroupRows as $aRow) {
        $iNewOrder = (int)$aRow["order"];
        if ((int)$aRow["id"] === (int)$aGroupRows[$iCurrentIndex]["id"]) {
            $iNewOrder = $iTargetOrder;
        } elseif ((int)$aRow["id"] === (int)$aGroupRows[$iTargetIndex]["id"]) {
            $iNewOrder = $iCurrentOrder;
        }
        $oStatement->execute(array("order" => $iNewOrder, "id" => (int)$aRow["id"]));
    }
}

function menuAdminRenderRow($aRow) {
    global $sEditEmoji, $sDeleteEmoji, $sMoveUpEmoji, $sMoveDownEmoji, $sEmptyValueEmoji;

    if (!$aRow) {
        return "";
    }
    $sIcon = $aRow["separator"] ? $sEmptyValueEmoji : htmlValue($aRow["icon"]);
    $sName = $aRow["separator"] ? $sEmptyValueEmoji : htmlValue($aRow["name"]);
    $sTitle = $aRow["separator"] ? $sEmptyValueEmoji : htmlValue($aRow["title"]);
    $sTarget = $aRow["target"] === null ? $sEmptyValueEmoji : htmlValue($aRow["target"]);
    $sAccess = menuAdminRenderScriptAccess($aRow);
    return "          <tr data-menu-id=\"" . (int)$aRow["id"] . "\""
        . " data-menu-path=\"" . html($aRow["path"]) . "\""
        . " data-menu-icon=\"" . html($aRow["icon"]) . "\""
        . " data-menu-name=\"" . html($aRow["name"]) . "\""
        . " data-menu-title=\"" . html($aRow["title"]) . "\""
        . " data-menu-target=\"" . html($aRow["target"]) . "\""
        . " data-menu-active=\"" . ((int)$aRow["is_active"] == 1 ? "1" : "0") . "\""
        . " data-menu-separator=\"" . ($aRow["separator"] ? "1" : "0") . "\""
        . ">"
        . "<td class=\"monospace\">" . html($aRow["path"]) . "</td>"
        . "<td>" . $sIcon . "</td>"
        . "<td>" . $sName . "</td>"
        . "<td>" . $sTitle . "</td>"
        . "<td class=\"monospace\">" . $sTarget . "</td>"
        . "<td>" . $sAccess . "</td>"
        . "<td>" . ((int)$aRow["is_active"] == 1 ? "Yes" : "No") . "</td>"
        . "<td>" . ($aRow["separator"] ? "Yes" : "No") . "</td>"
        . "<td class=\"admin-action-column\"><a href=\"#\" class=\"item-action js-move-menu-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-move-menu-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a></td>"
        . "<td class=\"admin-action-column\"><a href=\"#\" class=\"item-action js-edit-menu-item\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-menu-item\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a></td>"
        . "</tr>\n";
}

function menuAdminRenderTableStart() {
    return "      <table class=\"menu-admin-table\">\n"
        . "        <colgroup><col class=\"menu-col-path\"><col class=\"menu-col-icon\"><col class=\"menu-col-name\"><col class=\"menu-col-title\"><col class=\"menu-col-target\"><col class=\"menu-col-access\"><col class=\"menu-col-active\"><col class=\"menu-col-separator\"><col class=\"menu-col-order\"><col class=\"menu-col-actions\"></colgroup>\n";
}

function menuAdminRenderGroupRow($sLabel) {
    return "          <tr class=\"menu-admin-group-row admin-static-row quick-filter-static-row\"><th colspan=\"10\">" . html($sLabel) . "</th></tr>\n";
}

function menuAdminRenderColumnHeaderRow() {
    return "          <tr class=\"menu-admin-column-row admin-static-row quick-filter-static-row\"><th>Path</th><th>Icon</th><th>Name</th><th>Title</th><th>Target</th><th>Access</th><th>Active</th><th>Separator</th><th class=\"admin-action-column\">Order</th><th class=\"admin-action-column\"></th></tr>\n";
}

function menuAdminRenderSectionHeader($sLabel) {
    return menuAdminRenderGroupRow($sLabel)
        . menuAdminRenderColumnHeaderRow();
}

function menuAdminRenderSectionGap() {
    return "          <tr class=\"menu-admin-section-gap admin-static-row quick-filter-static-row\"><td colspan=\"10\"></td></tr>\n";
}

function menuAdminRenderTables($oPdo) {
    $aRows = menuAdminFetchRows($oPdo);
    if (!$aRows) {
        return "  <p>No records found.</p>\n";
    }
    $sHtml = menuAdminRenderTableStart()
        . "        <tbody>\n";
    $sCurrentGroup = null;
    $blFirstGroup = true;
    foreach ($aRows as $aRow) {
        if ($sCurrentGroup !== $aRow["group_key"]) {
            if (!$blFirstGroup) {
                $sHtml .= menuAdminRenderSectionGap();
            }
            $sCurrentGroup = $aRow["group_key"];
            $sHtml .= menuAdminRenderSectionHeader($aRow["group_label"]);
            $blFirstGroup = false;
        }
        $sHtml .= menuAdminRenderRow($aRow);
    }
    $sHtml .= "        </tbody>\n      </table>\n";
    return $sHtml;
}

function menuAdminCreateOrUpdate($oPdo, $iMenuId) {
    $sPath = menuAdminDisplayPath(getPostedTrimmedValue("path"));
    $sIcon = getPostedTrimmedValue("icon");
    $sName = getPostedTrimmedValue("name");
    $sTitle = getPostedTrimmedValue("title");
    $sTarget = getPostedTrimmedValue("target");
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    $blSeparator = isset($_POST["is_separator"]) && (string)$_POST["is_separator"] == "1";
    if (!menuAdminPathIsValid($sPath)) {
        sendJsonAndExit(array("success" => false, "message" => "Menu path is invalid."), 400);
    }
    if (!menuAdminTargetIsValid($sTarget)) {
        sendJsonAndExit(array("success" => false, "message" => "Menu target is invalid."), 400);
    }
    $mIcon = $blSeparator ? null : $sIcon;
    $mName = $blSeparator ? null : $sName;
    $mTitle = $blSeparator ? null : $sTitle;
    $mTarget = $blSeparator || $sTarget == "" ? null : $sTarget;
    $sGroupKey = menuAdminGroupKey($sPath);
    try {
        $oPdo->beginTransaction();
        if ($iMenuId > 0) {
            $oStatement = $oPdo->prepare("SELECT path FROM fs_menu WHERE id = :id FOR UPDATE");
            $oStatement->execute(array("id" => $iMenuId));
            $aCurrent = $oStatement->fetch(PDO::FETCH_ASSOC);
            if (!$aCurrent) {
                $oPdo->rollBack();
                sendJsonAndExit(array("success" => false, "message" => "Menu item was not found."), 404);
            }
            if (menuAdminGroupKey($aCurrent["path"]) != $sGroupKey) {
                $oPdo->rollBack();
                sendJsonAndExit(array("success" => false, "message" => "Menu item cannot be moved to another path group."), 409);
            }
            $oStatement = $oPdo->prepare("UPDATE fs_menu SET path = :path, icon = :icon, name = :name, title = :title, target = :target, is_active = :is_active WHERE id = :id");
            $oStatement->execute(array(
                "path" => $sPath,
                "icon" => $mIcon,
                "name" => $mName,
                "title" => $mTitle,
                "target" => $mTarget,
                "is_active" => $iIsActive,
                "id" => $iMenuId
            ));
        } else {
            $iOrder = menuAdminNextGroupOrder($oPdo, $sGroupKey);
            $oStatement = $oPdo->prepare("INSERT INTO fs_menu (path, icon, name, title, target, is_active, `order`) VALUES (:path, :icon, :name, :title, :target, :is_active, :order)");
            $oStatement->execute(array(
                "path" => $sPath,
                "icon" => $mIcon,
                "name" => $mName,
                "title" => $mTitle,
                "target" => $mTarget,
                "is_active" => $iIsActive,
                "order" => $iOrder
            ));
            $iMenuId = (int)$oPdo->lastInsertId();
        }
        menuAdminNormalizeGroupOrder($oPdo, $sGroupKey);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "menu_id" => $iMenuId, "tables_html" => menuAdminRenderTables($oPdo)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
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
    $oStatement = $oPdo->query("SELECT id, issue_type, status, priority, title, description, DATE_FORMAT(due_date, '%Y-%m-%d') AS due_date_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at_text, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') AS updated_at_text, DATE_FORMAT(closed_at, '%Y-%m-%d %H:%i') AS closed_at_text FROM fs_issues ORDER BY status = 'done' ASC, FIELD(status, 'open', 'in_progress', 'waiting', 'done'), due_date IS NULL ASC, due_date ASC, FIELD(priority, 'urgent', 'high', 'normal', 'low'), created_at DESC, id DESC");
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
    $sToggleEmoji = $aRow["status"] == "done" ? "&#128260;" : "&#9989;";
    $sRowClass = $aRow["status"] == "done" ? " class=\"issue-row-done\"" : "";
    if ($aRow["status"] != "done" && $sDueDate != "" && $sDueDate < date("Y-m-d")) {
        $sDueClass = " issue-due-overdue";
    }
    return "      <tr" . $sRowClass . " data-issue-id=\"" . (int)$aRow["id"] . "\""
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
        . "<td class=\"admin-action-column\"><a href=\"#\" class=\"item-action js-toggle-issue\" title=\"" . html($sToggleTitle) . "\" aria-label=\"" . html($sToggleTitle) . "\">" . $sToggleEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-edit-issue\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-issue issue-delete-action\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a></td>"
        . "</tr>\n";
}

function issueTrackerRenderRows($oPdo) {
    $aRows = issueTrackerFetchRows($oPdo);
    if (!$aRows) {
        return "";
    }
    $sHtml = "";
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
