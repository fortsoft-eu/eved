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

function dashboardServiceCheckTypeLabels() {
    return array(
        "auto" => "Auto",
        "http" => "HTTP",
        "stream" => "Stream",
        "tcp" => "TCP"
    );
}

function dashboardServiceNormalizeCheckType($sValue) {
    $sValue = trim((string)$sValue);
    $aLabels = dashboardServiceCheckTypeLabels();
    return isset($aLabels[$sValue]) ? $sValue : "auto";
}

function dashboardServiceMatchLabels() {
    return array(
        "contains" => "Contains",
        "starts_with" => "Starts With"
    );
}

function dashboardServiceNormalizeMatch($sValue) {
    $sValue = trim((string)$sValue);
    $aLabels = dashboardServiceMatchLabels();
    return isset($aLabels[$sValue]) ? $sValue : "contains";
}

function dashboardServiceDefaultPort($sScheme) {
    $aPorts = array(
        "ftp" => 21,
        "ftps" => 990,
        "http" => 80,
        "https" => 443,
        "imap" => 143,
        "imaps" => 993,
        "ldap" => 389,
        "ldaps" => 636,
        "mysql" => 3306,
        "pop3" => 110,
        "pop3s" => 995,
        "postgres" => 5432,
        "rdp" => 3389,
        "redis" => 6379,
        "rtmp" => 1935,
        "rtmps" => 443,
        "rtsp" => 554,
        "rtsps" => 322,
        "sftp" => 22,
        "smtp" => 25,
        "smtps" => 465,
        "ssh" => 22,
        "telnet" => 23
    );
    $sScheme = strtolower((string)$sScheme);
    return isset($aPorts[$sScheme]) ? (int)$aPorts[$sScheme] : 0;
}

function dashboardServiceParseEndpoint($sUrl) {
    $aUrl = parse_url((string)$sUrl);
    if (!is_array($aUrl) || !isset($aUrl["scheme"]) || !isset($aUrl["host"])) {
        return null;
    }
    $sScheme = strtolower((string)$aUrl["scheme"]);
    $iPort = isset($aUrl["port"]) ? (int)$aUrl["port"] : dashboardServiceDefaultPort($sScheme);
    if ($iPort < 1 || $iPort > 65535) {
        return null;
    }
    return array(
        "scheme" => $sScheme,
        "host" => (string)$aUrl["host"],
        "port" => $iPort
    );
}

function dashboardServiceResolveCheckType($sCheckType, $sUrl) {
    $sCheckType = dashboardServiceNormalizeCheckType($sCheckType);
    if ($sCheckType != "auto") {
        return $sCheckType;
    }
    $aEndpoint = dashboardServiceParseEndpoint($sUrl);
    if ($aEndpoint && ($aEndpoint["scheme"] == "http" || $aEndpoint["scheme"] == "https")) {
        return "http";
    }
    if ($aEndpoint && in_array($aEndpoint["scheme"], array("rtmp", "rtmps", "rtsp", "rtsps"), true)) {
        return "stream";
    }
    return "tcp";
}

function dashboardServiceUrlIsValid($sUrl, $sCheckType = "auto") {
    $aEndpoint = dashboardServiceParseEndpoint($sUrl);
    if (!$aEndpoint) {
        return false;
    }
    if (dashboardServiceResolveCheckType($sCheckType, $sUrl) == "http") {
        return $aEndpoint["scheme"] == "http" || $aEndpoint["scheme"] == "https";
    }
    return true;
}

