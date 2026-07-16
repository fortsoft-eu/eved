<?php

include "config.php";


function getContentSecurityPolicySource() {
    global $sScheme;

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

    return $sScheme . "://" . $sHost . $sPath;
}

function sendSecurityHeaders($sStyleNonce = "") {
    $sContentSecurityPolicySource = getContentSecurityPolicySource();
    $sStyleSource = $sContentSecurityPolicySource;
    if ($sStyleNonce != "") {
        $sStyleSource .= " 'nonce-" . $sStyleNonce . "'";
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

$sStyleNonce = base64_encode(random_bytes(16));

if (in_array($_SERVER["REMOTE_ADDR"], $aAllowedIps, true)) {
    sendPhpInfoAndExit($sStyleNonce);
}

$iTime    = time();
$iExpires = $iTime + 10;
$sDate    = gmdate("D, d M Y H:i:s", $iTime);
$sExpires = gmdate("D, d M Y H:i:s", $iExpires);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache", true);
header("Last-Modified: " . $sDate . " GMT", true);
header("Expires: " . $sExpires . " GMT", true);
header("Content-Type: text/html; charset=utf-8", true);
header("Content-Language: he", true);
header("X-Robots-Tag: noindex, nofollow", true);
sendSecurityHeaders($sStyleNonce);

?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
    <head>
        <title>עבד יהוה</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css" nonce="<?php echo htmlspecialchars($sStyleNonce, ENT_QUOTES, "UTF-8"); ?>">
            body {
                background-color: #FFF;
                font-family: "Times New Roman", Times, serif;
                color: #000;
                font-size: 24px;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                text-align: center;
                flex-direction: column;
            }
            h1, h2 {
                margin: 10px 0;
            }
        </style>
    </head>
    <body>

        <h1>עֶבֶד יְהוָה</h1>
        <h2>וְאָנֹכִי וּבֵיתִי נַעֲבֹד אֶת־יְהוָה</h2>

    </body>
</html>
