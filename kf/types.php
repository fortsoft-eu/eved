<?php

include "main.php";


$blCanEdit = isFullAccessAllowed($aAllowedIps, "kf");
requireViewAccess($aAllowedIps, "kf", "kf_csrf_token");


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sAction = getPostedTrimmedValue("action");
    $blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
    requireFullAccess($aAllowedIps, "kf", "kf_csrf_token", $blJsonResponse);
    requireNamedCsrfToken("kf_csrf_token", $blJsonResponse);
    if ($sAction == "save_type") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        $sName = getPostedTrimmedValue("name");
        $sTypeKind = getPostedTrimmedValue("type_kind", "expense");
        $aAllowedKinds = array("expense", "income", "group");
        if ($sName == "" || !in_array($sTypeKind, $aAllowedKinds, true)) {
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The type could not be saved. Name and kind are required."), 400);
            }
            redirect(getCurrentScriptName());
        }
        try {
            if ($iId > 0) {
                $oStatement = $oPdo->prepare("UPDATE kf_fin_types SET name = :name, type_kind = :type_kind WHERE id = :id");
                $oStatement->execute(array("name" => $sName, "type_kind" => $sTypeKind, "id" => $iId));
            } else {
                $oStatement = $oPdo->prepare("INSERT INTO kf_fin_types (name, type_kind) VALUES (:name, :type_kind)");
                $oStatement->execute(array("name" => $sName, "type_kind" => $sTypeKind));
                $iId = (int)$oPdo->lastInsertId();
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
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => true, "type_id" => $iId, "rows_html" => renderFinanceTypeAdminRows(fetchFinanceTypeAdminRows($oPdo), $blCanEdit)));
            }
        } catch (PDOException $oException) {
            error_log((string)$oException);
            if ($blJsonResponse) {
                sendJsonAndExit(array("success" => false, "message" => "The type could not be saved. The name may already exist."), 409);
            }
        }
        redirect(getCurrentScriptName());
    } elseif ($sAction == "delete_type") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM kf_fin_trans WHERE finance_type_id = :id");
            $oStatement->execute(array("id" => $iId));
            if ((int)$oStatement->fetchColumn() > 0) {
                if ($blJsonResponse) {
                    sendJsonAndExit(array("success" => false, "message" => "The type is used by transactions and cannot be deleted."), 409);
                }
            } else {
                try {
                    $oStatement = $oPdo->prepare("DELETE FROM kf_fin_types WHERE id = :id");
                    $oStatement->execute(array("id" => $iId));
                    if ($blJsonResponse) {
                        sendJsonAndExit(array("success" => true, "type_id" => $iId, "type_deleted" => true, "rows_html" => renderFinanceTypeAdminRows(fetchFinanceTypeAdminRows($oPdo), $blCanEdit)));
                    }
                } catch (PDOException $oException) {
                    error_log((string)$oException);
                    if ($blJsonResponse) {
                        sendJsonAndExit(array("success" => false, "message" => "The type could not be deleted."), 500);
                    }
                }
            }
        }
        redirect(getCurrentScriptName());
    }
}


$aRows = fetchFinanceTypeAdminRows($oPdo);


$aMemberTypes = $blCanEdit ? getFinanceTypes(false) : array();
$sToolbarHtml = "";
if ($blCanEdit) {
    $sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-type\">New</button>\n";
}


$sTitle = getPageTitle("Income and Expense Types");
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
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("kf_csrf_token")); ?>">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="types-table" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

echo $sToolbarHtml,
    "  </p>\n";

?>
  <table id="types-table" class="table-filter-target" data-member-types="<?php echo htmlspecialchars(json_encode($aMemberTypes), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
    <thead>
      <tr>
        <th>Kind</th>
        <th>Name</th>
        <th>Group Members</th>
<?php

if ($blCanEdit) {
    echo "        <th class=\"admin-action-column\"></th>\n";

}
echo "      </tr>\n",
    "    </thead>\n",
    "    <tbody>\n";

echo renderFinanceTypeAdminRows($aRows, $blCanEdit),
    "    </tbody>\n",
    "  </table>\n";


?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
