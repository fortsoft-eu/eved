<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$aCalendarGroups = exCalendarGetCalendarGroups();
$aCalendarNames = exCalendarGetCalendars();
$iCal = exCalendarGetICal();
$iYear = exCalendarGetYear();
$iPreviousYear = $iYear > 1583 ? $iYear - 1 : $iYear;
$iNextYear = $iYear < 9999 ? $iYear + 1 : $iYear;
$iCurrentYear = (int)date("Y");
$sCalendarPngFileName = strtolower(trim(preg_replace("/[^a-zA-Z0-9]+/", "_", $aCalendarNames[$iCal]), "_")) . "_" . $iYear . "_calendar";
$_SESSION["ex_calendar"] = array(
    "iCal" => $iCal,
    "iYear" => $iYear
);
$blCanViewPersons = isTrustedClient($aAllowedIps) || isProjectViewAllowed("ex");
session_write_close();
$sPageTitle = getPageTitleText($aAllowedIps);
$aHolidays = exCalendarGetHolidays($iYear);
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
} elseif ($iCal == 3) {
    exCalendarAddExternalCalendarDatabaseEvents($aHolidays, $oPdo, $iYear, exCalendarGetExternalCalendarUrl());
} else {
    try {
        exCalendarAddNameDays($aHolidays, $oPdo, $iYear, $iCal - 3);
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
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html($sPageTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
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
    <button type="button" class="button-link calendar-year-link js-calendar-current-year" data-calendar-url="<?php echo html($sBaseUrl . "cal.php?iCal=" . $iCal . "&iYear=" . $iCurrentYear); ?>" title="Current year">Today</button>
    <button type="button" class="button-link calendar-year-link js-calendar-save-png" data-file-name="<?php echo html($sCalendarPngFileName); ?>" aria-label="Save PNG">Save PNG</button>
  </p>
  <form id="calendar-year-form" method="get" action="<?php echo html($sBaseUrl . "cal.php"); ?>" enctype="application/x-www-form-urlencoded" hidden></form>
  <form id="calendar-previous-year-form" method="get" action="<?php echo html($sBaseUrl . "cal.php"); ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="iCal" value="<?php echo $iCal; ?>">
    <input type="hidden" name="iYear" value="<?php echo $iPreviousYear; ?>">
  </form>
  <form id="calendar-next-year-form" method="get" action="<?php echo html($sBaseUrl . "cal.php"); ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="iCal" value="<?php echo $iCal; ?>">
    <input type="hidden" name="iYear" value="<?php echo $iNextYear; ?>">
  </form>
  <div class="holiday-calendar-export">
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
  </div>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/html2canvas-1.4.1/html2canvas.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
