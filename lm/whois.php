<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "csrf_token");

if (session_status() == PHP_SESSION_ACTIVE) {
    session_write_close();
}


$sDomainValue = "";
$sLookupMessage = "";
$sLookupMessageClass = "";
$sLookupSource = "";
$sLookupCaption = "";
$blLookupIsIp = false;
$aDomainRow = null;
$aDomainDnsResult = null;
$aDomainDnsRecords = array();

if (isset($_GET["query"])) {
    $sDomainValue = (string)$_GET["query"];
    $sLookupValue = domainLookupNormalizeValue($sDomainValue);
    if ($sLookupValue == "") {
        $sLookupMessage = "Enter a domain name or IP address.";
        $sLookupMessageClass = "message-error";
    } elseif (domainLookupIpAddressIsValid($sLookupValue)) {
        $blLookupIsIp = true;
        $sDomainValue = $sLookupValue;
        $sLookupCaption = "IP Data";
    } else {
        $sDomain = $sLookupValue;
        if (!domainLookupDomainShapeIsValid($sDomain)) {
            $sDomainValue = $sDomain;
            $sLookupMessage = "Enter a valid domain name or IP address.";
            $sLookupMessageClass = "message-error";
        } elseif (!domainLookupDomainTldIsSupported($sDomain)) {
            $sDomainValue = $sDomain;
            $sLookupMessage = "This domain suffix is not supported.";
            $sLookupMessageClass = "message-error";
        } else {
            $sDomainValue = $sDomain;
            $sLookupCaption = "WHOIS Data";
        }
    }
    if ($sLookupMessage == "") {
        try {
            $aCachedRow = domainLookupFetchRow($oPdo, $sLookupValue);
            if ($aCachedRow && (!$blLookupIsIp || $aCachedRow["reverse_dns"] !== null) && !domainLookupNeedsApiCall($aCachedRow, $blLookupIsIp ? 2592000 : 14400)) {
                $aDomainRow = $aCachedRow;
                $sLookupSource = "Database cache";
                $sLookupMessage = $blLookupIsIp ? "Cached IP data was loaded from the database." : "Cached result was loaded from the database.";
                $sLookupMessageClass = (string)$aDomainRow["result_status"] == "success" ? "message-success" : "message-warning";
            } else {
                $aLookupResult = $blLookupIsIp ? domainLookupCallIpApi($sLookupValue, $sAbstractIpIntelApiKey) : domainLookupCallApi($sLookupValue, $sApiLayerWhoisApiKey);
                if ($blLookupIsIp) {
                    $sReverseDns = "";
                    $sHostName = @gethostbyaddr($sLookupValue);
                    if (is_string($sHostName) && $sHostName != "" && strtolower($sHostName) != strtolower($sLookupValue)) {
                        $sReverseDns = rtrim($sHostName, ".");
                    }
                    $aLookupResult["reverse_dns"] = $sReverseDns;
                }
                $aDomainRow = domainLookupSaveResult($oPdo, $aLookupResult);
                $sLookupSource = "API";
                if ((string)$aDomainRow["result_status"] == "success") {
                    $sLookupMessage = $blLookupIsIp ? "IP data was loaded from the API and saved to the database." : "Domain data was loaded from the API and saved to the database.";
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
        if (!$blLookupIsIp && $aDomainRow && (string)$aDomainRow["result_status"] == "success") {
            if ((int)$aDomainRow["dns_lookup_disabled"] == 1) {
                $aDomainDnsResult = domainLookupSkippedDnsResult();
            } else {
                $aDomainDnsResult = domainLookupFetchDnsRecords($sLookupValue);
                if (isset($aDomainDnsResult["disable_dns_lookup"]) && $aDomainDnsResult["disable_dns_lookup"]) {
                    domainLookupDisableDnsLookup($oPdo, $sLookupValue);
                }
            }
        }
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
  <title><?php echo html(getPageTitleText($aAllowedIps)); ?></title>
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
    <label for="query">Domain/IP:</label>
    <input type="text" id="query" name="query" class="domain-lookup-input" form="domain-lookup-form" value="<?php echo html($sDomainValue); ?>" spellcheck="false" required>
    <button type="submit" form="domain-lookup-form" class="button-link domain-lookup-button">Lookup</button>
  </p>
<?php

if ($sLookupMessage != "") {
    echo "  <p class=\"domain-lookup-message " . html($sLookupMessageClass) . "\">" . html($sLookupMessage) . "</p>\n";
}
if ($blLookupIsIp && $aDomainRow) {

?>
  <table class="domain-result-table<?php echo getCondensedTableClass(); ?>">
    <caption>Reverse DNS</caption>
    <tbody>
<?php

    domainLookupRenderResultRow("Host Name", $aDomainRow["reverse_dns"], false, false);

?>
    </tbody>
  </table>
<?php

}
if ($aDomainRow) {

?>
  <table class="domain-result-table<?php echo getCondensedTableClass(); ?>">
    <caption><?php echo html($sLookupCaption); ?></caption>
    <tbody>
<?php

    domainLookupRenderResultRows($aDomainRow, $sLookupSource);

?>
    </tbody>
  </table>
<?php

}
if ($aDomainDnsResult) {
    $aDomainDnsRecords = domainLookupDnsResultRecords($aDomainDnsResult);
    $sDomainDnsMessage = domainLookupDnsResultMessage($aDomainDnsResult);
    if ($sDomainDnsMessage != "") {
        echo "  <p class=\"domain-lookup-message message-warning\">" . html($sDomainDnsMessage) . "</p>\n";
    } elseif (!$aDomainDnsRecords) {
        echo "  <p class=\"domain-lookup-message message-warning\">No DNS records were found.</p>\n";
    }
}
if ($aDomainDnsResult && $aDomainDnsRecords) {

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
