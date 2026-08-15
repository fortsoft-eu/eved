<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess("film", "csrf_token");

if (isset($_GET["select"]) && preg_match("/^(info|credits)_\d+$/", $_GET["select"])) {
    list($sType, $iSelect) = explode("_", $_GET["select"], 2);
    $iSelect = (int)$iSelect;
    sendPhpGeneratedOutputAndExit($sType, $iSelect);
}

$aInfoTypes = array(
    "INFO_GENERAL" => INFO_GENERAL,
    "INFO_CREDITS" => INFO_CREDITS,
    "INFO_CONFIGURATION" => INFO_CONFIGURATION,
    "INFO_MODULES" => INFO_MODULES,
    "INFO_ENVIRONMENT" => INFO_ENVIRONMENT,
    "INFO_VARIABLES" => INFO_VARIABLES,
    "INFO_LICENSE" => INFO_LICENSE,
    "INFO_ALL" => INFO_ALL
);

$aCreditsTypes = array(
    "CREDITS_GROUP" => CREDITS_GROUP,
    "CREDITS_GENERAL" => CREDITS_GENERAL,
    "CREDITS_SAPI" => CREDITS_SAPI,
    "CREDITS_MODULES" => CREDITS_MODULES,
    "CREDITS_DOCS" => CREDITS_DOCS,
    "CREDITS_QA" => CREDITS_QA,
    "CREDITS_ALL" => CREDITS_ALL
);

ksort($aInfoTypes);
ksort($aCreditsTypes);
$iDefaultSelectedInfo = INFO_VARIABLES;

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
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="get" target="phpinfo-frame" id="phpinfo-select-form">
    <label for="select">Select:</label>
    <select name="select" id="select" class="js-submit-on-change">
      <optgroup label="PHP INFO">
<?php

foreach ($aInfoTypes as $sKey => $iValue) {
    echo "        <option value=\"info_" . $iValue . "\"" . ($iValue == $iDefaultSelectedInfo ? " selected" : "") . ">" . html($sKey) . "</option>\n";
}
echo "      </optgroup>\n",
    "      <optgroup label=\"PHP CREDITS\">\n";
foreach ($aCreditsTypes as $sKey => $iValue) {
    echo "        <option value=\"credits_" . $iValue . "\">" . html($sKey) . "</option>\n";
}

?>
      </optgroup>
    </select>
    <button type="submit" formtarget="_blank">Open Selected in New Window</button>
  </form>
  <iframe class="phpinfo-frame" name="phpinfo-frame" src="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]) . "?select=info_" . $iDefaultSelectedInfo); ?>" title="PHP Info"></iframe>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
