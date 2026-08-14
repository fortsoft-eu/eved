<?php

$blDatabaseBackupDownloadLogin  = true;
$sMenuEmoji                     = "&#9776;";
$sFilterFocusEmoji              = "&#128269;";
$sAddEmoji                      = "&#10133;";
$sEditEmoji                     = "&#128221;";
$sDeleteEmoji                   = "&#128465;&#65039;";
$sCopyEmoji                     = "&#128203;";
$sCopySuccessEmoji              = "&#10004;&#65039;";
$sCopyFailureEmoji              = "&#10060;&#65039;";
$sEmptyValueEmoji               = "&#10134;";
$sPrimaryEmoji                  = "&#11088;";
$sCalendarToggleEmoji           = "&#9662;";
$sSubscriptionServedEmoji       = "&#9745;&#65039;";
$sContactEmailEmoji             = "&#128231;";
$sContactLandlineEmoji          = "&#128222;";
$sContactCellEmoji              = "&#128241;";
$sContactFaxEmoji               = "&#128224;";
$sContactPagerEmoji             = "&#128223;";
$aEmojiData                     = array(
    "copy"                      => $sCopyEmoji,
    "copy-success"              => $sCopySuccessEmoji,
    "copy-failure"              => $sCopyFailureEmoji,
    "calendar-toggle"           => $sCalendarToggleEmoji
);
$sDefaultCurrency               = "USD";


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