function dashboardServiceFetchRows($oPdo, $iServiceId = 0) {
    $aRows = array();
    if ($iServiceId > 0) {
        $oStatement = $oPdo->prepare("SELECT id, name, url, check_type, http_code, match_type, match_text, is_active, `order` AS service_order, UNIX_TIMESTAMP(checked_at) AS checked_at_ts, UNIX_TIMESTAMP(updated_at) AS updated_at_ts, `ok` AS check_ok, `code` AS check_code, `message` AS check_message, DATE_FORMAT(checked_at, '%Y-%m-%d %H:%i:%s') AS checked_at_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at_text, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') AS updated_at_text FROM fs_dashboard WHERE id = :id");
        $oStatement->execute(array("id" => $iServiceId));
    } else {
        $oStatement = $oPdo->query("SELECT id, name, url, check_type, http_code, match_type, match_text, is_active, `order` AS service_order, UNIX_TIMESTAMP(checked_at) AS checked_at_ts, UNIX_TIMESTAMP(updated_at) AS updated_at_ts, `ok` AS check_ok, `code` AS check_code, `message` AS check_message, DATE_FORMAT(checked_at, '%Y-%m-%d %H:%i:%s') AS checked_at_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at_text, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') AS updated_at_text FROM fs_dashboard ORDER BY is_active DESC, `order` ASC, name ASC, id ASC");
    }
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $aRows[] = array(
            "id" => (int)$aRow["id"],
            "name" => (string)$aRow["name"],
            "url" => (string)$aRow["url"],
            "check_type" => dashboardServiceNormalizeCheckType($aRow["check_type"]),
            "http_code" => (int)$aRow["http_code"],
            "match_type" => dashboardServiceNormalizeMatch($aRow["match_type"]),
            "match_text" => $aRow["match_text"] === null ? "" : (string)$aRow["match_text"],
            "is_active" => (int)$aRow["is_active"],
            "order" => (int)$aRow["service_order"],
            "checked_at_ts" => $aRow["checked_at_ts"] === null ? 0 : (int)$aRow["checked_at_ts"],
            "updated_at_ts" => $aRow["updated_at_ts"] === null ? 0 : (int)$aRow["updated_at_ts"],
            "checked_at" => (string)$aRow["checked_at_text"],
            "ok" => $aRow["check_ok"] === null ? null : (int)$aRow["check_ok"],
            "code" => $aRow["check_code"] === null ? "" : (string)$aRow["check_code"],
            "message" => $aRow["check_message"] === null ? "" : (string)$aRow["check_message"],
            "created_at" => (string)$aRow["created_at_text"],
            "updated_at" => (string)$aRow["updated_at_text"]
        );
    }
    return $aRows;
}

function dashboardServiceFetchRow($oPdo, $iServiceId) {
    $aRows = dashboardServiceFetchRows($oPdo, $iServiceId);
    return $aRows ? $aRows[0] : null;
}

function dashboardServiceRenderCheckType($aRow) {
    $sResolvedType = dashboardServiceResolveCheckType($aRow["check_type"], $aRow["url"]);
    if ($aRow["check_type"] == "auto") {
        return "Auto " . strtoupper($sResolvedType);
    }
    return strtoupper($sResolvedType);
}

function dashboardServiceRenderExpected($aRow) {
    $sResolvedType = dashboardServiceResolveCheckType($aRow["check_type"], $aRow["url"]);
    if ($sResolvedType == "tcp") {
        $aEndpoint = dashboardServiceParseEndpoint($aRow["url"]);
        return html($aEndpoint ? "TCP " . $aEndpoint["host"] . ":" . $aEndpoint["port"] : "TCP connection");
    }
    if ($sResolvedType == "stream") {
        $aEndpoint = dashboardServiceParseEndpoint($aRow["url"]);
        return html($aEndpoint ? "Stream " . $aEndpoint["host"] . ":" . $aEndpoint["port"] : "Stream response");
    }
    $sText = "HTTP " . (int)$aRow["http_code"];
    $sMatchText = (string)$aRow["match_text"];
    if ($sMatchText != "") {
        $sText .= "; body " . ($aRow["match_type"] == "starts_with" ? "starts with" : "contains") . " \"" . $sMatchText . "\"";
    }
    return html($sText);
}

function dashboardServiceStatusData($aRow) {
    if ((int)$aRow["is_active"] != 1) {
        return array("class" => "dashboard-status-inactive", "text" => "Inactive");
    }
    if ((int)$aRow["checked_at_ts"] < 1) {
        return array("class" => "dashboard-status-waiting", "text" => "Waiting");
    }
    if ($aRow["ok"] === null) {
        return array("class" => "dashboard-status-checking", "text" => "Checking");
    }
    if ((int)$aRow["ok"] == 1) {
        return array("class" => "dashboard-status-ok", "text" => "OK");
    }
    return array("class" => "dashboard-status-failed", "text" => "Failed");
}

