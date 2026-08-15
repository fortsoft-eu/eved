<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess("portal", "csrf_token");


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
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/admin-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/admin-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/admin-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/admin-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/admin-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/admin-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/admin-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/admin-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/admin-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/admin-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/admin-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/admin-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/admin-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/admin-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/admin-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/admin-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/admin-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/admin-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/admin-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/admin-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body class="phpinfo-page">
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <div id="phpinfo-select-form">
    <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="get" target="phpinfo-frame">
      <fieldset>
        <legend>PHP INFO</legend>
        <input type="hidden" name="type" value="info">
        <div class="phpinfo-checkboxes">
<?php

foreach ($aInfoTypes as $sKey => $iValue) {
    echo "        <label><input type=\"checkbox\" name=\"info[]\" value=\"" . (int)$iValue . "\" class=\"js-submit-on-change\""
        . ($iValue == $iDefaultSelectedInfo ? " checked" : "") . "> "
        . html($sKey) . "</label><br>\n";
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
    <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="get" target="phpinfo-frame">
      <fieldset>
        <legend>PHP CREDITS</legend>
        <input type="hidden" name="type" value="credits">
        <div class="phpinfo-checkboxes">
<?php

foreach ($aCreditsTypes as $sKey => $iValue) {
    echo "        <label><input type=\"checkbox\" name=\"credits[]\" value=\"" . (int)$iValue . "\" class=\"js-submit-on-change\"" . ($iValue == $iDefaultSelectedCredits ? " checked" : "") . "> " . html($sKey) . "</label><br>\n";
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
  <iframe class="phpinfo-frame" name="phpinfo-frame" src="<?php echo html($sDefaultFrameUrl); ?>" title="PHP Info"></iframe>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
