<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if (session_status() == PHP_SESSION_ACTIVE) {
    session_write_close();
}


$sCompanyIdValue = isset($_GET["ico"]) ? preg_replace("/\\s+/", "", trim((string)$_GET["ico"])) : "";
$sBusinessNameValue = isset($_GET["business_name"]) ? trim(preg_replace("/\\s+/", " ", (string)$_GET["business_name"])) : "";
$sRegisteredOfficeValue = isset($_GET["registered_office"]) ? trim(preg_replace("/\\s+/", " ", (string)$_GET["registered_office"])) : "";
$sLegalFormValue = isset($_GET["legal_form"]) ? trim((string)$_GET["legal_form"]) : "";
$sLegalFormRosValue = isset($_GET["legal_form_ros"]) ? trim((string)$_GET["legal_form_ros"]) : "";
$sTaxOfficeValue = isset($_GET["tax_office"]) ? trim((string)$_GET["tax_office"]) : "";
$sCzNaceValue = isset($_GET["cz_nace"]) ? trim((string)$_GET["cz_nace"]) : "";
$sAresMessage = "";
$sAresMessageClass = "";
$sAresMode = "";
$aAresData = null;

if (isset($_GET["ico"]) || isset($_GET["business_name"]) || isset($_GET["registered_office"]) || isset($_GET["legal_form"]) || isset($_GET["legal_form_ros"]) || isset($_GET["tax_office"]) || isset($_GET["cz_nace"])) {
    if ($sCompanyIdValue == "" && $sBusinessNameValue == "" && $sRegisteredOfficeValue == "" && $sLegalFormValue == "" && $sLegalFormRosValue == "" && $sTaxOfficeValue == "" && $sCzNaceValue == "") {
        $sAresMessage = "Enter at least one ARES filter.";
        $sAresMessageClass = "message-error";
    } elseif ($sCompanyIdValue != "" && preg_match("/^\\d{8}$/", $sCompanyIdValue) !== 1) {
        $sAresMessage = "Enter company ID as 8 digits.";
        $sAresMessageClass = "message-error";
    } elseif ($sBusinessNameValue != "" && strlen($sBusinessNameValue) > 2000) {
        $sAresMessage = "Business name is too long.";
        $sAresMessageClass = "message-error";
    } elseif ($sRegisteredOfficeValue != "" && strlen($sRegisteredOfficeValue) > 1500) {
        $sAresMessage = "Registered office is too long.";
        $sAresMessageClass = "message-error";
    } elseif ($sLegalFormValue != "" && !preg_match("/^\\d{3}$/", $sLegalFormValue)) {
        $sAresMessage = "Enter legal form as 3 digits.";
        $sAresMessageClass = "message-error";
    } elseif ($sLegalFormRosValue != "" && !preg_match("/^\\d{3}$/", $sLegalFormRosValue)) {
        $sAresMessage = "Enter legal form ROS as 3 digits.";
        $sAresMessageClass = "message-error";
    } elseif ($sTaxOfficeValue != "" && !preg_match("/^\\d{3}$/", $sTaxOfficeValue)) {
        $sAresMessage = "Enter tax office as 3 digits.";
        $sAresMessageClass = "message-error";
    } elseif ($sCzNaceValue != "" && strlen($sCzNaceValue) > 5) {
        $sAresMessage = "CZ-NACE is too long.";
        $sAresMessageClass = "message-error";
    } else {
        if ($sCompanyIdValue != "" && $sBusinessNameValue == "" && $sRegisteredOfficeValue == "" && $sLegalFormValue == "" && $sLegalFormRosValue == "" && $sTaxOfficeValue == "" && $sCzNaceValue == "") {
            $sAresMode = "detail";
            $aAresResult = aresLookupCallApi("/ekonomicke-subjekty/" . rawurlencode($sCompanyIdValue));
        } else {
            $sAresMode = "search";
            $aPayload = array(
                "start" => 0,
                "pocet" => 20,
                "razeni" => array("obchodniJmeno")
            );
            if ($sCompanyIdValue != "") {
                $aPayload["ico"] = array($sCompanyIdValue);
            }
            if ($sBusinessNameValue != "") {
                $aPayload["obchodniJmeno"] = $sBusinessNameValue;
            }
            if ($sRegisteredOfficeValue != "") {
                $aPayload["sidlo"] = array("textovaAdresa" => $sRegisteredOfficeValue);
            }
            if ($sLegalFormValue != "") {
                $aPayload["pravniForma"] = array($sLegalFormValue);
            }
            if ($sLegalFormRosValue != "") {
                $aPayload["pravniFormaRos"] = array($sLegalFormRosValue);
            }
            if ($sTaxOfficeValue != "") {
                $aPayload["financniUrad"] = array($sTaxOfficeValue);
            }
            if ($sCzNaceValue != "") {
                $aPayload["czNace"] = array($sCzNaceValue);
            }
            $aAresResult = aresLookupCallApi("/ekonomicke-subjekty/vyhledat", $aPayload);
        }
        if ($aAresResult["success"]) {
            $aAresData = $aAresResult["data"];
            if ($sAresMode == "search" && !aresLookupSearchResultItems($aAresData)) {
                $sAresMessage = "No ARES records found.";
                $sAresMessageClass = "message-warning";
            } else {
                $sAresMessage = $sAresMode == "detail" ? "ARES record loaded." : "ARES records loaded.";
                $sAresMessageClass = "message-success";
            }
        } else {
            $sAresMessage = $aAresResult["message"];
            $sAresMessageClass = "message-warning";
        }
    }
}

