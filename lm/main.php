<?php

$blDatabaseBackupDownloadLogin  = true;
$sMenuEmoji                     = "&#9776;";
$sFilterFocusEmoji              = "&#128269;";
$sAddEmoji                      = "&#10133;";
$sEditEmoji                     = "&#128221;";
$sDeleteEmoji                   = "&#128465;&#65039;";
$sMoveUpEmoji                   = "&#9650;";
$sMoveDownEmoji                 = "&#9660;";
$sEmptyValueEmoji               = "&#10134;";
$sThrobberEmoji                 = "&#8987;";
$sCopyEmoji                     = "&#128203;";
$sCopySuccessEmoji              = "&#10004;&#65039;";
$sCopyFailureEmoji              = "&#10060;&#65039;";


ini_set("log_errors", "1");
ini_set("error_log", __DIR__ . "/../log/error.log");


include "config.php";
include "../functions.php";


redirectIndexPhpToRoot();
redirectToCanonicalUrl();
configureErrorReporting();
initializeSession();
handleQuickTableFilterRequest();


include "./functions.php";


list($sOrigin, $sBaseUrl) = getBaseUrl();
list($oPdo, $sError) = databaseConnect();
