<?php

include "main.php";

requireExFullAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aTables = array();
$aRelations = array();
$aForeignKeys = array();
try {
    $oStatement = $oPdo->query("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'kf\\_%' ORDER BY TABLE_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch()) {
        if (!preg_match("/^kf_[A-Za-z0-9_]+$/", $aRow["TABLE_NAME"])) {
            continue;
        }
        if (!isset($aTables[$aRow["TABLE_NAME"]])) {
            $aTables[$aRow["TABLE_NAME"]] = array();
        }
        $aTables[$aRow["TABLE_NAME"]][] = $aRow;
    }

    $oStatement = $oPdo->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'kf\\_%' AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch()) {
        if (!preg_match("/^kf_[A-Za-z0-9_]+$/", $aRow["TABLE_NAME"])
            || !preg_match("/^kf_[A-Za-z0-9_]+$/", $aRow["REFERENCED_TABLE_NAME"])) {
            continue;
        }
        $aRelations[] = $aRow;
        $aForeignKeys[$aRow["TABLE_NAME"] . "." . $aRow["COLUMN_NAME"]] = true;
    }
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}

$sTitle = kfGetPageTitle("Database Schema");
$iTime = kfSendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo kfHtml(kfGetCsrfToken()); ?>">
  <title><?php echo kfHtml($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo kfHtml($sBaseUrl . "../ex/css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/../ex/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
  <link href="<?php echo kfHtml($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php kfRenderMenu(); ?>
  </p>
<?php kfRenderMessage(); ?>
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
    echo "        <table class=\"schema-table\" data-table=\"" . kfHtml($sTableName) . "\">\n"
        . "          <caption>" . kfHtml($sTableName) . "</caption>\n"
        . "          <colgroup>\n"
        . "            <col class=\"schema-col-key\">\n"
        . "            <col class=\"schema-col-column\">\n"
        . "            <col class=\"schema-col-type\">\n"
        . "            <col class=\"schema-col-null\">\n"
        . "            <col class=\"schema-col-extra\">\n"
        . "          </colgroup>\n"
        . "          <thead>\n"
        . "            <tr>\n"
        . "              <th>Key</th>\n"
        . "              <th>Column</th>\n"
        . "              <th>Type</th>\n"
        . "              <th>Null</th>\n"
        . "              <th>Extra</th>\n"
        . "            </tr>\n"
        . "          </thead>\n"
        . "          <tbody>\n";
    foreach ($aColumns as $aColumn) {
        $sKey = "";
        $sKeyClass = "";
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
        echo "            <tr id=\"" . kfHtml($sColumnId) . "\">\n"
            . "              <td class=\"schema-key" . $sKeyClass . "\">" . kfHtml($sKey) . "</td>\n"
            . "              <td>" . kfHtml($aColumn["COLUMN_NAME"]) . "</td>\n"
            . "              <td class=\"schema-column-type\">" . kfHtml($aColumn["COLUMN_TYPE"]) . "</td>\n"
            . "              <td class=\"schema-null\">" . ($aColumn["IS_NULLABLE"] == "YES" ? "Yes" : "No") . "</td>\n"
            . "              <td>" . kfHtml($aColumn["EXTRA"]) . "</td>\n"
            . "            </tr>\n";
    }
    echo "          </tbody>\n"
        . "        </table>\n";
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
    echo "      <tr data-source-table=\"" . kfHtml($aRelation["TABLE_NAME"])
        . "\" data-source-column=\"" . kfHtml($aRelation["COLUMN_NAME"])
        . "\" data-target-table=\"" . kfHtml($aRelation["REFERENCED_TABLE_NAME"])
        . "\" data-target-column=\"" . kfHtml($aRelation["REFERENCED_COLUMN_NAME"]) . "\">\n"
        . "        <td>" . kfHtml($aRelation["CONSTRAINT_NAME"]) . "</td>\n"
        . "        <td>" . kfHtml($aRelation["TABLE_NAME"] . "." . $aRelation["COLUMN_NAME"]) . "</td>\n"
        . "        <td>" . kfHtml($aRelation["REFERENCED_TABLE_NAME"] . "." . $aRelation["REFERENCED_COLUMN_NAME"]) . "</td>\n"
        . "      </tr>\n";
}

if (!$aRelations) {
    echo "      <tr><td colspan=\"3\">No relations found.</td></tr>\n";
}

?>
    </tbody>
  </table>
<?php

echo "  <script type=\"text/javascript\" src=\"" . kfHtml($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n"
    . "</body>\n"
    . "</html>\n";

