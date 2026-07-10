<?php

include "main.php";


requireExViewAccess($aAllowedIps);
$blCanEdit = isExFullAccessAllowed($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

function nxAddressesNormalizeKey($sValue) {
    $sValue = str_replace("\r\n", "\n", (string)$sValue);
    $sValue = str_replace("\r", "\n", $sValue);
    if (function_exists("mb_strtolower")) {
        return mb_strtolower($sValue, "UTF-8");
    }
    return strtolower($sValue);
}

function nxAddressesCompareRows($aFirst, $aSecond) {
    $iResult = strcmp((string)$aFirst["address_sort"], (string)$aSecond["address_sort"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return strcmp((string)$aFirst["address_text"], (string)$aSecond["address_text"]);
}

function nxAddressesCompareSubjectNames($sFirst, $sSecond) {
    return strcmp((string)$sFirst, (string)$sSecond);
}

function nxAddressesCompareSubjects($aFirst, $aSecond) {
    $iResult = nxAddressesCompareSubjectNames($aFirst["subject_name"], $aSecond["subject_name"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["address_id"] - (int)$aSecond["address_id"];
}

function nxAddressesAddressFields() {
    return array(
        "organization_name",
        "department_name",
        "care_of",
        "street_name",
        "house_number",
        "evidence_number",
        "orientation_number",
        "orientation_suffix",
        "address_line2",
        "city",
        "city_part",
        "postal_code",
        "region",
        "country"
    );
}

function nxAddressesRequiredAddressFields() {
    return array("country");
}

function nxAddressesSubjectAddressFields() {
    return array_merge(array("address_type"), nxAddressesAddressFields(), array("note"));
}

function nxAddressesBuildMatch($aAddress) {
    $aMatch = array();
    foreach (nxAddressesAddressFields() as $sField) {
        $aMatch[$sField] = array_key_exists($sField, $aAddress) && $aAddress[$sField] !== null ? (string)$aAddress[$sField] : null;
    }
    return $aMatch;
}

function nxAddressesEncodeMatch($aMatch) {
    return base64_encode(json_encode($aMatch));
}

function nxAddressesDecodeMatch($sMatch) {
    $sJson = base64_decode((string)$sMatch, true);
    $aMatch = $sJson !== false ? json_decode($sJson, true) : null;
    $aFields = nxAddressesAddressFields();

    if (!is_array($aMatch)) {
        return null;
    }
    foreach ($aFields as $sField) {
        if (!array_key_exists($sField, $aMatch)) {
            return null;
        }
        if ($aMatch[$sField] !== null) {
            $aMatch[$sField] = (string)$aMatch[$sField];
        }
    }
    return $aMatch;
}

function nxAddressesNullValue($sField, $sValue) {
    return in_array($sField, nxAddressesRequiredAddressFields(), true) || (string)$sValue !== "" ? (string)$sValue : null;
}

function nxAddressesMatchSql($sPrefix) {
    $aSql = array();
    foreach (nxAddressesAddressFields() as $sField) {
        $aSql[] = "`" . $sField . "` <=> :" . $sPrefix . $sField;
    }
    return implode(" AND ", $aSql);
}

function nxAddressesMatchParams($aMatch, $sPrefix) {
    $aParams = array();
    foreach (nxAddressesAddressFields() as $sField) {
        $aParams[$sPrefix . $sField] = array_key_exists($sField, $aMatch) ? $aMatch[$sField] : null;
    }
    return $aParams;
}

function nxAddressesPostedAddressValues() {
    $sOrganizationName = nxGetPostedTrimmedValue("organization_name");
    $sDepartmentName = nxGetPostedTrimmedValue("department_name");
    $sCareOf = nxGetPostedTrimmedValue("care_of");
    $sStreetName = nxGetPostedTrimmedValue("street_name");
    $sHouseNumber = nxGetPostedTrimmedValue("house_number");
    $sEvidenceNumber = nxGetPostedTrimmedValue("evidence_number");
    $sOrientationNumber = nxGetPostedTrimmedValue("orientation_number");
    $sOrientationSuffix = nxGetPostedTrimmedValue("orientation_suffix");
    $sAddressLine2 = nxGetPostedTrimmedValue("address_line2");
    $sCity = nxGetPostedTrimmedValue("city");
    $sCityPart = nxGetPostedTrimmedValue("city_part");
    $sPostalCode = nxGetPostedTrimmedValue("postal_code");
    $sRegion = nxGetPostedTrimmedValue("region");
    $sCountry = nxCountryNameToCode(nxGetPostedTrimmedValue("country"));

    if ($sCountry !== "") {
        $sCountry = strtoupper($sCountry);
    }
    if ($sCountry === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry !== "" && !in_array($sCountry, nxGetCountryCodes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = nxNormalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName === "" && $sDepartmentName === "" && $sCareOf === "" && $sStreetName === "" && $sHouseNumber === "" && $sEvidenceNumber === "" && $sOrientationNumber === "" && $sOrientationSuffix === "" && $sAddressLine2 === "" && $sCity === "" && $sCityPart === "" && $sPostalCode === "" && $sRegion === "" && $sCountry === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }

    return array(
        "organization_name" => nxAddressesNullValue("organization_name", $sOrganizationName),
        "department_name" => nxAddressesNullValue("department_name", $sDepartmentName),
        "care_of" => nxAddressesNullValue("care_of", $sCareOf),
        "street_name" => nxAddressesNullValue("street_name", $sStreetName),
        "house_number" => nxAddressesNullValue("house_number", $sHouseNumber),
        "evidence_number" => nxAddressesNullValue("evidence_number", $sEvidenceNumber),
        "orientation_number" => nxAddressesNullValue("orientation_number", $sOrientationNumber),
        "orientation_suffix" => nxAddressesNullValue("orientation_suffix", $sOrientationSuffix),
        "address_line2" => nxAddressesNullValue("address_line2", $sAddressLine2),
        "city" => nxAddressesNullValue("city", $sCity),
        "city_part" => nxAddressesNullValue("city_part", $sCityPart),
        "postal_code" => nxAddressesNullValue("postal_code", $sPostalCode),
        "region" => nxAddressesNullValue("region", $sRegion),
        "country" => $sCountry
    );
}

function nxAddressesPostedSubjectAddressValues() {
    $sAddressType = nxGetPostedTrimmedValue("address_type");
    $sNote = nxGetPostedTrimmedValue("note");
    $aAddress = nxAddressesPostedAddressValues();

    if ($sAddressType === "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, nxGetAddressTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    $aAddress["address_type"] = $sAddressType;
    $aAddress["note"] = nxAddressesNullValue("note", $sNote);
    return $aAddress;
}

function nxAddressesRenderDataAttributes($aAddressRow) {
    $sHtml = " data-address-match=\"" . nxHtml($aAddressRow["address_match"]) . "\"";
    foreach (nxAddressesAddressFields() as $sField) {
        $sAttribute = str_replace("_", "-", $sField);
        $sValue = isset($aAddressRow["address_values"][$sField]) && $aAddressRow["address_values"][$sField] !== null ? (string)$aAddressRow["address_values"][$sField] : "";
        if ($sField === "postal_code") {
            $sValue = nxPostalCodeDisplayValue($aAddressRow["address_values"]["country"], $sValue);
        } else if ($sField === "country") {
            $sHtml .= " data-country-name=\"" . nxHtml(nxCountryCodeToName($sValue)) . "\"";
        }
        $sHtml .= " data-" . $sAttribute . "=\"" . nxHtml($sValue) . "\"";
    }
    return $sHtml;
}

function nxAddressesRenderSubjectDataAttributes($aSubject) {
    $sHtml = " data-address-id=\"" . nxHtml($aSubject["address_id"]) . "\"";
    foreach (nxAddressesSubjectAddressFields() as $sField) {
        $sAttribute = str_replace("_", "-", $sField);
        $sValue = isset($aSubject["address_values"][$sField]) && $aSubject["address_values"][$sField] !== null ? (string)$aSubject["address_values"][$sField] : "";
        if ($sField === "postal_code") {
            $sValue = nxPostalCodeDisplayValue($aSubject["address_values"]["country"], $sValue);
        } else if ($sField === "country") {
            $sHtml .= " data-country-name=\"" . nxHtml(nxCountryCodeToName($sValue)) . "\"";
        }
        $sHtml .= " data-" . $sAttribute . "=\"" . nxHtml($sValue) . "\"";
    }
    $sHtml .= " data-primary=\"" . ((int)$aSubject["is_primary"] === 1 ? "1" : "0") . "\"";
    $sHtml .= " data-active=\"" . ((int)$aSubject["address_is_active"] === 1 ? "1" : "0") . "\"";
    $sHtml .= " data-subject-active=\"" . (!empty($aSubject["is_active"]) ? "1" : "0") . "\"";
    return $sHtml;
}

function nxAddressesSubjectCellClass($aSubject) {
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aSubject["subject_type"]));
    return "nx-address-subject-cell nx-address-subject-type-" . $sSubjectType . (!empty($aSubject["is_active"]) && (int)$aSubject["address_is_active"] === 1 ? " nx-address-subject-active" : " nx-address-subject-inactive");
}

function nxAddressesTypeLabel($sAddressType) {
    return ucwords(str_replace("_", " ", (string)$sAddressType));
}

function nxAddressesFetchRows($oPdo, $aAddressSettings) {
    $aRows = array();
    $aSubjectNames = array();
    $aSubjectRows = nxFetchSubjectRows($oPdo);
    foreach ($aSubjectRows as $aSubjectRow) {
        if (empty($aAddressSettings["show_inactive_subjects"]) && (int)$aSubjectRow["is_active"] !== 1) {
            continue;
        }
        $aSubjectNames[(int)$aSubjectRow["subject_id"]] = array(
            "subject_id" => (int)$aSubjectRow["subject_id"],
            "subject_name" => (string)$aSubjectRow["subject_name"],
            "subject_type" => (string)$aSubjectRow["subject_type"],
            "is_active" => (int)$aSubjectRow["is_active"] === 1
        );
    }

    $aSubjectAddresses = nxFetchSubjectAddresses($oPdo);
    foreach ($aSubjectAddresses as $iSubjectId => $aAddresses) {
        $iSubjectId = (int)$iSubjectId;
        if (!isset($aSubjectNames[$iSubjectId])) {
            continue;
        }
        foreach ($aAddresses as $aAddress) {
            if (empty($aAddressSettings["show_inactive_addresses"]) && (int)$aAddress["is_active"] !== 1) {
                continue;
            }
            $aAddressMatch = nxAddressesBuildMatch($aAddress);
            $sAddressKey = json_encode($aAddressMatch);
            $aCopyAddress = $aAddress;
            $aCopyAddress["note"] = "";
            $sAddressCopyText = nxRenderAddressCopyText($aCopyAddress, "");
            $sAddressText = nxRenderAddressText($aAddress, $aAddressSettings);
            if (trim($sAddressText) === "") {
                continue;
            }
            if (!isset($aRows[$sAddressKey])) {
                $aRows[$sAddressKey] = array(
                    "address_text" => $sAddressText,
                    "address_copy_text" => $sAddressCopyText,
                    "address_sort" => nxAddressesNormalizeKey($sAddressText),
                    "address_match" => nxAddressesEncodeMatch($aAddressMatch),
                    "address_values" => $aAddressMatch,
                    "subjects" => array()
                );
            }
            $aRows[$sAddressKey]["subjects"][] = array_merge($aSubjectNames[$iSubjectId], array(
                "address_id" => (int)$aAddress["id"],
                "address_values" => array(
                    "address_type" => (string)$aAddress["address_type"],
                    "organization_name" => $aAddress["organization_name"],
                    "department_name" => $aAddress["department_name"],
                    "care_of" => $aAddress["care_of"],
                    "street_name" => $aAddress["street_name"],
                    "house_number" => $aAddress["house_number"],
                    "evidence_number" => $aAddress["evidence_number"],
                    "orientation_number" => $aAddress["orientation_number"],
                    "orientation_suffix" => $aAddress["orientation_suffix"],
                    "address_line2" => $aAddress["address_line2"],
                    "city" => $aAddress["city"],
                    "city_part" => $aAddress["city_part"],
                    "postal_code" => $aAddress["postal_code"],
                    "region" => $aAddress["region"],
                    "country" => $aAddress["country"],
                    "note" => $aAddress["note"]
                ),
                "is_primary" => (int)$aAddress["is_primary"],
                "address_is_active" => (int)$aAddress["is_active"]
            ));
        }
    }

    foreach ($aRows as $sKey => $aRow) {
        usort($aRows[$sKey]["subjects"], "nxAddressesCompareSubjects");
    }
    uasort($aRows, "nxAddressesCompareRows");
    return $aRows;
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
        $aAddressSettings[$sAddressSettingName] = (int)$_SESSION["ex_addresses_settings"][$sAddressSettingName] === 1 ? 1 : 0;
    } else {
        $aAddressSettings[$sAddressSettingName] = $iAddressSettingDefault;
    }
}
$aAddressSettings = nxApplyExCountrySettings($aAddressSettings);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    requireExCsrfToken();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "get_subject") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    try {
        $aSubject = nxFetchSubjectEditorData($oPdo, $iSubjectId);
        if (!$aSubject) {
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        nxSendJsonAndExit(array("success" => true, "subject" => $aSubject));
    } catch (Exception $oException) {
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_subject") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $sPayload = nxGetPostedValue("subject_payload");
    $aPayload = $sPayload !== "" ? json_decode($sPayload, true) : null;
    if (!is_array($aPayload)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject data."), 400);
    }

    $iSubjectId = isset($aPayload["subject_id"]) ? (int)$aPayload["subject_id"] : 0;
    $sSubjectType = nxPayloadValue($aPayload, "subject_type");
    $sBirthDate = nxPayloadValue($aPayload, "birth_date");
    $sDeathDate = nxPayloadValue($aPayload, "death_date");
    $sBirthNumber = nxNormalizeBirthNumber(nxPayloadValue($aPayload, "birth_number"));
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sSubjectType !== "" && !in_array($sSubjectType, nxGetSubjectTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject type."), 400);
    }
    if ($sBirthDate !== "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sBirthDate)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Birth date must use YYYY-MM-DD."), 400);
    }
    if ($sDeathDate !== "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDeathDate)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Death date must use YYYY-MM-DD."), 400);
    }
    if ($sBirthNumber === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Birth number must contain 9 or 10 digits."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id, subject_type FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        $aSubjectRow = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aSubjectRow) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        if ($sSubjectType !== "" && $sSubjectType !== (string)$aSubjectRow["subject_type"]) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject type cannot be changed."), 409);
        }
        $sEffectiveSubjectType = (string)$aSubjectRow["subject_type"];

        $oStatement = $oPdo->prepare("UPDATE ex_subjects SET is_active = :is_active WHERE id = :subject_id");
        $oStatement->execute(array(
            "is_active" => nxPayloadFlag($aPayload, "is_active"),
            "subject_id" => $iSubjectId
        ));

        $sSubjectName = nxPayloadValue($aPayload, "subject_name_value");
        if ($sEffectiveSubjectType === "person" || $sSubjectName === "") {
            $oStatement = $oPdo->prepare("DELETE FROM ex_subject_names WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
        } else {
            $oStatement = $oPdo->prepare("INSERT INTO ex_subject_names (subject_id, name) VALUES (:subject_id, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            $oStatement->execute(array("subject_id" => $iSubjectId, "name" => $sSubjectName));
        }

        if ($sEffectiveSubjectType !== "person") {
            $oStatement = $oPdo->prepare("DELETE FROM ex_persons WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
        } else {
            $aPersonValues = array(
                "title_before" => nxDbValue(nxPayloadValue($aPayload, "title_before")),
                "first_name" => nxDbValue(nxPayloadValue($aPayload, "first_name")),
                "middle_name" => nxDbValue(nxPayloadValue($aPayload, "middle_name")),
                "last_name" => nxDbValue(nxPayloadValue($aPayload, "last_name")),
                "title_after" => nxDbValue(nxPayloadValue($aPayload, "title_after")),
                "birth_name" => nxDbValue(nxPayloadValue($aPayload, "birth_name")),
                "birth_number" => nxDbValue($sBirthNumber),
                "birth_date" => $sBirthDate !== "" ? $sBirthDate : null,
                "death_date" => $sDeathDate !== "" ? $sDeathDate : null
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
        nxSendJsonAndExit(array("success" => true, "subject_id" => $iSubjectId, "reload_required" => true));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "save_addresses_settings") {
    foreach ($aAddressesSettingsDefaults as $sAddressSettingName => $iAddressSettingDefault) {
        $aAddressSettings[$sAddressSettingName] = isset($_POST[$sAddressSettingName]) && (string)$_POST[$sAddressSettingName] === "1" ? 1 : 0;
    }
    $aAddressSettings = nxSaveExCountrySettings($aAddressSettings, $_POST);
    $_SESSION["ex_addresses_settings"] = nxRemoveExCountrySettings($aAddressSettings);
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_shared_address") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $aOldAddress = nxAddressesDecodeMatch(nxGetPostedValue("address_match"));
    if (!is_array($aOldAddress)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    $aNewAddress = nxAddressesPostedAddressValues();
    try {
        $oPdo->beginTransaction();
        $sWhere = nxAddressesMatchSql("old_");
        $aOldParams = nxAddressesMatchParams($aOldAddress, "old_");
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subject_addresses WHERE " . $sWhere . " FOR UPDATE");
        $oStatement->execute($aOldParams);
        $aAddressIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
        if (count($aAddressIds) === 0) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $aSetSql = array();
        foreach (nxAddressesAddressFields() as $sField) {
            $aSetSql[] = "`" . $sField . "` = :new_" . $sField;
        }
        $aParams = array_merge(nxAddressesMatchParams($aOldAddress, "old_"), nxAddressesMatchParams($aNewAddress, "new_"));
        $oStatement = $oPdo->prepare("UPDATE ex_subject_addresses SET " . implode(", ", $aSetSql) . " WHERE " . $sWhere);
        $oStatement->execute($aParams);
        $oPdo->commit();
        nxSendJsonAndExit(array("success" => true));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_shared_address") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $aOldAddress = nxAddressesDecodeMatch(nxGetPostedValue("address_match"));
    if (!is_array($aOldAddress)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $sWhere = nxAddressesMatchSql("old_");
        $aOldParams = nxAddressesMatchParams($aOldAddress, "old_");
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subject_addresses WHERE " . $sWhere . " FOR UPDATE");
        $oStatement->execute($aOldParams);
        $aAddressIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
        if (count($aAddressIds) === 0) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_addresses WHERE " . $sWhere);
        $oStatement->execute($aOldParams);
        $oPdo->commit();
        nxSendJsonAndExit(array("success" => true));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_subject_address") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iAddressId = (int)nxGetPostedValue("address_id");
    if ($iAddressId <= 0) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    $aNewAddress = nxAddressesPostedSubjectAddressValues();
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id, subject_id, address_type, organization_name, department_name, care_of, street_name, house_number, evidence_number, orientation_number, orientation_suffix, address_line2, city, city_part, postal_code, region, country, is_primary, is_active, note FROM ex_subject_addresses WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iAddressId));
        $aOldAddress = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aOldAddress) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $aSetSql = array();
        foreach (nxAddressesSubjectAddressFields() as $sField) {
            $aSetSql[] = "`" . $sField . "` = :new_" . $sField;
        }
        $aSetSql[] = "is_primary = :is_primary";
        $aSetSql[] = "is_active = :is_active";
        $aParams = array("id" => $iAddressId);
        foreach (nxAddressesSubjectAddressFields() as $sField) {
            $aParams["new_" . $sField] = array_key_exists($sField, $aNewAddress) ? $aNewAddress[$sField] : null;
        }
        $aParams["is_primary"] = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] === "1" ? 1 : 0;
        $aParams["is_active"] = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;
        $oStatement = $oPdo->prepare("UPDATE ex_subject_addresses SET " . implode(", ", $aSetSql) . " WHERE id = :id");
        $oStatement->execute($aParams);
        $oPdo->commit();
        nxSendJsonAndExit(array(
            "success" => true,
            "reload_required" => json_encode(nxAddressesBuildMatch($aOldAddress)) !== json_encode(nxAddressesBuildMatch($aNewAddress))
        ));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_subject_address") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iAddressId = (int)nxGetPostedValue("address_id");
    if ($iAddressId <= 0) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    try {
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_addresses WHERE id = :id");
        $oStatement->execute(array("id" => $iAddressId));
        if ($oStatement->rowCount() === 0) {
            nxSendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        nxSendJsonAndExit(array("success" => true, "reload_required" => true));
    } catch (Exception $oException) {
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

$aAddressRows = nxAddressesFetchRows($oPdo, $aAddressSettings);
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo nxHtml(getExPageTitleText("Addresses", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo nxHtml(getExCsrfToken()); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-addresses-table" value="<?php echo nxHtml(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
  </p>
  <div class="confirm-dialog index-settings-dialog" id="index-settings-dialog" hidden>
    <form class="confirm-dialog-box index-settings-form" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_addresses_settings">
      <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
      <div class="confirm-dialog-header">
        <strong>Address Settings</strong>
        <button type="button" class="confirm-dialog-close js-index-settings-close" aria-label="Close">&times;</button>
      </div>
      <div class="index-settings-options">
        <label><input type="checkbox" name="show_inactive_addresses" value="1"<?php echo $aAddressSettings["show_inactive_addresses"] ? " checked" : ""; ?>> Show inactive addresses</label>
        <label><input type="checkbox" name="show_inactive_subjects" value="1"<?php echo $aAddressSettings["show_inactive_subjects"] ? " checked" : ""; ?>> Show inactive subjects</label>
        <hr>
        <label><input type="checkbox" name="show_czechia_country" value="1" class="js-czechia-country-toggle"<?php echo $aAddressSettings["show_czechia_country"] ? " checked" : ""; ?>> Also show the country Czechia</label>
        <label><input type="checkbox" name="show_czechia_country_in_czech" value="1" class="js-czechia-country-dependent" data-czechia-stored="<?php echo $aAddressSettings["show_czechia_country_in_czech"] ? "1" : "0"; ?>"<?php echo $aAddressSettings["show_czechia_country"] && $aAddressSettings["show_czechia_country_in_czech"] ? " checked" : ""; ?><?php echo $aAddressSettings["show_czechia_country"] ? "" : " disabled"; ?>> Show the country Czechia in Czech</label>
        <label><input type="checkbox" name="show_czechia_country_as_czech_republic" value="1" class="js-czechia-country-dependent" data-czechia-stored="<?php echo $aAddressSettings["show_czechia_country_as_czech_republic"] ? "1" : "0"; ?>"<?php echo $aAddressSettings["show_czechia_country"] && $aAddressSettings["show_czechia_country_as_czech_republic"] ? " checked" : ""; ?><?php echo $aAddressSettings["show_czechia_country"] ? "" : " disabled"; ?>> Show &#268;esk&aacute; republika instead of &#268;esko</label>
      </div>
      <?php echo nxRenderExSettingsScopeNote(); ?>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-index-settings-cancel">Cancel</button>
      </div>
    </form>
  </div>
  <table id="nx-addresses-table" class="nx-contacts-table table-filter-target">
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
    $sAddressFilterText = (string)$aAddressRow["address_text"];
    $sEditActionEmoji = isset($sEditEmoji) ? $sEditEmoji : "&#9998;";
    $sDeleteActionEmoji = isset($sDeleteEmoji) ? $sDeleteEmoji : "&#128465;";
    $sAddressActions = $blCanEdit ? "<span class=\"nx-list-item-actions\"><a href=\"#\" class=\"nx-item-action js-edit-shared-address\" title=\"Edit shared address\" aria-label=\"Edit shared address\">" . $sEditActionEmoji . "</a><a href=\"#\" class=\"nx-item-action js-delete-shared-address\" title=\"Delete shared address\" aria-label=\"Delete shared address\">" . $sDeleteActionEmoji . "</a></span>" : "";
    foreach ($aAddressRow["subjects"] as $aFilterSubject) {
        $sAddressFilterText .= " " . (string)$aFilterSubject["subject_name"];
    }
    foreach ($aAddressRow["subjects"] as $aSubject) {
        $sSubjectActions = $blCanEdit ? "<span class=\"nx-list-item-actions\"><a href=\"#\" class=\"nx-item-action js-edit-subject-address-local\" title=\"Edit subject address\" aria-label=\"Edit subject address\">" . $sEditActionEmoji . "</a><a href=\"#\" class=\"nx-item-action js-delete-subject-address-local\" title=\"Delete subject address\" aria-label=\"Delete subject address\">" . $sDeleteActionEmoji . "</a></span>" : "";
        $sSubjectEditAction = $blCanEdit ? "<span class=\"nx-list-item-actions\"><a href=\"#\" class=\"nx-item-action js-edit-subject\" data-subject-id=\"" . nxHtml($aSubject["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditActionEmoji . "</a></span>" : "";
        $sSubjectValueClass = "nx-subject-item-value" . ((string)$aSubject["address_values"]["address_type"] === "main" ? " nx-subject-address-main-value" : "");
        $sSubjectPrimaryFlag = "<span class=\"nx-subject-item-flags\"><span title=\"Primary\">" . ((int)$aSubject["is_primary"] === 1 ? "&#11088;" : "") . "</span><span title=\"Inactive\">" . ((int)$aSubject["address_is_active"] === 1 ? "" : "&#9940;") . "</span></span>";
        echo "      <tr data-subject-id=\"" . nxHtml($aSubject["subject_id"]) . "\">\n";
        if ($blFirstSubject) {
            echo "        <td class=\"nx-address-cell\" rowspan=\"" . nxHtml($iSubjectCount) . "\"" . nxAddressesRenderDataAttributes($aAddressRow) . ">"
                . "<span class=\"nx-subject-item-value\">" . nxHtmlValue($aAddressRow["address_text"]) . "</span>"
                . nxRenderCopyAction($aAddressRow["address_copy_text"])
                . $sAddressActions
                . "</td>\n";
            $blFirstSubject = false;
        }
        echo "        <td class=\"" . nxHtml(nxAddressesSubjectCellClass($aSubject)) . " nx-list-item nx-subject-address-item\"" . nxAddressesRenderSubjectDataAttributes($aSubject) . "><span class=\"nx-column-hidden\">" . nxHtmlValue($sAddressFilterText) . "</span><span class=\"" . nxHtml($sSubjectValueClass) . "\">" . nxHtmlValue($aSubject["subject_name"]) . "</span>" . nxRenderCopyAction($aSubject["subject_name"]) . $sSubjectEditAction . $sSubjectPrimaryFlag . $sSubjectActions . "</td>\n"
            . "      </tr>\n";
    }
}
if (count($aAddressRows) === 0) {
    echo "      <tr>\n"
        . "        <td colspan=\"2\">No visible records found.</td>\n"
        . "      </tr>\n";
}

?>
    </tbody>
  </table>
  <div class="confirm-dialog" id="shared-address-edit-dialog" hidden>
    <form class="confirm-dialog-box subject-edit-dialog subject-address-edit-dialog" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
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
    <form class="confirm-dialog-box subject-edit-dialog" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
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
    <form class="confirm-dialog-box subject-edit-dialog subject-address-edit-dialog" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
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

foreach (nxGetAddressTypes() as $sAddressType) {
    echo "            <option value=\"" . nxHtml($sAddressType) . "\">" . nxHtml(nxAddressesTypeLabel($sAddressType)) . "</option>\n";
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
    <form class="confirm-dialog-box subject-edit-dialog" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
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
  <?php echo nxRenderCountryDatalist(); ?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter">&#128269; Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
