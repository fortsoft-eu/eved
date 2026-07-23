<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

requireFullAccess($aAllowedIps, "ex", "ex_csrf_token");

$sUploadError = "";
$aPersonDiff = null;
$aSubjectDiff = null;
$aStructureDiff = array();
$aTableDiff = array();
$blCompared = false;
$blHasDifferences = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("ex_csrf_token");
    try {
        if (!isset($_FILES["database_backup"]) || !is_array($_FILES["database_backup"])) {
            $sUploadError = "No backup file was uploaded.";
        } elseif ((int)$_FILES["database_backup"]["error"] != UPLOAD_ERR_OK) {
            $sUploadError = diffUploadErrorMessage((int)$_FILES["database_backup"]["error"]);
        } elseif (!isset($_FILES["database_backup"]["tmp_name"]) || !is_uploaded_file($_FILES["database_backup"]["tmp_name"])) {
            $sUploadError = "The uploaded backup file is not available.";
        } else {
            $sBackupSql = file_get_contents($_FILES["database_backup"]["tmp_name"]);
            if ($sBackupSql === false || trim($sBackupSql) == "") {
                $sUploadError = "The uploaded backup file is empty.";
            } else {
                $aBackupDump = diffParseDatabaseSql($sBackupSql);
                $aCurrentDump = diffGetCurrentDump($oPdo);
                $aPersonFields = array(
                    "name" => "Name",
                    "subject_type" => "Subject Type",
                    "is_active" => "Active",
                    "legacy_id" => "Legacy ID",
                    "person_row" => "Person Row",
                    "title_before" => "Title Before",
                    "first_name" => "First Name",
                    "middle_name" => "Middle Name",
                    "last_name" => "Last Name",
                    "title_after" => "Title After",
                    "birth_name" => "Birth Name",
                    "birth_number" => "Birth Number",
                    "birth_date" => "Birth Date",
                    "death_date" => "Death Date"
                );
                $aSubjectFields = array(
                    "name" => "Name",
                    "subject_type" => "Subject Type",
                    "is_active" => "Active",
                    "legacy_id" => "Legacy ID"
                );
                $aPersonDiff = diffCompareEntityRows(diffBuildPersonRows($aBackupDump), diffBuildPersonRows($aCurrentDump), $aPersonFields);
                $aSubjectDiff = diffCompareEntityRows(diffBuildSubjectRows($aBackupDump), diffBuildSubjectRows($aCurrentDump), $aSubjectFields);
                $aStructureDiff = diffCompareStructure($aBackupDump, $aCurrentDump);
                $aTableDiff = diffCompareTableRows($aBackupDump, $aCurrentDump);
                $blCompared = true;
                $blHasDifferences = $aStructureDiff || $aPersonDiff["missing"] || $aPersonDiff["added"] || $aPersonDiff["changed"] || $aSubjectDiff["missing"] || $aSubjectDiff["added"] || $aSubjectDiff["changed"];
                foreach ($aTableDiff as $aRow) {
                    if ((int)$aRow["missing_rows"] > 0 || (int)$aRow["added_rows"] > 0 || (int)$aRow["changed_rows"] > 0) {
                        $blHasDifferences = true;
                        break;
                    }
                }
            }
        }
    } catch (Exception $oException) {
        error_log((string)$oException);
        $sUploadError = $oException->getMessage();
    }
}

$sScriptUrl = $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]);
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
  <title><?php echo html(getPageTitleText("Database Difference", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <h1>Database Difference</h1>
  <form action="<?php echo htmlspecialchars($sScriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken("ex_csrf_token")); ?>">
    <label for="database-backup">Database Backup</label>
    <input type="file" id="database-backup" name="database_backup" accept=".sql,application/sql,text/plain" required>
    <button type="submit">Compare Backup</button>
  </form>
<?php

if ($sUploadError != "") {
    echo "  <p class=\"consistency-status consistency-status-error\">" . html($sUploadError) . "</p>\n";
} elseif (!$blCompared) {
    echo "  <p class=\"consistency-status consistency-status-warning\">Upload a backup generated by db.php to compare it with the current database.</p>\n";
} elseif ($blHasDifferences) {
    echo "  <p class=\"consistency-status consistency-status-error\">Database differences were found.</p>\n";
} else {
    echo "  <p class=\"consistency-status consistency-status-ok\">No database differences were found.</p>\n";
}

if ($blCompared) {
    $aEntityColumns = array(
        "subject_id" => "Subject ID",
        "name" => "Name",
        "is_active" => "Active",
        "legacy_id" => "Legacy ID",
        "birth_date" => "Birth Date"
    );
    echo "  <h2>Persons missing from current database (" . count($aPersonDiff["missing"]) . ")</h2>\n";
    diffRenderEntityTable($aPersonDiff["missing"], $aEntityColumns);
    echo "  <h2>Persons added in current database (" . count($aPersonDiff["added"]) . ")</h2>\n";
    diffRenderEntityTable($aPersonDiff["added"], $aEntityColumns);
    echo "  <h2>Changed persons (" . count($aPersonDiff["changed"]) . ")</h2>\n";
    diffRenderChangedEntityTable($aPersonDiff["changed"]);
    $aSubjectColumns = array(
        "subject_id" => "Subject ID",
        "name" => "Name",
        "subject_type" => "Type",
        "is_active" => "Active",
        "legacy_id" => "Legacy ID"
    );
    echo "  <h2>Subjects missing from current database (" . count($aSubjectDiff["missing"]) . ")</h2>\n";
    diffRenderEntityTable($aSubjectDiff["missing"], $aSubjectColumns);
    echo "  <h2>Subjects added in current database (" . count($aSubjectDiff["added"]) . ")</h2>\n";
    diffRenderEntityTable($aSubjectDiff["added"], $aSubjectColumns);
    echo "  <h2>Changed subjects (" . count($aSubjectDiff["changed"]) . ")</h2>\n";
    diffRenderChangedEntityTable($aSubjectDiff["changed"]);
    echo "  <h2>Structure differences (" . count($aStructureDiff) . ")</h2>\n";
    diffRenderEntityTable($aStructureDiff, array("table" => "Table", "difference" => "Difference"));
    $aChangedTableRows = array();
    foreach ($aTableDiff as $aRow) {
        if ((int)$aRow["missing_rows"] > 0 || (int)$aRow["added_rows"] > 0 || (int)$aRow["changed_rows"] > 0) {
            $aChangedTableRows[] = $aRow;
        }
    }
    echo "  <h2>Data table summary (" . count($aChangedTableRows) . ")</h2>\n";
    diffRenderEntityTable($aChangedTableRows, array(
        "table" => "Table",
        "backup_rows" => "Backup Rows",
        "current_rows" => "Current Rows",
        "missing_rows" => "Missing",
        "added_rows" => "Added",
        "changed_rows" => "Changed"
    ));
}

?>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
