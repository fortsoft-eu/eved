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


$sHost = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "";
$sPath = isset($_SERVER["REQUEST_URI"]) ? parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) : "/kf/";
if (!$sPath || substr($sPath, -1) != "/") {
    $sPath = dirname($sPath) . "/";
}
$sBaseUrl = $sHost != "" ? $sScheme . "://" . $sHost . $sPath : "";


$sMenuEmoji        = "&#9776;";
$sFilterFocusEmoji = "&#128269;";
$sEmptyValueEmoji  = "&#10134;";


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

