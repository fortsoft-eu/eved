<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if (session_status() == PHP_SESSION_ACTIVE) {
    session_write_close();
}


$aNamedEntities = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES | ENT_HTML5, "UTF-8");
$aCharacterPalette = array(
    "!", "\"", "#", "$", "%", "&", "'", "(", ")", "*", "+", ",", "-", ".", "/",
    ":", ";", "<", "=", ">", "?", "@", "[", "\\", "]", "^", "_", "`", "{", "|", "}", "~",
    " ", "­", "–", "—", "―", "…", "·", "•", "‣", "◦", "′", "″", "‹", "›", "«", "»",
    "‘", "’", "‚", "‛", "“", "”", "„", "‟", "©", "®", "™", "℠", "℅", "§", "¶", "†", "‡",
    "°", "№", "ª", "º", "¹", "²", "³", "⁰", "⁴", "⁵", "⁶", "⁷", "⁸", "⁹", "₀", "₁",
    "₂", "₃", "₄", "₅", "₆", "₇", "₈", "₉", "¼", "½", "¾", "⅐", "⅑", "⅒", "⅓", "⅔",
    "⅕", "⅖", "⅗", "⅘", "⅙", "⅚", "⅛", "⅜", "⅝", "⅞", "€", "£", "¥", "¢", "₹", "₿",
    "Æ", "æ", "Œ", "œ", "ß", "ẞ", "Ĳ", "ĳ", "Ǆ", "ǅ", "ǆ", "Ǉ", "ǈ", "ǉ", "Ǌ", "ǋ",
    "ǌ", "ﬀ", "ﬁ", "ﬂ", "ﬃ", "ﬄ", "ﬅ", "ﬆ",
    "±", "×", "÷", "−", "≠", "≈", "≡", "≤", "≥", "∞", "√", "∑", "∏", "∫", "∂", "∆",
    "∇", "π", "µ", "Ω", "←", "↑", "→", "↓", "↔", "↕", "↖", "↗", "↘", "↙", "↩", "↪",
    "⇐", "⇒", "⇔", "✓", "✔", "☑", "✅", "✕", "✖", "✗", "✘", "★", "☆", "✦", "✧", "✩",
    "♠", "♥", "♦", "♣", "♪", "♫", "☀", "☁", "☂", "☃", "☄", "☕", "⚠", "⚡", "⚙", "⛔",
);
$aCharacterPaletteScriptRanges = array(
    "Latin" => array(
        array(0x00C0, 0x00FF),
        array(0x0100, 0x024F),
        array(0x0250, 0x02AF),
        array(0x1D00, 0x1DBF),
        array(0x1E00, 0x1EFF),
        array(0x2C60, 0x2C7F),
        array(0xA720, 0xA7AD),
        array(0xA7B0, 0xA7B1),
        array(0xA7F7, 0xA7FF),
        array(0xAB30, 0xAB5F),
        array(0xAB64, 0xAB65),
        array(0xAB6A, 0xAB6F),
        array(0x1DF2B, 0x1DFFF)
    ),
    "Greek" => array(
        array(0x0370, 0x03FF),
        array(0x1F00, 0x1FFF)
    ),
    "Hebrew" => array(
        array(0x0590, 0x05EE),
        array(0x05F0, 0x05FF),
        array(0xFB1D, 0xFB4F)
    )
);
foreach ($aCharacterPaletteScriptRanges as $sCharacterPaletteScript => $aCharacterPaletteRanges) {
    foreach ($aCharacterPaletteRanges as $aCharacterPaletteRange) {
        for ($iCharacterPaletteCodePoint = $aCharacterPaletteRange[0]; $iCharacterPaletteCodePoint <= $aCharacterPaletteRange[1]; ++$iCharacterPaletteCodePoint) {
            $sCharacterPaletteCharacter = html_entity_decode("&#x" . strtoupper(dechex($iCharacterPaletteCodePoint)) . ";", ENT_QUOTES | ENT_HTML5, "UTF-8");
            if ($sCharacterPaletteCharacter != "" && preg_match("/^\\p{" . $sCharacterPaletteScript . "}$/u", $sCharacterPaletteCharacter) && !preg_match("/^\\p{M}$/u", $sCharacterPaletteCharacter)) {
                $aCharacterPalette[] = $sCharacterPaletteCharacter;
            }
        }
    }
}
$aFitzpatrickModifiers = array(
    json_decode('"\uD83C\uDFFB"'),
    json_decode('"\uD83C\uDFFC"'),
    json_decode('"\uD83C\uDFFD"'),
    json_decode('"\uD83C\uDFFE"'),
    json_decode('"\uD83C\uDFFF"')
);
$aCharacterPaletteToneVariantOverrides = array(
    json_decode('"\uD83E\uDD1D"') => true
);
$sTextVariationSelector = json_decode('"\uFE0E"');
$sEmojiVariationSelector = json_decode('"\uFE0F"');
$sCombiningEnclosingKeycap = json_decode('"\u20E3"');
$aCharacterPaletteEntries = array();
$aCharacterPaletteEntryIndexes = array();
$aCharacterPaletteEmojiKeys = array();
$aCharacterPaletteEmojiSorts = array();
$aCharacterPaletteEmojiFlagKeys = array();
$aCharacterPaletteCharacterNames = array();
foreach ($aCharacterPalette as $sCharacter) {
    $blCharacterPaletteEmoji = false;
    if (!preg_match('/^[#*0-9]$/u', $sCharacter) && preg_match("/^(.)(.*)$/us", $sCharacter, $aCharacterPaletteParts) && preg_match('/^\p{Emoji}$/u', $aCharacterPaletteParts[1])) {
        $blCharacterPaletteEmoji = true;
        if (strpos($sCharacter, $sEmojiVariationSelector) === false && !preg_match('/^\p{Emoji_Presentation}$/u', $aCharacterPaletteParts[1])) {
            $sCharacter = $aCharacterPaletteParts[1] . $sEmojiVariationSelector . $aCharacterPaletteParts[2];
        }
    }
    $sCharacterPaletteKey = str_replace(array($sTextVariationSelector, $sEmojiVariationSelector), "", $sCharacter);
    if ($blCharacterPaletteEmoji) {
        $aCharacterPaletteEmojiKeys[$sCharacterPaletteKey] = true;
    }
    if (!isset($aCharacterPaletteEntryIndexes[$sCharacterPaletteKey])) {
        $aCharacterPaletteEntries[] = array(
            "character" => $sCharacter,
            "variants" => array()
        );
        $aCharacterPaletteEntryIndexes[$sCharacterPaletteKey] = count($aCharacterPaletteEntries) - 1;
    }
}
$sEmojiDataFile = __DIR__ . "/../vendors/tinymce-8.8.1/plugins/emoticons/js/emojis.js";
$sEmojiData = is_file($sEmojiDataFile) ? file_get_contents($sEmojiDataFile) : false;
if ($sEmojiData !== false && preg_match_all("/(?:^|[,{])(\"[^\"]+\"|[A-Za-z0-9_]+):\\{[^{}]*?\\bchar:\"((?:\\\\\\\\.|[^\"\\\\\\\\])*)\",fitzpatrick_scale:(true|false),category:\"([^\"]+)\"/u", $sEmojiData, $aEmojiMatches, PREG_SET_ORDER)) {
    $iCharacterPaletteEmojiSort = 0;
    foreach ($aEmojiMatches as $aEmojiMatch) {
        $sEmojiName = trim($aEmojiMatch[1], "\"");
        $sEmojiCharacter = json_decode("\"" . $aEmojiMatch[2] . "\"");
        if (!is_string($sEmojiCharacter)) {
            $sEmojiCharacter = $aEmojiMatch[2];
        }
        $sEmojiCharacter = str_replace($sTextVariationSelector, "", $sEmojiCharacter);
        if (preg_match('/^([#*0-9])(?:\x{FE0F})?\x{20E3}?$/u', $sEmojiCharacter, $aKeycapMatch)) {
            $sEmojiCharacter = $aKeycapMatch[1] . $sEmojiVariationSelector . $sCombiningEnclosingKeycap;
        } else if (strpos($sEmojiCharacter, $sEmojiVariationSelector) === false && preg_match("/^(.)(.*)$/us", $sEmojiCharacter, $aEmojiParts) && preg_match('/^\p{Emoji}$/u', $aEmojiParts[1]) && !preg_match('/^\p{Emoji_Presentation}$/u', $aEmojiParts[1])) {
            $sEmojiCharacter = $aEmojiParts[1] . $sEmojiVariationSelector . $aEmojiParts[2];
        }
        $sCharacterPaletteKey = str_replace(array($sTextVariationSelector, $sEmojiVariationSelector), "", $sEmojiCharacter);
        if ($sEmojiCharacter == "") {
            continue;
        }
        $aCharacterPaletteEmojiKeys[$sCharacterPaletteKey] = true;
        if (!isset($aCharacterPaletteEmojiSorts[$sCharacterPaletteKey])) {
            $aCharacterPaletteEmojiSorts[$sCharacterPaletteKey] = $iCharacterPaletteEmojiSort;
        }
        if (!isset($aCharacterPaletteCharacterNames[$sCharacterPaletteKey]) && $sEmojiName != "") {
            if ($sEmojiName == "+1") {
                $aCharacterPaletteCharacterNames[$sCharacterPaletteKey] = "THUMBS UP";
            } else if ($sEmojiName == "-1") {
                $aCharacterPaletteCharacterNames[$sCharacterPaletteKey] = "THUMBS DOWN";
            } else {
                $aCharacterPaletteCharacterNames[$sCharacterPaletteKey] = strtoupper(str_replace("_", " ", $sEmojiName));
            }
        }
        if ($aEmojiMatch[4] == "flags") {
            $aCharacterPaletteEmojiFlagKeys[$sCharacterPaletteKey] = true;
        }
        ++$iCharacterPaletteEmojiSort;
        if (isset($aCharacterPaletteEntryIndexes[$sCharacterPaletteKey])) {
            $iCharacterPaletteEntryIndex = $aCharacterPaletteEntryIndexes[$sCharacterPaletteKey];
            $blExistingEmojiPresentation = strpos($aCharacterPaletteEntries[$iCharacterPaletteEntryIndex]["character"], $sEmojiVariationSelector) !== false;
            $blNewEmojiPresentation = strpos($sEmojiCharacter, $sEmojiVariationSelector) !== false;
            if ($blNewEmojiPresentation || !$blExistingEmojiPresentation) {
                $aCharacterPaletteEntries[$iCharacterPaletteEntryIndex]["character"] = $sEmojiCharacter;
            }
        } else {
            $aCharacterPaletteEntries[] = array(
                "character" => $sEmojiCharacter,
                "variants" => array()
            );
            $iCharacterPaletteEntryIndex = count($aCharacterPaletteEntries) - 1;
            $aCharacterPaletteEntryIndexes[$sCharacterPaletteKey] = $iCharacterPaletteEntryIndex;
        }
        if (($aEmojiMatch[3] == "true" || array_key_exists($sCharacterPaletteKey, $aCharacterPaletteToneVariantOverrides)) && preg_match("/^(.)(.*)$/us", $sEmojiCharacter, $aEmojiParts)) {
            $aCharacterToneVariants = array($aCharacterPaletteEntries[$iCharacterPaletteEntryIndex]["character"]);
            $sEmojiToneRemainder = preg_replace('/^\x{FE0F}/u', "", $aEmojiParts[2]);
            foreach ($aFitzpatrickModifiers as $sFitzpatrickModifier) {
                $sEmojiCharacter = $aEmojiParts[1] . $sFitzpatrickModifier . $sEmojiToneRemainder;
                $aCharacterToneVariants[] = $sEmojiCharacter;
            }
            $aCharacterPaletteEntries[$iCharacterPaletteEntryIndex]["variants"] = $aCharacterToneVariants;
        }
    }
}
$aCharacterPaletteNonEmojiEntries = array();
$aCharacterPaletteNonEmojiSort = array();
$aCharacterPaletteEmojiEntries = array();
$aCharacterPaletteEmojiGroupSort = array();
$aCharacterPaletteEmojiSort = array();
$aCharacterPaletteEmojiFallbackSort = array();
foreach ($aCharacterPaletteEntries as $aCharacterPaletteEntry) {
    $sCharacterPaletteKey = str_replace(array($sTextVariationSelector, $sEmojiVariationSelector), "", $aCharacterPaletteEntry["character"]);
    if (isset($aCharacterPaletteEmojiKeys[$sCharacterPaletteKey])) {
        $aCharacterPaletteEmojiEntries[] = $aCharacterPaletteEntry;
        $aCharacterPaletteEmojiGroupSort[] = isset($aCharacterPaletteEmojiFlagKeys[$sCharacterPaletteKey]) ? 1 : 0;
        $aCharacterPaletteEmojiSort[] = isset($aCharacterPaletteEmojiSorts[$sCharacterPaletteKey]) ? $aCharacterPaletteEmojiSorts[$sCharacterPaletteKey] : 1000000;
        $aCharacterPaletteEmojiFallbackSort[] = bin2hex($aCharacterPaletteEntry["character"]);
    } else {
        $aCharacterPaletteNonEmojiEntries[] = $aCharacterPaletteEntry;
        $aCharacterPaletteNonEmojiSort[] = bin2hex($aCharacterPaletteEntry["character"]);
    }
}
if ($aCharacterPaletteNonEmojiEntries) {
    array_multisort($aCharacterPaletteNonEmojiSort, SORT_ASC, SORT_STRING, $aCharacterPaletteNonEmojiEntries);
}
if ($aCharacterPaletteEmojiEntries) {
    array_multisort($aCharacterPaletteEmojiGroupSort, SORT_ASC, SORT_NUMERIC, $aCharacterPaletteEmojiSort, SORT_ASC, SORT_NUMERIC, $aCharacterPaletteEmojiFallbackSort, SORT_ASC, SORT_STRING, $aCharacterPaletteEmojiEntries);
}
$aCharacterPaletteEntries = array_merge($aCharacterPaletteNonEmojiEntries, $aCharacterPaletteEmojiEntries);
$iCharacterPaletteButtonCount = 0;
foreach ($aCharacterPaletteEntries as $aCharacterPaletteEntry) {
    $iCharacterPaletteButtonCount += count($aCharacterPaletteEntry["variants"]) > 0 ? count($aCharacterPaletteEntry["variants"]) : 1;
}
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr" class="character-converter-html" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
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
<body class="mail-page character-converter-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>" data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <button type="submit" form="character-converter-form" class="button-link">Convert</button>
    <button type="button" id="character-text-presentation" class="button-link">Text</button>
    <button type="button" id="character-emoji-presentation" class="button-link">Emoji</button>
    <button type="button" id="character-reset" class="button-link">Reset</button>
    <span class="table-record-count" aria-live="polite"><?php echo $iCharacterPaletteButtonCount; ?></span>
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
  <div class="character-palette-scroll">
    <div class="character-palette" aria-label="Character palette">
