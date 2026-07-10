<?php

function isFirefoxBrowser() {
    if (!isset($_SERVER["HTTP_USER_AGENT"])) {
        return false;
    }
    $sUserAgent = $_SERVER["HTTP_USER_AGENT"];
    return stripos($sUserAgent, "Firefox/") && !stripos($sUserAgent, "Seamonkey/");
}

function isEnglishLanguage() {
    if (!isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
        return false;
    }
    $aLanguages = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
    if (!$aLanguages) {
        return false;
    }
    $sPrimaryLanguage = trim($aLanguages[0]);
    $iSemicolonPosition = strpos($sPrimaryLanguage, ";");
    if ($iSemicolonPosition) {
        $sPrimaryLanguage = substr($sPrimaryLanguage, 0, $iSemicolonPosition);
    }
    $sPrimaryLanguage = strtolower(trim($sPrimaryLanguage));
    return strpos($sPrimaryLanguage, "en") === 0;
}

function isFirefoxWithEnglishLanguage() {
    return isFirefoxBrowser() && isEnglishLanguage();
}

function isAllowedIp($aAllowedIps) {
    return isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $aAllowedIps, true);
}

function getUrlWithoutScriptName() {
    $sPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if (substr($sPath, -1) == "/") {
        return $sPath;
    }
    return dirname($sPath) . "/";
}

function sendQuickTableFilterJsonAndExit($aData, $iStatusCode = 200) {
    http_response_code($iStatusCode);
    header("Content-Type: application/json; charset=utf-8", true);
    header("Cache-Control: no-store", true);
    sendSecurityHeaders();
    echo json_encode($aData);
    exit;
}

function isQuickTableFilterAjaxRequest() {
    return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest";
}

function getQuickTableFilterScriptName() {
    $sScriptName = isset($_SERVER["SCRIPT_NAME"]) ? (string)$_SERVER["SCRIPT_NAME"] : "";
    $sScriptName = str_replace("\\", "/", $sScriptName);
    $sScriptName = basename($sScriptName);
    if ($sScriptName == "") {
        $sScriptName = "index.php";
    }
    return $sScriptName;
}

function getQuickTableFilterId($sFilterId) {
    $sFilterId = trim((string)$sFilterId);
    $sFilterId = preg_replace("/[^A-Za-z0-9_\\-]/", "", $sFilterId);
    if ($sFilterId == "") {
        $sFilterId = "table-filter";
    }
    return $sFilterId;
}

function getQuickTableFilterValue($sFilterId = "table-filter") {
    $sScriptName = getQuickTableFilterScriptName();
    $sFilterId = getQuickTableFilterId($sFilterId);
    if (!isset($_SESSION["quick_table_filters"]) || !is_array($_SESSION["quick_table_filters"])) {
        return "";
    }
    if (!isset($_SESSION["quick_table_filters"][$sScriptName]) || !is_array($_SESSION["quick_table_filters"][$sScriptName])) {
        return "";
    }
    if (!isset($_SESSION["quick_table_filters"][$sScriptName][$sFilterId]) || !is_string($_SESSION["quick_table_filters"][$sScriptName][$sFilterId])) {
        return "";
    }
    return $_SESSION["quick_table_filters"][$sScriptName][$sFilterId];
}

function setQuickTableFilterValue($sFilterId, $sValue) {
    $sScriptName = getQuickTableFilterScriptName();
    $sFilterId = getQuickTableFilterId($sFilterId);
    if (!isset($_SESSION["quick_table_filters"]) || !is_array($_SESSION["quick_table_filters"])) {
        $_SESSION["quick_table_filters"] = array();
    }
    if (!isset($_SESSION["quick_table_filters"][$sScriptName]) || !is_array($_SESSION["quick_table_filters"][$sScriptName])) {
        $_SESSION["quick_table_filters"][$sScriptName] = array();
    }
    $_SESSION["quick_table_filters"][$sScriptName][$sFilterId] = (string)$sValue;
}

function resetQuickTableFilterValue($sFilterId) {
    $sScriptName = getQuickTableFilterScriptName();
    $sFilterId = getQuickTableFilterId($sFilterId);
    if (isset($_SESSION["quick_table_filters"][$sScriptName][$sFilterId])) {
        unset($_SESSION["quick_table_filters"][$sScriptName][$sFilterId]);
    }
    if (isset($_SESSION["quick_table_filters"][$sScriptName]) && is_array($_SESSION["quick_table_filters"][$sScriptName]) && count($_SESSION["quick_table_filters"][$sScriptName]) === 0) {
        unset($_SESSION["quick_table_filters"][$sScriptName]);
    }
}

function handleQuickTableFilterRequest() {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["quick_table_filter_action"])) {
        return;
    }
    if (!isQuickTableFilterAjaxRequest()) {
        send403AndExit();
    }
    $sAction = (string)$_POST["quick_table_filter_action"];
    $sFilterId = isset($_POST["filter_id"]) ? (string)$_POST["filter_id"] : "table-filter";
    if ($sAction == "save") {
        $sValue = isset($_POST["filter_value"]) ? (string)$_POST["filter_value"] : "";
        setQuickTableFilterValue($sFilterId, $sValue);
        session_write_close();
        sendQuickTableFilterJsonAndExit(array("success" => true));
    } elseif ($sAction == "reset") {
        resetQuickTableFilterValue($sFilterId);
        session_write_close();
        sendQuickTableFilterJsonAndExit(array("success" => true));
    }
    sendQuickTableFilterJsonAndExit(array("success" => false, "message" => "Invalid quick filter action."), 400);
}