function dashboardServiceRenderRow($aRow) {
    global $sEditEmoji, $sDeleteEmoji, $sMoveUpEmoji, $sMoveDownEmoji, $sEmptyValueEmoji;

    $sRowClass = (int)$aRow["is_active"] == 1 ? "" : " class=\"dashboard-service-inactive\"";
    $aStatus = dashboardServiceStatusData($aRow);
    $sMatchText = (string)$aRow["match_text"];
    return "      <tr" . $sRowClass . " data-dashboard-service-id=\"" . (int)$aRow["id"] . "\""
        . " data-dashboard-service-name=\"" . html($aRow["name"]) . "\""
        . " data-dashboard-service-url=\"" . html($aRow["url"]) . "\""
        . " data-dashboard-service-check-type=\"" . html($aRow["check_type"]) . "\""
        . " data-dashboard-service-http-code=\"" . (int)$aRow["http_code"] . "\""
        . " data-dashboard-service-match-type=\"" . html($aRow["match_type"]) . "\""
        . " data-dashboard-service-match-text=\"" . html($sMatchText) . "\""
        . " data-dashboard-service-active=\"" . ((int)$aRow["is_active"] == 1 ? "1" : "0") . "\""
        . " data-dashboard-service-checked-at-ts=\"" . (int)$aRow["checked_at_ts"] . "\""
        . " data-dashboard-service-updated-at-ts=\"" . (int)$aRow["updated_at_ts"] . "\""
        . " data-dashboard-service-ok=\"" . ($aRow["ok"] === null ? "" : (int)$aRow["ok"]) . "\""
        . ">"
        . "<td><span class=\"dashboard-status " . $aStatus["class"] . " js-dashboard-service-status\">" . html($aStatus["text"]) . "</span></td>"
        . "<td>" . html(dashboardServiceRenderCheckType($aRow)) . "</td>"
        . "<td><strong>" . html($aRow["name"]) . "</strong></td>"
        . "<td class=\"dashboard-service-url\"><a href=\"" . html($aRow["url"]) . "\" target=\"_blank\" rel=\"noopener\">" . html($aRow["url"]) . "</a></td>"
        . "<td class=\"dashboard-service-expected\">" . dashboardServiceRenderExpected($aRow) . "</td>"
        . "<td class=\"dashboard-service-http js-dashboard-service-http\">" . ($aRow["code"] != "" ? html($aRow["code"]) : $sEmptyValueEmoji) . "</td>"
        . "<td class=\"dashboard-service-checked js-dashboard-service-checked\">" . ($aRow["checked_at"] != "" ? html($aRow["checked_at"]) : $sEmptyValueEmoji) . "</td>"
        . "<td class=\"dashboard-service-detail js-dashboard-service-detail\">" . ($aRow["message"] != "" ? html($aRow["message"]) : $sEmptyValueEmoji) . "</td>"
        . "<td>" . ((int)$aRow["is_active"] == 1 ? "Yes" : "No") . "</td>"
        . "<td class=\"admin-action-column\"><a href=\"#\" class=\"item-action js-move-dashboard-service-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-move-dashboard-service-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a></td>"
        . "<td class=\"admin-action-column\"><a href=\"#\" class=\"item-action js-check-dashboard-service\" title=\"Check\" aria-label=\"Check\">&#128260;</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-edit-dashboard-service\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-dashboard-service\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a></td>"
        . "</tr>\n";
}

function dashboardServiceRenderRows($oPdo) {
    $aRows = dashboardServiceFetchRows($oPdo);
    if (!$aRows) {
        return "";
    }
    $sHtml = "";
    foreach ($aRows as $aRow) {
        $sHtml .= dashboardServiceRenderRow($aRow);
    }
    return $sHtml;
}

function dashboardServiceFetchLockedRows($oPdo) {
    $aRows = array();
    $oStatement = $oPdo->query("SELECT id, `order` AS service_order FROM fs_dashboard ORDER BY `order` ASC, id ASC FOR UPDATE");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $aRows[] = array(
            "id" => (int)$aRow["id"],
            "order" => (int)$aRow["service_order"]
        );
    }
    return $aRows;
}

