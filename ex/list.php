<?php

include "main.php";


$blCanEdit = isExFullAccessAllowed($aAllowedIps);
requireExViewAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$aFullListSettingsDefaults = array(
    "show_inactive_subjects" => 1,
    "show_inactive_nicknames" => 1,
    "show_inactive_addresses" => 1,
    "show_inactive_contacts" => 1,
    "show_inactive_notes" => 1
);
$aFullListSettings = array();

if (!isset($_SESSION["ex_full_list_settings"]) || !is_array($_SESSION["ex_full_list_settings"])) {
    $_SESSION["ex_full_list_settings"] = array();
}
foreach ($aFullListSettingsDefaults as $sFullListSettingName => $iFullListSettingDefault) {
    if (isset($_SESSION["ex_full_list_settings"][$sFullListSettingName])) {
        $aFullListSettings[$sFullListSettingName] = (int)$_SESSION["ex_full_list_settings"][$sFullListSettingName] === 1 ? 1 : 0;
    } else {
        $aFullListSettings[$sFullListSettingName] = $iFullListSettingDefault;
    }
}
$aFullListSettings = nxApplyExCountrySettings($aFullListSettings);

function nxGetFullListComplexFilterFields() {
    return array(
        "subject_type" => array("label" => "Type", "sql" => "`subject_type`", "value_type" => "text"),
        "subject_name" => array("label" => "Name", "sql" => "`subject_name`"),
        "title_before" => array("label" => "Title Before", "sql" => "`title_before`", "scope_sql" => "`subject_type` = 'person'"),
        "first_name" => array("label" => "First Name", "sql" => "`first_name`", "scope_sql" => "`subject_type` = 'person'"),
        "middle_name" => array("label" => "Middle Name", "sql" => "`middle_name`", "scope_sql" => "`subject_type` = 'person'"),
        "last_name" => array("label" => "Last Name", "sql" => "`last_name`", "scope_sql" => "`subject_type` = 'person'"),
        "title_after" => array("label" => "Title After", "sql" => "`title_after`", "scope_sql" => "`subject_type` = 'person'"),
        "birth_name" => array("label" => "Birth Name", "sql" => "`birth_name`", "scope_sql" => "`subject_type` = 'person'"),
        "birth_number" => array("label" => "Birth Number", "sql" => "`birth_number`", "value_type" => "birth_number", "scope_sql" => "`subject_type` = 'person'"),
        "birth_date" => array("label" => "Birth Date", "sql" => "`birth_date`", "value_type" => "date", "scope_sql" => "`subject_type` = 'person'"),
        "death_date" => array("label" => "Death Date", "sql" => "`death_date`", "value_type" => "date", "scope_sql" => "`subject_type` = 'person'"),
        "birthday_served_at" => array("label" => "Birthday Served At", "sql" => "`birthday_served_at`", "value_type" => "datetime", "scope_sql" => "`subject_type` = 'person'"),
        "inter_served_at" => array("label" => "Interaction Served At", "sql" => "`inter_served_at`", "value_type" => "datetime", "scope_sql" => "`subject_type` = 'person'"),
        "nicknames" => array("label" => "Nicknames", "sql" => "`nicknames`"),
        "addresses" => array("label" => "Addresses", "sql" => "`addresses`"),
        "address_type" => array("label" => "Address Type", "address_column" => "address_type", "value_type" => "address_type"),
        "organization_name" => array("label" => "Organization Name", "address_column" => "organization_name"),
        "department_name" => array("label" => "Department Name", "address_column" => "department_name"),
        "care_of" => array("label" => "Care Of", "address_column" => "care_of"),
        "street_name" => array("label" => "Street Name", "address_column" => "street_name"),
        "house_number" => array("label" => "House Number", "address_column" => "house_number"),
        "evidence_number" => array("label" => "Evidence Number", "address_column" => "evidence_number"),
        "orientation_number" => array("label" => "Orientation Number", "address_column" => "orientation_number"),
        "orientation_suffix" => array("label" => "Orientation Suffix", "address_column" => "orientation_suffix"),
        "address_line2" => array("label" => "Address Line 2", "address_column" => "address_line2"),
        "city" => array("label" => "City", "address_column" => "city"),
        "city_part" => array("label" => "City Part", "address_column" => "city_part"),
        "postal_code" => array("label" => "Postal Code", "address_column" => "postal_code"),
        "region" => array("label" => "Region", "address_column" => "region"),
        "country" => array("label" => "Country", "address_column" => "country", "value_type" => "country"),
        "address_is_primary" => array("label" => "Address Is Primary", "address_column" => "is_primary", "value_type" => "boolean"),
        "address_is_active" => array("label" => "Address Is Active", "address_column" => "is_active", "value_type" => "boolean"),
        "address_note" => array("label" => "Address Note", "address_column" => "note"),
        "contacts" => array("label" => "Contacts", "sql" => "`contacts`"),
        "group_names" => array("label" => "Groups", "sql" => "`group_names`", "value_type" => "group"),
        "notes" => array("label" => "Subject Notes", "sql" => "`notes`"),
        "is_active" => array("label" => "Active", "sql" => "`is_active`", "value_type" => "boolean"),
        "created_at" => array("label" => "Created At", "sql" => "`created_at`", "value_type" => "datetime")
    );
}

function nxGetFullListComplexFilterOperators() {
    return array(
        "equals" => array("label" => "is equal to", "needs_value" => 1),
        "not_equals" => array("label" => "is not equal to", "needs_value" => 1),
        "is_lower_than" => array("label" => "is lower than", "needs_value" => 1),
        "is_lower_than_or_equal" => array("label" => "is lower than or equal to", "needs_value" => 1),
        "is_greater_than" => array("label" => "is greater than", "needs_value" => 1),
        "is_greater_than_or_equal" => array("label" => "is greater than or equal to", "needs_value" => 1),
        "contains" => array("label" => "contains", "needs_value" => 1),
        "not_contains" => array("label" => "does not contain", "needs_value" => 1),
        "starts" => array("label" => "starts with", "needs_value" => 1),
        "not_starts" => array("label" => "does not start with", "needs_value" => 1),
        "ends" => array("label" => "ends with", "needs_value" => 1),
        "not_ends" => array("label" => "does not end with", "needs_value" => 1),
        "empty" => array("label" => "is empty", "needs_value" => 0),
        "not_empty" => array("label" => "is not empty", "needs_value" => 0)
    );
}

function nxGetDefaultFullListComplexFilter() {
    return array(
        "match" => "all",
        "conditions" => array()
    );
}

function nxGetDefaultFullListComplexFilterDraft() {
    return array(
        "match" => "all",
        "conditions" => array(
            array(
                "field" => "subject_name",
                "operator" => "contains",
                "value" => ""
            )
        )
    );
}