function getContentSecurityPolicySource() {
    if (!isset($_SERVER["HTTP_HOST"]) || !isset($_SERVER["REQUEST_URI"])) {
        return "'self'";
    }
    $sRequestScheme = "http";
    if (isset($GLOBALS["sScheme"]) && ($GLOBALS["sScheme"] == "http" || $GLOBALS["sScheme"] == "https")) {
        $sRequestScheme = $GLOBALS["sScheme"];
    } elseif ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "" && $_SERVER["HTTPS"] != "off")
        || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https")) {
        $sRequestScheme = "https";
    }
    $sHost = preg_replace("/[^A-Za-z0-9\\.\\-\\:\\[\\]]/", "", $_SERVER["HTTP_HOST"]);
    $sPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if ($sPath === false || $sPath === null || $sPath == "") {
        $sPath = "/";
    }
    if (substr($sPath, -1) != "/") {
        $sPath = dirname($sPath) . "/";
    }
    $sPath = preg_replace("#[^A-Za-z0-9/_\\-.%~]#", "", $sPath);
    if ($sPath == "") {
        $sPath = "/";
    }
    return $sRequestScheme . "://" . $sHost . $sPath;
}

function sendSecurityHeaders($sStyleNonce = "") {
    $sContentSecurityPolicySource = getContentSecurityPolicySource();
    $sStyleSource = $sContentSecurityPolicySource;
    if ($sStyleNonce != "") {
        $sStyleSource .= " 'nonce-" . $sStyleNonce . "'";
    } else {
        $sStyleSource .= " 'unsafe-inline'";
    }
    $sContentSecurityPolicy = "default-src 'none'; "
        . "script-src " . $sContentSecurityPolicySource . "; "
        . "style-src " . $sStyleSource . "; "
        . "img-src " . $sContentSecurityPolicySource . " data: blob:; "
        . "font-src " . $sContentSecurityPolicySource . " data:; "
        . "connect-src " . $sContentSecurityPolicySource . "; "
        . "media-src " . $sContentSecurityPolicySource . "; "
        . "frame-src " . $sContentSecurityPolicySource . "; "
        . "object-src 'none'; "
        . "base-uri " . $sContentSecurityPolicySource . "; "
        . "form-action " . $sContentSecurityPolicySource . "; "
        . "frame-ancestors 'self'";
    header("Strict-Transport-Security: max-age=31536000", true);
    header("X-Content-Type-Options: nosniff", true);
    header("X-Frame-Options: SAMEORIGIN", true);
    header("Referrer-Policy: strict-origin-when-cross-origin", true);
    header("Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=(), serial=(), bluetooth=()", true);
    header("Content-Security-Policy: " . $sContentSecurityPolicy, true);
}

function sendPageHeaders() {
    $iTime = time();
    if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
        if (strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) >= $iTime) {
            sendSecurityHeaders();
            header("HTTP/1.1 304 Not Modified", true);
            exit;
        }
    }
    $sDate = gmdate("D, d M Y H:i:s", $iTime);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-UA-Compatible: IE=edge", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();
    return $iTime;
}

function isFilmUaFingerprintRequest() {
    return $_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["fingerprint"]) && $_GET["fingerprint"] == "1";
}

function startFilmUaPageRequest($iRequestedFilmScanId) {
    $iRequestedFilmScanId = $iRequestedFilmScanId !== null ? (int)$iRequestedFilmScanId : null;
    $iNow = time();
    $iVisitTimeout = 20 * 60;
    $aPageVisits = array();
    if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
        $_SESSION["film"] = array();
    }
    if (!isset($_SESSION["film"]["ua"]) || !is_array($_SESSION["film"]["ua"])) {
        $_SESSION["film"]["ua"] = array();
    }
    if (isset($_SESSION["film"]["ua"]["visits"]) && is_array($_SESSION["film"]["ua"]["visits"])) {
        $aPageVisits = $_SESSION["film"]["ua"]["visits"];
    }
    foreach ($aPageVisits as $iFilmScanId => $iPageVisitTime) {
        if (!is_int($iPageVisitTime) || $iPageVisitTime < $iNow - $iVisitTimeout) {
            unset($aPageVisits[$iFilmScanId]);
        }
    }
    if ($iRequestedFilmScanId !== null && $iRequestedFilmScanId > 0) {
        $aPageVisits[$iRequestedFilmScanId] = $iNow;
    }
    if (count($aPageVisits) > 0) {
        $_SESSION["film"]["ua"]["visits"] = $aPageVisits;
    } else {
        unset($_SESSION["film"]["ua"]["visits"]);
    }
    $_SESSION["film"]["ua"]["request"] = array(
        "requested_film_scan_id" => $iRequestedFilmScanId !== null && $iRequestedFilmScanId > 0 ? $iRequestedFilmScanId : null,
        "request_uri"            => $_SERVER["REQUEST_URI"],
        "referer"                => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "",
        "requested_img"          => null
    );
}

