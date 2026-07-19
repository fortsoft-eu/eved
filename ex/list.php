<?php

include "main.php";


$blCanEdit = isFullAccessAllowed($aAllowedIps);
requireViewAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$aFullListSettingsDefaults = array(
    "show_inactive_subjects" => 1,
    "show_inactive_nicknames" => 1,
    "show_inactive_addresses" => 1,
    "show_inactive_contacts" => 1,
    "show_inactive_notes" => 1
);
$aFullListSettings = array();

if (!isset($_SESSION["ex_full_list_settings"]) || !is_array($_SESSION["ex_full_list_settings"])) {
    $_SESSION["ex_full_list_settings"] = array();
}
foreach ($aFullListSettingsDefaults as $sFullListSettingName => $iFullListSettingDefault) {
    if (isset($_SESSION["ex_full_list_settings"][$sFullListSettingName])) {
        $aFullListSettings[$sFullListSettingName] = (int)$_SESSION["ex_full_list_settings"][$sFullListSettingName] == 1 ? 1 : 0;
    } else {
        $aFullListSettings[$sFullListSettingName] = $iFullListSettingDefault;
    }
}
$aFullListSettings = applyCountrySettings($aFullListSettings);

$aFullListComplexFilterContactTypes = array();
try {
    $oStatement = $oPdo->query("SELECT id, contact_type, name FROM ex_contact_types ORDER BY `order` ASC, id ASC");
    $aFullListComplexFilterContactTypes = $oStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}
$aFullListComplexFilterFields = getFullListComplexFilterFields($aFullListComplexFilterContactTypes);
$aFullListComplexFilterOperators = getFullListComplexFilterOperators();
$aFullListComplexFilter = getDefaultFullListComplexFilter();
$aFullListComplexFilterDraft = getDefaultFullListComplexFilterDraft();

