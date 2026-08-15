<?php

$blDatabaseBackupDownloadLogin  = true;
$sMenuEmoji                     = "&#9776;";
$sFilterFocusEmoji              = "&#128269;";
$sAddEmoji                      = "&#10133;";
$sEditEmoji                     = "&#128221;";
$sDeleteEmoji                   = "&#128465;&#65039;";
$sRefreshEmoji                  = "&#128260;";
$sDoneEmoji                     = "&#9989;";
$sMoveUpEmoji                   = "&#9650;";
$sMoveDownEmoji                 = "&#9660;";
$sCalendarToggleEmoji           = "&#9662;";
$sEmptyValueEmoji               = "&#10134;";
$sThrobberEmoji                 = "&#8987;";
$sLockedEmoji                   = "&#128274;";
$sCopyEmoji                     = "&#128203;";
$sCopySuccessEmoji              = "&#10004;&#65039;";
$sCopyFailureEmoji              = "&#10060;&#65039;";
$sContactCellEmoji              = "&#128241;";
$sContactTelegramEmoji          = "&#9992;&#65039;";
$sDefaultCurrency               = "USD";
$aEmojiData                     = array(
    "copy"                      => $sCopyEmoji,
    "copy-success"              => $sCopySuccessEmoji,
    "copy-failure"              => $sCopyFailureEmoji,
    "calendar-toggle"           => $sCalendarToggleEmoji
);


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
