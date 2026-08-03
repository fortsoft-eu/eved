<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "lm_csrf_token");


$sDomainValue = "";
$sLookupMessage = "";
$sLookupMessageClass = "";
$sLookupSource = "";
$aDomainRow = null;
$aDomainDnsResult = null;

if (isset($_GET["domain"])) {
    $sDomainValue = (string)$_GET["domain"];
    $sDomain = domainLookupNormalizeDomain($sDomainValue);
    if ($sDomain == "") {
        $sLookupMessage = "Enter a domain name.";
        $sLookupMessageClass = "message-error";
    } elseif (!domainLookupDomainShapeIsValid($sDomain)) {
        $sDomainValue = $sDomain;
        $sLookupMessage = "Enter a valid domain name.";
        $sLookupMessageClass = "message-error";
    } elseif (!domainLookupDomainTldIsSupported($sDomain)) {
        $sDomainValue = $sDomain;
        $sLookupMessage = "This domain suffix is not supported.";
        $sLookupMessageClass = "message-error";
    } else {
        $sDomainValue = $sDomain;
        try {
            $aCachedRow = domainLookupFetchRow($oPdo, $sDomain);
            if ($aCachedRow && !domainLookupNeedsApiCall($aCachedRow)) {
                $aDomainRow = $aCachedRow;
                $sLookupSource = "Database cache";
                $sLookupMessage = "Cached result was loaded from the database.";
                $sLookupMessageClass = (string)$aDomainRow["result_status"] == "success" ? "message-success" : "message-warning";
            } else {
                $aDomainRow = domainLookupSaveResult($oPdo, domainLookupCallApi($sDomain, trim((string)$sApiLayerWhoisApiKey)));
                $sLookupSource = "API";
                if ((string)$aDomainRow["result_status"] == "success") {
                    $sLookupMessage = "Domain data was loaded from the API and saved to the database.";
                    $sLookupMessageClass = "message-success";
                } else {
                    $sLookupMessage = "API returned an error. The error was saved to the database.";
                    $sLookupMessageClass = "message-warning";
                }
            }
        } catch (Exception $oException) {
            error_log((string)$oException);
            send500AndExit("Database error: " . $oException->getMessage());
        }
        $aDomainDnsResult = domainLookupFetchDnsRecords($sDomain);
    }
}

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
  <title><?php echo html(getPageTitleText("Domain Lookup", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="get" id="domain-lookup-form" hidden>
  </form>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="domain">Domain:</label>
    <input type="text" id="domain" name="domain" class="domain-lookup-input" form="domain-lookup-form" value="<?php echo html($sDomainValue); ?>" spellcheck="false" required>
    <button type="submit" form="domain-lookup-form" class="button-link domain-lookup-button">Lookup</button>
  </p>
<?php

if ($sLookupMessage != "") {
    echo "  <p class=\"domain-lookup-message " . html($sLookupMessageClass) . "\">" . html($sLookupMessage) . "</p>\n";
}
if ($aDomainRow) {

?>
  <table class="domain-result-table<?php echo getCondensedTableClass(); ?>">
    <caption>WHOIS Data</caption>
    <tbody>
<?php

    domainLookupRenderResultRows($aDomainRow, $sLookupSource);

?>
    </tbody>
  </table>
<?php

}
if ($aDomainDnsResult) {

?>
  <table class="domain-dns-table<?php echo getCondensedTableClass(); ?>">
    <caption>DNS Records</caption>
    <thead>
      <tr>
        <th>Type</th>
        <th>Host</th>
        <th>TTL</th>
        <th>Value</th>
      </tr>
    </thead>
    <tbody>
<?php

    domainLookupRenderDnsRows($aDomainDnsResult);

?>
    </tbody>
  </table>
<?php

}

?>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
