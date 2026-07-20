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
    global $sBaseUrl;

    $sScriptDirectory = dirname((string)$_SERVER["SCRIPT_FILENAME"]);
    return "  <link href=\"" . htmlspecialchars($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime($sScriptDirectory . "/css/admin.css")), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" rel=\"stylesheet\" type=\"text/css\">\n";
}

function formatPhpGeneratedOutput($sHtml, $sStyleNonce, $sTitle) {
    if (stripos($sHtml, "<html") !== false) {
        $sHtml = preg_replace("#<style\\b[^>]*>.*?</style>\\s*#is", "", $sHtml);
        if (stripos($sHtml, "css/admin.css") === false && stripos($sHtml, "</head>") !== false) {
            $sHtml = preg_replace("#</head>#i", getPhpGeneratedStyleTag($sStyleNonce) . "</head>", $sHtml, 1);
        }
        $sHtml = preg_replace_callback("#<body\\b([^>]*)>#i", function ($aMatches) {
            $sAttributes = $aMatches[1];
            if (preg_match("#\\bclass\\s*=\\s*([\"'])(.*?)\\1#i", $sAttributes, $aClassMatches)) {
                if (strpos($aClassMatches[2], "php-generated-output") === false) {
                    $sClass = trim($aClassMatches[2] . " php-generated-output");
                    $sAttributes = preg_replace("#\\bclass\\s*=\\s*([\"'])(.*?)\\1#i", "class=" . $aClassMatches[1] . $sClass . $aClassMatches[1], $sAttributes, 1);
                }
            } else {
                $sAttributes .= " class=\"php-generated-output\"";
            }
            return "<body" . $sAttributes . ">";
        }, $sHtml, 1);
        return addPhpGeneratedViewportMeta($sHtml);
    }
    $sTitle = htmlspecialchars($sTitle, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    ob_start();
    echo "<!DOCTYPE html>\n",
        "<html lang=\"en-US\" dir=\"ltr\">\n",
        "<head>\n",
        "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n",
        "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no\">\n",
        "  <title>", $sTitle, "</title>\n",
        getPhpGeneratedStyleTag($sStyleNonce),
        "</head>\n",
        "<body class=\"php-generated-output\"><div class=\"center\">\n",
        $sHtml,
        "\n</div></body>\n",
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

function getCurrentMenuNameFromDatabase($oPdo) {
    $oStatement = $oPdo->prepare("SELECT name FROM fs_menu WHERE is_active = 1 AND path = :path AND name IS NOT NULL ORDER BY `order` ASC, id ASC LIMIT 1");
    $oStatement->execute(array("path" => getCurrentMenuPath()));
    return trim((string)$oStatement->fetchColumn());
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
    $sScriptName = getCurrentScriptName();
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
        $sScriptName = getCurrentScriptName();
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
        $sScriptName = getCurrentScriptName();
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

function userHasPermission($oPdo, $iUserId, $iSubjectId, $sPermissionKey) {
    $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM ex_permissions AS p WHERE p.permission_key = :permission_key AND p.is_active = 1 AND (EXISTS (SELECT 1 FROM ex_user_permissions AS up WHERE up.permission_id = p.id AND up.user_id = :user_id AND up.is_allowed = 1) OR EXISTS (SELECT 1 FROM ex_group_permissions AS gp INNER JOIN ex_subject_groups AS sg ON sg.group_id = gp.group_id WHERE gp.permission_id = p.id AND gp.is_allowed = 1 AND sg.subject_id = :subject_id))");
    $oStatement->execute(array(
        "permission_key" => $sPermissionKey,
        "user_id" => $iUserId,
        "subject_id" => $iSubjectId
    ));
    return (int)$oStatement->fetchColumn() > 0;
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

function isPermissionAllowed($sPermissionKey) {
    return refreshAuthSession() && isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && !empty($_SESSION["permissions"][$sPermissionKey]);
}

function isProjectViewAllowed($sProject) {
    return refreshAuthSession() && isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && permissionArrayAllowsProjectView($_SESSION["permissions"], $sProject);
}

function isProjectFullAllowed($sProject) {
    return refreshAuthSession() && isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && permissionArrayAllowsProjectFull($_SESSION["permissions"], $sProject);
}

function isViewAllowedForProject($aAllowedIps, $sProject) {
    return isTrustedClient($aAllowedIps) || isProjectViewAllowed($sProject);
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

function getLogoutUrlForToken($sTokenName) {
    $sUrl = getCurrentUrlWithoutAuthActionForToken($sTokenName);
    return $sUrl . (strpos($sUrl, "?") === false ? "?" : "&") . "logout=1&" . $sTokenName . "=" . rawurlencode(getCsrfToken($sTokenName));
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
        "  <meta name=\"theme-color\" content=\"#FFD8BB\">\n",
        "  <link rel=\"icon\" href=\"", $sFaviconUrl, "\" type=\"image/x-icon\">\n",
        "  <link rel=\"shortcut icon\" href=\"", $sFaviconUrl, "\" type=\"image/x-icon\">\n",
        "  <title>Sign In</title>\n",
        "  <meta name=\"date\" content=\"", gmdate("D, d M Y H:i:s", $iTime), " GMT\">\n",
        "  <link href=\"", $sAdminCssUrl, "\" rel=\"stylesheet\" type=\"text/css\">\n",
        "</head>\n",
        "<body class=\"login-page\">\n",
        "  <div class=\"confirm-dialog login-dialog\">\n",
        "    <form class=\"confirm-dialog-box login-form\" method=\"post\" action=\"", $sAction, "\" enctype=\"application/x-www-form-urlencoded\">\n",
        "      <input type=\"hidden\" name=\"login_token\" value=\"", $sToken, "\">\n",
        "      <div class=\"confirm-dialog-header\">\n",
        "        <strong>Sign In</strong>\n",
        "        <button type=\"submit\" name=\"action\" value=\"cancel\" class=\"confirm-dialog-close\" aria-label=\"Close\" formnovalidate>&times;</button>\n",
        "      </div>\n",
        "      <div class=\"login-fields\">\n",
        "      <label for=\"login-user\">User Name</label>\n",
        "      <input type=\"text\" id=\"login-user\" name=\"user_name\" autocomplete=\"username\" required autofocus>\n",
        "      <label for=\"login-password\">Password</label>\n",
        "      <input type=\"password\" id=\"login-password\" name=\"password\" autocomplete=\"current-password\" required>\n",
        $sMessageHtml,
        "      </div>\n",
        "      <div class=\"confirm-dialog-actions\">\n",
        "        <button type=\"submit\" name=\"action\" value=\"login\" class=\"confirm-dialog-button\">Login</button>\n",
        "        <button type=\"submit\" name=\"action\" value=\"cancel\" class=\"confirm-dialog-button\" formnovalidate>Cancel</button>\n",
        "      </div>\n",
        "    </form>\n",
        "  </div>\n",
        "  <script type=\"text/javascript\" src=\"", $sLoginScriptUrl, "\"></script>\n",
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
        if (isTrustedClient($aAllowedIps) || refreshAuthSession()) {
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
    if (isTrustedClient($aAllowedIps)) {
        unset($_SESSION["login_cancel_forbidden"]);
        return;
    }
    if (refreshAuthSession()) {
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

function htmlValue($mValue) {
    global $sEmptyValueEmoji;

    $sValue = trim((string)$mValue);
    return $sValue != "" ? html($sValue) : $sEmptyValueEmoji;
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

function schemaColumnTypeDisplay($sColumnType, $bShorten = true) {
    $sColumnType = (string)$sColumnType;
    if (preg_match("/^enum\\((.*)\\)$/i", $sColumnType, $aMatches)) {
        preg_match_all("/'((?:''|[^'])*)'/", $aMatches[1], $aEnumValues);
        $aDisplayValues = array();
        foreach ($aEnumValues[1] as $sEnumValue) {
            $aDisplayValues[] = "'" . $sEnumValue . "'";
        }
        if ($bShorten && count($aDisplayValues) > 24) {
            $aShortValues = array_slice($aDisplayValues, 0, 12);
            $aShortValues[] = "…";
            $aShortValues[] = $aDisplayValues[count($aDisplayValues) - 1];
            return "enum(" . implode(", ", $aShortValues) . ")";
        }
        return "enum(" . implode(", ", $aDisplayValues) . ")";
    }
    return $sColumnType;
}

function addPhpInfoStyleAttributes($sHtml, $sStyleNonce) {
    $sNonce = htmlspecialchars($sStyleNonce, ENT_QUOTES, "UTF-8");
    return preg_replace_callback("#<style([^>]*)>#i", function ($aMatches) use ($sNonce) {
        $sAttributes = $aMatches[1];
        if (!preg_match("#\\stype\\s*=#i", $sAttributes)) {
            $sAttributes .= " type=\"text/css\"";
        }
        if (!preg_match("#\\snonce\\s*=#i", $sAttributes)) {
            $sAttributes .= " nonce=\"" . $sNonce . "\"";
        }
        return "<style" . $sAttributes . ">";
    }, $sHtml);
}

function sendPhpInfoAndExit($sStyleNonce) {
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
    ob_start();
    phpinfo();
    echo addPhpInfoStyleAttributes(ob_get_clean(), $sStyleNonce);
    exit;
}
