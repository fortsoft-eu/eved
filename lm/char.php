<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "lm_csrf_token");

if (session_status() == PHP_SESSION_ACTIVE) {
    session_write_close();
}


$aNamedEntities = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES | ENT_HTML5, "UTF-8");
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
  <title><?php echo html(getPageTitleText($aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body class="mail-page character-converter-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <button type="submit" form="character-converter-form" class="button-link mail-send-button">Convert</button>
    <button type="button" id="character-text-presentation" class="button-link mail-send-button">Text Presentation</button>
    <button type="button" id="character-emoji-presentation" class="button-link mail-send-button">Emoji Presentation</button>
  </p>
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="get" id="character-converter-form" class="snippet-board-form mail-form character-converter-form" autocomplete="on" novalidate>
    <div class="mail-form-fields">
      <label for="character-value">Character:</label>
      <input type="text" id="character-value" data-character-converter-field="text" value="" aria-label="Character" spellcheck="false">
      <label for="character-decimal">Decimal Code Point:</label>
      <input type="text" id="character-decimal" data-character-converter-field="decimal" aria-label="Decimal code point" inputmode="numeric" spellcheck="false">
      <label for="character-hexadecimal">Hexadecimal Code Point:</label>
      <input type="text" id="character-hexadecimal" data-character-converter-field="hexadecimal" aria-label="Hexadecimal code point" spellcheck="false">
      <label for="character-decimal-entity">Decimal HTML Entity:</label>
      <input type="text" id="character-decimal-entity" data-character-converter-field="decimal-entity" aria-label="Decimal HTML entity" spellcheck="false">
      <label for="character-hexadecimal-entity">Hexadecimal HTML Entity:</label>
      <input type="text" id="character-hexadecimal-entity" data-character-converter-field="hexadecimal-entity" aria-label="Hexadecimal HTML entity" spellcheck="false">
      <label for="character-named-entity">Named HTML Entity:</label>
      <input type="text" id="character-named-entity" data-character-converter-field="named-entity" aria-label="Named HTML entity" spellcheck="false">
    </div>
    <select id="character-named-entities" hidden aria-hidden="true" tabindex="-1">
<?php

foreach ($aNamedEntities as $sCharacter => $sEntity) {
    echo "      <option value=\"" . html($sCharacter) . "\" data-entity=\"" . html($sEntity) . "\"></option>\n";
}

?>
    </select>
  </form>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
