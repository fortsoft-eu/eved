<?php

$blDatabaseBackupDownloadLogin  = true;
$blMailRestrictFromToOneAddress = true;
$iRenderThrobberRowLimit        = 300;
$iCalendarFirstDay              = 1;
$iBirthdayDisplayMinDays        = -2;
$iBirthdayDisplayMaxDays        = 17;
$sMenuEmoji                     = "&#9776;";
$sEditEmoji                     = "&#128221;";
$sDeleteEmoji                   = "&#128465;&#65039;";
$sAddEmoji                      = "&#10133;";
$sHiddenInactiveEmoji           = "&#128451;&#65039;";
$sPortalEmoji                   = "&#128272;";
$sEmptyValueEmoji               = "&#10134;";
$sThrobberEmoji                 = "&#8987;";
$sFilterFocusEmoji              = "&#128269;";
$sCopyEmoji                     = "&#128203;";
$sCopySuccessEmoji              = "&#10004;&#65039;";
$sCopyFailureEmoji              = "&#10060;&#65039;";
$sPrimaryEmoji                  = "&#11088;";
$sInactiveEmoji                 = "&#9940;";
$sMergeEmoji                    = "&#128260;";
$sMoveUpEmoji                   = "&#9650;";
$sMoveDownEmoji                 = "&#9660;";
$sBirthdayServedEmoji           = "&#9745;&#65039;";
$sCommunicationServedEmoji      = "&#128232;";
$sContactEmailEmoji             = "&#128231;";
$sContactLandlineEmoji          = "&#128222;";
$sContactCellEmoji              = "&#128241;";
$sContactFaxEmoji               = "&#128224;";
$sContactPagerEmoji             = "&#128223;";
$sContactWebEmoji               = "&#127760;";
$sContactTelegramEmoji          = "&#9992;&#65039;";
$sContactMessageEmoji           = "&#128172;";
$sContactYouTubeEmoji           = "&#9654;&#65039;";
$sDateInputFormat               = "YYYY-MM-DD";


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