<?php

$blCharacterPaletteHasIntlChar = class_exists("IntlChar");
foreach ($aCharacterPaletteEntries as $aCharacterPaletteEntry) {
    $sCharacter = $aCharacterPaletteEntry["character"];
    $aCharacterTitleValues = array($sCharacter);
    $aCharacterTitles = array();
    $aCharacterVariantTitles = array();
    foreach ($aCharacterPaletteEntry["variants"] as $sCharacterVariant) {
        $aCharacterTitleValues[] = $sCharacterVariant;
    }
    foreach ($aCharacterTitleValues as $sCharacterTitleValue) {
        $aCharacterCodePointNames = array();
        $aCharacterCodePointTitles = array();
        $sCharacterPaletteKey = str_replace(array($sTextVariationSelector, $sEmojiVariationSelector), "", $sCharacterTitleValue);
        if (preg_match_all("/./us", $sCharacterTitleValue, $aCharacterCodePointMatches)) {
            foreach ($aCharacterCodePointMatches[0] as $sCharacterCodePoint) {
                $aCharacterBytes = array_values(unpack("C*", $sCharacterCodePoint));
                $iCharacterCodePoint = 0;
                if ($aCharacterBytes[0] < 0x80) {
                    $iCharacterCodePoint = $aCharacterBytes[0];
                } else if (($aCharacterBytes[0] & 0xE0) == 0xC0 && count($aCharacterBytes) >= 2) {
                    $iCharacterCodePoint = (($aCharacterBytes[0] & 0x1F) << 6) | ($aCharacterBytes[1] & 0x3F);
                } else if (($aCharacterBytes[0] & 0xF0) == 0xE0 && count($aCharacterBytes) >= 3) {
                    $iCharacterCodePoint = (($aCharacterBytes[0] & 0x0F) << 12) | (($aCharacterBytes[1] & 0x3F) << 6) | ($aCharacterBytes[2] & 0x3F);
                } else if (($aCharacterBytes[0] & 0xF8) == 0xF0 && count($aCharacterBytes) >= 4) {
                    $iCharacterCodePoint = (($aCharacterBytes[0] & 0x07) << 18) | (($aCharacterBytes[1] & 0x3F) << 12) | (($aCharacterBytes[2] & 0x3F) << 6) | ($aCharacterBytes[3] & 0x3F);
                }
                if ($iCharacterCodePoint > 0) {
                    if ($blCharacterPaletteHasIntlChar) {
                        $sCharacterCodePointName = IntlChar::charName($iCharacterCodePoint);
                        if (is_string($sCharacterCodePointName) && $sCharacterCodePointName != "") {
                            $aCharacterCodePointNames[] = $sCharacterCodePointName;
                        }
                    }
                    $aCharacterCodePointTitles[] = sprintf("U+%04X", $iCharacterCodePoint);
                }
            }
        }
        if (!$aCharacterCodePointNames && isset($aCharacterPaletteCharacterNames[$sCharacterPaletteKey])) {
            $aCharacterCodePointNames[] = $aCharacterPaletteCharacterNames[$sCharacterPaletteKey];
        }
        if ($aCharacterCodePointTitles) {
            if ($aCharacterCodePointNames) {
                $aCharacterTitles[$sCharacterTitleValue] = implode(" + ", $aCharacterCodePointNames) . "\n" . implode(" ", $aCharacterCodePointTitles);
            } else {
                $aCharacterTitles[$sCharacterTitleValue] = implode(" ", $aCharacterCodePointTitles);
            }
        }
    }
    foreach ($aCharacterPaletteEntry["variants"] as $sCharacterVariant) {
        $aCharacterVariantTitles[] = isset($aCharacterTitles[$sCharacterVariant]) ? $aCharacterTitles[$sCharacterVariant] : "";
    }
    $sCharacterTitle = isset($aCharacterTitles[$sCharacter]) ? $aCharacterTitles[$sCharacter] : "";
    $sCharacterVariantsAttribute = count($aCharacterPaletteEntry["variants"]) > 0 ? " data-character-variants=\"" . html(json_encode($aCharacterPaletteEntry["variants"])) . "\" data-character-variant-titles=\"" . html(json_encode($aCharacterVariantTitles)) . "\"" : "";
    echo "      <button type=\"button\" class=\"character-palette-button\" data-character-insert=\"" . html($sCharacter) . "\" title=\"" . html($sCharacterTitle) . "\"" . $sCharacterVariantsAttribute . ">" . html($sCharacter) . "</button>\n";
}

?>
    </div>
  </div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
