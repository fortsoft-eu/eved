<?php

include "main.php";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireFullAccess("film", "csrf_token");
    requireNamedCsrfToken("csrf_token");
}

$sMessage = "";
$sMessageType = "";
$blRedirectAfterPost = false;
$blCanManageEquipmentGroups = isFullAccessAllowed("film");

if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
    $_SESSION["film"] = array();
}
if (!isset($_SESSION["film"]["equip"]) || !is_array($_SESSION["film"]["equip"])) {
    $_SESSION["film"]["equip"] = array();
}

if ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_SESSION["film"]["equip"]["message"])) {
    if (is_string($_SESSION["film"]["equip"]["message"])) {
        $sMessage = $_SESSION["film"]["equip"]["message"];
    }
    if (isset($_SESSION["film"]["equip"]["type"]) && is_string($_SESSION["film"]["equip"]["type"])) {
        $sMessageType = $_SESSION["film"]["equip"]["type"];
    }
    unset($_SESSION["film"]["equip"]["message"], $_SESSION["film"]["equip"]["type"]);
} elseif ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_SESSION["film"]["equip"]["type"])) {
    unset($_SESSION["film"]["equip"]["type"]);
}

$aEquipment = array();
$aEquipmentRelations = array();
$blEquipmentGroupTableExists = false;
try {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_equipment_link"])) {
        $blRedirectAfterPost = true;
        $iEquipmentId = isset($_POST["equip_id"]) ? (int)$_POST["equip_id"] : 0;
        $iMemberEquipmentId = isset($_POST["member_equip_id"]) ? (int)$_POST["member_equip_id"] : 0;
        if ($iEquipmentId < 1 || $iMemberEquipmentId < 1) {
            $sMessage = "You must select two equipment items.";
            $sMessageType = "error";
        } elseif ($iEquipmentId == $iMemberEquipmentId) {
            $sMessage = "You must select two different equipment items.";
            $sMessageType = "error";
        } else {
            $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fs_photo_equip_groups'");
            $oStatement->execute();
            $blEquipmentGroupTableExists = (int)$oStatement->fetchColumn() > 0;
            if (!$blEquipmentGroupTableExists) {
                $sMessage = "The equipment group table is not available.";
                $sMessageType = "error";
            } else {
                $oStatement = $oPdo->prepare("SELECT id, equip_type, equip_name FROM fs_photo_equip WHERE id IN (:equip_id, :member_equip_id)");
                $oStatement->execute(array(
                    ":equip_id"        => $iEquipmentId,
                    ":member_equip_id" => $iMemberEquipmentId
                ));
                $aSelectedEquipmentRows = $oStatement->fetchAll();
                $aSelectedEquipment = array();
                foreach ($aSelectedEquipmentRows as $aSelectedEquipmentRow) {
                    $aSelectedEquipment[(int)$aSelectedEquipmentRow["id"]] = $aSelectedEquipmentRow;
                }
                if (!isset($aSelectedEquipment[$iEquipmentId], $aSelectedEquipment[$iMemberEquipmentId])) {
                    $sMessage = "The equipment link could not be created.";
                    $sMessageType = "error";
                } else {
                    $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM fs_photo_equip_groups WHERE (equip_id = :equip_id_a AND member_equip_id = :member_equip_id_a) OR (equip_id = :member_equip_id_b AND member_equip_id = :equip_id_b)");
                    $oStatement->execute(array(
                        ":equip_id_a"        => $iEquipmentId,
                        ":member_equip_id_a" => $iMemberEquipmentId,
                        ":member_equip_id_b" => $iMemberEquipmentId,
                        ":equip_id_b"        => $iEquipmentId
                    ));
                    if ((int)$oStatement->fetchColumn() > 0) {
                        $sMessage = "The equipment <strong>" . formatEquipmentOptionLabel($aSelectedEquipment[$iEquipmentId]) . "</strong> is already linked to <strong>" . formatEquipmentOptionLabel($aSelectedEquipment[$iMemberEquipmentId]) . "</strong>.";
                        $sMessageType = "warning";
                    } else {
                        $oStatement = $oPdo->prepare("INSERT INTO fs_photo_equip_groups (equip_id, member_equip_id) VALUES (:equip_id, :member_equip_id)");
                        $oStatement->execute(array(
                            ":equip_id"        => $iEquipmentId,
                            ":member_equip_id" => $iMemberEquipmentId
                        ));
                        if ($oStatement->rowCount() > 0) {
                            $sMessage = "The equipment <strong>" . formatEquipmentOptionLabel($aSelectedEquipment[$iEquipmentId]) . "</strong> has been linked to <strong>" . formatEquipmentOptionLabel($aSelectedEquipment[$iMemberEquipmentId]) . "</strong>.";
                            $sMessageType = "success";
                        } else {
                            $sMessage = "The equipment link could not be created.";
                            $sMessageType = "error";
                        }
                    }
                }
            }
        }
    }
    if ($blRedirectAfterPost) {
        if ($sMessage) {
            $_SESSION["film"]["equip"]["message"] = $sMessage;
            $_SESSION["film"]["equip"]["type"] = $sMessageType;
        }
        sendSecurityHeaders();
        header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
        exit;
    }

    $oStatement = $oPdo->prepare("SELECT id, equip_type, equip_name, acquired_at, retired_at, disposition_note FROM fs_photo_equip ORDER BY acquired_at ASC, id ASC");
    $oStatement->execute();
    $aEquipment = $oStatement->fetchAll();

    $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fs_photo_equip_groups'");
    $oStatement->execute();
    $blEquipmentGroupTableExists = (int)$oStatement->fetchColumn() > 0;
    if ($blEquipmentGroupTableExists) {
        $oStatement = $oPdo->prepare("SELECT equip_id, member_equip_id FROM fs_photo_equip_groups ORDER BY equip_id ASC, member_equip_id ASC");
        $oStatement->execute();
        $aEquipmentRelations = $oStatement->fetchAll();
    }
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$aEquipmentById = array();
$aEquipmentGroupMembers = array();
$aEquipmentGroupParents = array();
foreach ($aEquipment as $iIndex => $aRow) {
    $iEquipmentId = (int)$aRow["id"];
    $aEquipment[$iIndex]["equipment_group_id"] = $iEquipmentId;
    $aEquipment[$iIndex]["equipment_group_size"] = 1;
    $aEquipment[$iIndex]["equipment_group_order"] = $iIndex;
    $aEquipment[$iIndex]["associated_equipment"] = array();
    $aEquipmentById[$iEquipmentId] = $iIndex;
    $aEquipmentGroupParents[$iEquipmentId] = $iEquipmentId;
}
foreach ($aEquipmentRelations as $aRelation) {
    $iEquipmentId = (int)$aRelation["equip_id"];
    $iMemberEquipmentId = (int)$aRelation["member_equip_id"];
    if ($iEquipmentId > 0 && $iMemberEquipmentId > 0 && $iEquipmentId != $iMemberEquipmentId && isset($aEquipmentById[$iEquipmentId], $aEquipmentById[$iMemberEquipmentId])) {
        filmEquipmentGroupUnion($aEquipmentGroupParents, $iEquipmentId, $iMemberEquipmentId);
    }
}
foreach ($aEquipmentById as $iEquipmentId => $iIndex) {
    $iGroupId = filmEquipmentGroupFind($aEquipmentGroupParents, $iEquipmentId);
    $aEquipment[$iIndex]["equipment_group_id"] = $iGroupId;
    if (!isset($aEquipmentGroupMembers[$iGroupId])) {
        $aEquipmentGroupMembers[$iGroupId] = array();
    }
    $aEquipmentGroupMembers[$iGroupId][] = $iIndex;
}
foreach ($aEquipmentGroupMembers as $iGroupId => $aMemberIndexes) {
    $iGroupSize = count($aMemberIndexes);
    $iGroupOrder = count($aEquipment);
    foreach ($aMemberIndexes as $iMemberIndex) {
        if ((int)$aEquipment[$iMemberIndex]["equipment_group_order"] < $iGroupOrder) {
            $iGroupOrder = (int)$aEquipment[$iMemberIndex]["equipment_group_order"];
        }
    }
    foreach ($aMemberIndexes as $iMemberIndex) {
        $aEquipment[$iMemberIndex]["equipment_group_size"] = $iGroupSize;
        $aEquipment[$iMemberIndex]["equipment_group_order"] = $iGroupOrder;
        if ($iGroupSize > 1) {
            foreach ($aMemberIndexes as $iRelatedIndex) {
                if ($iRelatedIndex != $iMemberIndex) {
                    $aEquipment[$iMemberIndex]["associated_equipment"][] = ucfirst($aEquipment[$iRelatedIndex]["equip_type"]) . ": " . $aEquipment[$iRelatedIndex]["equip_name"];
                }
            }
        }
    }
}
usort($aEquipment, "filmEquipmentRowCompare");

