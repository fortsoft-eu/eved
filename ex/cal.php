<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

try {
    $aExternalCalendars = exCalendarFetchExternalCalendars($oPdo);
    $aNameDayGroups = exCalendarFetchNameDayGroups($oPdo);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}
$aCalendarGroups = exCalendarGetCalendarGroups($aExternalCalendars, $aNameDayGroups);
$aCalendarNames = exCalendarGetCalendars($aCalendarGroups);
$iCal = exCalendarGetICal($aCalendarNames);
$iYear = exCalendarGetYear();
$iPreviousYear = $iYear > 1583 ? $iYear - 1 : $iYear;
$iNextYear = $iYear < 9999 ? $iYear + 1 : $iYear;
$iCurrentYear = (int)date("Y");
$sScriptUrl = $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]);
$sCalendarPngFileName = strtolower(trim(preg_replace("/[^a-zA-Z0-9]+/", "_", $aCalendarNames[$iCal]), "_")) . "_" . $iYear . "_calendar";
$_SESSION["ex_calendar"] = array(
    "iCal" => $iCal,
    "iYear" => $iYear
);
$blCanViewPersons = isTrustedClient() || isProjectViewAllowed("ex");
session_write_close();
$sPageTitle = getPageTitleText();
$aHolidays = exCalendarGetHolidays($iYear);
$iExternalCalendarCount = count($aExternalCalendars);
if ($iCal == 1 || $iCal == 2) {
    if ($blCanViewPersons) {
        try {
            if ($iCal == 1) {
                exCalendarAddPersonBirthdays($aHolidays, $oPdo, $iYear);
            } else {
                exCalendarAddPersonNameDays($aHolidays, $oPdo, $iYear);
            }
        } catch (Exception $oException) {
            error_log((string)$oException);
            send500AndExit("Database error: " . $oException->getMessage());
        }
    }
} elseif ($iCal >= 3 && $iCal < 3 + $iExternalCalendarCount) {
    exCalendarAddExternalCalendarDatabaseEvents($aHolidays, $oPdo, $iYear, $aExternalCalendars[$iCal - 3]["calendar_url_hash"]);
} else {
    try {
        exCalendarAddNameDays($aHolidays, $oPdo, $iYear, $aNameDayGroups[$iCal - 3 - $iExternalCalendarCount]["id"]);
    } catch (Exception $oException) {
        error_log((string)$oException);
        send500AndExit("Database error: " . $oException->getMessage());
    }
}
$iTime = sendPageHeaders("", "cs-CZ");

?>
<!DOCTYPE html>
<html lang="cs-CZ" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html($sPageTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body class="calendar-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
  <p class="admin-controls">
<?php

renderMenu();
echo "    <select id=\"calendar-type\" name=\"iCal\" class=\"calendar-type js-submit-on-change\" form=\"calendar-year-form\">\n";
foreach ($aCalendarGroups as $sCalendarGroup => $aCalendars) {
    echo "      <optgroup label=\"" . html($sCalendarGroup) . "\"" . (!$blCanViewPersons && $sCalendarGroup == "Persons" ? " disabled" : "") . ">\n";
    foreach ($aCalendars as $iCalValue => $sCalLabel) {
        echo "        <option value=\"" . $iCalValue . "\"" . ($iCalValue == $iCal ? " selected" : "") . ">" . html($sCalLabel) . "</option>\n";
    }
    echo "      </optgroup>\n";
}
echo "    </select>\n";

?>
    <label for="calendar-year">Year:</label>
    <input type="text" id="calendar-year" name="iYear" class="calendar-year-input" value="<?php echo $iYear; ?>" inputmode="numeric" pattern="[0-9]{1,4}" maxlength="4" autocomplete="off" form="calendar-year-form">
    <button type="submit" class="button-link calendar-year-submit" form="calendar-year-form">Show</button>
    <button type="submit" class="button-link calendar-year-link" form="calendar-previous-year-form" title="Previous year">Prev</button>
    <button type="submit" class="button-link calendar-year-link" form="calendar-next-year-form" title="Next year">Next</button>
    <button type="button" class="button-link calendar-year-link js-calendar-current-year" data-calendar-url="<?php echo $sScriptUrl . "?iCal=" . $iCal . "&amp;iYear=" . $iCurrentYear; ?>" title="Current year">Today</button>
    <span class="menu png-export-menu" data-menu>
      <button type="button" class="button-link calendar-year-link png-export-menu-button" data-menu-button aria-haspopup="true" aria-expanded="false">Save PNG<span class="png-export-menu-arrow" aria-hidden="true"><?php echo $sCalendarToggleEmoji; ?></span></button>
      <span class="menu-panel png-export-menu-panel" data-menu-panel hidden>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="4" data-layout-width="1920" data-scale="1">4 columns, Scale 1:1</button>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="4" data-layout-width="1920" data-scale="2">4 columns, Scale 2:1</button>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="4" data-layout-width="1920" data-scale="3">4 columns, Scale 3:1</button>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="4" data-layout-width="1920" data-scale="4">4 columns, Scale 4:1</button>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="4" data-layout-width="1920" data-scale="5">4 columns, Scale 5:1</button>
        <span class="menu-separator"></span>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="3" data-layout-width="1500" data-scale="1">3 columns, Scale 1:1</button>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="3" data-layout-width="1500" data-scale="2">3 columns, Scale 2:1</button>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="3" data-layout-width="1500" data-scale="3">3 columns, Scale 3:1</button>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="3" data-layout-width="1500" data-scale="4">3 columns, Scale 4:1</button>
        <button type="button" class="menu-link png-export-menu-option js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" data-columns="3" data-layout-width="1500" data-scale="5">3 columns, Scale 5:1</button>
      </span>
    </span>
  </p>
  <form id="calendar-year-form" method="get" action="<?php echo $sScriptUrl; ?>" enctype="application/x-www-form-urlencoded" hidden></form>
  <form id="calendar-previous-year-form" method="get" action="<?php echo $sScriptUrl; ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="iCal" value="<?php echo $iCal; ?>">
    <input type="hidden" name="iYear" value="<?php echo $iPreviousYear; ?>">
  </form>
  <form id="calendar-next-year-form" method="get" action="<?php echo $sScriptUrl; ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="iCal" value="<?php echo $iCal; ?>">
    <input type="hidden" name="iYear" value="<?php echo $iNextYear; ?>">
  </form>
  <p class="holiday-legend">
    <span class="holiday-legend-item"><span class="holiday-legend-swatch holiday-legend-state"></span>Státní svátek</span>
    <span class="holiday-legend-item"><span class="holiday-legend-swatch holiday-legend-other"></span>Ostatní svátek</span>
    <span class="holiday-legend-item"><span class="holiday-legend-swatch holiday-legend-moving"></span>Pohyblivý svátek</span>
    <span class="holiday-legend-item"><span class="holiday-legend-swatch holiday-legend-external"></span>Furry event</span>
  </p>
  <div class="holiday-calendar-grid">
<?php

for ($iMonth = 1; $iMonth <= 12; $iMonth += 1) {
    exCalendarRenderMonth($iYear, $iMonth, $aHolidays);
}

?>
  </div>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/snapdom-2.23.1/snapdom.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
