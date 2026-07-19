<?php

function isTrustedClient($aAllowedIps) {
    global $sTrustedUserAgent, $sTrustedAcceptLanguage;

    if (!isAllowedIp($aAllowedIps) || $sTrustedUserAgent == "" || $sTrustedAcceptLanguage == "") {
        return false;
    }
    if (!isset($_SERVER["HTTP_USER_AGENT"], $_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
        return false;
    }
    return hash_equals($sTrustedUserAgent, (string)$_SERVER["HTTP_USER_AGENT"]) && hash_equals($sTrustedAcceptLanguage, (string)$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
}

function isAuthenticatedClient() {
    return (isset($_SESSION["kf_view_auth"], $_SESSION["kf_auth_user_id"])
            && $_SESSION["kf_view_auth"] === true
            && (int)$_SESSION["kf_auth_user_id"] > 0)
        || (isset($_SESSION["ex_view_auth"], $_SESSION["ex_auth_user_id"])
            && $_SESSION["ex_view_auth"] === true
            && (int)$_SESSION["ex_auth_user_id"] > 0);
}

function requireFullAccess($aAllowedIps) {
    if (isTrustedClient($aAllowedIps)) {
        return;
    }

    send403AndExit();
}

function getCurrentScriptName() {
    $sScriptName = isset($_SERVER["SCRIPT_NAME"]) ? basename((string)$_SERVER["SCRIPT_NAME"]) : "index.php";
    return $sScriptName != "" ? $sScriptName : "index.php";
}

function normalizeMenuPath($sPath) {
    $sPath = str_replace("\\", "/", trim((string)$sPath));
    $sPath = preg_replace("#/+#", "/", $sPath);
    $sPath = preg_replace("#^/+#", "", $sPath);
    return $sPath;
}

function encodeMenuPath($sPath) {
    $aParts = explode("/", normalizeMenuPath($sPath));
    $aEncodedParts = array();
    foreach ($aParts as $sPart) {
        $aEncodedParts[] = rawurlencode($sPart);
    }
    return implode("/", $aEncodedParts);
}

function getMenuPathPrefix() {
    $sScriptFile = isset($_SERVER["SCRIPT_FILENAME"]) ? (string)$_SERVER["SCRIPT_FILENAME"] : __FILE__;
    $sScriptFile = str_replace("\\", "/", $sScriptFile);
    $sScriptDirectory = dirname($sScriptFile);
    return normalizeMenuPath(basename(dirname($sScriptDirectory)) . "/" . basename($sScriptDirectory)) . "/";
}

function getCurrentMenuPath() {
    $sScriptName = getCurrentScriptName();
    return $sScriptName == "index.php" ? getMenuPathPrefix() : getMenuPathPrefix() . $sScriptName;
}

function getMenuItems() {
    global $oPdo;

    $aItems = array();
    if (!$oPdo) {
        return $aItems;
    }
    $sPathPrefix = getMenuPathPrefix();
    $oStatement = $oPdo->prepare("SELECT id, path, icon, name, title, target, `order` AS menu_order FROM kf_menu WHERE is_active = 1 AND path LIKE :path_prefix ORDER BY `order` ASC, id ASC");
    $oStatement->execute(array("path_prefix" => $sPathPrefix . "%"));
    while ($aRow = $oStatement->fetch()) {
        $sPath = normalizeMenuPath($aRow["path"]);
        if (strpos($sPath, $sPathPrefix) !== 0) {
            continue;
        }
        $aItems[] = array(
            "id" => (int)$aRow["id"],
            "path" => $sPath,
            "relative_path" => substr($sPath, strlen($sPathPrefix)),
            "icon" => (string)$aRow["icon"],
            "name" => (string)$aRow["name"],
            "title" => (string)$aRow["title"],
            "target" => (string)$aRow["target"],
            "order" => (int)$aRow["menu_order"]
        );
    }
    return $aItems;
}

function getPageTitle($sFallbackTitle) {
    global $oPdo;
    global $aAllowedIps;

    $sTitle = $sFallbackTitle;
    if (!$oPdo) {
        return getPageTitleText($sTitle, $aAllowedIps);
    }
    $oStatement = $oPdo->prepare("SELECT name FROM kf_menu WHERE is_active = 1 AND path = :path LIMIT 1");
    $oStatement->execute(array("path" => getCurrentMenuPath()));
    $sMenuTitle = trim((string)$oStatement->fetchColumn());
    $sTitle = $sMenuTitle != "" ? $sMenuTitle : $sFallbackTitle;
    return getPageTitleText($sTitle, $aAllowedIps);
}

function getPageTitleText($sTitle, $aAllowedIps) {
    $aStates = array();
    if (isTrustedClient($aAllowedIps)) {
        $aStates[] = "Trusted";
    }
    if (isAuthenticatedClient()) {
        $aStates[] = "Authenticated";
    }
    if (count($aStates) > 0) {
        $sTitle .= " - " . implode(" + ", $aStates);
    }
    return $sTitle;
}

function renderMenu() {
    global $sBaseUrl, $sMenuEmoji;

    $aItems = getMenuItems();
    if (!$aItems) {
        return;
    }
    $sCurrentPath = getCurrentMenuPath();
    echo "    <span class=\"kf-menu\" data-kf-menu>\n"
        . "      <button type=\"button\" class=\"kf-menu-button\" data-kf-menu-button aria-haspopup=\"true\" aria-expanded=\"false\" title=\"Menu\" aria-label=\"Menu\">" . $sMenuEmoji . "</button>\n"
        . "      <span class=\"kf-menu-panel\" data-kf-menu-panel hidden>\n";
    foreach ($aItems as $aItem) {
        $sClass = "kf-menu-link";
        $sCurrent = "";
        if ($aItem["path"] === $sCurrentPath) {
            $sClass .= " kf-menu-link-active";
            $sCurrent = " aria-current=\"page\"";
        }
        $sTitle = trim((string)$aItem["title"]);
        $sTarget = trim((string)$aItem["target"]);
        $sTitleAttribute = $sTitle != "" ? " title=\"" . html($sTitle) . "\"" : "";
        $sTargetAttribute = $sTarget != "" ? " target=\"" . html($sTarget) . "\"" : "";
        $sRelAttribute = $sTarget == "_blank" ? " rel=\"noopener noreferrer\"" : "";
        echo "        <a class=\"" . html($sClass) . "\" href=\"" . html($sBaseUrl . encodeMenuPath($aItem["relative_path"])) . "\"" . $sTitleAttribute . $sTargetAttribute . $sRelAttribute . $sCurrent . "><span class=\"kf-menu-icon\" aria-hidden=\"true\">" . html($aItem["icon"]) . "</span><span class=\"kf-menu-text\">" . html($aItem["name"]) . "</span></a>\n";
    }
    echo "      </span>\n"
        . "    </span>\n";
}

function getCsrfToken() {
    if (!isset($_SESSION["kf_csrf_token"]) || !is_string($_SESSION["kf_csrf_token"]) || $_SESSION["kf_csrf_token"] == "") {
        $_SESSION["kf_csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["kf_csrf_token"];
}

function requireCsrfToken() {
    $sToken = isset($_POST["kf_csrf_token"]) ? (string)$_POST["kf_csrf_token"] : "";
    $sSessionToken = isset($_SESSION["kf_csrf_token"]) ? (string)$_SESSION["kf_csrf_token"] : "";
    if ($sToken == "" || $sSessionToken == "" || !hash_equals($sSessionToken, $sToken)) {
        send500AndExit("Invalid form token.");
    }
}

function setMessage($sMessage, $sType = "success") {
    $_SESSION["kf_message"] = array("message" => $sMessage, "type" => $sType);
}

function renderMessage() {
    if (!isset($_SESSION["kf_message"]) || !is_array($_SESSION["kf_message"])) {
        return;
    }
    $aMessage = $_SESSION["kf_message"];
    unset($_SESSION["kf_message"]);
    $sType = isset($aMessage["type"]) && $aMessage["type"] == "error" ? "error" : "success";
    echo "  <p class=\"message-box message-" . html($sType) . "\">" . html($aMessage["message"]) . "</p>\n";
}

function redirect($sPath) {
    sendSecurityHeaders();
    header("Location: " . $sPath, true, 303);
    exit;
}

function parseAmount($sValue) {
    $sValue = str_replace(array(" ", ",", "−"), array("", ".", "-"), trim((string)$sValue));
    return is_numeric($sValue) ? (float)$sValue : null;
}

function formatAmount($mAmount) {
    $fAmount = round((float)$mAmount, 2);
    $sAmount = number_format(abs($fAmount), 2, ".", " ");
    return $fAmount < 0 ? "−" . $sAmount : $sAmount;
}

function formatDate($sDate) {
    $iTime = strtotime((string)$sDate);
    return $iTime ? date("Y-m-d", $iTime) : "";
}

function monthLabel($sMonth) {
    $iTime = strtotime($sMonth . "-01");
    return $iTime ? date("F Y", $iTime) : $sMonth;
}

function getFinanceTypes($blIncludeGroups = false) {
    global $oPdo;

    $sWhere = $blIncludeGroups ? "" : "WHERE type_kind IN ('income', 'expense')";
    $oStatement = $oPdo->query("SELECT id, type_kind, name FROM kf_fin_types " . $sWhere . " ORDER BY FIELD(type_kind, 'income', 'expense', 'group'), name ASC, id ASC");
    return $oStatement->fetchAll();
}

function getFinanceTypeOptionsHtml($iSelectedId = 0) {
    $sHtml = "";
    foreach (getFinanceTypes(false) as $aType) {
        $sLabel = ($aType["type_kind"] == "income" ? "Income: " : "Expense: ") . $aType["name"];
        $sHtml .= "          <option value=\"" . (int)$aType["id"] . "\"" . ((int)$aType["id"] == $iSelectedId ? " selected" : "") . ">" . html($sLabel) . "</option>\n";
    }
    return $sHtml;
}