function nxNormalizeFullListComplexFilter($aPayload, $aFields, $aOperators) {
    $aFilter = nxGetDefaultFullListComplexFilter();

    if (isset($aPayload["match"]) && (string)$aPayload["match"] === "any") {
        $aFilter["match"] = "any";
    } elseif (isset($aPayload["complex_filter_match"]) && (string)$aPayload["complex_filter_match"] === "any") {
        $aFilter["match"] = "any";
    }
    if (isset($aPayload["conditions"]) && is_array($aPayload["conditions"])) {
        $iCount = 0;
        foreach ($aPayload["conditions"] as $aCondition) {
            if ($iCount >= 25) {
                break;
            }
            $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
            $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
            $sValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
            if (!isset($aFields[$sField])) {
                continue;
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] === "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                continue;
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
            $iCount += 1;
        }
        return $aFilter;
    }

    $aInputFields = isset($aPayload["complex_filter_field"]) && is_array($aPayload["complex_filter_field"]) ? $aPayload["complex_filter_field"] : array();
    $aInputOperators = isset($aPayload["complex_filter_operator"]) && is_array($aPayload["complex_filter_operator"]) ? $aPayload["complex_filter_operator"] : array();
    $aInputValues = isset($aPayload["complex_filter_value"]) && is_array($aPayload["complex_filter_value"]) ? $aPayload["complex_filter_value"] : array();
    $iCount = max(count($aInputFields), count($aInputOperators), count($aInputValues));
    for ($iI = 0; $iI < $iCount && $iI < 25; $iI += 1) {
        $sField = isset($aInputFields[$iI]) ? (string)$aInputFields[$iI] : "";
        $sOperator = isset($aInputOperators[$iI]) ? (string)$aInputOperators[$iI] : "";
        $sValue = isset($aInputValues[$iI]) ? (string)$aInputValues[$iI] : "";
        if (!isset($aFields[$sField])) {
            continue;
        }
        if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] === "boolean") {
            $sOperator = "equals";
        } elseif (!isset($aOperators[$sOperator])) {
            continue;
        }
        if (empty($aOperators[$sOperator]["needs_value"])) {
            $sValue = "";
        }
        $aFilter["conditions"][] = array(
            "field" => $sField,
            "operator" => $sOperator,
            "value" => $sValue
        );
    }
    return $aFilter;
}

function nxNormalizeFullListComplexFilterDraft($aPayload, $aFields, $aOperators) {
    $aFilter = nxGetDefaultFullListComplexFilterDraft();
    $aFilter["conditions"] = array();

    if (isset($aPayload["match"]) && (string)$aPayload["match"] === "any") {
        $aFilter["match"] = "any";
    } elseif (isset($aPayload["complex_filter_match"]) && (string)$aPayload["complex_filter_match"] === "any") {
        $aFilter["match"] = "any";
    }
    if (isset($aPayload["conditions"]) && is_array($aPayload["conditions"])) {
        $iCount = 0;
        foreach ($aPayload["conditions"] as $aCondition) {
            if ($iCount >= 25) {
                break;
            }
            $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
            $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
            $sValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
            if ($sField === "" || $sOperator === "") {
                $aFilter["conditions"][] = array(
                    "field" => $sField,
                    "operator" => $sOperator,
                    "value" => $sValue
                );
                $iCount += 1;
                continue;
            }
            if (!isset($aFields[$sField])) {
                $sField = "subject_name";
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] === "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                $sOperator = "contains";
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
            $iCount += 1;
        }
    } else {
        $aInputFields = isset($aPayload["complex_filter_field"]) && is_array($aPayload["complex_filter_field"]) ? $aPayload["complex_filter_field"] : array();
        $aInputOperators = isset($aPayload["complex_filter_operator"]) && is_array($aPayload["complex_filter_operator"]) ? $aPayload["complex_filter_operator"] : array();
        $aInputValues = isset($aPayload["complex_filter_value"]) && is_array($aPayload["complex_filter_value"]) ? $aPayload["complex_filter_value"] : array();
        $iCount = max(count($aInputFields), count($aInputOperators), count($aInputValues));
        for ($iI = 0; $iI < $iCount && $iI < 25; $iI += 1) {
            $sField = isset($aInputFields[$iI]) ? (string)$aInputFields[$iI] : "";
            $sOperator = isset($aInputOperators[$iI]) ? (string)$aInputOperators[$iI] : "";
            $sValue = isset($aInputValues[$iI]) ? (string)$aInputValues[$iI] : "";
            if ($sField === "" || $sOperator === "") {
                $aFilter["conditions"][] = array(
                    "field" => $sField,
                    "operator" => $sOperator,
                    "value" => $sValue
                );
                continue;
            }
            if (!isset($aFields[$sField])) {
                $sField = "subject_name";
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] === "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                $sOperator = "contains";
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
        }
    }
    if (count($aFilter["conditions"]) === 0) {
        $aFilter = nxGetDefaultFullListComplexFilterDraft();
    }
    return $aFilter;
}

function nxEscapeFullListComplexFilterLike($sValue) {
    return str_replace(array("!", "%", "_"), array("!!", "!%", "!_"), $sValue);
}

function nxNormalizeFullListComplexFilterSqlValue($aField, $sValue) {
    if (isset($aField["value_type"]) && (string)$aField["value_type"] === "boolean") {
        $sNormalized = strtolower(trim((string)$sValue));
        if ($sNormalized === "0" || $sNormalized === "false" || $sNormalized === "no" || $sNormalized === "off") {
            return "0";
        }
        return "1";
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] === "birth_number") {
        $sNormalized = nxNormalizeBirthNumber($sValue);
        return $sNormalized === false ? (string)$sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] === "country") {
        return nxCountryNameToCode($sValue);
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] === "address_type") {
        $sNormalized = strtolower(trim((string)$sValue));
        foreach (nxGetAddressTypes() as $sAddressType) {
            if ($sNormalized === $sAddressType || $sNormalized === strtolower(nxAddressTypeLabel($sAddressType))) {
                return $sAddressType;
            }
        }
        return $sNormalized;
    }
    return (string)$sValue;
}

function nxBuildFullListComplexAddressFilterSql($sColumn, $sOperator, $sParam, $sValue) {
    $sColumnSql = "COALESCE(CAST(a_cf.`" . $sColumn . "` AS CHAR), '')";
    $sColumnLowerSql = "LOWER(" . $sColumnSql . ")";
    $sNonEmptySql = $sColumnSql . " <> ''";
    $sHasRowSql = "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id)";
    $sHasValueSql = "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . ")";
    $sExactSql = $sHasValueSql
        . " AND NOT EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " <> LOWER(:" . $sParam . "))";

    if ($sOperator === "empty") {
        return $sHasRowSql . " AND NOT " . $sHasValueSql;
    }
    if ($sOperator === "not_empty") {
        return $sHasValueSql;
    }
    if ($sOperator === "equals") {
        if ((string)$sValue === "") {
            return $sHasRowSql . " AND NOT " . $sHasValueSql;
        }
        return $sExactSql;
    }
    if ($sOperator === "not_equals") {
        if ((string)$sValue === "") {
            return $sHasValueSql;
        }
        return $sHasRowSql . " AND NOT (" . $sExactSql . ")";
    }
    if ($sOperator === "is_lower_than") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " < LOWER(:" . $sParam . "))";
    }
    if ($sOperator === "is_lower_than_or_equal") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " <= LOWER(:" . $sParam . "))";
    }
    if ($sOperator === "is_greater_than") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " > LOWER(:" . $sParam . "))";
    }
    if ($sOperator === "is_greater_than_or_equal") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " >= LOWER(:" . $sParam . "))";
    }
    if ($sOperator === "contains") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator === "not_contains") {
        return $sHasRowSql . " AND NOT EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator === "starts") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator === "not_starts") {
        return $sHasRowSql . " AND NOT EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator === "ends") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator === "not_ends") {
        return $sHasRowSql . " AND NOT EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    return "";
}

function nxApplyFullListComplexFilterScopeSql($sSql, $aField) {
    if ($sSql !== "" && isset($aField["scope_sql"]) && (string)$aField["scope_sql"] !== "") {
        return "(" . (string)$aField["scope_sql"] . " AND " . $sSql . ")";
    }
    return $sSql;
}

