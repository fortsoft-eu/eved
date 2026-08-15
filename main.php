<?php

ini_set("log_errors", "1");
ini_set("error_log", __DIR__ . "/log/error.log");


include "config.php";
include "./functions.php";


redirectIndexPhpToRoot();
redirectToCanonicalUrl();
list($sOrigin, $sBaseUrl) = getBaseUrl();
list($oPdo, $sError) = databaseConnect();
loadConfiguration($oPdo);
configureErrorReporting();
initializeSession(true);
