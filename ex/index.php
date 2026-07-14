<?php

include "main.php";


$blCanEdit = isExFullAccessAllowed($aAllowedIps);
requireExViewAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$aIndexSettingsDefaults = array(
    "show_inactive_subjects" => 0,
    "show_inactive_nicknames" => 0,
    "show_inactive_addresses" => 0,
    "show_inactive_contacts" => 0,
    "show_inactive_notes" => 0
);
$aIndexSettings = array();

if (!isset($_SESSION["ex_index_settings"]) || !is_array($_SESSION["ex_index_settings"])) {
    $_SESSION["ex_index_settings"] = array();
}
foreach ($aIndexSettingsDefaults as $sIndexSettingName => $iIndexSettingDefault) {
    if (isset($_SESSION["ex_index_settings"][$sIndexSettingName])) {
        $aIndexSettings[$sIndexSettingName] = (int)$_SESSION["ex_index_settings"][$sIndexSettingName] == 1 ? 1 : 0;
    } else {
        $aIndexSettings[$sIndexSettingName] = $iIndexSettingDefault;
    }
}
$aIndexSettings = nxApplyExCountrySettings($aIndexSettings);

$aFullListComplexFilterContactTypes = array();
try {
    $oStatement = $oPdo->query("SELECT id, contact_type, name FROM ex_contact_types ORDER BY `order` ASC, id ASC");
    $aFullListComplexFilterContactTypes = $oStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}
$aFullListComplexFilterFields = nxGetFullListComplexFilterFields($aFullListComplexFilterContactTypes);
$aFullListComplexFilterOperators = nxGetFullListComplexFilterOperators();
$aFullListComplexFilter = nxGetDefaultFullListComplexFilter();
$aFullListComplexFilterDraft = nxGetDefaultFullListComplexFilterDraft();