function nxBuildFullListComplexFilterSql($aFilter, $aFields, $aOperators) {
    $aSql = array();
    $aParams = array();
    $iIndex = 0;

    if (!is_array($aFilter) || empty($aFilter["conditions"]) || !is_array($aFilter["conditions"])) {
        return array("sql" => "", "params" => array());
    }
    foreach ($aFilter["conditions"] as $aCondition) {
        $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
        $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
        $sValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
        if (!isset($aFields[$sField]) || !isset($aOperators[$sOperator])) {
            continue;
        }
        $sValue = nxNormalizeFullListComplexFilterSqlValue($aFields[$sField], $sValue);
        if (isset($aFields[$sField]["address_column"])) {
            $sParam = "complex_filter_" . $iIndex;
            $sAddressSql = nxBuildFullListComplexAddressFilterSql($aFields[$sField]["address_column"], $sOperator, $sParam, $sValue);
            if ($sAddressSql === "") {
                continue;
            }
            $aSql[] = $sAddressSql;
            if ($sOperator !== "empty" && $sOperator !== "not_empty") {
                if ($sOperator === "contains" || $sOperator === "not_contains") {
                    $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator === "starts" || $sOperator === "not_starts") {
                    $aParams[$sParam] = nxEscapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator === "ends" || $sOperator === "not_ends") {
                    $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue);
                } else {
                    $aParams[$sParam] = $sValue;
                }
                $iIndex += 1;
            }
            continue;
        }
        if (isset($aFields[$sField]["value_type"]) && $aFields[$sField]["value_type"] === "datetime") {
            $sSqlValueBase = "DATE_FORMAT(" . $aFields[$sField]["sql"] . ", '%Y-%m-%dT%H:%i')";
        } else {
            $sSqlValueBase = "CAST(" . $aFields[$sField]["sql"] . " AS CHAR)";
        }
        $sSqlValue = "LOWER(COALESCE(" . $sSqlValueBase . ", ''))";
        $sSqlTrimmedValue = "COALESCE(CAST(" . $aFields[$sField]["sql"] . " AS CHAR), '')";
        $sConditionSql = "";
        if ($sOperator === "empty") {
            $sConditionSql = $sSqlTrimmedValue . " = ''";
        } elseif ($sOperator === "not_empty") {
            $sConditionSql = $sSqlTrimmedValue . " <> ''";
        } else {
            $sParam = "complex_filter_" . $iIndex;
            if ($sOperator === "equals") {
                $sConditionSql = $sSqlValue . " = LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator === "not_equals") {
                $sConditionSql = $sSqlValue . " <> LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator === "is_lower_than") {
                $sConditionSql = $sSqlValue . " < LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator === "is_lower_than_or_equal") {
                $sConditionSql = $sSqlValue . " <= LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator === "is_greater_than") {
                $sConditionSql = $sSqlValue . " > LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator === "is_greater_than_or_equal") {
                $sConditionSql = $sSqlValue . " >= LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator === "contains") {
                $sConditionSql = $sSqlValue . " LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator === "not_contains") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator === "starts") {
                $sConditionSql = $sSqlValue . " LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = nxEscapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator === "not_starts") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = nxEscapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator === "ends") {
                $sConditionSql = $sSqlValue . " LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue);
            } elseif ($sOperator === "not_ends") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue);
            }
            $iIndex += 1;
        }
        if ($sConditionSql !== "") {
            $aSql[] = nxApplyFullListComplexFilterScopeSql($sConditionSql, $aFields[$sField]);
        }
    }
    if (count($aSql) === 0) {
        return array("sql" => "", "params" => array());
    }
    return array(
        "sql" => "(" . implode(!empty($aFilter["match"]) && $aFilter["match"] === "any" ? ") OR (" : ") AND (", $aSql) . ")",
        "params" => $aParams
    );
}

function nxRenderFullListComplexFilterFieldOptions($aFields, $sSelected) {
    $sHtml = "<option value=\"\" data-value-type=\"text\"" . ($sSelected === "" ? " selected" : "") . "></option>";

    foreach ($aFields as $sField => $aField) {
        $sValueType = isset($aField["value_type"]) ? (string)$aField["value_type"] : "text";
        $sHtml .= "<option value=\"" . nxHtml($sField) . "\" data-value-type=\"" . nxHtml($sValueType) . "\"" . ($sSelected === $sField ? " selected" : "") . ">" . nxHtml($aField["label"]) . "</option>";
    }
    return $sHtml;
}

function nxRenderFullListComplexFilterOperatorOptions($aOperators, $sSelected) {
    $sHtml = "<option value=\"\" data-needs-value=\"1\"" . ($sSelected === "" ? " selected" : "") . "></option>";

    foreach ($aOperators as $sOperator => $aOperator) {
        $sHtml .= "<option value=\"" . nxHtml($sOperator) . "\" data-needs-value=\"" . (!empty($aOperator["needs_value"]) ? "1" : "0") . "\"" . ($sSelected === $sOperator ? " selected" : "") . ">" . nxHtml($aOperator["label"]) . "</option>";
    }
    return $sHtml;
}

$aFullListComplexFilterFields = nxGetFullListComplexFilterFields();
$aFullListComplexFilterOperators = nxGetFullListComplexFilterOperators();
$aFullListComplexFilter = nxGetDefaultFullListComplexFilter();
$aFullListComplexFilterDraft = nxGetDefaultFullListComplexFilterDraft();