function dashboardServiceNormalizeOrder($oPdo, $aRows = null) {
    if ($aRows === null) {
        $aRows = dashboardServiceFetchLockedRows($oPdo);
    }
    $iOrder = 10;
    $oStatement = $oPdo->prepare("UPDATE fs_dashboard SET `order` = :order WHERE id = :id");
    foreach ($aRows as $aRow) {
        $oStatement->execute(array("order" => $iOrder, "id" => (int)$aRow["id"]));
        $iOrder += 10;
    }
}

function dashboardServiceNextOrder($oPdo) {
    $iMaxOrder = 0;
    $aRows = dashboardServiceFetchLockedRows($oPdo);
    foreach ($aRows as $aRow) {
        if ((int)$aRow["order"] > $iMaxOrder) {
            $iMaxOrder = (int)$aRow["order"];
        }
    }
    return $iMaxOrder + 10;
}

function dashboardServiceMove($oPdo, $iServiceId, $sDirection) {
    $aRows = dashboardServiceFetchLockedRows($oPdo);
    $iOrder = 10;
    $iCurrentIndex = -1;
    foreach ($aRows as $iIndex => $aRow) {
        $aRows[$iIndex]["order"] = $iOrder;
        if ((int)$aRow["id"] === $iServiceId) {
            $iCurrentIndex = $iIndex;
        }
        $iOrder += 10;
    }
    if ($iCurrentIndex < 0) {
        throw new RuntimeException("Service was not found.");
    }
    $iTargetIndex = $sDirection == "up" ? $iCurrentIndex - 1 : $iCurrentIndex + 1;
    if (!isset($aRows[$iTargetIndex])) {
        dashboardServiceNormalizeOrder($oPdo, $aRows);
        return;
    }
    $iCurrentOrder = (int)$aRows[$iCurrentIndex]["order"];
    $iTargetOrder = (int)$aRows[$iTargetIndex]["order"];
    $oStatement = $oPdo->prepare("UPDATE fs_dashboard SET `order` = :order WHERE id = :id");
    foreach ($aRows as $aRow) {
        $iNewOrder = (int)$aRow["order"];
        if ((int)$aRow["id"] === (int)$aRows[$iCurrentIndex]["id"]) {
            $iNewOrder = $iTargetOrder;
        } elseif ((int)$aRow["id"] === (int)$aRows[$iTargetIndex]["id"]) {
            $iNewOrder = $iCurrentOrder;
        }
        $oStatement->execute(array("order" => $iNewOrder, "id" => (int)$aRow["id"]));
    }
}