if (isset($_SESSION["ex_index_complex_filter"]) && is_array($_SESSION["ex_index_complex_filter"])) {
    $aFullListComplexFilter = nxNormalizeFullListComplexFilter($_SESSION["ex_index_complex_filter"], $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
}
if (isset($_SESSION["ex_index_complex_filter_draft"]) && is_array($_SESSION["ex_index_complex_filter_draft"])) {
    $aFullListComplexFilterDraft = nxNormalizeFullListComplexFilterDraft($_SESSION["ex_index_complex_filter_draft"], $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
} elseif (count($aFullListComplexFilter["conditions"]) > 0) {
    $aFullListComplexFilterDraft = nxNormalizeFullListComplexFilterDraft($aFullListComplexFilter, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireExCsrfToken();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_index_settings") {
    foreach ($aIndexSettingsDefaults as $sIndexSettingName => $iIndexSettingDefault) {
        $aIndexSettings[$sIndexSettingName] = isset($_POST[$sIndexSettingName]) && (string)$_POST[$sIndexSettingName] == "1" ? 1 : 0;
    }
    $aIndexSettings = nxSaveExCountrySettings($aIndexSettings, $_POST);
    $_SESSION["ex_index_settings"] = nxRemoveExCountrySettings($aIndexSettings);
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl, true, 303);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_full_list_complex_filter") {
    $aFullListComplexFilterPayload = nxGetFullListComplexFilterPostPayload();
    $aFullListComplexFilterDraft = nxNormalizeFullListComplexFilterDraft($aFullListComplexFilterPayload, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $aFullListComplexFilter = nxNormalizeFullListComplexFilter($aFullListComplexFilterPayload, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $_SESSION["ex_index_complex_filter"] = $aFullListComplexFilter;
    $_SESSION["ex_index_complex_filter_draft"] = $aFullListComplexFilterDraft;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl, true, 303);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_full_list_complex_filter_draft") {
    $aFullListComplexFilterDraft = nxNormalizeFullListComplexFilterDraft(nxGetFullListComplexFilterPostPayload(), $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $_SESSION["ex_index_complex_filter_draft"] = $aFullListComplexFilterDraft;
    session_write_close();
    nxSendJsonAndExit(array("success" => true));
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "reset_full_list_complex_filter") {
    $aFullListComplexFilter = nxGetDefaultFullListComplexFilter();
    $_SESSION["ex_index_complex_filter"] = $aFullListComplexFilter;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl, true, 303);
    exit;
}

$aRows = array();
$aContacts = array();
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
    $aNicknames = nxFetchSubjectNicknames($oPdo);
    $aAddresses = nxFetchSubjectAddresses($oPdo);
    $aGroups = nxFetchSubjectGroups($oPdo);
    $aAllGroups = nxFetchGroups($oPdo);
    $aNotes = nxFetchSubjectNotes($oPdo);
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}

$aHiddenInactive = nxGetHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aIndexSettings);

if (!$aIndexSettings["show_inactive_subjects"]) {
    $aActiveRows = array();
    foreach ($aRows as $aRow) {
        if ((int)$aRow["is_active"] == 1) {
            $aActiveRows[] = $aRow;
        }
    }
    $aRows = $aActiveRows;
}

if (!$aIndexSettings["show_inactive_nicknames"]) {
    foreach ($aNicknames as $iSubjectId => $aSubjectNicknames) {
        $aActiveNicknames = array();
        foreach ($aSubjectNicknames as $aNickname) {
            if (!isset($aNickname["is_active"]) || (int)$aNickname["is_active"] == 1) {
                $aActiveNicknames[] = $aNickname;
            }
        }
        $aNicknames[$iSubjectId] = $aActiveNicknames;
    }
}

if (!$aIndexSettings["show_inactive_addresses"]) {
    foreach ($aAddresses as $iSubjectId => $aSubjectAddresses) {
        $aActiveAddresses = array();
        foreach ($aSubjectAddresses as $aAddress) {
            if (!isset($aAddress["is_active"]) || (int)$aAddress["is_active"] == 1) {
                $aActiveAddresses[] = $aAddress;
            }
        }
        $aAddresses[$iSubjectId] = $aActiveAddresses;
    }
}

if (!$aIndexSettings["show_inactive_contacts"]) {
    foreach ($aContacts as $iSubjectId => $aSubjectContacts) {
        $aActiveContacts = array();
        foreach ($aSubjectContacts as $aContact) {
            if ((int)$aContact["is_active"] == 1) {
                $aActiveContacts[] = $aContact;
            }
        }
        $aContacts[$iSubjectId] = $aActiveContacts;
    }
}

if (!$aIndexSettings["show_inactive_notes"]) {
    foreach ($aNotes as $iSubjectId => $aSubjectNotes) {
        $aActiveNotes = array();
        foreach ($aSubjectNotes as $aNote) {
            if (!isset($aNote["is_active"]) || (int)$aNote["is_active"] == 1) {
                $aActiveNotes[] = $aNote;
            }
        }
        $aNotes[$iSubjectId] = $aActiveNotes;
    }
}

$blFullListComplexFilterActive = count($aFullListComplexFilter["conditions"]) > 0;
$aFullListComplexFilterRows = isset($aFullListComplexFilterDraft["conditions"]) && is_array($aFullListComplexFilterDraft["conditions"]) ? $aFullListComplexFilterDraft["conditions"] : array();
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
$aFullListComplexFilterSubjectTypes = array();
foreach (nxGetSubjectTypes() as $sSubjectType) {
    $aFullListComplexFilterSubjectTypes[] = array(
        "value" => $sSubjectType,
        "label" => ucfirst($sSubjectType)
    );
}
$aFullListComplexFilterAddressTypes = array();
foreach (nxGetAddressTypes() as $sAddressType) {
    $aFullListComplexFilterAddressTypes[] = array(
        "value" => $sAddressType,
        "label" => nxAddressTypeLabel($sAddressType)
    );
}

$sViewportContent = nxGetLockedViewportContent();
$sRenderThrobberHtmlAttributes = nxGetRenderThrobberHtmlAttributes(count($aRows) > 0);
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr"<?php echo $sRenderThrobberHtmlAttributes; ?>>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="<?php echo nxHtml($sViewportContent); ?>">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo nxHtml(getExPageTitleText("Contacts", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo nxHtml(getExCsrfToken()); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-contacts-table" value="<?php echo nxHtml(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-complex-filter-open<?php echo $blFullListComplexFilterActive ? " complex-filter-active" : ""; ?>" aria-pressed="<?php echo $blFullListComplexFilterActive ? "true" : "false"; ?>">Complex</button>
    <button type="submit" class="button-link js-complex-filter-page-reset<?php echo $blFullListComplexFilterActive ? " complex-filter-active" : ""; ?>" form="complex-filter-reset-form" title="Reset complex filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
  </p>
  <form id="complex-filter-reset-form" method="post" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="action" value="reset_full_list_complex_filter">
    <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
  </form>
  <?php echo nxRenderCountryDatalist(); ?>
  <div class="confirm-dialog complex-filter-dialog" id="complex-filter-dialog" hidden>
    <form class="confirm-dialog-box complex-filter-form" method="post" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_full_list_complex_filter">
      <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
      <div class="confirm-dialog-header">
        <strong>Complex Filter</strong>
        <button type="button" class="confirm-dialog-close js-complex-filter-close" aria-label="Close">&times;</button>
      </div>
      <div class="complex-filter-options">
        <div class="complex-filter-match">
          <label><input type="radio" name="complex_filter_match" value="all"<?php echo $aFullListComplexFilterDraft["match"] == "all" ? " checked" : ""; ?>> Match all conditions</label>
          <label><input type="radio" name="complex_filter_match" value="any"<?php echo $aFullListComplexFilterDraft["match"] == "any" ? " checked" : ""; ?>> Match any condition</label>
        </div>
        <div class="complex-filter-rows js-complex-filter-rows" data-empty-row-count="1" data-group-options="<?php echo nxHtml(json_encode($aFullListComplexFilterGroups)); ?>" data-subject-type-options="<?php echo nxHtml(json_encode($aFullListComplexFilterSubjectTypes)); ?>" data-address-type-options="<?php echo nxHtml(json_encode($aFullListComplexFilterAddressTypes)); ?>">
<?php

foreach ($aFullListComplexFilterRows as $aCondition) {
    $sComplexField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "subject_name";
    if ($sComplexField != "" && !isset($aFullListComplexFilterFields[$sComplexField])) {
        $sComplexField = "subject_name";
    }
    $sComplexOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "contains";
    if ($sComplexOperator != "" && !isset($aFullListComplexFilterOperators[$sComplexOperator])) {
        $sComplexOperator = $sComplexField != "" ? nxGetFullListComplexFilterDefaultOperator($aFullListComplexFilterFields[$sComplexField]) : "contains";
    }
    $sComplexValueType = $sComplexField != "" && isset($aFullListComplexFilterFields[$sComplexField]["value_type"]) ? (string)$aFullListComplexFilterFields[$sComplexField]["value_type"] : "text";
    if ($sComplexValueType == "boolean") {
        $sComplexOperator = "equals";
    }
    if ($sComplexField != "" && !nxIsFullListComplexFilterOperatorAllowed($aFullListComplexFilterFields[$sComplexField], $sComplexOperator)) {
        $sComplexOperator = nxGetFullListComplexFilterDefaultOperator($aFullListComplexFilterFields[$sComplexField]);
    }
    $sComplexValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
    $blComplexNeedsValue = $sComplexOperator == "" || !empty($aFullListComplexFilterOperators[$sComplexOperator]["needs_value"]);
    $blComplexOperatorHidden = $sComplexValueType == "boolean";
    echo "          <div class=\"complex-filter-row js-complex-filter-row\">\n"
        . "            <select name=\"complex_filter_field[]\" class=\"js-complex-filter-field\">" . nxRenderFullListComplexFilterFieldOptions($aFullListComplexFilterFields, $sComplexField) . "</select>\n"
        . "            <select name=\"complex_filter_operator[]\" class=\"js-complex-filter-operator\"" . ($blComplexOperatorHidden ? " disabled aria-hidden=\"true\" tabindex=\"-1\"" : "") . ">" . nxRenderFullListComplexFilterOperatorOptions($aFullListComplexFilterOperators, $sComplexOperator, $sComplexField != "" ? $aFullListComplexFilterFields[$sComplexField] : null) . "</select>\n"
        . "            <input type=\"text\" name=\"complex_filter_value[]\" class=\"js-complex-filter-value\" value=\"" . nxHtml($sComplexValue) . "\" autocomplete=\"off\"" . ($blComplexNeedsValue ? "" : " disabled") . ">\n"
        . "            <button type=\"button\" class=\"complex-filter-remove js-complex-filter-remove\" title=\"Remove condition\" aria-label=\"Remove condition\">&times;</button>\n"
        . "          </div>\n";
}

?>
        </div>
        <button type="button" class="button-link complex-filter-add js-complex-filter-add">Add condition</button>
      </div>
      <div class="confirm-dialog-actions">
        <button type="button" class="confirm-dialog-button js-complex-filter-reset">Reset</button>
        <button type="submit" class="confirm-dialog-button">Apply</button>
        <button type="button" class="confirm-dialog-button js-complex-filter-cancel">Close</button>
      </div>
    </form>
  </div>
  <div class="confirm-dialog index-settings-dialog" id="index-settings-dialog" hidden>
    <form class="confirm-dialog-box index-settings-form" method="post" action="<?php echo $sBaseUrl; ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_index_settings">
      <input type="hidden" name="ex_csrf_token" value="<?php echo nxHtml(getExCsrfToken()); ?>">
      <div class="confirm-dialog-header">
        <strong>Index Settings</strong>
        <button type="button" class="confirm-dialog-close js-index-settings-close" aria-label="Close">&times;</button>
      </div>
      <div class="index-settings-options">
        <label><input type="checkbox" name="show_inactive_subjects" value="1"<?php echo $aIndexSettings["show_inactive_subjects"] ? " checked" : ""; ?>> Show inactive subjects</label>
        <label><input type="checkbox" name="show_inactive_nicknames" value="1"<?php echo $aIndexSettings["show_inactive_nicknames"] ? " checked" : ""; ?>> Show inactive nicknames</label>
        <label><input type="checkbox" name="show_inactive_addresses" value="1"<?php echo $aIndexSettings["show_inactive_addresses"] ? " checked" : ""; ?>> Show inactive addresses</label>
        <label><input type="checkbox" name="show_inactive_contacts" value="1"<?php echo $aIndexSettings["show_inactive_contacts"] ? " checked" : ""; ?>> Show inactive contacts</label>
        <label><input type="checkbox" name="show_inactive_notes" value="1"<?php echo $aIndexSettings["show_inactive_notes"] ? " checked" : ""; ?>> Show inactive notes</label>
        <hr>
        <label><input type="checkbox" name="show_czechia_country" value="1" class="js-czechia-country-toggle"<?php echo $aIndexSettings["show_czechia_country"] ? " checked" : ""; ?>> Also show the country Czechia</label>
        <label><input type="checkbox" name="show_czechia_country_in_czech" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aIndexSettings["show_czechia_country_in_czech"] ? "1" : "0") . "\"" . ($aIndexSettings["show_czechia_country"] && $aIndexSettings["show_czechia_country_in_czech"] ? " checked" : "") . ($aIndexSettings["show_czechia_country"] ? "" : " disabled"); ?>> Show the country Czechia in Czech</label>
        <label><input type="checkbox" name="show_czechia_country_as_czech_republic" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aIndexSettings["show_czechia_country_as_czech_republic"] ? "1" : "0") . "\"" . ($aIndexSettings["show_czechia_country"] && $aIndexSettings["show_czechia_country_as_czech_republic"] ? " checked" : "") . ($aIndexSettings["show_czechia_country"] ? "" : " disabled"); ?>> Show Česká republika instead of Česko</label>
      </div>
      <?php echo nxRenderExSettingsScopeNote(); ?>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-index-settings-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