function markFilmUaImageRequest($oPdo, $sImgParam, $sExtension, $aAllowedIps) {
    if (!$oPdo || isAllowedIp($aAllowedIps)) {
        return;
    }

    $sRequestedImg = basename($sImgParam);
    if ($sRequestedImg === "") {
        return;
    }
    $sSubdir = substr(pathinfo($sRequestedImg, PATHINFO_FILENAME), 0, 8);
    $sFileName = $sRequestedImg . $sExtension;
    $iRequestedFilmScanId = null;

    try {
        $oPdoStatement = $oPdo->prepare("SELECT scan_id FROM fs_film_photos WHERE subdir = :subdir AND filename = :filename LIMIT 1");
        $oPdoStatement->execute(array("subdir" => $sSubdir, "filename" => $sFileName));
        $mFilmScanId = $oPdoStatement->fetchColumn();
        if ($mFilmScanId !== false) {
            $iRequestedFilmScanId = (int)$mFilmScanId;
        }
    } catch (PDOException $oException) {
        return;
    }

    $iNow = time();
    $iVisitTimeout = 20 * 60;
    $aPageVisits = array();
    if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
        $_SESSION["film"] = array();
    }
    if (!isset($_SESSION["film"]["ua"]) || !is_array($_SESSION["film"]["ua"])) {
        $_SESSION["film"]["ua"] = array();
    }
    if (isset($_SESSION["film"]["ua"]["visits"]) && is_array($_SESSION["film"]["ua"]["visits"])) {
        $aPageVisits = $_SESSION["film"]["ua"]["visits"];
    }
    foreach ($aPageVisits as $iFilmScanId => $iPageVisitTime) {
        if (!is_int($iPageVisitTime) || $iPageVisitTime < $iNow - $iVisitTimeout) {
            unset($aPageVisits[$iFilmScanId]);
        }
    }
    if (count($aPageVisits) > 0) {
        $_SESSION["film"]["ua"]["visits"] = $aPageVisits;
    } else {
        unset($_SESSION["film"]["ua"]["visits"]);
    }
    if ($iRequestedFilmScanId !== null && isset($aPageVisits[$iRequestedFilmScanId])) {
        return;
    }

    $aData = array();
    if (isset($_SESSION["film"]["ua"]["fingerprint"]) && is_array($_SESSION["film"]["ua"]["fingerprint"])) {
        $aData = $_SESSION["film"]["ua"]["fingerprint"];
    }
    insertFilmUaRequest($oPdo, array(
        "request_uri"            => $_SERVER["REQUEST_URI"],
        "requested_film_scan_id" => $iRequestedFilmScanId,
        "requested_img"          => substr($sRequestedImg, 0, 64),
        "referer"                => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : ""
    ), $aData);
}

function getFilmUaPostData() {
    $sInput = file_get_contents("php://input");
    $aData = json_decode($sInput, true);
    return is_array($aData) ? $aData : array();
}

function getFilmUaFingerprintText($aData, $sName) {
    if (!isset($aData[$sName])) {
        return "";
    }
    if (is_array($aData[$sName])) {
        $aValues = array();
        foreach ($aData[$sName] as $mValue) {
            if (is_scalar($mValue)) {
                $aValues[] = (string)$mValue;
            }
        }
        return implode(",", $aValues);
    }
    if (!is_scalar($aData[$sName])) {
        return "";
    }
    return (string)$aData[$sName];
}

function getFilmUaFingerprintNullableText($aData, $sName, $iMaxLength = 0) {
    $sValue = trim(getFilmUaFingerprintText($aData, $sName));
    if ($sValue === "") {
        return null;
    }
    if ($iMaxLength > 0) {
        $sValue = substr($sValue, 0, $iMaxLength);
    }
    return $sValue;
}

function getFilmUaFingerprintBoolean($aData, $sName) {
    if (!array_key_exists($sName, $aData) || !is_scalar($aData[$sName]) || $aData[$sName] === "") {
        return null;
    }
    return $aData[$sName] ? 1 : 0;
}