function dashboardServiceCreateOrUpdate($oPdo, $iServiceId) {
    $sName = getPostedTrimmedValue("name");
    $sUrl = getPostedTrimmedValue("url");
    $sCheckType = dashboardServiceNormalizeCheckType(getPostedTrimmedValue("check_type", "auto"));
    $iHttpCode = (int)getPostedTrimmedValue("http_code", "200");
    $sMatchType = dashboardServiceNormalizeMatch(getPostedTrimmedValue("match_type", "contains"));
    $sMatchText = getPostedTrimmedValue("match_text");
    $mMatchText = $sMatchText == "" ? null : $sMatchText;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($sName == "") {
        sendJsonAndExit(array("success" => false, "message" => "Name is required."), 400);
    }
    if (strlen($sName) > 255) {
        sendJsonAndExit(array("success" => false, "message" => "Name is too long."), 400);
    }
    if ($sUrl == "" || !dashboardServiceUrlIsValid($sUrl, $sCheckType)) {
        sendJsonAndExit(array("success" => false, "message" => "Endpoint is invalid."), 400);
    }
    if (strlen($sUrl) > 2048) {
        sendJsonAndExit(array("success" => false, "message" => "Endpoint is too long."), 400);
    }
    if ($iHttpCode < 100 || $iHttpCode > 599) {
        sendJsonAndExit(array("success" => false, "message" => "HTTP code is invalid."), 400);
    }
    if (strlen($sMatchText) > 1024) {
        sendJsonAndExit(array("success" => false, "message" => "Match text is too long."), 400);
    }
    try {
        $oPdo->beginTransaction();
        if ($iServiceId > 0) {
            $oStatement = $oPdo->prepare("SELECT id FROM fs_dashboard WHERE id = :id FOR UPDATE");
            $oStatement->execute(array("id" => $iServiceId));
            if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
                $oPdo->rollBack();
                sendJsonAndExit(array("success" => false, "message" => "Service was not found."), 404);
            }
            $oStatement = $oPdo->prepare("UPDATE fs_dashboard SET name = :name, url = :url, check_type = :check_type, http_code = :http_code, match_type = :match_type, match_text = :match_text, is_active = :is_active WHERE id = :id");
            $oStatement->execute(array(
                "name" => $sName,
                "url" => $sUrl,
                "check_type" => $sCheckType,
                "http_code" => $iHttpCode,
                "match_type" => $sMatchType,
                "match_text" => $mMatchText,
                "is_active" => $iIsActive,
                "id" => $iServiceId
            ));
        } else {
            $iOrder = dashboardServiceNextOrder($oPdo);
            $oStatement = $oPdo->prepare("INSERT INTO fs_dashboard (name, url, check_type, http_code, match_type, match_text, is_active, `order`) VALUES (:name, :url, :check_type, :http_code, :match_type, :match_text, :is_active, :order)");
            $oStatement->execute(array(
                "name" => $sName,
                "url" => $sUrl,
                "check_type" => $sCheckType,
                "http_code" => $iHttpCode,
                "match_type" => $sMatchType,
                "match_text" => $mMatchText,
                "is_active" => $iIsActive,
                "order" => $iOrder
            ));
            $iServiceId = (int)$oPdo->lastInsertId();
        }
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

function dashboardServiceDelete($oPdo, $iServiceId) {
    if ($iServiceId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid service."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("DELETE FROM fs_dashboard WHERE id = :id");
        $oStatement->execute(array("id" => $iServiceId));
        if ($oStatement->rowCount() < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Service was not found."), 404);
        }
        dashboardServiceNormalizeOrder($oPdo);
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

function dashboardServiceGetHttpStatusCode($aHeaders) {
    $iStatusCode = 0;
    foreach ($aHeaders as $sHeader) {
        if (preg_match("#^HTTP/\\S+\\s+([0-9]{3})\\b#i", (string)$sHeader, $aMatches)) {
            $iStatusCode = (int)$aMatches[1];
        }
    }
    return $iStatusCode;
}

function dashboardServiceFetchUrl($sUrl) {
    if (function_exists("curl_init")) {
        $oCurl = curl_init($sUrl);
        if (!$oCurl) {
            return array(
                "status_code" => 0,
                "body" => "",
                "error" => "HTTP client could not be initialized."
            );
        }
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 20);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array("Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"));
        curl_setopt($oCurl, CURLOPT_USERAGENT, "eved-lm");
        @curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, true);
        @curl_setopt($oCurl, CURLOPT_MAXREDIRS, 5);
        $sBody = curl_exec($oCurl);
        $iStatusCode = (int)curl_getinfo($oCurl, CURLINFO_RESPONSE_CODE);
        $sError = curl_errno($oCurl) ? curl_error($oCurl) : "";
        if (PHP_VERSION_ID < 80000) {
            curl_close($oCurl);
        }
        return array(
            "status_code" => $iStatusCode,
            "body" => $sBody !== false ? (string)$sBody : "",
            "error" => $sError
        );
    }
    $aContext = array(
        "http" => array(
            "method" => "GET",
            "header" => "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\nUser-Agent: eved-lm\r\n",
            "timeout" => 20,
            "ignore_errors" => true,
            "follow_location" => 1,
            "max_redirects" => 5
        )
    );
    $sBody = @file_get_contents($sUrl, false, stream_context_create($aContext));
    $aHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
    $iStatusCode = dashboardServiceGetHttpStatusCode($aHeaders);
    $aError = error_get_last();
    return array(
        "status_code" => $iStatusCode,
        "body" => $sBody !== false ? (string)$sBody : "",
        "error" => $sBody !== false ? "" : (isset($aError["message"]) ? (string)$aError["message"] : "HTTP request failed.")
    );
}

