<?php

include "main.php";


requireExViewAccess($aAllowedIps);
$blCanEdit = isExFullAccessAllowed($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$aBirthdaySettingsDefaults = array(
    "show_inactive_subjects" => 0,
    "show_inactive_nicknames" => 0,
    "show_inactive_addresses" => 0,
    "show_inactive_contacts" => 0,
    "show_inactive_notes" => 0
);
$aBirthdaySettings = array();

if (!isset($_SESSION["ex_inter_settings"]) || !is_array($_SESSION["ex_inter_settings"])) {
    $_SESSION["ex_inter_settings"] = array();
}
foreach ($aBirthdaySettingsDefaults as $sBirthdaySettingName => $iBirthdaySettingDefault) {
    if (isset($_SESSION["ex_inter_settings"][$sBirthdaySettingName])) {
        $aBirthdaySettings[$sBirthdaySettingName] = (int)$_SESSION["ex_inter_settings"][$sBirthdaySettingName] == 1 ? 1 : 0;
    } else {
        $aBirthdaySettings[$sBirthdaySettingName] = $iBirthdaySettingDefault;
    }
}
$aBirthdaySettings = nxApplyExCountrySettings($aBirthdaySettings);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireExCsrfToken();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "mark_communication_served") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT p.subject_id, p.inter_served_at FROM ex_persons AS p INNER JOIN ex_subjects AS s ON s.id = p.subject_id WHERE p.subject_id = :subject_id AND s.subject_type = 'person' FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        $aPerson = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aPerson) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $aBirthdayInfo = nxInterGetBirthdayInfo(isset($aPerson["inter_served_at"]) ? $aPerson["inter_served_at"] : "");
        if (!is_array($aBirthdayInfo)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Communication is not in the current service window."), 409);
        }
        $oServedAt = new DateTimeImmutable("now");
        $oStatement = $oPdo->prepare("UPDATE ex_persons SET inter_served_at = :inter_served_at WHERE subject_id = :subject_id");
        $oStatement->execute(array("inter_served_at" => $oServedAt->format("Y-m-d H:i:s.u"), "subject_id" => $iSubjectId));
        $oPdo->commit();
        nxSendJsonAndExit(array("success" => true, "subject_id" => $iSubjectId));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_inter_settings") {
    foreach ($aBirthdaySettingsDefaults as $sBirthdaySettingName => $iBirthdaySettingDefault) {
        $aBirthdaySettings[$sBirthdaySettingName] = isset($_POST[$sBirthdaySettingName]) && (string)$_POST[$sBirthdaySettingName] == "1" ? 1 : 0;
    }
    $aBirthdaySettings = nxSaveExCountrySettings($aBirthdaySettings, $_POST);
    $_SESSION["ex_inter_settings"] = nxRemoveExCountrySettings($aBirthdaySettings);
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}

$sBdPostAction = $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) ? (string)$_POST["action"] : "";
$aBdEditActions = array(
    "get_subject",
    "get_subject_portal_user",
    "update_subject_portal_user",
    "update_subject",
    "create_subject_nickname",
    "update_subject_nickname",
    "create_subject_address",
    "update_subject_address",
    "create_subject_group",
    "update_subject_group",
    "create_subject_note",
    "update_subject_note",
    "delete_subject",
    "delete_subject_contact",
    "delete_subject_nickname",
    "delete_subject_address",
    "delete_subject_group",
    "delete_subject_note",
    "create_contact",
    "update_contact"
);
$aBdCreateActions = array(
    "create_subject"
);
if (in_array($sBdPostAction, $aBdCreateActions, true)) {
    nxSendJsonAndExit(array("success" => false, "message" => "Adding records is not available here."), 403);
}
if (!$blCanEdit && in_array($sBdPostAction, $aBdEditActions, true)) {
    nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
}

