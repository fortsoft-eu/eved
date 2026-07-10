<?php

$iDefaultSelectedInfo = INFO_VARIABLES;

include "main.php";


if (!isAllowedIp($aAllowedIps)) {
    send403AndExit();
}

function getPhpGeneratedStyleTag($sStyleNonce) {
    return "  <style type=\"text/css\" nonce=\"" . htmlspecialchars($sStyleNonce, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">\n"
        . "    body {background-color: #fff; color: #222; font-family: sans-serif;}\n"
        . "    pre {margin: 0; font-family: monospace;}\n"
        . "    a {color: inherit;}\n"
        . "    a:hover {text-decoration: none;}\n"
        . "    table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px rgba(0, 0, 0, 0.2);}\n"
        . "    .center {text-align: center;}\n"
        . "    .center table {margin: 1em auto; text-align: left;}\n"
        . "    .center th {text-align: center !important;}\n"
        . "    td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}\n"
        . "    th {position: sticky; top: 0; background: inherit;}\n"
        . "    h1 {font-size: 150%;}\n"
        . "    h2 {font-size: 125%;}\n"
        . "    h2 > a {text-decoration: none;}\n"
        . "    h2 > a:hover {text-decoration: underline;}\n"
        . "    .p {text-align: left;}\n"
        . "    .e {background-color: #ccf; width: 300px; font-weight: bold;}\n"
        . "    .h {background-color: #99c; font-weight: bold;}\n"
        . "    .v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: normal;}\n"
        . "    .v i {color: #999;}\n"
        . "    img {float: right; border: 0;}\n"
        . "    hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}\n"
        . "    :root {--php-dark-grey: #333; --php-dark-blue: #4F5B93; --php-medium-blue: #8892BF; --php-light-blue: #E2E4EF; --php-accent-purple: #793862;}\n"
        . "    @media (prefers-color-scheme: dark) {\n"
        . "      body {background: var(--php-dark-grey); color: var(--php-light-blue);}\n"
        . "      .h td, td.e, th {border-color: #606A90;}\n"
        . "      td {border-color: #505153;}\n"
        . "      .e {background-color: #404A77;}\n"
        . "      .h {background-color: var(--php-dark-blue);}\n"
        . "      .v {background-color: var(--php-dark-grey);}\n"
        . "      hr {background-color: #505153;}\n"
        . "    }\n"
        . "  </style>\n";
}

function addPhpGeneratedStyleAttributes($sHtml, $sStyleNonce) {
    $sNonce = htmlspecialchars($sStyleNonce, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    return preg_replace_callback("#<style([^>]*)>#i", function ($aMatches) use ($sNonce) {
        $sAttributes = $aMatches[1];
        if (!preg_match("#\\stype\\s*=#i", $sAttributes)) {
            $sAttributes .= " type=\"text/css\"";
        }
        if (!preg_match("#\\snonce\\s*=#i", $sAttributes)) {
            $sAttributes .= " nonce=\"" . $sNonce . "\"";
        }
        return "<style" . $sAttributes . ">";
    }, $sHtml);
}

function addPhpGeneratedViewportMeta($sHtml) {
    if (preg_match("#<meta\\b[^>]*\\bname\\s*=\\s*([\"'])viewport\\1#i", $sHtml) || stripos($sHtml, "</head>") === false) {
        return $sHtml;
    }
    return preg_replace("#</head>#i", "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n</head>", $sHtml, 1);
}

function formatPhpGeneratedOutput($sHtml, $sStyleNonce, $sTitle) {
    $sHtml = addPhpGeneratedStyleAttributes($sHtml, $sStyleNonce);
    if (stripos($sHtml, "<html") !== false) {
        if (stripos($sHtml, "<style") === false && stripos($sHtml, "</head>") !== false) {
            $sHtml = preg_replace("#</head>#i", getPhpGeneratedStyleTag($sStyleNonce) . "</head>", $sHtml, 1);
        }
        return addPhpGeneratedViewportMeta($sHtml);
    }

    return "<!DOCTYPE html>\n"
        . "<html lang=\"en-US\" dir=\"ltr\">\n"
        . "<head>\n"
        . "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n"
        . "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n"
        . "  <title>" . htmlspecialchars($sTitle, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</title>\n"
        . getPhpGeneratedStyleTag($sStyleNonce)
        . "</head>\n"
        . "<body><div class=\"center\">\n"
        . $sHtml
        . "\n</div></body>\n"
        . "</html>\n";
}

function sendPhpGeneratedHeaders($sStyleNonce) {
    $iTime = time();
    $sDate = gmdate("D, d M Y H:i:s", $iTime);

    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders($sStyleNonce);
}

function sendPhpGeneratedOutputAndExit($sType, $iSelect) {
    $sStyleNonce = base64_encode(random_bytes(16));
    ob_start();
    if ($sType == "credits") {
        if ($iSelect > 0) {
            phpcredits($iSelect | CREDITS_FULLPAGE);
        } else {
            phpcredits();
        }
    } else {
        if ($iSelect > 0) {
            phpinfo($iSelect);
        } else {
            phpinfo();
        }
    }
    $sHtml = formatPhpGeneratedOutput(ob_get_clean(), $sStyleNonce, $sType == "credits" ? "PHP Credits" : "PHP Info");
    sendPhpGeneratedHeaders($sStyleNonce);
    echo $sHtml;
    exit;
}

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

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>PHP Info and PHP Credits</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <form action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" method="get" target="phpinfo-frame" id="phpinfo-select-form">
    <label for="select">Select:</label>
    <select name="select" id="select" class="js-submit-on-change">
      <optgroup label="PHP INFO">
<?php

foreach ($aInfoTypes as $sKey => $iValue) {
    echo "        <option value=\"info_" . $iValue . "\"" . ($iValue == $iDefaultSelectedInfo ? " selected" : "") . ">"
        . htmlspecialchars($sKey, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</option>\n";
}

?>
      </optgroup>
      <optgroup label="PHP CREDITS">
<?php

foreach ($aCreditsTypes as $sKey => $iValue) {
    echo "        <option value=\"credits_" . $iValue . "\">"
        . htmlspecialchars($sKey, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</option>\n";
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
