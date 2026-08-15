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
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr ÄŚervinka &lt;cervinka@fortsoft.cz&gt;">
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
<body class="iban-page">
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
