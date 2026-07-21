<?php

include "main.php";


requireFullAccess($aAllowedIps, "kf", "kf_csrf_token");


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aTables = array();
$aRelations = array();
$aForeignKeys = array();
$aDependencies = array();
try {
    $oStatement = $oPdo->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'kf\\_%' ORDER BY TABLE_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        if (!preg_match("/^kf_[A-Za-z0-9_]+$/", $aRow["TABLE_NAME"])) {
            continue;
        }
        if (!isset($aTables[$aRow["TABLE_NAME"]])) {
            $aTables[$aRow["TABLE_NAME"]] = array();
            $aDependencies[$aRow["TABLE_NAME"]] = array();
        }
        $aTables[$aRow["TABLE_NAME"]][] = $aRow;
    }
    $oStatement = $oPdo->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'kf\\_%' AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        if (!preg_match("/^kf_[A-Za-z0-9_]+$/", $aRow["TABLE_NAME"])
            || !preg_match("/^kf_[A-Za-z0-9_]+$/", $aRow["REFERENCED_TABLE_NAME"])) {
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
    "kf_fin_groups.group_type_id>kf_fin_types.id" => array("source" => "right", "target" => "left", "curve" => "36", "target-y" => "-10"),
    "kf_fin_groups.member_type_id>kf_fin_types.id" => array("source" => "right", "target" => "left", "curve" => "54", "target-y" => "10"),
    "kf_fin_transactions.finance_type_id>kf_fin_types.id" => array("source" => "left", "target" => "right", "curve" => "72", "target-y" => "-10"),
    "kf_debt_movements.debt_id>kf_debts.id" => array("source" => "left", "target" => "right", "curve" => "52"),
    "kf_subscriptions.finance_type_id>kf_fin_types.id" => array("source" => "left", "target" => "right", "curve" => "72", "target-y" => "10")
);


$sTitle = getPageTitleText("Database Schema", $aAllowedIps);
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
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
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
    echo "        <table class=\"schema-table\" data-table=\"" . html($sTableName) . "\">\n",
        "          <caption>" . html($sTableName) . "</caption>\n",
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
        $sColumnTypeTitle = $sColumnTypeDisplay != $sColumnTypeTitleDisplay ? " title=\"" . str_replace("…", "&hellip;", html($sColumnTypeTitleDisplay)) . "\"" : "";
        if ($aColumn["COLUMN_KEY"] == "PRI") {
            $sKey = "PK";
            $sKeyClass = " schema-key-pk";
        } elseif (isset($aForeignKeys[$sTableName . "." . $aColumn["COLUMN_NAME"]])) {
            $sKey = "FK";
            $sKeyClass = " schema-key-fk";
        } elseif ($aColumn["COLUMN_KEY"] == "UNI") {
            $sKey = "UQ";
        } elseif ($aColumn["COLUMN_KEY"] == "MUL") {
            $sKey = "IX";
        }
        $sColumnId = "column-" . preg_replace("/[^a-zA-Z0-9_-]/", "-", $sTableName . "-" . $aColumn["COLUMN_NAME"]);
        echo "            <tr id=\"" . html($sColumnId) . "\">\n",
            "              <td class=\"schema-key" . $sKeyClass . "\">" . html($sKey) . "</td>\n",
            "              <td>" . html($aColumn["COLUMN_NAME"]) . "</td>\n",
            "              <td class=\"schema-column-type\"" . $sColumnTypeTitle . ">" . str_replace("…", "&hellip;", html($sColumnTypeDisplay)) . "</td>\n",
            "              <td class=\"schema-null\">" . ($aColumn["IS_NULLABLE"] == "YES" ? "Yes" : "No") . "</td>\n",
            "              <td>" . html($aColumn["EXTRA"]) . "</td>\n",
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
        $sRouteAttributes = " data-source-side=\"" . html($aSchemaRelationRoutes[$sRelationKey]["source"]) . "\""
            . " data-target-side=\"" . html($aSchemaRelationRoutes[$sRelationKey]["target"]) . "\"";
        if (isset($aSchemaRelationRoutes[$sRelationKey]["curve"])) {
            $sRouteAttributes .= " data-curve=\"" . html($aSchemaRelationRoutes[$sRelationKey]["curve"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["source-x"])) {
            $sRouteAttributes .= " data-source-x-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["source-x"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["source-y"])) {
            $sRouteAttributes .= " data-source-y-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["source-y"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["target-x"])) {
            $sRouteAttributes .= " data-target-x-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["target-x"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["target-y"])) {
            $sRouteAttributes .= " data-target-y-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["target-y"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-x"])) {
            $sRouteAttributes .= " data-via-x=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-x"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-x-offset"])) {
            $sRouteAttributes .= " data-via-x-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-x-offset"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-y"])) {
            $sRouteAttributes .= " data-via-y=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-y"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-y-offset"])) {
            $sRouteAttributes .= " data-via-y-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-y-offset"]) . "\"";
        }
        if (isset($aSchemaRelationRoutes[$sRelationKey]["via-table-bottom-offset"])) {
            $sRouteAttributes .= " data-via-table-bottom-offset=\"" . html($aSchemaRelationRoutes[$sRelationKey]["via-table-bottom-offset"]) . "\"";
        }
    }
    echo "      <tr data-source-table=\"" . html($aRelation["TABLE_NAME"]),
        "\" data-source-column=\"" . html($aRelation["COLUMN_NAME"]),
        "\" data-target-table=\"" . html($aRelation["REFERENCED_TABLE_NAME"]),
        "\" data-target-column=\"" . html($aRelation["REFERENCED_COLUMN_NAME"]) . "\"",
        $sRouteAttributes . ">\n",
        "        <td>" . html($aRelation["CONSTRAINT_NAME"]) . "</td>\n",
        "        <td>" . html($aRelation["TABLE_NAME"] . "." . $aRelation["COLUMN_NAME"]) . "</td>\n",
        "        <td>" . html($aRelation["REFERENCED_TABLE_NAME"] . "." . $aRelation["REFERENCED_COLUMN_NAME"]) . "</td>\n",
        "      </tr>\n";
}

if (!$aRelations) {
    echo "      <tr><td colspan=\"3\">No relations found.</td></tr>\n";
}

?>
    </tbody>
  </table>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