if ($sBdPostAction == "get_subject") {
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

if ($sBdPostAction == "get_subject_portal_user") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    try {
        $aSubject = nxFetchSubjectPortalEditorData($oPdo, $iSubjectId);
        if (!$aSubject) {
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        nxSendJsonAndExit(array("success" => true, "subject" => $aSubject));
    } catch (Exception $oException) {
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "update_subject_portal_user") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $aPermissionKeys = isset($_POST["permissions"]) && is_array($_POST["permissions"]) ? $_POST["permissions"] : array();
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
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
        $aPayload = array(
            "portal_user_enabled" => isset($_POST["portal_user_enabled"]) && (string)$_POST["portal_user_enabled"] == "1" ? "1" : "0",
            "portal_user_name" => nxGetPostedTrimmedValue("portal_user_name"),
            "portal_password" => nxGetPostedValue("portal_password"),
            "portal_user_active" => isset($_POST["portal_user_active"]) && (string)$_POST["portal_user_active"] == "1" ? "1" : "0",
            "portal_permission_keys" => $aPermissionKeys
        );
        nxSaveSubjectPortalAccess($oPdo, $iSubjectId, (string)$aSubjectRow["subject_type"], $aPayload);
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected user name already exists."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "update_subject") {
    $sPayload = nxGetPostedValue("subject_payload");
    $aPayload = $sPayload != "" ? json_decode($sPayload, true) : null;
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
    if ($sSubjectType != "" && !in_array($sSubjectType, nxGetSubjectTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject type."), 400);
    }
    if ($sBirthDate != "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sBirthDate)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Birth date must use YYYY-MM-DD."), 400);
    }
    if ($sDeathDate != "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDeathDate)) {
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
        if ($sSubjectType != "" && $sSubjectType != (string)$aSubjectRow["subject_type"]) {
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
                "title_before" => nxDbValue(nxPayloadValue($aPayload, "title_before")),
                "first_name" => nxDbValue(nxPayloadValue($aPayload, "first_name")),
                "middle_name" => nxDbValue(nxPayloadValue($aPayload, "middle_name")),
                "last_name" => nxDbValue(nxPayloadValue($aPayload, "last_name")),
                "title_after" => nxDbValue(nxPayloadValue($aPayload, "title_after")),
                "birth_name" => nxDbValue(nxPayloadValue($aPayload, "birth_name")),
                "birth_number" => nxDbValue($sBirthNumber),
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

        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "update_subject_nickname") {
    $iNicknameId = isset($_POST["nickname_id"]) ? (int)$_POST["nickname_id"] : 0;
    $sNickname = nxGetPostedTrimmedValue("nickname");
    $sContext = nxGetPostedTrimmedValue("context");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iNicknameId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid nickname."), 400);
    }
    if ($sNickname == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Nickname is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_nicknames WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNicknameId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Nickname was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_subject_nicknames SET nickname = :nickname, context = :context, is_primary = :is_primary, is_active = :is_active, note = :note WHERE id = :id");
        $oStatement->execute(array(
            "nickname" => $sNickname,
            "context" => $sContext != "" ? $sContext : null,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null,
            "id" => $iNicknameId
        ));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "create_subject_nickname") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sNickname = nxGetPostedTrimmedValue("nickname");
    $sContext = nxGetPostedTrimmedValue("context");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sNickname == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Nickname is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_nicknames (subject_id, nickname, context, is_primary, is_active, note) VALUES (:subject_id, :nickname, :context, :is_primary, :is_active, :note)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "nickname" => $sNickname,
            "context" => $sContext != "" ? $sContext : null,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null
        ));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "update_subject_address") {
    $iAddressId = isset($_POST["address_id"]) ? (int)$_POST["address_id"] : 0;
    $sAddressType = nxGetPostedTrimmedValue("address_type");
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
    $sCountry = nxGetPostedTrimmedValue("country");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    if ($sCountry != "") {
        $sCountry = strtoupper($sCountry);
    }

    if ($iAddressId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    if ($sAddressType == "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, nxGetAddressTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    if ($sCountry == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry != "" && !in_array($sCountry, nxGetCountryCodes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = nxNormalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName == "" && $sDepartmentName == "" && $sCareOf == "" && $sStreetName == "" && $sHouseNumber == "" && $sEvidenceNumber == "" && $sOrientationNumber == "" && $sOrientationSuffix == "" && $sAddressLine2 == "" && $sCity == "" && $sCityPart == "" && $sPostalCode == "" && $sRegion == "" && $sCountry == "" && $sNote == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_addresses WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iAddressId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_subject_addresses SET address_type = :address_type, organization_name = :organization_name, department_name = :department_name, care_of = :care_of, street_name = :street_name, house_number = :house_number, evidence_number = :evidence_number, orientation_number = :orientation_number, orientation_suffix = :orientation_suffix, address_line2 = :address_line2, city = :city, city_part = :city_part, postal_code = :postal_code, region = :region, country = :country, is_primary = :is_primary, is_active = :is_active, note = :note WHERE id = :id");
        $oStatement->execute(array(
            "address_type" => $sAddressType,
            "organization_name" => $sOrganizationName != "" ? $sOrganizationName : null,
            "department_name" => $sDepartmentName != "" ? $sDepartmentName : null,
            "care_of" => $sCareOf != "" ? $sCareOf : null,
            "street_name" => $sStreetName != "" ? $sStreetName : null,
            "house_number" => $sHouseNumber != "" ? $sHouseNumber : null,
            "evidence_number" => $sEvidenceNumber != "" ? $sEvidenceNumber : null,
            "orientation_number" => $sOrientationNumber != "" ? $sOrientationNumber : null,
            "orientation_suffix" => $sOrientationSuffix != "" ? $sOrientationSuffix : null,
            "address_line2" => $sAddressLine2 != "" ? $sAddressLine2 : null,
            "city" => $sCity != "" ? $sCity : null,
            "city_part" => $sCityPart != "" ? $sCityPart : null,
            "postal_code" => $sPostalCode != "" ? $sPostalCode : null,
            "region" => $sRegion != "" ? $sRegion : null,
            "country" => $sCountry,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null,
            "id" => $iAddressId
        ));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "create_subject_address") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sAddressType = nxGetPostedTrimmedValue("address_type");
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
    $sCountry = nxGetPostedTrimmedValue("country");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    if ($sCountry != "") {
        $sCountry = strtoupper($sCountry);
    }

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sAddressType == "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, nxGetAddressTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    if ($sCountry == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry != "" && !in_array($sCountry, nxGetCountryCodes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = nxNormalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName == "" && $sDepartmentName == "" && $sCareOf == "" && $sStreetName == "" && $sHouseNumber == "" && $sEvidenceNumber == "" && $sOrientationNumber == "" && $sOrientationSuffix == "" && $sAddressLine2 == "" && $sCity == "" && $sCityPart == "" && $sPostalCode == "" && $sRegion == "" && $sCountry == "" && $sNote == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_addresses (subject_id, address_type, organization_name, department_name, care_of, street_name, house_number, evidence_number, orientation_number, orientation_suffix, address_line2, city, city_part, postal_code, region, country, is_primary, is_active, note) VALUES (:subject_id, :address_type, :organization_name, :department_name, :care_of, :street_name, :house_number, :evidence_number, :orientation_number, :orientation_suffix, :address_line2, :city, :city_part, :postal_code, :region, :country, :is_primary, :is_active, :note)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "address_type" => $sAddressType,
            "organization_name" => $sOrganizationName != "" ? $sOrganizationName : null,
            "department_name" => $sDepartmentName != "" ? $sDepartmentName : null,
            "care_of" => $sCareOf != "" ? $sCareOf : null,
            "street_name" => $sStreetName != "" ? $sStreetName : null,
            "house_number" => $sHouseNumber != "" ? $sHouseNumber : null,
            "evidence_number" => $sEvidenceNumber != "" ? $sEvidenceNumber : null,
            "orientation_number" => $sOrientationNumber != "" ? $sOrientationNumber : null,
            "orientation_suffix" => $sOrientationSuffix != "" ? $sOrientationSuffix : null,
            "address_line2" => $sAddressLine2 != "" ? $sAddressLine2 : null,
            "city" => $sCity != "" ? $sCity : null,
            "city_part" => $sCityPart != "" ? $sCityPart : null,
            "postal_code" => $sPostalCode != "" ? $sPostalCode : null,
            "region" => $sRegion != "" ? $sRegion : null,
            "country" => $sCountry,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null
        ));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "update_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    $sGroupName = nxGetPostedTrimmedValue("name");

    if ($iSubjectId < 1 || $iGroupId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid group link."), 400);
    }
    if ($sGroupName == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Group was not found."), 404);
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE name = :name AND id <> :id");
        $oStatement->execute(array("name" => $sGroupName, "id" => $iGroupId));
        if ($oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "The selected group name already exists."), 409);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_groups SET name = :name WHERE id = :id");
        $oStatement->execute(array("name" => $sGroupName, "id" => $iGroupId));
        $oPdo->commit();
        $aResponse = nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit);
        $aResponse["group"] = array(
            "group_id" => $iGroupId,
            "name" => $sGroupName
        );
        nxSendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "create_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sGroupName = nxGetPostedTrimmedValue("name");

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sGroupName == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE name = :name FOR UPDATE");
        $oStatement->execute(array("name" => $sGroupName));
        $iGroupId = (int)$oStatement->fetchColumn();
        if ($iGroupId < 1) {
            $oStatement = $oPdo->prepare("INSERT INTO ex_groups (name) VALUES (:name)");
            $oStatement->execute(array("name" => $sGroupName));
            $iGroupId = (int)$oPdo->lastInsertId();
        }
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        if ($oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject is already assigned to this group."), 409);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_groups (subject_id, group_id) VALUES (:subject_id, :group_id)");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        $oPdo->commit();
        $aResponse = nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit);
        $aResponse["group"] = array(
            "group_id" => $iGroupId,
            "name" => $sGroupName
        );
        nxSendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected group already exists or is already assigned."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "update_subject_note") {
    $iNoteId = isset($_POST["note_id"]) ? (int)$_POST["note_id"] : 0;
    $sNoteText = nxGetPostedTrimmedValue("note_text");
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iNoteId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid note."), 400);
    }
    if ($sNoteText == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Note text is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_notes WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNoteId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Note was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_subject_notes SET note_text = :note_text, is_active = :is_active WHERE id = :id");
        $oStatement->execute(array("note_text" => $sNoteText, "is_active" => $iIsActive, "id" => $iNoteId));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "create_subject_note") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sNoteText = nxGetPostedTrimmedValue("note_text");
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sNoteText == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Note text is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_notes (subject_id, note_text, is_active) VALUES (:subject_id, :note_text, :is_active)");
        $oStatement->execute(array("subject_id" => $iSubjectId, "note_text" => $sNoteText, "is_active" => $iIsActive));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "delete_subject") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subjects WHERE id = :subject_id");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        $oPdo->commit();
        nxSendJsonAndExit(array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "delete_subject_contact") {
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    if ($iSubjectContactId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact link."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_contacts WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iSubjectContactId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Contact link was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_contacts WHERE id = :id");
        $oStatement->execute(array("id" => $iSubjectContactId));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "delete_subject_nickname") {
    $iNicknameId = isset($_POST["nickname_id"]) ? (int)$_POST["nickname_id"] : 0;
    if ($iNicknameId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid nickname."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_nicknames WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNicknameId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Nickname was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_nicknames WHERE id = :id");
        $oStatement->execute(array("id" => $iNicknameId));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "delete_subject_address") {
    $iAddressId = isset($_POST["address_id"]) ? (int)$_POST["address_id"] : 0;
    if ($iAddressId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_addresses WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iAddressId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_addresses WHERE id = :id");
        $oStatement->execute(array("id" => $iAddressId));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "delete_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    if ($iSubjectId < 1 || $iGroupId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid group link."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "delete_subject_note") {
    $iNoteId = isset($_POST["note_id"]) ? (int)$_POST["note_id"] : 0;
    if ($iNoteId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid note."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_notes WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNoteId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Note was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_notes WHERE id = :id");
        $oStatement->execute(array("id" => $iNoteId));
        $oPdo->commit();
        nxSendJsonAndExit(nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "create_contact") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sContactValue = nxGetPostedValue("contact_value");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    $aContactType = nxGetContactTypeById($iContactTypeId, $oPdo, true);

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if (!$aContactType) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact type."), 400);
    }
    $sContactValue = nxNormalizeContactInputForStorage((string)$aContactType["contact_type"], $sContactValue);
    if ($sContactValue === false) {
        nxSendJsonAndExit(array("success" => false, "message" => nxContactInputErrorMessage((string)$aContactType["contact_type"])), 400);
    }
    if ($sContactValue == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Contact value is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }

        $oStatement = $oPdo->prepare("SELECT id FROM ex_contacts WHERE contact_type_id = :contact_type_id AND contact_value = :contact_value FOR UPDATE");
        $oStatement->execute(array("contact_type_id" => $iContactTypeId, "contact_value" => $sContactValue));
        $iContactId = (int)$oStatement->fetchColumn();
        if ($iContactId < 1) {
            $oStatement = $oPdo->prepare("INSERT INTO ex_contacts (contact_type_id, contact_value) VALUES (:contact_type_id, :contact_value)");
            $oStatement->execute(array("contact_type_id" => $iContactTypeId, "contact_value" => $sContactValue));
            $iContactId = (int)$oPdo->lastInsertId();
        }

        $oStatement = $oPdo->prepare("SELECT id FROM ex_subject_contacts WHERE subject_id = :subject_id AND contact_id = :contact_id");
        $oStatement->execute(array("subject_id" => $iSubjectId, "contact_id" => $iContactId));
        if ($oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "This contact is already assigned to the subject."), 409);
        }

        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_contacts (subject_id, contact_id, is_primary, is_active, note) VALUES (:subject_id, :contact_id, :is_primary, :is_active, :note)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "contact_id" => $iContactId,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null
        ));
        $iSubjectContactId = (int)$oPdo->lastInsertId();
        $oPdo->commit();

        $aResponse = nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blCanEdit);
        $aResponse["contact"] = array(
            "subject_contact_id" => $iSubjectContactId,
            "contact_id" => $iContactId,
            "contact_type_id" => $iContactTypeId,
            "contact_type" => (string)$aContactType["contact_type"],
            "contact_type_label" => (string)$aContactType["name"],
            "contact_value" => $sContactValue,
            "contact_display_value" => nxContactDisplayValue((string)$aContactType["contact_type"], $sContactValue),
            "note" => $sNote,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive
        );
        nxSendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected contact value already exists or is already assigned."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($sBdPostAction == "update_contact") {
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sContactValue = nxGetPostedValue("contact_value");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    $aContactType = nxGetContactTypeById($iContactTypeId, $oPdo, true);

    if ($iSubjectContactId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact link."), 400);
    }
    if (!$aContactType) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact type."), 400);
    }
    $sContactValue = nxNormalizeContactInputForStorage((string)$aContactType["contact_type"], $sContactValue);
    if ($sContactValue === false) {
        nxSendJsonAndExit(array("success" => false, "message" => nxContactInputErrorMessage((string)$aContactType["contact_type"])), 400);
    }
    if ($sContactValue == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Contact value is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT sc.id, sc.subject_id, sc.contact_id FROM ex_subject_contacts AS sc WHERE sc.id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iSubjectContactId));
        $aSubjectContact = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aSubjectContact) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Contact link was not found."), 404);
        }

        $iContactId = (int)$aSubjectContact["contact_id"];
        $oStatement = $oPdo->prepare("UPDATE ex_contacts SET contact_type_id = :contact_type_id, contact_value = :contact_value WHERE id = :id");
        $oStatement->execute(array(
            "contact_type_id" => $iContactTypeId,
            "contact_value" => $sContactValue,
            "id" => $iContactId
        ));

        $oStatement = $oPdo->prepare("UPDATE ex_subject_contacts SET is_primary = :is_primary, is_active = :is_active, note = :note WHERE id = :id");
        $oStatement->execute(array(
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null,
            "id" => $iSubjectContactId
        ));
        $oPdo->commit();

        $aResponse = nxInterGetUpdatedSubjectResponse($oPdo, (int)$aSubjectContact["subject_id"], $aBirthdaySettings, $blCanEdit);
        $aResponse["contact"] = array(
            "subject_contact_id" => $iSubjectContactId,
            "contact_id" => $iContactId,
            "contact_type_id" => $iContactTypeId,
            "contact_type" => (string)$aContactType["contact_type"],
            "contact_type_label" => (string)$aContactType["name"],
            "contact_value" => $sContactValue,
            "contact_display_value" => nxContactDisplayValue((string)$aContactType["contact_type"], $sContactValue),
            "note" => $sNote,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive
        );
        nxSendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected contact value already exists."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