if (isset($_SESSION["ex_full_list_complex_filter"]) && is_array($_SESSION["ex_full_list_complex_filter"])) {
    $aFullListComplexFilter = normalizeFullListComplexFilter($_SESSION["ex_full_list_complex_filter"], $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
}
if (isset($_SESSION["ex_full_list_complex_filter_draft"]) && is_array($_SESSION["ex_full_list_complex_filter_draft"])) {
    $aFullListComplexFilterDraft = normalizeFullListComplexFilterDraft($_SESSION["ex_full_list_complex_filter_draft"], $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
} elseif (count($aFullListComplexFilter["conditions"]) > 0) {
    $aFullListComplexFilterDraft = normalizeFullListComplexFilterDraft($aFullListComplexFilter, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
}
$aFullListComplexFilterSql = buildFullListComplexFilterSql($aFullListComplexFilter, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrfToken();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_full_list_settings") {
    foreach ($aFullListSettingsDefaults as $sFullListSettingName => $iFullListSettingDefault) {
        $aFullListSettings[$sFullListSettingName] = isset($_POST[$sFullListSettingName]) && (string)$_POST[$sFullListSettingName] == "1" ? 1 : 0;
    }
    $aFullListSettings = saveCountrySettings($aFullListSettings, $_POST);
    $_SESSION["ex_full_list_settings"] = removeCountrySettings($aFullListSettings);
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_full_list_complex_filter") {
    $aFullListComplexFilterPayload = getFullListComplexFilterPostPayload();
    $aFullListComplexFilterDraft = normalizeFullListComplexFilterDraft($aFullListComplexFilterPayload, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $aFullListComplexFilter = normalizeFullListComplexFilter($aFullListComplexFilterPayload, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $_SESSION["ex_full_list_complex_filter"] = $aFullListComplexFilter;
    $_SESSION["ex_full_list_complex_filter_draft"] = $aFullListComplexFilterDraft;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_full_list_complex_filter_draft") {
    $aFullListComplexFilterDraft = normalizeFullListComplexFilterDraft(getFullListComplexFilterPostPayload(), $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $_SESSION["ex_full_list_complex_filter_draft"] = $aFullListComplexFilterDraft;
    session_write_close();
    sendJsonAndExit(array("success" => true));
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "reset_full_list_complex_filter") {
    $aFullListComplexFilter = getDefaultFullListComplexFilter();
    $_SESSION["ex_full_list_complex_filter"] = $aFullListComplexFilter;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}


if (!$blCanEdit && $_SERVER["REQUEST_METHOD"] == "POST") {
    sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "get_subject") {
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
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "get_subject_portal_user") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    try {
        $aSubject = fetchSubjectPortalEditorData($oPdo, $iSubjectId);
        if (!$aSubject) {
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        sendJsonAndExit(array("success" => true, "subject" => $aSubject));
    } catch (Exception $oException) {
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject_portal_user") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $aPermissionKeys = isset($_POST["permissions"]) && is_array($_POST["permissions"]) ? $_POST["permissions"] : array();
    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
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
        $aPayload = array(
            "portal_user_enabled" => isset($_POST["portal_user_enabled"]) && (string)$_POST["portal_user_enabled"] == "1" ? "1" : "0",
            "portal_user_name" => getPostedTrimmedValue("portal_user_name"),
            "portal_password" => getPostedValue("portal_password"),
            "portal_user_active" => isset($_POST["portal_user_active"]) && (string)$_POST["portal_user_active"] == "1" ? "1" : "0",
            "portal_permission_keys" => $aPermissionKeys
        );
        saveSubjectPortalAccess($oPdo, $iSubjectId, (string)$aSubjectRow["subject_type"], $aPayload);
        $oPdo->commit();
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected user name already exists."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject") {
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
    if ($sBirthNumber === false) {
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

        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_subject") {
    $sPayload = getPostedValue("subject_payload");
    $aPayload = $sPayload != "" ? json_decode($sPayload, true) : null;
    if (!is_array($aPayload)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject data."), 400);
    }

    $sSubjectType = payloadValue($aPayload, "subject_type");
    $sBirthDate = payloadValue($aPayload, "birth_date");
    $sDeathDate = payloadValue($aPayload, "death_date");
    $sBirthNumber = normalizeBirthNumber(payloadValue($aPayload, "birth_number"));
    if (!in_array($sSubjectType, getSubjectTypes(), true)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject type."), 400);
    }
    if ($sBirthDate != "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sBirthDate)) {
        sendJsonAndExit(array("success" => false, "message" => "Birth date must use YYYY-MM-DD."), 400);
    }
    if ($sDeathDate != "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDeathDate)) {
        sendJsonAndExit(array("success" => false, "message" => "Death date must use YYYY-MM-DD."), 400);
    }
    if ($sBirthNumber === false) {
        sendJsonAndExit(array("success" => false, "message" => "Birth number must contain 9 or 10 digits."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("INSERT INTO ex_subjects (subject_type, is_active) VALUES (:subject_type, :is_active)");
        $oStatement->execute(array(
            "subject_type" => $sSubjectType,
            "is_active" => payloadFlag($aPayload, "is_active")
        ));
        $iSubjectId = (int)$oPdo->lastInsertId();

        $sSubjectName = payloadValue($aPayload, "subject_name_value");
        if ($sSubjectType != "person" && $sSubjectName != "") {
            $oStatement = $oPdo->prepare("INSERT INTO ex_subject_names (subject_id, name) VALUES (:subject_id, :name)");
            $oStatement->execute(array("subject_id" => $iSubjectId, "name" => $sSubjectName));
        }

        if ($sSubjectType == "person") {
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
            if ($blHasPersonValues) {
                $oStatement = $oPdo->prepare("INSERT INTO ex_persons (subject_id, title_before, first_name, middle_name, last_name, title_after, birth_name, birth_number, birth_date, death_date) VALUES (:subject_id, :title_before, :first_name, :middle_name, :last_name, :title_after, :birth_name, :birth_number, :birth_date, :death_date)");
                $aPersonValues["subject_id"] = $iSubjectId;
                $oStatement->execute($aPersonValues);
            }
        }

        $oPdo->commit();

        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject_nickname") {
    $iNicknameId = isset($_POST["nickname_id"]) ? (int)$_POST["nickname_id"] : 0;
    $sNickname = getPostedTrimmedValue("nickname");
    $sContext = getPostedTrimmedValue("context");
    $sNote = getPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iNicknameId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid nickname."), 400);
    }
    if ($sNickname == "") {
        sendJsonAndExit(array("success" => false, "message" => "Nickname is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_nicknames WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNicknameId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Nickname was not found."), 404);
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
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_subject_nickname") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sNickname = getPostedTrimmedValue("nickname");
    $sContext = getPostedTrimmedValue("context");
    $sNote = getPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sNickname == "") {
        sendJsonAndExit(array("success" => false, "message" => "Nickname is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
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
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject_address") {
    $iAddressId = isset($_POST["address_id"]) ? (int)$_POST["address_id"] : 0;
    $sAddressType = getPostedTrimmedValue("address_type");
    $sOrganizationName = getPostedTrimmedValue("organization_name");
    $sDepartmentName = getPostedTrimmedValue("department_name");
    $sCareOf = getPostedTrimmedValue("care_of");
    $sStreetName = getPostedTrimmedValue("street_name");
    $sHouseNumber = getPostedTrimmedValue("house_number");
    $sEvidenceNumber = getPostedTrimmedValue("evidence_number");
    $sOrientationNumber = getPostedTrimmedValue("orientation_number");
    $sOrientationSuffix = getPostedTrimmedValue("orientation_suffix");
    $sAddressLine2 = getPostedTrimmedValue("address_line2");
    $sCity = getPostedTrimmedValue("city");
    $sCityPart = getPostedTrimmedValue("city_part");
    $sPostalCode = getPostedTrimmedValue("postal_code");
    $sRegion = getPostedTrimmedValue("region");
    $sCountry = getPostedTrimmedValue("country");
    $sNote = getPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    if ($sCountry != "") {
        $sCountry = strtoupper($sCountry);
    }

    if ($iAddressId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    if ($sAddressType == "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, getAddressTypes(), true)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    if ($sCountry == "") {
        sendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry != "" && !in_array($sCountry, getCountryCodes(), true)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = normalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName == "" && $sDepartmentName == "" && $sCareOf == "" && $sStreetName == "" && $sHouseNumber == "" && $sEvidenceNumber == "" && $sOrientationNumber == "" && $sOrientationSuffix == "" && $sAddressLine2 == "" && $sCity == "" && $sCityPart == "" && $sPostalCode == "" && $sRegion == "" && $sCountry == "" && $sNote == "") {
        sendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_addresses WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iAddressId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
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
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_subject_address") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sAddressType = getPostedTrimmedValue("address_type");
    $sOrganizationName = getPostedTrimmedValue("organization_name");
    $sDepartmentName = getPostedTrimmedValue("department_name");
    $sCareOf = getPostedTrimmedValue("care_of");
    $sStreetName = getPostedTrimmedValue("street_name");
    $sHouseNumber = getPostedTrimmedValue("house_number");
    $sEvidenceNumber = getPostedTrimmedValue("evidence_number");
    $sOrientationNumber = getPostedTrimmedValue("orientation_number");
    $sOrientationSuffix = getPostedTrimmedValue("orientation_suffix");
    $sAddressLine2 = getPostedTrimmedValue("address_line2");
    $sCity = getPostedTrimmedValue("city");
    $sCityPart = getPostedTrimmedValue("city_part");
    $sPostalCode = getPostedTrimmedValue("postal_code");
    $sRegion = getPostedTrimmedValue("region");
    $sCountry = getPostedTrimmedValue("country");
    $sNote = getPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    if ($sCountry != "") {
        $sCountry = strtoupper($sCountry);
    }

    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sAddressType == "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, getAddressTypes(), true)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    if ($sCountry == "") {
        sendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry != "" && !in_array($sCountry, getCountryCodes(), true)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = normalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName == "" && $sDepartmentName == "" && $sCareOf == "" && $sStreetName == "" && $sHouseNumber == "" && $sEvidenceNumber == "" && $sOrientationNumber == "" && $sOrientationSuffix == "" && $sAddressLine2 == "" && $sCity == "" && $sCityPart == "" && $sPostalCode == "" && $sRegion == "" && $sCountry == "" && $sNote == "") {
        sendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
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
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    $sGroupName = getPostedTrimmedValue("name");

    if ($iSubjectId < 1 || $iGroupId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid group link."), 400);
    }
    if ($sGroupName == "") {
        sendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Group was not found."), 404);
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE name = :name AND id <> :id");
        $oStatement->execute(array("name" => $sGroupName, "id" => $iGroupId));
        if ($oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "The selected group name already exists."), 409);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_groups SET name = :name WHERE id = :id");
        $oStatement->execute(array("name" => $sGroupName, "id" => $iGroupId));
        $oPdo->commit();
        $aResponse = getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql);
        $aResponse["group"] = fetchGroupAjaxData($oPdo, $iGroupId, $sGroupName);
        sendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sGroupName = getPostedTrimmedValue("name");

    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sGroupName == "") {
        sendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
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
            sendJsonAndExit(array("success" => false, "message" => "Subject is already assigned to this group."), 409);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_groups (subject_id, group_id) VALUES (:subject_id, :group_id)");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        $oPdo->commit();
        $aResponse = getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql);
        $aResponse["group"] = fetchGroupAjaxData($oPdo, $iGroupId, $sGroupName);
        sendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected group already exists or is already assigned."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject_note") {
    $iNoteId = isset($_POST["note_id"]) ? (int)$_POST["note_id"] : 0;
    $sNoteText = getPostedTrimmedValue("note_text");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iNoteId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid note."), 400);
    }
    if ($sNoteText == "") {
        sendJsonAndExit(array("success" => false, "message" => "Note text is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_notes WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNoteId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Note was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_subject_notes SET note_text = :note_text, is_primary = :is_primary, is_active = :is_active WHERE id = :id");
        $oStatement->execute(array("note_text" => $sNoteText, "is_primary" => $iIsPrimary, "is_active" => $iIsActive, "id" => $iNoteId));
        $oPdo->commit();
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_subject_note") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sNoteText = getPostedTrimmedValue("note_text");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;

    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sNoteText == "") {
        sendJsonAndExit(array("success" => false, "message" => "Note text is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_notes (subject_id, note_text, is_primary, is_active) VALUES (:subject_id, :note_text, :is_primary, :is_active)");
        $oStatement->execute(array("subject_id" => $iSubjectId, "note_text" => $sNoteText, "is_primary" => $iIsPrimary, "is_active" => $iIsActive));
        $oPdo->commit();
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subjects WHERE id = :subject_id");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject_contact") {
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    if ($iSubjectContactId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact link."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_contacts WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iSubjectContactId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Contact link was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_contacts WHERE id = :id");
        $oStatement->execute(array("id" => $iSubjectContactId));
        $oPdo->commit();
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject_nickname") {
    $iNicknameId = isset($_POST["nickname_id"]) ? (int)$_POST["nickname_id"] : 0;
    if ($iNicknameId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid nickname."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_nicknames WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNicknameId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Nickname was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_nicknames WHERE id = :id");
        $oStatement->execute(array("id" => $iNicknameId));
        $oPdo->commit();
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject_address") {
    $iAddressId = isset($_POST["address_id"]) ? (int)$_POST["address_id"] : 0;
    if ($iAddressId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_addresses WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iAddressId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_addresses WHERE id = :id");
        $oStatement->execute(array("id" => $iAddressId));
        $oPdo->commit();
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    if ($iSubjectId < 1 || $iGroupId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid group link."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        $oPdo->commit();
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject_note") {
    $iNoteId = isset($_POST["note_id"]) ? (int)$_POST["note_id"] : 0;
    if ($iNoteId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid note."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_notes WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNoteId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Note was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_notes WHERE id = :id");
        $oStatement->execute(array("id" => $iNoteId));
        $oPdo->commit();
        sendJsonAndExit(getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_contact") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sContactValue = getPostedValue("contact_value");
    $sNote = getPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    $aContactType = getContactTypeById($iContactTypeId, $oPdo, true);

    if ($iSubjectId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if (!$aContactType) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact type."), 400);
    }
    $sContactValue = normalizeContactInputForStorage((string)$aContactType["contact_type"], $sContactValue);
    if ($sContactValue === false) {
        sendJsonAndExit(array("success" => false, "message" => contactInputErrorMessage((string)$aContactType["contact_type"])), 400);
    }
    if ($sContactValue == "") {
        sendJsonAndExit(array("success" => false, "message" => "Contact value is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
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
            sendJsonAndExit(array("success" => false, "message" => "This contact is already assigned to the subject."), 409);
        }

        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_contacts (subject_id, contact_id, is_primary, is_active, note) VALUES (:subject_id, :contact_id, :is_primary, :is_active, :note)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "contact_id" => $iContactId,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null
        ));
        $oPdo->commit();

        $aResponse = getUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings, $aFullListComplexFilterSql);
        sendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected contact value already exists or is already assigned."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_contact") {
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sContactValue = getPostedValue("contact_value");
    $sNote = getPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] == "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    $aContactType = getContactTypeById($iContactTypeId, $oPdo, true);

    if ($iSubjectContactId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact link."), 400);
    }
    if (!$aContactType) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact type."), 400);
    }
    $sContactValue = normalizeContactInputForStorage((string)$aContactType["contact_type"], $sContactValue);
    if ($sContactValue === false) {
        sendJsonAndExit(array("success" => false, "message" => contactInputErrorMessage((string)$aContactType["contact_type"])), 400);
    }
    if ($sContactValue == "") {
        sendJsonAndExit(array("success" => false, "message" => "Contact value is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $aUpdatedContact = updateSubjectContactTarget($oPdo, $iSubjectContactId, $iContactTypeId, $sContactValue, $aContactType);
        if (!$aUpdatedContact) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Contact link was not found."), 404);
        }

        $oStatement = $oPdo->prepare("UPDATE ex_subject_contacts SET is_primary = :is_primary, is_active = :is_active, note = :note WHERE id = :id");
        $oStatement->execute(array(
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null,
            "id" => $iSubjectContactId
        ));
        $oPdo->commit();

        $aResponse = getUpdatedSubjectResponse($oPdo, (int)$aUpdatedContact["subject_id"], $aFullListSettings, $aFullListComplexFilterSql);
        sendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected contact value already exists."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
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
try {
    $oPdo->query("SET SESSION group_concat_max_len = 1048576");
    $aRows = fetchSubjectRows($oPdo, 0, $aFullListComplexFilterSql);
    $aContacts = fetchSubjectContacts($oPdo);
    $aContactTypes = fetchContactTypes($oPdo, false);
    $aNicknames = fetchSubjectNicknames($oPdo);
    $aAddresses = fetchSubjectAddresses($oPdo);
    $aGroups = fetchSubjectGroups($oPdo);
    $aAllGroups = fetchGroups($oPdo);
    $aNotes = fetchSubjectNotes($oPdo);
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}

$aHiddenInactive = getHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aFullListSettings);
applySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aFullListSettings);

$blFullListComplexFilterActive = count($aFullListComplexFilter["conditions"]) > 0;
$aFullListComplexFilterRows = $aFullListComplexFilterDraft["conditions"];
while (count($aFullListComplexFilterRows) < 1) {
    $aFullListComplexFilterRows[] = array(
        "field" => "subject_name",
        "operator" => "contains",
        "value" => ""
    );
}
$aFullListComplexFilterGroups = array();
foreach ($aAllGroups as $aGroup) {
    $aFullListComplexFilterGroups[] = (string)$aGroup["name"];
}
$aFullListComplexFilterSubjectTypes = array();
foreach (getSubjectTypes() as $sSubjectType) {
    $aFullListComplexFilterSubjectTypes[] = array(
        "value" => $sSubjectType,
        "label" => ucfirst($sSubjectType)
    );
}
$aFullListComplexFilterAddressTypes = array();
foreach (getAddressTypes() as $sAddressType) {
    $aFullListComplexFilterAddressTypes[] = array(
        "value" => $sAddressType,
        "label" => addressTypeLabel($sAddressType)
    );
}

$sViewportContent = getLockedViewportContent();
$sRenderThrobberHtmlAttributes = getRenderThrobberHtmlAttributes(count($aRows) > 0);

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr"<?php echo $sRenderThrobberHtmlAttributes; ?>>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="<?php echo html($sViewportContent); ?>">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText("Subjects", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken()); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body data-calendar-first-day="<?php echo html($iCalendarFirstDay); ?>" data-date-input-format="<?php echo html($sDateInputFormat); ?>" data-date-input-pattern="<?php echo html($sDateInputPattern); ?>">
  <p class="admin-controls">
<?php renderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-subjects-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-complex-filter-open<?php echo $blFullListComplexFilterActive ? " complex-filter-active" : ""; ?>" aria-pressed="<?php echo $blFullListComplexFilterActive ? "true" : "false"; ?>">Complex</button>
    <button type="submit" class="button-link js-complex-filter-page-reset<?php echo $blFullListComplexFilterActive ? " complex-filter-active" : ""; ?>" form="complex-filter-reset-form" title="Reset complex filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
<?php

if ($blCanEdit) {
    echo "    <button type=\"button\" class=\"button-link js-add-subject\">New</button>\n";
}

?>
  </p>
  <form id="complex-filter-reset-form" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="action" value="reset_full_list_complex_filter">
    <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken()); ?>">
  </form>
  <?php echo renderCountryDatalist(); ?>
  <div class="confirm-dialog complex-filter-dialog" id="complex-filter-dialog" hidden>
    <form class="confirm-dialog-box complex-filter-form" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_full_list_complex_filter">
      <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken()); ?>">
      <div class="confirm-dialog-header">
        <strong>Complex Filter</strong>
        <button type="button" class="confirm-dialog-close js-complex-filter-close" aria-label="Close">&times;</button>
      </div>
      <div class="complex-filter-options">
        <div class="complex-filter-match">
          <label><input type="radio" name="complex_filter_match" value="all"<?php echo $aFullListComplexFilterDraft["match"] == "all" ? " checked" : ""; ?>> Match all conditions</label>
          <label><input type="radio" name="complex_filter_match" value="any"<?php echo $aFullListComplexFilterDraft["match"] == "any" ? " checked" : ""; ?>> Match any condition</label>
        </div>
        <div class="complex-filter-rows js-complex-filter-rows" data-empty-row-count="1" data-group-options="<?php echo html(json_encode($aFullListComplexFilterGroups)); ?>" data-subject-type-options="<?php echo html(json_encode($aFullListComplexFilterSubjectTypes)); ?>" data-address-type-options="<?php echo html(json_encode($aFullListComplexFilterAddressTypes)); ?>">
<?php

foreach ($aFullListComplexFilterRows as $aCondition) {
    $sComplexField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "subject_name";
    if ($sComplexField != "" && !isset($aFullListComplexFilterFields[$sComplexField])) {
        $sComplexField = "subject_name";
    }
    $sComplexOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "contains";
    if ($sComplexOperator != "" && !isset($aFullListComplexFilterOperators[$sComplexOperator])) {
        $sComplexOperator = $sComplexField != "" ? getFullListComplexFilterDefaultOperator($aFullListComplexFilterFields[$sComplexField]) : "contains";
    }
    $sComplexValueType = $sComplexField != "" && isset($aFullListComplexFilterFields[$sComplexField]["value_type"]) ? (string)$aFullListComplexFilterFields[$sComplexField]["value_type"] : "text";
    if ($sComplexValueType == "boolean") {
        $sComplexOperator = "equals";
    }
    if ($sComplexField != "" && !isFullListComplexFilterOperatorAllowed($aFullListComplexFilterFields[$sComplexField], $sComplexOperator)) {
        $sComplexOperator = getFullListComplexFilterDefaultOperator($aFullListComplexFilterFields[$sComplexField]);
    }
    $sComplexValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
    $blComplexNeedsValue = $sComplexOperator == "" || !empty($aFullListComplexFilterOperators[$sComplexOperator]["needs_value"]);
    $blComplexOperatorHidden = $sComplexValueType == "boolean";
    echo "          <div class=\"complex-filter-row js-complex-filter-row\">\n"
        . "            <select name=\"complex_filter_field[]\" class=\"js-complex-filter-field\">" . renderFullListComplexFilterFieldOptions($aFullListComplexFilterFields, $sComplexField) . "</select>\n"
        . "            <select name=\"complex_filter_operator[]\" class=\"js-complex-filter-operator\"" . ($blComplexOperatorHidden ? " disabled aria-hidden=\"true\" tabindex=\"-1\"" : "") . ">" . renderFullListComplexFilterOperatorOptions($aFullListComplexFilterOperators, $sComplexOperator, $sComplexField != "" ? $aFullListComplexFilterFields[$sComplexField] : null) . "</select>\n"
        . "            <input type=\"text\" name=\"complex_filter_value[]\" class=\"js-complex-filter-value\" value=\"" . html($sComplexValue) . "\" autocomplete=\"off\"" . ($blComplexNeedsValue ? "" : " disabled") . ">\n"
        . "            <button type=\"button\" class=\"complex-filter-remove js-complex-filter-remove\" title=\"Remove condition\" aria-label=\"Remove condition\">&times;</button>\n"
        . "          </div>\n";
}

?>
        </div>
        <button type="button" class="button-link complex-filter-add js-complex-filter-add">Add condition</button>
      </div>
      <div class="confirm-dialog-actions complex-filter-actions">
        <button type="button" class="confirm-dialog-button js-complex-filter-modal-reset">Reset</button>
        <button type="submit" class="confirm-dialog-button">Apply</button>
        <button type="button" class="confirm-dialog-button js-complex-filter-cancel">Close</button>
      </div>
    </form>
  </div>
  <div class="confirm-dialog index-settings-dialog" id="index-settings-dialog" hidden>
    <form class="confirm-dialog-box index-settings-form" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_full_list_settings">
      <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken()); ?>">
      <div class="confirm-dialog-header">
        <strong>Full List Settings</strong>
        <button type="button" class="confirm-dialog-close js-index-settings-close" aria-label="Close">&times;</button>
      </div>
      <div class="index-settings-options">
        <label><input type="checkbox" name="show_inactive_subjects" value="1"<?php echo $aFullListSettings["show_inactive_subjects"] ? " checked" : ""; ?>> Show inactive subjects</label>
        <label><input type="checkbox" name="show_inactive_nicknames" value="1"<?php echo $aFullListSettings["show_inactive_nicknames"] ? " checked" : ""; ?>> Show inactive nicknames</label>
        <label><input type="checkbox" name="show_inactive_addresses" value="1"<?php echo $aFullListSettings["show_inactive_addresses"] ? " checked" : ""; ?>> Show inactive addresses</label>
        <label><input type="checkbox" name="show_inactive_contacts" value="1"<?php echo $aFullListSettings["show_inactive_contacts"] ? " checked" : ""; ?>> Show inactive contacts</label>
        <label><input type="checkbox" name="show_inactive_notes" value="1"<?php echo $aFullListSettings["show_inactive_notes"] ? " checked" : ""; ?>> Show inactive notes</label>
        <hr>
        <label><input type="checkbox" name="show_czechia_country" value="1" class="js-czechia-country-toggle"<?php echo $aFullListSettings["show_czechia_country"] ? " checked" : ""; ?>> Also show the country Czechia</label>
        <label><input type="checkbox" name="show_czechia_country_in_czech" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aFullListSettings["show_czechia_country_in_czech"] ? "1" : "0") . "\"" . ($aFullListSettings["show_czechia_country"] && $aFullListSettings["show_czechia_country_in_czech"] ? " checked" : "") . ($aFullListSettings["show_czechia_country"] ? "" : " disabled"); ?>> Show the country Czechia in Czech</label>
        <label><input type="checkbox" name="show_czechia_country_as_czech_republic" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aFullListSettings["show_czechia_country_as_czech_republic"] ? "1" : "0") . "\"" . ($aFullListSettings["show_czechia_country"] && $aFullListSettings["show_czechia_country_as_czech_republic"] ? " checked" : "") . ($aFullListSettings["show_czechia_country"] ? "" : " disabled"); ?>> Show Česká republika instead of Česko</label>
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

echo "  <datalist id=\"nx-group-list\">\n";

foreach ($aAllGroups as $aGroup) {
    echo "    <option value=\"" . html($aGroup["name"]) . "\"></option>\n";
}

echo "  </datalist>\n"
    . "  <select id=\"nx-contact-type-list\" hidden>\n";

foreach ($aContactTypes as $aContactType) {
    echo "    <option value=\"" . html($aContactType["id"]) . "\" data-contact-type=\"" . html($aContactType["contact_type"]) . "\" data-contact-type-active=\"" . html($aContactType["is_active"]) . "\">" . html($aContactType["name"]) . "</option>\n";
}

echo "  </select>\n";

if (!$aRows) {
    echo "  <p>" . ($blFullListComplexFilterActive ? "<strong>Complex Filter: </strong>" : "") . "No visible records found.</p>\n";
} else {
    echo renderPageThrobber();

?>
  <table id="nx-subjects-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th class="nx-subject-type-column">Type</th>
        <th>Name</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Birth Name</th>
        <th>Birth Number</th>
        <th>Birth Date</th>
        <th>Death Date</th>
        <th>Nicknames</th>
        <th>Addresses</th>
        <th>Contacts</th>
        <th>Groups</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
<?php

    foreach ($aRows as $aRow) {
        echo renderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blCanEdit, $aHiddenInactive, $aFullListSettings);
    }

    echo "    </tbody>\n"
        . "  </table>\n";
}

echo renderFilterFocusButton()
    . renderAdminScript($sBaseUrl);

?>
</body>
</html>
