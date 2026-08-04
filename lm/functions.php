<?php

function menuAdminDisplayPath($sPath) {
    $sPath = normalizeMenuPath($sPath);
    return $sPath == "" ? "/" : "/" . $sPath;
}

function renderDateTimeWithNbspIndent($mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return "";
    }
    if (preg_match("/^([0-9]{4}-[0-9]{2}-[0-9]{2})[T ]([0-9]{2}:[0-9]{2}(?::[0-9]{2})?)(?:\\.[0-9]+)?(?:Z|[+-][0-9]{2}:?[0-9]{2})?$/", $sValue, $aMatches)) {
        return html($aMatches[1]) . str_repeat("&nbsp;", 9) . html($aMatches[2]);
    }
    return html($sValue);
}

function getPhpGeneratedSelectedFlags($sName, $aTypes, $iDefaultValue) {
    $iSelected = 0;
    $aValues = array();
    if (isset($_GET[$sName])) {
        $aValues = is_array($_GET[$sName]) ? $_GET[$sName] : array($_GET[$sName]);
    }
    foreach ($aValues as $sValue) {
        if (ctype_digit((string)$sValue)) {
            $iValue = (int)$sValue;
            if (in_array($iValue, $aTypes, true)) {
                $iSelected |= $iValue;
            }
        }
    }
    if ($iSelected == 0) {
        $iSelected = $iDefaultValue;
    }
    return $iSelected;
}

function getRequestHeaders() {
    if (function_exists("getallheaders")) {
        return getallheaders();
    }
    $aHeaders = array();
    foreach ($_SERVER as $sKey => $mValue) {
        if (strpos($sKey, "HTTP_") !== 0) {
            continue;
        }
        $sName = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($sKey, 5)))));
        $aHeaders[$sName] = $mValue;
    }
    return $aHeaders;
}

function getRequestPlainTextInfo() {
    $sOutput = "";
    $sOutput .= "<b>Navigation</b>\n";
    $sOutput .= "Referer: " . (isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "N/A") . "\n";
    $sOutput .= "<hr>";
    $sOutput .= "<b>IP address sources</b>\n";
    $sOutput .= "Remote address: " . (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "N/A") . "\n";
    $sOutput .= "X-Real-IP: " . (isset($_SERVER["HTTP_X_REAL_IP"]) ? $_SERVER["HTTP_X_REAL_IP"] : "N/A") . "\n";
    $sOutput .= "X-Forwarded-For: " . (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : "N/A") . "\n";
    $sOutput .= "<hr>";
    $sOutput .= "<b>HTTP headers</b>\n";
    foreach (getRequestHeaders() as $sHeaderName => $sHeaderValue) {
        $sOutput .= $sHeaderName . ": " . $sHeaderValue . "\n";
    }
    $sOutput .= "<hr>";
    $sOutput .= "<b>PHP \$_SERVER array</b>\n";
    foreach ($_SERVER as $sKey => $sValue) {
        $sOutput .= $sKey . ": " . $sValue . "\n";
    }
    $sOutput .= "<hr>";
    $sOutput .= "<b>PHP \$_SESSION array</b>\n";
    if (isset($_SESSION)) {
        foreach ($_SESSION as $sKey => $mValue) {
            if (is_array($mValue)) {
                $mValue = dumpVar($mValue);
            }
            $sOutput .= $sKey . ": " . $mValue . "\n";
        }
    }
    $sOutput .= "<hr>";
    $sOutput .= "<b>PHP \$_COOKIE array</b>\n";
    foreach ($_COOKIE as $sKey => $mValue) {
        if (is_array($mValue)) {
            $mValue = dumpVar($mValue);
        }
        $sOutput .= $sKey . ": " . $mValue . "\n";
    }
    return $sOutput;
}

function encryptTextMessage($sText, $sPassword) {
    if (!function_exists("openssl_encrypt")) {
        throw new RuntimeException("OpenSSL extension is required.");
    }
    $iOptions = defined("OPENSSL_RAW_DATA") ? constant("OPENSSL_RAW_DATA") : 1;
    $sPayload = (string)$sText . md5((string)$sText, true);
    $sEncrypted = openssl_encrypt($sPayload, "AES-128-CBC", md5((string)$sPassword, true), $iOptions, str_repeat("\0", 16));
    if ($sEncrypted === false) {
        throw new RuntimeException("Text encryption failed.");
    }
    return base64_encode($sEncrypted);
}

