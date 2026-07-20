<?php

include "main.php";


requireFullAccess($aAllowedIps, "kf", "kf_csrf_token");

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aTables = array();
try {
    $oStatement = $oPdo->query("SHOW TABLES");
    while ($sTableName = $oStatement->fetchColumn()) {
        if (!preg_match("/^kf_[A-Za-z0-9_]+$/", $sTableName)) {
            continue;
        }
        $oCreateStatement = $oPdo->query("SHOW CREATE TABLE `" . str_replace("`", "``", $sTableName) . "`");
        $aTable = $oCreateStatement->fetch(PDO::FETCH_NUM);
        if (isset($aTable[0], $aTable[1])) {
            $aTable[1] = preg_replace("/\s+AUTO_INCREMENT=\d+\b/i", "", $aTable[1]);
            $aTables[] = $aTable;
        }
    }
    $aTableRows = array();
    $aDependencies = array();
    foreach ($aTables as $aTable) {
        $aTableRows[$aTable[0]] = $aTable;
        $aDependencies[$aTable[0]] = array();
    }
    $oStatement = $oPdo->query("SELECT TABLE_NAME, REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        if (isset($aDependencies[$aRow["TABLE_NAME"]], $aDependencies[$aRow["REFERENCED_TABLE_NAME"]])
            && $aRow["TABLE_NAME"] !== $aRow["REFERENCED_TABLE_NAME"]) {
            $aDependencies[$aRow["TABLE_NAME"]][$aRow["REFERENCED_TABLE_NAME"]] = true;
        }
    }
    $aSortedTables = array();
    $aTableStates = array();
    foreach ($aTables as $aTable) {
        $aStack = array($aTable[0]);
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
            $aSortedTables[] = $aTableRows[$sTableName];
            $aTableStates[$sTableName] = "done";
            array_pop($aStack);
        }
    }
    $aTables = $aSortedTables;
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

if (isset($_GET["download"])) {
    $sDownload = (string)$_GET["download"];
    if ($sDownload == "db.sql") {
        $sDownload = "schema";
    }
    if ($sDownload == "schema" || $sDownload == "backup") {
        try {
            $sBody = $sDownload == "backup" ? getDatabaseBackupSql($oPdo, $aTables) : getDatabaseSchemaSql($aTables);
        } catch (Exception $oException) {
            error_log((string)$oException);
            send500AndExit("Database error: " . $oException->getMessage());
        }
        $aScriptNameParts = explode("/", trim((string)$_SERVER["SCRIPT_NAME"], "/"));
        $sPrefix = isset($aScriptNameParts[0]) && $aScriptNameParts[0] != "" ? $aScriptNameParts[0] : "eved";
        $sProject = isset($aScriptNameParts[1]) && $aScriptNameParts[1] != "" ? $aScriptNameParts[1] : "kf";
        $sFileName = $sPrefix . "_" . $sProject . "_" . $sDownload . "_" . date("Y-m-d_His", time()) . ".sql";
        sendDatabaseSqlAndExit($sFileName, $sBody);
    }
}

$sScriptUrl = $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]);
$sSchemaDownloadUrl = $sScriptUrl . "?download=schema";
$sBackupDownloadUrl = $sScriptUrl . "?download=backup";
$sDatabaseFormsHtml = "  <form action=\"" . html($sScriptUrl) . "\" method=\"get\" id=\"database-schema-download-form\" hidden>\n"
    . "    <input type=\"hidden\" name=\"download\" value=\"schema\">\n"
    . "  </form>\n"
    . "  <form action=\"" . html($sScriptUrl) . "\" method=\"get\" id=\"database-backup-download-form\" hidden>\n"
    . "    <input type=\"hidden\" name=\"download\" value=\"backup\">\n"
    . "  </form>\n";
$sDatabaseToolbarHtml = "    <button type=\"submit\" form=\"database-schema-download-form\" class=\"button-link database-action-button\">Download schema</button>\n"
    . "    <button type=\"button\" class=\"button-link database-action-button js-copy-link\" data-copy-link=\"" . html($sSchemaDownloadUrl) . "\">Copy schema link</button>\n"
    . "    <button type=\"submit\" form=\"database-backup-download-form\" class=\"button-link database-action-button\">Download backup</button>\n"
    . "    <button type=\"button\" class=\"button-link database-action-button js-copy-link\" data-copy-link=\"" . html($sBackupDownloadUrl) . "\">Copy backup link</button>\n";

$sTitle = getPageTitle("Database Structure");
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("kf_csrf_token")); ?>">
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
<?php

echo $sDatabaseFormsHtml;

?>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="kf-database-table" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php

echo $sDatabaseToolbarHtml;

?>
  </p>
<?php

renderMessage();

?>
  <table id="kf-database-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Table</th>
        <th>Structure</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aTables as $aTable) {
    echo "      <tr>\n",
        "        <td class=\"database-table-name\">" . html($aTable[0]) . "</td>\n",
        "        <td class=\"database-structure-cell\">" . formatDatabaseStructureHtml($aTable[1]) . "</td>\n",
        "      </tr>\n";
}

if (!$aTables) {
    echo "      <tr><td colspan=\"2\">No kf tables found.</td></tr>\n";
}

?>
    </tbody>
  </table>
<?php

echo "  <button type=\"button\" class=\"filter-focus-button js-filter-focus\" data-filter-input=\"table-filter\" title=\"Focus filter\" aria-label=\"Focus filter\">" . $sFilterFocusEmoji . " Filter</button>\n",
    "  <script type=\"text/javascript\" src=\"" . html($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n",
    "</body>\n",
    "</html>\n";

