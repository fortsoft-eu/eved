<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireViewAccess("kf", "csrf_token");


$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="author" content="Petr ÄŚervinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link rel="icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" title="Original">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" title="Graphite">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" title="Midnight">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" title="Slate">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" title="Sepia">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" title="Sand">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" title="Forest">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" title="Moss">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" title="Ocean">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" title="Ice">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" title="Lavender">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" title="Rose">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" title="Copper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" title="Burgundy">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" title="Monochrome">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" title="High Contrast">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" title="Soft">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" title="Paper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" title="Terminal">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" title="Cobalt">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body class="iban-page" data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <div class="admin-top">
    <form id="iban-form" class="iban-form" action="#" method="get" novalidate>
      <label for="iban-national-account">Account Number</label>
      <input type="text" id="iban-national-account" name="account_number" class="iban-account-input" placeholder="000000-0000000000/0000">
      <p class="admin-controls">
        <button type="submit" class="button-link">Calculate</button>
        <button type="reset" class="button-link">Reset</button>
      </p>
      <label for="iban-normalized-account">Czech National Account Format</label>
      <input type="text" id="iban-normalized-account" class="iban-result-input" readonly>
      <label for="iban-plain">IBAN — Electronic Format</label>
      <input type="text" id="iban-plain" class="iban-result-input" readonly>
      <label for="iban-formatted">IBAN — Written Representation</label>
      <input type="text" id="iban-formatted" class="iban-result-input" readonly>
      <label for="iban-swift">SWIFT Code</label>
      <input type="text" id="iban-swift" class="iban-result-input" readonly>
      <p id="iban-message" class="iban-message" aria-live="polite"></p>
    </form>
  </div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
