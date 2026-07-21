<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aTables = array();
$aRelations = array();
$aForeignKeys = array();
$aDependencies = array();
try {
    $oStatement = $oPdo->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $aRow["TABLE_NAME"])) {
            continue;
        }
        if (!isset($aTables[$aRow["TABLE_NAME"]])) {
            $aTables[$aRow["TABLE_NAME"]] = array();
            $aDependencies[$aRow["TABLE_NAME"]] = array();
        }
        $aTables[$aRow["TABLE_NAME"]][] = $aRow;
    }

    $oStatement = $oPdo->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $aRow["TABLE_NAME"])
            || !preg_match("/^ex_[a-zA-Z0-9_]+$/", $aRow["REFERENCED_TABLE_NAME"])) {
            continue;
        }
        $aRelations[] = $aRow;
        $aForeignKeys[$aRow["TABLE_NAME"] . "." . $aRow["COLUMN_NAME"]] = true;
        if (isset($aDependencies[$aRow["TABLE_NAME"]], $aDependencies[$aRow["REFERENCED_TABLE_NAME"]])
            && $aRow["TABLE_NAME"] !== $aRow["REFERENCED_TABLE_NAME"]) {
            $aDependencies[$aRow["TABLE_NAME"]][$aRow["REFERENCED_TABLE_NAME"]] = true;
        }
    }

    $aSortedTables = array();
    $aTableStates = array();
    foreach (array_keys($aTables) as $sFirstTableName) {
        $aStack = array($sFirstTableName);
        while (count($aStack) > 0) {
            $sTableName = end($aStack);
            if (isset($aTableStates[$sTableName]) && $aTableStates[$sTableName] == "done") {
                array_pop($aStack);
                continue;
            }
            if (!isset($aTableStates[$sTableName])) {
                $aTableStates[$sTableName] = "visiting";
            }
            $blDependencyAdded = false;
            foreach ($aDependencies[$sTableName] as $sReferencedTableName => $blDependency) {
                if (!isset($aTableStates[$sReferencedTableName])) {
                    $aStack[] = $sReferencedTableName;
                    $blDependencyAdded = true;
                    break;
                }
            }
            if ($blDependencyAdded) {
                continue;
            }
            $aSortedTables[$sTableName] = $aTables[$sTableName];
            $aTableStates[$sTableName] = "done";
            array_pop($aStack);
        }
    }
    $aTables = $aSortedTables;
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$aSchemaRelationRoutes = array(
    "ex_contacts.contact_type_id>ex_contact_types.id" => array("source" => "right", "target" => "right", "curve" => "24", "via-x-offset" => "36"),
    "ex_group_permissions.group_id>ex_groups.id" => array("source" => "right", "target" => "left", "curve" => "74"),
    "ex_group_permissions.permission_id>ex_permissions.id" => array("source" => "right", "target" => "right", "curve" => "24", "target-y" => "-18", "via-x-offset" => "36"),
    "ex_persons.subject_id>ex_subjects.id" => array("source" => "left", "target" => "right", "curve" => "70", "target-y" => "-53"),
    "ex_subject_addresses.subject_id>ex_subjects.id" => array("source" => "left", "target" => "right", "curve" => "24", "target-y" => "-24", "via-x-offset" => "96"),
    "ex_subject_contacts.contact_id>ex_contacts.id" => array("source" => "left", "target" => "right", "curve" => "70"),
    "ex_subject_contacts.subject_id>ex_subjects.id" => array("source" => "left", "target" => "left", "curve" => "24", "target-y" => "48", "via-x-offset" => "-35"),
    "ex_subject_groups.group_id>ex_groups.id" => array("source" => "right", "target" => "right", "curve" => "24", "via-x-offset" => "32"),
    "ex_subject_groups.subject_id>ex_subjects.id" => array("source" => "left", "target" => "left", "curve" => "44", "target-y" => "24", "via-x-offset" => "-54"),
    "ex_subject_names.subject_id>ex_subjects.id" => array("source" => "right", "target" => "left", "curve" => "70", "target-y" => "-53"),
    "ex_subject_nicknames.subject_id>ex_subjects.id" => array("source" => "left", "target" => "right", "curve" => "24", "target-y" => "48", "via-x-offset" => "52"),
    "ex_subject_notes.subject_id>ex_subjects.id" => array("source" => "left", "target" => "right", "curve" => "44", "target-y" => "24", "via-x-offset" => "74"),
    "ex_users.subject_id>ex_subjects.id" => array("source" => "left", "target" => "left", "curve" => "70", "target-y" => "-24", "via-x-offset" => "-73"),
    "ex_user_permissions.permission_id>ex_permissions.id" => array("source" => "left", "target" => "right", "curve" => "72", "target-y" => "18", "via-y-offset" => "160"),
    "ex_user_permissions.user_id>ex_users.id" => array("source" => "left", "target" => "right", "curve" => "74")
);

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
  <title><?php echo html(getPageTitleText("Database Schema", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <p class="schema-unavailable-message"><strong>Database Schema: </strong>The database schema cannot be displayed on this device.</p>
  <div class="schema-diagram" id="schema-diagram">
    <div class="schema-canvas" id="schema-canvas">
      <svg class="schema-lines" id="schema-lines" aria-hidden="true">
        <defs>
          <marker id="schema-arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto" markerUnits="strokeWidth">
            <path d="M0,0 L8,4 L0,8 Z" fill="#A32929"></path>
          </marker>
        </defs>
      </svg>
      <div class="schema-grid">
<?php

foreach ($aTables as $sTableName => $aColumns) {
    echo "        <table class=\"schema-table\" data-table=\"" . htmlspecialchars($sTableName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">\n",
        "          <caption>" . htmlspecialchars($sTableName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</caption>\n",
        "          <colgroup>\n",
        "            <col class=\"schema-col-key\">\n",
        "            <col class=\"schema-col-column\">\n",
        "            <col class=\"schema-col-type\">\n",
        "            <col class=\"schema-col-null\">\n",
        "            <col class=\"schema-col-extra\">\n",
        "          </colgroup>\n",
        "          <thead>\n",
        "            <tr>\n",
        "              <th>Key</th>\n",
        "              <th>Column</th>\n",
        "              <th>Type</th>\n",
        "              <th>Null</th>\n",
        "              <th>Extra</th>\n",
        "            </tr>\n",
        "          </thead>\n",
        "          <tbody>\n";
    foreach ($aColumns as $aColumn) {
        $sKey = "";
        $sKeyClass = "";
        $sColumnType = (string)$aColumn["COLUMN_TYPE"];
        $sColumnTypeDisplay = schemaColumnTypeDisplay($sColumnType);
        $sColumnTypeTitleDisplay = schemaColumnTypeDisplay($sColumnType, false);
        $sColumnTypeTitle = $sColumnTypeDisplay != $sColumnTypeTitleDisplay ? " title=\"" . str_replace("…", "&hellip;", htmlspecialchars($sColumnTypeTitleDisplay, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")) . "\"" : "";
        if ($aColumn["COLUMN_KEY"] == "PRI") {
            $sKey = "PK";
            $sKeyClass = " schema-key-pk";
        } elseif (isset($aForeignKeys[$sTableName . "." . $aColumn["COLUMN_NAME"]])) {
            $sKey = "FK";
            $sKeyClass = " schema-key-fk";
        } elseif ($aColumn["COLUMN_KEY"] == "UNI") {
            $sKey = "UQ";
        }
        $sColumnId = "column-" . preg_replace("/[^a-zA-Z0-9_-]/", "-", $sTableName . "-" . $aColumn["COLUMN_NAME"]);
        echo "            <tr id=\"" . htmlspecialchars($sColumnId, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">\n",
            "              <td class=\"schema-key" . $sKeyClass . "\">" . $sKey . "</td>\n",
            "              <td>" . htmlspecialchars($aColumn["COLUMN_NAME"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "              <td class=\"schema-column-type\"" . $sColumnTypeTitle . ">" . str_replace("…", "&hellip;", htmlspecialchars($sColumnTypeDisplay, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")) . "</td>\n",
            "              <td class=\"schema-null\">" . ($aColumn["IS_NULLABLE"] == "YES" ? "Yes" : "No") . "</td>\n",
            "              <td>" . htmlspecialchars($aColumn["EXTRA"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "            </tr>\n";
    }
    echo "          </tbody>\n",
        "        </table>\n";
}

?>
      </div>
    </div>
  </div>
  <table class="schema-relations">
    <thead>
      <tr>
        <th>Constraint</th>
        <th>Foreign Key</th>
        <th>References</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aRelations as $aRelation) {
    $sRelationKey = $aRelation["TABLE_NAME"] . "." . $aRelation["COLUMN_NAME"] . ">" . $aRelation["REFERENCED_TABLE_NAME"] . "." . $aRelation["REFERENCED_COLUMN_NAME"];
    $sRouteAttributes = "";
    if (isset($aSchemaRelationRoutes[$sRelationKey])) {
        $sRouteAttributes = " data-source-side=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["source"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\""
            . " data-target-side=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["target"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        if (isset($aSchemaRelationRoutes[$sRelationKey]["curve"])) {
            $sRouteAttributes .= " data-curve=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["curve"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["source-x"])) {
            $sRouteAttributes .= " data-source-x-offset=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["source-x"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["source-y"])) {
            $sRouteAttributes .= " data-source-y-offset=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["source-y"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["target-x"])) {
            $sRouteAttributes .= " data-target-x-offset=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["target-x"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["target-y"])) {
            $sRouteAttributes .= " data-target-y-offset=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["target-y"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-x"])) {
            $sRouteAttributes .= " data-via-x=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["via-x"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-x-offset"])) {
            $sRouteAttributes .= " data-via-x-offset=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["via-x-offset"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-y"])) {
            $sRouteAttributes .= " data-via-y=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["via-y"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-y-offset"])) {
            $sRouteAttributes .= " data-via-y-offset=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["via-y-offset"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-table-bottom-offset"])) {
            $sRouteAttributes .= " data-via-table-bottom-offset=\"" . htmlspecialchars($aSchemaRelationRoutes[$sRelationKey]["via-table-bottom-offset"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"";
        }
    }
    echo "      <tr data-source-table=\"" . htmlspecialchars($aRelation["TABLE_NAME"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"),
        "\" data-source-column=\"" . htmlspecialchars($aRelation["COLUMN_NAME"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"),
        "\" data-target-table=\"" . htmlspecialchars($aRelation["REFERENCED_TABLE_NAME"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"),
        "\" data-target-column=\"" . htmlspecialchars($aRelation["REFERENCED_COLUMN_NAME"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\"",
        $sRouteAttributes . ">\n",
        "        <td>" . htmlspecialchars($aRelation["CONSTRAINT_NAME"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td>" . htmlspecialchars($aRelation["TABLE_NAME"] . "." . $aRelation["COLUMN_NAME"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td>" . htmlspecialchars($aRelation["REFERENCED_TABLE_NAME"] . "." . $aRelation["REFERENCED_COLUMN_NAME"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "      </tr>\n";
}

?>
    </tbody>
  </table>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
