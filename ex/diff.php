<?php

include "main.php";


requireExFullAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

function nxDiffEnsureDumpTable(&$aDump, $sTableName) {
    if (!isset($aDump["tables"][$sTableName])) {
        $aDump["tables"][$sTableName] = array(
            "create" => "",
            "primary_keys" => array(),
            "columns" => array(),
            "rows" => array()
        );
        $aDump["table_order"][] = $sTableName;
    }
}

function nxDiffDecodeSqlIdentifier($sIdentifier) {
    return str_replace("``", "`", $sIdentifier);
}

function nxDiffParseSqlIdentifierList($sSql) {
    $aIdentifiers = array();
    if (preg_match_all("/`((?:``|[^`])*)`/", $sSql, $aMatches)) {
        foreach ($aMatches[1] as $sIdentifier) {
            $aIdentifiers[] = nxDiffDecodeSqlIdentifier($sIdentifier);
        }
    }
    return $aIdentifiers;
}

function nxDiffNormalizeCreateSql($sSql) {
    $sSql = trim((string)$sSql);
    $sSql = preg_replace("/\s+AUTO_INCREMENT=\d+\b/i", "", $sSql);
    return preg_replace("/\r\n|\r|\n/", "\n", $sSql);
}

function nxDiffGetPrimaryKeyColumns($sCreateSql) {
    if (!preg_match("/PRIMARY\s+KEY\s+\(([^)]*)\)/is", $sCreateSql, $aMatches)) {
        return array();
    }
    return nxDiffParseSqlIdentifierList($aMatches[1]);
}

function nxDiffSplitSqlStatements($sSql) {
    $aStatements = array();
    $sStatement = "";
    $sMode = "";
    $iLength = strlen($sSql);
    for ($i = 0; $i < $iLength; $i++) {
        $sChar = $sSql[$i];
        $sStatement .= $sChar;
        if ($sMode == "string") {
            if ($sChar == "\\") {
                if ($i + 1 < $iLength) {
                    $i++;
                    $sStatement .= $sSql[$i];
                }
                continue;
            }
            if ($sChar == "'") {
                if ($i + 1 < $iLength && $sSql[$i + 1] == "'") {
                    $i++;
                    $sStatement .= $sSql[$i];
                    continue;
                }
                $sMode = "";
            }
            continue;
        }
        if ($sMode == "identifier") {
            if ($sChar == "`") {
                if ($i + 1 < $iLength && $sSql[$i + 1] == "`") {
                    $i++;
                    $sStatement .= $sSql[$i];
                    continue;
                }
                $sMode = "";
            }
            continue;
        }
        if ($sChar == "'") {
            $sMode = "string";
        } elseif ($sChar == "`") {
            $sMode = "identifier";
        } elseif ($sChar == ";") {
            $sStatement = trim(substr($sStatement, 0, -1));
            if ($sStatement != "") {
                $aStatements[] = $sStatement;
            }
            $sStatement = "";
        }
    }
    $sStatement = trim($sStatement);
    if ($sStatement != "") {
        $aStatements[] = $sStatement;
    }
    return $aStatements;
}

function nxDiffDecodeSqlString($sValue) {
    $sResult = "";
    $iLength = strlen($sValue);
    for ($i = 0; $i < $iLength; $i++) {
        $sChar = $sValue[$i];
        if ($sChar == "\\") {
            if ($i + 1 >= $iLength) {
                $sResult .= $sChar;
                continue;
            }
            $i++;
            $sNext = $sValue[$i];
            if ($sNext == "n") {
                $sResult .= "\n";
            } elseif ($sNext == "r") {
                $sResult .= "\r";
            } elseif ($sNext == "t") {
                $sResult .= "\t";
            } elseif ($sNext == "0") {
                $sResult .= chr(0);
            } elseif ($sNext == "b") {
                $sResult .= chr(8);
            } elseif ($sNext == "Z") {
                $sResult .= chr(26);
            } else {
                $sResult .= $sNext;
            }
        } elseif ($sChar == "'" && $i + 1 < $iLength && $sValue[$i + 1] == "'") {
            $sResult .= "'";
            $i++;
        } else {
            $sResult .= $sChar;
        }
    }
    return $sResult;
}

