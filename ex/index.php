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
        $aIndexSettings[$sIndexSettingName] = (int)$_SESSION["ex_index_settings"][$sIndexSettingName] === 1 ? 1 : 0;
    } else {
        $aIndexSettings[$sIndexSettingName] = $iIndexSettingDefault;
    }
}
$aIndexSettings = nxApplyExCountrySettings($aIndexSettings);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    requireExCsrfToken();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "save_index_settings") {
    foreach ($aIndexSettingsDefaults as $sIndexSettingName => $iIndexSettingDefault) {
        $aIndexSettings[$sIndexSettingName] = isset($_POST[$sIndexSettingName]) && (string)$_POST[$sIndexSettingName] === "1" ? 1 : 0;
    }
    $aIndexSettings = nxSaveExCountrySettings($aIndexSettings, $_POST);
    $_SESSION["ex_index_settings"] = nxRemoveExCountrySettings($aIndexSettings);
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
$aNotes = array();
$aHiddenInactive = array();
try {
    $oPdo->query("SET SESSION group_concat_max_len = 1048576");
    $aRows = nxFetchSubjectRows($oPdo);
    $aContacts = nxFetchSubjectContacts($oPdo);
    $aNicknames = nxFetchSubjectNicknames($oPdo);
    $aAddresses = nxFetchSubjectAddresses($oPdo);
    $aGroups = nxFetchSubjectGroups($oPdo);
    $aNotes = nxFetchSubjectNotes($oPdo);
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
}

$aHiddenInactive = nxGetHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aIndexSettings);

if (!$aIndexSettings["show_inactive_subjects"]) {
    $aActiveRows = array();
    foreach ($aRows as $aRow) {
        if ((int)$aRow["is_active"] === 1) {
            $aActiveRows[] = $aRow;
        }
    }
    $aRows = $aActiveRows;
}

if (!$aIndexSettings["show_inactive_nicknames"]) {
    foreach ($aNicknames as $iSubjectId => $aSubjectNicknames) {
        $aActiveNicknames = array();
        foreach ($aSubjectNicknames as $aNickname) {
            if (!isset($aNickname["is_active"]) || (int)$aNickname["is_active"] === 1) {
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
            if (!isset($aAddress["is_active"]) || (int)$aAddress["is_active"] === 1) {
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
            if ((int)$aContact["is_active"] === 1) {
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
            if (!isset($aNote["is_active"]) || (int)$aNote["is_active"] === 1) {
                $aActiveNotes[] = $aNote;
            }
        }
        $aNotes[$iSubjectId] = $aActiveNotes;
    }
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
    <button type="button" class="button-link js-index-settings-open">Settings</button>
  </p>
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
        <label><input type="checkbox" name="show_czechia_country_in_czech" value="1" class="js-czechia-country-dependent" data-czechia-stored="<?php echo $aIndexSettings["show_czechia_country_in_czech"] ? "1" : "0"; ?>"<?php echo $aIndexSettings["show_czechia_country"] && $aIndexSettings["show_czechia_country_in_czech"] ? " checked" : ""; ?><?php echo $aIndexSettings["show_czechia_country"] ? "" : " disabled"; ?>> Show the country Czechia in Czech</label>
        <label><input type="checkbox" name="show_czechia_country_as_czech_republic" value="1" class="js-czechia-country-dependent" data-czechia-stored="<?php echo $aIndexSettings["show_czechia_country_as_czech_republic"] ? "1" : "0"; ?>"<?php echo $aIndexSettings["show_czechia_country"] && $aIndexSettings["show_czechia_country_as_czech_republic"] ? " checked" : ""; ?><?php echo $aIndexSettings["show_czechia_country"] ? "" : " disabled"; ?>> Show Česká republika instead of Česko</label>
      </div>
      <?php echo nxRenderExSettingsScopeNote(); ?>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-index-settings-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

if (count($aRows) === 0) {
    echo "  <p>No visible records found.</p>\n";
} else {

?>
  <div class="render-throbber js-render-throbber" role="status" aria-live="polite">
    <div class="render-throbber-box">
      <span class="render-throbber-icon" aria-hidden="true">&#8987;</span>
    </div>
  </div>
  <table id="nx-contacts-table" class="nx-contacts-table table-filter-target">
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
        $iSubjectId = (int)$aRow["subject_id"];
        $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aRow["subject_type"]));
        $blIsActive = (int)$aRow["is_active"] === 1;
        $sBirthNumberClass = nxBirthNumberClass($aRow["birth_number"], "nx-column-hidden");
        $sBirthDateClass = nxBirthDateClass($aRow["birth_number"], $aRow["birth_date"], "nx-column-step-two");
        echo "      <tr class=\"nx-subject-row nx-subject-row-type-" . nxHtml($sSubjectType) . ($blIsActive ? " nx-subject-row-active" : " nx-subject-row-inactive") . "\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" data-subject-type=\"" . nxHtml($aRow["subject_type"]) . "\" data-subject-active=\"" . ($blIsActive ? "1" : "0") . "\">\n"
            . "        <td class=\"nx-column-hidden\">" . nxHtml($aRow["subject_type"]) . "</td>\n"
            . "        <td>" . nxHtmlValue($aRow["subject_name"]) . nxRenderCopyAction($aRow["subject_name"]) . "</td>\n"
            . "        <td class=\"nx-column-hidden\">" . nxHtmlValue($aRow["first_name"]) . "</td>\n"
            . "        <td class=\"nx-column-hidden\">" . nxHtmlValue($aRow["last_name"]) . "</td>\n"
            . "        <td class=\"nx-column-step-one\">" . nxHtmlValue($aRow["birth_name"]) . "</td>\n"
            . "        <td class=\"" . nxHtml($sBirthNumberClass) . "\">" . nxRenderBirthNumberValue($aRow["birth_number"]) . "</td>\n"
            . "        <td class=\"" . nxHtml($sBirthDateClass) . "\" style=\"overflow-wrap: normal; white-space: nowrap; word-break: normal;\">" . nxHtmlValue($aRow["birth_date"]) . "</td>\n"
            . "        <td class=\"nx-column-hidden\">" . nxHtmlValue($aRow["death_date"]) . "</td>\n"
            . "        <td class=\"nx-column-step-one\">" . nxRenderNicknameList(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(), false, 0, !empty($aHiddenInactive["nicknames"][$iSubjectId])) . "</td>\n"
            . "        <td>" . nxRenderAddressList(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), false, 0, $aRow["subject_name"], !empty($aHiddenInactive["addresses"][$iSubjectId]), $aIndexSettings) . "</td>\n"
            . "        <td>" . nxRenderContactList(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(), false, 0, true, true, !empty($aHiddenInactive["contacts"][$iSubjectId])) . "</td>\n"
            . "        <td class=\"nx-column-step-three\">" . nxRenderGroupList(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(), false) . "</td>\n"
            . "        <td class=\"nx-column-step-three\">" . nxRenderNoteList(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array(), false, 0, !empty($aHiddenInactive["notes"][$iSubjectId])) . "</td>\n"
            . "      </tr>\n";
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