if (!$aRows) {
    echo "  <p>No visible records found.</p>\n";
} else {

echo nxRenderPageThrobber();

?>
  <table id="nx-contacts-table" class="nx-contacts-table table-filter-target<?php echo nxGetCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th class="nx-column-hidden">Type</th>
        <th>Name</th>
        <th class="nx-column-hidden">First Name</th>
        <th class="nx-column-hidden">Last Name</th>
        <th class="nx-column-step-one">Birth Name</th>
        <th class="nx-column-hidden">Birth Number</th>
        <th class="nx-column-step-two" style="overflow-wrap: normal; white-space: nowrap; word-break: normal;">Birth Date</th>
        <th class="nx-column-hidden">Death Date</th>
        <th class="nx-column-step-one">Nicknames</th>
        <th>Addresses</th>
        <th>Contacts</th>
        <th class="nx-column-step-three">Groups</th>
        <th class="nx-column-step-three">Notes</th>
      </tr>
    </thead>
    <tbody>
<?php

    foreach ($aRows as $aRow) {
        echo nxRenderResponsiveSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aHiddenInactive, $aIndexSettings);
    }

    echo "    </tbody>\n"
        . "  </table>\n";
}

echo nxRenderFilterFocusButton()
    . nxRenderAdminScript($sBaseUrl);

?>
</body>
</html>
