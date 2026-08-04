<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireViewAccess($aAllowedIps, "kf", "kf_csrf_token");


$sTitle = getPageTitleText("IBAN Calculator", $aAllowedIps);
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
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
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
