<?php

include "config.php";
include "../functions.php";
include "functions.php";


redirectIndexPhpToRoot();


if ($blDebug && isAllowedIp($aAllowedIps)) {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
} else {
    error_reporting(0);
    ini_set("display_errors", 0);
    ini_set("display_startup_errors", 0);
}


ignore_user_abort(true);
ini_set("session.use_strict_mode", 1);
ini_set("session.use_only_cookies", 1);
ini_set("session.use_trans_sid", 0);
ini_set("session.gc_maxlifetime", 31536000);
session_name("EVEDEXSID");
session_set_cookie_params(array(
    "lifetime" => 31536000,
    "path" => "/ex/",
    "domain" => "",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax"
));
session_start();


handleQuickTableFilterRequest();


$sHost = $_SERVER["HTTP_HOST"];
$sPrefix = preg_replace("/\..*$/", "", $sHost);
$sPattern = "#^/" . preg_quote($sPrefix, "#") . "(/.*)?$#i";


if (preg_match($sPattern, $_SERVER["REQUEST_URI"])) {
    $sNewUri = preg_replace("#^/" . preg_quote($sPrefix, "#") . "#i", "", $_SERVER["REQUEST_URI"]);
    if ($sNewUri == "" || $sNewUri[0] != "/") {
        $sNewUri = "/" . $sNewUri;
    }
    sendSecurityHeaders();
    header("Location: " . $sScheme . "://" . $sHost . $sNewUri, true, 301);
    exit;
}

$sPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if (substr($sPath, -1) != "/") {
    $sPath = dirname($sPath) . "/";
}
$sBaseUrl = $sScheme . "://" . $sHost . $sPath;


$sMenuEmoji                 = "&#9776;";
$sEditEmoji                 = "&#128221;";
$sDeleteEmoji               = "&#128465;&#65039;";
$sAddEmoji                  = "&#10133;";
$sHiddenInactiveEmoji       = "&#128451;&#65039;";
$sPortalEmoji               = "&#128272;";
$sEmptyValueEmoji           = "&#10134;";
$sThrobberEmoji             = "&#8987;";
$sFilterFocusEmoji          = "&#128269;";
$sCopyEmoji                 = "&#128203;";
$sCopySuccessEmoji          = "&#10004;&#65039;";
$sCopyFailureEmoji          = "&#10060;&#65039;";
$sPrimaryEmoji              = "&#11088;";
$sInactiveEmoji             = "&#9940;";
$sMergeEmoji                = "&#128260;";
$sMoveUpEmoji               = "&#9650;";
$sMoveDownEmoji             = "&#9660;";
$sBirthdayServedEmoji       = "&#9745;&#65039;";
$sCommunicationServedEmoji  = "&#128232;";
$sContactEmailEmoji         = "&#128231;";
$sContactLandlineEmoji      = "&#128222;";
$sContactCellEmoji          = "&#128241;";
$sContactFaxEmoji           = "&#128224;";
$sContactPagerEmoji         = "&#128223;";
$sContactWebEmoji           = "&#127760;";
$sContactTelegramEmoji      = "&#9992;&#65039;";
$sContactMessageEmoji       = "&#128172;";
$sContactYouTubeEmoji       = "&#9654;&#65039;";
$iCalendarFirstDay          = 1;
$sDateInputFormat           = "YYYY-MM-DD";
$sDateInputPattern          = "\\d{4}-\\d{2}-\\d{2}";


$sError = "";
$oPdo = null;


try {
    $oPdo = new PDO(
        "mysql:host=" . $sDbHost . ";dbname=" . $sDbName . ";charset=utf8mb4",
        $sDbUserName,
        $sDbUserPass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false)
    );
} catch (PDOException $oException) {
    $sError = $oException->getMessage();
}