function dashboardServiceCheckTcpEndpoint($aEndpoint) {
    $iErrorNumber = 0;
    $sErrorText = "";
    $oSocket = @fsockopen($aEndpoint["host"], $aEndpoint["port"], $iErrorNumber, $sErrorText, 10);
    if ($oSocket) {
        fclose($oSocket);
        return array(
            "ok" => true,
            "http_status" => "",
            "checked_at" => date("Y-m-d H:i:s"),
            "message" => "TCP connection established on " . $aEndpoint["host"] . ":" . $aEndpoint["port"] . "."
        );
    }
    if ($sErrorText == "") {
        $sErrorText = "Connection failed.";
    }
    if ($iErrorNumber > 0) {
        $sErrorText .= " (" . $iErrorNumber . ")";
    }
    return array(
        "ok" => false,
        "http_status" => "",
        "checked_at" => date("Y-m-d H:i:s"),
        "message" => $sErrorText
    );
}

function dashboardServiceOpenSocket($aEndpoint, &$iErrorNumber, &$sErrorText) {
    $sHost = $aEndpoint["host"];
    if ($aEndpoint["scheme"] == "rtsps" || $aEndpoint["scheme"] == "rtmps") {
        $sHost = "ssl://" . $sHost;
    }
    return @fsockopen($sHost, $aEndpoint["port"], $iErrorNumber, $sErrorText, 10);
}

function dashboardServiceCheckStreamEndpoint($aEndpoint, $sUrl) {
    $iErrorNumber = 0;
    $sErrorText = "";
    $oSocket = dashboardServiceOpenSocket($aEndpoint, $iErrorNumber, $sErrorText);
    $sLine = "";
    $aInfo = array();

    if (!$oSocket) {
        if ($sErrorText == "") {
            $sErrorText = "Stream connection failed.";
        }
        if ($iErrorNumber > 0) {
            $sErrorText .= " (" . $iErrorNumber . ")";
        }
        return array(
            "ok" => false,
            "http_status" => "",
            "checked_at" => date("Y-m-d H:i:s"),
            "message" => $sErrorText
        );
    }
    stream_set_timeout($oSocket, 5);
    if ($aEndpoint["scheme"] == "rtsp" || $aEndpoint["scheme"] == "rtsps") {
        fwrite($oSocket, "OPTIONS " . $sUrl . " RTSP/1.0\r\nCSeq: 1\r\nUser-Agent: eved-lm\r\nConnection: close\r\n\r\n");
        $sLine = fgets($oSocket, 512);
        $aInfo = stream_get_meta_data($oSocket);
        fclose($oSocket);
        if (preg_match("#^RTSP/\\S+\\s+([0-9]{3})\\b#i", (string)$sLine, $aMatches)) {
            $iCode = (int)$aMatches[1];
            return array(
                "ok" => $iCode > 0 && $iCode < 500,
                "http_status" => (string)$iCode,
                "checked_at" => date("Y-m-d H:i:s"),
                "message" => "RTSP response received."
            );
        }
        return array(
            "ok" => false,
            "http_status" => "",
            "checked_at" => date("Y-m-d H:i:s"),
            "message" => isset($aInfo["timed_out"]) && $aInfo["timed_out"] ? "RTSP response timed out." : "RTSP response was not received."
        );
    }
    fclose($oSocket);
    return array(
        "ok" => true,
        "http_status" => "",
        "checked_at" => date("Y-m-d H:i:s"),
        "message" => "Stream port accepted connection on " . $aEndpoint["host"] . ":" . $aEndpoint["port"] . "."
    );
}

