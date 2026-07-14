<?php

include "main.php";


$blCanEdit = isExFullAccessAllowed($aAllowedIps);
requireExViewAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireExCsrfToken();
}


if (!$blCanEdit && $_SERVER["REQUEST_METHOD"] == "POST") {
    nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_group") {
    $sName = nxGetPostedTrimmedValue("name");
    $aPermissionKeys = isset($_POST["permissions"]) && is_array($_POST["permissions"]) ? $_POST["permissions"] : array();
    if ($sName == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $iOrder = (int)$oPdo->query("SELECT COALESCE(MAX(`order`), 0) + 10 FROM ex_groups")->fetchColumn();
        $oStatement = $oPdo->prepare("INSERT INTO ex_groups (name, `order`) VALUES (:name, :order)");
        $oStatement->execute(array("name" => $sName, "order" => $iOrder));
        $iGroupId = (int)$oPdo->lastInsertId();
        nxSaveGroupPortalPermissions($oPdo, $iGroupId, $aPermissionKeys);
        $oPdo->commit();
        $aGroups = nxFetchGroupAdminRows($oPdo, $iGroupId);
        $aGroup = count($aGroups) > 0 ? $aGroups[0] : null;
        nxSendJsonAndExit(array("success" => true, "group_id" => $iGroupId, "row_html" => nxRenderGroupAdminRow($aGroup)));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected group name already exists."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_group") {
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    $sName = nxGetPostedTrimmedValue("name");
    $aPermissionKeys = isset($_POST["permissions"]) && is_array($_POST["permissions"]) ? $_POST["permissions"] : array();
    if ($iGroupId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid group."), 400);
    }
    if ($sName == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Group was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_groups SET name = :name WHERE id = :id");
        $oStatement->execute(array("name" => $sName, "id" => $iGroupId));
        nxSaveGroupPortalPermissions($oPdo, $iGroupId, $aPermissionKeys);
        $oPdo->commit();
        $aGroups = nxFetchGroupAdminRows($oPdo, $iGroupId);
        $aGroup = count($aGroups) > 0 ? $aGroups[0] : null;
        nxSendJsonAndExit(array("success" => true, "group_id" => $iGroupId, "row_html" => nxRenderGroupAdminRow($aGroup)));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() == "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected group name already exists."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_group") {
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    if ($iGroupId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid group."), 400);
    }
    if ($iGroupId === 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "The portal access group cannot be deleted."), 409);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Group was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_groups WHERE group_id = :id");
        $oStatement->execute(array("id" => $iGroupId));
        $oStatement = $oPdo->prepare("DELETE FROM ex_group_permissions WHERE group_id = :id");
        $oStatement->execute(array("id" => $iGroupId));
        $oStatement = $oPdo->prepare("DELETE FROM ex_groups WHERE id = :id");
        $oStatement->execute(array("id" => $iGroupId));
        $oPdo->commit();
        nxSendJsonAndExit(array("success" => true, "group_id" => $iGroupId, "group_deleted" => true));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "move_group") {
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    $sDirection = isset($_POST["direction"]) ? (string)$_POST["direction"] : "";
    if ($iGroupId < 1 || ($sDirection != "up" && $sDirection != "down")) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid order change."), 400);
    }

    try {
        $oPdo->beginTransaction();
        nxMoveGroupOrder($oPdo, $iGroupId, $sDirection);
        $oPdo->commit();
        nxSendJsonAndExit(array("success" => true, "rows_html" => nxRenderGroupAdminRows($oPdo, $blCanEdit)));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
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
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid target group."), 400);
    }
    if (!$aSourceGroupIds) {
        nxSendJsonAndExit(array("success" => false, "message" => "Select at least one source group."), 400);
    }
    if ($blDeleteSourceGroups && isset($aSourceGroupIdMap[1])) {
        nxSendJsonAndExit(array("success" => false, "message" => "The portal access group cannot be deleted."), 409);
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
            nxSendJsonAndExit(array("success" => false, "message" => "Target group was not found."), 404);
        }
        foreach ($aSourceGroupIds as $iSourceGroupId) {
            if (!isset($aFoundGroupIds[$iSourceGroupId])) {
                $oPdo->rollBack();
                nxSendJsonAndExit(array("success" => false, "message" => "Source group was not found."), 404);
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
        $aGroups = nxFetchGroupAdminRows($oPdo, $iTargetGroupId);
        $aGroup = count($aGroups) > 0 ? $aGroups[0] : null;
        nxSendJsonAndExit(array(
            "success" => true,
            "groups_merged" => true,
            "target_group_id" => $iTargetGroupId,
            "source_group_ids" => $aSourceGroupIds,
            "source_groups_deleted" => $blDeleteSourceGroups,
            "target_row_html" => nxRenderGroupAdminRow($aGroup)
        ));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


$aGroups = array();
$aPortalPermissions = array();
try {
    $aGroups = nxFetchGroupAdminRows($oPdo);
    $aPortalPermissions = nxFetchPortalPermissions($oPdo);
} catch (Exception $oException) {
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
  <meta name="viewport" content="<?php echo nxHtml(nxGetLockedViewportContent()); ?>">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo nxHtml(getExPageTitleText("Groups", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo nxHtml(getExCsrfToken()); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-groups-table" value="<?php echo nxHtml(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

if ($blCanEdit) {
    echo "    <button type=\"button\" class=\"button-link js-add-group\">New</button>\n";
}

?>
  </p>
  <table id="nx-groups-table" class="table-filter-target" data-permissions="<?php echo htmlspecialchars(json_encode($aPortalPermissions), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
    <thead>
      <tr>
        <th>Name</th>
        <th class="nx-admin-subjects-column">Subjects</th>
        <th class="nx-admin-permissions-column">Permissions</th>
        <th class="nx-admin-action-column">Order</th>
        <th class="nx-admin-action-column"></th>
        <th class="nx-admin-action-column"></th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aGroups as $aGroup) {
    echo nxRenderGroupAdminRow($aGroup, $blCanEdit);
}

echo "    </tbody>\n";
echo "  </table>\n";
echo nxRenderFilterFocusButton();
echo nxRenderAdminScript($sBaseUrl);
?>
</body>
</html>