function nxDiffDecodeSqlValue($sToken) {
    $sToken = trim((string)$sToken);
    if (strcasecmp($sToken, "NULL") === 0) {
        return null;
    }
    if (strlen($sToken) >= 2 && $sToken[0] == "'" && substr($sToken, -1) == "'") {
        return nxDiffDecodeSqlString(substr($sToken, 1, -1));
    }
    return $sToken;
}

function nxDiffParseSqlValues($sSql) {
    $aValues = array();
    $sToken = "";
    $sMode = "";
    $iLength = strlen($sSql);
    for ($i = 0; $i < $iLength; $i++) {
        $sChar = $sSql[$i];
        if ($sMode == "string") {
            $sToken .= $sChar;
            if ($sChar == "\\") {
                if ($i + 1 < $iLength) {
                    $i++;
                    $sToken .= $sSql[$i];
                }
                continue;
            }
            if ($sChar == "'") {
                if ($i + 1 < $iLength && $sSql[$i + 1] == "'") {
                    $i++;
                    $sToken .= $sSql[$i];
                    continue;
                }
                $sMode = "";
            }
            continue;
        }
        if ($sChar == "'") {
            $sMode = "string";
            $sToken .= $sChar;
        } elseif ($sChar == ",") {
            $aValues[] = nxDiffDecodeSqlValue($sToken);
            $sToken = "";
        } else {
            $sToken .= $sChar;
        }
    }
    if (trim($sToken) != "" || $sSql != "") {
        $aValues[] = nxDiffDecodeSqlValue($sToken);
    }
    return $aValues;
}

function nxDiffParseDatabaseSql($sSql) {
    $aDump = array(
        "tables" => array(),
        "table_order" => array()
    );
    foreach (nxDiffSplitSqlStatements($sSql) as $sStatement) {
        if (preg_match("/^CREATE\s+TABLE\s+`((?:``|[^`])+)`/is", $sStatement, $aMatches)) {
            $sTableName = nxDiffDecodeSqlIdentifier($aMatches[1]);
            if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
                continue;
            }
            nxDiffEnsureDumpTable($aDump, $sTableName);
            $sCreateSql = nxDiffNormalizeCreateSql($sStatement);
            $aDump["tables"][$sTableName]["create"] = $sCreateSql;
            $aDump["tables"][$sTableName]["primary_keys"] = nxDiffGetPrimaryKeyColumns($sCreateSql);
        } elseif (preg_match("/^INSERT\s+INTO\s+`((?:``|[^`])+)`\s*\((.*)\)\s+VALUES\s*\((.*)\)$/is", $sStatement, $aMatches)) {
            $sTableName = nxDiffDecodeSqlIdentifier($aMatches[1]);
            if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
                continue;
            }
            nxDiffEnsureDumpTable($aDump, $sTableName);
            $aColumns = nxDiffParseSqlIdentifierList($aMatches[2]);
            $aValues = nxDiffParseSqlValues($aMatches[3]);
            if (count($aColumns) != count($aValues)) {
                throw new Exception("Invalid INSERT statement in table " . $sTableName . ".");
            }
            if (!$aDump["tables"][$sTableName]["columns"]) {
                $aDump["tables"][$sTableName]["columns"] = $aColumns;
            }
            $aRow = array();
            foreach ($aColumns as $iIndex => $sColumnName) {
                $aRow[$sColumnName] = $aValues[$iIndex];
            }
            $aDump["tables"][$sTableName]["rows"][] = $aRow;
        }
    }
    if (!$aDump["tables"]) {
        throw new Exception("The uploaded file does not look like a database backup generated by db.php.");
    }
    return $aDump;
}