function dashboardServiceCheckRow($aRow) {
    $aEndpoint = dashboardServiceParseEndpoint($aRow["url"]);
    $sCheckType = dashboardServiceResolveCheckType($aRow["check_type"], $aRow["url"]);
    if (!$aEndpoint || !dashboardServiceUrlIsValid($aRow["url"], $aRow["check_type"])) {
        return array(
            "ok" => false,
            "http_status" => "",
            "checked_at" => date("Y-m-d H:i:s"),
            "message" => "Endpoint is invalid."
        );
    }
    if ($sCheckType == "tcp") {
        return dashboardServiceCheckTcpEndpoint($aEndpoint);
    }
    if ($sCheckType == "stream") {
        return dashboardServiceCheckStreamEndpoint($aEndpoint, $aRow["url"]);
    }
    $aResponse = dashboardServiceFetchUrl($aRow["url"]);
    $iHttpCode = (int)$aRow["http_code"];
    $iStatusCode = (int)$aResponse["status_code"];
    $sMatchText = (string)$aRow["match_text"];
    $sMessage = "";
    $blOk = false;

    if ((string)$aResponse["error"] != "") {
        $sMessage = (string)$aResponse["error"];
    } elseif ($iStatusCode !== $iHttpCode) {
        $sMessage = "Expected HTTP " . $iHttpCode . ", got " . ($iStatusCode > 0 ? $iStatusCode : "no status") . ".";
    } elseif ($sMatchText != "") {
        if ($aRow["match_type"] == "starts_with") {
            $blOk = strpos(ltrim((string)$aResponse["body"]), $sMatchText) === 0;
            if (!$blOk) {
                $sMessage = "Response body does not start with match text.";
            }
        } else {
            $blOk = strpos((string)$aResponse["body"], $sMatchText) !== false;
            if (!$blOk) {
                $sMessage = "Response body does not contain match text.";
            }
        }
    } else {
        $blOk = true;
    }
    if ($sMessage == "" && $blOk) {
        $sMessage = "HTTP response matched.";
    }
    return array(
        "ok" => $blOk,
        "http_status" => $iStatusCode > 0 ? (string)$iStatusCode : "",
        "checked_at" => date("Y-m-d H:i:s"),
        "message" => $sMessage
    );
}

function dashboardServiceResultCacheSeconds() {
    return 3600;
}

function dashboardServicePendingCacheSeconds() {
    return 120;
}

function dashboardServiceFreshResultIsAvailable($aRow) {
    $iCheckedAt = isset($aRow["checked_at_ts"]) ? (int)$aRow["checked_at_ts"] : 0;
    $iUpdatedAt = isset($aRow["updated_at_ts"]) ? (int)$aRow["updated_at_ts"] : 0;
    $iAge = $iCheckedAt > 0 ? time() - $iCheckedAt : 0;
    if ($iCheckedAt < 1) {
        return false;
    }
    if ($iUpdatedAt > $iCheckedAt) {
        return false;
    }
    if ($aRow["ok"] === null) {
        return $iAge >= 0 && $iAge < dashboardServicePendingCacheSeconds();
    }
    return $iAge >= 0 && $iAge < dashboardServiceResultCacheSeconds();
}

function dashboardServiceCheckState($mOk) {
    if ($mOk === null) {
        return "checking";
    }
    return (int)$mOk == 1 ? "ok" : "failed";
}

function dashboardServiceCheckStatusText($mOk) {
    if ($mOk === null) {
        return "Checking";
    }
    return (int)$mOk == 1 ? "OK" : "Failed";
}

function dashboardServiceBuildCheckJson($iServiceId, $aResult) {
    return array(
        "success" => true,
        "service_id" => $iServiceId,
        "ok" => $aResult["ok"],
        "state" => dashboardServiceCheckState($aResult["ok"]),
        "status_text" => dashboardServiceCheckStatusText($aResult["ok"]),
        "http_status" => (string)$aResult["http_status"],
        "checked_at" => (string)$aResult["checked_at"],
        "checked_at_ts" => isset($aResult["checked_at_ts"]) ? (int)$aResult["checked_at_ts"] : time(),
        "message" => (string)$aResult["message"]
    );
}

function dashboardServiceBuildCachedResult($aRow) {
    return array(
        "ok" => $aRow["ok"],
        "http_status" => (string)$aRow["code"],
        "checked_at" => (string)$aRow["checked_at"],
        "checked_at_ts" => (int)$aRow["checked_at_ts"],
        "message" => (string)$aRow["message"]
    );
}

