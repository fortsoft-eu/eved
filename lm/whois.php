<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


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

if (isset($_GET["sQuery"])) {
    $sDomainValue = (string)$_GET["sQuery"];
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
                $aLookupResult = $blLookupIsIp ? domainLookupCallIpApi($sLookupValue, $aApiKeys["AbstractAPI.IPIntelligence"]) : domainLookupCallApi($sLookupValue, $aApiKeys["APILayer.WhoisAPI"]);
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
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
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
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>" integrity="sha384-Y1RNflBlpVdW8pnre87MlysCCOkrIrejOEni6hIeWzTbOmboT6BgObKOeMYSJ5C5"></script>
</head>
<body data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="get" id="domain-lookup-form" hidden>
  </form>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="sQuery">Domain/IP:</label>
    <input type="text" id="sQuery" name="sQuery" class="domain-lookup-input" form="domain-lookup-form" value="<?php echo html($sDomainValue); ?>" spellcheck="false" required>
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
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>" integrity="sha384-zi9FpsGST+GJEbRKC9fbVcqxHcE9U9rYiXnYRpsOJC6WiDPEFPeUOmPlmaMCUYcm"></script>
</body>
</html>