function insertFilmUaRequest($oPdo, $aRequest, $aData) {
    if (!$oPdo) {
        return false;
    }

    try {
        $oPdoStatement = $oPdo->prepare("INSERT INTO fs_film_ua (ip_address, x_real_ip, x_forwarded_for, x_web_id, x_geo_provider, x_geo_continent_code, x_geo_country_code, user_agent, browser_name, browser_version, os_name, os_version, platform_type, device_vendor, device_model, architecture, bitness, is_mobile, ua_brands, request_uri, requested_film_scan_id, requested_img, referer, gpu_info, fonts, screen_resolution, screen_physical, color_depth, timezone, language, platform, plugins, mime_types, `timestamp`) VALUES (:ip_address, :x_real_ip, :x_forwarded_for, :x_web_id, :x_geo_provider, :x_geo_continent_code, :x_geo_country_code, :user_agent, :browser_name, :browser_version, :os_name, :os_version, :platform_type, :device_vendor, :device_model, :architecture, :bitness, :is_mobile, :ua_brands, :request_uri, :requested_film_scan_id, :requested_img, :referer, :gpu_info, :fonts, :screen_resolution, :screen_physical, :color_depth, :timezone, :language, :platform, :plugins, :mime_types, CURRENT_TIMESTAMP(6))");
        $oPdoStatement->execute(array(
            "ip_address"             => isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "",
            "x_real_ip"              => isset($_SERVER["HTTP_X_REAL_IP"]) ? substr($_SERVER["HTTP_X_REAL_IP"], 0, 45) : null,
            "x_forwarded_for"        => isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? substr($_SERVER["HTTP_X_FORWARDED_FOR"], 0, 1024) : null,
            "x_web_id"               => isset($_SERVER["HTTP_X_WEB_ID"]) ? substr($_SERVER["HTTP_X_WEB_ID"], 0, 255) : null,
            "x_geo_provider"         => isset($_SERVER["HTTP_X_GEO_PROVIDER"]) ? substr($_SERVER["HTTP_X_GEO_PROVIDER"], 0, 100) : null,
            "x_geo_continent_code"   => isset($_SERVER["HTTP_X_GEO_CONTINENT_CODE"]) ? substr($_SERVER["HTTP_X_GEO_CONTINENT_CODE"], 0, 2) : null,
            "x_geo_country_code"     => isset($_SERVER["HTTP_X_GEO_COUNTRY_CODE"]) ? substr($_SERVER["HTTP_X_GEO_COUNTRY_CODE"], 0, 2) : null,
            "user_agent"             => isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "",
            "browser_name"           => getFilmUaFingerprintNullableText($aData, "browser_name", 100),
            "browser_version"        => getFilmUaFingerprintNullableText($aData, "browser_version", 100),
            "os_name"                => getFilmUaFingerprintNullableText($aData, "os_name", 100),
            "os_version"             => getFilmUaFingerprintNullableText($aData, "os_version", 100),
            "platform_type"          => getFilmUaFingerprintNullableText($aData, "platform_type", 32),
            "device_vendor"          => getFilmUaFingerprintNullableText($aData, "device_vendor", 100),
            "device_model"           => getFilmUaFingerprintNullableText($aData, "device_model", 191),
            "architecture"           => getFilmUaFingerprintNullableText($aData, "architecture", 32),
            "bitness"                => getFilmUaFingerprintNullableText($aData, "bitness", 16),
            "is_mobile"              => getFilmUaFingerprintBoolean($aData, "is_mobile"),
            "ua_brands"              => getFilmUaFingerprintNullableText($aData, "ua_brands"),
            "request_uri"            => isset($aRequest["request_uri"]) && is_scalar($aRequest["request_uri"]) ? (string)$aRequest["request_uri"] : "",
            "requested_film_scan_id" => isset($aRequest["requested_film_scan_id"]) && is_int($aRequest["requested_film_scan_id"]) ? $aRequest["requested_film_scan_id"] : null,
            "requested_img"          => isset($aRequest["requested_img"]) && is_scalar($aRequest["requested_img"]) ? (string)$aRequest["requested_img"] : null,
            "referer"                => isset($aRequest["referer"]) && is_scalar($aRequest["referer"]) ? (string)$aRequest["referer"] : "",
            "gpu_info"               => getFilmUaFingerprintText($aData, "gpu"),
            "fonts"                  => getFilmUaFingerprintText($aData, "fonts"),
            "screen_resolution"      => getFilmUaFingerprintText($aData, "screen"),
            "screen_physical"        => getFilmUaFingerprintText($aData, "screen_physical"),
            "color_depth"            => getFilmUaFingerprintText($aData, "depth"),
            "timezone"               => getFilmUaFingerprintText($aData, "tz"),
            "language"               => getFilmUaFingerprintText($aData, "lang"),
            "platform"               => getFilmUaFingerprintText($aData, "platform"),
            "plugins"                => getFilmUaFingerprintText($aData, "plugins"),
            "mime_types"             => getFilmUaFingerprintText($aData, "mimes")
        ));
    } catch (PDOException $oException) {
        return false;
    }

    return true;
}

function sendFilmUaJsonAndExit($aResponse, $iStatus = 200) {
    http_response_code($iStatus);
    header("Content-Type: application/json; charset=utf-8", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    sendSecurityHeaders();
    echo json_encode($aResponse);
    exit;
}

function sendFilmUaFingerprintResponse($oPdo, $aAllowedIps) {
    if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
        $_SESSION["film"] = array();
    }
    if (isAllowedIp($aAllowedIps)) {
        unset($_SESSION["film"]["ua"]);
        sendFilmUaJsonAndExit(array("status" => "ignored"));
    }
    if (!$oPdo) {
        sendFilmUaJsonAndExit(array("status" => "error"), 500);
    }
    if (!isset($_SESSION["film"]["ua"]["request"]) || !is_array($_SESSION["film"]["ua"]["request"])) {
        unset($_SESSION["film"]["ua"]["request"]);
        sendFilmUaJsonAndExit(array("status" => "ignored"));
    }
    $aData = getFilmUaPostData();
    $_SESSION["film"]["ua"]["fingerprint"] = $aData;
    if (!insertFilmUaRequest($oPdo, $_SESSION["film"]["ua"]["request"], $aData)) {
        sendFilmUaJsonAndExit(array("status" => "error"), 500);
    }

    unset($_SESSION["film"]["ua"]["request"]);
    sendFilmUaJsonAndExit(array("status" => "ok"));
}