function nxDiffFetchDatabaseTables($oPdo) {
    $aTables = array();
    $oStatement = $oPdo->query("SHOW TABLES");
    $aTableNames = $oStatement->fetchAll(PDO::FETCH_COLUMN);
    foreach ($aTableNames as $sTableName) {
        if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
            continue;
        }
        $oStatement = $oPdo->query("SHOW CREATE TABLE `" . $sTableName . "`");
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
            $bDependencyAdded = false;
            foreach ($aDependencies[$sTableName] as $sReferencedTableName => $bDependency) {
                if (!isset($aTableStates[$sReferencedTableName])) {
                    $aStack[] = $sReferencedTableName;
                    $bDependencyAdded = true;
                    break;
                }
            }
            if ($bDependencyAdded) {
                continue;
            }
            $aSortedTables[] = $aTableRows[$sTableName];
            $aTableStates[$sTableName] = "done";
            array_pop($aStack);
        }
    }
    return $aSortedTables;
}

function nxDiffGetCurrentDump($oPdo) {
    $aTables = nxDiffFetchDatabaseTables($oPdo);
    return nxDiffParseDatabaseSql(getDatabaseBackupSql($oPdo, $aTables));
}

function nxDiffGetTableRows($aDump, $sTableName) {
    return isset($aDump["tables"][$sTableName]) ? $aDump["tables"][$sTableName]["rows"] : array();
}

function nxDiffRowsByColumn($aDump, $sTableName, $sColumnName) {
    $aRows = array();
    foreach (nxDiffGetTableRows($aDump, $sTableName) as $aRow) {
        if (array_key_exists($sColumnName, $aRow) && $aRow[$sColumnName] !== null) {
            $aRows[(string)$aRow[$sColumnName]] = $aRow;
        }
    }
    return $aRows;
}

function nxDiffRowsGroupedByColumn($aDump, $sTableName, $sColumnName) {
    $aRows = array();
    foreach (nxDiffGetTableRows($aDump, $sTableName) as $aRow) {
        if (array_key_exists($sColumnName, $aRow) && $aRow[$sColumnName] !== null) {
            $sKey = (string)$aRow[$sColumnName];
            if (!isset($aRows[$sKey])) {
                $aRows[$sKey] = array();
            }
            $aRows[$sKey][] = $aRow;
        }
    }
    return $aRows;
}

function nxDiffRowValue($aRow, $sColumnName) {
    if (!is_array($aRow) || !array_key_exists($sColumnName, $aRow)) {
        return null;
    }
    return $aRow[$sColumnName];
}

function nxDiffTrimmedValue($aRow, $sColumnName) {
    $mValue = nxDiffRowValue($aRow, $sColumnName);
    return $mValue === null ? "" : trim((string)$mValue);
}

function nxDiffJoinNonEmptyValues($aValues, $sSeparator) {
    $aResult = array();
    foreach ($aValues as $mValue) {
        $sValue = trim((string)$mValue);
        if ($sValue != "") {
            $aResult[] = $sValue;
        }
    }
    return implode($sSeparator, $aResult);
}

function nxDiffCompareSubjectItems($aFirst, $aSecond) {
    $iFirstActive = (int)nxDiffRowValue($aFirst, "is_active");
    $iSecondActive = (int)nxDiffRowValue($aSecond, "is_active");
    if ($iFirstActive != $iSecondActive) {
        return $iSecondActive - $iFirstActive;
    }
    $iFirstPrimary = (int)nxDiffRowValue($aFirst, "is_primary");
    $iSecondPrimary = (int)nxDiffRowValue($aSecond, "is_primary");
    if ($iFirstPrimary != $iSecondPrimary) {
        return $iSecondPrimary - $iFirstPrimary;
    }
    return (int)nxDiffRowValue($aFirst, "id") - (int)nxDiffRowValue($aSecond, "id");
}

function nxDiffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts) {
    if (isset($aSubjectNames[$sSubjectId])) {
        $sName = nxDiffTrimmedValue($aSubjectNames[$sSubjectId], "name");
        if ($sName != "") {
            return $sName;
        }
    }
    if (isset($aNicknames[$sSubjectId])) {
        $aRows = $aNicknames[$sSubjectId];
        usort($aRows, "nxDiffCompareSubjectItems");
        foreach ($aRows as $aRow) {
            $sName = nxDiffTrimmedValue($aRow, "nickname");
            if ($sName != "") {
                return $sName;
            }
        }
    }
    if (isset($aSubjectContacts[$sSubjectId])) {
        $aRows = $aSubjectContacts[$sSubjectId];
        usort($aRows, "nxDiffCompareSubjectItems");
        foreach ($aRows as $aRow) {
            $sContactId = nxDiffTrimmedValue($aRow, "contact_id");
            if (isset($aContacts[$sContactId])) {
                $sName = nxDiffTrimmedValue($aContacts[$sContactId], "contact_value");
                if ($sName != "") {
                    return $sName;
                }
            }
        }
    }
    return "Unnamed subject";
}

function nxDiffBuildPersonDisplayName($aPerson, $sFallbackName) {
    $sBase = nxDiffJoinNonEmptyValues(array(
        nxDiffRowValue($aPerson, "title_before"),
        nxDiffRowValue($aPerson, "first_name"),
        nxDiffRowValue($aPerson, "middle_name"),
        nxDiffRowValue($aPerson, "last_name")
    ), " ");
    $sTitleAfter = nxDiffTrimmedValue($aPerson, "title_after");
    if ($sTitleAfter != "") {
        $sBase = $sBase != "" ? $sBase . ", " . $sTitleAfter : $sTitleAfter;
    }
    return $sBase != "" ? $sBase : $sFallbackName;
}

function nxDiffBuildPersonRows($aDump) {
    $aSubjects = nxDiffRowsByColumn($aDump, "ex_subjects", "id");
    $aPersons = nxDiffRowsByColumn($aDump, "ex_persons", "subject_id");
    $aSubjectNames = nxDiffRowsByColumn($aDump, "ex_subject_names", "subject_id");
    $aNicknames = nxDiffRowsGroupedByColumn($aDump, "ex_subject_nicknames", "subject_id");
    $aSubjectContacts = nxDiffRowsGroupedByColumn($aDump, "ex_subject_contacts", "subject_id");
    $aContacts = nxDiffRowsByColumn($aDump, "ex_contacts", "id");
    $aIds = array();
    foreach ($aSubjects as $sSubjectId => $aSubject) {
        if (nxDiffTrimmedValue($aSubject, "subject_type") == "person") {
            $aIds[$sSubjectId] = true;
        }
    }
    foreach ($aPersons as $sSubjectId => $aPerson) {
        $aIds[$sSubjectId] = true;
    }
    ksort($aIds, SORT_NUMERIC);
    $aRows = array();
    foreach ($aIds as $sSubjectId => $bUsed) {
        $aSubject = isset($aSubjects[$sSubjectId]) ? $aSubjects[$sSubjectId] : array();
        $aPerson = isset($aPersons[$sSubjectId]) ? $aPersons[$sSubjectId] : array();
        $sFallbackName = nxDiffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts);
        $aRows[$sSubjectId] = array(
            "subject_id" => $sSubjectId,
            "name" => nxDiffBuildPersonDisplayName($aPerson, $sFallbackName),
            "subject_type" => nxDiffRowValue($aSubject, "subject_type"),
            "is_active" => nxDiffRowValue($aSubject, "is_active"),
            "legacy_id" => nxDiffRowValue($aSubject, "legacy_id"),
            "person_row" => isset($aPersons[$sSubjectId]) ? "yes" : "no",
            "title_before" => nxDiffRowValue($aPerson, "title_before"),
            "first_name" => nxDiffRowValue($aPerson, "first_name"),
            "middle_name" => nxDiffRowValue($aPerson, "middle_name"),
            "last_name" => nxDiffRowValue($aPerson, "last_name"),
            "title_after" => nxDiffRowValue($aPerson, "title_after"),
            "birth_name" => nxDiffRowValue($aPerson, "birth_name"),
            "birth_number" => nxDiffRowValue($aPerson, "birth_number"),
            "birth_date" => nxDiffRowValue($aPerson, "birth_date"),
            "death_date" => nxDiffRowValue($aPerson, "death_date")
        );
    }
    return $aRows;
}