if (isset($_SESSION["ex_full_list_complex_filter"]) && is_array($_SESSION["ex_full_list_complex_filter"])) {
    $aFullListComplexFilter = nxNormalizeFullListComplexFilter($_SESSION["ex_full_list_complex_filter"], $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
}
if (isset($_SESSION["ex_full_list_complex_filter_draft"]) && is_array($_SESSION["ex_full_list_complex_filter_draft"])) {
    $aFullListComplexFilterDraft = nxNormalizeFullListComplexFilterDraft($_SESSION["ex_full_list_complex_filter_draft"], $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
} elseif (count($aFullListComplexFilter["conditions"]) > 0) {
    $aFullListComplexFilterDraft = nxNormalizeFullListComplexFilterDraft($aFullListComplexFilter, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    requireExCsrfToken();
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "save_full_list_settings") {
    foreach ($aFullListSettingsDefaults as $sFullListSettingName => $iFullListSettingDefault) {
        $aFullListSettings[$sFullListSettingName] = isset($_POST[$sFullListSettingName]) && (string)$_POST[$sFullListSettingName] === "1" ? 1 : 0;
    }
    $aFullListSettings = nxSaveExCountrySettings($aFullListSettings, $_POST);
    $_SESSION["ex_full_list_settings"] = nxRemoveExCountrySettings($aFullListSettings);
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "save_full_list_complex_filter") {
    $aFullListComplexFilterPayload = nxGetFullListComplexFilterPostPayload();
    $aFullListComplexFilterDraft = nxNormalizeFullListComplexFilterDraft($aFullListComplexFilterPayload, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $aFullListComplexFilter = nxNormalizeFullListComplexFilter($aFullListComplexFilterPayload, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $_SESSION["ex_full_list_complex_filter"] = $aFullListComplexFilter;
    $_SESSION["ex_full_list_complex_filter_draft"] = $aFullListComplexFilterDraft;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "save_full_list_complex_filter_draft") {
    $aFullListComplexFilterDraft = nxNormalizeFullListComplexFilterDraft(nxGetFullListComplexFilterPostPayload(), $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $_SESSION["ex_full_list_complex_filter_draft"] = $aFullListComplexFilterDraft;
    session_write_close();
    nxSendJsonAndExit(array("success" => true));
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "reset_full_list_complex_filter") {
    $aFullListComplexFilter = nxGetDefaultFullListComplexFilter();
    $_SESSION["ex_full_list_complex_filter"] = $aFullListComplexFilter;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}


if (!$blCanEdit && $_SERVER["REQUEST_METHOD"] === "POST") {
    nxSendJsonAndExit(array("success" => false, "message" => "Editing is not allowed from this location."), 403);
}


function nxGetPostedContactValue() {
    return nxGetPostedValue("contact_value");
}


function nxGetFullListComplexFilterPostPayload() {
    $aPayload = $_POST;
    if (isset($_POST["complex_filter_value_b64"]) && is_array($_POST["complex_filter_value_b64"])) {
        $aPayload["complex_filter_value"] = nxGetPostedValues("complex_filter_value");
    }
    return $aPayload;
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "get_subject") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    try {
        $aSubject = nxFetchSubjectEditorData($oPdo, $iSubjectId);
        if (!$aSubject) {
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        nxSendJsonAndExit(array("success" => true, "subject" => $aSubject));
    } catch (Exception $oException) {
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "get_subject_portal_user") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    try {
        $aSubject = nxFetchSubjectPortalEditorData($oPdo, $iSubjectId);
        if (!$aSubject) {
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        nxSendJsonAndExit(array("success" => true, "subject" => $aSubject));
    } catch (Exception $oException) {
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_subject_portal_user") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $aPermissionKeys = isset($_POST["permissions"]) && is_array($_POST["permissions"]) ? $_POST["permissions"] : array();
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id, subject_type FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        $aSubjectRow = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aSubjectRow) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $aPayload = array(
            "portal_user_enabled" => isset($_POST["portal_user_enabled"]) && (string)$_POST["portal_user_enabled"] === "1" ? "1" : "0",
            "portal_user_name" => nxGetPostedTrimmedValue("portal_user_name"),
            "portal_password" => nxGetPostedValue("portal_password"),
            "portal_user_active" => isset($_POST["portal_user_active"]) && (string)$_POST["portal_user_active"] === "1" ? "1" : "0",
            "portal_permission_keys" => $aPermissionKeys
        );
        nxSaveSubjectPortalAccess($oPdo, $iSubjectId, (string)$aSubjectRow["subject_type"], $aPayload);
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() === "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected user name already exists."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_subject") {
    $sPayload = nxGetPostedValue("subject_payload");
    $aPayload = $sPayload !== "" ? json_decode($sPayload, true) : null;
    if (!is_array($aPayload)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject data."), 400);
    }

    $iSubjectId = isset($aPayload["subject_id"]) ? (int)$aPayload["subject_id"] : 0;
    $sSubjectType = nxPayloadValue($aPayload, "subject_type");
    $sBirthDate = nxPayloadValue($aPayload, "birth_date");
    $sDeathDate = nxPayloadValue($aPayload, "death_date");
    $sBirthNumber = nxNormalizeBirthNumber(nxPayloadValue($aPayload, "birth_number"));
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sSubjectType !== "" && !in_array($sSubjectType, nxGetSubjectTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject type."), 400);
    }
    if ($sBirthDate !== "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sBirthDate)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Birth date must use YYYY-MM-DD."), 400);
    }
    if ($sDeathDate !== "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDeathDate)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Death date must use YYYY-MM-DD."), 400);
    }
    if ($sBirthNumber === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Birth number must contain 9 or 10 digits."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id, subject_type FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        $aSubjectRow = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aSubjectRow) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        if ($sSubjectType !== "" && $sSubjectType !== (string)$aSubjectRow["subject_type"]) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject type cannot be changed."), 409);
        }
        $sEffectiveSubjectType = (string)$aSubjectRow["subject_type"];

        $oStatement = $oPdo->prepare("UPDATE ex_subjects SET is_active = :is_active WHERE id = :subject_id");
        $oStatement->execute(array(
            "is_active" => nxPayloadFlag($aPayload, "is_active"),
            "subject_id" => $iSubjectId
        ));

        $sSubjectName = nxPayloadValue($aPayload, "subject_name_value");
        if ($sEffectiveSubjectType === "person" || $sSubjectName === "") {
            $oStatement = $oPdo->prepare("DELETE FROM ex_subject_names WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
        } else {
            $oStatement = $oPdo->prepare("INSERT INTO ex_subject_names (subject_id, name) VALUES (:subject_id, :name) ON DUPLICATE KEY UPDATE name = VALUES(name)");
            $oStatement->execute(array("subject_id" => $iSubjectId, "name" => $sSubjectName));
        }

        if ($sEffectiveSubjectType !== "person") {
            $oStatement = $oPdo->prepare("DELETE FROM ex_persons WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
        } else {
            $aPersonValues = array(
                "title_before" => nxDbValue(nxPayloadValue($aPayload, "title_before")),
                "first_name" => nxDbValue(nxPayloadValue($aPayload, "first_name")),
                "middle_name" => nxDbValue(nxPayloadValue($aPayload, "middle_name")),
                "last_name" => nxDbValue(nxPayloadValue($aPayload, "last_name")),
                "title_after" => nxDbValue(nxPayloadValue($aPayload, "title_after")),
                "birth_name" => nxDbValue(nxPayloadValue($aPayload, "birth_name")),
                "birth_number" => nxDbValue($sBirthNumber),
                "birth_date" => $sBirthDate !== "" ? $sBirthDate : null,
                "death_date" => $sDeathDate !== "" ? $sDeathDate : null
            );
            $blHasPersonValues = false;
            foreach ($aPersonValues as $mValue) {
                if ($mValue !== null) {
                    $blHasPersonValues = true;
                    break;
                }
            }
            $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_persons WHERE subject_id = :subject_id");
            $oStatement->execute(array("subject_id" => $iSubjectId));
            $blHasPersonRow = (bool)$oStatement->fetch(PDO::FETCH_ASSOC);
            if ($blHasPersonValues || $blHasPersonRow) {
                if ($blHasPersonRow && !$blHasPersonValues) {
                    $oStatement = $oPdo->prepare("DELETE FROM ex_persons WHERE subject_id = :subject_id");
                    $oStatement->execute(array("subject_id" => $iSubjectId));
                } elseif ($blHasPersonRow) {
                    $oStatement = $oPdo->prepare("UPDATE ex_persons SET title_before = :title_before, first_name = :first_name, middle_name = :middle_name, last_name = :last_name, title_after = :title_after, birth_name = :birth_name, birth_number = :birth_number, birth_date = :birth_date, death_date = :death_date WHERE subject_id = :subject_id");
                    $aPersonValues["subject_id"] = $iSubjectId;
                    $oStatement->execute($aPersonValues);
                } else {
                    $oStatement = $oPdo->prepare("INSERT INTO ex_persons (subject_id, title_before, first_name, middle_name, last_name, title_after, birth_name, birth_number, birth_date, death_date) VALUES (:subject_id, :title_before, :first_name, :middle_name, :last_name, :title_after, :birth_name, :birth_number, :birth_date, :death_date)");
                    $aPersonValues["subject_id"] = $iSubjectId;
                    $oStatement->execute($aPersonValues);
                }
            }
        }

        $oPdo->commit();

        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create_subject") {
    $sPayload = nxGetPostedValue("subject_payload");
    $aPayload = $sPayload !== "" ? json_decode($sPayload, true) : null;
    if (!is_array($aPayload)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject data."), 400);
    }

    $sSubjectType = nxPayloadValue($aPayload, "subject_type");
    $sBirthDate = nxPayloadValue($aPayload, "birth_date");
    $sDeathDate = nxPayloadValue($aPayload, "death_date");
    $sBirthNumber = nxNormalizeBirthNumber(nxPayloadValue($aPayload, "birth_number"));
    if (!in_array($sSubjectType, nxGetSubjectTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject type."), 400);
    }
    if ($sBirthDate !== "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sBirthDate)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Birth date must use YYYY-MM-DD."), 400);
    }
    if ($sDeathDate !== "" && !preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sDeathDate)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Death date must use YYYY-MM-DD."), 400);
    }
    if ($sBirthNumber === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Birth number must contain 9 or 10 digits."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("INSERT INTO ex_subjects (subject_type, is_active) VALUES (:subject_type, :is_active)");
        $oStatement->execute(array(
            "subject_type" => $sSubjectType,
            "is_active" => nxPayloadFlag($aPayload, "is_active")
        ));
        $iSubjectId = (int)$oPdo->lastInsertId();

        $sSubjectName = nxPayloadValue($aPayload, "subject_name_value");
        if ($sSubjectType !== "person" && $sSubjectName !== "") {
            $oStatement = $oPdo->prepare("INSERT INTO ex_subject_names (subject_id, name) VALUES (:subject_id, :name)");
            $oStatement->execute(array("subject_id" => $iSubjectId, "name" => $sSubjectName));
        }

        if ($sSubjectType === "person") {
            $aPersonValues = array(
                "title_before" => nxDbValue(nxPayloadValue($aPayload, "title_before")),
                "first_name" => nxDbValue(nxPayloadValue($aPayload, "first_name")),
                "middle_name" => nxDbValue(nxPayloadValue($aPayload, "middle_name")),
                "last_name" => nxDbValue(nxPayloadValue($aPayload, "last_name")),
                "title_after" => nxDbValue(nxPayloadValue($aPayload, "title_after")),
                "birth_name" => nxDbValue(nxPayloadValue($aPayload, "birth_name")),
                "birth_number" => nxDbValue($sBirthNumber),
                "birth_date" => $sBirthDate !== "" ? $sBirthDate : null,
                "death_date" => $sDeathDate !== "" ? $sDeathDate : null
            );
            $blHasPersonValues = false;
            foreach ($aPersonValues as $mValue) {
                if ($mValue !== null) {
                    $blHasPersonValues = true;
                    break;
                }
            }
            if ($blHasPersonValues) {
                $oStatement = $oPdo->prepare("INSERT INTO ex_persons (subject_id, title_before, first_name, middle_name, last_name, title_after, birth_name, birth_number, birth_date, death_date) VALUES (:subject_id, :title_before, :first_name, :middle_name, :last_name, :title_after, :birth_name, :birth_number, :birth_date, :death_date)");
                $aPersonValues["subject_id"] = $iSubjectId;
                $oStatement->execute($aPersonValues);
            }
        }

        $oPdo->commit();

        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_subject_nickname") {
    $iNicknameId = isset($_POST["nickname_id"]) ? (int)$_POST["nickname_id"] : 0;
    $sNickname = nxGetPostedTrimmedValue("nickname");
    $sContext = nxGetPostedTrimmedValue("context");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] === "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;

    if ($iNicknameId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid nickname."), 400);
    }
    if ($sNickname === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Nickname is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_nicknames WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNicknameId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Nickname was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_subject_nicknames SET nickname = :nickname, context = :context, is_primary = :is_primary, is_active = :is_active, note = :note WHERE id = :id");
        $oStatement->execute(array(
            "nickname" => $sNickname,
            "context" => $sContext !== "" ? $sContext : null,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote !== "" ? $sNote : null,
            "id" => $iNicknameId
        ));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create_subject_nickname") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sNickname = nxGetPostedTrimmedValue("nickname");
    $sContext = nxGetPostedTrimmedValue("context");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] === "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sNickname === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Nickname is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_nicknames (subject_id, nickname, context, is_primary, is_active, note) VALUES (:subject_id, :nickname, :context, :is_primary, :is_active, :note)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "nickname" => $sNickname,
            "context" => $sContext !== "" ? $sContext : null,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote !== "" ? $sNote : null
        ));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_subject_address") {
    $iAddressId = isset($_POST["address_id"]) ? (int)$_POST["address_id"] : 0;
    $sAddressType = nxGetPostedTrimmedValue("address_type");
    $sOrganizationName = nxGetPostedTrimmedValue("organization_name");
    $sDepartmentName = nxGetPostedTrimmedValue("department_name");
    $sCareOf = nxGetPostedTrimmedValue("care_of");
    $sStreetName = nxGetPostedTrimmedValue("street_name");
    $sHouseNumber = nxGetPostedTrimmedValue("house_number");
    $sEvidenceNumber = nxGetPostedTrimmedValue("evidence_number");
    $sOrientationNumber = nxGetPostedTrimmedValue("orientation_number");
    $sOrientationSuffix = nxGetPostedTrimmedValue("orientation_suffix");
    $sAddressLine2 = nxGetPostedTrimmedValue("address_line2");
    $sCity = nxGetPostedTrimmedValue("city");
    $sCityPart = nxGetPostedTrimmedValue("city_part");
    $sPostalCode = nxGetPostedTrimmedValue("postal_code");
    $sRegion = nxGetPostedTrimmedValue("region");
    $sCountry = nxGetPostedTrimmedValue("country");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] === "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;
    if ($sCountry !== "") {
        $sCountry = strtoupper($sCountry);
    }

    if ($iAddressId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }
    if ($sAddressType === "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, nxGetAddressTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    if ($sCountry === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry !== "" && !in_array($sCountry, nxGetCountryCodes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = nxNormalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName === "" && $sDepartmentName === "" && $sCareOf === "" && $sStreetName === "" && $sHouseNumber === "" && $sEvidenceNumber === "" && $sOrientationNumber === "" && $sOrientationSuffix === "" && $sAddressLine2 === "" && $sCity === "" && $sCityPart === "" && $sPostalCode === "" && $sRegion === "" && $sCountry === "" && $sNote === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_addresses WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iAddressId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_subject_addresses SET address_type = :address_type, organization_name = :organization_name, department_name = :department_name, care_of = :care_of, street_name = :street_name, house_number = :house_number, evidence_number = :evidence_number, orientation_number = :orientation_number, orientation_suffix = :orientation_suffix, address_line2 = :address_line2, city = :city, city_part = :city_part, postal_code = :postal_code, region = :region, country = :country, is_primary = :is_primary, is_active = :is_active, note = :note WHERE id = :id");
        $oStatement->execute(array(
            "address_type" => $sAddressType,
            "organization_name" => $sOrganizationName !== "" ? $sOrganizationName : null,
            "department_name" => $sDepartmentName !== "" ? $sDepartmentName : null,
            "care_of" => $sCareOf !== "" ? $sCareOf : null,
            "street_name" => $sStreetName !== "" ? $sStreetName : null,
            "house_number" => $sHouseNumber !== "" ? $sHouseNumber : null,
            "evidence_number" => $sEvidenceNumber !== "" ? $sEvidenceNumber : null,
            "orientation_number" => $sOrientationNumber !== "" ? $sOrientationNumber : null,
            "orientation_suffix" => $sOrientationSuffix !== "" ? $sOrientationSuffix : null,
            "address_line2" => $sAddressLine2 !== "" ? $sAddressLine2 : null,
            "city" => $sCity !== "" ? $sCity : null,
            "city_part" => $sCityPart !== "" ? $sCityPart : null,
            "postal_code" => $sPostalCode !== "" ? $sPostalCode : null,
            "region" => $sRegion !== "" ? $sRegion : null,
            "country" => $sCountry,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote !== "" ? $sNote : null,
            "id" => $iAddressId
        ));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create_subject_address") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sAddressType = nxGetPostedTrimmedValue("address_type");
    $sOrganizationName = nxGetPostedTrimmedValue("organization_name");
    $sDepartmentName = nxGetPostedTrimmedValue("department_name");
    $sCareOf = nxGetPostedTrimmedValue("care_of");
    $sStreetName = nxGetPostedTrimmedValue("street_name");
    $sHouseNumber = nxGetPostedTrimmedValue("house_number");
    $sEvidenceNumber = nxGetPostedTrimmedValue("evidence_number");
    $sOrientationNumber = nxGetPostedTrimmedValue("orientation_number");
    $sOrientationSuffix = nxGetPostedTrimmedValue("orientation_suffix");
    $sAddressLine2 = nxGetPostedTrimmedValue("address_line2");
    $sCity = nxGetPostedTrimmedValue("city");
    $sCityPart = nxGetPostedTrimmedValue("city_part");
    $sPostalCode = nxGetPostedTrimmedValue("postal_code");
    $sRegion = nxGetPostedTrimmedValue("region");
    $sCountry = nxGetPostedTrimmedValue("country");
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] === "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;
    if ($sCountry !== "") {
        $sCountry = strtoupper($sCountry);
    }

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sAddressType === "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, nxGetAddressTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    if ($sCountry === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry !== "" && !in_array($sCountry, nxGetCountryCodes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = nxNormalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName === "" && $sDepartmentName === "" && $sCareOf === "" && $sStreetName === "" && $sHouseNumber === "" && $sEvidenceNumber === "" && $sOrientationNumber === "" && $sOrientationSuffix === "" && $sAddressLine2 === "" && $sCity === "" && $sCityPart === "" && $sPostalCode === "" && $sRegion === "" && $sCountry === "" && $sNote === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_addresses (subject_id, address_type, organization_name, department_name, care_of, street_name, house_number, evidence_number, orientation_number, orientation_suffix, address_line2, city, city_part, postal_code, region, country, is_primary, is_active, note) VALUES (:subject_id, :address_type, :organization_name, :department_name, :care_of, :street_name, :house_number, :evidence_number, :orientation_number, :orientation_suffix, :address_line2, :city, :city_part, :postal_code, :region, :country, :is_primary, :is_active, :note)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "address_type" => $sAddressType,
            "organization_name" => $sOrganizationName !== "" ? $sOrganizationName : null,
            "department_name" => $sDepartmentName !== "" ? $sDepartmentName : null,
            "care_of" => $sCareOf !== "" ? $sCareOf : null,
            "street_name" => $sStreetName !== "" ? $sStreetName : null,
            "house_number" => $sHouseNumber !== "" ? $sHouseNumber : null,
            "evidence_number" => $sEvidenceNumber !== "" ? $sEvidenceNumber : null,
            "orientation_number" => $sOrientationNumber !== "" ? $sOrientationNumber : null,
            "orientation_suffix" => $sOrientationSuffix !== "" ? $sOrientationSuffix : null,
            "address_line2" => $sAddressLine2 !== "" ? $sAddressLine2 : null,
            "city" => $sCity !== "" ? $sCity : null,
            "city_part" => $sCityPart !== "" ? $sCityPart : null,
            "postal_code" => $sPostalCode !== "" ? $sPostalCode : null,
            "region" => $sRegion !== "" ? $sRegion : null,
            "country" => $sCountry,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote !== "" ? $sNote : null
        ));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    $sGroupName = nxGetPostedTrimmedValue("name");

    if ($iSubjectId < 1 || $iGroupId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid group link."), 400);
    }
    if ($sGroupName === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Group was not found."), 404);
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE name = :name AND id <> :id");
        $oStatement->execute(array("name" => $sGroupName, "id" => $iGroupId));
        if ($oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "The selected group name already exists."), 409);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_groups SET name = :name WHERE id = :id");
        $oStatement->execute(array("name" => $sGroupName, "id" => $iGroupId));
        $oPdo->commit();
        $aResponse = nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings);
        $aResponse["group"] = array(
            "group_id" => $iGroupId,
            "name" => $sGroupName
        );
        nxSendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sGroupName = nxGetPostedTrimmedValue("name");

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sGroupName === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Group name is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("SELECT id FROM ex_groups WHERE name = :name FOR UPDATE");
        $oStatement->execute(array("name" => $sGroupName));
        $iGroupId = (int)$oStatement->fetchColumn();
        if ($iGroupId < 1) {
            $oStatement = $oPdo->prepare("INSERT INTO ex_groups (name) VALUES (:name)");
            $oStatement->execute(array("name" => $sGroupName));
            $iGroupId = (int)$oPdo->lastInsertId();
        }
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        if ($oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject is already assigned to this group."), 409);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_groups (subject_id, group_id) VALUES (:subject_id, :group_id)");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        $oPdo->commit();
        $aResponse = nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings);
        $aResponse["group"] = array(
            "group_id" => $iGroupId,
            "name" => $sGroupName
        );
        nxSendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() === "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected group already exists or is already assigned."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_subject_note") {
    $iNoteId = isset($_POST["note_id"]) ? (int)$_POST["note_id"] : 0;
    $sNoteText = nxGetPostedTrimmedValue("note_text");
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;

    if ($iNoteId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid note."), 400);
    }
    if ($sNoteText === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Note text is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_notes WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNoteId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Note was not found."), 404);
        }
        $oStatement = $oPdo->prepare("UPDATE ex_subject_notes SET note_text = :note_text, is_active = :is_active WHERE id = :id");
        $oStatement->execute(array("note_text" => $sNoteText, "is_active" => $iIsActive, "id" => $iNoteId));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create_subject_note") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sNoteText = nxGetPostedTrimmedValue("note_text");
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if ($sNoteText === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Note text is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_notes (subject_id, note_text, is_active) VALUES (:subject_id, :note_text, :is_active)");
        $oStatement->execute(array("subject_id" => $iSubjectId, "note_text" => $sNoteText, "is_active" => $iIsActive));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_subject") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subjects WHERE id = :subject_id");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        $oPdo->commit();
        nxSendJsonAndExit(array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_subject_contact") {
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    if ($iSubjectContactId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact link."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_contacts WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iSubjectContactId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Contact link was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_contacts WHERE id = :id");
        $oStatement->execute(array("id" => $iSubjectContactId));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_subject_nickname") {
    $iNicknameId = isset($_POST["nickname_id"]) ? (int)$_POST["nickname_id"] : 0;
    if ($iNicknameId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid nickname."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_nicknames WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNicknameId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Nickname was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_nicknames WHERE id = :id");
        $oStatement->execute(array("id" => $iNicknameId));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_subject_address") {
    $iAddressId = isset($_POST["address_id"]) ? (int)$_POST["address_id"] : 0;
    if ($iAddressId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_addresses WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iAddressId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_addresses WHERE id = :id");
        $oStatement->execute(array("id" => $iAddressId));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_subject_group") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iGroupId = isset($_POST["group_id"]) ? (int)$_POST["group_id"] : 0;
    if ($iSubjectId < 1 || $iGroupId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid group link."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_groups WHERE subject_id = :subject_id AND group_id = :group_id");
        $oStatement->execute(array("subject_id" => $iSubjectId, "group_id" => $iGroupId));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete_subject_note") {
    $iNoteId = isset($_POST["note_id"]) ? (int)$_POST["note_id"] : 0;
    if ($iNoteId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid note."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT subject_id FROM ex_subject_notes WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iNoteId));
        $iSubjectId = (int)$oStatement->fetchColumn();
        if ($iSubjectId < 1) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Note was not found."), 404);
        }
        $oStatement = $oPdo->prepare("DELETE FROM ex_subject_notes WHERE id = :id");
        $oStatement->execute(array("id" => $iNoteId));
        $oPdo->commit();
        nxSendJsonAndExit(nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings));
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "create_contact") {
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sContactValue = nxGetPostedContactValue();
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] === "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;
    $aContactType = nxGetContactTypeById($iContactTypeId, $oPdo, true);

    if ($iSubjectId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid subject."), 400);
    }
    if (!$aContactType) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact type."), 400);
    }
    $sContactValue = nxNormalizeContactInputForStorage((string)$aContactType["contact_type"], $sContactValue);
    if ($sContactValue === false) {
        nxSendJsonAndExit(array("success" => false, "message" => nxContactInputErrorMessage((string)$aContactType["contact_type"])), 400);
    }
    if ($sContactValue === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Contact value is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subjects WHERE id = :subject_id FOR UPDATE");
        $oStatement->execute(array("subject_id" => $iSubjectId));
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Subject was not found."), 404);
        }

        $oStatement = $oPdo->prepare("SELECT id FROM ex_contacts WHERE contact_type_id = :contact_type_id AND contact_value = :contact_value FOR UPDATE");
        $oStatement->execute(array("contact_type_id" => $iContactTypeId, "contact_value" => $sContactValue));
        $iContactId = (int)$oStatement->fetchColumn();
        if ($iContactId < 1) {
            $oStatement = $oPdo->prepare("INSERT INTO ex_contacts (contact_type_id, contact_value) VALUES (:contact_type_id, :contact_value)");
            $oStatement->execute(array("contact_type_id" => $iContactTypeId, "contact_value" => $sContactValue));
            $iContactId = (int)$oPdo->lastInsertId();
        }

        $oStatement = $oPdo->prepare("SELECT id FROM ex_subject_contacts WHERE subject_id = :subject_id AND contact_id = :contact_id");
        $oStatement->execute(array("subject_id" => $iSubjectId, "contact_id" => $iContactId));
        if ($oStatement->fetch(PDO::FETCH_ASSOC)) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "This contact is already assigned to the subject."), 409);
        }

        $oStatement = $oPdo->prepare("INSERT INTO ex_subject_contacts (subject_id, contact_id, is_primary, is_active, note) VALUES (:subject_id, :contact_id, :is_primary, :is_active, :note)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "contact_id" => $iContactId,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote !== "" ? $sNote : null
        ));
        $iSubjectContactId = (int)$oPdo->lastInsertId();
        $oPdo->commit();

        $aResponse = nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aFullListSettings);
        $aResponse["contact"] = array(
            "subject_contact_id" => $iSubjectContactId,
            "contact_id" => $iContactId,
            "contact_type_id" => $iContactTypeId,
            "contact_type" => (string)$aContactType["contact_type"],
            "contact_type_label" => (string)$aContactType["name"],
            "contact_value" => $sContactValue,
            "contact_display_value" => nxContactDisplayValue((string)$aContactType["contact_type"], $sContactValue),
            "note" => $sNote,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive
        );
        nxSendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() === "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected contact value already exists or is already assigned."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_contact") {
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    $iContactTypeId = isset($_POST["contact_type_id"]) ? (int)$_POST["contact_type_id"] : 0;
    $sContactValue = nxGetPostedContactValue();
    $sNote = nxGetPostedTrimmedValue("note");
    $iIsPrimary = isset($_POST["is_primary"]) && (string)$_POST["is_primary"] === "1" ? 1 : 0;
    $iIsActive = isset($_POST["is_active"]) && (string)$_POST["is_active"] === "1" ? 1 : 0;
    $aContactType = nxGetContactTypeById($iContactTypeId, $oPdo, true);

    if ($iSubjectContactId < 1) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact link."), 400);
    }
    if (!$aContactType) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid contact type."), 400);
    }
    $sContactValue = nxNormalizeContactInputForStorage((string)$aContactType["contact_type"], $sContactValue);
    if ($sContactValue === false) {
        nxSendJsonAndExit(array("success" => false, "message" => nxContactInputErrorMessage((string)$aContactType["contact_type"])), 400);
    }
    if ($sContactValue === "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Contact value is required."), 400);
    }

    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT sc.id, sc.subject_id, sc.contact_id FROM ex_subject_contacts AS sc WHERE sc.id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iSubjectContactId));
        $aSubjectContact = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aSubjectContact) {
            $oPdo->rollBack();
            nxSendJsonAndExit(array("success" => false, "message" => "Contact link was not found."), 404);
        }

        $iContactId = (int)$aSubjectContact["contact_id"];
        $oStatement = $oPdo->prepare("UPDATE ex_contacts SET contact_type_id = :contact_type_id, contact_value = :contact_value WHERE id = :id");
        $oStatement->execute(array(
            "contact_type_id" => $iContactTypeId,
            "contact_value" => $sContactValue,
            "id" => $iContactId
        ));

        $oStatement = $oPdo->prepare("UPDATE ex_subject_contacts SET is_primary = :is_primary, is_active = :is_active, note = :note WHERE id = :id");
        $oStatement->execute(array(
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive,
            "note" => $sNote !== "" ? $sNote : null,
            "id" => $iSubjectContactId
        ));
        $oPdo->commit();

        $aResponse = nxGetUpdatedSubjectResponse($oPdo, (int)$aSubjectContact["subject_id"], $aFullListSettings);
        $aResponse["contact"] = array(
            "subject_contact_id" => $iSubjectContactId,
            "contact_id" => $iContactId,
            "contact_type_id" => $iContactTypeId,
            "contact_type" => (string)$aContactType["contact_type"],
            "contact_type_label" => (string)$aContactType["name"],
            "contact_value" => $sContactValue,
            "contact_display_value" => nxContactDisplayValue((string)$aContactType["contact_type"], $sContactValue),
            "note" => $sNote,
            "is_primary" => $iIsPrimary,
            "is_active" => $iIsActive
        );
        nxSendJsonAndExit($aResponse);
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ((string)$oException->getCode() === "23000") {
            nxSendJsonAndExit(array("success" => false, "message" => "The selected contact value already exists."), 409);
        }
        nxSendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}