$blShowEquipmentActions = $blCanManageEquipmentGroups && $blEquipmentGroupTableExists;
$sCsrfToken = $blShowEquipmentActions ? getCsrfToken("csrf_token") : "";
session_write_close();
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="equipment-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

echo "    <span class=\"table-record-count js-table-record-count\" data-table-count=\"equipment-table\" aria-live=\"polite\">" . count($aEquipment) . "</span>\n";
if ($sMessage) {
    echo "    <span class=\"message-box message-" . html($sMessageType) . "\" id=\"message-box\">" . $sMessage . "</span>\n";
}

?>
  </p>
  <table id="equipment-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Equipment Type</th>
        <th>Equipment Name</th>
        <th>Acquired Date</th>
        <th>Retired Date</th>
        <th>Disposition Note</th>
        <th>Related Equipment</th>
<?php

if ($blShowEquipmentActions) {
    echo "        <th class=\"admin-action-column\"></th>\n";
}

?>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aEquipment as $aRow) {
    $sAcquiredAt = substr($aRow["acquired_at"], 0, 10);
    $sRetiredAt = $aRow["retired_at"] !== null ? substr($aRow["retired_at"], 0, 10) : "";
    $sEquipmentLabel = formatEquipmentOptionLabel($aRow);
    $sAssociatedEquipment = $aRow["associated_equipment"] ? html(implode("; ", $aRow["associated_equipment"])) : "&mdash;";
    $sEquipmentLinked = (int)$aRow["equipment_group_size"] > 1 ? "1" : "0";
    $sActionCell = $blShowEquipmentActions ? "<a href=\"#\" class=\"ia js-equipment-link\" data-equipment-id=\"" . (int)$aRow["id"] . "\" data-equipment-label=\"" . $sEquipmentLabel . "\" data-equipment-linked=\"" . $sEquipmentLinked . "\" title=\"Create equipment link\" aria-label=\"Create equipment link\">" . $sEquipmentLinkEmoji . "</a>" : "";
    $sGroupAttributes = (int)$aRow["equipment_group_size"] > 1 ? " data-order-id=\"" . html($aRow["equipment_group_id"]) . "\" data-order-no=\"" . html($aRow["equipment_group_id"]) . "\"" : "";
    $sAcquiredAt = html($sAcquiredAt);
    $sRetiredAt = html($sRetiredAt);
    echo "      <tr data-equipment-id=\"" . (int)$aRow["id"] . "\" data-equipment-label=\"" . $sEquipmentLabel . "\"" . $sGroupAttributes . ">\n",
        "        <td>" . html(ucfirst($aRow["equip_type"])) . "</td>\n",
        "        <td>" . html($aRow["equip_name"]) . "</td>\n",
        "        <td>" . $sAcquiredAt . "</td>\n",
        "        <td>" . $sRetiredAt . "</td>\n",
        "        <td>" . html($aRow["disposition_note"]) . "</td>\n",
        "        <td class=\"equipment-associated-cell\">" . $sAssociatedEquipment . "</td>\n",
        ($blShowEquipmentActions ? "        <td class=\"admin-action-column\">" . $sActionCell . "</td>\n" : ""),
        "      </tr>\n";
}

