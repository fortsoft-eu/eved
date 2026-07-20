<?php

include "main.php";


requireViewAccess($aAllowedIps, "ex", "ex_csrf_token", true);
$blCanEdit = isFullAccessAllowed($aAllowedIps, "ex");

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$aAddressesSettingsDefaults = array(
    "show_inactive_addresses" => 0,
    "show_inactive_subjects" => 0
);
$aAddressSettings = array();

if (!isset($_SESSION["ex_addresses_settings"]) || !is_array($_SESSION["ex_addresses_settings"])) {
    $_SESSION["ex_addresses_settings"] = array();
}
foreach ($aAddressesSettingsDefaults as $sAddressSettingName => $iAddressSettingDefault) {
    if (isset($_SESSION["ex_addresses_settings"][$sAddressSettingName])) {
        $aAddressSettings[$sAddressSettingName] = (int)$_SESSION["ex_addresses_settings"][$sAddressSettingName] == 1 ? 1 : 0;
    } else {
        $aAddressSettings[$sAddressSettingName] = $iAddressSettingDefault;
    }
}
$aAddressSettings = applyCountrySettings($aAddressSettings);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("ex_csrf_token", true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "get_subject") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    try {
        $aSubject = fetchSubjectEditorData($oPdo, $iSubjectId);
        if (!$aSubject) {
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        sendJsonAndExit(array("success" => true, "subject" => $aSubject));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $sPayload = getPostedValue("subject_payload");
    $aPayload = $sPayload != "" ? json_decode($sPayload, true) : null;
    if (!is_array($aPayload)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject data."), 400);
    }
    $iSubjectId = isset($aPayload["subject_id"]) ? (int)$aPayload["subject_id"] : 0;
    $sSubjectType = payloadValue($aPayload, "subject_type");
    $sBirthDate = payloadValue($aPayload, "birth_date");
    $sDeathDate = payloadValue($aPayload, "death_date");
    $sBirthNumber = normalizeBirthNumber(payloadValue($aPayload, "birth_number"));
    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sSubjectType != "" && !in_array($sSubjectType, getSubjectTypes(), true)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject type."), 400);
    }
    if ($sBirthDate != "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sBirthDate)) {
        sendJsonAndExit(array("success" => false, "message" => "Birth date must use YYYY-MM-DD."), 400);
    }
    if ($sDeathDate != "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDeathDate)) {
        sendJsonAndExit(array("success" => false, "message" => "Death date must use YYYY-MM-DD."), 400);
    }
    if (!$sBirthNumber) {
        sendJsonAndExit(array("success" => false, "message" => "Birth number must contain 9 or 10 digits."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id, subject_type FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        $aSubjectRow = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aSubjectRow) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        if ($sSubjectType != "" && $sSubjectType != (string)$aSubjectRow["subject_type"]) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Subject type cannot be changed."), 409);
        }
        $sEffectiveSubjectType = (string)$aSubjectRow["subject_type"];

        $oStatement = $oPdo->prepare("UPDATE ex_subjects SET is_active = :is_active WHERE id = :subject_id");
        $oStatement->execute(array(
            "is_active" => payloadFlag($aPayload, "is_active"),
            "subject_id" => $iSubjectId
        ));
        $sSubjectName = payloadValue($aPayload, "subject_name_value");
        if ($sEffectiveSubjectType == "person" || $sSubjectName == "") {
            $oStatement = $oPdo->prepare("DELETE FROM ex_subject_names WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
        } else {
            $oStatement = $oPdo->prepare("INSERT INTO ex_subject_names (subject_id, name) VALUES (:subject_id, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            $oStatement->execute(array("subject_id" => $iSubjectId, "name" => $sSubjectName));
        }

        if ($sEffectiveSubjectType != "person") {
            $oStatement = $oPdo->prepare("DELETE FROM ex_persons WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
        } else {
            $aPersonValues = array(
                "title_before" => dbValue(payloadValue($aPayload, "title_before")),
                "first_name" => dbValue(payloadValue($aPayload, "first_name")),
                "middle_name" => dbValue(payloadValue($aPayload, "middle_name")),
                "last_name" => dbValue(payloadValue($aPayload, "last_name")),
                "title_after" => dbValue(payloadValue($aPayload, "title_after")),
                "birth_name" => dbValue(payloadValue($aPayload, "birth_name")),
                "birth_number" => dbValue($sBirthNumber),
                "birth_date" => $sBirthDate != "" ? $sBirthDate : null,
                "death_date" => $sDeathDate != "" ? $sDeathDate : null
            );
            $blHasPersonValues = false;
            foreach ($aPersonValues as $mValue) {
                if ($mValue !== null) {
                    $blHasPersonValues = true;
                    break;
                }
            }
            $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_persons WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
            $blHasPersonRow = (bool)$oStatement->fetch(PDO::FETCH_ASSOC);
            if ($blHasPersonValues || $blHasPersonRow) {
                if ($blHasPersonRow && !$blHasPersonValues) {
                    $oStatement = $oPdo->prepare("DELETE FROM ex_persons WHERE subject_id = :subject_id");
                    $oStatement->execute(array("subject_id" => $iSubjectId));
                } elseif ($blHasPersonRow) {
                    $oStatement = $oPdo->prepare("UPDATE ex_persons SET title_before = :title_before, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, title_after = :title_after, birth_name = :birth_name, birth_number = :birth_number, birth_date = :birth_date, death_date = :death_date WHERE subject_id = :subject_id");
                    $aPersonValues["subject_id"] = $iSubjectId;
                    $oStatement->execute($aPersonValues);
                } else {
                    $oStatement = $oPdo->prepare("INSERT INTO ex_persons (subject_id, title_before, first_name, middle_name, last_name, title_after, birth_name, birth_number, birth_date, death_date) VALUES (:subject_id, :title_before, :first_name, :middle_name, :last_name, :title_after, :birth_name, :birth_number, :birth_date, :death_date)");
                    $aPersonValues["subject_id"] = $iSubjectId;
                    $oStatement->execute($aPersonValues);
                }
            }
        }
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "subject_id" => $iSubjectId, "reload_required" => true));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_addresses_settings") {
    foreach ($aAddressesSettingsDefaults as $sAddressSettingName => $iAddressSettingDefault) {
        $aAddressSettings[$sAddressSettingName] = isset($_POST[$sAddressSettingName]) && (string)$_POST[$sAddressSettingName] == "1" ? 1 : 0;
    }
    $aAddressSettings = saveCountrySettings($aAddressSettings, $_POST);
    $_SESSION["ex_addresses_settings"] = removeCountrySettings($aAddressSettings);
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_shared_address") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $aOldAddress = addressesDecodeMatch(getPostedValue("address_match"));
    if (!is_array($aOldAddress)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    $aNewAddress = addressesPostedAddressValues();
    try {
        $oPdo->beginTransaction();
        $sWhere = addressesMatchSql("old_");
        $aOldParams = addressesMatchParams($aOldAddress, "old_");
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subject_addresses WHERE " . $sWhere . " FOR UPDATE");
        $oStatement->execute($aOldParams);
        $aAddressIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!$aAddressIds) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $aSetSql = array();
        foreach (addressesAddressFields() as $sField) {
            $aSetSql[] = "`" . $sField . "` = :new_" . $sField;
        }
        $aParams = array_merge(addressesMatchParams($aOldAddress, "old_"), addressesMatchParams($aNewAddress, "new_"));
        $oStatement = $oPdo->prepare("UPDATE ex_subject_addresses SET " . implode(", ", $aSetSql) . " WHERE " . $sWhere);
        $oStatement->execute($aParams);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_shared_address") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $aOldAddress = addressesDecodeMatch(getPostedValue("address_match"));
    if (!is_array($aOldAddress)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $sWhere = addressesMatchSql("old_");
        $aOldParams = addressesMatchParams($aOldAddress, "old_");
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subject_addresses WHERE " . $sWhere . " FOR UPDATE");
        $oStatement->execute($aOldParams);
        $aAddressIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!$aAddressIds) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_addresses WHERE " . $sWhere);
        $oStatement->execute($aOldParams);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject_address") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iAddressId = (int)getPostedValue("address_id");
    if ($iAddressId <= 0) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    $aNewAddress = addressesPostedSubjectAddressValues();
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id, subject_id, address_type, organization_name, department_name, care_of, street_name, house_number, evidence_number, orientation_number, orientation_suffix, address_line2, city, city_part, postal_code, region, country, is_primary, is_active, note FROM ex_subject_addresses WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iAddressId));
        $aOldAddress = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aOldAddress) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $aSetSql = array();
        foreach (addressesSubjectAddressFields() as $sField) {
            $aSetSql[] = "`" . $sField . "` = :new_" . $sField;
        }
        $aSetSql[] = "is_primary = :is_primary";
        $aSetSql[] = "is_active = :is_active";
        $aParams = array("id" => $iAddressId);
        foreach (addressesSubjectAddressFields() as $sField) {
            $aParams["new_" . $sField] = array_key_exists($sField, $aNewAddress) ? $aNewAddress[$sField] : null;
        }
        $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
        $aParams["is_primary"] = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
        $aParams["is_active"] = $iIsActive;
        $oStatement = $oPdo->prepare("UPDATE ex_subject_addresses SET " . implode(", ", $aSetSql) . " WHERE id = :id");
        $oStatement->execute($aParams);
        $oPdo->commit();
        $blAddressGroupChanged = json_encode(addressesBuildMatch($aOldAddress)) !== json_encode(addressesBuildMatch($aNewAddress));
        $blAddressVisibilityChanged = empty($aAddressSettings["show_inactive_addresses"]) && (int)$aOldAddress["is_active"] != $iIsActive;
        $aResponse = array(
            "success" => true,
            "reload_required" => $blAddressGroupChanged || $blAddressVisibilityChanged
        );
        if (!$aResponse["reload_required"]) {
            $aUpdatedRows = addressesFetchRows($oPdo, $aAddressSettings);
            foreach ($aUpdatedRows as $aUpdatedRow) {
                $sUpdatedAddressFilterText = addressesFilterText($aUpdatedRow);
                foreach ($aUpdatedRow["subjects"] as $aUpdatedSubject) {
                    if ((int)$aUpdatedSubject["address_id"] == $iAddressId) {
                        $aResponse["address_cell_html"] = addressesRenderAddressCell($aUpdatedRow, count($aUpdatedRow["subjects"]), $blCanEdit);
                        $aResponse["subject_cell_html"] = addressesRenderSubjectCell($aUpdatedSubject, $sUpdatedAddressFilterText, $blCanEdit);
                        break 2;
                    }
                }
            }
            if (empty($aResponse["subject_cell_html"])) {
                $aResponse["reload_required"] = true;
            }
        }
        sendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject_address") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iAddressId = (int)getPostedValue("address_id");
    if ($iAddressId <= 0) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    try {
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_addresses WHERE id = :id");
        $oStatement->execute(array("id" => $iAddressId));
        if (!$oStatement->rowCount()) {
            sendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        sendJsonAndExit(array("success" => true, "reload_required" => true));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

$aAddressRows = addressesFetchRows($oPdo, $aAddressSettings);
$sRenderThrobberHtmlAttributes = getRenderThrobberHtmlAttributes(count($aAddressRows) > 0);
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr"<?php echo $sRenderThrobberHtmlAttributes; ?>>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText("Addresses", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("ex_csrf_token")); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-addresses-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
  </p>
  <div class="confirm-dialog index-settings-dialog" id="index-settings-dialog" hidden>
    <form class="confirm-dialog-box index-settings-form" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_addresses_settings">
      <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken("ex_csrf_token")); ?>">
      <div class="confirm-dialog-header">
        <strong>Address Settings</strong>
        <button type="button" class="confirm-dialog-close js-index-settings-close" aria-label="Close">&times;</button>
      </div>
      <div class="index-settings-options">
        <label><input type="checkbox" name="show_inactive_addresses" value="1"<?php echo $aAddressSettings["show_inactive_addresses"] ? " checked" : ""; ?>> Show inactive addresses</label>
        <label><input type="checkbox" name="show_inactive_subjects" value="1"<?php echo $aAddressSettings["show_inactive_subjects"] ? " checked" : ""; ?>> Show inactive subjects</label>
        <hr>
        <label><input type="checkbox" name="show_czechia_country" value="1" class="js-czechia-country-toggle"<?php echo $aAddressSettings["show_czechia_country"] ? " checked" : ""; ?>> Also show the country Czechia</label>
        <label><input type="checkbox" name="show_czechia_country_in_czech" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aAddressSettings["show_czechia_country_in_czech"] ? "1" : "0") . "\"" . ($aAddressSettings["show_czechia_country"] && $aAddressSettings["show_czechia_country_in_czech"] ? " checked" : "") . ($aAddressSettings["show_czechia_country"] ? "" : " disabled"); ?>> Show the country Czechia in Czech</label>
        <label><input type="checkbox" name="show_czechia_country_as_czech_republic" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aAddressSettings["show_czechia_country_as_czech_republic"] ? "1" : "0") . "\"" . ($aAddressSettings["show_czechia_country"] && $aAddressSettings["show_czechia_country_as_czech_republic"] ? " checked" : "") . ($aAddressSettings["show_czechia_country"] ? "" : " disabled"); ?>> Show &#268;esk&aacute; republika instead of &#268;esko</label>
      </div>