$aRows = array();
$aContacts = array();
$aContactTypes = array();
$aNicknames = array();
$aAddresses = array();
$aGroups = array();
$aAllGroups = array();
$aNotes = array();
$aHiddenInactive = array();
$aFullListComplexFilterSql = nxBuildFullListComplexFilterSql($aFullListComplexFilter, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
try {
    $oPdo->query("SET SESSION group_concat_max_len = 1048576");
    $aRows = nxFetchSubjectRows($oPdo, 0, $aFullListComplexFilterSql);
    $aContacts = nxFetchSubjectContacts($oPdo);
    $aContactTypes = nxFetchContactTypes($oPdo, false);
    $aNicknames = nxFetchSubjectNicknames($oPdo);
    $aAddresses = nxFetchSubjectAddresses($oPdo);
    $aGroups = nxFetchSubjectGroups($oPdo);
    $aAllGroups = nxFetchGroups($oPdo);
    $aNotes = nxFetchSubjectNotes($oPdo);
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}

$aHiddenInactive = nxGetHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aFullListSettings);
nxApplySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aFullListSettings);

$blFullListComplexFilterActive = count($aFullListComplexFilter["conditions"]) > 0;
$aFullListComplexFilterRows = $aFullListComplexFilterDraft["conditions"];
while (count($aFullListComplexFilterRows) < 1) {
    $aFullListComplexFilterRows[] = array(
        "field" => "subject_name",
        "operator" => "contains",
        "value" => ""
    );
}
$aFullListComplexFilterGroups = array();
foreach ($aAllGroups as $aGroup) {
    $aFullListComplexFilterGroups[] = (string)$aGroup["name"];
}
$aFullListComplexFilterAddressTypes = array();
foreach (nxGetAddressTypes() as $sAddressType) {
    $aFullListComplexFilterAddressTypes[] = array(
        "value" => $sAddressType,
        "label" => nxAddressTypeLabel($sAddressType)
    );
}

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo nxHtml(getExPageTitleText("Subjects", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo nxHtml(getExCsrfToken()); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body data-calendar-first-day="<?php echo nxHtml($iCalendarFirstDay); ?>" data-date-input-format="<?php echo nxHtml($sDateInputFormat); ?>" data-date-input-pattern="<?php echo nxHtml($sDateInputPattern); ?>">
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-subjects-table" value="<?php echo nxHtml(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-complex-filter-open<?php echo $blFullListComplexFilterActive ? " complex-filter-active" : ""; ?>" aria-pressed="<?php echo $blFullListComplexFilterActive ? "true" : "false"; ?>">Complex</button>
    <button type="submit" class="button-link js-complex-filter-page-reset<?php echo $blFullListComplexFilterActive ? " complex-filter-active" : ""; ?>" form="complex-filter-reset-form" title="Reset complex filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
<?php

if ($blCanEdit) {
    echo "    <button type=\"button\" class=\"button-link js-add-subject\">New</button>\n";
}

?>
  </p>
  <form id="complex-filter-reset-form" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="action" value="reset_full_list_complex_filter">
    <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
  </form>
  <?php echo nxRenderCountryDatalist(); ?>
  <div class="confirm-dialog complex-filter-dialog" id="complex-filter-dialog" hidden>
    <form class="confirm-dialog-box complex-filter-form" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_full_list_complex_filter">
      <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
      <div class="confirm-dialog-header">
        <strong>Complex Filter</strong>
        <button type="button" class="confirm-dialog-close js-complex-filter-close" aria-label="Close">&times;</button>
      </div>
      <div class="complex-filter-options">
        <div class="complex-filter-match">
          <label><input type="radio" name="complex_filter_match" value="all"<?php echo $aFullListComplexFilterDraft["match"] === "all" ? " checked" : ""; ?>> Match all conditions</label>
          <label><input type="radio" name="complex_filter_match" value="any"<?php echo $aFullListComplexFilterDraft["match"] === "any" ? " checked" : ""; ?>> Match any condition</label>
        </div>
        <div class="complex-filter-rows js-complex-filter-rows" data-empty-row-count="1" data-group-options="<?php echo nxHtml(json_encode($aFullListComplexFilterGroups)); ?>" data-address-type-options="<?php echo nxHtml(json_encode($aFullListComplexFilterAddressTypes)); ?>">
<?php

foreach ($aFullListComplexFilterRows as $aCondition) {
    $sComplexField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "subject_name";
    if ($sComplexField !== "" && !isset($aFullListComplexFilterFields[$sComplexField])) {
        $sComplexField = "subject_name";
    }
    $sComplexOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "contains";
    if ($sComplexOperator !== "" && !isset($aFullListComplexFilterOperators[$sComplexOperator])) {
        $sComplexOperator = "contains";
    }
    $sComplexValueType = $sComplexField !== "" && isset($aFullListComplexFilterFields[$sComplexField]["value_type"]) ? (string)$aFullListComplexFilterFields[$sComplexField]["value_type"] : "text";
    if ($sComplexValueType === "boolean") {
        $sComplexOperator = "equals";
    }
    $sComplexValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
    $blComplexNeedsValue = $sComplexOperator === "" || !empty($aFullListComplexFilterOperators[$sComplexOperator]["needs_value"]);
    $blComplexOperatorHidden = $sComplexValueType === "boolean";
    echo "          <div class=\"complex-filter-row js-complex-filter-row\">\n"
        . "            <select name=\"complex_filter_field[]\" class=\"js-complex-filter-field\">" . nxRenderFullListComplexFilterFieldOptions($aFullListComplexFilterFields, $sComplexField) . "</select>\n"
        . "            <select name=\"complex_filter_operator[]\" class=\"js-complex-filter-operator\"" . ($blComplexOperatorHidden ? " disabled aria-hidden=\"true\" tabindex=\"-1\"" : "") . ">" . nxRenderFullListComplexFilterOperatorOptions($aFullListComplexFilterOperators, $sComplexOperator) . "</select>\n"
        . "            <input type=\"text\" name=\"complex_filter_value[]\" class=\"js-complex-filter-value\" value=\"" . nxHtml($sComplexValue) . "\" autocomplete=\"off\"" . ($blComplexNeedsValue ? "" : " disabled") . ">\n"
        . "            <button type=\"button\" class=\"complex-filter-remove js-complex-filter-remove\" title=\"Remove condition\" aria-label=\"Remove condition\">&times;</button>\n"
        . "          </div>\n";
}

?>
        </div>
        <button type="button" class="button-link complex-filter-add js-complex-filter-add">Add condition</button>
      </div>
      <div class="confirm-dialog-actions complex-filter-actions">
        <button type="button" class="confirm-dialog-button js-complex-filter-modal-reset">Reset</button>
        <button type="submit" class="confirm-dialog-button">Apply</button>
        <button type="button" class="confirm-dialog-button js-complex-filter-cancel">Close</button>
      </div>
    </form>
  </div>
  <div class="confirm-dialog index-settings-dialog" id="index-settings-dialog" hidden>
    <form class="confirm-dialog-box index-settings-form" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_full_list_settings">
      <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
      <div class="confirm-dialog-header">
        <strong>Full List Settings</strong>
        <button type="button" class="confirm-dialog-close js-index-settings-close" aria-label="Close">&times;</button>
      </div>
      <div class="index-settings-options">
        <label><input type="checkbox" name="show_inactive_subjects" value="1"<?php echo $aFullListSettings["show_inactive_subjects"] ? " checked" : ""; ?>> Show inactive subjects</label>
        <label><input type="checkbox" name="show_inactive_nicknames" value="1"<?php echo $aFullListSettings["show_inactive_nicknames"] ? " checked" : ""; ?>> Show inactive nicknames</label>
        <label><input type="checkbox" name="show_inactive_addresses" value="1"<?php echo $aFullListSettings["show_inactive_addresses"] ? " checked" : ""; ?>> Show inactive addresses</label>
        <label><input type="checkbox" name="show_inactive_contacts" value="1"<?php echo $aFullListSettings["show_inactive_contacts"] ? " checked" : ""; ?>> Show inactive contacts</label>
        <label><input type="checkbox" name="show_inactive_notes" value="1"<?php echo $aFullListSettings["show_inactive_notes"] ? " checked" : ""; ?>> Show inactive notes</label>
        <hr>
        <label><input type="checkbox" name="show_czechia_country" value="1" class="js-czechia-country-toggle"<?php echo $aFullListSettings["show_czechia_country"] ? " checked" : ""; ?>> Also show the country Czechia</label>
        <label><input type="checkbox" name="show_czechia_country_in_czech" value="1" class="js-czechia-country-dependent" data-czechia-stored="<?php echo $aFullListSettings["show_czechia_country_in_czech"] ? "1" : "0"; ?>"<?php echo $aFullListSettings["show_czechia_country"] && $aFullListSettings["show_czechia_country_in_czech"] ? " checked" : ""; ?><?php echo $aFullListSettings["show_czechia_country"] ? "" : " disabled"; ?>> Show the country Czechia in Czech</label>
        <label><input type="checkbox" name="show_czechia_country_as_czech_republic" value="1" class="js-czechia-country-dependent" data-czechia-stored="<?php echo $aFullListSettings["show_czechia_country_as_czech_republic"] ? "1" : "0"; ?>"<?php echo $aFullListSettings["show_czechia_country"] && $aFullListSettings["show_czechia_country_as_czech_republic"] ? " checked" : ""; ?><?php echo $aFullListSettings["show_czechia_country"] ? "" : " disabled"; ?>> Show Česká republika instead of Česko</label>
      </div>
      <?php echo nxRenderExSettingsScopeNote(); ?>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-index-settings-cancel">Cancel</button>
      </div>
    </form>
  </div>
  <datalist id="nx-group-list">
<?php

foreach ($aAllGroups as $aGroup) {
    echo "    <option value=\"" . nxHtml($aGroup["name"]) . "\"></option>\n";
}

?>
  </datalist>
  <select id="nx-contact-type-list" hidden>
<?php

foreach ($aContactTypes as $aContactType) {
    echo "    <option value=\"" . nxHtml($aContactType["id"]) . "\" data-contact-type=\"" . nxHtml($aContactType["contact_type"]) . "\" data-contact-type-active=\"" . nxHtml($aContactType["is_active"]) . "\">" . nxHtml($aContactType["name"]) . "</option>\n";
}

?>
  </select>
<?php

if (count($aRows) === 0) {
    echo "  <p>" . ($blFullListComplexFilterActive ? "<strong>Complex Filter: </strong>" : "") . "No visible records found.</p>\n";
} else {

?>
  <table id="nx-subjects-table" class="table-filter-target">
    <thead>
      <tr>
        <th class="nx-subject-type-column">Type</th>
        <th>Name</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Birth Name</th>
        <th>Birth Number</th>
        <th>Birth Date</th>
        <th>Death Date</th>
        <th>Nicknames</th>
        <th>Addresses</th>
        <th>Contacts</th>
        <th>Groups</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
<?php

    foreach ($aRows as $aRow) {
        echo nxRenderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blCanEdit, $aHiddenInactive, $aFullListSettings);
    }

?>
    </tbody>
  </table>
<?php

}

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter">&#128269; Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
