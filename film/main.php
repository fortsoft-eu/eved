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
session_set_cookie_params(array(
    "lifetime" => 31536000,
    "path" => "/",
    "domain" => "",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax"
));
session_start();
unset(
    $_SESSION["cover"],
    $_SESSION["metadata"],
    $_SESSION["mode"],
    $_SESSION["link_last_bag_id"],
    $_SESSION["link_message"],
    $_SESSION["link_message_type"],
    $_SESSION["fs_film_ua"],
    $_SESSION["fs_film_ua_page_visits"],
    $_SESSION["fs_film_ua_fingerprint"]
);


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


$sDirectory         = "./img";
$sExtension         = ".avif";
$iVisitTimeout      = 1200;
$sFilterFocusEmoji  = "&#128269;";
$sMenuEmoji         = "&#9776;";
$sFilmMenuEmoji     = "&#127902;&#65039;";


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
    error_log((string)$oException);
    $sError = $oException->getMessage();
}