function printPhpFileLinks($sBaseUrl) {
    $aExcludedFiles = array("index.php", "main.php", "functions.php");
    $aPhotoFiles = array("equip.php", "link.php", "list.php", "orders.php", "ua.php");
    $aPhpFiles = array();
    foreach (scandir(".") as $sFileName) {
        if (!is_file($sFileName)) {
            continue;
        }
        if (pathinfo($sFileName, PATHINFO_EXTENSION) != "php") {
            continue;
        }
        if (in_array($sFileName, $aExcludedFiles, true)) {
            continue;
        }
        $aPhpFiles[] = $sFileName;
    }
    foreach (array(true, false) as $blPhotoFiles) {
        foreach ($aPhpFiles as $sFileName) {
            if (in_array($sFileName, $aPhotoFiles, true) !== $blPhotoFiles) {
                continue;
            }
            $sName = pathinfo($sFileName, PATHINFO_FILENAME);
            $aLines = file($sFileName);
            if ($aLines) {
                $blHtmlOutput = false;
                foreach ($aLines as $sLine) {
                    if (!$blHtmlOutput) {
                        if (strpos($sLine, "?>") !== false) {
                            $blHtmlOutput = true;
                        }
                        continue;
                    }
                    if (preg_match("#<title>([^<]+)</title>#i", $sLine, $aMatches)) {
                        $sName = trim($aMatches[1]);
                        break;
                    }
                }
            }
            $sTitle = preg_replace("/\bphp\b/iu", "PHP", mbUcfirst($sName));
            $sTitle = htmlspecialchars($sTitle, ENT_QUOTES, "UTF-8");
            echo "          <p><a href=\"" . $sBaseUrl . $sFileName . "\" target=\"_blank\" rel=\"noopener\" data-admin-link=\"1\" title=\"" . $sTitle . "\">" . $sTitle . "</a></p>\n";
        }
        if ($blPhotoFiles) {
            echo "          <hr>\n";
        }
    }
}

function mbUcfirst($sText): string {
    if (!$sText) {
        return "";
    }
    return mb_strtoupper(mb_substr($sText, 0, 1, "UTF-8"), "UTF-8") . mb_strtolower(mb_substr($sText, 1, null, "UTF-8"), "UTF-8");
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
    foreach (getallheaders() as $sHeaderName => $sHeaderValue) {
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
                $mValue = print_r($mValue, true);
            }
            $sOutput .= $sKey . ": " . $mValue . "\n";
        }
    }
    $sOutput .= "<hr>";
    $sOutput .= "<b>PHP \$_COOKIE array</b>\n";
    foreach ($_COOKIE as $sKey => $mValue) {
        if (is_array($mValue)) {
            $mValue = print_r($mValue, true);
        }
        $sOutput .= $sKey . ": " . $mValue . "\n";
    }
    return $sOutput;
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
    sendSecurityHeaders();
    header("Location: " . $sTarget, true, 301);
    exit;
}

function getDatabaseDownloadFileName($sType) {
    $sPrefix = isset($GLOBALS["sDatabaseDownloadPrefix"]) ? $GLOBALS["sDatabaseDownloadPrefix"] : "eved";
    $sProject = isset($GLOBALS["sDatabaseDownloadProject"]) ? $GLOBALS["sDatabaseDownloadProject"] : basename(__DIR__);
    return $sPrefix . "_" . $sProject . "_" . $sType . "_" . date("Y-m-d_His", time()) . ".sql";
}

function quoteDatabaseIdentifier($sIdentifier) {
    return "`" . str_replace("`", "``", $sIdentifier) . "`";
}

function quoteDatabaseValue($oPdo, $mValue) {
    if ($mValue === null) {
        return "NULL";
    }
    $sQuoted = $oPdo->quote((string)$mValue);
    if ($sQuoted === false) {
        return "'" . str_replace("'", "''", (string)$mValue) . "'";
    }
    return $sQuoted;
}

function getDatabaseSchemaSql($aTables) {
    $sBody = "";
    foreach ($aTables as $aTable) {
        $sCreateTable = preg_replace("/\r\n|\r|\n/", "\r\n", $aTable[1]);
        $sBody .= $sCreateTable . ";\r\n\r\n";
    }
    return rtrim($sBody) . "\r\n";
}

