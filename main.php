<?php

include "config.php";
include "functions.php";


ini_set("log_errors", "1");
ini_set("error_log", __DIR__ . "/log/error.log");


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


$sError = "";
$oPdo = null;


try {
    $oPdo = new PDO("mysql:host=" . $sDbHost . ";dbname=" . $sDbName . ";charset=utf8mb4", $sDbUserName, $sDbUserPass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false)
    );
} catch (PDOException $oException) {
    error_log((string)$oException);
    $sError = $oException->getMessage();
}