?>
    </tbody>
  </table>
<?php

if ($blShowEquipmentActions) {

?>
  <div class="confirm-dialog" id="film-equipment-link-dialog" hidden>
    <form class="confirm-dialog-box" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="csrf_token" value="<?php echo html($sCsrfToken); ?>">
      <input type="hidden" name="create_equipment_link" value="1">
      <input type="hidden" name="equip_id" class="js-equipment-link-id" value="">
      <div class="confirm-dialog-header">
        <strong>Create Equipment Link</strong>
        <button type="button" class="confirm-dialog-close js-equipment-link-close" aria-label="Close">&times;</button>
      </div>
      <p class="confirm-dialog-message"><strong class="js-equipment-link-source"></strong></p>
      <div class="equipment-link-dialog-field">
        <label for="equipment-link-member-id">Related Equipment</label>
        <select name="member_equip_id" id="equipment-link-member-id" class="js-equipment-link-member" required>
          <option value="">&ndash; Choose equipment &ndash;</option>
<?php

foreach ($aEquipment as $aOption) {
    $sEquipmentLinked = (int)$aOption["equipment_group_size"] > 1 ? "1" : "0";
    echo "          <option value=\"" . (int)$aOption["id"] . "\" data-equipment-linked=\"" . $sEquipmentLinked . "\"" . ($sEquipmentLinked == "1" ? " disabled" : "") . ">" . formatEquipmentOptionLabel($aOption) . "</option>\n";
}

?>
        </select>
      </div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button js-equipment-link-confirm">Save</button>
        <button type="button" class="confirm-dialog-button js-equipment-link-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

}

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
