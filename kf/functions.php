<?php

function kfIsAllowedIp($aAllowedIps) {
    return isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $aAllowedIps, true);
}

function kfIsTrustedClient($aAllowedIps) {
    global $sTrustedUserAgent, $sTrustedAcceptLanguage;

    $sTrustedUserAgent = isset($sTrustedUserAgent) ? (string)$sTrustedUserAgent : "";
    $sTrustedAcceptLanguage = isset($sTrustedAcceptLanguage) ? (string)$sTrustedAcceptLanguage : "";
    if (!kfIsAllowedIp($aAllowedIps) || $sTrustedUserAgent == "" || $sTrustedAcceptLanguage == "") {
        return false;
    }
    if (!isset($_SERVER["HTTP_USER_AGENT"], $_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
        return false;
    }
    return hash_equals($sTrustedUserAgent, (string)$_SERVER["HTTP_USER_AGENT"])
        && hash_equals($sTrustedAcceptLanguage, (string)$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
}

function kfIsAuthenticatedClient() {
    return (isset($_SESSION["kf_view_auth"], $_SESSION["kf_auth_user_id"])
            && $_SESSION["kf_view_auth"] === true
            && (int)$_SESSION["kf_auth_user_id"] > 0)
        || (isset($_SESSION["ex_view_auth"], $_SESSION["ex_auth_user_id"])
            && $_SESSION["ex_view_auth"] === true
            && (int)$_SESSION["ex_auth_user_id"] > 0);
}

function kfHtml($mValue) {
    return htmlspecialchars((string)$mValue, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function kfHtmlValue($mValue) {
    global $sEmptyValueText;

    $sValue = trim((string)$mValue);
    return $sValue != "" ? kfHtml($sValue) : kfHtml($sEmptyValueText);
}

function kfSendSecurityHeaders() {
    header("X-Content-Type-Options: nosniff", true);
    header("X-Frame-Options: SAMEORIGIN", true);
    header("Referrer-Policy: strict-origin-when-cross-origin", true);
    header("Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=(), serial=(), bluetooth=()", true);
}

function kfSendPageHeaders() {
    $iTime = time();
    $sDate = gmdate("D, d M Y H:i:s", $iTime);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    kfSendSecurityHeaders();
    return $iTime;
}

if (!function_exists("send500AndExit")) {
    function send500AndExit($sMessage) {
        kfSendSecurityHeaders();
        http_response_code(500);
        header("Content-Type: text/html; charset=utf-8", true);
        echo "<!DOCTYPE html>\n<html lang=\"en-US\"><head><title>Database Error</title></head><body><h1>Database Error</h1><p>" . kfHtml($sMessage) . "</p></body></html>";
        exit;
    }
}

function kfSend403AndExit() {
    kfSendSecurityHeaders();
    http_response_code(403);
    header("Content-Type: text/html; charset=utf-8", true);
    echo "<!DOCTYPE html>\n<html lang=\"en-US\"><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don't have permission to access this resource.</p></body></html>";
    exit;
}

if (!function_exists("requireExFullAccess")) {
    function requireExFullAccess($aAllowedIps) {
        if (function_exists("isExFullAccessAllowed") && isExFullAccessAllowed($aAllowedIps)) {
            return;
        }
        if (kfIsTrustedClient($aAllowedIps)) {
            return;
        }
        kfSend403AndExit();
    }
}

function redirectIndexPhpToRoot() {
    if (basename($_SERVER["SCRIPT_NAME"]) != "index.php") {
        return;
    }
    $sPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if (substr($sPath, -9) != "index.php") {
        return;
    }
    $sTarget = dirname($sPath);
    if ($sTarget == "\\" || $sTarget == ".") {
        $sTarget = "/";
    }
    $sQueryString = $_SERVER["QUERY_STRING"];
    $sTarget .= $sQueryString == "" ? "/" : "/?" . $sQueryString;
    kfSendSecurityHeaders();
    header("Location: " . $sTarget, true, 301);
    exit;
}

function kfGetDatabaseSchemaSql($aTables) {
    $sBody = "";
    foreach ($aTables as $aTable) {
        $sCreateTable = preg_replace("/\r\n|\r|\n/", "\r\n", $aTable[1]);
        $sBody .= $sCreateTable . ";\r\n\r\n";
    }
    return rtrim($sBody) . "\r\n";
}

function kfGetDatabaseBackupSql($oPdo, $aTables) {
    $sBody = "SET NAMES utf8mb4;\r\n\r\n" . kfGetDatabaseSchemaSql($aTables) . "\r\n";
    foreach ($aTables as $aTable) {
        $sTableName = $aTable[0];
        $sQuotedTableName = "`" . str_replace("`", "``", $sTableName) . "`";
        $oStatement = $oPdo->query("SELECT * FROM " . $sQuotedTableName);
        $sColumns = "";
        $blHasRows = false;
        while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
            if ($sColumns == "") {
                $aColumns = array();
                foreach (array_keys($aRow) as $sColumnName) {
                    $aColumns[] = "`" . str_replace("`", "``", $sColumnName) . "`";
                }
                $sColumns = implode(", ", $aColumns);
            }
            $aValues = array();
            foreach ($aRow as $mValue) {
                if ($mValue === null) {
                    $aValues[] = "NULL";
                } else {
                    $sQuoted = $oPdo->quote((string)$mValue);
                    $aValues[] = $sQuoted === false ? "'" . str_replace("'", "''", (string)$mValue) . "'" : $sQuoted;
                }
            }
            $sBody .= "INSERT INTO " . $sQuotedTableName . " (" . $sColumns . ") VALUES (" . implode(", ", $aValues) . ");\r\n";
            $blHasRows = true;
        }
        if ($blHasRows) {
            $sBody .= "\r\n";
        }
    }
    return rtrim($sBody) . "\r\n";
}

function kfFormatDatabaseStructureHtml($sSql) {
    $sSql = preg_replace_callback("/\\benum\\(([^)]*)\\)/i", function ($aMatches) {
        return "enum(" . preg_replace("/,\\s*/", ", ", $aMatches[1]) . ")";
    }, $sSql);
    $sSql .= ";";
    $aParts = preg_split("/('(?:\\\\.|''|[^'\\\\])*'|`(?:``|[^`])*`)/", $sSql, -1, PREG_SPLIT_DELIM_CAPTURE);
    $sHtml = "";
    foreach ($aParts as $sPart) {
        if ($sPart == "") {
            continue;
        }
        if ($sPart[0] == "'") {
            $sHtml .= "<span class=\"sql-string\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } elseif ($sPart[0] == "`") {
            $sHtml .= "<span class=\"sql-identifier\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } else {
            $sEscapedPart = htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8");
            $sHtml .= preg_replace("/\\b(ADD|ALTER|AUTO_INCREMENT|CASCADE|CHARACTER|CHARSET|CHECK|COLLATE|CONSTRAINT|CREATE|CURRENT_TIMESTAMP|DATABASE|DEFAULT|DELETE|ENGINE|ENUM|FOREIGN|KEY|NOT|NULL|ON|PRIMARY|REFERENCES|SET|TABLE|UNIQUE|UPDATE|USING|VALUES|INT|TINYINT|VARCHAR|TEXT|LONGTEXT|DATETIME|DATE|TIMESTAMP)\\b/i", "<span class=\"sql-keyword\">$1</span>", $sEscapedPart);
        }
    }
    return $sHtml;
}

function kfSchemaColumnTypeDisplay($sColumnType, $blShorten = true) {
    $sColumnType = (string)$sColumnType;
    if (preg_match("/^enum\\((.*)\\)$/i", $sColumnType, $aMatches)) {
        preg_match_all("/'((?:''|[^'])*)'/", $aMatches[1], $aEnumValues);
        $aDisplayValues = array();
        foreach ($aEnumValues[1] as $sEnumValue) {
            $aDisplayValues[] = "'" . $sEnumValue . "'";
        }
        if ($blShorten && count($aDisplayValues) > 24) {
            $aShortValues = array_slice($aDisplayValues, 0, 12);
            $aShortValues[] = "…";
            $aShortValues[] = $aDisplayValues[count($aDisplayValues) - 1];
            return "enum(" . implode(", ", $aShortValues) . ")";
        }
        return "enum(" . implode(", ", $aDisplayValues) . ")";
    }
    return $sColumnType;
}

function kfSendDatabaseSqlAndExit($sFileName, $sBody) {
    $sDate = gmdate("D, d M Y H:i:s", time());
    header("Content-Type: application/sql; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Content-Disposition: attachment; filename=\"" . $sFileName . "\"", true);
    header("Content-Transfer-Encoding: binary", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    kfSendSecurityHeaders();
    echo $sBody;
    exit;
}

function kfGetCurrentScriptName() {
    $sScriptName = isset($_SERVER["SCRIPT_NAME"]) ? basename((string)$_SERVER["SCRIPT_NAME"]) : "index.php";
    return $sScriptName != "" ? $sScriptName : "index.php";
}

function kfNormalizeMenuPath($sPath) {
    $sPath = str_replace("\\", "/", trim((string)$sPath));
    $sPath = preg_replace("#/+#", "/", $sPath);
    $sPath = preg_replace("#^/+#", "", $sPath);
    return $sPath;
}

function kfEncodeMenuPath($sPath) {
    $aParts = explode("/", kfNormalizeMenuPath($sPath));
    $aEncodedParts = array();
    foreach ($aParts as $sPart) {
        $aEncodedParts[] = rawurlencode($sPart);
    }
    return implode("/", $aEncodedParts);
}

function kfGetMenuPathPrefix() {
    $sScriptFile = isset($_SERVER["SCRIPT_FILENAME"]) ? (string)$_SERVER["SCRIPT_FILENAME"] : __FILE__;
    $sScriptFile = str_replace("\\", "/", $sScriptFile);
    $sScriptDirectory = dirname($sScriptFile);
    return kfNormalizeMenuPath(basename(dirname($sScriptDirectory)) . "/" . basename($sScriptDirectory)) . "/";
}

function kfGetCurrentMenuPath() {
    $sScriptName = kfGetCurrentScriptName();
    return $sScriptName == "index.php" ? kfGetMenuPathPrefix() : kfGetMenuPathPrefix() . $sScriptName;
}

function kfGetMenuItems() {
    global $oPdo;

    $aItems = array();
    if (!$oPdo) {
        return $aItems;
    }
    $sPathPrefix = kfGetMenuPathPrefix();
    $oStatement = $oPdo->prepare("SELECT id, path, icon, name, title, target, `order` AS menu_order FROM kf_menu WHERE is_active = 1 AND path LIKE :path_prefix ORDER BY `order` ASC, id ASC");
    $oStatement->execute(array("path_prefix" => $sPathPrefix . "%"));
    while ($aRow = $oStatement->fetch()) {
        $sPath = kfNormalizeMenuPath($aRow["path"]);
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

function kfGetPageTitle($sFallbackTitle) {
    global $oPdo;
    global $aAllowedIps;

    $sTitle = $sFallbackTitle;
    if (!$oPdo) {
        return kfGetPageTitleText($sTitle, $aAllowedIps);
    }
    $oStatement = $oPdo->prepare("SELECT name FROM kf_menu WHERE is_active = 1 AND path = :path LIMIT 1");
    $oStatement->execute(array("path" => kfGetCurrentMenuPath()));
    $sMenuTitle = trim((string)$oStatement->fetchColumn());
    $sTitle = $sMenuTitle != "" ? $sMenuTitle : $sFallbackTitle;
    return kfGetPageTitleText($sTitle, $aAllowedIps);
}

function kfGetPageTitleText($sTitle, $aAllowedIps) {
    $aStates = array();
    if (kfIsTrustedClient($aAllowedIps)) {
        $aStates[] = "Trusted";
    }
    if (kfIsAuthenticatedClient()) {
        $aStates[] = "Authenticated";
    }
    if (count($aStates) > 0) {
        $sTitle .= " - " . implode(" + ", $aStates);
    }
    return $sTitle;
}

function kfRenderMenu() {
    global $sBaseUrl, $sMenuEmoji;

    $aItems = kfGetMenuItems();
    if (!$aItems) {
        return;
    }
    $sCurrentPath = kfGetCurrentMenuPath();
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
        $sTitleAttribute = $sTitle != "" ? " title=\"" . kfHtml($sTitle) . "\"" : "";
        $sTargetAttribute = $sTarget != "" ? " target=\"" . kfHtml($sTarget) . "\"" : "";
        $sRelAttribute = $sTarget == "_blank" ? " rel=\"noopener noreferrer\"" : "";
        echo "        <a class=\"" . kfHtml($sClass) . "\" href=\"" . kfHtml($sBaseUrl . kfEncodeMenuPath($aItem["relative_path"])) . "\"" . $sTitleAttribute . $sTargetAttribute . $sRelAttribute . $sCurrent . "><span class=\"kf-menu-icon\" aria-hidden=\"true\">" . kfHtml($aItem["icon"]) . "</span><span class=\"kf-menu-text\">" . kfHtml($aItem["name"]) . "</span></a>\n";
    }
    echo "      </span>\n"
        . "    </span>\n";
}

function kfGetCsrfToken() {
    if (!isset($_SESSION["kf_csrf_token"]) || !is_string($_SESSION["kf_csrf_token"]) || $_SESSION["kf_csrf_token"] == "") {
        $_SESSION["kf_csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["kf_csrf_token"];
}

function kfRequireCsrfToken() {
    $sToken = isset($_POST["kf_csrf_token"]) ? (string)$_POST["kf_csrf_token"] : "";
    $sSessionToken = isset($_SESSION["kf_csrf_token"]) ? (string)$_SESSION["kf_csrf_token"] : "";
    if ($sToken == "" || $sSessionToken == "" || !hash_equals($sSessionToken, $sToken)) {
        send500AndExit("Invalid form token.");
    }
}

function kfSetMessage($sMessage, $sType = "success") {
    $_SESSION["kf_message"] = array("message" => $sMessage, "type" => $sType);
}

function kfRenderMessage() {
    if (!isset($_SESSION["kf_message"]) || !is_array($_SESSION["kf_message"])) {
        return;
    }
    $aMessage = $_SESSION["kf_message"];
    unset($_SESSION["kf_message"]);
    $sType = isset($aMessage["type"]) && $aMessage["type"] == "error" ? "error" : "success";
    echo "  <p class=\"message-box message-" . kfHtml($sType) . "\">" . kfHtml($aMessage["message"]) . "</p>\n";
}

function kfRedirect($sPath) {
    kfSendSecurityHeaders();
    header("Location: " . $sPath, true, 303);
    exit;
}

function kfPostedValue($sName, $sDefault = "") {
    return isset($_POST[$sName]) && !is_array($_POST[$sName]) ? trim((string)$_POST[$sName]) : $sDefault;
}

function kfParseAmount($sValue) {
    $sValue = str_replace(array(" ", ",", "−"), array("", ".", "-"), trim((string)$sValue));
    return is_numeric($sValue) ? (float)$sValue : null;
}

function kfFormatAmount($mAmount) {
    $fAmount = round((float)$mAmount, 2);
    $sAmount = number_format(abs($fAmount), 2, ".", " ");
    return $fAmount < 0 ? "−" . $sAmount : $sAmount;
}

function kfFormatDate($sDate) {
    $iTime = strtotime((string)$sDate);
    return $iTime ? date("Y-m-d", $iTime) : "";
}

function kfMonthLabel($sMonth) {
    $iTime = strtotime($sMonth . "-01");
    return $iTime ? date("F Y", $iTime) : $sMonth;
}

function kfGetFinanceTypes($blIncludeGroups = false) {
    global $oPdo;

    $sWhere = $blIncludeGroups ? "" : "WHERE type_kind IN ('income', 'expense')";
    $oStatement = $oPdo->query("SELECT id, type_kind, name FROM kf_fin_types " . $sWhere . " ORDER BY FIELD(type_kind, 'income', 'expense', 'group'), name ASC, id ASC");
    return $oStatement->fetchAll();
}

function kfGetFinanceTypeOptionsHtml($iSelectedId = 0) {
    $sHtml = "";
    foreach (kfGetFinanceTypes(false) as $aType) {
        $sLabel = ($aType["type_kind"] == "income" ? "Income: " : "Expense: ") . $aType["name"];
        $sHtml .= "          <option value=\"" . (int)$aType["id"] . "\"" . ((int)$aType["id"] == $iSelectedId ? " selected" : "") . ">" . kfHtml($sLabel) . "</option>\n";
    }
    return $sHtml;
}