$sScriptUrl = $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]);
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
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <div class="admin-top ares-lookup-top">
    <form action="<?php echo $sScriptUrl; ?>" method="get" id="ares-lookup-form" class="ares-lookup-form">
      <span class="ares-lookup-fields">
        <span class="ares-lookup-field">
          <label for="ico">Company ID</label>
          <input type="text" id="ico" name="ico" class="ares-lookup-input" value="<?php echo html($sCompanyIdValue); ?>" inputmode="numeric" maxlength="8">
        </span>
        <span class="ares-lookup-field">
          <label for="business_name">Business Name</label>
          <input type="text" id="business_name" name="business_name" class="ares-lookup-input" value="<?php echo html($sBusinessNameValue); ?>" maxlength="2000">
        </span>
        <span class="ares-lookup-field">
          <label for="registered_office">Registered Office</label>
          <input type="text" id="registered_office" name="registered_office" class="ares-lookup-input" value="<?php echo html($sRegisteredOfficeValue); ?>" maxlength="1500">
        </span>
        <span class="ares-lookup-field">
          <label for="legal_form">Legal Form</label>
          <input type="text" id="legal_form" name="legal_form" class="ares-lookup-input" value="<?php echo html($sLegalFormValue); ?>" inputmode="numeric" maxlength="3">
        </span>
        <span class="ares-lookup-field">
          <label for="legal_form_ros">Legal Form ROS</label>
          <input type="text" id="legal_form_ros" name="legal_form_ros" class="ares-lookup-input" value="<?php echo html($sLegalFormRosValue); ?>" inputmode="numeric" maxlength="3">
        </span>
        <span class="ares-lookup-field">
          <label for="tax_office">Tax Office</label>
          <input type="text" id="tax_office" name="tax_office" class="ares-lookup-input" value="<?php echo html($sTaxOfficeValue); ?>" inputmode="numeric" maxlength="3">
        </span>
        <span class="ares-lookup-field">
          <label for="cz_nace">CZ-NACE</label>
          <input type="text" id="cz_nace" name="cz_nace" class="ares-lookup-input" value="<?php echo html($sCzNaceValue); ?>" maxlength="5">
        </span>
      </span>
      <p class="admin-controls">
        <button type="submit" class="button-link ares-lookup-button">Lookup</button>
      </p>
    </form>
  </div>
<?php

if ($sAresMessage != "") {
    echo "  <p class=\"ares-lookup-message " . html($sAresMessageClass) . "\">" . html($sAresMessage) . "</p>\n";
}
if ($aAresData && $sAresMode == "detail") {

?>
  <table class="ares-result-table<?php echo getCondensedTableClass(); ?>">
    <caption>ARES Data</caption>
    <tbody>
<?php

    aresLookupRenderDetailRows($aAresData);

?>
    </tbody>
  </table>
<?php

}
if ($aAresData && $sAresMode == "search" && aresLookupSearchResultItems($aAresData)) {

?>
  <table class="ares-result-table<?php echo getCondensedTableClass(); ?>">
    <caption>ARES Results</caption>
    <thead>
      <tr>
        <th>Company ID</th>
        <th>Business Name</th>
        <th>Registered Office</th>
        <th>Date Established</th>
        <th>Date Terminated</th>
        <th>Primary Source</th>
      </tr>
    </thead>
    <tbody>
<?php

    aresLookupRenderSearchRows($aAresData, $sBaseUrl);

?>
    </tbody>
  </table>
<?php

}

?>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
