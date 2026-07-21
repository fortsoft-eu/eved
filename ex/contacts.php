<?php

include "main.php";


requireViewAccess($aAllowedIps, "ex", "ex_csrf_token", true);
$blCanEdit = isFullAccessAllowed($aAllowedIps, "ex");


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aContactsSettingsDefaults = array(
    "show_inactive_contacts" => 0,
    "show_inactive_subjects" => 0
);
$aContactSettings = array();
if (!isset($_SESSION["ex_contacts_settings"]) || !is_array($_SESSION["ex_contacts_settings"])) {
    $_SESSION["ex_contacts_settings"] = array();
}
foreach ($aContactsSettingsDefaults as $sContactSettingName => $iContactSettingDefault) {
    if (isset($_SESSION["ex_contacts_settings"][$sContactSettingName])) {
        $aContactSettings[$sContactSettingName] = (int)$_SESSION["ex_contacts_settings"][$sContactSettingName] == 1 ? 1 : 0;
    } else {
        $aContactSettings[$sContactSettingName] = $iContactSettingDefault;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("ex_csrf_token", true);
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_contacts_settings") {
    foreach ($aContactsSettingsDefaults as $sContactSettingName => $iContactSettingDefault) {
        $aContactSettings[$sContactSettingName] = isset($_POST[$sContactSettingName]) && (string)$_POST[$sContactSettingName] == "1" ? 1 : 0;
    }
    $_SESSION["ex_contacts_settings"] = $aContactSettings;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
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
            $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_persons WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
            $blHasPersonRow = (bool)$oStatement->fetch(PDO::FETCH_ASSOC);
            if ($blHasPersonRow) {
                $oStatement = $oPdo->prepare("UPDATE ex_persons SET title_before = :title_before, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, title_after = :title_after, birth_name = :birth_name, birth_number = :birth_number, birth_date = :birth_date, death_date = :death_date WHERE subject_id = :subject_id");
                $aPersonValues["subject_id"] = $iSubjectId;
                $oStatement->execute($aPersonValues);
            } else {
                $oStatement = $oPdo->prepare("INSERT INTO ex_persons (subject_id, title_before, first_name, middle_name, last_name, title_after, birth_name, birth_number, birth_date, death_date) VALUES (:subject_id, :title_before, :first_name, :middle_name, :last_name, :title_after, :birth_name, :birth_number, :birth_date, :death_date)");
                $aPersonValues["subject_id"] = $iSubjectId;
                $oStatement->execute($aPersonValues);
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_shared_contact") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iContactId = isset($_POST["contact_id"]) ? (int)$_POST["contact_id"] : 0;
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sContactValue = getPostedValue("contact_value");
    $aContactType = getContactTypeById($iContactTypeId, $oPdo, true);
    if ($iContactId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact."), 400);
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
        $oStatement = $oPdo->prepare("SELECT id, contact_type_id AS current_contact_type_id, contact_value AS current_contact_value FROM ex_contacts WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iContactId));
        $aContact = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aContact) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Contact was not found."), 404);
        }
        $blContactTypeChanged = (int)$aContact["current_contact_type_id"] != $iContactTypeId;
        $blContactValueChanged = (string)$aContact["current_contact_value"] != $sContactValue;
        if ($blContactTypeChanged || $blContactValueChanged) {
            $oStatement = $oPdo->prepare("UPDATE ex_contacts SET contact_type_id = :contact_type_id, contact_value = :contact_value WHERE id = :id");
            $oStatement->execute(array(
                "contact_type_id" => $iContactTypeId,
                "contact_value" => $sContactValue,
                "id" => $iContactId
            ));
        }
        $oPdo->commit();
        sendJsonAndExit(array(
            "success" => true,
            "reload_required" => $blContactTypeChanged || $blContactValueChanged
        ));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected contact value already exists."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_shared_contact") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iContactId = isset($_POST["contact_id"]) ? (int)$_POST["contact_id"] : 0;
    if ($iContactId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact."), 400);
    }
    try {
        $oStatement = $oPdo->prepare("DELETE FROM ex_contacts WHERE id = :id");
        $oStatement->execute(array("id" => $iContactId));
        if (!$oStatement->rowCount()) {
            sendJsonAndExit(array("success" => false, "message" => "Contact was not found."), 404);
        }
        sendJsonAndExit(array("success" => true, "reload_required" => true));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject_contact") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    if ($iSubjectContactId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact link."), 400);
    }
    try {
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_contacts WHERE id = :id");
        $oStatement->execute(array("id" => $iSubjectContactId));
        if (!$oStatement->rowCount()) {
            sendJsonAndExit(array("success" => false, "message" => "Contact link was not found."), 404);
        }
        sendJsonAndExit(array("success" => true, "reload_required" => true));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_contact") {
    if (!$blCanEdit) {
        sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
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
        $blContactVisibilityChanged = empty($aContactSettings["show_inactive_contacts"]) && (int)$aUpdatedContact["current_is_active"] != $iIsActive;
        $blReloadRequired = !empty($aUpdatedContact["contact_identity_changed"]) || $blContactVisibilityChanged;
        $oStatement = $oPdo->prepare("UPDATE ex_subject_contacts SET is_primary = :is_primary, is_active = :is_active, note = :note WHERE id = :id");
        $oStatement->execute(array(
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote != "" ? $sNote : null,
            "id" => $iSubjectContactId
        ));
        $oPdo->commit();
        $aResponse = array(
            "success" => true,
            "subject_id" => (int)$aUpdatedContact["subject_id"],
            "reload_required" => $blReloadRequired
        );
        if (!$blReloadRequired) {
            $aUpdatedRows = contactsFetchRows($oPdo, $aContactSettings);
            foreach ($aUpdatedRows as $aUpdatedRow) {
                $sUpdatedContactFilterText = contactsFilterText($aUpdatedRow);
                foreach ($aUpdatedRow["subjects"] as $aUpdatedSubject) {
                    if ((int)$aUpdatedSubject["subject_contact_id"] == $iSubjectContactId) {
                        $aResponse["subject_cell_html"] = contactsRenderSubjectCell($aUpdatedSubject, $sUpdatedContactFilterText, $blCanEdit);
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
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected contact value already exists."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


$aContactTypes = array();
try {
    $aContactTypes = fetchContactTypes($oPdo, false);
    $aContactRows = contactsFetchRows($oPdo, $aContactSettings);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}


$sRenderThrobberHtmlAttributes = getRenderThrobberHtmlAttributes(count($aContactRows) > 0);
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
  <title><?php echo html(getPageTitleText("Contacts", $aAllowedIps)); ?></title>
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
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="contacts-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
  </p>
  <div class="confirm-dialog index-settings-dialog" id="index-settings-dialog" hidden>
    <form class="confirm-dialog-box index-settings-form" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_contacts_settings">
      <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken("ex_csrf_token")); ?>">
      <div class="confirm-dialog-header">
        <strong>Contact Settings</strong>
        <button type="button" class="confirm-dialog-close js-index-settings-close" aria-label="Close">&times;</button>
      </div>
      <div class="index-settings-options">
        <label><input type="checkbox" name="show_inactive_contacts" value="1"<?php echo $aContactSettings["show_inactive_contacts"] ? " checked" : ""; ?>> Show inactive contacts</label>
        <label><input type="checkbox" name="show_inactive_subjects" value="1"<?php echo $aContactSettings["show_inactive_subjects"] ? " checked" : ""; ?>> Show inactive subjects</label>
      </div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-index-settings-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

echo "  <select id=\"contact-type-list\" hidden>\n";
foreach ($aContactTypes as $aContactType) {
    echo "    <option value=\"" . html($aContactType["id"]) . "\" data-contact-type=\"" . html($aContactType["contact_type"]) . "\" data-contact-type-active=\"" . html($aContactType["is_active"]) . "\">" . html($aContactType["name"]) . "</option>\n";
}
echo "  </select>\n";
if (count($aContactRows) > 0) {
    echo renderPageThrobber();
}

?>
  <table id="contacts-table" class="contacts-table table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Contact</th>
        <th>Subject</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aContactRows as $aContactRow) {
    $iSubjectCount = max(1, count($aContactRow["subjects"]));
    $blFirstSubject = true;
    $sContactFilterText = contactsFilterText($aContactRow);
    $sContactTimestampTooltipText = timestampTooltipText($aContactRow);
    $sContactTimestampTooltipAttribute = $sContactTimestampTooltipText ? " title=\"" . str_replace("\n", "&#10;", html($sContactTimestampTooltipText)) . "\"" : "";
    $sContactActions = $blCanEdit ? "<span class=\"list-item-actions\"><a href=\"#\" class=\"item-action js-edit-shared-contact\" title=\"Edit shared contact\" aria-label=\"Edit shared contact\">" . $sEditEmoji . "</a><a href=\"#\" class=\"item-action js-delete-shared-contact\" title=\"Delete shared contact\" aria-label=\"Delete shared contact\">" . $sDeleteEmoji . "</a></span>" : "";
    if (!$aContactRow["subjects"]) {
        echo "      <tr>\n",
            "        <td class=\"contact-cell contact-item\"" . contactsRenderContactDataAttributes($aContactRow) . "><span class=\"contact-db-values\"",
            $sContactTimestampTooltipAttribute . "><span class=\"contact-type\">" . html($aContactRow["contact_type_name"]) . "</span>: " . renderContactValueText($aContactRow["contact_type"], $aContactRow["contact_value"]),
            "</span>" . renderContactValueActions($aContactRow["contact_type"], $aContactRow["contact_value"], true, true) . $sContactActions . "</td>\n",
            "        <td class=\"contact-subject-cell contact-subject-inactive\">" . htmlValue("") . "</td>\n",
            "      </tr>\n";
        continue;
    }
    foreach ($aContactRow["subjects"] as $aSubject) {
        echo "      <tr data-subject-id=\"" . html($aSubject["subject_id"]) . "\">\n";
        if ($blFirstSubject) {
            echo "        <td class=\"contact-cell contact-item\" rowspan=\"" . html($iSubjectCount) . "\"" . contactsRenderContactDataAttributes($aContactRow) . "><span class=\"contact-db-values\"",
                $sContactTimestampTooltipAttribute . "><span class=\"contact-type\">" . html($aContactRow["contact_type_name"]) . "</span>: " . renderContactValueText($aContactRow["contact_type"], $aContactRow["contact_value"]),
                "</span>" . renderContactValueActions($aContactRow["contact_type"], $aContactRow["contact_value"], true, true) . $sContactActions . "</td>\n";
            $blFirstSubject = false;
        }
        echo contactsRenderSubjectCell($aSubject, $sContactFilterText, $blCanEdit) . "      </tr>\n";
    }
}
if (!$aContactRows) {
    echo "      <tr>\n",
        "        <td colspan=\"2\">No visible records found.</td>\n",
        "      </tr>\n";
}
echo "    </tbody>\n",
    "  </table>\n";

?>
  <div class="confirm-dialog" id="shared-contact-edit-dialog" hidden>
    <form class="confirm-dialog-box contact-edit-dialog" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="contact_id" value="">
      <div class="confirm-dialog-header">
        <strong>Edit Shared Contact</strong>
        <button type="button" class="confirm-dialog-close js-shared-contact-edit-close" aria-label="Close">&times;</button>
      </div>
      <label for="shared-contact-type">Type</label>
<?php

echo "      <select id=\"shared-contact-type\" name=\"contact_type_id\">\n";
foreach ($aContactTypes as $aContactType) {
    if ((int)$aContactType["is_active"] != 1) {
        continue;
    }
    echo "        <option value=\"" . html($aContactType["id"]) . "\">" . html($aContactType["name"]) . "</option>\n";
}
echo "      </select>\n";

?>
      <label for="shared-contact-value">Value</label>
      <input type="text" id="shared-contact-value" name="contact_value">
      <div class="contact-edit-error" hidden></div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-shared-contact-edit-cancel">Cancel</button>
      </div>
    </form>
  </div>
  <div class="confirm-dialog" id="shared-contact-delete-dialog" hidden>
    <form class="confirm-dialog-box subject-edit-dialog" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="contact_id" value="">
      <div class="confirm-dialog-header">
        <strong>Confirm Deletion</strong>
        <button type="button" class="confirm-dialog-close js-shared-contact-delete-close" aria-label="Close">&times;</button>
      </div>
      <p class="confirm-dialog-message">Delete this shared contact from all subjects using it?</p>
      <div class="contact-edit-error" hidden></div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Delete</button>
        <button type="button" class="confirm-dialog-button js-shared-contact-delete-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

echo renderEmojiData();

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