<?php

echo renderSettingsScopeNote();

?>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-index-settings-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

if (count($aAddressRows) > 0) {
    echo renderPageThrobber();
}

?>
  <table id="nx-addresses-table" class="nx-contacts-table table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Address</th>
        <th>Subject</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aAddressRows as $aAddressRow) {
    $iSubjectCount = count($aAddressRow["subjects"]);
    $blFirstSubject = true;
    $sAddressFilterText = addressesFilterText($aAddressRow);
    foreach ($aAddressRow["subjects"] as $aSubject) {
        echo "      <tr data-subject-id=\"" . html($aSubject["subject_id"]) . "\">\n";
        if ($blFirstSubject) {
            echo addressesRenderAddressCell($aAddressRow, $iSubjectCount, $blCanEdit);
            $blFirstSubject = false;
        }
        echo addressesRenderSubjectCell($aSubject, $sAddressFilterText, $blCanEdit) . "      </tr>\n";
    }
}
if (!$aAddressRows) {
    echo "      <tr>\n",
        "        <td colspan=\"2\">No visible records found.</td>\n",
        "      </tr>\n";
}

?>
    </tbody>
  </table>
  <div class="confirm-dialog" id="shared-address-edit-dialog" hidden>
    <form class="confirm-dialog-box subject-edit-dialog subject-address-edit-dialog" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="address_match" value="">
      <div class="confirm-dialog-header">
        <strong>Edit Shared Address</strong>
        <button type="button" class="confirm-dialog-close js-shared-address-edit-close" aria-label="Close">&times;</button>
      </div>
      <div class="subject-address-field-grid">
        <div class="subject-address-field" aria-hidden="true"></div>
        <div class="subject-address-field"><label for="shared-organization-name">Organization Name</label><input type="text" id="shared-organization-name" name="organization_name"></div>
        <div class="subject-address-field"><label for="shared-department-name">Department Name</label><input type="text" id="shared-department-name" name="department_name"></div>
        <div class="subject-address-field"><label for="shared-care-of">Care Of</label><input type="text" id="shared-care-of" name="care_of"></div>
        <div class="subject-address-field"><label for="shared-street-name">Street</label><input type="text" id="shared-street-name" name="street_name"></div>
        <div class="subject-address-field"><label for="shared-house-number">House Number</label><input type="text" id="shared-house-number" name="house_number"></div>
        <div class="subject-address-field"><label for="shared-evidence-number">Evidence Number</label><input type="text" id="shared-evidence-number" name="evidence_number"></div>
        <div class="subject-address-field"><label for="shared-orientation-number">Orientation Number</label><input type="text" id="shared-orientation-number" name="orientation_number"></div>
        <div class="subject-address-field"><label for="shared-orientation-suffix">Orientation Suffix</label><input type="text" id="shared-orientation-suffix" name="orientation_suffix"></div>
        <div class="subject-address-field"><label for="shared-address-line2">Address Line 2</label><input type="text" id="shared-address-line2" name="address_line2"></div>
        <div class="subject-address-field"><label for="shared-city">City</label><input type="text" id="shared-city" name="city"></div>
        <div class="subject-address-field"><label for="shared-city-part">City Part</label><input type="text" id="shared-city-part" name="city_part"></div>
        <div class="subject-address-field"><label for="shared-postal-code">Postal Code</label><input type="text" id="shared-postal-code" name="postal_code"></div>
        <div class="subject-address-field"><label for="shared-region">Region</label><input type="text" id="shared-region" name="region"></div>
        <div class="subject-address-field"><label for="shared-country">Country</label><input type="text" id="shared-country" name="country" list="nx-country-list"></div>
      </div>
      <div class="subject-edit-error" hidden></div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-shared-address-edit-cancel">Cancel</button>
      </div>
    </form>
  </div>
  <div class="confirm-dialog" id="shared-address-delete-dialog" hidden>
    <form class="confirm-dialog-box subject-edit-dialog" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="address_match" value="">
      <div class="confirm-dialog-header">
        <strong>Confirm Deletion</strong>
        <button type="button" class="confirm-dialog-close js-shared-address-delete-close" aria-label="Close">&times;</button>
      </div>
      <p class="confirm-dialog-message">Delete this exact shared address from all subjects using it?</p>
      <div class="subject-edit-error" hidden></div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Delete</button>
        <button type="button" class="confirm-dialog-button js-shared-address-delete-cancel">Cancel</button>
      </div>
    </form>
  </div>
  <div class="confirm-dialog" id="subject-address-edit-dialog" hidden>
    <form class="confirm-dialog-box subject-edit-dialog subject-address-edit-dialog" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="address_id" value="">
      <div class="confirm-dialog-header">
        <strong>Edit Subject Address</strong>
        <button type="button" class="confirm-dialog-close js-subject-address-edit-close" aria-label="Close">&times;</button>
      </div>
      <div class="subject-address-field-grid">
        <div class="subject-address-field">
          <label for="subject-address-type">Type</label>
          <select id="subject-address-type" name="address_type">