function getDatabaseBackupSql($oPdo, $aTables) {
    $sBody = "SET NAMES utf8mb4;\r\n\r\n" . getDatabaseSchemaSql($aTables) . "\r\n";
    foreach ($aTables as $aTable) {
        $sTableName = $aTable[0];
        $sQuotedTableName = quoteDatabaseIdentifier($sTableName);
        $oStatement = $oPdo->query("SELECT * FROM " . $sQuotedTableName);
        $sColumns = "";
        $blHasRows = false;
        while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
            if ($sColumns == "") {
                $aColumns = array();
                foreach (array_keys($aRow) as $sColumnName) {
                    $aColumns[] = quoteDatabaseIdentifier($sColumnName);
                }
                $sColumns = implode(", ", $aColumns);
            }
            $aValues = array();
            foreach ($aRow as $mValue) {
                $aValues[] = quoteDatabaseValue($oPdo, $mValue);
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

function formatDatabaseStructureHtml($sSql) {
    $sSql = preg_replace_callback("/\\benum\\(([^)]*)\\)/i", function ($aMatches) {
        return "enum(" . preg_replace("/,\\s*/", ", ", $aMatches[1]) . ")";
    }, $sSql);
    $sSql .= ";";
    $aParts = preg_split("/('(?:\\\\.|''|[^'\\\\])*'|`(?:``|[^`])*`)/", $sSql, -1, PREG_SPLIT_DELIM_CAPTURE);
    $sHtml = "";
    foreach ($aParts as $sPart) {
        if ($sPart === "") {
            continue;
        }
        if ($sPart[0] === "'") {
            $sHtml .= "<span class=\"sql-string\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } elseif ($sPart[0] === "`") {
            $sHtml .= "<span class=\"sql-identifier\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } else {
            $sEscapedPart = htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8");
            $sHtml .= preg_replace("/\\b(ADD|ALTER|AUTO_INCREMENT|CASCADE|CHARACTER|CHARSET|CHECK|COLLATE|CONSTRAINT|CREATE|CURRENT_TIMESTAMP|DATABASE|DEFAULT|DELETE|ENGINE|ENUM|FOREIGN|KEY|NOT|NULL|ON|PRIMARY|REFERENCES|SET|TABLE|UNIQUE|UPDATE|USING|VALUES|INT|TINYINT|VARCHAR|TEXT|LONGTEXT|DATETIME|DATE|TIMESTAMP)\\b/i", "<span class=\"sql-keyword\">$1</span>", $sEscapedPart);
        }
    }
    return $sHtml;
}

function sendDatabaseSqlAndExit($sFileName, $sBody) {
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
    sendSecurityHeaders();
    echo $sBody;
    exit;
}
function loadExposureDates(PDO $oPdo, $iId) {
    $sSql = "SELECT NULLIF(exposure_date,'0000-00-00') AS exposure_date FROM fs_film_exposure_dates WHERE film_scan_id = :id ORDER BY exposure_date";
    $oPdoStatement = $oPdo->prepare($sSql);
    $oPdoStatement->execute(array(":id" => $iId));
    $aDates = array();
    while ($sDate = $oPdoStatement->fetchColumn()) {
        if ($sDate) {
            $aDates[] = $sDate;
        }
    }
    return $aDates;
}

function formatExpirationDate($sDate) {
    if (!$sDate) {
        return "Unknown";
    }
    $oDateTime = DateTime::createFromFormat("Y-m-d", $sDate);
    if (!$oDateTime) {
        return "Unknown";
    }
    $iYear = (int)$oDateTime->format("Y");
    $iMonth = (int)$oDateTime->format("m");
    $iDay = (int)$oDateTime->format("d");

    if ($iMonth == 12 && $iDay == 31) {
        return (string)$iYear;
    }
    $iLastDay = cal_days_in_month(CAL_GREGORIAN, $iMonth, $iYear);
    if ($iDay == $iLastDay) {
        return sprintf("%02d/%d", $iMonth, $iYear);
    }
    return $oDateTime->format("Y-m-d");
}

function formatPushPull($iValue) {
    if (!$iValue) {
        return "None";
    }
    $iSteps = abs($iValue);
    if ($iValue > 0) {
        return "Push +" . $iSteps;
    }
    return "Pull −" . $iSteps;
}

function renderFilmScanHtml($oPdo, $aRow) {
    $aDates = loadExposureDates($oPdo, $aRow["id"]);
    $sDates = count($aDates) > 0 ? implode(", ", $aDates) : "Unknown";

    $sLabRoll = "";
    $aParts = preg_split("/\s+/", trim($aRow["folder_name"]));
    if (isset($aParts[1])) {
        $sLabRoll = $aParts[1] > 0 ? substr($aParts[1], -4) : "Unknown";
    }
    $iValue = $aRow["exposure_index"];
    if ($iValue <= 0) {
        $iValue = "Unknown";
    }
    $sValue = $aRow["corrections"] ?? "None";
    if (strtolower($sValue) == "none") {
        $sValue .= " (does not apply to preview display)";
    }
    $aFields = array(
        "Archive number"      => $aRow["archive_no"] > 0 ? $aRow["archive_no"] : "Unknown",
        "Lab roll number"     => $sLabRoll,
        "Film stock"          => $aRow["film_stock"],
        "Expiration date"     => formatExpirationDate($aRow["expiration_date"]),
        "Exposure index"      => $iValue,
        "Exposure correction" => $aRow["exposure_correction"] ?? "None",
        "Camera"              => $aRow["camera"],
        "Lens"                => $aRow["lens"],
        "Filter"              => $aRow["filter"],
        "Development process" => $aRow["development_process"],
        "Push/Pull"           => formatPushPull($aRow["push_pull"]),
        "Lab"                 => $aRow["lab"],
        "Exposure date"       => $sDates,
        "Scan date"           => substr((string)$aRow["scanned_at"], 0, 16),
        "Scan format"         => $aRow["scan_format"],
        "Scan resolution"     => sprintf("%d × %d", (int)$aRow["scan_width"], (int)$aRow["scan_height"]),
        "Archive format"      => $aRow["archive_format"],
        "Corrections"         => $sValue
    );
    $sHtml = "          <table class=\"film-metadata\">\n";
    foreach ($aFields as $sLabel => $sValue) {
        $sHtml .= "            <tr><th>" . htmlspecialchars($sLabel) . "</th><td>" . htmlspecialchars($sValue) . "</td></tr>\n";
    }
    $sHtml .= "          </table>\n";
    return $sHtml;
}

function sendFilmMetadataTxt($oPdo, $aRow) {
    $aDates = loadExposureDates($oPdo, $aRow["id"]);
    $sDates = count($aDates) > 0 ? implode(", ", $aDates) : "Unknown";

    $sLabRoll = "";
    $sCode = "";
    $aParts = preg_split("/\s+/", trim($aRow["folder_name"]));
    if (isset($aParts[1])) {
        $sLabRoll = $aParts[1] > 0 ? substr($aParts[1], -4) : "Unknown";
        $sCode = $aParts[1];
    }
    $iValue = $aRow["exposure_index"];
    if ($iValue <= 0) {
        $iValue = "Unknown";
    }
    $aLines = array(
        sprintf("Archive number:      %s", $aRow["archive_no"] > 0 ? (string)$aRow["archive_no"] : "Unknown"),
        sprintf("Lab roll number:     %s", $sLabRoll),
        sprintf("Film stock:          %s", $aRow["film_stock"] ?? ""),
        sprintf("Expiration date:     %s", formatExpirationDate($aRow["expiration_date"] ?? null)),
        sprintf("Exposure index:      %s", (string)$iValue),
        sprintf("Exposure correction: %s", $aRow["exposure_correction"] ?? "None"),
        sprintf("Camera:              %s", $aRow["camera"] ?? ""),
        sprintf("Lens:                %s", $aRow["lens"] ?? ""),
        sprintf("Filter:              %s", $aRow["filter"] ?? ""),
        sprintf("Development process: %s", $aRow["development_process"] ?? ""),
        sprintf("Push/Pull:           %s", formatPushPull($aRow["push_pull"])),
        sprintf("Lab:                 %s", $aRow["lab"] ?? ""),
        sprintf("Exposure date:       %s", $sDates),
        sprintf("Scan date:           %s", substr((string)($aRow["scanned_at"] ?? ""), 0, 16)),
        sprintf("Scan format:         %s", $aRow["scan_format"] ?? ""),
        sprintf("Scan resolution:     %d × %d", (int)$aRow["scan_width"], (int)$aRow["scan_height"]),
        sprintf("Archive format:      %s", $aRow["archive_format"] ?? ""),
        sprintf("Corrections:         %s", $aRow["corrections"] ?? "None")
    );

    $sContent = "";
    foreach ($aLines as $aLine) {
        $sContent .= trim($aLine) . "\r\n";
    }
    if (!$sCode) {
        $sCode = "film_" . $aRow["archive_no"];
    }
    $sFileName = $sCode . "_RAW.txt";
    $sBody = $sContent;

    $sDate = gmdate("D, d M Y H:i:s", time());
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Content-Disposition: attachment; filename=\"" . rawurlencode($sFileName) . "\"", true);
    header("Content-Transfer-Encoding: binary", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();

    echo $sBody;
    exit;
}

function generateRandomId($iLength = 8) {
    $sSet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $iLen = strlen($sSet);
    $sResult = "";

    for ($iI = 0; $iI < $iLength; $iI++) {
        $iIndex = mt_rand(0, $iLen - 1);
        $sResult .= $sSet[$iIndex];
    }

    return $sResult;
}

function renderCell($mValue, $blError) {
    $sValue = htmlspecialchars((string)$mValue, ENT_QUOTES, "UTF-8");
    if ($blError) {
        echo "      <td class=\"error-cell\">" . $sValue . "</td>\n";
    } else {
        echo "      <td>" . $sValue . "</td>\n";
    }
}

function formatFilmOptionLabel($aFilm) {
    return (string)$aFilm["archive_no"] . " – " . $aFilm["folder_name"];
}

function formatOrderOptionLabel($aOrder) {
    $sLabel = "";
    if ($aOrder["bag_no"] !== null && $aOrder["bag_no"] !== "") {
        $sLabel .= $aOrder["bag_no"];
    }
    if ($aOrder["order_no"] !== null && $aOrder["order_no"] !== "") {
        $sLabel .= " (" . $aOrder["order_no"] . ")";
    }
    return $sLabel;
}

function formatFilmUaCountryFlag($sCountryCode) {
    $sCountryCode = strtoupper(trim((string)$sCountryCode));
    if (strlen($sCountryCode) !== 2 || !ctype_alpha($sCountryCode)) {
        return "";
    }
    return "&#" . (127462 + ord($sCountryCode[0]) - 65) . ";&#" . (127462 + ord($sCountryCode[1]) - 65) . ";";
}

function formatFilmUaUserAgent($sUserAgent) {
    $sUserAgent = trim((string)$sUserAgent);
    $sBrowser = "Unknown browser";
    $sOperatingSystem = "Unknown operating system";
    $sArchitecture = "";
    $aMatches = array();
    $aWindowsVersions = array(
        "10.0" => "Windows 10/11",
        "6.3"  => "Windows 8.1",
        "6.2"  => "Windows 8",
        "6.1"  => "Windows 7",
        "6.0"  => "Windows Vista",
        "5.1"  => "Windows XP"
    );

    if (preg_match("#Edg(?:A|iOS)?/([0-9.]+)#", $sUserAgent, $aMatches)) {
        $sBrowser = "Microsoft Edge " . $aMatches[1];
    } elseif (preg_match("#OPR/([0-9.]+)#", $sUserAgent, $aMatches)) {
        $sBrowser = "Opera " . $aMatches[1];
    } elseif (preg_match("#Firefox/([0-9.]+)#", $sUserAgent, $aMatches)) {
        $sBrowser = "Firefox " . $aMatches[1];
    } elseif (preg_match("#CriOS/([0-9.]+)#", $sUserAgent, $aMatches)) {
        $sBrowser = "Chrome " . $aMatches[1];
    } elseif (preg_match("#Chrome/([0-9.]+)#", $sUserAgent, $aMatches)) {
        $sBrowser = "Chrome " . $aMatches[1];
    } elseif (preg_match("#Version/([0-9.]+).*Safari/#", $sUserAgent, $aMatches)) {
        $sBrowser = "Safari " . $aMatches[1];
    }

    if (preg_match("#Windows NT ([0-9.]+)#", $sUserAgent, $aMatches)) {
        $sOperatingSystem = isset($aWindowsVersions[$aMatches[1]]) ? $aWindowsVersions[$aMatches[1]] : "Windows NT " . $aMatches[1];
    } elseif (preg_match("#Android ([0-9.]+)#", $sUserAgent, $aMatches)) {
        $sOperatingSystem = "Android " . $aMatches[1];
    } elseif (preg_match("#(?:iPhone|CPU) OS ([0-9_]+)#", $sUserAgent, $aMatches)) {
        $sOperatingSystem = "iOS " . str_replace("_", ".", $aMatches[1]);
    } elseif (preg_match("#Mac OS X ([0-9_]+)#", $sUserAgent, $aMatches)) {
        $sOperatingSystem = "macOS " . str_replace("_", ".", $aMatches[1]);
    } elseif (stripos($sUserAgent, "Linux") !== false) {
        $sOperatingSystem = "Linux";
    }

    if (preg_match("#Win64|x86_64|x64|amd64#i", $sUserAgent)) {
        $sArchitecture = " (64-bit)";
    } elseif (preg_match("#i[3-6]86|Win32#i", $sUserAgent)) {
        $sArchitecture = " (32-bit)";
    }

    return $sBrowser . " on " . $sOperatingSystem . $sArchitecture;
}

function formatFilmUaGpu($sGpuInfo) {
    $sGpuInfo = trim((string)$sGpuInfo);
    if ($sGpuInfo === "") {
        return "";
    }

    $sFriendly = preg_replace("#^ANGLE\\s*\\(#i", "", $sGpuInfo);
    $sFriendly = preg_replace("#\\)?,?\\s*or similar\\s*$#i", "", $sFriendly);
    $sFriendly = preg_replace("#\\)\\s*$#", "", $sFriendly);
    $aParts = array_map("trim", explode(",", $sFriendly));
    if (isset($aParts[1]) && $aParts[1] !== "") {
        $sFriendly = $aParts[1];
    } elseif (isset($aParts[0])) {
        $sFriendly = $aParts[0];
    }
    $sFriendly = preg_replace("#\\s+(?:Direct3D|OpenGL|Vulkan|Metal)\\b.*$#i", "", $sFriendly);
    $sFriendly = preg_replace("#\\s+vs_[0-9_]+.*$#i", "", $sFriendly);

    return trim($sFriendly);
}

function send403AndExit() {
    $sDate = gmdate("D, d M Y H:i:s", time());
    $sHtml = "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n"
        . "<html><head>\n"
        . "<title>403 Forbidden</title>\n"
        . "</head><body>\n"
        . "<h1>Forbidden</h1>\n"
        . "<p>You don't have permission to access this resource.</p>\n"
        . "</body></html>\n";

    http_response_code(403);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Content-Length: " . strlen($sHtml), true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();

    echo $sHtml;
    exit;
}

function send404AndExit() {
    $sDate = gmdate("D, d M Y H:i:s", time());
    $sHtml = "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n"
        . "<html><head>\n"
        . "<title>404 Not Found</title>\n"
        . "</head><body>\n"
        . "<h1>Not Found</h1>\n"
        . "<p>The requested URL was not found on this server.</p>\n"
        . "</body></html>\n";

    http_response_code(404);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Content-Length: " . strlen($sHtml), true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();

    echo $sHtml;
    exit;
}

function send500AndExit($sMessage) {
    $sDate = gmdate("D, d M Y H:i:s", time());
    $sHtml = "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n"
        . "<html><head>\n"
        . "<title>500 Internal Server Error</title>\n"
        . "</head><body>\n"
        . "<h1>Internal Server Error</h1>\n"
        . "<p>" . htmlspecialchars($sMessage, ENT_QUOTES, "UTF-8") . "</p>\n"
        . "</body></html>\n";

    http_response_code(500);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Content-Length: " . strlen($sHtml), true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();

    echo $sHtml;
    exit;
}