function nxDiffBuildSubjectRows($aDump) {
    $aSubjects = nxDiffRowsByColumn($aDump, "ex_subjects", "id");
    $aPersons = nxDiffRowsByColumn($aDump, "ex_persons", "subject_id");
    $aSubjectNames = nxDiffRowsByColumn($aDump, "ex_subject_names", "subject_id");
    $aNicknames = nxDiffRowsGroupedByColumn($aDump, "ex_subject_nicknames", "subject_id");
    $aSubjectContacts = nxDiffRowsGroupedByColumn($aDump, "ex_subject_contacts", "subject_id");
    $aContacts = nxDiffRowsByColumn($aDump, "ex_contacts", "id");
    ksort($aSubjects, SORT_NUMERIC);
    $aRows = array();
    foreach ($aSubjects as $sSubjectId => $aSubject) {
        $sFallbackName = nxDiffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts);
        if (nxDiffTrimmedValue($aSubject, "subject_type") == "person" && isset($aPersons[$sSubjectId])) {
            $sName = nxDiffBuildPersonDisplayName($aPersons[$sSubjectId], $sFallbackName);
        } else {
            $sName = $sFallbackName;
        }
        $aRows[$sSubjectId] = array(
            "subject_id" => $sSubjectId,
            "name" => $sName,
            "subject_type" => nxDiffRowValue($aSubject, "subject_type"),
            "is_active" => nxDiffRowValue($aSubject, "is_active"),
            "legacy_id" => nxDiffRowValue($aSubject, "legacy_id")
        );
    }
    return $aRows;
}

function nxDiffGetFieldChanges($aBackupRow, $aCurrentRow, $aFields) {
    $aChanges = array();
    foreach ($aFields as $sField => $sLabel) {
        $mBackupValue = nxDiffRowValue($aBackupRow, $sField);
        $mCurrentValue = nxDiffRowValue($aCurrentRow, $sField);
        if ($mBackupValue !== $mCurrentValue) {
            $aChanges[] = array(
                "field" => $sLabel,
                "backup" => $mBackupValue,
                "current" => $mCurrentValue
            );
        }
    }
    return $aChanges;
}

function nxDiffCompareEntityRows($aBackupRows, $aCurrentRows, $aFields) {
    $aResult = array(
        "missing" => array(),
        "added" => array(),
        "changed" => array()
    );
    foreach ($aBackupRows as $sKey => $aBackupRow) {
        if (!isset($aCurrentRows[$sKey])) {
            $aResult["missing"][] = $aBackupRow;
            continue;
        }
        $aChanges = nxDiffGetFieldChanges($aBackupRow, $aCurrentRows[$sKey], $aFields);
        if ($aChanges) {
            $aResult["changed"][] = array(
                "backup" => $aBackupRow,
                "current" => $aCurrentRows[$sKey],
                "changes" => $aChanges
            );
        }
    }
    foreach ($aCurrentRows as $sKey => $aCurrentRow) {
        if (!isset($aBackupRows[$sKey])) {
            $aResult["added"][] = $aCurrentRow;
        }
    }
    return $aResult;
}

function nxDiffNormalizeRowForHash($aRow) {
    ksort($aRow, SORT_STRING);
    return $aRow;
}

function nxDiffGetRowHash($aRow) {
    return sha1(json_encode(nxDiffNormalizeRowForHash($aRow)));
}

function nxDiffBuildRowKey($aRow, $aPrimaryKeys, $iIndex) {
    if (!$aPrimaryKeys) {
        return "row:" . $iIndex . ":" . nxDiffGetRowHash($aRow);
    }
    $aParts = array();
    foreach ($aPrimaryKeys as $sColumnName) {
        $aParts[$sColumnName] = nxDiffRowValue($aRow, $sColumnName);
    }
    return json_encode($aParts);
}