<?php

foreach (getAddressTypes() as $sAddressType) {
    echo "            <option value=\"" . html($sAddressType) . "\">" . html(addressesTypeLabel($sAddressType)) . "</option>\n";
}

?>
          </select>
        </div>
        <div class="subject-address-field"><label for="subject-organization-name">Organization Name</label><input type="text" id="subject-organization-name" name="organization_name"></div>
        <div class="subject-address-field"><label for="subject-department-name">Department Name</label><input type="text" id="subject-department-name" name="department_name"></div>
        <div class="subject-address-field"><label for="subject-care-of">Care Of</label><input type="text" id="subject-care-of" name="care_of"></div>
        <div class="subject-address-field"><label for="subject-street-name">Street</label><input type="text" id="subject-street-name" name="street_name"></div>
        <div class="subject-address-field"><label for="subject-house-number">House Number</label><input type="text" id="subject-house-number" name="house_number"></div>
        <div class="subject-address-field"><label for="subject-evidence-number">Evidence Number</label><input type="text" id="subject-evidence-number" name="evidence_number"></div>
        <div class="subject-address-field"><label for="subject-orientation-number">Orientation Number</label><input type="text" id="subject-orientation-number" name="orientation_number"></div>
        <div class="subject-address-field"><label for="subject-orientation-suffix">Orientation Suffix</label><input type="text" id="subject-orientation-suffix" name="orientation_suffix"></div>
        <div class="subject-address-field"><label for="subject-address-line2">Address Line 2</label><input type="text" id="subject-address-line2" name="address_line2"></div>
        <div class="subject-address-field"><label for="subject-city">City</label><input type="text" id="subject-city" name="city"></div>
        <div class="subject-address-field"><label for="subject-city-part">City Part</label><input type="text" id="subject-city-part" name="city_part"></div>
        <div class="subject-address-field"><label for="subject-postal-code">Postal Code</label><input type="text" id="subject-postal-code" name="postal_code"></div>
        <div class="subject-address-field"><label for="subject-region">Region</label><input type="text" id="subject-region" name="region"></div>
        <div class="subject-address-field"><label for="subject-country">Country</label><input type="text" id="subject-country" name="country" list="nx-country-list"></div>
        <div class="subject-address-field"><label for="subject-note">Note</label><input type="text" id="subject-note" name="note"></div>
      </div>
      <label class="checkbox-label"><input type="checkbox" name="is_primary" value="1"> Primary</label>
      <label class="checkbox-label"><input type="checkbox" name="is_active" value="1"> Active</label>
      <div class="subject-edit-error" hidden></div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-subject-address-edit-cancel">Cancel</button>
      </div>
    </form>
  </div>
  <div class="confirm-dialog" id="subject-address-delete-dialog" hidden>
    <form class="confirm-dialog-box subject-edit-dialog" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="address_id" value="">
      <div class="confirm-dialog-header">
        <strong>Confirm Deletion</strong>
        <button type="button" class="confirm-dialog-close js-subject-address-delete-close" aria-label="Close">&times;</button>
      </div>
      <p class="confirm-dialog-message">Delete this address from this subject?</p>
      <div class="subject-edit-error" hidden></div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Delete</button>
        <button type="button" class="confirm-dialog-button js-subject-address-delete-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

echo renderCountryDatalist(),
    renderFilterFocusButton(),
    renderAdminScript($sBaseUrl);

?>
</body>
</html>
