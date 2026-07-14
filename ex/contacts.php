<?php

include "main.php";


requireExViewAccess($aAllowedIps);
$blCanEdit = isExFullAccessAllowed($aAllowedIps);

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
    requireExCsrfToken();
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_subject") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
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
        nxSendJsonAndExit(array("success" => true, "subject_id" => $iSubjectId, "reload_required" => true));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_shared_contact") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iContactId = isset($_POST["contact_id"]) ? (int)$_POST["contact_id"] : 0;
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sContactValue = nxGetPostedValue("contact_value");
    $aContactType = nxGetContactTypeById($iContactTypeId, $oPdo, true);
    if ($iContactId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact."), 400);
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
        $oStatement = $oPdo->prepare("SELECT id FROM ex_contacts WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iContactId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Contact was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_contacts SET contact_type_id = :contact_type_id, contact_value = :contact_value WHERE id = :id");
        $oStatement->execute(array(
            "contact_type_id" => $iContactTypeId,
            "contact_value" => $sContactValue,
            "id" => $iContactId
        ));
        $oPdo->commit();
        nxSendJsonAndExit(array("success" => true, "reload_required" => true));
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_shared_contact") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iContactId = isset($_POST["contact_id"]) ? (int)$_POST["contact_id"] : 0;
    if ($iContactId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact."), 400);
    }
    try {
        $oStatement = $oPdo->prepare("DELETE FROM ex_contacts WHERE id = :id");
        $oStatement->execute(array("id" => $iContactId));
        if (!$oStatement->rowCount()) {
            nxSendJsonAndExit(array("success" => false, "message" => "Contact was not found."), 404);
        }
        nxSendJsonAndExit(array("success" => true, "reload_required" => true));
    } catch (Exception $oException) {
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_subject_contact") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    if ($iSubjectContactId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact link."), 400);
    }
    try {
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_contacts WHERE id = :id");
        $oStatement->execute(array("id" => $iSubjectContactId));
        if (!$oStatement->rowCount()) {
            nxSendJsonAndExit(array("success" => false, "message" => "Contact link was not found."), 404);
        }
        nxSendJsonAndExit(array("success" => true, "reload_required" => true));
    } catch (Exception $oException) {
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_contact") {
    if (!$blCanEdit) {
        nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
    }
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
        nxSendJsonAndExit(array(
            "success" => true,
            "subject_id" => (int)$aSubjectContact["subject_id"],
            "reload_required" => true,
            "contact" => nxAddContactTimestampTooltip($oPdo, array(
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
            ))
        ));
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

$aContactTypes = array();
try {
    $aContactTypes = nxFetchContactTypes($oPdo, false);
    $aContactRows = nxContactsFetchRows($oPdo, $aContactSettings);
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}
$sViewportContent = nxGetLockedViewportContent();
$sRenderThrobberHtmlAttributes = nxGetRenderThrobberHtmlAttributes(count($aContactRows) > 0);
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr"<?php echo $sRenderThrobberHtmlAttributes; ?>>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="<?php echo nxHtml($sViewportContent); ?>">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo nxHtml(getExPageTitleText("Contacts", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo nxHtml(getExCsrfToken()); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-contacts-table" value="<?php echo nxHtml(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
  </p>
  <div class="confirm-dialog index-settings-dialog" id="index-settings-dialog" hidden>
    <form class="confirm-dialog-box index-settings-form" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_contacts_settings">
      <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
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

echo "  <select id=\"nx-contact-type-list\" hidden>\n";

foreach ($aContactTypes as $aContactType) {
    echo "    <option value=\"" . nxHtml($aContactType["id"]) . "\" data-contact-type=\"" . nxHtml($aContactType["contact_type"]) . "\" data-contact-type-active=\"" . nxHtml($aContactType["is_active"]) . "\">" . nxHtml($aContactType["name"]) . "</option>\n";
}

echo "  </select>\n";

if (count($aContactRows) > 0) {
    echo nxRenderPageThrobber();
}

?>
  <table id="nx-contacts-table" class="nx-contacts-table table-filter-target<?php echo nxGetCondensedTableClass(); ?>">
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
    $sContactFilterText = (string)$aContactRow["contact_type_name"] . " " . (string)$aContactRow["contact_display_value"];
    $sContactTimestampTooltipAttribute = nxRenderTimestampTooltipAttribute($aContactRow);
    $sContactActions = $blCanEdit ? "<span class=\"nx-list-item-actions\"><a href=\"#\" class=\"nx-item-action js-edit-shared-contact\" title=\"Edit shared contact\" aria-label=\"Edit shared contact\">" . $sEditEmoji . "</a><a href=\"#\" class=\"nx-item-action js-delete-shared-contact\" title=\"Delete shared contact\" aria-label=\"Delete shared contact\">" . $sDeleteEmoji . "</a></span>" : "";
    foreach ($aContactRow["subjects"] as $aFilterSubject) {
        $sContactFilterText .= " " . (string)$aFilterSubject["subject_name"];
    }
    if (!$aContactRow["subjects"]) {
        echo "      <tr>\n"
            . "        <td class=\"nx-contact-cell nx-contact-item\"" . nxContactsRenderContactDataAttributes($aContactRow) . ">"
            . "<span class=\"nx-contact-type\">" . nxHtml($aContactRow["contact_type_name"]) . "</span>: "
            . nxRenderContactValue($aContactRow["contact_type"], $aContactRow["contact_value"], true, true, $sContactTimestampTooltipAttribute)
            . $sContactActions
            . "</td>\n"
            . "        <td class=\"nx-contact-subject-cell nx-contact-subject-inactive\">" . nxHtmlValue("") . "</td>\n"
            . "      </tr>\n";
        continue;
    }
    foreach ($aContactRow["subjects"] as $aSubject) {
        $sSubjectActions = $blCanEdit ? "<span class=\"nx-list-item-actions\"><a href=\"#\" class=\"nx-item-action js-edit-subject-contact\" title=\"Edit subject contact\" aria-label=\"Edit subject contact\">" . $sEditEmoji . "</a><a href=\"#\" class=\"nx-item-action js-delete-subject-contact\" title=\"Delete subject contact\" aria-label=\"Delete subject contact\">" . $sDeleteEmoji . "</a></span>" : "";
        $sSubjectEditAction = $blCanEdit ? "<span class=\"nx-list-item-actions\"><a href=\"#\" class=\"nx-item-action js-edit-subject\" data-subject-id=\"" . nxHtml($aSubject["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a></span>" : "";
        $sSubjectPrimaryFlag = "<span class=\"nx-contact-flags\"><span class=\"nx-contact-primary\" title=\"Primary\">" . ((int)$aSubject["is_primary"] == 1 ? $sPrimaryEmoji : "") . "</span><span class=\"nx-contact-inactive-label\" title=\"Inactive\">" . ((int)$aSubject["contact_is_active"] == 1 ? "" : $sInactiveEmoji) . "</span></span>";
        echo "      <tr data-subject-id=\"" . nxHtml($aSubject["subject_id"]) . "\">\n";
        if ($blFirstSubject) {
            echo "        <td class=\"nx-contact-cell nx-contact-item\" rowspan=\"" . nxHtml($iSubjectCount) . "\"" . nxContactsRenderContactDataAttributes($aContactRow) . ">"
                . "<span class=\"nx-contact-type\">" . nxHtml($aContactRow["contact_type_name"]) . "</span>: "
                . nxRenderContactValue($aContactRow["contact_type"], $aContactRow["contact_value"], true, true, $sContactTimestampTooltipAttribute)
                . $sContactActions
                . "</td>\n";
            $blFirstSubject = false;
        }
        echo "        <td class=\"" . nxHtml(nxContactsSubjectCellClass($aSubject)) . " nx-list-item\"" . nxContactsRenderSubjectDataAttributes($aSubject) . "><span class=\"nx-column-hidden\">" . nxHtmlValue($sContactFilterText) . "</span><span class=\"nx-subject-item-value\"" . nxRenderTimestampTooltipAttribute($aSubject) . ">" . nxHtmlValue($aSubject["subject_name"]) . "</span>" . nxRenderCopyAction($aSubject["subject_name"]) . $sSubjectEditAction . "<span class=\"nx-contact-item nx-contact-subject-item\"" . nxContactsRenderSubjectDataAttributes($aSubject) . "><span class=\"nx-contact-note\">" . ($aSubject["note"] != "" ? " (" . nxHtml($aSubject["note"]) . ")" : "") . "</span>" . $sSubjectPrimaryFlag . $sSubjectActions . "</span></td>\n"
            . "      </tr>\n";
    }
}
if (!$aContactRows) {
    echo "      <tr>\n"
        . "        <td colspan=\"2\">No visible records found.</td>\n"
        . "      </tr>\n";
}

echo "    </tbody>\n"
    . "  </table>\n";

?>
  <div class="confirm-dialog" id="shared-contact-edit-dialog" hidden>
    <form class="confirm-dialog-box contact-edit-dialog" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
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
    echo "        <option value=\"" . nxHtml($aContactType["id"]) . "\">" . nxHtml($aContactType["name"]) . "</option>\n";
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
    <form class="confirm-dialog-box subject-edit-dialog" method="post" action="<?php echo nxHtml($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
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

echo nxRenderFilterFocusButton()
    . nxRenderAdminScript($sBaseUrl);

?>
</body>
</html>