function nxDiffBuildTableRowMap($aDump, $sTableName) {
    $aRows = array();
    if (!isset($aDump["tables"][$sTableName])) {
        return $aRows;
    }
    $aPrimaryKeys = $aDump["tables"][$sTableName]["primary_keys"];
    foreach ($aDump["tables"][$sTableName]["rows"] as $iIndex => $aRow) {
        $sKey = nxDiffBuildRowKey($aRow, $aPrimaryKeys, $iIndex);
        $aRows[$sKey] = array(
            "row" => $aRow,
            "hash" => nxDiffGetRowHash($aRow)
        );
    }
    return $aRows;
}

function nxDiffCompareTableRows($aBackupDump, $aCurrentDump) {
    $aNames = array();
    foreach ($aBackupDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    foreach ($aCurrentDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    ksort($aNames, SORT_STRING);
    $aRows = array();
    foreach ($aNames as $sTableName => $bUsed) {
        $aBackupRows = isset($aBackupDump["tables"][$sTableName]) ? $aBackupDump["tables"][$sTableName]["rows"] : array();
        $aCurrentRows = isset($aCurrentDump["tables"][$sTableName]) ? $aCurrentDump["tables"][$sTableName]["rows"] : array();
        $aBackupMap = nxDiffBuildTableRowMap($aBackupDump, $sTableName);
        $aCurrentMap = nxDiffBuildTableRowMap($aCurrentDump, $sTableName);
        $iMissingRows = 0;
        $iAddedRows = 0;
        $iChangedRows = 0;
        foreach ($aBackupMap as $sKey => $aBackupRow) {
            if (!isset($aCurrentMap[$sKey])) {
                $iMissingRows++;
            } elseif ($aBackupRow["hash"] !== $aCurrentMap[$sKey]["hash"]) {
                $iChangedRows++;
            }
        }
        foreach ($aCurrentMap as $sKey => $aCurrentRow) {
            if (!isset($aBackupMap[$sKey])) {
                $iAddedRows++;
            }
        }
        $aRows[] = array(
            "table" => $sTableName,
            "backup_rows" => count($aBackupRows),
            "current_rows" => count($aCurrentRows),
            "missing_rows" => $iMissingRows,
            "added_rows" => $iAddedRows,
            "changed_rows" => $iChangedRows
        );
    }
    return $aRows;
}

function nxDiffCompareStructure($aBackupDump, $aCurrentDump) {
    $aNames = array();
    foreach ($aBackupDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    foreach ($aCurrentDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    ksort($aNames, SORT_STRING);
    $aRows = array();
    foreach ($aNames as $sTableName => $bUsed) {
        if (!isset($aBackupDump["tables"][$sTableName])) {
            $aRows[] = array("table" => $sTableName, "difference" => "Table exists only in the current database.");
        } elseif (!isset($aCurrentDump["tables"][$sTableName])) {
            $aRows[] = array("table" => $sTableName, "difference" => "Table exists only in the uploaded backup.");
        } elseif ($aBackupDump["tables"][$sTableName]["create"] !== $aCurrentDump["tables"][$sTableName]["create"]) {
            $aRows[] = array("table" => $sTableName, "difference" => "Table structure is different.");
        }
    }
    return $aRows;
}

function nxDiffUploadErrorMessage($iError) {
    if ($iError == UPLOAD_ERR_INI_SIZE || $iError == UPLOAD_ERR_FORM_SIZE) {
        return "The uploaded file is too large.";
    }
    if ($iError == UPLOAD_ERR_PARTIAL) {
        return "The uploaded file was received only partially.";
    }
    if ($iError == UPLOAD_ERR_NO_FILE) {
        return "No backup file was uploaded.";
    }
    if ($iError == UPLOAD_ERR_NO_TMP_DIR) {
        return "The server upload directory is missing.";
    }
    if ($iError == UPLOAD_ERR_CANT_WRITE) {
        return "The uploaded file could not be saved.";
    }
    if ($iError == UPLOAD_ERR_EXTENSION) {
        return "The upload was stopped by a PHP extension.";
    }
    return "The backup file could not be uploaded.";
}

function nxDiffTextValue($mValue) {
    if ($mValue === null) {
        return "NULL";
    }
    $sValue = (string)$mValue;
    return $sValue != "" ? $sValue : "(empty)";
}

function nxDiffRenderChangeList($aChanges) {
    $aItems = array();
    foreach ($aChanges as $aChange) {
        $aItems[] = nxHtml($aChange["field"] . ": " . nxDiffTextValue($aChange["backup"]) . " -> " . nxDiffTextValue($aChange["current"]));
    }
    return implode("<br>", $aItems);
}

function nxDiffRenderEntityTable($aRows, $aColumns) {
    if (!$aRows) {
        echo "  <p><em>&mdash;</em></p>\n";
        return;
    }
    echo "  <table class=\"consistency-table\">\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    foreach ($aColumns as $sColumn => $sLabel) {
        echo "        <th>" . nxHtml($sLabel) . "</th>\n";
    }
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    foreach ($aRows as $aRow) {
        echo "      <tr>\n";
        foreach ($aColumns as $sColumn => $sLabel) {
            echo "        <td>" . nxHtmlValue(nxDiffRowValue($aRow, $sColumn)) . "</td>\n";
        }
        echo "      </tr>\n";
    }
    echo "    </tbody>\n";
    echo "  </table>\n";
}

function nxDiffRenderChangedEntityTable($aRows) {
    if (!$aRows) {
        echo "  <p><em>&mdash;</em></p>\n";
        return;
    }
    echo "  <table class=\"consistency-table\">\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    echo "        <th>Subject ID</th>\n";
    echo "        <th>Backup Name</th>\n";
    echo "        <th>Current Name</th>\n";
    echo "        <th>Changed Fields</th>\n";
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    foreach ($aRows as $aRow) {
        echo "      <tr>\n";
        echo "        <td>" . nxHtmlValue(nxDiffRowValue($aRow["backup"], "subject_id")) . "</td>\n";
        echo "        <td>" . nxHtmlValue(nxDiffRowValue($aRow["backup"], "name")) . "</td>\n";
        echo "        <td>" . nxHtmlValue(nxDiffRowValue($aRow["current"], "name")) . "</td>\n";
        echo "        <td>" . nxDiffRenderChangeList($aRow["changes"]) . "</td>\n";
        echo "      </tr>\n";
    }
    echo "    </tbody>\n";
    echo "  </table>\n";
}

$sUploadError = "";
$aPersonDiff = null;
$aSubjectDiff = null;
$aStructureDiff = array();
$aTableDiff = array();
$blCompared = false;
$blHasDifferences = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireExCsrfToken();
    try {
        if (!isset($_FILES["database_backup"]) || !is_array($_FILES["database_backup"])) {
            $sUploadError = "No backup file was uploaded.";
        } elseif ((int)$_FILES["database_backup"]["error"] != UPLOAD_ERR_OK) {
            $sUploadError = nxDiffUploadErrorMessage((int)$_FILES["database_backup"]["error"]);
        } elseif (!isset($_FILES["database_backup"]["tmp_name"]) || !is_uploaded_file($_FILES["database_backup"]["tmp_name"])) {
            $sUploadError = "The uploaded backup file is not available.";
        } else {
            $sBackupSql = file_get_contents($_FILES["database_backup"]["tmp_name"]);
            if ($sBackupSql === false || trim($sBackupSql) == "") {
                $sUploadError = "The uploaded backup file is empty.";
            } else {
                $aBackupDump = nxDiffParseDatabaseSql($sBackupSql);
                $aCurrentDump = nxDiffGetCurrentDump($oPdo);
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
                $aPersonDiff = nxDiffCompareEntityRows(nxDiffBuildPersonRows($aBackupDump), nxDiffBuildPersonRows($aCurrentDump), $aPersonFields);
                $aSubjectDiff = nxDiffCompareEntityRows(nxDiffBuildSubjectRows($aBackupDump), nxDiffBuildSubjectRows($aCurrentDump), $aSubjectFields);
                $aStructureDiff = nxDiffCompareStructure($aBackupDump, $aCurrentDump);
                $aTableDiff = nxDiffCompareTableRows($aBackupDump, $aCurrentDump);
                $blCompared = true;
                $blHasDifferences = $aStructureDiff
                    || $aPersonDiff["missing"]
                    || $aPersonDiff["added"]
                    || $aPersonDiff["changed"]
                    || $aSubjectDiff["missing"]
                    || $aSubjectDiff["added"]
                    || $aSubjectDiff["changed"];
                foreach ($aTableDiff as $aRow) {
                    if ((int)$aRow["missing_rows"] > 0 || (int)$aRow["added_rows"] > 0 || (int)$aRow["changed_rows"] > 0) {
                        $blHasDifferences = true;
                        break;
                    }
                }
            }
        }
    } catch (Exception $oException) {
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
  <meta name="author" content="Petr Cervinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="<?php echo nxHtml(nxGetLockedViewportContent()); ?>">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo nxHtml(getExPageTitleText("Database Difference", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
  </p>
  <h1>Database Difference</h1>
  <form action="<?php echo htmlspecialchars($sScriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
    <label for="database-backup">Database Backup</label>
    <input type="file" id="database-backup" name="database_backup" accept=".sql,application/sql,text/plain" required>
    <button type="submit">Compare Backup</button>
  </form>
<?php

if ($sUploadError != "") {
    echo "  <p class=\"consistency-status consistency-status-error\">" . nxHtml($sUploadError) . "</p>\n";
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
    nxDiffRenderEntityTable($aPersonDiff["missing"], $aEntityColumns);
    echo "  <h2>Persons added in current database (" . count($aPersonDiff["added"]) . ")</h2>\n";
    nxDiffRenderEntityTable($aPersonDiff["added"], $aEntityColumns);
    echo "  <h2>Changed persons (" . count($aPersonDiff["changed"]) . ")</h2>\n";
    nxDiffRenderChangedEntityTable($aPersonDiff["changed"]);

    $aSubjectColumns = array(
        "subject_id" => "Subject ID",
        "name" => "Name",
        "subject_type" => "Type",
        "is_active" => "Active",
        "legacy_id" => "Legacy ID"
    );
    echo "  <h2>Subjects missing from current database (" . count($aSubjectDiff["missing"]) . ")</h2>\n";
    nxDiffRenderEntityTable($aSubjectDiff["missing"], $aSubjectColumns);
    echo "  <h2>Subjects added in current database (" . count($aSubjectDiff["added"]) . ")</h2>\n";
    nxDiffRenderEntityTable($aSubjectDiff["added"], $aSubjectColumns);
    echo "  <h2>Changed subjects (" . count($aSubjectDiff["changed"]) . ")</h2>\n";
    nxDiffRenderChangedEntityTable($aSubjectDiff["changed"]);

    echo "  <h2>Structure differences (" . count($aStructureDiff) . ")</h2>\n";
    nxDiffRenderEntityTable($aStructureDiff, array("table" => "Table", "difference" => "Difference"));

    $aChangedTableRows = array();
    foreach ($aTableDiff as $aRow) {
        if ((int)$aRow["missing_rows"] > 0 || (int)$aRow["added_rows"] > 0 || (int)$aRow["changed_rows"] > 0) {
            $aChangedTableRows[] = $aRow;
        }
    }
    echo "  <h2>Data table summary (" . count($aChangedTableRows) . ")</h2>\n";
    nxDiffRenderEntityTable($aChangedTableRows, array(
        "table" => "Table",
        "backup_rows" => "Backup Rows",
        "current_rows" => "Current Rows",
        "missing_rows" => "Missing",
        "added_rows" => "Added",
        "changed_rows" => "Changed"
    ));
}

echo nxRenderAdminScript($sBaseUrl);

?>
</body>
</html>
