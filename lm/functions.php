<?php

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

function getLmRequestHeaders() {
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

function getLmRequestPlainTextInfo() {
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
    foreach (getLmRequestHeaders() as $sHeaderName => $sHeaderValue) {
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

function menuAdminNormalizePath($sPath) {
    return normalizeMenuPath($sPath);
}

function menuAdminDisplayPath($sPath) {
    $sPath = menuAdminNormalizePath($sPath);
    return $sPath == "" ? "/" : "/" . $sPath;
}

function menuAdminPathIsValid($sPath) {
    $sPath = menuAdminNormalizePath($sPath);
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

function menuAdminGroupKey($sPath) {
    $sPath = menuAdminNormalizePath($sPath);
    if ($sPath == "") {
        return "";
    }
    $aParts = explode("/", $sPath);
    if (count($aParts) > 1 || strpos($aParts[0], ".") === false) {
        return $aParts[0];
    }
    return "";
}

function menuAdminGroupLabel($sGroupKey) {
    return $sGroupKey == "" ? "/" : "/" . $sGroupKey . "/";
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
            "group_label" => menuAdminGroupLabel($sGroupKey),
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
        . "<td>" . ((int)$aRow["is_active"] == 1 ? "Yes" : "No") . "</td>"
        . "<td>" . ($aRow["separator"] ? "Yes" : "No") . "</td>"
        . "<td class=\"admin-action-column\"><a href=\"#\" class=\"item-action js-move-menu-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-move-menu-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a></td>"
        . "<td class=\"admin-action-column\"><a href=\"#\" class=\"item-action js-edit-menu-item\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-menu-item\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a></td>"
        . "</tr>\n";
}

function menuAdminRenderTableStart($sCaption) {
    return "      <table class=\"menu-admin-table\">\n"
        . "        <caption>" . html($sCaption) . "</caption>\n"
        . "        <colgroup><col class=\"menu-col-path\"><col class=\"menu-col-icon\"><col class=\"menu-col-name\"><col class=\"menu-col-title\"><col class=\"menu-col-target\"><col class=\"menu-col-active\"><col class=\"menu-col-separator\"><col class=\"menu-col-order\"><col class=\"menu-col-actions\"></colgroup>\n"
        . "        <thead><tr><th>Path</th><th>Icon</th><th>Name</th><th>Title</th><th>Target</th><th>Active</th><th>Separator</th><th class=\"admin-action-column\">Order</th><th class=\"admin-action-column\"></th></tr></thead>\n";
}

function menuAdminRenderTables($oPdo) {
    $aRows = menuAdminFetchRows($oPdo);
    if (!$aRows) {
        return menuAdminRenderTableStart("/")
            . "        <tbody><tr><td colspan=\"9\" class=\"empty-table-message\">No menu items found.</td></tr></tbody>\n"
            . "      </table>\n";
    }
    $sHtml = "";
    $sCurrentGroup = null;
    foreach ($aRows as $aRow) {
        if ($sCurrentGroup !== $aRow["group_key"]) {
            if ($sCurrentGroup !== null) {
                $sHtml .= "        </tbody>\n      </table>\n";
            }
            $sCurrentGroup = $aRow["group_key"];
            $sHtml .= menuAdminRenderTableStart($aRow["group_label"])
                . "        <tbody>\n";
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
