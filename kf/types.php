<?php

include "main.php";

$blCanEdit = isFullAccessAllowed($aAllowedIps);
requireViewAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireFullAccess($aAllowedIps);
    requireCsrfToken();
    $sAction = getPostedTrimmedValue("action");

    if ($sAction == "save_type") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        $sName = getPostedTrimmedValue("name");
        $sTypeKind = getPostedTrimmedValue("type_kind", "expense");
        $aAllowedKinds = array("expense", "income", "group");
        if ($sName == "" || !in_array($sTypeKind, $aAllowedKinds, true)) {
            setMessage("The type could not be saved. Name and kind are required.", "error");
            redirect("types.php");
        }

        try {
            if ($iId > 0) {
                $oStatement = $oPdo->prepare("UPDATE kf_fin_types SET name = :name, type_kind = :type_kind WHERE id = :id");
                $oStatement->execute(array("name" => $sName, "type_kind" => $sTypeKind, "id" => $iId));
                setMessage("Type updated.");
            } else {
                $oStatement = $oPdo->prepare("INSERT INTO kf_fin_types (name, type_kind) VALUES (:name, :type_kind)");
                $oStatement->execute(array("name" => $sName, "type_kind" => $sTypeKind));
                $iId = (int)$oPdo->lastInsertId();
                setMessage("Type added.");
            }

            $oStatement = $oPdo->prepare("DELETE FROM kf_fin_groups WHERE group_type_id = :group_type_id");
            $oStatement->execute(array("group_type_id" => $iId));
            if ($sTypeKind == "group" && isset($_POST["members"]) && is_array($_POST["members"])) {
                $oInsert = $oPdo->prepare("INSERT IGNORE INTO kf_fin_groups (group_type_id, member_type_id) VALUES (:group_type_id, :member_type_id)");
                foreach ($_POST["members"] as $sMemberId) {
                    $iMemberId = (int)$sMemberId;
                    if ($iMemberId > 0 && $iMemberId != $iId) {
                        $oInsert->execute(array("group_type_id" => $iId, "member_type_id" => $iMemberId));
                    }
                }
            }
        } catch (PDOException $oException) {
            error_log((string)$oException);
            setMessage("The type could not be saved. The name may already exist.", "error");
        }
        redirect("types.php");
    } elseif ($sAction == "delete_type") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM kf_fin_trans WHERE finance_type_id = :id");
            $oStatement->execute(array("id" => $iId));
            if ((int)$oStatement->fetchColumn() > 0) {
                setMessage("The type is used by transactions and cannot be deleted.", "error");
            } else {
                try {
                    $oStatement = $oPdo->prepare("DELETE FROM kf_fin_types WHERE id = :id");
                    $oStatement->execute(array("id" => $iId));
                    setMessage("Type deleted.");
                } catch (PDOException $oException) {
                    error_log((string)$oException);
                    setMessage("The type could not be deleted.", "error");
                }
            }
        }
        redirect("types.php");
    }
}

$aRows = array();
$oStatement = $oPdo->query("SELECT ft.id, ft.name, ft.type_kind, GROUP_CONCAT(m.id ORDER BY m.name SEPARATOR ',') AS member_ids, GROUP_CONCAT(m.name ORDER BY m.name SEPARATOR ', ') AS member_names FROM kf_fin_types ft LEFT JOIN kf_fin_groups gi ON gi.group_type_id = ft.id LEFT JOIN kf_fin_types m ON m.id = gi.member_type_id GROUP BY ft.id, ft.name, ft.type_kind ORDER BY FIELD(ft.type_kind, 'income', 'expense', 'group'), ft.name ASC, ft.id ASC");
while ($aRow = $oStatement->fetch()) {
    $aRows[] = $aRow;
}

$aMemberTypes = $blCanEdit ? getFinanceTypes(false) : array();

$sToolbarHtml = "";
if ($blCanEdit) {
    $sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-type\" data-modal-target=\"type-modal\" data-modal-title=\"New Type\" data-field-id=\"\" data-field-name=\"\" data-field-type_kind=\"expense\" data-field-members=\"\">New</button>\n";
}

$sTitle = getPageTitle("Income and Expense Types");
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken()); ?>">
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="kf-types-table" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php echo $sToolbarHtml; ?>
  </p>
<?php renderMessage(); ?>
  <table id="kf-types-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Kind</th>
        <th>Name</th>
        <th>Group Members</th>
<?php if ($blCanEdit) { ?>
        <th></th>
<?php } ?>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aRows as $aRow) {
    $sActionCell = "";
    if ($blCanEdit) {
        $sActionCell = "        <td class=\"nowrap\"><button type=\"button\" class=\"button-link\" data-modal-target=\"type-modal\" data-modal-title=\"Edit Type\" data-field-id=\"" . (int)$aRow["id"] . "\" data-field-name=\"" . html($aRow["name"]) . "\" data-field-type_kind=\"" . html($aRow["type_kind"]) . "\" data-field-members=\"" . html($aRow["member_ids"]) . "\">Edit</button></td>\n";
    }
    echo "      <tr>\n"
        . "        <td>" . html(ucfirst($aRow["type_kind"])) . "</td>\n"
        . "        <td>" . html($aRow["name"]) . "</td>\n"
        . "        <td>" . htmlValue($aRow["member_names"]) . "</td>\n"
        . $sActionCell
        . "      </tr>\n";
}

if (!$aRows) {
    echo "      <tr><td colspan=\"" . ($blCanEdit ? 4 : 3) . "\">No types found.</td></tr>\n";
}

?>
    </tbody>
  </table>

<?php if ($blCanEdit) { ?>
  <div id="type-modal" class="confirm-dialog" hidden>
    <form method="post" class="confirm-dialog-box kf-edit-dialog">
      <div class="confirm-dialog-header"><strong data-modal-heading>Type</strong><button type="button" class="confirm-dialog-close" data-modal-close aria-label="Close">&times;</button></div>
        <input type="hidden" name="kf_csrf_token" value="<?php echo html(getCsrfToken()); ?>">
        <input type="hidden" name="action" value="save_type">
        <input type="hidden" name="id" value="">
        <label for="type-name">Name</label>
        <input type="text" id="type-name" name="name" required>
        <label for="type-kind">Kind</label>
        <select id="type-kind" name="type_kind" required>
          <option value="income">Income</option>
          <option value="expense">Expense</option>
          <option value="group">Group</option>
        </select>
        <div data-visible-for-kind="group" hidden>
          <label>Group Members</label>
          <div class="checkbox-grid">
<?php

foreach ($aMemberTypes as $aType) {
    echo "            <label class=\"checkbox-label\"><input type=\"checkbox\" name=\"members[]\" value=\"" . (int)$aType["id"] . "\"> " . html($aType["name"]) . "</label>\n";
}

?>
          </div>
        </div>
        <div class="confirm-dialog-actions">
          <button type="submit" class="confirm-dialog-button">Save</button>
          <button type="submit" name="action" value="delete_type" class="confirm-dialog-button">Delete</button>
          <button type="button" class="confirm-dialog-button" data-modal-close>Cancel</button>
        </div>
    </form>
  </div>
<?php
}

echo "  <button type=\"button\" class=\"filter-focus-button js-filter-focus\" data-filter-input=\"table-filter\" title=\"Focus filter\" aria-label=\"Focus filter\">" . $sFilterFocusEmoji . " Filter</button>\n"
    . "  <script type=\"text/javascript\" src=\"" . html($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n"
    . "</body>\n"
    . "</html>\n";

