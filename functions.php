<?php

function isAllowedIp($aAllowedIps) {
    return isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $aAllowedIps, true);
}

function getContentSecurityPolicySource() {
    global $sScheme;

    if (!isset($_SERVER["HTTP_HOST"]) || !isset($_SERVER["REQUEST_URI"])) {
        return "'self'";
    }
    $sRequestScheme = $sScheme;
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
