<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "lm_csrf_token");


$aTables = array();
try {
    $oStatement = $oPdo->query("SHOW TABLES");
    $aTableNames = $oStatement->fetchAll(PDO::FETCH_COLUMN);
    foreach ($aTableNames as $sTableName) {
        $sQuotedTableName = "`" . str_replace("`", "``", $sTableName) . "`";
        $oStatement = $oPdo->query("SHOW CREATE TABLE " . $sQuotedTableName);
        $aTable = $oStatement->fetch(PDO::FETCH_NUM);
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
        $aScriptNameParts = explode("/", $_SERVER["SCRIPT_NAME"]);
        $sPrefix = $aScriptNameParts[1];
        $sProject = $aScriptNameParts[2];
        $sFileName = $sPrefix . "_" . $sProject . "_" . $sDownload . "_" . date("Y-m-d_His", time()) . ".sql";
        sendDatabaseSqlAndExit($sFileName, $sBody);
    }
}

$sScriptUrl = $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]);
$sSchemaDownloadUrl = $sScriptUrl . "?download=schema";
$sBackupDownloadUrl = $sScriptUrl . "?download=backup";

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
  <title><?php echo htmlspecialchars(getPageTitleText("Database Structure", $aAllowedIps), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <form action="<?php echo htmlspecialchars($sScriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" method="get" id="database-schema-download-form" hidden>
    <input type="hidden" name="download" value="schema">
  </form>
  <form action="<?php echo htmlspecialchars($sScriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" method="get" id="database-backup-download-form" hidden>
    <input type="hidden" name="download" value="backup">
  </form>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="database-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="submit" form="database-schema-download-form" class="button-link database-action-button">Download schema</button>
    <button type="button" class="button-link database-action-button js-copy-link" data-copy-link="<?php echo htmlspecialchars($sSchemaDownloadUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">Copy schema link</button>
    <button type="submit" form="database-backup-download-form" class="button-link database-action-button">Download backup</button>
    <button type="button" class="button-link database-action-button js-copy-link" data-copy-link="<?php echo htmlspecialchars($sBackupDownloadUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">Copy backup link</button>
  </p>
  <table id="database-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>">
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
        "        <td class=\"database-table-name\">" . htmlspecialchars($aTable[0], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td class=\"database-structure-cell\">" . formatDatabaseStructureHtml($aTable[1]) . "</td>\n",
        "      </tr>\n";
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js"></script>
</body>
</html>
