<?php

include "main.php";


requireFullAccess($aAllowedIps, "ex", "ex_csrf_token", true);


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$iDefaultSelectedInfo = INFO_VARIABLES;
$iDefaultSelectedCredits = CREDITS_GROUP;
$aInfoTypes = array(
    "INFO_GENERAL" => INFO_GENERAL,
    "INFO_CREDITS" => INFO_CREDITS,
    "INFO_CONFIGURATION" => INFO_CONFIGURATION,
    "INFO_MODULES" => INFO_MODULES,
    "INFO_ENVIRONMENT" => INFO_ENVIRONMENT,
    "INFO_VARIABLES" => INFO_VARIABLES,
    "INFO_LICENSE" => INFO_LICENSE
);

$aCreditsTypes = array(
    "CREDITS_GROUP" => CREDITS_GROUP,
    "CREDITS_GENERAL" => CREDITS_GENERAL,
    "CREDITS_SAPI" => CREDITS_SAPI,
    "CREDITS_MODULES" => CREDITS_MODULES,
    "CREDITS_DOCS" => CREDITS_DOCS,
    "CREDITS_QA" => CREDITS_QA
);

ksort($aInfoTypes);
ksort($aCreditsTypes);

if (isset($_GET["type"])) {
    if ($_GET["type"] == "info") {
        $iSelect = isset($_GET["info_all"]) && $_GET["info_all"] == "1"
            ? INFO_ALL
            : getPhpGeneratedSelectedFlags("info", array_values($aInfoTypes), $iDefaultSelectedInfo);
        sendPhpGeneratedOutputAndExit("info", $iSelect);
    } elseif ($_GET["type"] == "credits") {
        $iSelect = isset($_GET["credits_all"]) && $_GET["credits_all"] == "1"
            ? CREDITS_ALL
            : getPhpGeneratedSelectedFlags("credits", array_values($aCreditsTypes), $iDefaultSelectedCredits);
        sendPhpGeneratedOutputAndExit("credits", $iSelect);
    }
}
$sDefaultFrameUrl = $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]) . "?" . http_build_query(array(
    "type" => "info",
    "info" => array($iDefaultSelectedInfo)
), "", "&");
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
  <title><?php echo html(getPageTitleText("PHP Info and PHP Credits", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body class="phpinfo-page">
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <div id="phpinfo-select-form">
    <form action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" method="get" target="phpinfo-frame">
      <fieldset>
        <legend>PHP INFO</legend>
        <input type="hidden" name="type" value="info">
        <div class="phpinfo-checkboxes">
<?php

foreach ($aInfoTypes as $sKey => $iValue) {
    echo "        <label><input type=\"checkbox\" name=\"info[]\" value=\"" . (int)$iValue . "\" class=\"js-submit-on-change\""
        . ($iValue == $iDefaultSelectedInfo ? " checked" : "") . "> "
        . htmlspecialchars($sKey, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</label><br>\n";
}

?>
        </div>
        <div class="phpinfo-button-row">
          <button type="submit">Show Info</button>
          <button type="submit" formtarget="_blank">Open Info in New Window</button>
          <button type="submit" name="info_all" value="1">Show All Info</button>
          <button type="submit" name="info_all" value="1" formtarget="_blank">Open All Info in New Window</button>
        </div>
      </fieldset>
    </form>
    <form action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" method="get" target="phpinfo-frame">
      <fieldset>
        <legend>PHP CREDITS</legend>
        <input type="hidden" name="type" value="credits">
        <div class="phpinfo-checkboxes">
<?php

foreach ($aCreditsTypes as $sKey => $iValue) {
    echo "        <label><input type=\"checkbox\" name=\"credits[]\" value=\"" . (int)$iValue . "\" class=\"js-submit-on-change\"" . ($iValue == $iDefaultSelectedCredits ? " checked" : "") . "> " . htmlspecialchars($sKey, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</label><br>\n";
}

?>
        </div>
        <div class="phpinfo-button-row">
          <button type="submit">Show Credits</button>
          <button type="submit" formtarget="_blank">Open Credits in New Window</button>
          <button type="submit" name="credits_all" value="1">Show All Credits</button>
          <button type="submit" name="credits_all" value="1" formtarget="_blank">Open All Credits in New Window</button>
        </div>
      </fieldset>
    </form>
  </div>
  <iframe class="phpinfo-frame" name="phpinfo-frame" src="<?php echo htmlspecialchars($sDefaultFrameUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" title="PHP Info"></iframe>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
