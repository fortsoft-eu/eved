<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$iYear = exCalendarGetRequestYear();
$iPreviousYear = $iYear > 1583 ? $iYear - 1 : $iYear;
$iNextYear = $iYear < 9999 ? $iYear + 1 : $iYear;
$iCurrentYear = (int)date("Y");
$sPageTitle = getPageTitleText($aAllowedIps);
$aHolidays = exCalendarGetHolidays($iYear);
exCalendarAddExternalCalendarDatabaseEvents($aHolidays, $oPdo, $iYear, exCalendarGetExternalCalendarUrl());
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
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
<body class="calendar-page">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="calendar-year">Year:</label>
    <input type="text" id="calendar-year" name="year" class="calendar-year-input" value="<?php echo $iYear; ?>" inputmode="numeric" pattern="[0-9]{1,4}" maxlength="4" autocomplete="off" form="calendar-year-form">
    <button type="submit" class="button-link calendar-year-submit" form="calendar-year-form">Show</button>
    <button type="submit" class="button-link calendar-year-link" form="calendar-previous-year-form" title="Previous year">Prev</button>
    <button type="submit" class="button-link calendar-year-link" form="calendar-next-year-form" title="Next year">Next</button>
    <button type="submit" class="button-link calendar-year-link" form="calendar-current-year-form" title="Current year">Today</button>
  </p>
  <form id="calendar-year-form" method="get" action="<?php echo html($sBaseUrl . "cal.php"); ?>" enctype="application/x-www-form-urlencoded" hidden></form>
  <form id="calendar-previous-year-form" method="get" action="<?php echo html($sBaseUrl . "cal.php"); ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="year" value="<?php echo $iPreviousYear; ?>">
  </form>
  <form id="calendar-next-year-form" method="get" action="<?php echo html($sBaseUrl . "cal.php"); ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="year" value="<?php echo $iNextYear; ?>">
  </form>
  <form id="calendar-current-year-form" method="get" action="<?php echo html($sBaseUrl . "cal.php"); ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="year" value="<?php echo $iCurrentYear; ?>">
  </form>
  <p class="holiday-legend">
    <span class="holiday-legend-item"><span class="holiday-legend-swatch holiday-legend-state"></span>Státní svátek</span>
    <span class="holiday-legend-item"><span class="holiday-legend-swatch holiday-legend-other"></span>Ostatní svátek</span>
    <span class="holiday-legend-item"><span class="holiday-legend-swatch holiday-legend-moving"></span>Pohyblivý svátek</span>
    <span class="holiday-legend-item"><span class="holiday-legend-swatch holiday-legend-external"></span>Externí kalendář</span>
  </p>
  <div class="holiday-calendar-grid">
<?php

for ($iMonth = 1; $iMonth <= 12; $iMonth += 1) {
    exCalendarRenderMonth($iYear, $iMonth, $aHolidays);
}

?>
  </div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
