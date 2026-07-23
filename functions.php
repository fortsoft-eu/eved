<?php

function isAllowedIp($aAllowedIps) {
    return isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $aAllowedIps, true);
}

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

function isDesktop() {
    $sUserAgent = isset($_SERVER["HTTP_USER_AGENT"]) ? (string)$_SERVER["HTTP_USER_AGENT"] : "";
    return !preg_match("/(?:Android|iPhone|iPad|iPod|Mobile|Tablet|Silk|Kindle|FxiOS)/i", $sUserAgent);
}

function getCondensedTableClass() {
    return isDesktop() ? "" : " condensed-table";
}

function getEvedUaFingerprintText($aData, $sName) {
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
    return is_scalar($aData[$sName]) ? (string)$aData[$sName] : "";
}

function getEvedUaFingerprintNullableText($aData, $sName, $iMaxLength = 0) {
    $sValue = trim(getEvedUaFingerprintText($aData, $sName));
    if ($sValue == "") {
        return null;
    }
    if ($iMaxLength > 0) {
        $sValue = substr($sValue, 0, $iMaxLength);
    }
    return $sValue;
}

function insertEvedUaRequest($oPdo) {
    if (!$oPdo) {
        return 0;
    }
    try {
        $oStatement = $oPdo->prepare("INSERT INTO fs_eved_ua (ip_address, x_real_ip, x_forwarded_for, x_web_id, x_geo_provider, x_geo_continent_code, x_geo_country_code, request_uri, referer, user_agent, accept_language, `timestamp`) VALUES (:ip_address, :x_real_ip, :x_forwarded_for, :x_web_id, :x_geo_provider, :x_geo_continent_code, :x_geo_country_code, :request_uri, :referer, :user_agent, :accept_language, CURRENT_TIMESTAMP(6))");
        $oStatement->execute(array(
            "ip_address"           => isset($_SERVER["REMOTE_ADDR"]) ? substr((string)$_SERVER["REMOTE_ADDR"], 0, 45) : "",
            "x_real_ip"            => isset($_SERVER["HTTP_X_REAL_IP"]) ? substr((string)$_SERVER["HTTP_X_REAL_IP"], 0, 45) : null,
            "x_forwarded_for"      => isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? substr((string)$_SERVER["HTTP_X_FORWARDED_FOR"], 0, 1024) : null,
            "x_web_id"             => isset($_SERVER["HTTP_X_WEB_ID"]) ? substr((string)$_SERVER["HTTP_X_WEB_ID"], 0, 255) : null,
            "x_geo_provider"       => isset($_SERVER["HTTP_X_GEO_PROVIDER"]) ? substr((string)$_SERVER["HTTP_X_GEO_PROVIDER"], 0, 100) : null,
            "x_geo_continent_code" => isset($_SERVER["HTTP_X_GEO_CONTINENT_CODE"]) ? substr((string)$_SERVER["HTTP_X_GEO_CONTINENT_CODE"], 0, 2) : null,
            "x_geo_country_code"   => isset($_SERVER["HTTP_X_GEO_COUNTRY_CODE"]) ? substr((string)$_SERVER["HTTP_X_GEO_COUNTRY_CODE"], 0, 2) : null,
            "request_uri"          => isset($_SERVER["REQUEST_URI"]) ? substr((string)$_SERVER["REQUEST_URI"], 0, 1024) : "",
            "referer"              => isset($_SERVER["HTTP_REFERER"]) ? substr((string)$_SERVER["HTTP_REFERER"], 0, 1024) : null,
            "user_agent"           => isset($_SERVER["HTTP_USER_AGENT"]) ? (string)$_SERVER["HTTP_USER_AGENT"] : "",
            "accept_language"      => isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? substr((string)$_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 255) : null
        ));
        return (int)$oPdo->lastInsertId();
    } catch (PDOException $oException) {
        error_log((string)$oException);
    }
    return 0;
}

function updateEvedUaFingerprint($oPdo, $iEvedUaId, $aData) {
    if (!$oPdo || $iEvedUaId < 1) {
        return false;
    }
    try {
        $mIsMobile = null;
        if (array_key_exists("is_mobile", $aData) && is_scalar($aData["is_mobile"]) && $aData["is_mobile"] != "") {
            $mIsMobile = $aData["is_mobile"] ? 1 : 0;
        }
        $aParameters = array(
            "browser_name"      => getEvedUaFingerprintNullableText($aData, "browser_name", 100),
            "browser_version"   => getEvedUaFingerprintNullableText($aData, "browser_version", 100),
            "os_name"           => getEvedUaFingerprintNullableText($aData, "os_name", 100),
            "os_version"        => getEvedUaFingerprintNullableText($aData, "os_version", 100),
            "platform_type"     => getEvedUaFingerprintNullableText($aData, "platform_type", 32),
            "device_vendor"     => getEvedUaFingerprintNullableText($aData, "device_vendor", 100),
            "device_model"      => getEvedUaFingerprintNullableText($aData, "device_model", 191),
            "architecture"      => getEvedUaFingerprintNullableText($aData, "architecture", 32),
            "bitness"           => getEvedUaFingerprintNullableText($aData, "bitness", 16),
            "is_mobile"         => $mIsMobile,
            "ua_brands"         => getEvedUaFingerprintNullableText($aData, "ua_brands"),
            "gpu_info"          => getEvedUaFingerprintText($aData, "gpu"),
            "fonts"             => getEvedUaFingerprintText($aData, "fonts"),
            "screen_resolution" => getEvedUaFingerprintText($aData, "screen"),
            "screen_physical"   => getEvedUaFingerprintText($aData, "screen_physical"),
            "color_depth"       => getEvedUaFingerprintText($aData, "depth"),
            "timezone"          => getEvedUaFingerprintText($aData, "tz"),
            "language"          => getEvedUaFingerprintText($aData, "lang"),
            "platform"          => getEvedUaFingerprintText($aData, "platform"),
            "plugins"           => getEvedUaFingerprintText($aData, "plugins"),
            "mime_types"        => getEvedUaFingerprintText($aData, "mimes"),
            "id"                => $iEvedUaId,
            "ip_address"        => isset($_SERVER["REMOTE_ADDR"]) ? substr((string)$_SERVER["REMOTE_ADDR"], 0, 45) : "",
            "user_agent"        => isset($_SERVER["HTTP_USER_AGENT"]) ? (string)$_SERVER["HTTP_USER_AGENT"] : ""
        );
        $oStatement = $oPdo->prepare("UPDATE fs_eved_ua SET browser_name = :browser_name, browser_version = :browser_version, os_name = :os_name, os_version = :os_version, platform_type = :platform_type, device_vendor = :device_vendor, device_model = :device_model, architecture = :architecture, bitness = :bitness, is_mobile = :is_mobile, ua_brands = :ua_brands, gpu_info = :gpu_info, fonts = :fonts, screen_resolution = :screen_resolution, screen_physical = :screen_physical, color_depth = :color_depth, timezone = :timezone, language = :language, platform = :platform, plugins = :plugins, mime_types = :mime_types WHERE id = :id AND ip_address = :ip_address AND user_agent = :user_agent AND `timestamp` >= DATE_SUB(CURRENT_TIMESTAMP(6), INTERVAL 10 MINUTE)");
        $oStatement->execute($aParameters);
        if ($oStatement->rowCount() > 0) {
            return true;
        }
        $oStatement = $oPdo->prepare("SELECT 1 FROM fs_eved_ua WHERE id = :id AND ip_address = :ip_address AND user_agent = :user_agent AND `timestamp` >= DATE_SUB(CURRENT_TIMESTAMP(6), INTERVAL 10 MINUTE) LIMIT 1");
        $oStatement->execute(array(
            "id"         => $aParameters["id"],
            "ip_address" => $aParameters["ip_address"],
            "user_agent" => $aParameters["user_agent"]
        ));
        return $oStatement->fetchColumn() !== false;
    } catch (PDOException $oException) {
        error_log((string)$oException);
    }
    return false;
}