$aRows = array();
$aContacts = array();
$aContactTypes = array();
$aNicknames = array();
$aAddresses = array();
$aGroups = array();
$aAllGroups = array();
$aNotes = array();
$aHiddenInactive = array();
$aBirthdayServedRows = array();
try {
    $oPdo->query("SET SESSION group_concat_max_len = 1048576");
    $aRows = nxFetchSubjectRows($oPdo);
    $aContacts = nxFetchSubjectContacts($oPdo);
    $aContactTypes = nxFetchContactTypes($oPdo, false);
    $aNicknames = nxFetchSubjectNicknames($oPdo);
    $aAddresses = nxFetchSubjectAddresses($oPdo);
    $aGroups = nxFetchSubjectGroups($oPdo);
    $aAllGroups = nxFetchGroups($oPdo);
    $aNotes = nxFetchSubjectNotes($oPdo);
    $aBirthdayServedRows = nxInterFetchBirthdayServedRows($oPdo);
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}

$aHiddenInactive = nxGetHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aBirthdaySettings);
nxApplySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aBirthdaySettings);

$aBirthdayRows = array();
foreach ($aRows as $aRow) {
    if ((string)$aRow["subject_type"] != "person") {
        continue;
    }
    $aBirthdayInfo = nxInterGetBirthdayInfo(isset($aBirthdayServedRows[(int)$aRow["subject_id"]]["inter_served_at"]) ? $aBirthdayServedRows[(int)$aRow["subject_id"]]["inter_served_at"] : "");
    if (!is_array($aBirthdayInfo)) {
        continue;
    }
    $aRow["days_to_birthday"] = $aBirthdayInfo["days_to_birthday"];
    $aRow["birthday_date"] = $aBirthdayInfo["birthday_date"];
    $aBirthdayRows[] = $aRow;
}
usort($aBirthdayRows, "nxBdCompareRows");

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
  <title><?php echo nxHtml(getExPageTitleText("Interactions", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo nxHtml(getExCsrfToken()); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body data-calendar-first-day="<?php echo nxHtml($iCalendarFirstDay); ?>" data-date-input-format="<?php echo nxHtml($sDateInputFormat); ?>" data-date-input-pattern="<?php echo nxHtml($sDateInputPattern); ?>">
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-interactions-table" value="<?php echo nxHtml(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
  </p>
  <div class="confirm-dialog index-settings-dialog" id="index-settings-dialog" hidden>
    <form class="confirm-dialog-box index-settings-form" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_inter_settings">
      <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
      <div class="confirm-dialog-header">
        <strong>Interaction Settings</strong>
        <button type="button" class="confirm-dialog-close js-index-settings-close" aria-label="Close">&times;</button>
      </div>
      <div class="index-settings-options">
        <label><input type="checkbox" name="show_inactive_subjects" value="1"<?php echo $aBirthdaySettings["show_inactive_subjects"] ? " checked" : ""; ?>> Show inactive subjects</label>
        <label><input type="checkbox" name="show_inactive_nicknames" value="1"<?php echo $aBirthdaySettings["show_inactive_nicknames"] ? " checked" : ""; ?>> Show inactive nicknames</label>
        <label><input type="checkbox" name="show_inactive_addresses" value="1"<?php echo $aBirthdaySettings["show_inactive_addresses"] ? " checked" : ""; ?>> Show inactive addresses</label>
        <label><input type="checkbox" name="show_inactive_contacts" value="1"<?php echo $aBirthdaySettings["show_inactive_contacts"] ? " checked" : ""; ?>> Show inactive contacts</label>
        <label><input type="checkbox" name="show_inactive_notes" value="1"<?php echo $aBirthdaySettings["show_inactive_notes"] ? " checked" : ""; ?>> Show inactive notes</label>
        <hr>
        <label><input type="checkbox" name="show_czechia_country" value="1" class="js-czechia-country-toggle"<?php echo $aBirthdaySettings["show_czechia_country"] ? " checked" : ""; ?>> Also show the country Czechia</label>
        <label><input type="checkbox" name="show_czechia_country_in_czech" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aBirthdaySettings["show_czechia_country_in_czech"] ? "1" : "0") . "\"" . ($aBirthdaySettings["show_czechia_country"] && $aBirthdaySettings["show_czechia_country_in_czech"] ? " checked" : "") . ($aBirthdaySettings["show_czechia_country"] ? "" : " disabled"); ?>> Show the country Czechia in Czech</label>
        <label><input type="checkbox" name="show_czechia_country_as_czech_republic" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aBirthdaySettings["show_czechia_country_as_czech_republic"] ? "1" : "0") . "\"" . ($aBirthdaySettings["show_czechia_country"] && $aBirthdaySettings["show_czechia_country_as_czech_republic"] ? " checked" : "") . ($aBirthdaySettings["show_czechia_country"] ? "" : " disabled"); ?>> Show &#268;esk&aacute; republika instead of &#268;esko</label>
      </div>
      <?php echo nxRenderExSettingsScopeNote(); ?>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-index-settings-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

echo "  <datalist id=\"nx-group-list\">\n";

foreach ($aAllGroups as $aGroup) {
    echo "    <option value=\"" . nxHtml($aGroup["name"]) . "\"></option>\n";
}

echo "  </datalist>\n";
echo "  <select id=\"nx-contact-type-list\" hidden>\n";

foreach ($aContactTypes as $aContactType) {
    echo "    <option value=\"" . nxHtml($aContactType["id"]) . "\" data-contact-type=\"" . nxHtml($aContactType["contact_type"]) . "\" data-contact-type-active=\"" . nxHtml($aContactType["is_active"]) . "\">" . nxHtml($aContactType["name"]) . "</option>\n";
}

echo "  </select>\n";

if (!$aBirthdayRows) {
    echo "  <p>No visible records found.</p>\n";
} else {
    echo nxRenderPageThrobber();

?>
  <table id="nx-interactions-table" class="nx-contacts-table table-filter-target" style="table-layout: auto;">
    <thead>
      <tr>
        <th class="nx-column-hidden">Type</th>
        <th class="nx-birthday-in-column">In</th>
        <th>Name</th>
        <th class="nx-column-hidden">First Name</th>
        <th class="nx-column-hidden">Last Name</th>
        <th class="nx-column-step-two">Birth Name</th>
        <th class="nx-column-hidden">Birth Number</th>
        <th style="overflow-wrap: normal; white-space: nowrap; word-break: normal;">Birth Date</th>
        <th class="nx-column-step-two" style="overflow-wrap: normal; white-space: nowrap; word-break: normal;">Death Date</th>
        <th class="nx-column-step-one">Nicknames</th>
        <th class="nx-column-step-one">Addresses</th>
        <th>Contacts</th>
        <th class="nx-column-step-three">Groups</th>
        <th class="nx-column-step-three">Notes</th>
      </tr>
    </thead>
    <tbody>
<?php

    foreach ($aBirthdayRows as $aRow) {
        echo nxInterRenderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blCanEdit, $aHiddenInactive, $aBirthdaySettings);
    }

    echo "    </tbody>\n";
echo "  </table>\n";
}

echo nxRenderFilterFocusButton();
echo nxRenderAdminScript($sBaseUrl);
?>
</body>
</html>