function dashboardServiceFetchRowForUpdate($oPdo, $iServiceId) {
    $oStatement = $oPdo->prepare("SELECT id, name, url, check_type, http_code, match_type, match_text, is_active, `order` AS service_order, UNIX_TIMESTAMP(checked_at) AS checked_at_ts, UNIX_TIMESTAMP(updated_at) AS updated_at_ts, `ok` AS check_ok, `code` AS check_code, `message` AS check_message, DATE_FORMAT(checked_at, '%Y-%m-%d %H:%i:%s') AS checked_at_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at_text, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') AS updated_at_text FROM fs_dashboard WHERE id = :id FOR UPDATE");
    $oStatement->execute(array("id" => $iServiceId));
    $aRows = array();
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $aRows[] = array(
            "id" => (int)$aRow["id"],
            "name" => (string)$aRow["name"],
            "url" => (string)$aRow["url"],
            "check_type" => dashboardServiceNormalizeCheckType($aRow["check_type"]),
            "http_code" => (int)$aRow["http_code"],
            "match_type" => dashboardServiceNormalizeMatch($aRow["match_type"]),
            "match_text" => $aRow["match_text"] === null ? "" : (string)$aRow["match_text"],
            "is_active" => (int)$aRow["is_active"],
            "order" => (int)$aRow["service_order"],
            "checked_at_ts" => $aRow["checked_at_ts"] === null ? 0 : (int)$aRow["checked_at_ts"],
            "updated_at_ts" => $aRow["updated_at_ts"] === null ? 0 : (int)$aRow["updated_at_ts"],
            "checked_at" => (string)$aRow["checked_at_text"],
            "ok" => $aRow["check_ok"] === null ? null : (int)$aRow["check_ok"],
            "code" => $aRow["check_code"] === null ? "" : (string)$aRow["check_code"],
            "message" => $aRow["check_message"] === null ? "" : (string)$aRow["check_message"],
            "created_at" => (string)$aRow["created_at_text"],
            "updated_at" => (string)$aRow["updated_at_text"]
        );
    }
    return $aRows ? $aRows[0] : null;
}

function dashboardServiceMarkCheckStarted($oPdo, $iServiceId) {
    $oStatement = $oPdo->prepare("UPDATE fs_dashboard SET checked_at = CURRENT_TIMESTAMP(6), `ok` = NULL, `code` = NULL, `message` = :message WHERE id = :id");
    $oStatement->execute(array(
        "message" => "Checking...",
        "id" => $iServiceId
    ));
}

function dashboardServiceSaveCheckResult($oPdo, $iServiceId, $aResult) {
    $oStatement = $oPdo->prepare("UPDATE fs_dashboard SET checked_at = CURRENT_TIMESTAMP(6), `ok` = :ok, `code` = :code, `message` = :message WHERE id = :id");
    $oStatement->execute(array(
        "ok" => !empty($aResult["ok"]) ? 1 : 0,
        "code" => (string)$aResult["http_status"] != "" ? (string)$aResult["http_status"] : null,
        "message" => (string)$aResult["message"],
        "id" => $iServiceId
    ));
}

function dashboardServiceSendCheckJson($oPdo, $iServiceId, $blForce = false) {
    if ($iServiceId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid service."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $aRow = dashboardServiceFetchRowForUpdate($oPdo, $iServiceId);
        if (!$aRow) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Service was not found."), 404);
        }
        if ((int)$aRow["is_active"] != 1) {
            $oPdo->commit();
            sendJsonAndExit(array(
                "success" => true,
                "service_id" => $iServiceId,
                "ok" => null,
                "state" => "inactive",
                "status_text" => "Inactive",
                "http_status" => (string)$aRow["code"],
                "checked_at" => (string)$aRow["checked_at"],
                "checked_at_ts" => (int)$aRow["checked_at_ts"],
                "message" => "Service is inactive."
            ));
        }
        if (!$blForce && dashboardServiceFreshResultIsAvailable($aRow)) {
            $oPdo->commit();
            sendJsonAndExit(dashboardServiceBuildCheckJson($iServiceId, dashboardServiceBuildCachedResult($aRow)));
        }
        dashboardServiceMarkCheckStarted($oPdo, $iServiceId);
        $oPdo->commit();
        $aResult = dashboardServiceCheckRow($aRow);
        dashboardServiceSaveCheckResult($oPdo, $iServiceId, $aResult);
        sendJsonAndExit(dashboardServiceBuildCheckJson($iServiceId, $aResult));
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