function decryptTextMessage($sText, $sPassword) {
    if (!function_exists("openssl_decrypt")) {
        throw new RuntimeException("OpenSSL extension is required.");
    }
    $sBytes = base64_decode((string)$sText, true);
    if ($sBytes === false) {
        throw new RuntimeException("Invalid encrypted text.");
    }
    $iOptions = defined("OPENSSL_RAW_DATA") ? constant("OPENSSL_RAW_DATA") : 1;
    $sPayload = openssl_decrypt($sBytes, "AES-128-CBC", md5((string)$sPassword, true), $iOptions, str_repeat("\0", 16));
    if ($sPayload === false) {
        throw new RuntimeException("Text decryption failed.");
    }
    $iLength = strlen($sPayload) - 16;
    if ($iLength < 0) {
        throw new RuntimeException("Message hash is missing.");
    }
    $sMessage = substr($sPayload, 0, $iLength);
    $sHash = substr($sPayload, $iLength, 16);
    if (!hash_equals(md5($sMessage, true), $sHash)) {
        throw new RuntimeException("Message hash is invalid.");
    }
    return $sMessage;
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
    while ($aRow = $oStatement->fetch()) {
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
    while ($aRow = $oStatement->fetch()) {
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
    return "      <table class=\"menu-admin-table table-filter-target\">\n"
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
            $aCurrent = $oStatement->fetch();
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

function businessHoursDayLabels() {
    return array(
        "mon" => "Mon",
        "tue" => "Tue",
        "wed" => "Wed",
        "thu" => "Thu",
        "fri" => "Fri",
        "sat" => "Sat",
        "sun" => "Sun"
    );
}

function businessHoursDefaultHours() {
    $aHours = array();
    foreach (businessHoursDayLabels() as $sDay => $sLabel) {
        $aHours[$sDay] = array(
            "closed" => $sDay == "sat" || $sDay == "sun" ? 1 : 0,
            "open" => $sDay == "sat" || $sDay == "sun" ? "" : "08:00",
            "break_start" => "",
            "break_end" => "",
            "close" => $sDay == "sat" || $sDay == "sun" ? "" : "17:00"
        );
    }
    return $aHours;
}

function businessHoursNormalizeTime($sValue) {
    $sValue = trim((string)$sValue);
    if ($sValue == "") {
        return "";
    }
    $sValue = preg_replace("/\s+/", " ", $sValue);
    if (preg_match("/^([0-9]{1,2})[\s:.]+([0-9]{1,2})$/", $sValue, $aMatches)) {
        $iHour = (int)$aMatches[1];
        $iMinute = (int)$aMatches[2];
    } elseif (preg_match("/^[0-9]{3,4}$/", $sValue)) {
        $iHour = (int)substr($sValue, 0, -2);
        $iMinute = (int)substr($sValue, -2);
    } elseif (preg_match("/^[0-9]{1,2}$/", $sValue)) {
        $iHour = (int)$sValue;
        $iMinute = 0;
    } else {
        return false;
    }
    if ($iHour < 0 || $iHour > 23 || $iMinute < 0 || $iMinute > 59) {
        return false;
    }
    return sprintf("%02d:%02d", $iHour, $iMinute);
}

function businessHoursTimeToMinutes($sValue) {
    $aParts = explode(":", (string)$sValue);
    return ((int)$aParts[0] * 60) + (int)$aParts[1];
}

function businessHoursReadPostedDay($sDay, $sLabel) {
    $blClosed = isset($_POST["closed_" . $sDay]) && (string)$_POST["closed_" . $sDay] == "1";
    $sOpen = businessHoursNormalizeTime(getPostedTrimmedValue("open_" . $sDay));
    $sBreakStart = businessHoursNormalizeTime(getPostedTrimmedValue("break_start_" . $sDay));
    $sBreakEnd = businessHoursNormalizeTime(getPostedTrimmedValue("break_end_" . $sDay));
    $sClose = businessHoursNormalizeTime(getPostedTrimmedValue("close_" . $sDay));
    $iOpen = 0;
    $iClose = 0;
    $iBreakStart = 0;
    $iBreakEnd = 0;

    if ($sOpen === false || $sBreakStart === false || $sBreakEnd === false || $sClose === false) {
        sendJsonAndExit(array("success" => false, "message" => $sLabel . " contains invalid time."), 400);
    }
    if ($blClosed) {
        return array(
            "closed" => 1,
            "open" => "",
            "break_start" => "",
            "break_end" => "",
            "close" => ""
        );
    }
    if ($sOpen == "" || $sClose == "") {
        sendJsonAndExit(array("success" => false, "message" => $sLabel . " must have opening and closing time or be closed."), 400);
    }
    $iOpen = businessHoursTimeToMinutes($sOpen);
    $iClose = businessHoursTimeToMinutes($sClose);
    if ($iClose <= $iOpen) {
        $iClose += 1440;
    }
    if (($sBreakStart == "") !== ($sBreakEnd == "")) {
        sendJsonAndExit(array("success" => false, "message" => $sLabel . " break must have both start and end time."), 400);
    }
    if ($sBreakStart != "") {
        $iBreakStart = businessHoursTimeToMinutes($sBreakStart);
        $iBreakEnd = businessHoursTimeToMinutes($sBreakEnd);
        if ($iBreakStart <= $iOpen) {
            $iBreakStart += 1440;
        }
        if ($iBreakEnd <= $iBreakStart) {
            $iBreakEnd += 1440;
        }
        if (!($iOpen < $iBreakStart && $iBreakStart < $iBreakEnd && $iBreakEnd < $iClose)) {
            sendJsonAndExit(array("success" => false, "message" => $sLabel . " break must be inside opening hours."), 400);
        }
    }
    return array(
        "closed" => 0,
        "open" => $sOpen,
        "break_start" => $sBreakStart,
        "break_end" => $sBreakEnd,
        "close" => $sClose
    );
}

function businessHoursReadPostedHoursJson() {
    $aHours = array();
    foreach (businessHoursDayLabels() as $sDay => $sLabel) {
        $aHours[$sDay] = businessHoursReadPostedDay($sDay, $sLabel);
    }
    $sJson = json_encode($aHours);
    if ($sJson === false) {
        sendJsonAndExit(array("success" => false, "message" => "Business hours could not be saved."), 500);
    }
    return $sJson;
}

function businessHoursNormalizeHours($sJson) {
    $aDefaults = businessHoursDefaultHours();
    $aData = json_decode((string)$sJson, true);
    if (!is_array($aData)) {
        return $aDefaults;
    }
    foreach ($aDefaults as $sDay => $aDefaultDay) {
        if (!isset($aData[$sDay]) || !is_array($aData[$sDay])) {
            $aData[$sDay] = $aDefaultDay;
        }
        foreach ($aDefaultDay as $sKey => $sValue) {
            if (!isset($aData[$sDay][$sKey])) {
                $aData[$sDay][$sKey] = $sValue;
            }
        }
        $aData[$sDay]["closed"] = (int)$aData[$sDay]["closed"] == 1 ? 1 : 0;
        $aData[$sDay]["open"] = businessHoursNormalizeTime($aData[$sDay]["open"]);
        $aData[$sDay]["break_start"] = businessHoursNormalizeTime($aData[$sDay]["break_start"]);
        $aData[$sDay]["break_end"] = businessHoursNormalizeTime($aData[$sDay]["break_end"]);
        $aData[$sDay]["close"] = businessHoursNormalizeTime($aData[$sDay]["close"]);
        if ($aData[$sDay]["open"] === false || $aData[$sDay]["break_start"] === false || $aData[$sDay]["break_end"] === false || $aData[$sDay]["close"] === false) {
            $aData[$sDay] = $aDefaultDay;
        }
    }
    return $aData;
}

function businessHoursEncodeHours($aHours) {
    $sJson = json_encode($aHours);
    return $sJson === false ? "{}" : $sJson;
}

function businessHoursFetchSubjectExactMatches($oPdo, $sTerm, $iLimit = 2) {
    $sTerm = trim((string)$sTerm);
    if ($sTerm == "") {
        return array();
    }
    $iLimit = (int)$iLimit;
    if ($iLimit < 1) {
        $iLimit = 2;
    }
    if ($iLimit > 30) {
        $iLimit = 30;
    }
    $oStatement = $oPdo->prepare("SELECT subject_id, subject_name FROM (" . getSubjectNameSelectSql() . ") AS subject_rows WHERE subject_name = :subject_name_term OR subject_sort_name = :subject_sort_name_term ORDER BY subject_sort_name COLLATE utf8mb4_czech_ci ASC, subject_id ASC LIMIT " . $iLimit);
    $oStatement->execute(array(
        "subject_name_term" => $sTerm,
        "subject_sort_name_term" => $sTerm
    ));
    return $oStatement->fetchAll();
}

function businessHoursFetchSubjectSuggestions($oPdo, $sTerm, $iLimit = 12) {
    $sTerm = trim((string)$sTerm);
    if (strlen($sTerm) < 3) {
        return array();
    }
    $iLimit = (int)$iLimit;
    if ($iLimit < 1) {
        $iLimit = 12;
    }
    if ($iLimit > 30) {
        $iLimit = 30;
    }
    $sLike = "%" . strtr($sTerm, array("!" => "!!", "%" => "!%", "_" => "!_")) . "%";
    $oStatement = $oPdo->prepare("SELECT subject_id, subject_name FROM (" . getSubjectNameSelectSql() . ") AS subject_rows WHERE LOWER(subject_name) LIKE LOWER(:subject_name_term) ESCAPE '!' OR LOWER(subject_sort_name) LIKE LOWER(:subject_sort_name_term) ESCAPE '!' ORDER BY subject_sort_name COLLATE utf8mb4_czech_ci ASC, subject_id ASC LIMIT " . $iLimit);
    $oStatement->execute(array(
        "subject_name_term" => $sLike,
        "subject_sort_name_term" => $sLike
    ));
    return $oStatement->fetchAll();
}

function businessHoursFetchSingleSubjectInputRow($oPdo, $sTerm) {
    $sTerm = trim((string)$sTerm);
    if ($sTerm == "") {
        return null;
    }
    if (preg_match('/^(.*) \(#([1-9][0-9]*)\)$/', $sTerm, $aMatches)) {
        $aRow = fetchSubjectNameRow($oPdo, (int)$aMatches[2]);
        if ($aRow && (string)$aRow["subject_name"] == trim((string)$aMatches[1])) {
            return $aRow;
        }
        return null;
    }
    $aRows = businessHoursFetchSubjectExactMatches($oPdo, $sTerm, 2);
    if (count($aRows) == 1) {
        return $aRows[0];
    }
    if (count($aRows) > 1) {
        return null;
    }
    $aRows = businessHoursFetchSubjectSuggestions($oPdo, $sTerm, 2);
    if (count($aRows) == 1) {
        return $aRows[0];
    }
    return null;
}

function businessHoursAddressText($aAddress, $blIncludeOrganization = true) {
    $aParts = array();
    $sNumber = trim(trim((string)$aAddress["house_number"]) . ((string)$aAddress["orientation_number"] != "" ? "/" . trim((string)$aAddress["orientation_number"]) . trim((string)$aAddress["orientation_suffix"]) : ""));
    if ($sNumber == "" && (string)$aAddress["evidence_number"] != "") {
        $sNumber = "ev. " . trim((string)$aAddress["evidence_number"]);
    }
    $sStreet = trim(trim((string)$aAddress["street_name"]) . " " . $sNumber);
    if ($blIncludeOrganization && trim((string)$aAddress["organization_name"]) != "") {
        $aParts[] = trim((string)$aAddress["organization_name"]);
    }
    foreach (array("department_name", "care_of") as $sColumn) {
        if (trim((string)$aAddress[$sColumn]) != "") {
            $aParts[] = trim((string)$aAddress[$sColumn]);
        }
    }
    if ($sStreet != "") {
        $aParts[] = $sStreet;
    }
    if (trim((string)$aAddress["address_line2"]) != "") {
        $aParts[] = trim((string)$aAddress["address_line2"]);
    }
    $sCityLine = trim(implode(" ", array_filter(array(trim((string)$aAddress["postal_code"]), trim((string)$aAddress["city"]), trim((string)$aAddress["city_part"])))));
    if ($sCityLine != "") {
        $aParts[] = $sCityLine;
    }
    if (trim((string)$aAddress["region"]) != "") {
        $aParts[] = trim((string)$aAddress["region"]);
    }
    $sText = trim(implode(", ", $aParts));
    return $sText != "" ? $sText : "Address #" . (int)$aAddress["id"];
}

function businessHoursFetchAddressRows($oPdo, $iSubjectId, $iAddressId = 0, $sTerm = "", $iLimit = 30) {
    $aRows = array();
    if ((int)$iSubjectId < 1) {
        return $aRows;
    }
    $sSql = "SELECT id, subject_id, address_type, organization_name, department_name, care_of, street_name, house_number, evidence_number, orientation_number, orientation_suffix, address_line2, city, city_part, postal_code, region, country, is_primary, is_active, note FROM ex_subject_addresses WHERE subject_id = :subject_id";
    if ((int)$iAddressId > 0) {
        $sSql .= " AND id = :address_id";
    }
    $sSql .= " ORDER BY is_active DESC, is_primary DESC, id ASC";
    $oStatement = $oPdo->prepare($sSql);
    $aParams = array("subject_id" => (int)$iSubjectId);
    if ((int)$iAddressId > 0) {
        $aParams["address_id"] = (int)$iAddressId;
    }
    $oStatement->execute($aParams);
    $sTerm = trim((string)$sTerm);
    $iLimit = (int)$iLimit;
    if ($iLimit < 1) {
        $iLimit = 30;
    }
    while ($aRow = $oStatement->fetch()) {
        $aRow["address_id"] = (int)$aRow["id"];
        $aRow["address_text"] = businessHoursAddressText($aRow);
        if ($sTerm != "" && stripos($aRow["address_text"], $sTerm) === false) {
            continue;
        }
        $aRows[] = $aRow;
        if (count($aRows) >= $iLimit) {
            break;
        }
    }
    return $aRows;
}

function businessHoursFetchAddressRow($oPdo, $iSubjectId, $iAddressId) {
    $aRows = businessHoursFetchAddressRows($oPdo, $iSubjectId, $iAddressId, "", 1);
    return $aRows ? $aRows[0] : null;
}

function businessHoursFetchSingleAddressInputRow($oPdo, $iSubjectId, $sTerm) {
    $sTerm = trim((string)$sTerm);
    if ((int)$iSubjectId < 1 || $sTerm == "") {
        return null;
    }
    if (preg_match('/^(.*) \(#([1-9][0-9]*)\)$/', $sTerm, $aMatches)) {
        $aRow = businessHoursFetchAddressRow($oPdo, $iSubjectId, (int)$aMatches[2]);
        if ($aRow && (string)$aRow["address_text"] == trim((string)$aMatches[1])) {
            return $aRow;
        }
        return null;
    }
    $aRows = businessHoursFetchAddressRows($oPdo, $iSubjectId, 0, "", 200);
    $aMatches = array();
    foreach ($aRows as $aRow) {
        if ((string)$aRow["address_text"] == $sTerm) {
            $aMatches[] = $aRow;
        }
    }
    if (count($aMatches) == 1) {
        return $aMatches[0];
    }
    return null;
}

function businessHoursFetchRows($oPdo, $iId = 0) {
    $aRows = array();
    $sSql = "SELECT bh.id, bh.subject_id, bh.address_id, bh.hours, bh.icon, bh.is_active, bh.`order` AS bh_order, DATE_FORMAT(bh.created_at, '%Y-%m-%d %H:%i') AS created_at_text, DATE_FORMAT(bh.updated_at, '%Y-%m-%d %H:%i') AS updated_at_text, subject_rows.subject_name, subject_rows.subject_sort_name, a.address_type, a.organization_name, a.department_name, a.care_of, a.street_name, a.house_number, a.evidence_number, a.orientation_number, a.orientation_suffix, a.address_line2, a.city, a.city_part, a.postal_code, a.region, a.country, a.is_primary AS address_primary, a.is_active AS address_active, a.note AS address_note FROM fs_business_hours AS bh LEFT JOIN (" . getSubjectNameSelectSql() . ") AS subject_rows ON subject_rows.subject_id = bh.subject_id LEFT JOIN ex_subject_addresses AS a ON a.id = bh.address_id";
    if ((int)$iId > 0) {
        $oStatement = $oPdo->prepare($sSql . " WHERE bh.id = :id");
        $oStatement->execute(array("id" => (int)$iId));
    } else {
        $oStatement = $oPdo->query($sSql . " ORDER BY bh.is_active DESC, bh.`order` ASC, subject_rows.subject_sort_name COLLATE utf8mb4_czech_ci ASC, bh.id ASC");
    }
    while ($aRow = $oStatement->fetch()) {
        $aAddress = array(
            "id" => (int)$aRow["address_id"],
            "subject_id" => (int)$aRow["subject_id"],
            "address_type" => (string)$aRow["address_type"],
            "organization_name" => (string)$aRow["organization_name"],
            "department_name" => (string)$aRow["department_name"],
            "care_of" => (string)$aRow["care_of"],
            "street_name" => (string)$aRow["street_name"],
            "house_number" => (string)$aRow["house_number"],
            "evidence_number" => (string)$aRow["evidence_number"],
            "orientation_number" => (string)$aRow["orientation_number"],
            "orientation_suffix" => (string)$aRow["orientation_suffix"],
            "address_line2" => (string)$aRow["address_line2"],
            "city" => (string)$aRow["city"],
            "city_part" => (string)$aRow["city_part"],
            "postal_code" => (string)$aRow["postal_code"],
            "region" => (string)$aRow["region"],
            "country" => (string)$aRow["country"],
            "is_primary" => (int)$aRow["address_primary"],
            "is_active" => (int)$aRow["address_active"],
            "note" => (string)$aRow["address_note"]
        );
        $aHours = businessHoursNormalizeHours($aRow["hours"]);
        $aRows[] = array(
            "id" => (int)$aRow["id"],
            "subject_id" => (int)$aRow["subject_id"],
            "address_id" => (int)$aRow["address_id"],
            "subject_name" => (string)$aRow["subject_name"] != "" ? (string)$aRow["subject_name"] : "Subject #" . (int)$aRow["subject_id"],
            "organization_name" => trim((string)$aAddress["organization_name"]),
            "address_text" => businessHoursAddressText($aAddress),
            "address_display_text" => businessHoursAddressText($aAddress, false),
            "hours" => businessHoursEncodeHours($aHours),
            "hours_data" => $aHours,
            "icon" => (string)$aRow["icon"],
            "is_active" => (int)$aRow["is_active"],
            "order" => (int)$aRow["bh_order"],
            "created_at" => (string)$aRow["created_at_text"],
            "updated_at" => (string)$aRow["updated_at_text"]
        );
    }
    return $aRows;
}

function businessHoursRenderHours($aHours) {
    $blHasBreak = false;
    foreach (businessHoursDayLabels() as $sDay => $sLabel) {
        $aDay = isset($aHours[$sDay]) && is_array($aHours[$sDay]) ? $aHours[$sDay] : array();
        if (empty($aDay["closed"]) && (string)$aDay["break_start"] != "" && (string)$aDay["break_end"] != "") {
            $blHasBreak = true;
            break;
        }
    }
    if ($blHasBreak) {
        $sHtml = "<table class=\"business-hours-table business-hours-table-with-breaks\"><colgroup><col class=\"business-hours-col-day\"><col class=\"business-hours-col-interval\"><col class=\"business-hours-col-divider\"><col class=\"business-hours-col-interval\"></colgroup><tbody>";
    } else {
        $sHtml = "<table class=\"business-hours-table business-hours-table-plain\"><colgroup><col class=\"business-hours-col-day\"><col class=\"business-hours-col-hours\"></colgroup><tbody>";
    }
    foreach (businessHoursDayLabels() as $sDay => $sLabel) {
        $aDay = isset($aHours[$sDay]) && is_array($aHours[$sDay]) ? $aHours[$sDay] : array();
        $sHtml .= "<tr><th scope=\"row\">" . html($sLabel) . "</th>";
        if (!empty($aDay["closed"])) {
            $sHtml .= "<td class=\"business-hours-closed\"" . ($blHasBreak ? " colspan=\"3\"" : "") . ">Closed</td>";
        } elseif (!$blHasBreak) {
            $sHtml .= "<td class=\"business-hours-hours\">" . html((string)$aDay["open"]) . "&mdash;" . html((string)$aDay["close"]) . "</td>";
        } else {
            if ((string)$aDay["break_start"] != "" && (string)$aDay["break_end"] != "") {
                $sHtml .= "<td class=\"business-hours-time-range\">" . html((string)$aDay["open"]) . "&mdash;" . html((string)$aDay["break_start"]) . "</td>";
                $sHtml .= "<td></td>";
                $sHtml .= "<td class=\"business-hours-time-range business-hours-time-range-second\">" . html((string)$aDay["break_end"]) . "&mdash;" . html((string)$aDay["close"]) . "</td>";
            } else {
                $sHtml .= "<td class=\"business-hours-time-open\">" . html((string)$aDay["open"]) . "</td>";
                $sHtml .= "<td class=\"business-hours-divider\">&mdash;</td>";
                $sHtml .= "<td class=\"business-hours-time-close\">" . html((string)$aDay["close"]) . "</td>";
            }
        }
        $sHtml .= "</tr>";
    }
    return $sHtml . "</tbody></table>";
}

function businessHoursIsShortNoBreakWord($sWord) {
    $sLetters = preg_replace('/[^\p{L}\p{N}]+/u', '', (string)$sWord);
    return $sLetters != "" && preg_match('/^[\p{L}\p{N}]{1,3}$/u', $sLetters);
}

function businessHoursHtmlNoShortWordBreaks($sText) {
    $aParts = preg_split('/(\s+)/u', (string)$sText, -1, PREG_SPLIT_DELIM_CAPTURE);
    $sHtml = "";
    $iCount = is_array($aParts) ? count($aParts) : 0;
    $i;

    if (!is_array($aParts)) {
        return html($sText);
    }
    for ($i = 0; $i < $iCount; $i++) {
        if ($i % 2 == 1 && businessHoursIsShortNoBreakWord($aParts[$i - 1])) {
            $sHtml .= "&nbsp;";
        } else {
            $sHtml .= html($aParts[$i]);
        }
    }
    return $sHtml;
}

function businessHoursRenderCard($aRow, $blActiveCard = false) {
    global $sEditEmoji, $sDeleteEmoji, $sMoveUpEmoji, $sMoveDownEmoji;

    $sCardClass = (int)$aRow["is_active"] == 1 ? "business-hours-card" : "business-hours-card business-hours-card-inactive";
    if ($blActiveCard) {
        $sCardClass .= " business-hours-card-active";
    }
    $sOrganizationName = trim((string)$aRow["organization_name"]);
    $sAddressText = isset($aRow["address_display_text"]) ? (string)$aRow["address_display_text"] : (string)$aRow["address_text"];
    if ($sOrganizationName != "") {
        $sTitleHtml = "<span class=\"business-hours-card-title\"><strong>" . businessHoursHtmlNoShortWordBreaks($sOrganizationName) . "</strong><br class=\"business-hours-linear-break\"><span>" . businessHoursHtmlNoShortWordBreaks($aRow["subject_name"]) . "</span></span>";
    } else {
        $sTitleHtml = "<span class=\"business-hours-card-title\"><strong>" . businessHoursHtmlNoShortWordBreaks($aRow["subject_name"]) . "</strong><br class=\"business-hours-linear-break\"><span>&nbsp;</span></span>";
    }
    return "    <section class=\"" . $sCardClass . "\" data-business-hours-id=\"" . (int)$aRow["id"] . "\""
        . " data-business-hours-subject-id=\"" . (int)$aRow["subject_id"] . "\""
        . " data-business-hours-address-id=\"" . (int)$aRow["address_id"] . "\""
        . " data-business-hours-subject-name=\"" . html($aRow["subject_name"]) . "\""
        . " data-business-hours-address-text=\"" . html($aRow["address_text"]) . "\""
        . " data-business-hours-hours=\"" . html($aRow["hours"]) . "\""
        . " data-business-hours-icon=\"" . html($aRow["icon"]) . "\""
        . " data-business-hours-active=\"" . ((int)$aRow["is_active"] == 1 ? "1" : "0") . "\">\n"
        . "      <div class=\"business-hours-card-top\">" . $sTitleHtml . "<span class=\"business-hours-card-actions\"><a href=\"#\" class=\"item-action js-move-business-hours-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-move-business-hours-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a>&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-edit-business-hours\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-business-hours\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a></span></div>\n"
        . "      <div class=\"business-hours-address\">" . html($sAddressText) . "</div>\n"
        . "      " . businessHoursRenderHours($aRow["hours_data"]) . "<br class=\"business-hours-linear-gap\"><br class=\"business-hours-linear-gap\">\n"
        . "    </section>\n";
}

function businessHoursRenderCards($aRows) {
    $blActiveCardSet = false;
    $blActiveCard;
    if (!$aRows) {
        return "    <p class=\"business-hours-empty\">No records found.</p>\n";
    }
    $sHtml = "";
    foreach ($aRows as $aRow) {
        $blActiveCard = !$blActiveCardSet && (int)$aRow["is_active"] == 1;
        $sHtml .= businessHoursRenderCard($aRow, $blActiveCard);
        if ($blActiveCard) {
            $blActiveCardSet = true;
        }
    }
    return $sHtml;
}

function businessHoursRenderTabs($aRows) {
    $sHtml = "";
    $iIndex = 1;
    foreach ($aRows as $aRow) {
        if ((int)$aRow["is_active"] != 1) {
            continue;
        }
        $sLabel = (string)$aRow["icon"] != "" ? htmlValue($aRow["icon"]) : (string)$iIndex;
        $sHtml .= "      <button type=\"button\" class=\"button-link business-hours-tab" . ($iIndex == 1 ? " business-hours-tab-active" : "") . "\" data-business-hours-tab-id=\"" . (int)$aRow["id"] . "\" role=\"tab\" aria-selected=\"" . ($iIndex == 1 ? "true" : "false") . "\" aria-label=\"Business Hours " . $iIndex . "\">" . $sLabel . "</button>\n";
        $iIndex++;
    }
    return $sHtml;
}

function businessHoursFetchLockedRows($oPdo) {
    $aRows = array();
    $oStatement = $oPdo->query("SELECT id, `order` AS bh_order FROM fs_business_hours ORDER BY `order` ASC, id ASC FOR UPDATE");
    while ($aRow = $oStatement->fetch()) {
        $aRows[] = array(
            "id" => (int)$aRow["id"],
            "order" => (int)$aRow["bh_order"]
        );
    }
    return $aRows;
}

function businessHoursNormalizeOrder($oPdo, $aRows = null) {
    if ($aRows === null) {
        $aRows = businessHoursFetchLockedRows($oPdo);
    }
    $iOrder = 10;
    $oStatement = $oPdo->prepare("UPDATE fs_business_hours SET `order` = :order WHERE id = :id");
    foreach ($aRows as $aRow) {
        $oStatement->execute(array("order" => $iOrder, "id" => (int)$aRow["id"]));
        $iOrder += 10;
    }
}

function businessHoursNextOrder($oPdo) {
    $iMaxOrder = 0;
    $aRows = businessHoursFetchLockedRows($oPdo);
    foreach ($aRows as $aRow) {
        if ((int)$aRow["order"] > $iMaxOrder) {
            $iMaxOrder = (int)$aRow["order"];
        }
    }
    return $iMaxOrder + 10;
}

function businessHoursMove($oPdo, $iId, $sDirection) {
    $aRows = businessHoursFetchLockedRows($oPdo);
    $iOrder = 10;
    $iCurrentIndex = -1;
    foreach ($aRows as $iIndex => $aRow) {
        $aRows[$iIndex]["order"] = $iOrder;
        if ((int)$aRow["id"] === $iId) {
            $iCurrentIndex = $iIndex;
        }
        $iOrder += 10;
    }
    if ($iCurrentIndex < 0) {
        throw new RuntimeException("Business hours were not found.");
    }
    $iTargetIndex = $sDirection == "up" ? $iCurrentIndex - 1 : $iCurrentIndex + 1;
    if (!isset($aRows[$iTargetIndex])) {
        businessHoursNormalizeOrder($oPdo, $aRows);
        return;
    }
    $iCurrentOrder = (int)$aRows[$iCurrentIndex]["order"];
    $iTargetOrder = (int)$aRows[$iTargetIndex]["order"];
    $oStatement = $oPdo->prepare("UPDATE fs_business_hours SET `order` = :order WHERE id = :id");
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

function businessHoursBuildResponse($oPdo, $iId = 0) {
    $aRows = businessHoursFetchRows($oPdo);
    return array(
        "success" => true,
        "business_hours_id" => (int)$iId,
        "cards_html" => businessHoursRenderCards($aRows),
        "tabs_html" => businessHoursRenderTabs($aRows)
    );
}

function businessHoursCreateOrUpdate($oPdo, $iId) {
    $iSubjectId = (int)getPostedTrimmedValue("subject_id", "0");
    $iAddressId = (int)getPostedTrimmedValue("address_id", "0");
    $sSubjectName = getPostedTrimmedValue("subject_name");
    $sAddressText = getPostedTrimmedValue("address_text");
    $sHours = businessHoursReadPostedHoursJson();
    $sIcon = getPostedTrimmedValue("icon");
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iSubjectId < 1 && $sSubjectName != "") {
        $aSubjectRow = businessHoursFetchSingleSubjectInputRow($oPdo, $sSubjectName);
        if ($aSubjectRow) {
            $iSubjectId = (int)$aSubjectRow["subject_id"];
        }
    }
    if ($iSubjectId < 1 || !fetchSubjectNameRow($oPdo, $iSubjectId)) {
        sendJsonAndExit(array("success" => false, "message" => "Subject is required."), 400);
    }
    if ($iAddressId < 1 && $sAddressText != "") {
        $aAddressRow = businessHoursFetchSingleAddressInputRow($oPdo, $iSubjectId, $sAddressText);
        if ($aAddressRow) {
            $iAddressId = (int)$aAddressRow["address_id"];
        }
    }
    if ($iAddressId < 1 || !businessHoursFetchAddressRow($oPdo, $iSubjectId, $iAddressId)) {
        sendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }
    try {
        $oPdo->beginTransaction();
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("SELECT id FROM fs_business_hours WHERE id = :id FOR UPDATE");
            $oStatement->execute(array("id" => $iId));
            if (!$oStatement->fetch()) {
                $oPdo->rollBack();
                sendJsonAndExit(array("success" => false, "message" => "Business hours were not found."), 404);
            }
            $oStatement = $oPdo->prepare("UPDATE fs_business_hours SET subject_id = :subject_id, address_id = :address_id, hours = :hours, is_active = :is_active, icon = :icon WHERE id = :id");
            $oStatement->execute(array(
                "subject_id" => $iSubjectId,
                "address_id" => $iAddressId,
                "hours" => $sHours,
                "is_active" => $iIsActive,
                "icon" => $sIcon,
                "id" => $iId
            ));
        } else {
            $iOrder = businessHoursNextOrder($oPdo);
            $oStatement = $oPdo->prepare("INSERT INTO fs_business_hours (subject_id, address_id, hours, is_active, icon, `order`) VALUES (:subject_id, :address_id, :hours, :is_active, :icon, :order)");
            $oStatement->execute(array(
                "subject_id" => $iSubjectId,
                "address_id" => $iAddressId,
                "hours" => $sHours,
                "is_active" => $iIsActive,
                "icon" => $sIcon,
                "order" => $iOrder
            ));
            $iId = (int)$oPdo->lastInsertId();
        }
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

function businessHoursDelete($oPdo, $iId) {
    if ($iId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid business hours."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("DELETE FROM fs_business_hours WHERE id = :id");
        $oStatement->execute(array("id" => $iId));
        if ($oStatement->rowCount() < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Business hours were not found."), 404);
        }
        businessHoursNormalizeOrder($oPdo);
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
    while ($aRow = $oStatement->fetch()) {
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
    global $sEditEmoji, $sDeleteEmoji, $sMoveUpEmoji, $sMoveDownEmoji;

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
        . "<td class=\"dashboard-service-http js-dashboard-service-http\">" . ($aRow["code"] != "" ? html($aRow["code"]) : "") . "</td>"
        . "<td class=\"dashboard-service-checked js-dashboard-service-checked\">" . ($aRow["checked_at"] != "" ? renderDateTimeWithNbspIndent($aRow["checked_at"]) : "") . "</td>"
        . "<td class=\"dashboard-service-detail js-dashboard-service-detail\">" . ($aRow["message"] != "" ? html($aRow["message"]) : "") . "</td>"
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
    while ($aRow = $oStatement->fetch()) {
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
            if (!$oStatement->fetch()) {
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
    $aHeaders = http_get_last_response_headers();
    if (!is_array($aHeaders)) {
        $aHeaders = array();
    }
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
    while ($aRow = $oStatement->fetch()) {
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
    $iHour = 0;
    $iMinute = 0;
    $iSecond = 0;
    if ($sDueDate == "") {
        return null;
    }
    if (!preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})(?:[ T]([0-9]{2}):([0-9]{2})(?::([0-9]{2}))?)?$/", $sDueDate, $aMatches)) {
        sendJsonAndExit(array("success" => false, "message" => "Due date is invalid."), 400);
    }
    if (!checkdate((int)$aMatches[2], (int)$aMatches[3], (int)$aMatches[1])) {
        sendJsonAndExit(array("success" => false, "message" => "Due date is invalid."), 400);
    }
    if (isset($aMatches[4]) && $aMatches[4] != "") {
        $iHour = (int)$aMatches[4];
        $iMinute = (int)$aMatches[5];
        $iSecond = isset($aMatches[6]) && $aMatches[6] != "" ? (int)$aMatches[6] : 0;
        if ($iHour > 23 || $iMinute > 59 || $iSecond > 59) {
            sendJsonAndExit(array("success" => false, "message" => "Due date is invalid."), 400);
        }
        return sprintf("%04d-%02d-%02d %02d:%02d:%02d", (int)$aMatches[1], (int)$aMatches[2], (int)$aMatches[3], $iHour, $iMinute, $iSecond);
    }
    return sprintf("%04d-%02d-%02d", (int)$aMatches[1], (int)$aMatches[2], (int)$aMatches[3]);
}

function issueTrackerRenderBadge($sClass, $sText) {
    return "<span class=\"issue-badge " . html($sClass) . "\">" . html($sText) . "</span>";
}

function issueTrackerFetchRows($oPdo) {
    $aRows = array();
    $oStatement = $oPdo->query("SELECT id, issue_type, status, priority, title, description, CASE WHEN due_date IS NULL THEN NULL WHEN TIME(due_date) = '00:00:00' THEN DATE_FORMAT(due_date, '%Y-%m-%d') ELSE DATE_FORMAT(due_date, '%Y-%m-%d %H:%i') END AS due_date_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') AS created_at_text, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') AS updated_at_text, DATE_FORMAT(closed_at, '%Y-%m-%d %H:%i') AS closed_at_text FROM fs_issues ORDER BY status = 'done' ASC, FIELD(status, 'open', 'in_progress', 'waiting', 'done'), due_date IS NULL ASC, due_date ASC, FIELD(priority, 'urgent', 'high', 'normal', 'low'), created_at DESC, id DESC");
    while ($aRow = $oStatement->fetch()) {
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

function issueTrackerDueDateIsOverdue($sDueDate) {
    $sDueDate = trim((string)$sDueDate);
    if ($sDueDate == "") {
        return false;
    }
    if (strlen($sDueDate) > 16) {
        return $sDueDate < date("Y-m-d H:i:s");
    }
    if (strlen($sDueDate) > 10) {
        return $sDueDate < date("Y-m-d H:i");
    }
    return $sDueDate < date("Y-m-d");
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
    if ($aRow["status"] != "done" && issueTrackerDueDateIsOverdue($sDueDate)) {
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
        . "<td class=\"issue-date" . $sDueClass . "\">" . ($sDueDate != "" ? renderDateTimeWithNbspIndent($sDueDate) : "&mdash;") . "</td>"
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
            if (!$oStatement->fetch()) {
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
        $aRow = $oStatement->fetch();
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

function domainLookupNormalizeDomain($sValue) {
    $sValue = trim((string)$sValue);
    if ($sValue == "") {
        return "";
    }
    if (strpos($sValue, "://") !== false) {
        $sHost = parse_url($sValue, PHP_URL_HOST);
        if (is_string($sHost) && $sHost != "") {
            $sValue = $sHost;
        }
    } elseif (substr($sValue, 0, 2) == "//") {
        $sHost = parse_url("https:" . $sValue, PHP_URL_HOST);
        if (is_string($sHost) && $sHost != "") {
            $sValue = $sHost;
        }
    } else {
        $sValue = preg_replace("~[/?#].*$~", "", $sValue);
    }
    $sValue = trim($sValue);
    if (preg_match("/^(.+):[0-9]+$/", $sValue, $aMatches)) {
        $sValue = $aMatches[1];
    }
    $sValue = trim($sValue, ". \t\r\n");
    $sValue = strtolower($sValue);
    if ($sValue == "" || strlen($sValue) > 253 || preg_match("/\\s/", $sValue)) {
        return "";
    }
    return $sValue;
}

function domainLookupNormalizeValue($sValue) {
    $sValue = trim((string)$sValue);
    if ($sValue == "") {
        return "";
    }
    if (strpos($sValue, "://") !== false) {
        $sHost = parse_url($sValue, PHP_URL_HOST);
        if (is_string($sHost) && $sHost != "") {
            $sValue = $sHost;
        }
    } elseif (substr($sValue, 0, 2) == "//") {
        $sHost = parse_url("https:" . $sValue, PHP_URL_HOST);
        if (is_string($sHost) && $sHost != "") {
            $sValue = $sHost;
        }
    } else {
        $sValue = preg_replace("~[/?#].*$~", "", $sValue);
    }
    $sValue = trim($sValue);
    $sIpValue = trim($sValue, "[] \t\r\n");
    if (domainLookupIpAddressIsValid($sIpValue)) {
        return strtolower($sIpValue);
    }
    if (preg_match("/^(.+):[0-9]+$/", $sIpValue, $aMatches) && domainLookupIpAddressIsValid($aMatches[1])) {
        return strtolower($aMatches[1]);
    }
    return domainLookupNormalizeDomain($sValue);
}

function domainLookupIpAddressIsValid($sValue) {
    return filter_var(trim((string)$sValue), FILTER_VALIDATE_IP) !== false;
}

function domainLookupSupportedTlds() {
    return array(
        "com" => true,
        "me" => true,
        "net" => true,
        "org" => true,
        "sh" => true,
        "io" => true,
        "co" => true,
        "club" => true,
        "biz" => true,
        "mobi" => true,
        "info" => true,
        "us" => true,
        "domains" => true,
        "cloud" => true,
        "fr" => true,
        "au" => true,
        "ru" => true,
        "uk" => true,
        "nl" => true,
        "fi" => true,
        "br" => true,
        "hr" => true,
        "ee" => true,
        "ca" => true,
        "sk" => true,
        "se" => true,
        "no" => true,
        "cz" => true,
        "it" => true,
        "in" => true,
        "icu" => true,
        "top" => true,
        "xyz" => true,
        "cn" => true,
        "cf" => true,
        "hk" => true,
        "sg" => true,
        "pt" => true,
        "site" => true,
        "kz" => true,
        "si" => true,
        "ae" => true,
        "do" => true,
        "yoga" => true,
        "xxx" => true,
        "ws" => true,
        "work" => true,
        "wiki" => true,
        "watch" => true,
        "wtf" => true,
        "world" => true,
        "website" => true,
        "vip" => true,
        "ly" => true,
        "network" => true,
        "company" => true,
        "rs" => true,
        "run" => true,
        "science" => true,
        "sex" => true,
        "shop" => true,
        "solutions" => true,
        "so" => true,
        "studio" => true,
        "style" => true,
        "tech" => true,
        "travel" => true,
        "vc" => true,
        "pub" => true,
        "pro" => true,
        "press" => true,
        "ooo" => true,
        "de" => true
    );
}

function domainLookupDomainShapeIsValid($sDomain) {
    $sDomain = trim((string)$sDomain);
    if ($sDomain == "" || strlen($sDomain) > 253 || strpos($sDomain, ".") === false) {
        return false;
    }
    if (!preg_match("/^[a-z0-9.-]+$/", $sDomain)) {
        return false;
    }
    $aLabels = explode(".", $sDomain);
    foreach ($aLabels as $sLabel) {
        if ($sLabel == "" || strlen($sLabel) > 63 || substr($sLabel, 0, 1) == "-" || substr($sLabel, -1) == "-") {
            return false;
        }
    }
    return true;
}

function domainLookupDomainTldIsSupported($sDomain) {
    $iPosition = strrpos((string)$sDomain, ".");
    if ($iPosition === false) {
        return false;
    }
    $sTld = substr((string)$sDomain, $iPosition + 1);
    $aSupportedTlds = domainLookupSupportedTlds();
    return isset($aSupportedTlds[$sTld]);
}

function domainLookupDnsRecordTypes() {
    $aConstants = array(
        "A" => "DNS_A",
        "AAAA" => "DNS_AAAA",
        "CNAME" => "DNS_CNAME",
        "MX" => "DNS_MX",
        "NS" => "DNS_NS",
        "TXT" => "DNS_TXT",
        "SOA" => "DNS_SOA",
        "SRV" => "DNS_SRV",
        "CAA" => "DNS_CAA"
    );
    $aTypes = array();
    foreach ($aConstants as $sType => $sConstant) {
        if (defined($sConstant)) {
            $aTypes[$sType] = constant($sConstant);
        }
    }
    return $aTypes;
}

function domainLookupDnsRecordValue($aRecord) {
    $sType = isset($aRecord["type"]) ? strtoupper((string)$aRecord["type"]) : "";
    $aParts = array();
    $aFallbackParts = array();
    if ($sType == "A" && isset($aRecord["ip"])) {
        return (string)$aRecord["ip"];
    }
    if ($sType == "AAAA" && isset($aRecord["ipv6"])) {
        return (string)$aRecord["ipv6"];
    }
    if (($sType == "CNAME" || $sType == "NS" || $sType == "PTR") && isset($aRecord["target"])) {
        return (string)$aRecord["target"];
    }
    if ($sType == "MX") {
        if (isset($aRecord["pri"])) {
            $aParts[] = "priority " . (int)$aRecord["pri"];
        }
        if (isset($aRecord["target"])) {
            $aParts[] = (string)$aRecord["target"];
        }
        return implode(" ", $aParts);
    }
    if ($sType == "TXT") {
        if (isset($aRecord["entries"]) && is_array($aRecord["entries"])) {
            return implode("\n", $aRecord["entries"]);
        }
        if (isset($aRecord["txt"])) {
            return (string)$aRecord["txt"];
        }
    }
    if ($sType == "SOA") {
        foreach (array("mname", "rname", "serial", "refresh", "retry", "expire", "minimum-ttl") as $sKey) {
            if (isset($aRecord[$sKey])) {
                $aParts[] = $sKey . ": " . (string)$aRecord[$sKey];
            }
        }
        return implode("\n", $aParts);
    }
    if ($sType == "SRV") {
        foreach (array("pri", "weight", "port", "target") as $sKey) {
            if (isset($aRecord[$sKey])) {
                $aParts[] = $sKey . ": " . (string)$aRecord[$sKey];
            }
        }
        return implode("\n", $aParts);
    }
    if ($sType == "CAA") {
        foreach (array("flags", "tag", "value") as $sKey) {
            if (isset($aRecord[$sKey])) {
                $aParts[] = $sKey . ": " . (string)$aRecord[$sKey];
            }
        }
        return implode("\n", $aParts);
    }
    foreach ($aRecord as $sKey => $mValue) {
        if ($sKey == "host" || $sKey == "class" || $sKey == "type" || $sKey == "ttl") {
            continue;
        }
        if (is_array($mValue)) {
            $aFallbackParts[] = $sKey . ": " . implode(", ", $mValue);
        } elseif ($mValue !== null) {
            $aFallbackParts[] = $sKey . ": " . (string)$mValue;
        }
    }
    return implode("\n", $aFallbackParts);
}

function domainLookupAddDnsQuery(&$aQueries, $sHost, $aTypes) {
    if (!isset($aQueries[$sHost])) {
        $aQueries[$sHost] = array();
    }
    foreach ($aTypes as $sType) {
        $aQueries[$sHost][$sType] = true;
    }
}

function domainLookupDnsGuessedQueries($sDomain) {
    $aQueries = array();
    foreach (array("www", "mail", "smtp", "imap", "pop", "pop3", "webmail", "autodiscover", "autoconfig", "mta-sts") as $sPrefix) {
        domainLookupAddDnsQuery($aQueries, $sPrefix . "." . $sDomain, array("A", "AAAA", "CNAME"));
    }
    foreach (array("_dmarc", "_domainkey", "_mta-sts", "_smtp._tls", "default._bimi", "_acme-challenge") as $sPrefix) {
        domainLookupAddDnsQuery($aQueries, $sPrefix . "." . $sDomain, array("TXT"));
    }
    foreach (array("default", "dkim", "dkim1", "dkim2", "google", "k1", "mail", "selector1", "selector2", "s1", "s2", "smtp") as $sSelector) {
        domainLookupAddDnsQuery($aQueries, $sSelector . "._domainkey." . $sDomain, array("TXT", "CNAME"));
    }
    foreach (array("_autodiscover._tcp", "_caldav._tcp", "_caldavs._tcp", "_carddav._tcp", "_carddavs._tcp", "_imaps._tcp", "_pop3s._tcp", "_sip._tcp", "_sip._tls", "_sips._tcp", "_submission._tcp", "_xmpp-client._tcp", "_xmpp-server._tcp") as $sPrefix) {
        domainLookupAddDnsQuery($aQueries, $sPrefix . "." . $sDomain, array("SRV"));
    }
    return $aQueries;
}

function domainLookupAddDnsRecordRows(&$aRows, &$aSeenRecords, $sHost, $sType, $iType) {
    $fStart = microtime(true);
    $aRecords = @dns_get_record($sHost, $iType);
    $fElapsed = microtime(true) - $fStart;
    if (!is_array($aRecords)) {
        return $fElapsed;
    }
    foreach ($aRecords as $aRecord) {
        $sRecordType = isset($aRecord["type"]) ? (string)$aRecord["type"] : $sType;
        $sRecordHost = isset($aRecord["host"]) ? (string)$aRecord["host"] : $sHost;
        $sTtl = isset($aRecord["ttl"]) ? (string)$aRecord["ttl"] : "";
        $sValue = domainLookupDnsRecordValue($aRecord);
        $sSeenKey = strtolower($sRecordType) . "\n" . strtolower($sRecordHost) . "\n" . $sValue;
        if (isset($aSeenRecords[$sSeenKey])) {
            continue;
        }
        $aSeenRecords[$sSeenKey] = true;
        $aRows[] = array(
            "type" => $sRecordType,
            "host" => $sRecordHost,
            "ttl" => $sTtl,
            "value" => $sValue
        );
    }
    return $fElapsed;
}

function domainLookupFetchDnsRecords($sDomain) {
    $aRows = array();
    if (!function_exists("dns_get_record")) {
        return array(
            "message" => "PHP DNS lookup is not available.",
            "records" => $aRows
        );
    }
    $aTypes = domainLookupDnsRecordTypes();
    $aSeenRecords = array();
    $sMessage = "";
    $blDisableDnsLookup = false;
    foreach ($aTypes as $sType => $iType) {
        $fElapsed = domainLookupAddDnsRecordRows($aRows, $aSeenRecords, $sDomain, $sType, $iType);
        if ($fElapsed > 10) {
            $sMessage = "DNS lookup was stopped because " . $sType . " query for " . $sDomain . " took " . number_format($fElapsed, 1, ".", "") . " seconds.";
            $blDisableDnsLookup = true;
            break;
        }
    }
    if (!$blDisableDnsLookup) {
        foreach (domainLookupDnsGuessedQueries($sDomain) as $sHost => $aQueryTypes) {
            foreach ($aQueryTypes as $sType => $blEnabled) {
                if (!isset($aTypes[$sType])) {
                    continue;
                }
                $fElapsed = domainLookupAddDnsRecordRows($aRows, $aSeenRecords, $sHost, $sType, $aTypes[$sType]);
                if ($fElapsed > 10) {
                    $sMessage = "DNS lookup was stopped because " . $sType . " query for " . $sHost . " took " . number_format($fElapsed, 1, ".", "") . " seconds.";
                    $blDisableDnsLookup = true;
                    break 2;
                }
            }
        }
    }
    return array(
        "message" => $sMessage,
        "records" => $aRows,
        "disable_dns_lookup" => $blDisableDnsLookup
    );
}

function domainLookupSkippedDnsResult() {
    return array(
        "message" => "DNS lookup was skipped because a previous DNS query for this domain took more than 10 seconds.",
        "records" => array(),
        "disable_dns_lookup" => true
    );
}

function domainLookupRenderDnsValue($mValue) {
    $sValue = trim((string)$mValue);
    return $sValue == "" ? "<em>&mdash;</em>" : nl2br(html($sValue), false);
}

function domainLookupDnsResultRecords($aDnsResult) {
    return isset($aDnsResult["records"]) && is_array($aDnsResult["records"]) ? $aDnsResult["records"] : array();
}

function domainLookupDnsResultMessage($aDnsResult) {
    return isset($aDnsResult["message"]) ? trim((string)$aDnsResult["message"]) : "";
}

function domainLookupRenderDnsRows($aDnsResult) {
    $aRecords = domainLookupDnsResultRecords($aDnsResult);
    foreach ($aRecords as $aRecord) {
        echo "      <tr>\n",
            "        <td>" . domainLookupRenderDnsValue($aRecord["type"]) . "</td>\n",
            "        <td>" . domainLookupRenderDnsValue($aRecord["host"]) . "</td>\n",
            "        <td>" . domainLookupRenderDnsValue($aRecord["ttl"]) . "</td>\n",
            "        <td>" . domainLookupRenderDnsValue($aRecord["value"]) . "</td>\n",
            "      </tr>\n";
    }
}

function domainLookupDateForDatabase($mValue) {
    if (!is_string($mValue) || trim($mValue) == "") {
        return null;
    }
    $iTimestamp = strtotime($mValue);
    if ($iTimestamp === false) {
        return null;
    }
    return date("Y-m-d H:i:s", $iTimestamp);
}

function domainLookupJsonValue($mValue) {
    if ($mValue === null) {
        return null;
    }
    $sJson = json_encode($mValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $sJson === false ? null : $sJson;
}

function domainLookupTextValue($mValue) {
    if ($mValue === null) {
        return null;
    }
    if (is_scalar($mValue)) {
        return (string)$mValue;
    }
    return domainLookupJsonValue($mValue);
}

function domainLookupEmptyResult($sDomain) {
    return array(
        "domain" => $sDomain,
        "result_status" => "error",
        "message" => "",
        "domain_name" => null,
        "reverse_dns" => null,
        "creation_date" => null,
        "expiration_date" => null,
        "name_servers" => null,
        "registrant_name" => null,
        "registrar" => null,
        "status_text" => null,
        "updated_dates" => null,
        "raw_response" => "",
        "http_code" => null,
        "curl_errno" => null,
        "curl_error" => null
    );
}

function domainLookupExtractApiErrorMessage($aData, $iHttpCode) {
    if (isset($aData["details"]) && is_array($aData["details"])) {
        foreach ($aData["details"] as $sDetailName => $mDetailValue) {
            if (is_array($mDetailValue)) {
                foreach ($mDetailValue as $mDetailLine) {
                    if (is_scalar($mDetailLine) && (string)$mDetailLine != "") {
                        return (string)$sDetailName . ": " . (string)$mDetailLine;
                    }
                }
            } elseif (is_scalar($mDetailValue) && (string)$mDetailValue != "") {
                return (string)$sDetailName . ": " . (string)$mDetailValue;
            }
        }
    }
    if (isset($aData["message"]) && is_scalar($aData["message"]) && (string)$aData["message"] != "") {
        return (string)$aData["message"];
    }
    if (isset($aData["error"])) {
        if (is_scalar($aData["error"]) && (string)$aData["error"] != "") {
            return (string)$aData["error"];
        }
        if (is_array($aData["error"])) {
            if (isset($aData["error"]["message"]) && is_scalar($aData["error"]["message"]) && (string)$aData["error"]["message"] != "") {
                return (string)$aData["error"]["message"];
            }
            if (isset($aData["error"]["code"]) && is_scalar($aData["error"]["code"]) && (string)$aData["error"]["code"] != "") {
                return "API error: " . (string)$aData["error"]["code"];
            }
        }
    }
    if (isset($aData["result"]) && is_scalar($aData["result"]) && (string)$aData["result"] != "") {
        return "API result: " . (string)$aData["result"];
    }
    if ($iHttpCode >= 400) {
        return "HTTP error " . $iHttpCode . ".";
    }
    return "API returned an error.";
}

function domainLookupBuildErrorResult($sDomain, $sMessage, $sRawResponse = "", $iHttpCode = null, $iCurlErrno = null, $sCurlError = null) {
    $aResult = domainLookupEmptyResult($sDomain);
    $aResult["message"] = $sMessage;
    $aResult["raw_response"] = (string)$sRawResponse;
    $aResult["http_code"] = $iHttpCode === null ? null : (int)$iHttpCode;
    $aResult["curl_errno"] = $iCurlErrno === null ? null : (int)$iCurlErrno;
    $aResult["curl_error"] = $sCurlError === null ? null : (string)$sCurlError;
    return $aResult;
}

function domainLookupCallApi($sDomain, $sApiKey) {
    if (!function_exists("curl_init")) {
        return domainLookupBuildErrorResult($sDomain, "PHP cURL extension is not available.");
    }
    $oCurl = curl_init();
    curl_setopt_array($oCurl, array(
        CURLOPT_URL => "https://api.apilayer.com/whois/query?domain=" . rawurlencode($sDomain),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/plain",
            "apikey: " . $sApiKey
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET"
    ));
    $sResponse = curl_exec($oCurl);
    $iCurlErrno = curl_errno($oCurl);
    $sCurlError = curl_error($oCurl);
    $iHttpCode = (int)curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
    if ($sResponse === false) {
        return domainLookupBuildErrorResult($sDomain, "cURL error: " . $sCurlError, "", $iHttpCode, $iCurlErrno, $sCurlError);
    }
    $aData = json_decode($sResponse, true);
    if (!is_array($aData)) {
        return domainLookupBuildErrorResult($sDomain, "Invalid API response.", $sResponse, $iHttpCode, $iCurlErrno, $sCurlError);
    }
    if (!isset($aData["result"]) || !is_array($aData["result"])) {
        return domainLookupBuildErrorResult($sDomain, domainLookupExtractApiErrorMessage($aData, $iHttpCode), $sResponse, $iHttpCode, $iCurlErrno, $sCurlError);
    }
    $aWhois = $aData["result"];
    $aResult = domainLookupEmptyResult($sDomain);
    $aResult["result_status"] = "success";
    $aResult["message"] = null;
    $aResult["domain_name"] = isset($aWhois["domain_name"]) && is_scalar($aWhois["domain_name"]) ? strtolower((string)$aWhois["domain_name"]) : null;
    $aResult["creation_date"] = isset($aWhois["creation_date"]) ? domainLookupDateForDatabase($aWhois["creation_date"]) : null;
    $aResult["expiration_date"] = isset($aWhois["expiration_date"]) ? domainLookupDateForDatabase($aWhois["expiration_date"]) : null;
    $aResult["name_servers"] = isset($aWhois["name_servers"]) ? domainLookupJsonValue($aWhois["name_servers"]) : null;
    $aResult["registrant_name"] = isset($aWhois["registrant_name"]) ? domainLookupTextValue($aWhois["registrant_name"]) : (array_key_exists("name", $aWhois) ? domainLookupTextValue($aWhois["name"]) : null);
    $aResult["registrar"] = isset($aWhois["registrar"]) ? domainLookupTextValue($aWhois["registrar"]) : null;
    $aResult["status_text"] = array_key_exists("status", $aWhois) ? domainLookupTextValue($aWhois["status"]) : null;
    $aResult["updated_dates"] = isset($aWhois["updated_date"]) ? domainLookupJsonValue($aWhois["updated_date"]) : null;
    $aResult["raw_response"] = $sResponse;
    $aResult["http_code"] = $iHttpCode;
    $aResult["curl_errno"] = $iCurlErrno;
    $aResult["curl_error"] = $sCurlError == "" ? null : $sCurlError;
    return $aResult;
}

function domainLookupCallIpApi($sIpAddress, $sApiKey) {
    if (!function_exists("curl_init")) {
        return domainLookupBuildErrorResult($sIpAddress, "PHP cURL extension is not available.");
    }
    if (trim((string)$sApiKey) == "") {
        return domainLookupBuildErrorResult($sIpAddress, "API key is missing.");
    }
    $oCurl = curl_init();
    curl_setopt_array($oCurl, array(
        CURLOPT_URL => "https://ip-intelligence.abstractapi.com/v1/?api_key=" . rawurlencode($sApiKey) . "&ip_address=" . rawurlencode($sIpAddress),
        CURLOPT_HTTPHEADER => array(
            "Accept: application/json"
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET"
    ));
    $sResponse = curl_exec($oCurl);
    $iCurlErrno = curl_errno($oCurl);
    $sCurlError = curl_error($oCurl);
    $iHttpCode = (int)curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
    if ($sResponse === false) {
        return domainLookupBuildErrorResult($sIpAddress, "cURL error: " . $sCurlError, "", $iHttpCode, $iCurlErrno, $sCurlError);
    }
    $aData = json_decode($sResponse, true);
    if (!is_array($aData)) {
        return domainLookupBuildErrorResult($sIpAddress, "Invalid API response.", $sResponse, $iHttpCode, $iCurlErrno, $sCurlError);
    }
    if ($iHttpCode >= 400 || isset($aData["error"]) || (isset($aData["code"]) && !isset($aData["ip_address"]))) {
        return domainLookupBuildErrorResult($sIpAddress, domainLookupExtractApiErrorMessage($aData, $iHttpCode), $sResponse, $iHttpCode, $iCurlErrno, $sCurlError);
    }
    if (!isset($aData["ip_address"])) {
        return domainLookupBuildErrorResult($sIpAddress, "Invalid API response.", $sResponse, $iHttpCode, $iCurlErrno, $sCurlError);
    }
    $aResult = domainLookupEmptyResult($sIpAddress);
    $aResult["result_status"] = "success";
    $aResult["message"] = null;
    $aResult["domain_name"] = $sIpAddress;
    $aResult["raw_response"] = $sResponse;
    $aResult["http_code"] = $iHttpCode;
    $aResult["curl_errno"] = $iCurlErrno;
    $aResult["curl_error"] = $sCurlError == "" ? null : $sCurlError;
    return $aResult;
}

function domainLookupFetchRow($oPdo, $sDomain) {
    $oStatement = $oPdo->prepare("SELECT id, domain, result_status, message, domain_name, reverse_dns, DATE_FORMAT(creation_date, '%Y-%m-%d %H:%i:%s') AS creation_date_text, DATE_FORMAT(expiration_date, '%Y-%m-%d %H:%i:%s') AS expiration_date_text, name_servers, registrant_name, registrar, status_text, updated_dates, raw_response, http_code, curl_errno, curl_error, dns_lookup_disabled, UNIX_TIMESTAMP(last_checked_at) AS last_checked_at_ts, DATE_FORMAT(last_checked_at, '%Y-%m-%d %H:%i:%s') AS last_checked_at_text, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at_text, DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') AS updated_at_text FROM fs_domains WHERE domain = :domain LIMIT 1");
    $oStatement->execute(array("domain" => $sDomain));
    $aRow = $oStatement->fetch();
    return $aRow ? $aRow : null;
}

function domainLookupNeedsApiCall($aRow, $iCacheSeconds = 14400) {
    if (!$aRow) {
        return true;
    }
    if (isset($aRow["result_status"]) && (string)$aRow["result_status"] == "success" && isset($aRow["raw_response"])) {
        $aData = json_decode((string)$aRow["raw_response"], true);
        if (is_array($aData) && !isset($aData["result"]) && !isset($aData["ip_address"]) && (isset($aData["code"]) || isset($aData["message"]) || isset($aData["error"]))) {
            return true;
        }
    }
    $iLastCheckedAt = isset($aRow["last_checked_at_ts"]) ? (int)$aRow["last_checked_at_ts"] : 0;
    return $iLastCheckedAt < time() - (int)$iCacheSeconds;
}

function domainLookupSaveResult($oPdo, $aResult) {
    $oStatement = $oPdo->prepare("INSERT INTO fs_domains (domain, result_status, message, domain_name, reverse_dns, creation_date, expiration_date, name_servers, registrant_name, registrar, status_text, updated_dates, raw_response, http_code, curl_errno, curl_error, last_checked_at) VALUES (:domain, :result_status, :message, :domain_name, :reverse_dns, :creation_date, :expiration_date, :name_servers, :registrant_name, :registrar, :status_text, :updated_dates, :raw_response, :http_code, :curl_errno, :curl_error, CURRENT_TIMESTAMP(6)) ON DUPLICATE KEY UPDATE result_status = VALUES(result_status), message = VALUES(message), domain_name = VALUES(domain_name), reverse_dns = VALUES(reverse_dns), creation_date = VALUES(creation_date), expiration_date = VALUES(expiration_date), name_servers = VALUES(name_servers), registrant_name = VALUES(registrant_name), registrar = VALUES(registrar), status_text = VALUES(status_text), updated_dates = VALUES(updated_dates), raw_response = VALUES(raw_response), http_code = VALUES(http_code), curl_errno = VALUES(curl_errno), curl_error = VALUES(curl_error), last_checked_at = VALUES(last_checked_at)");
    $oStatement->execute(array(
        "domain" => $aResult["domain"],
        "result_status" => $aResult["result_status"],
        "message" => $aResult["message"],
        "domain_name" => $aResult["domain_name"],
        "reverse_dns" => $aResult["reverse_dns"],
        "creation_date" => $aResult["creation_date"],
        "expiration_date" => $aResult["expiration_date"],
        "name_servers" => $aResult["name_servers"],
        "registrant_name" => $aResult["registrant_name"],
        "registrar" => $aResult["registrar"],
        "status_text" => $aResult["status_text"],
        "updated_dates" => $aResult["updated_dates"],
        "raw_response" => $aResult["raw_response"],
        "http_code" => $aResult["http_code"],
        "curl_errno" => $aResult["curl_errno"],
        "curl_error" => $aResult["curl_error"]
    ));
    return domainLookupFetchRow($oPdo, $aResult["domain"]);
}

function domainLookupDisableDnsLookup($oPdo, $sDomain) {
    $oStatement = $oPdo->prepare("UPDATE fs_domains SET dns_lookup_disabled = 1 WHERE domain = :domain LIMIT 1");
    $oStatement->execute(array("domain" => $sDomain));
}

function domainLookupRenderStoredList($sJson) {
    $sJson = (string)$sJson;
    if ($sJson == "") {
        return "";
    }
    $aValues = json_decode($sJson, true);
    if (json_last_error() == JSON_ERROR_NONE && (is_scalar($aValues) || $aValues === null)) {
        return $aValues === null ? "" : (string)$aValues;
    }
    if (!is_array($aValues)) {
        return $sJson;
    }
    if (!$aValues) {
        return "";
    }
    $aLines = array();
    foreach ($aValues as $mValue) {
        if (is_scalar($mValue) || $mValue === null) {
            $aLines[] = $mValue === null ? "" : (string)$mValue;
        } else {
            $sEncoded = json_encode($mValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $aLines[] = $sEncoded === false ? "" : $sEncoded;
        }
    }
    return implode("\n", $aLines);
}

function domainLookupRenderValue($mValue) {
    $sValue = trim((string)$mValue);
    return $sValue == "" ? "<em>&mdash;</em>" : html($sValue);
}

function domainLookupRawWhoisResult($aRow) {
    if (isset($aRow["result_status"]) && (string)$aRow["result_status"] != "success") {
        return array();
    }
    if (!isset($aRow["raw_response"]) || trim((string)$aRow["raw_response"]) == "") {
        return array();
    }
    $aData = json_decode((string)$aRow["raw_response"], true);
    if (!is_array($aData)) {
        return array();
    }
    if (isset($aData["result"]) && is_array($aData["result"])) {
        return $aData["result"];
    }
    return $aData;
}

function domainLookupRawWhoisTextValue($mValue) {
    if ($mValue === null) {
        return "";
    }
    if (is_bool($mValue)) {
        return $mValue ? "true" : "false";
    }
    if (is_scalar($mValue)) {
        return (string)$mValue;
    }
    if (!is_array($mValue) || !$mValue) {
        return "";
    }
    if (array_keys($mValue) === range(0, count($mValue) - 1)) {
        $aLines = array();
        foreach ($mValue as $mLineValue) {
            if ($mLineValue === null) {
                $aLines[] = "";
            } elseif (is_bool($mLineValue)) {
                $aLines[] = $mLineValue ? "true" : "false";
            } elseif (is_scalar($mLineValue)) {
                $aLines[] = (string)$mLineValue;
            } else {
                $sJson = json_encode($mLineValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $aLines[] = $sJson === false ? "" : $sJson;
            }
        }
        return implode("\n", $aLines);
    }
    $aLines = array();
    foreach ($mValue as $sName => $mLineValue) {
        $sLineValue = domainLookupRawWhoisTextValue($mLineValue);
        if ($sLineValue == "") {
            continue;
        }
        $aLines[] = domainLookupWhoisFieldLabel($sName) . ": " . str_replace("\n", "\n" . domainLookupWhoisFieldLabel($sName) . ": ", $sLineValue);
    }
    return implode("\n", $aLines);
}

function domainLookupRawWhoisValue($aWhois, $sName) {
    if (!array_key_exists($sName, $aWhois)) {
        return "";
    }
    return domainLookupRawWhoisTextValue($aWhois[$sName]);
}

function domainLookupKnownWhoisFields() {
    return array(
        "result" => array("name" => "Result", "multiline" => false, "date_time" => false),
        "message" => array("name" => "Message", "multiline" => false, "date_time" => false),
        "ip_address" => array("name" => "IP Address", "multiline" => false, "date_time" => false),
        "security" => array("name" => "Security", "multiline" => true, "date_time" => false),
        "asn" => array("name" => "ASN", "multiline" => true, "date_time" => false),
        "company" => array("name" => "Company", "multiline" => true, "date_time" => false),
        "domains" => array("name" => "Domains", "multiline" => true, "date_time" => false),
        "location" => array("name" => "Location", "multiline" => true, "date_time" => false),
        "timezone" => array("name" => "Timezone", "multiline" => true, "date_time" => false),
        "flag" => array("name" => "Flag", "multiline" => true, "date_time" => false),
        "currency" => array("name" => "Currency", "multiline" => true, "date_time" => false),
        "domain_name" => array("name" => "API Domain", "multiline" => false, "date_time" => false),
        "registrar" => array("name" => "Registrar", "multiline" => false, "date_time" => false),
        "whois_server" => array("name" => "WHOIS Server", "multiline" => false, "date_time" => false),
        "registrant_name" => array("name" => "Registrant", "multiline" => false, "date_time" => false),
        "name" => array("name" => "Name", "multiline" => false, "date_time" => false),
        "org" => array("name" => "Organization", "multiline" => false, "date_time" => false),
        "address" => array("name" => "Address", "multiline" => true, "date_time" => false),
        "city" => array("name" => "City", "multiline" => false, "date_time" => false),
        "region" => array("name" => "Region", "multiline" => false, "date_time" => false),
        "region_iso_code" => array("name" => "Region ISO Code", "multiline" => false, "date_time" => false),
        "state" => array("name" => "State", "multiline" => false, "date_time" => false),
        "registrant_postal_code" => array("name" => "Postal Code", "multiline" => false, "date_time" => false),
        "postal_code" => array("name" => "Postal Code", "multiline" => false, "date_time" => false),
        "country" => array("name" => "Country", "multiline" => false, "date_time" => false),
        "country_code" => array("name" => "Country Code", "multiline" => false, "date_time" => false),
        "continent" => array("name" => "Continent", "multiline" => false, "date_time" => false),
        "continent_code" => array("name" => "Continent Code", "multiline" => false, "date_time" => false),
        "latitude" => array("name" => "Latitude", "multiline" => false, "date_time" => false),
        "longitude" => array("name" => "Longitude", "multiline" => false, "date_time" => false),
        "connection" => array("name" => "Connection", "multiline" => true, "date_time" => false),
        "dnssec" => array("name" => "DNSSEC", "multiline" => false, "date_time" => false),
        "emails" => array("name" => "Emails", "multiline" => true, "date_time" => false),
        "referral_url" => array("name" => "Referral URL", "multiline" => false, "date_time" => false),
        "creation_date" => array("name" => "Creation Date", "multiline" => false, "date_time" => true),
        "updated_date" => array("name" => "Updated Date", "multiline" => true, "date_time" => true),
        "expiration_date" => array("name" => "Expiration Date", "multiline" => false, "date_time" => true),
        "name_servers" => array("name" => "Name Servers", "multiline" => true, "date_time" => false),
        "status" => array("name" => "Status", "multiline" => true, "date_time" => false),
        "error" => array("name" => "Error", "multiline" => true, "date_time" => false),
        "code" => array("name" => "Code", "multiline" => false, "date_time" => false)
    );
}

function domainLookupWhoisFieldLabel($sName) {
    $sLabel = ucwords(str_replace("_", " ", (string)$sName));
    return strtr($sLabel, array(
        "DNSSEC" => "DNSSEC",
        "Dnssec" => "DNSSEC",
        "Dns" => "DNS",
        "Url" => "URL",
        "Api" => "API",
        "Idn" => "IDN",
        "Id" => "ID",
        "Ip" => "IP",
        "Tld" => "TLD",
        "Whois" => "WHOIS"
    ));
}

function domainLookupRenderResultRow($sName, $mValue, $blMultiline, $blDateTime) {
    $sValue = trim(domainLookupRawWhoisTextValue($mValue));
    if ($sValue == "") {
        $sHtmlValue = domainLookupRenderValue($sValue);
    } elseif ($blDateTime && $blMultiline) {
        $aDateTimeHtmlLines = array();
        foreach (explode("\n", $sValue) as $sDateTimeLine) {
            $aDateTimeHtmlLines[] = renderDateTimeWithNbspIndent($sDateTimeLine);
        }
        $sHtmlValue = implode("<br>", $aDateTimeHtmlLines);
    } elseif ($blDateTime) {
        $sHtmlValue = renderDateTimeWithNbspIndent($sValue);
    } else {
        $sHtmlValue = html($sValue);
    }
    if ($sValue != "" && $blMultiline && !$blDateTime) {
        $sHtmlValue = nl2br($sHtmlValue, false);
    }
    echo "      <tr>\n",
        "        <th>" . html($sName) . "</th>\n",
        "        <td>" . $sHtmlValue . "</td>\n",
        "      </tr>\n";
}

function domainLookupRenderResultRows($aRow, $sSource) {
    $aRawWhois = domainLookupRawWhoisResult($aRow);
    $aKnownFields = domainLookupKnownWhoisFields();
    $aRenderedFields = array();
    foreach ($aKnownFields as $sName => $aField) {
        if (!array_key_exists($sName, $aRawWhois)) {
            continue;
        }
        domainLookupRenderResultRow($aField["name"], $aRawWhois[$sName], $aField["multiline"], $aField["date_time"]);
        $aRenderedFields[$sName] = true;
    }
    foreach ($aRawWhois as $sName => $mValue) {
        if (isset($aRenderedFields[$sName])) {
            continue;
        }
        domainLookupRenderResultRow(domainLookupWhoisFieldLabel($sName), $mValue, true, false);
        $aRenderedFields[$sName] = true;
    }
    if (!$aRenderedFields) {
        $aRows = array(
            "Result" => array("value" => $aRow["result_status"], "multiline" => false, "date_time" => false),
            "Message" => array("value" => $aRow["message"], "multiline" => false, "date_time" => false)
        );
        foreach ($aRows as $sName => $aValue) {
            domainLookupRenderResultRow($sName, $aValue["value"], $aValue["multiline"], $aValue["date_time"]);
        }
    }
}
