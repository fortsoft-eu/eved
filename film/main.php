<?php

$blDatabaseBackupDownloadLogin  = true;
$iVisitTimeout                  = 1200;
$sDirectory                     = "./img";
$sExtension                     = ".avif";
$sFilterFocusEmoji              = "&#128269;";
$sMenuEmoji                     = "&#9776;";
$sCalendarToggleEmoji           = "&#9662;";
$sFilmMenuEmoji                 = "&#127902;&#65039;";
$sEquipmentLinkEmoji            = "&#128279;";


ini_set("log_errors", "1");
ini_set("error_log", __DIR__ . "/../log/error.log");


include "config.php";
include "../functions.php";


redirectIndexPhpToRoot();
redirectToCanonicalUrl();
list($sOrigin, $sBaseUrl) = getBaseUrl();
list($oPdo, $sError) = databaseConnect();
loadConfiguration($oPdo);
configureErrorReporting();
initializeSession();
handleQuickTableFilterRequest();


include "./functions.php";
