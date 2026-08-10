<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "csrf_token");

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
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <div class="admin-top ares-lookup-top">
    <form action="<?php echo $sBaseUrl; ?>ares.php" method="get" id="ares-lookup-form" class="ares-lookup-form">
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
