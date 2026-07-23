<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "film", "film_csrf_token");

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
  <title><?php echo htmlspecialchars(getPageTitleText("PHP Info and PHP Credits", $aAllowedIps), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <form action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" method="get" target="phpinfo-frame" id="phpinfo-select-form">
    <label for="select">Select:</label>
    <select name="select" id="select" class="js-submit-on-change">
      <optgroup label="PHP INFO">
<?php

foreach ($aInfoTypes as $sKey => $iValue) {
    echo "        <option value=\"info_" . $iValue . "\"" . ($iValue == $iDefaultSelectedInfo ? " selected" : "") . ">" . htmlspecialchars($sKey, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</option>\n";
}
echo "      </optgroup>\n",
    "      <optgroup label=\"PHP CREDITS\">\n";
foreach ($aCreditsTypes as $sKey => $iValue) {
    echo "        <option value=\"credits_" . $iValue . "\">" . htmlspecialchars($sKey, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</option>\n";
}

?>
      </optgroup>
    </select>
    <button type="submit" formtarget="_blank">Open Selected in New Window</button>
  </form>
  <iframe class="phpinfo-frame" name="phpinfo-frame" src="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]) . "?select=info_" . $iDefaultSelectedInfo, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" title="PHP Info"></iframe>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js"></script>
</body>
</html>
