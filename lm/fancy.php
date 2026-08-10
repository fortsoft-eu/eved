<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if (session_status() == PHP_SESSION_ACTIVE) {
    session_write_close();
}


$aFancyTextStyles = array(
    "plain" => "Plain",
    "bold" => "Bold",
    "italic" => "Italic",
    "bold-italic" => "Bold Italic",
    "script" => "Script",
    "script-chancery" => "Script Chancery",
    "script-roundhand" => "Script Roundhand",
    "bold-script" => "Bold Script",
    "fraktur" => "Fraktur",
    "double-struck" => "Double-Struck",
    "bold-fraktur" => "Bold Fraktur",
    "sans-serif" => "Sans-Serif",
    "sans-serif-bold" => "Sans-Serif Bold",
    "sans-serif-italic" => "Sans-Serif Italic",
    "sans-serif-bold-italic" => "Sans-Serif Bold Italic",
    "monospace" => "Monospace"
);

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText($aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body class="fancy-text-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="fancy-text-style">Style:</label>
    <select id="fancy-text-style" class="fancy-text-style">
<?php

foreach ($aFancyTextStyles as $sFancyTextStyle => $sFancyTextLabel) {
    echo "      <option value=\"" . html($sFancyTextStyle) . "\">" . html($sFancyTextLabel) . "</option>\n";
}

?>
    </select>
    <button type="button" class="button-link js-fancy-text-convert">Convert</button>
  </p>
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="get" id="fancy-text-form" class="snippet-board-form fancy-text-form" autocomplete="on" novalidate>
    <div class="fancy-text-grid">
      <div class="fancy-text-panel">
        <label for="fancy-text-input">Input Text:</label>
        <textarea id="fancy-text-input" class="fancy-textarea" rows="18"></textarea>
      </div>
      <div class="fancy-text-panel">
        <label for="fancy-text-output">Fancy Text:</label>
        <textarea id="fancy-text-output" class="fancy-textarea" rows="18" readonly></textarea>
      </div>
    </div>
  </form>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
