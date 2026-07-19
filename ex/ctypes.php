<?php

include "main.php";


$blCanEdit = isFullAccessAllowed($aAllowedIps);
requireViewAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrfToken();
}


if (!$blCanEdit && $_SERVER["REQUEST_METHOD"] == "POST") {
    sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_contact_type") {
    $sName = getPostedTrimmedValue("name");
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    if ($sName == "") {
        sendJsonAndExit(array("success" => false, "message" => "Contact type name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $sContactType = generateContactTypeKey($oPdo, $sName);
        $iOrder = (int)$oPdo->query("SELECT COALESCE(MAX(`order`), 0) + 10 FROM ex_contact_types")->fetchColumn();
        $oStatement = $oPdo->prepare("INSERT INTO ex_contact_types (contact_type, name, is_active, `order`) VALUES (:contact_type, :name, :is_active, :order)");
        $oStatement->execute(array(
            "contact_type" => $sContactType,
            "name" => $sName,
            "is_active" => $iIsActive,
            "order" => $iOrder
        ));
        $iContactTypeId = (int)$oPdo->lastInsertId();
        $oPdo->commit();
        $aContactTypes = fetchContactTypeAdminRows($oPdo, $iContactTypeId);
        $aContactType = count($aContactTypes) > 0 ? $aContactTypes[0] : null;
        sendJsonAndExit(array("success" => true, "contact_type_id" => $iContactTypeId, "row_html" => renderContactTypeAdminRow($aContactType)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected contact type name already exists."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_contact_type") {
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sName = getPostedTrimmedValue("name");
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] == "1" ? 1 : 0;
    if ($iContactTypeId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact type."), 400);
    }
    if ($sName == "") {
        sendJsonAndExit(array("success" => false, "message" => "Contact type name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_contact_types WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iContactTypeId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Contact type was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_contact_types SET name = :name, is_active = :is_active WHERE id = :id");
        $oStatement->execute(array(
            "name" => $sName,
            "is_active" => $iIsActive,
            "id" => $iContactTypeId
        ));
        $oPdo->commit();
        $aContactTypes = fetchContactTypeAdminRows($oPdo, $iContactTypeId);
        $aContactType = count($aContactTypes) > 0 ? $aContactTypes[0] : null;
        sendJsonAndExit(array("success" => true, "contact_type_id" => $iContactTypeId, "row_html" => renderContactTypeAdminRow($aContactType)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected contact type name already exists."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_contact_type") {
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    if ($iContactTypeId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact type."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_contact_types WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iContactTypeId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Contact type was not found."), 404);
        }
        $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM ex_contacts WHERE contact_type_id = :id");
        $oStatement->execute(array("id" => $iContactTypeId));
        if ((int)$oStatement->fetchColumn() > 0) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Merge this contact type before deleting it."), 409);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_contact_types WHERE id = :id");
        $oStatement->execute(array("id" => $iContactTypeId));
        normalizeContactTypeOrder($oPdo);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "contact_type_id" => $iContactTypeId, "contact_type_deleted" => true, "rows_html" => renderContactTypeAdminRows($oPdo, $blCanEdit)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "move_contact_type") {
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sDirection = isset($_POST["direction"]) ? (string)$_POST["direction"] : "";
    if ($iContactTypeId < 1 || ($sDirection != "up" && $sDirection != "down")) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid order change."), 400);
    }

    try {
        $oPdo->beginTransaction();
        moveContactTypeOrder($oPdo, $iContactTypeId, $sDirection);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "rows_html" => renderContactTypeAdminRows($oPdo, $blCanEdit)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "merge_contact_types") {
    $iTargetContactTypeId = isset($_POST["target_contact_type_id"]) ? (int)$_POST["target_contact_type_id"] : 0;
    $aSourceContactTypeValues = isset($_POST["source_contact_type_ids"]) && is_array($_POST["source_contact_type_ids"]) ? $_POST["source_contact_type_ids"] : array();
    $blDeleteSourceContactTypes = isset($_POST["delete_source_contact_types"]) && (int)$_POST["delete_source_contact_types"] == 1;
    $aSourceContactTypeIds = array();
    $aSourceContactTypeIdMap = array();
    foreach ($aSourceContactTypeValues as $mSourceContactTypeId) {
        $iSourceContactTypeId = (int)$mSourceContactTypeId;
        if ($iSourceContactTypeId > 0 && $iSourceContactTypeId !== $iTargetContactTypeId && !isset($aSourceContactTypeIdMap[$iSourceContactTypeId])) {
            $aSourceContactTypeIds[] = $iSourceContactTypeId;
            $aSourceContactTypeIdMap[$iSourceContactTypeId] = true;
        }
    }
    if ($iTargetContactTypeId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid target contact type."), 400);
    }
    if (!$aSourceContactTypeIds) {
        sendJsonAndExit(array("success" => false, "message" => "Select at least one source contact type."), 400);
    }

    try {
        $oPdo->beginTransaction();

        $aAllContactTypeIds = array_merge(array($iTargetContactTypeId), $aSourceContactTypeIds);
        $aContactTypePlaceholders = array();
        $aContactTypeParams = array();
        foreach ($aAllContactTypeIds as $iIndex => $iContactTypeId) {
            $sParam = "contact_type_id_" . $iIndex;
            $aContactTypePlaceholders[] = ":" . $sParam;
            $aContactTypeParams[$sParam] = $iContactTypeId;
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_contact_types WHERE id IN (" . implode(", ", $aContactTypePlaceholders) . ") FOR UPDATE");
        $oStatement->execute($aContactTypeParams);
        $aFoundContactTypeIds = array();
        while ($iFoundContactTypeId = $oStatement->fetchColumn()) {
            $aFoundContactTypeIds[(int)$iFoundContactTypeId] = true;
        }
        if (!isset($aFoundContactTypeIds[$iTargetContactTypeId])) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Target contact type was not found."), 404);
        }
        foreach ($aSourceContactTypeIds as $iSourceContactTypeId) {
            if (!isset($aFoundContactTypeIds[$iSourceContactTypeId])) {
                $oPdo->rollBack();
                sendJsonAndExit(array("success" => false, "message" => "Source contact type was not found."), 404);
            }
        }

        foreach ($aSourceContactTypeIds as $iSourceContactTypeId) {
            mergeContactTypeContacts($oPdo, $iTargetContactTypeId, $iSourceContactTypeId);
        }

        if ($blDeleteSourceContactTypes) {
            $aSourcePlaceholders = array();
            $aSourceParams = array();
            foreach ($aSourceContactTypeIds as $iIndex => $iSourceContactTypeId) {
                $sParam = "source_contact_type_id_" . $iIndex;
                $aSourcePlaceholders[] = ":" . $sParam;
                $aSourceParams[$sParam] = $iSourceContactTypeId;
            }
            $oStatement = $oPdo->prepare("DELETE FROM ex_contact_types WHERE id IN (" . implode(", ", $aSourcePlaceholders) . ")");
            $oStatement->execute($aSourceParams);
            normalizeContactTypeOrder($oPdo);
        }

        $oPdo->commit();
        sendJsonAndExit(array(
            "success" => true,
            "contact_types_merged" => true,
            "target_contact_type_id" => $iTargetContactTypeId,
            "source_contact_type_ids" => $aSourceContactTypeIds,
            "source_contact_types_deleted" => $blDeleteSourceContactTypes,
            "rows_html" => renderContactTypeAdminRows($oPdo, $blCanEdit)
        ));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


$aContactTypes = array();
try {
    $aContactTypes = fetchContactTypeAdminRows($oPdo);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
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
  <meta name="viewport" content="<?php echo html(getLockedViewportContent()); ?>">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText("Contact Types", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken()); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-contact-types-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

if ($blCanEdit) {
    echo "    <button type=\"button\" class=\"button-link js-add-contact-type\">New</button>\n";
}

?>
  </p>
  <table id="nx-contact-types-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Name</th>
        <th class="nx-admin-small-column">Contacts</th>
        <th class="nx-admin-small-column">Active</th>
        <th class="nx-admin-action-column">Order</th>
        <th class="nx-admin-action-column"></th>
        <th class="nx-admin-action-column"></th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aContactTypes as $aContactType) {
    echo renderContactTypeAdminRow($aContactType, $blCanEdit);
}

echo "    </tbody>\n"
    . "  </table>\n"
    . renderFilterFocusButton()
    . renderAdminScript($sBaseUrl);
?>
</body>
</html>
