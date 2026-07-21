<?php

include "main.php";


$blCanEdit = isFullAccessAllowed($aAllowedIps, "ex");
requireViewAccess($aAllowedIps, "ex", "ex_csrf_token", true);


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("ex_csrf_token", true);
}
if (!$blCanEdit && $_SERVER["REQUEST_METHOD"] == "POST") {
    sendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_group") {
    $sName = getPostedTrimmedValue("name");
    $aPermissionKeys = isset($_POST["permissions"]) && is_array($_POST["permissions"]) ? $_POST["permissions"] : array();
    if ($sName == "") {
        sendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $iOrder = (int)$oPdo->query("SELECT COALESCE(MAX(`order`), 0) + 10 FROM ex_groups")->fetchColumn();
        $oStatement = $oPdo->prepare("INSERT INTO ex_groups (name, `order`) VALUES (:name, :order)");
        $oStatement->execute(array("name" => $sName, "order" => $iOrder));
        $iGroupId = (int)$oPdo->lastInsertId();
        saveGroupPortalPermissions($oPdo, $iGroupId, $aPermissionKeys);
        $oPdo->commit();
        $aGroups = fetchGroupAdminRows($oPdo, $iGroupId);
        $aGroup = count($aGroups) > 0 ? $aGroups[0] : null;
        sendJsonAndExit(array("success" => true, "group_id" => $iGroupId, "row_html" => renderGroupAdminRow($aGroup)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected group name already exists."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_group") {
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    $sName = getPostedTrimmedValue("name");
    $aPermissionKeys = isset($_POST["permissions"]) && is_array($_POST["permissions"]) ? $_POST["permissions"] : array();
    if ($iGroupId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid group."), 400);
    }
    if ($sName == "") {
        sendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Group was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_groups SET name = :name WHERE id = :id");
        $oStatement->execute(array("name" => $sName, "id" => $iGroupId));
        saveGroupPortalPermissions($oPdo, $iGroupId, $aPermissionKeys);
        $oPdo->commit();
        $aGroups = fetchGroupAdminRows($oPdo, $iGroupId);
        $aGroup = count($aGroups) > 0 ? $aGroups[0] : null;
        sendJsonAndExit(array("success" => true, "group_id" => $iGroupId, "row_html" => renderGroupAdminRow($aGroup)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            sendJsonAndExit(array("success" => false, "message" => "The selected group name already exists."), 409);
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_group") {
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    if ($iGroupId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid group."), 400);
    }
    if ($iGroupId === 1) {
        sendJsonAndExit(array("success" => false, "message" => "The portal access group cannot be deleted."), 409);
    }
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Group was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_groups WHERE group_id = :id");
        $oStatement->execute(array("id" => $iGroupId));
        $oStatement = $oPdo->prepare("DELETE FROM ex_group_permissions WHERE group_id = :id");
        $oStatement->execute(array("id" => $iGroupId));
        $oStatement = $oPdo->prepare("DELETE FROM ex_groups WHERE id = :id");
        $oStatement->execute(array("id" => $iGroupId));
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "group_id" => $iGroupId, "group_deleted" => true));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "move_group") {
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    $sDirection = isset($_POST["direction"]) ? (string)$_POST["direction"] : "";
    if ($iGroupId < 1 || ($sDirection != "up" && $sDirection != "down")) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid order change."), 400);
    }
    try {
        $oPdo->beginTransaction();
        moveGroupOrder($oPdo, $iGroupId, $sDirection);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "rows_html" => renderGroupAdminRows($oPdo, $blCanEdit)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "merge_groups") {
    $iTargetGroupId = isset($_POST["target_group_id"]) ? (int)$_POST["target_group_id"] : 0;
    $aSourceGroupValues = isset($_POST["source_group_ids"]) && is_array($_POST["source_group_ids"]) ? $_POST["source_group_ids"] : array();
    $blDeleteSourceGroups = isset($_POST["delete_source_groups"]) && (int)$_POST["delete_source_groups"] == 1;
    $aSourceGroupIds = array();
    $aSourceGroupIdMap = array();
    foreach ($aSourceGroupValues as $mSourceGroupId) {
        $iSourceGroupId = (int)$mSourceGroupId;
        if ($iSourceGroupId > 0 && $iSourceGroupId !== $iTargetGroupId && !isset($aSourceGroupIdMap[$iSourceGroupId])) {
            $aSourceGroupIds[] = $iSourceGroupId;
            $aSourceGroupIdMap[$iSourceGroupId] = true;
        }
    }
    if ($iTargetGroupId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid target group."), 400);
    }
    if (!$aSourceGroupIds) {
        sendJsonAndExit(array("success" => false, "message" => "Select at least one source group."), 400);
    }
    if ($blDeleteSourceGroups && isset($aSourceGroupIdMap[1])) {
        sendJsonAndExit(array("success" => false, "message" => "The portal access group cannot be deleted."), 409);
    }
    try {
        $oPdo->beginTransaction();

        $aAllGroupIds = array_merge(array($iTargetGroupId), $aSourceGroupIds);
        $aGroupPlaceholders = array();
        $aGroupParams = array();
        foreach ($aAllGroupIds as $iIndex => $iGroupId) {
            $sParam = "group_id_" . $iIndex;
            $aGroupPlaceholders[] = ":" . $sParam;
            $aGroupParams[$sParam] = $iGroupId;
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE id IN (" . implode(", ", $aGroupPlaceholders) . ") FOR UPDATE");
        $oStatement->execute($aGroupParams);
        $aFoundGroupIds = array();
        while ($iFoundGroupId = $oStatement->fetchColumn()) {
            $aFoundGroupIds[(int)$iFoundGroupId] = true;
        }
        if (!isset($aFoundGroupIds[$iTargetGroupId])) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Target group was not found."), 404);
        }
        foreach ($aSourceGroupIds as $iSourceGroupId) {
            if (!isset($aFoundGroupIds[$iSourceGroupId])) {
                $oPdo->rollBack();
                sendJsonAndExit(array("success" => false, "message" => "Source group was not found."), 404);
            }
        }
        $aTargetSubjectIds = array();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE group_id = :target_group_id FOR UPDATE");
        $oStatement->execute(array("target_group_id" => $iTargetGroupId));
        while ($iSubjectId = $oStatement->fetchColumn()) {
            $aTargetSubjectIds[(int)$iSubjectId] = true;
        }

        $aSourcePlaceholders = array();
        $aSourceParams = array();
        foreach ($aSourceGroupIds as $iIndex => $iSourceGroupId) {
            $sParam = "source_group_id_" . $iIndex;
            $aSourcePlaceholders[] = ":" . $sParam;
            $aSourceParams[$sParam] = $iSourceGroupId;
        }
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE group_id IN (" . implode(", ", $aSourcePlaceholders) . ") FOR UPDATE");
        $oStatement->execute($aSourceParams);
        $aMergeSubjectIds = array();
        while ($iSubjectId = $oStatement->fetchColumn()) {
            $aMergeSubjectIds[(int)$iSubjectId] = true;
        }
        $oInsertStatement = $oPdo->prepare("INSERT INTO ex_subject_groups (subject_id, group_id) VALUES (:subject_id, :group_id)");
        foreach ($aMergeSubjectIds as $iSubjectId => $blMergeSubject) {
            if (!isset($aTargetSubjectIds[$iSubjectId])) {
                $oInsertStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iTargetGroupId));
                $aTargetSubjectIds[$iSubjectId] = true;
            }
        }
        $aTargetPermissionIds = array();
        $oStatement = $oPdo->prepare("SELECT permission_id FROM ex_group_permissions WHERE group_id = :target_group_id AND is_allowed = 1 FOR UPDATE");
        $oStatement->execute(array("target_group_id" => $iTargetGroupId));
        while ($iPermissionId = $oStatement->fetchColumn()) {
            $aTargetPermissionIds[(int)$iPermissionId] = true;
        }
        $oStatement = $oPdo->prepare("SELECT permission_id FROM ex_group_permissions WHERE group_id IN (" . implode(", ", $aSourcePlaceholders) . ") AND is_allowed = 1 FOR UPDATE");
        $oStatement->execute($aSourceParams);
        $aMergePermissionIds = array();
        while ($iPermissionId = $oStatement->fetchColumn()) {
            $aMergePermissionIds[(int)$iPermissionId] = true;
        }
        $oInsertStatement = $oPdo->prepare("INSERT INTO ex_group_permissions (group_id, permission_id, is_allowed) VALUES (:group_id, :permission_id, 1)");
        foreach ($aMergePermissionIds as $iPermissionId => $blMergePermission) {
            if (!isset($aTargetPermissionIds[$iPermissionId])) {
                $oInsertStatement->execute(array("group_id" => $iTargetGroupId, "permission_id" => $iPermissionId));
                $aTargetPermissionIds[$iPermissionId] = true;
            }
        }
        if ($blDeleteSourceGroups) {
            $oStatement = $oPdo->prepare("DELETE FROM ex_subject_groups WHERE group_id IN (" . implode(", ", $aSourcePlaceholders) . ")");
            $oStatement->execute($aSourceParams);
            $oStatement = $oPdo->prepare("DELETE FROM ex_group_permissions WHERE group_id IN (" . implode(", ", $aSourcePlaceholders) . ")");
            $oStatement->execute($aSourceParams);
            $oStatement = $oPdo->prepare("DELETE FROM ex_groups WHERE id IN (" . implode(", ", $aSourcePlaceholders) . ")");
            $oStatement->execute($aSourceParams);
        }
        $oPdo->commit();
        $aGroups = fetchGroupAdminRows($oPdo, $iTargetGroupId);
        $aGroup = count($aGroups) > 0 ? $aGroups[0] : null;
        sendJsonAndExit(array(
            "success" => true,
            "groups_merged" => true,
            "target_group_id" => $iTargetGroupId,
            "source_group_ids" => $aSourceGroupIds,
            "source_groups_deleted" => $blDeleteSourceGroups,
            "target_row_html" => renderGroupAdminRow($aGroup)
        ));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


$aGroups = array();
$aPortalPermissions = array();
try {
    $aGroups = fetchGroupAdminRows($oPdo);
    $aPortalPermissions = fetchPortalPermissions($oPdo);
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText("Groups", $aAllowedIps)); ?></title>
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
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="groups-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

if ($blCanEdit) {
    echo "    <button type=\"button\" class=\"button-link js-add-group\">New</button>\n";
}

?>
  </p>
  <table id="groups-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>" data-permissions="<?php echo htmlspecialchars(json_encode($aPortalPermissions), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
    <thead>
      <tr>
        <th>Name</th>
        <th class="admin-subjects-column">Subjects</th>
        <th class="admin-permissions-column">Permissions</th>
        <th class="admin-action-column">Order</th>
        <th class="admin-action-column"></th>
        <th class="admin-action-column"></th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aGroups as $aGroup) {
    echo renderGroupAdminRow($aGroup, $blCanEdit);
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
