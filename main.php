<?php

include "config.php";
include "functions.php";



ini_set("log_errors", "1");
ini_set("error_log", __DIR__ . "/log/error.log");


redirectIndexPhpToRoot();


if ($blPortalDebugMode && isTrustedClient()) {
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
$sSessionName = session_name();
session_set_cookie_params(array(
    "lifetime" => 31536000,
    "path" => "/",
    "domain" => "",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax"
));
if (isset($_COOKIE[$sSessionName]) && (string)$_COOKIE[$sSessionName] != "") {
    session_start();
}


redirectToCanonicalUrl();
list($sOrigin, $sBaseUrl) = getBaseUrl();


$sError = "";
$oPdo = null;
try {
    $oPdo = new PDO(
        "mysql:host=" . $sDatabaseHost . ";dbname=" . $sDatabaseName . ";charset=utf8mb4",
        $sDatabaseUsername,
        $sDatabasePassword,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch (PDOException $oException) {
    error_log((string)$oException);
    $sError = $oException->getMessage();
}