function sendEvedUaJsonAndExit($aResponse, $iStatus = 200) {
    http_response_code($iStatus);
    header("Content-Type: application/json; charset=utf-8", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    sendSecurityHeaders();
    echo json_encode($aResponse);
    exit;
}

function sendEvedUaFingerprintResponse($oPdo, $aAllowedIps) {
    if (isAllowedIp($aAllowedIps)) {
        sendEvedUaJsonAndExit(array("status" => "ignored"));
    }
    if (!$oPdo) {
        sendEvedUaJsonAndExit(array("status" => "error"), 500);
    }
    $sInput = file_get_contents("php://input");
    $aData = json_decode($sInput, true);
    if (!is_array($aData)) {
        $aData = array();
    }
    $iEvedUaId = isset($aData["ua_id"]) ? (int)$aData["ua_id"] : 0;
    if (!updateEvedUaFingerprint($oPdo, $iEvedUaId, $aData)) {
        sendEvedUaJsonAndExit(array("status" => "error"), 500);
    }
    sendEvedUaJsonAndExit(array("status" => "ok"));
}

function formatUaCountryFlag($sCountryCode) {
    $sCountryCode = strtoupper(trim((string)$sCountryCode));
    if (strlen($sCountryCode) != 2 || !ctype_alpha($sCountryCode)) {
        return "";
    }
    return "&#" . (127462 + ord($sCountryCode[0]) - 65) . ";&#" . (127462 + ord($sCountryCode[1]) - 65) . ";";
}

function formatUaUserAgent($sUserAgent) {
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

function formatUaGpu($sGpuInfo) {
    $sGpuInfo = trim((string)$sGpuInfo);
    if ($sGpuInfo == "") {
        return "";
    }
    $sFriendly = preg_replace("#^ANGLE\\s*\\(#i", "", $sGpuInfo);
    $sFriendly = preg_replace("#\\)?,?\\s*or similar\\s*$#i", "", $sFriendly);
    $sFriendly = preg_replace("#\\)\\s*$#", "", $sFriendly);
    $aParts = array_map("trim", explode(",", $sFriendly));
    if (isset($aParts[1]) && $aParts[1] != "") {
        $sFriendly = $aParts[1];
    } elseif (isset($aParts[0])) {
        $sFriendly = $aParts[0];
    }
    $sFriendly = preg_replace("#\\s+(?:Direct3D|OpenGL|Vulkan|Metal)\\b.*$#i", "", $sFriendly);
    $sFriendly = preg_replace("#\\s+vs_[0-9_]+.*$#i", "", $sFriendly);
    return trim($sFriendly);
}

function getContentSecurityPolicySource() {
    global $sScheme;

    if (!isset($_SERVER["HTTP_HOST"]) || !isset($_SERVER["REQUEST_URI"])) {
        return "'self'";
    }
    $sRequestScheme = $sScheme;
    $sHost = preg_replace("/[^A-Za-z0-9\\.\\-\\:\\[\\]]/", "", $_SERVER["HTTP_HOST"]);
    $sPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if (!$sPath) {
        $sPath = "/";
    }
    if (substr($sPath, -1) != "/") {
        $sPath = dirname($sPath) . "/";
    }
    $sPath = preg_replace("#[^A-Za-z0-9/_\\-.%~]#", "", $sPath);
    if (!$sPath) {
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

function sendPageHeaders($sStyleNonce = "") {
    $iTime = time();
    if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
        if (strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) >= $iTime) {
            sendSecurityHeaders($sStyleNonce);
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
    sendSecurityHeaders($sStyleNonce);
    return $iTime;
}

function addPhpGeneratedViewportMeta($sHtml) {
    if (preg_match("#<meta\\b[^>]*\\bname\\s*=\\s*([\"'])viewport\\1#i", $sHtml) || stripos($sHtml, "</head>") === false) {
        return $sHtml;
    }
    return preg_replace("#</head>#i", "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no\">\n</head>", $sHtml, 1);
}

function getPhpGeneratedStyleTag($sStyleNonce) {
    $sNonce = $sStyleNonce != "" ? " nonce=\"" . htmlspecialchars($sStyleNonce, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"" : "";
    return "  <style" . $sNonce . ">\n"
        . "body {background-color: #fff; color: #222; font-family: sans-serif;}\n"
        . "pre {margin: 0; font-family: monospace;}\n"
        . "a {color: inherit;}\n"
        . "a:hover {text-decoration: none;}\n"
        . "table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px rgba(0, 0, 0, 0.2);}\n"
        . ".center {text-align: center;}\n"
        . ".center table {margin: 1em auto; text-align: left;}\n"
        . ".center th {text-align: center !important;}\n"
        . "td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}\n"
        . "th {position: sticky; top: 0; background: inherit;}\n"
        . "h1 {font-size: 150%;}\n"
        . "h2 {font-size: 125%;}\n"
        . "h2 > a {text-decoration: none;}\n"
        . "h2 > a:hover {text-decoration: underline;}\n"
        . ".p {text-align: left;}\n"
        . ".e {background-color: #ccf; width: 300px; font-weight: bold;}\n"
        . ".h {background-color: #99c; font-weight: bold;}\n"
        . ".v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: normal;}\n"
        . ".v i {color: #999;}\n"
        . "img {float: right; border: 0;}\n"
        . "hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}\n"
        . ":root {--php-dark-grey: #333; --php-dark-blue: #4F5B93; --php-medium-blue: #8892BF; --php-light-blue: #E2E4EF; --php-accent-purple: #793862}\n"
        . "@media (prefers-color-scheme: dark) {\n"
        . "  body {background: var(--php-dark-grey); color: var(--php-light-blue)}\n"
        . "  .h td, td.e, th {border-color: #606A90}\n"
        . "  td {border-color: #505153}\n"
        . "  .e {background-color: #404A77}\n"
        . "  .h {background-color: var(--php-dark-blue)}\n"
        . "  .v {background-color: var(--php-dark-grey)}\n"
        . "  hr {background-color: #505153}\n"
        . "}\n"
        . "  </style>\n";
}

function formatPhpGeneratedOutput($sHtml, $sStyleNonce, $sTitle) {
    if (stripos($sHtml, "<html") !== false) {
        if (stripos($sHtml, "<style") === false && stripos($sHtml, "</head>") !== false) {
            $sHtml = preg_replace("#</head>#i", getPhpGeneratedStyleTag($sStyleNonce) . "</head>", $sHtml, 1);
        }
        return addPhpGeneratedViewportMeta($sHtml);
    }
    $sTitle = htmlspecialchars($sTitle, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    ob_start();
    echo "<!DOCTYPE html>\n",
        "<html lang=\"en-US\" dir=\"ltr\">\n",
        "<head>\n",
        "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n",
        "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no\">\n",
        "  <title>" . $sTitle . "</title>\n",
        getPhpGeneratedStyleTag($sStyleNonce)
        . "</head>\n",
        "<body><div class=\"center\">\n",
        $sHtml
        . "\n</div></body>\n",
        "</html>\n";
    return ob_get_clean();
}

function sendPhpGeneratedOutputAndExit($sType, $iSelect) {
    global $aAllowedIps;

    ob_start();
    if ($sType == "credits") {
        if ($iSelect > 0) {
            phpcredits($iSelect | CREDITS_FULLPAGE);
        } else {
            phpcredits();
        }
    } else {
        if ($iSelect > 0) {
            phpinfo($iSelect);
        } else {
            phpinfo();
        }
    }
    $sTitle = $sType == "credits" ? "PHP Credits" : "PHP Info";
    $sTitle = getPageTitleText($sTitle, $aAllowedIps);
    $sHtml = ob_get_clean();
    $sStyleNonce = stripos($sHtml, "<html") !== false ? "" : base64_encode(random_bytes(16));
    $sHtml = formatPhpGeneratedOutput($sHtml, $sStyleNonce, $sTitle);
    sendPhpGeneratedHeaders($sStyleNonce);
    echo $sHtml;
    exit;
}

function sendPhpGeneratedHeaders($sStyleNonce) {
    $iTime = time();
    $sDate = gmdate("D, d M Y H:i:s", $iTime);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders($sStyleNonce);
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

function formatDatabaseStructureHtml($sSql) {
    $sSql = preg_replace_callback("/\\benum\\(([^)]*)\\)/i", function ($aMatches) {
        return "enum(" . preg_replace("/,\\s*/", ", ", $aMatches[1]) . ")";
    }, $sSql);
    $sSql .= ";";
    $aParts = preg_split("/('(?:\\\\.|''|[^'\\\\])*'|`(?:``|[^`])*`)/", $sSql, -1, PREG_SPLIT_DELIM_CAPTURE);
    $sHtml = "";
    $blEnumOpen = false;
    $fFormatPlainSql = function ($sPart) {
        $sEscapedPart = htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8");
        return preg_replace("/\\b(ADD|ALTER|AUTO_INCREMENT|CASCADE|CHARACTER|CHARSET|CHECK|COLLATE|CONSTRAINT|CREATE|CURRENT_TIMESTAMP|DATABASE|DEFAULT|DELETE|ENGINE|ENUM|FOREIGN|KEY|NOT|NULL|ON|PRIMARY|REFERENCES|SET|TABLE|UNIQUE|UPDATE|USING|VALUES|INT|TINYINT|VARCHAR|TEXT|LONGTEXT|DATETIME|DATE|TIMESTAMP)\\b/i", "<span class=\"sql-keyword\">$1</span>", $sEscapedPart);
    };
    foreach ($aParts as $sPart) {
        if ($sPart == "") {
            continue;
        }
        if ($sPart[0] == "'") {
            $sHtml .= "<span class=\"sql-string\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } elseif ($sPart[0] == "`") {
            $sHtml .= "<span class=\"sql-identifier\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } else {
            while ($sPart != "") {
                if (!$blEnumOpen) {
                    if (!preg_match("/\\benum\\s*\\(/i", $sPart, $aMatches, PREG_OFFSET_CAPTURE)) {
                        $sHtml .= $fFormatPlainSql($sPart);
                        break;
                    }
                    $iOffset = $aMatches[0][1];
                    if ($iOffset > 0) {
                        $sHtml .= $fFormatPlainSql(substr($sPart, 0, $iOffset));
                    }
                    $sHtml .= "<span class=\"sql-enum\">" . $fFormatPlainSql($aMatches[0][0]);
                    $sPart = substr($sPart, $iOffset + strlen($aMatches[0][0]));
                    $blEnumOpen = true;
                    continue;
                }
                $iOffset = strpos($sPart, ")");
                if ($iOffset === false) {
                    $sHtml .= $fFormatPlainSql($sPart);
                    break;
                }
                $sHtml .= $fFormatPlainSql(substr($sPart, 0, $iOffset + 1)) . "</span>";
                $sPart = substr($sPart, $iOffset + 1);
                $blEnumOpen = false;
            }
        }
    }
    if ($blEnumOpen) {
        $sHtml .= "</span>";
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

function sendJsonAndExit($aData, $iStatusCode = 200) {
    sendSecurityHeaders();
    http_response_code($iStatusCode);
    header("Content-Type: application/json; charset=utf-8", true);
    header("Cache-Control: no-store", true);
    echo json_encode($aData);
    exit;
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
    error_log("500 Internal Server Error: " . (string)$sMessage . " [" . $_SERVER["REQUEST_METHOD"] . " " . $_SERVER["REQUEST_URI"] . "]");
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

function getCurrentScriptName() {
    $sScriptName = isset($_SERVER["SCRIPT_NAME"]) ? (string)$_SERVER["SCRIPT_NAME"] : "";
    $sScriptName = str_replace("\\", "/", $sScriptName);
    $sScriptName = basename($sScriptName);
    return $sScriptName != "" ? $sScriptName : "index.php";
}

function getMenuItems($oPdo) {
    try {
        return getMenuItemsFromDatabase($oPdo);
    } catch (Exception $oException) {
        error_log((string)$oException);
        return array();
    }
}

function renderMenu() {
    global $oPdo, $sBaseUrl, $sMenuEmoji;

    $aItems = getMenuItems($oPdo);
    if (!$aItems) {
        return;
    }
    $sCurrentPath = getCurrentMenuPath();
    echo "    <span class=\"menu\" data-menu>\n",
        "      <button type=\"button\" class=\"menu-button\" data-menu-button aria-haspopup=\"true\" aria-expanded=\"false\" title=\"Menu\" aria-label=\"Menu\">" . $sMenuEmoji . "</button>\n",
        "      <span class=\"menu-panel\" data-menu-panel hidden>\n";
    foreach ($aItems as $aItem) {
        if ($aItem["separator"]) {
            echo "        <span class=\"menu-separator\"></span>\n";
            continue;
        }
        $sClass = "menu-link";
        $sCurrent = "";
        $sIcon = trim((string)$aItem["icon"]);
        $sTitle = trim((string)$aItem["title"]);
        $sTarget = trim((string)$aItem["target"]);
        $sTitleAttribute = $sTitle != "" ? " title=\"" . html($sTitle) . "\"" : "";
        $sTargetAttribute = $sTarget != "" && preg_match("#^(_blank|_self|_parent|_top|[A-Za-z][A-Za-z0-9_\\-]*)$#", $sTarget) ? " target=\"" . html($sTarget) . "\"" : "";
        $sRelAttribute = $sTarget == "_blank" ? " rel=\"noopener noreferrer\"" : "";
        if ($aItem["path"] === $sCurrentPath) {
            $sClass .= " menu-link-active";
            $sCurrent = " aria-current=\"page\"";
        }
        echo "        <a class=\"" . html($sClass) . "\" href=\"" . html($sBaseUrl . encodeMenuPath($aItem["relative_path"])) . "\"" . $sTitleAttribute . $sTargetAttribute . $sRelAttribute . $sCurrent . "><span class=\"menu-icon\" aria-hidden=\"true\">" . html($sIcon) . "</span><span class=\"menu-text\">" . html($aItem["name"]) . "</span></a>\n";
    }
    echo "      </span>\n",
        "    </span>\n";
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
    $sScriptName = isset($_SERVER["SCRIPT_NAME"]) ? (string)$_SERVER["SCRIPT_NAME"] : "";
    $sScriptName = str_replace("\\", "/", $sScriptName);
    $sScriptDirectory = dirname($sScriptName);
    return "/" . normalizeMenuPath(basename($sScriptDirectory)) . "/";
}

function getCurrentMenuPath() {
    $sScriptName = getCurrentScriptName();
    return $sScriptName == "index.php" ? getMenuPathPrefix() : getMenuPathPrefix() . $sScriptName;
}

function getMenuItemsFromDatabase($oPdo) {
    $aItems = array();
    if (!$oPdo) {
        return $aItems;
    }
    $sPathPrefix = getMenuPathPrefix();
    $oStatement = $oPdo->prepare("SELECT id, path, icon, name, title, target, `order` AS menu_order FROM fs_menu WHERE is_active = 1 AND path LIKE :path_prefix ORDER BY `order` ASC, id ASC");
    $oStatement->execute(array("path_prefix" => $sPathPrefix . "%"));
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $sPath = "/" . normalizeMenuPath($aRow["path"]);
        if (strpos($sPath, $sPathPrefix) !== 0) {
            continue;
        }
        $blSeparator = $aRow["icon"] === null || $aRow["name"] === null || $aRow["title"] === null;
        $sRelativePath = substr($sPath, strlen($sPathPrefix));
        if (!$blSeparator && (strpos($sRelativePath, "..") !== false || preg_match("#(^|/)\\.#", $sRelativePath) || preg_match("#[^A-Za-z0-9/_\\.\\-]#", $sRelativePath))) {
            continue;
        }
        $sName = $blSeparator ? "" : trim((string)$aRow["name"]);
        if (!$blSeparator && $sName == "") {
            $sName = $sRelativePath;
        }
        $aItems[] = array(
            "id" => (int)$aRow["id"],
            "path" => $sPath,
            "relative_path" => $sRelativePath,
            "icon" => $blSeparator ? "" : (string)$aRow["icon"],
            "name" => $sName,
            "title" => $blSeparator ? "" : (string)$aRow["title"],
            "target" => isset($aRow["target"]) ? (string)$aRow["target"] : "",
            "order" => (int)$aRow["menu_order"],
            "separator" => $blSeparator
        );
    }
    return $aItems;
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
    $sScriptName = getCurrentMenuPath();
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

function handleQuickTableFilterRequest() {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["quick_table_filter_action"])) {
        return;
    }
    if (!(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest")) {
        send403AndExit();
    }
    $sAction = (string)$_POST["quick_table_filter_action"];
    $sFilterId = isset($_POST["filter_id"]) ? (string)$_POST["filter_id"] : "table-filter";
    if ($sAction == "save") {
        $sValue = getPostedValue("filter_value");
        $sScriptName = getCurrentMenuPath();
        $sFilterId = getQuickTableFilterId($sFilterId);
        if (!isset($_SESSION["quick_table_filters"]) || !is_array($_SESSION["quick_table_filters"])) {
            $_SESSION["quick_table_filters"] = array();
        }
        if (!isset($_SESSION["quick_table_filters"][$sScriptName]) || !is_array($_SESSION["quick_table_filters"][$sScriptName])) {
            $_SESSION["quick_table_filters"][$sScriptName] = array();
        }
        $_SESSION["quick_table_filters"][$sScriptName][$sFilterId] = (string)$sValue;
        session_write_close();
        sendJsonAndExit(array("success" => true));
    } elseif ($sAction == "reset") {
        $sScriptName = getCurrentMenuPath();
        $sFilterId = getQuickTableFilterId($sFilterId);
        if (isset($_SESSION["quick_table_filters"][$sScriptName][$sFilterId])) {
            unset($_SESSION["quick_table_filters"][$sScriptName][$sFilterId]);
        }
        if (isset($_SESSION["quick_table_filters"][$sScriptName]) && is_array($_SESSION["quick_table_filters"][$sScriptName]) && !$_SESSION["quick_table_filters"][$sScriptName]) {
            unset($_SESSION["quick_table_filters"][$sScriptName]);
        }
        session_write_close();
        sendJsonAndExit(array("success" => true));
    }
    sendJsonAndExit(array("success" => false, "message" => "Invalid quick filter action."), 400);
}

function fetchPortalLoginUser($oPdo, $sUserName) {
    $oStatement = $oPdo->prepare("SELECT u.id, u.subject_id, u.user_name, u.password_hash, u.is_active, s.subject_type, s.is_active AS subject_active FROM ex_users AS u INNER JOIN ex_subjects AS s ON s.id = u.subject_id WHERE u.user_name = :user_name LIMIT 1");
    $oStatement->execute(array("user_name" => $sUserName));
    $aUser = $oStatement->fetch(PDO::FETCH_ASSOC);
    return $aUser ? $aUser : null;
}

function fetchPortalSessionUser($oPdo, $iUserId) {
    $oStatement = $oPdo->prepare("SELECT u.id, u.subject_id, u.user_name, u.is_active, s.subject_type, s.is_active AS subject_active FROM ex_users AS u INNER JOIN ex_subjects AS s ON s.id = u.subject_id WHERE u.id = :id LIMIT 1");
    $oStatement->execute(array("id" => $iUserId));
    $aUser = $oStatement->fetch(PDO::FETCH_ASSOC);
    return $aUser ? $aUser : null;
}

function fetchUserEffectivePermissions($oPdo, $iUserId, $iSubjectId) {
    $aPermissions = array();
    $oStatement = $oPdo->prepare("(SELECT p.permission_key FROM ex_user_permissions AS up INNER JOIN ex_permissions AS p ON p.id = up.permission_id WHERE up.user_id = :user_id AND up.is_allowed = 1 AND p.is_active = 1) UNION (SELECT p.permission_key FROM ex_group_permissions AS gp INNER JOIN ex_permissions AS p ON p.id = gp.permission_id INNER JOIN ex_subject_groups AS sg ON sg.group_id = gp.group_id WHERE sg.subject_id = :subject_id AND gp.is_allowed = 1 AND p.is_active = 1)");
    $oStatement->execute(array(
        "user_id" => $iUserId,
        "subject_id" => $iSubjectId
    ));
    while ($sPermissionKey = $oStatement->fetchColumn()) {
        $aPermissions[(string)$sPermissionKey] = true;
    }
    return $aPermissions;
}

function updateLastLogin($oPdo, $iUserId) {
    try {
        $oStatement = $oPdo->prepare("UPDATE ex_users SET last_login_at = NOW() WHERE id = :id");
        $oStatement->execute(array("id" => $iUserId));
    } catch (Exception $oException) {
        error_log((string)$oException);
    }
}

function permissionArrayAllowsProjectView($aPermissions, $sProject) {
    return !empty($aPermissions["portal.view"]) || !empty($aPermissions["portal.full"]) || !empty($aPermissions[$sProject . ".view"]) || !empty($aPermissions[$sProject . ".full"]);
}

function permissionArrayAllowsProjectFull($aPermissions, $sProject) {
    return !empty($aPermissions["portal.full"]) || !empty($aPermissions[$sProject . ".full"]);
}

function setAuthSession($aUser, $aPermissions) {
    $_SESSION["auth"] = true;
    $_SESSION["auth_user_id"] = (int)$aUser["id"];
    $_SESSION["auth_subject_id"] = (int)$aUser["subject_id"];
    $_SESSION["auth_user"] = (string)$aUser["user_name"];
    $_SESSION["permissions"] = $aPermissions;
    $_SESSION["auth_time"] = time();
}

function clearAuthSession() {
    unset(
        $_SESSION["auth"],
        $_SESSION["auth_user_id"],
        $_SESSION["auth_subject_id"],
        $_SESSION["auth_user"],
        $_SESSION["auth_time"],
        $_SESSION["permissions"],
        $_SESSION["ex_view_auth"],
        $_SESSION["ex_auth_user_id"],
        $_SESSION["ex_auth_subject_id"],
        $_SESSION["ex_view_auth_user"],
        $_SESSION["ex_view_auth_time"],
        $_SESSION["ex_view_permissions"],
        $_SESSION["kf_view_auth"],
        $_SESSION["kf_auth_user_id"]
    );
}

function refreshAuthSession() {
    global $oPdo;

    static $blRefreshed = false;
    static $blAuthenticated = false;
    if ($blRefreshed) {
        return $blAuthenticated;
    }
    $blRefreshed = true;
    $blAuthenticated = false;
    if (!isset($_SESSION["auth"], $_SESSION["auth_user_id"]) || $_SESSION["auth"] !== true || (int)$_SESSION["auth_user_id"] < 1) {
        clearAuthSession();
        return false;
    }
    $iAuthTime = isset($_SESSION["auth_time"]) ? (int)$_SESSION["auth_time"] : 0;
    if ($iAuthTime < 1 || $iAuthTime < time() - 1200) {
        clearAuthSession();
        return false;
    }
    if (!$oPdo) {
        clearAuthSession();
        return false;
    }
    try {
        $aUser = fetchPortalSessionUser($oPdo, (int)$_SESSION["auth_user_id"]);
        if (!$aUser || (int)$aUser["is_active"] != 1 || (int)$aUser["subject_active"] != 1 || !in_array((string)$aUser["subject_type"], array("person", "service"), true)) {
            clearAuthSession();
            return false;
        }
        $aPermissions = fetchUserEffectivePermissions($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"]);
        if (!$aPermissions) {
            clearAuthSession();
            return false;
        }
        setAuthSession($aUser, $aPermissions);
        $blAuthenticated = true;
        return true;
    } catch (Exception $oException) {
        error_log((string)$oException);
        clearAuthSession();
        return false;
    }
}

function isProjectViewAllowed($sProject) {
    return refreshAuthSession() && isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && permissionArrayAllowsProjectView($_SESSION["permissions"], $sProject);
}

function isProjectFullAllowed($sProject) {
    return refreshAuthSession() && isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && permissionArrayAllowsProjectFull($_SESSION["permissions"], $sProject);
}

function isFullAccessAllowed($aAllowedIps, $sProject) {
    return isTrustedClient($aAllowedIps) || isProjectFullAllowed($sProject);
}

function getPageTitleText($sTitle, $aAllowedIps) {
    $aStates = array();
    if (isTrustedClient($aAllowedIps)) {
        $aStates[] = "Trusted";
    }
    if (refreshAuthSession()) {
        $aStates[] = "Authenticated";
    }
    if (count($aStates) > 0) {
        $sTitle .= " — " . implode(" + ", $aStates);
    }
    return $sTitle;
}

function getLoginMessageHtml($sMessage) {
    return $sMessage ? "    <p class=\"message-error login-message\">" . htmlspecialchars($sMessage, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</p>\n" : "";
}

function getCsrfToken($sTokenName) {
    if (!isset($_SESSION[$sTokenName]) || !is_string($_SESSION[$sTokenName]) || $_SESSION[$sTokenName] == "") {
        $_SESSION[$sTokenName] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$sTokenName];
}

function resetNamedCsrfToken($sTokenName) {
    $_SESSION[$sTokenName] = bin2hex(random_bytes(32));
    return $_SESSION[$sTokenName];
}

function isNamedCsrfTokenValid($sTokenName, $sToken) {
    $sSessionToken = isset($_SESSION[$sTokenName]) ? (string)$_SESSION[$sTokenName] : "";
    return $sToken != "" && $sSessionToken != "" && hash_equals($sSessionToken, $sToken);
}

function getPostedCsrfToken($sTokenName) {
    if (isset($_POST[$sTokenName])) {
        return (string)$_POST[$sTokenName];
    }
    return isset($_SERVER["HTTP_X_CSRF_TOKEN"]) ? (string)$_SERVER["HTTP_X_CSRF_TOKEN"] : "";
}

function requireNamedCsrfToken($sTokenName, $blJsonResponse = false) {
    if (!isNamedCsrfTokenValid($sTokenName, getPostedCsrfToken($sTokenName))) {
        if ($blJsonResponse && (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest")) {
            sendJsonAndExit(array("success" => false, "message" => "Invalid security token."), 403);
        }
        send403AndExit();
    }
}

function getCurrentUrlWithoutAuthActionForToken($sTokenName) {
    $sPath = isset($_SERVER["REQUEST_URI"]) ? (string)$_SERVER["REQUEST_URI"] : "";
    $aParts = parse_url($sPath);
    $sResult = isset($aParts["path"]) ? $aParts["path"] : "";
    $aQuery = array();
    if (isset($aParts["query"]) && $aParts["query"] != "") {
        parse_str($aParts["query"], $aQuery);
        unset($aQuery["logout"]);
        unset($aQuery[$sTokenName]);
    }
    if (count($aQuery) > 0) {
        $sResult .= "?" . http_build_query($aQuery, "", "&");
    }
    return $sResult == "" ? "/" : $sResult;
}

function getLoginToken() {
    if (!isset($_SESSION["login_token"]) || !is_string($_SESSION["login_token"]) || $_SESSION["login_token"] == "") {
        $_SESSION["login_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["login_token"];
}

function resetLoginToken() {
    $_SESSION["login_token"] = bin2hex(random_bytes(32));
    return $_SESSION["login_token"];
}

function redirectLoginForm($sTokenName, $sMessage = "") {
    if ($sMessage != "") {
        $_SESSION["login_message"] = $sMessage;
    }
    resetLoginToken();
    sendSecurityHeaders();
    header("Location: " . getCurrentUrlWithoutAuthActionForToken($sTokenName), true, 303);
    exit;
}

function redirectLoginForbidden($sTokenName) {
    $_SESSION["login_cancel_forbidden"] = true;
    resetLoginToken();
    sendSecurityHeaders();
    header("Location: " . getCurrentUrlWithoutAuthActionForToken($sTokenName), true, 303);
    exit;
}

function getLoginDelaySeconds() {
    $iFailures = isset($_SESSION["login_failures"]) ? (int)$_SESSION["login_failures"] : 0;
    $iLastFailure = isset($_SESSION["login_last_failure"]) ? (int)$_SESSION["login_last_failure"] : 0;
    if ($iFailures < 5 || $iLastFailure < 1) {
        return 0;
    }
    $iDelay = 300 - (time() - $iLastFailure);
    return $iDelay > 0 ? $iDelay : 0;
}

function renderLoginPageAndExit($sTokenName, $sMessage = "") {
    global $sBaseUrl;

    if (isset($_SESSION["login_message"]) && is_string($_SESSION["login_message"])) {
        if ($sMessage == "") {
            $sMessage = $_SESSION["login_message"];
        }
        unset($_SESSION["login_message"]);
    }
    $iTime = sendPageHeaders();
    $sScriptDirectory = dirname((string)$_SERVER["SCRIPT_FILENAME"]);
    $sLoginScriptUrl = htmlspecialchars($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime($sScriptDirectory . "/js/admin.js")), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sFaviconUrl = htmlspecialchars($sBaseUrl . "favicon.ico", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sAdminCssUrl = htmlspecialchars($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime($sScriptDirectory . "/css/admin.css")), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sAction = htmlspecialchars(getCurrentUrlWithoutAuthActionForToken($sTokenName), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sToken = htmlspecialchars(getLoginToken(), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sMessageHtml = getLoginMessageHtml($sMessage);
    echo "<!DOCTYPE html>\n",
        "<html lang=\"en-US\" dir=\"ltr\">\n",
        "<head>\n",
        "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n",
        "  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n",
        "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no\">\n",
        "  <link rel=\"icon\" href=\"" . $sFaviconUrl . "\" type=\"image/x-icon\">\n",
        "  <link rel=\"shortcut icon\" href=\"" . $sFaviconUrl . "\" type=\"image/x-icon\">\n",
        "  <title>Sign In</title>\n",
        "  <meta name=\"date\" content=\"" . gmdate("D, d M Y H:i:s", $iTime) . " GMT\">\n",
        "  <link href=\"" . $sAdminCssUrl . "\" rel=\"stylesheet\" type=\"text/css\">\n",
        "</head>\n",
        "<body class=\"login-page\">\n",
        "  <div class=\"confirm-dialog login-dialog\">\n",
        "    <form class=\"confirm-dialog-box login-form\" method=\"post\" action=\"" . $sAction . "\" enctype=\"application/x-www-form-urlencoded\">\n",
        "      <input type=\"hidden\" name=\"login_token\" value=\"" . $sToken . "\">\n",
        "      <div class=\"confirm-dialog-header\">\n",
        "        <strong>Sign In</strong>\n",
        "        <button type=\"submit\" name=\"action\" value=\"cancel\" class=\"confirm-dialog-close\" aria-label=\"Close\" formnovalidate>&times;</button>\n",
        "      </div>\n",
        "      <div class=\"login-fields\">\n",
        "        <label for=\"login-user\">User Name</label>\n",
        "        <input type=\"text\" id=\"login-user\" name=\"user_name\" autocomplete=\"username\" required autofocus>\n",
        "        <label for=\"login-password\">Password</label>\n",
        "        <input type=\"password\" id=\"login-password\" name=\"password\" autocomplete=\"current-password\" required>\n",
        $sMessageHtml
        . "      </div>\n",
        "      <div class=\"confirm-dialog-actions\">\n",
        "        <button type=\"submit\" name=\"action\" value=\"login\" class=\"confirm-dialog-button\">Login</button>\n",
        "        <button type=\"submit\" name=\"action\" value=\"cancel\" class=\"confirm-dialog-button\" formnovalidate>Cancel</button>\n",
        "      </div>\n",
        "    </form>\n",
        "  </div>\n",
        "  <script type=\"text/javascript\" src=\"" . $sLoginScriptUrl . "\"></script>\n",
        "</body>\n",
        "</html>\n";
    exit;
}

function handleLoginPost($sTokenName) {
    global $oPdo;

    $sToken = isset($_POST["login_token"]) ? (string)$_POST["login_token"] : "";
    $sSessionToken = isset($_SESSION["login_token"]) ? (string)$_SESSION["login_token"] : "";
    $sUserName = isset($_POST["user_name"]) ? trim((string)$_POST["user_name"]) : "";
    $sPassword = isset($_POST["password"]) ? (string)$_POST["password"] : "";
    $iDelay = getLoginDelaySeconds();

    if ($iDelay > 0) {
        redirectLoginForm($sTokenName, "Too many failed attempts. Try again later.");
    }
    if ($sToken == "" || $sSessionToken == "" || !hash_equals($sSessionToken, $sToken)) {
        redirectLoginForm($sTokenName, "Invalid sign-in request.");
    }
    $aUser = fetchPortalLoginUser($oPdo, $sUserName);
    $aPermissions = array();
    $blValidLogin = $aUser
        && (int)$aUser["is_active"] == 1
        && (int)$aUser["subject_active"] == 1
        && in_array((string)$aUser["subject_type"], array("person", "service"), true)
        && password_verify($sPassword, (string)$aUser["password_hash"]);
    if ($blValidLogin) {
        $aPermissions = fetchUserEffectivePermissions($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"]);
    }
    if ($blValidLogin) {
        session_regenerate_id(true);
        setAuthSession($aUser, $aPermissions);
        resetNamedCsrfToken($sTokenName);
        unset($_SESSION["login_failures"], $_SESSION["login_last_failure"], $_SESSION["login_token"], $_SESSION["login_cancel_forbidden"]);
        updateLastLogin($oPdo, (int)$aUser["id"]);
        sendSecurityHeaders();
        header("Location: " . getCurrentUrlWithoutAuthActionForToken($sTokenName), true, 303);
        exit;
    }
    $_SESSION["login_failures"] = isset($_SESSION["login_failures"]) ? (int)$_SESSION["login_failures"] + 1 : 1;
    $_SESSION["login_last_failure"] = time();
    redirectLoginForm($sTokenName, "Invalid user name or password.");
}

function requireViewAccess($aAllowedIps, $sProject, $sTokenName, $blJsonResponse = false) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "logout") {
        requireNamedCsrfToken($sTokenName, $blJsonResponse);
        clearAuthSession();
        session_regenerate_id(true);
        resetNamedCsrfToken($sTokenName);
        sendSecurityHeaders();
        header("Location: " . getCurrentUrlWithoutAuthActionForToken($sTokenName), true, 303);
        exit;
    }
    if (isset($_GET["logout"]) && refreshAuthSession()) {
        $sToken = isset($_GET[$sTokenName]) ? (string)$_GET[$sTokenName] : "";
        if (!isNamedCsrfTokenValid($sTokenName, $sToken)) {
            send403AndExit();
        }
        clearAuthSession();
        session_regenerate_id(true);
        resetNamedCsrfToken($sTokenName);
        sendSecurityHeaders();
        header("Location: " . getCurrentUrlWithoutAuthActionForToken($sTokenName), true, 303);
        exit;
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "cancel") {
        $blAuthenticated = refreshAuthSession();
        if (isTrustedClient($aAllowedIps) || $blAuthenticated) {
            unset($_SESSION["login_cancel_forbidden"]);
            resetLoginToken();
            sendSecurityHeaders();
            header("Location: " . getCurrentUrlWithoutAuthActionForToken($sTokenName), true, 303);
            exit;
        }
        redirectLoginForbidden($sTokenName);
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "login") {
        if (refreshAuthSession()) {
            unset($_SESSION["login_cancel_forbidden"]);
            resetLoginToken();
            sendSecurityHeaders();
            header("Location: " . getCurrentUrlWithoutAuthActionForToken($sTokenName), true, 303);
            exit;
        }
        handleLoginPost($sTokenName);
    }
    $blAuthenticated = refreshAuthSession();
    if (isTrustedClient($aAllowedIps)) {
        unset($_SESSION["login_cancel_forbidden"]);
        return;
    }
    if ($blAuthenticated) {
        unset($_SESSION["login_cancel_forbidden"]);
        if (isProjectViewAllowed($sProject)) {
            return;
        }
        if ($blJsonResponse && (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest")) {
            sendJsonAndExit(array("success" => false, "message" => "Access is denied."), 403);
        }
        send403AndExit();
    }
    if ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_SESSION["login_cancel_forbidden"]) && $_SESSION["login_cancel_forbidden"] === true) {
        unset($_SESSION["login_cancel_forbidden"]);
        send403AndExit();
    }
    if ($blJsonResponse && (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest")) {
        sendJsonAndExit(array("success" => false, "message" => "Sign-in is required."), 403);
    }
    renderLoginPageAndExit($sTokenName);
}

function requireFullAccess($aAllowedIps, $sProject, $sTokenName, $blJsonResponse = false) {
    requireViewAccess($aAllowedIps, $sProject, $sTokenName, $blJsonResponse);
    if (isFullAccessAllowed($aAllowedIps, $sProject)) {
        return;
    }
    if ($blJsonResponse && (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest")) {
        sendJsonAndExit(array("success" => false, "message" => "Full access is required."), 403);
    }
    send403AndExit();
}

function html($mValue) {
    return htmlspecialchars((string)$mValue, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function htmlValue($mValue, $sEmptyValue = "&#10134;") {
    $sValue = trim((string)$mValue);
    return $sValue != "" ? html($sValue) : $sEmptyValue;
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

function renderCopyAction($mValue, $sTitle = "Copy") {
    global $sCopyEmoji;

    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return "";
    }
    return "<a class=\"copy-action\" href=\"#\" data-copy-value=\"" . html($sValue) . "\" title=\"" . html($sTitle) . "\" aria-label=\"" . html($sTitle) . "\"><span class=\"copy-action-box\">" . $sCopyEmoji . "</span></a>";
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

function phoneContactTypes() {
    return array(
        "landline" => true,
        "cell" => true,
        "fax" => true,
        "pager" => true
    );
}

function isPhoneContactType($sContactType) {
    $aPhoneTypes = phoneContactTypes();
    return isset($aPhoneTypes[(string)$sContactType]);
}

function phoneMetadataRegex($sPattern) {
    return preg_replace("/\\s+/", "", trim((string)$sPattern));
}

function phonePatternMatches($sPattern, $sValue, $blFullMatch = true, &$aMatches = null) {
    $sPattern = phoneMetadataRegex($sPattern);
    $aMatches = array();
    if ($sPattern == "") {
        return false;
    }
    $sRegex = $blFullMatch ? "~^(?:" . str_replace("~", "\\~", $sPattern) . ")$~" : "~^(?:" . str_replace("~", "\\~", $sPattern) . ")~";
    return @preg_match($sRegex, (string)$sValue, $aMatches);
}

function phoneMetadata() {
    static $aMetadata = null;

    if ($aMetadata !== null) {
        return $aMetadata;
    }
    $aMetadata = array("codes" => array());
    if (!function_exists("simplexml_load_file")) {
        return $aMetadata;
    }
    $sFile = __DIR__ . "/ex/lib/phone_metadata.xml";
    $blPreviousLibxmlState = libxml_use_internal_errors(true);
    $sXml = is_file($sFile) ? file_get_contents($sFile) : "";
    $sXml = preg_replace("/^\\xEF\\xBB\\xBF/", "", (string)$sXml);
    $oXml = $sXml != "" ? simplexml_load_string($sXml) : false;
    libxml_clear_errors();
    libxml_use_internal_errors($blPreviousLibxmlState);
    if (!$oXml || !isset($oXml->territories->territory)) {
        return $aMetadata;
    }
    foreach ($oXml->territories->territory as $oTerritory) {
        $sCountryCode = (string)$oTerritory["countryCode"];
        if ($sCountryCode == "") {
            continue;
        }
        $aFormats = array();
        if (isset($oTerritory->availableFormats->numberFormat)) {
            foreach ($oTerritory->availableFormats->numberFormat as $oFormat) {
                $aLeadingDigits = array();
                foreach ($oFormat->leadingDigits as $oLeadingDigits) {
                    $aLeadingDigits[] = phoneMetadataRegex((string)$oLeadingDigits);
                }
                $aFormats[] = array(
                    "pattern" => phoneMetadataRegex((string)$oFormat["pattern"]),
                    "format" => (string)$oFormat->format,
                    "leading_digits" => $aLeadingDigits
                );
            }
        }
        if (!isset($aMetadata["codes"][$sCountryCode])) {
            $aMetadata["codes"][$sCountryCode] = array();
        }
        $aMetadata["codes"][$sCountryCode][] = array(
            "id" => (string)$oTerritory["id"],
            "main" => (string)$oTerritory["mainCountryForCode"] == "true",
            "leading_digits" => phoneMetadataRegex((string)$oTerritory["leadingDigits"]),
            "national_prefix" => preg_replace("/\\D/", "", (string)$oTerritory["nationalPrefix"]),
            "pattern" => phoneMetadataRegex((string)$oTerritory->generalDesc->nationalNumberPattern),
            "formats" => $aFormats
        );
    }
    return $aMetadata;
}

function findPhoneTerritory($sDigits) {
    $aMetadata = phoneMetadata();
    $iMaxCountryCodeLength = min(3, strlen((string)$sDigits) - 1);
    for ($iLength = $iMaxCountryCodeLength; $iLength >= 1; $iLength--) {
        $sCountryCode = substr((string)$sDigits, 0, $iLength);
        if (!isset($aMetadata["codes"][$sCountryCode])) {
            continue;
        }
        $sNationalNumber = substr((string)$sDigits, $iLength);
        foreach ($aMetadata["codes"][$sCountryCode] as $aTerritory) {
            $aNationalNumbers = array($sNationalNumber);
            if ($aTerritory["national_prefix"] != "" && strpos($sNationalNumber, (string)$aTerritory["national_prefix"]) === 0) {
                $aNationalNumbers[] = substr($sNationalNumber, strlen((string)$aTerritory["national_prefix"]));
            }
            foreach ($aNationalNumbers as $sCandidateNationalNumber) {
                if ($aTerritory["leading_digits"] != "" && !phonePatternMatches((string)$aTerritory["leading_digits"], $sCandidateNationalNumber, false)) {
                    continue;
                }
                if (phonePatternMatches((string)$aTerritory["pattern"], $sCandidateNationalNumber, true)) {
                    return array(
                        "country_code" => $sCountryCode,
                        "national_number" => $sCandidateNationalNumber,
                        "territory" => $aTerritory
                    );
                }
            }
        }
        return false;
    }
    return false;
}

function phoneDefaultFormats($sCountryCode) {
    $aMetadata = phoneMetadata();
    $aFallbackFormats = array();
    if (!isset($aMetadata["codes"][(string)$sCountryCode])) {
        return array();
    }
    foreach ($aMetadata["codes"][(string)$sCountryCode] as $aTerritory) {
        if (count($aTerritory["formats"]) > 0 && !empty($aTerritory["main"])) {
            return $aTerritory["formats"];
        }
        if (!$aFallbackFormats && count($aTerritory["formats"]) > 0) {
            $aFallbackFormats = $aTerritory["formats"];
        }
    }
    return $aFallbackFormats;
}

function applyPhoneNumberFormat($sPattern, $sFormat, $sNationalNumber) {
    $aMatches = array();
    $sFormatted = (string)$sFormat;
    if ($sFormatted == "" || !phonePatternMatches($sPattern, $sNationalNumber, true, $aMatches)) {
        return "";
    }
    for ($iIndex = 1; $iIndex < count($aMatches); $iIndex++) {
        $sFormatted = str_replace("$" . $iIndex, $aMatches[$iIndex], $sFormatted);
    }
    return $sFormatted;
}

function formatPhoneContactDisplayValue($sCountryCode, $sNationalNumber, $aTerritory) {
    $aFormats = count($aTerritory["formats"]) > 0 ? $aTerritory["formats"] : phoneDefaultFormats($sCountryCode);
    foreach ($aFormats as $aFormat) {
        $aLeadingDigits = $aFormat["leading_digits"];
        if (count($aLeadingDigits) > 0 && !phonePatternMatches($aLeadingDigits[count($aLeadingDigits) - 1], $sNationalNumber, false)) {
            continue;
        }
        $sFormatted = applyPhoneNumberFormat((string)$aFormat["pattern"], (string)$aFormat["format"], $sNationalNumber);
        if ($sFormatted != "") {
            return "+" . (string)$sCountryCode . " " . $sFormatted;
        }
    }
    return "+" . (string)$sCountryCode . " " . (string)$sNationalNumber;
}

function analyzePhoneContactValue($sValue) {
    $sText = trim((string)$sValue);
    $sDigits = "";
    $aPhone = array();
    if ($sText == "") {
        return array("valid" => true, "canonical" => "", "display" => "");
    }
    if (!preg_match("/^(?:\\+|00)/", $sText) && preg_match("/^[0-9().\\s\\-]+$/", $sText)) {
        $sDigits = preg_replace("/\\D/", "", $sText);
        $sText = "+420" . $sDigits;
    }
    if (!preg_match("/^(?:\\+|00)[0-9().\\s\\-]+$/", $sText)) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    if (strpos($sText, "+") === 0) {
        $sDigits = preg_replace("/\\D/", "", substr($sText, 1));
    } else {
        $sDigits = preg_replace("/\\D/", "", substr($sText, 2));
    }
    if (!preg_match("/^[1-9][0-9]{5,14}$/", $sDigits)) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    $aPhone = findPhoneTerritory($sDigits);
    if ($aPhone === false) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    return array(
        "valid" => true,
        "canonical" => "+" . (string)$aPhone["country_code"] . "." . (string)$aPhone["national_number"],
        "display" => formatPhoneContactDisplayValue((string)$aPhone["country_code"], (string)$aPhone["national_number"], $aPhone["territory"])
    );
}

function normalizePhoneContactValue($sValue) {
    $aPhone = analyzePhoneContactValue($sValue);
    if (empty($aPhone["valid"])) {
        return false;
    }
    if (strpos((string)$aPhone["canonical"], "00") === 0) {
        return "+" . substr((string)$aPhone["canonical"], 2);
    }
    return (string)$aPhone["canonical"];
}

function phoneContactDisplayValue($sValue) {
    $aPhone = analyzePhoneContactValue($sValue);
    return !empty($aPhone["valid"]) ? (string)$aPhone["display"] : (string)$sValue;
}

function phoneContactHref($sValue) {
    $aPhone = analyzePhoneContactValue($sValue);
    return !empty($aPhone["valid"]) && $aPhone["canonical"] != "" ? "tel:" . str_replace(".", "", (string)$aPhone["canonical"]) : "";
}

function contactTypeKey($sContactType) {
    return strtolower(trim((string)$sContactType));
}

function renderContactValueText($sType, $sValue, $sTooltipAttribute = "") {
    $sDisplayValue = contactDisplayValue($sType, $sValue);
    $sClass = "contact-value" . (contactValueIsInvalid($sType, $sValue) ? " invalid-contact-value" : "");
    return "<span class=\"" . html($sClass) . "\"" . $sTooltipAttribute . ">" . html($sDisplayValue) . "</span>";
}

function renderContactValueActions($sType, $sValue, $blShowCopy = false, $blAllowExternalLinks = false) {
    global $sCopyEmoji;

    $sDisplayValue = contactDisplayValue($sType, $sValue);
    $sHref = contactHref($sType, $sValue, $blAllowExternalLinks);
    $sHtml = "";
    $sLinkTitle = "";
    $blHasIcon = false;
    if ($blShowCopy && $sDisplayValue != "") {
        $sHtml .= "<a class=\"contact-copy\" href=\"#\" title=\"Copy\" aria-label=\"Copy\"><span class=\"copy-action-box\">" . $sCopyEmoji . "</span></a>";
        $blHasIcon = true;
    }
    if ($sHref != "") {
        $sTarget = $blAllowExternalLinks && preg_match("#^https?://#i", $sHref) ? " target=\"_blank\" rel=\"noopener noreferrer\"" : "";
        $sLinkTitle = contactLinkTitle($sType);
        return $sHtml . ($blHasIcon ? "" : " ") . "<a class=\"contact-link\" href=\"" . html($sHref) . "\"" . $sTarget . " title=\"" . html($sLinkTitle) . "\" aria-label=\"" . html($sLinkTitle) . "\">" . contactLinkEmoji($sType) . "</a>";
    }
    return $sHtml;
}

function decodePostedBase64Value($sValue) {
    $sDecoded = base64_decode((string)$sValue, true);
    return $sDecoded !== false ? $sDecoded : (string)$sValue;
}

function getPostedValue($sName, $sDefault = "") {
    $sEncodedName = $sName . "_b64";
    if (isset($_POST[$sEncodedName]) && !is_array($_POST[$sEncodedName])) {
        return decodePostedBase64Value($_POST[$sEncodedName]);
    }
    if (isset($_POST[$sName]) && !is_array($_POST[$sName])) {
        return (string)$_POST[$sName];
    }
    return (string)$sDefault;
}

function getPostedTrimmedValue($sName, $sDefault = "") {
    return trim(getPostedValue($sName, $sDefault));
}

function schemaColumnTypeDisplay($sColumnType, $blShorten = true) {
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

function dumpVar($mVar) {
    return formatDumpVarValue($mVar, 0);
}

function formatDumpVarValue($mVar, $iLevel) {
    if (is_array($mVar)) {
        return formatDumpVarArray($mVar, $iLevel);
    }
    if (is_object($mVar)) {
        return formatDumpVarObject($mVar, $iLevel);
    }
    if (is_bool($mVar)) {
        return "<span style=\"font-weight: bold !important;\">" . ($mVar ? "true" : "false") . "</span>";
    }
    if ($mVar === null) {
        return "<span style=\"color: #808 !important; font-weight: bold !important; font-style: italic !important;\">null</span>";
    }
    if (is_int($mVar)) {
        return "<span style=\"color: #888 !important;\">int:</span> <span style=\"color: #088 !important; font-weight: bold !important;\">" . htmlspecialchars((string)$mVar, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
    }
    if (is_float($mVar)) {
        return "<span style=\"color: #888 !important;\">float:</span> <span style=\"color: #080 !important; font-weight: bold !important;\">" . htmlspecialchars((string)$mVar, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
    }
    return "<span style=\"color: #080 !important;\">\"" . htmlspecialchars((string)$mVar, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"</span>";
}

function formatDumpVarArray($aArray, $iLevel) {
    if (!$aArray) {
        return "<span style=\"font-weight: bold !important; color: #F0F !important;\">Array</span><span style=\"color: #000 !important;\">()</span>\n";
    }
    $sOutput = "<span style=\"font-weight: bold !important; color: #F0F !important;\">Array</span><span style=\"color: #000 !important;\">(" . count($aArray) . ")</span>\n";
    $sOutput .= getDumpVarIndentation($iLevel) . "<span style=\"color: #000 !important;\">(</span>\n";
    foreach ($aArray as $mKey => $mValue) {
        $sOutput .= getDumpVarIndentation($iLevel + 1) . formatDumpVarKey($mKey);
        if (is_array($mValue) || is_object($mValue)) {
            $sOutput .= formatDumpVarValue($mValue, $iLevel + 1);
        } else {
            $sOutput .= formatDumpVarValue($mValue, $iLevel + 1) . "\n";
        }
    }
    $sOutput .= getDumpVarIndentation($iLevel) . "<span style=\"color: #000 !important;\">)</span>\n";
    return $sOutput;
}

function formatDumpVarObject($oObject, $iLevel) {
    $aProperties = get_object_vars($oObject);
    $sClassName = htmlspecialchars(get_class($oObject), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    if (!$aProperties) {
        return "<span style=\"color: #000 !important;\">" . $sClassName . " </span><span style=\"font-weight: bold !important; color: #F00 !important;\">Object</span><span style=\"color: #000 !important;\">()</span>\n";
    }
    $sOutput = "<span style=\"color: #000 !important;\">" . $sClassName . " </span><span style=\"font-weight: bold !important; color: #F00 !important;\">Object</span><span style=\"color: #000 !important;\">(" . count($aProperties) . ")</span>\n";
    $sOutput .= getDumpVarIndentation($iLevel) . "<span style=\"color: #000 !important;\">(</span>\n";
    foreach ($aProperties as $sKey => $mValue) {
        $sOutput .= getDumpVarIndentation($iLevel + 1) . formatDumpVarKey($sKey);
        if (is_array($mValue) || is_object($mValue)) {
            $sOutput .= formatDumpVarValue($mValue, $iLevel + 1);
        } else {
            $sOutput .= formatDumpVarValue($mValue, $iLevel + 1) . "\n";
        }
    }
    $sOutput .= getDumpVarIndentation($iLevel) . "<span style=\"color: #000 !important;\">)</span>\n";
    return $sOutput;
}

function formatDumpVarKey($mKey) {
    $sKey = htmlspecialchars((string)$mKey, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    if ($sKey != "" && $sKey[0] == "_") {
        return "<span style=\"color: #BBB !important;\">[" . $sKey . "] => </span>";
    }
    if (strpos($sKey, "__") !== false) {
        return "<span style=\"color: #000 !important;\">[</span><span style=\"font-weight: bold !important; color: #00F !important;\">" . $sKey . "</span><span style=\"color: #000 !important;\">] => </span>";
    }
    return "<span style=\"color: #000 !important;\">[" . $sKey . "] => </span>";
}

function getDumpVarIndentation($mVar) {
    $iIndentation = 3;
    if ($iIndentation > 4) {
        $iIndentation = 4;
    } elseif ($iIndentation < 1) {
        $iIndentation = 1;
    }
    $sIndentation = str_pad(" ", $iIndentation);
    $sOutput = "";
    if (is_string($mVar)) {
        $mVar = strlen($mVar) / 2;
    }
    for ($iI = 0; $iI < $mVar; $iI++) {
        $sOutput .= $sIndentation;
    }
    return $sOutput;
}
