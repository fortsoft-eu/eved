<?php

$blDatabaseBackupDownloadLogin  = true;
$blMailRestrictFromToOneAddress = true;
$blDirectoryAriaAttributes      = false;
$blSubjectsAriaAttributes       = false;
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
$sPhoneBookEmoji                = "&#128242;";
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
$sCalendarToggleEmoji           = "&#9662;";
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
$aEmojiData                     = array(
    "edit"                      => $sEditEmoji,
    "delete"                    => $sDeleteEmoji,
    "add"                       => $sAddEmoji,
    "hidden-inactive"           => $sHiddenInactiveEmoji,
    "portal"                    => $sPortalEmoji,
    "empty-value"               => $sEmptyValueEmoji,
    "throbber"                  => $sThrobberEmoji,
    "filter-focus"              => $sFilterFocusEmoji,
    "copy"                      => $sCopyEmoji,
    "copy-success"              => $sCopySuccessEmoji,
    "copy-failure"              => $sCopyFailureEmoji,
    "primary"                   => $sPrimaryEmoji,
    "inactive"                  => $sInactiveEmoji,
    "merge"                     => $sMergeEmoji,
    "move-up"                   => $sMoveUpEmoji,
    "move-down"                 => $sMoveDownEmoji,
    "calendar-toggle"           => $sCalendarToggleEmoji,
    "birthday-served"           => $sBirthdayServedEmoji,
    "communication-served"      => $sCommunicationServedEmoji,
    "contact-email"             => $sContactEmailEmoji,
    "contact-landline"          => $sContactLandlineEmoji,
    "contact-cell"              => $sContactCellEmoji,
    "contact-fax"               => $sContactFaxEmoji,
    "contact-pager"             => $sContactPagerEmoji,
    "contact-web"               => $sContactWebEmoji,
    "contact-telegram"          => $sContactTelegramEmoji,
    "contact-message"           => $sContactMessageEmoji,
    "contact-youtube"           => $sContactYouTubeEmoji
);


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
