<?php

include "main.php";


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

if (!isset($_SESSION["ex_demo_full_list_settings"]) || !is_array($_SESSION["ex_demo_full_list_settings"])) {
    $_SESSION["ex_demo_full_list_settings"] = array();
}
foreach ($aFullListSettingsDefaults as $sFullListSettingName => $iFullListSettingDefault) {
    if (isset($_SESSION["ex_demo_full_list_settings"][$sFullListSettingName])) {
        $aFullListSettings[$sFullListSettingName] = (int)$_SESSION["ex_demo_full_list_settings"][$sFullListSettingName] == 1 ? 1 : 0;
    } else {
        $aFullListSettings[$sFullListSettingName] = $iFullListSettingDefault;
    }
}
$aFullListSettings = applyCountrySettings($aFullListSettings);
$aFullListSettings["hide_personal_number"] = 1;

$aFullListComplexFilterFields = getDemoFullListComplexFilterFields();
$aFullListComplexFilterOperators = getFullListComplexFilterOperators();
$aFullListComplexFilter = getDefaultFullListComplexFilter();
$aFullListComplexFilterDraft = getDefaultFullListComplexFilterDraft();

if (isset($_SESSION["ex_demo_full_list_complex_filter"]) && is_array($_SESSION["ex_demo_full_list_complex_filter"])) {
    $aFullListComplexFilter = normalizeDemoFullListComplexFilter($_SESSION["ex_demo_full_list_complex_filter"], $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
}
if (isset($_SESSION["ex_demo_full_list_complex_filter_draft"]) && is_array($_SESSION["ex_demo_full_list_complex_filter_draft"])) {
    $aFullListComplexFilterDraft = normalizeDemoFullListComplexFilterDraft($_SESSION["ex_demo_full_list_complex_filter_draft"], $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
} elseif (count($aFullListComplexFilter["conditions"]) > 0) {
    $aFullListComplexFilterDraft = normalizeDemoFullListComplexFilterDraft($aFullListComplexFilter, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("ex_csrf_token", true);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_full_list_settings") {
    foreach ($aFullListSettingsDefaults as $sFullListSettingName => $iFullListSettingDefault) {
        $aFullListSettings[$sFullListSettingName] = isset($_POST[$sFullListSettingName]) && (string)$_POST[$sFullListSettingName] == "1" ? 1 : 0;
    }
    $aFullListSettings = saveCountrySettings($aFullListSettings, $_POST);
    $aFullListSettings["hide_personal_number"] = 1;
    $_SESSION["ex_demo_full_list_settings"] = removeCountrySettings($aFullListSettings);
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_full_list_complex_filter") {
    $aFullListComplexFilterPayload = getFullListComplexFilterPostPayload();
    $aFullListComplexFilterDraft = normalizeDemoFullListComplexFilterDraft($aFullListComplexFilterPayload, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $aFullListComplexFilter = normalizeDemoFullListComplexFilter($aFullListComplexFilterPayload, $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $_SESSION["ex_demo_full_list_complex_filter"] = $aFullListComplexFilter;
    $_SESSION["ex_demo_full_list_complex_filter_draft"] = $aFullListComplexFilterDraft;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_full_list_complex_filter_draft") {
    $aFullListComplexFilterDraft = normalizeDemoFullListComplexFilterDraft(getFullListComplexFilterPostPayload(), $aFullListComplexFilterFields, $aFullListComplexFilterOperators);
    $_SESSION["ex_demo_full_list_complex_filter_draft"] = $aFullListComplexFilterDraft;
    session_write_close();
    sendJsonAndExit(array("success" => true));
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "reset_full_list_complex_filter") {
    $aFullListComplexFilter = getDefaultFullListComplexFilter();
    $_SESSION["ex_demo_full_list_complex_filter"] = $aFullListComplexFilter;
    session_write_close();
    sendSecurityHeaders();
    header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
    exit;
}

$blFullListComplexFilterActive = count($aFullListComplexFilter["conditions"]) > 0;
$aFullListComplexFilterRows = $aFullListComplexFilterDraft["conditions"];
while (count($aFullListComplexFilterRows) < 1) {
    $aFullListComplexFilterRows[] = array(
        "field" => "subject_name",
        "operator" => "contains",
        "value" => ""
    );
}

$aRows = array(
    array(
        "subject_id" => 101,
        "subject_type" => "person",
        "subject_name" => "Mgr. Ada M. Example, Ph.D.",
        "subject_sort_name" => "Example Ada",
        "is_active" => 1,
        "created_at" => "2026-07-03 10:00:00",
        "title_before" => "Mgr.",
        "first_name" => "Ada",
        "middle_name" => "M.",
        "last_name" => "Example",
        "title_after" => "Ph.D.",
        "birth_name" => "Tester",
        "birth_number" => "850412/1234",
        "birth_date" => "1985-04-12",
        "death_date" => "",
        "birthday_served_at" => "2026-04-12 08:00:00",
        "inter_served_at" => "2026-06-01 09:00:00"
    ),
    array(
        "subject_id" => 102,
        "subject_type" => "service",
        "subject_name" => "Example Service Desk",
        "subject_sort_name" => "Example Service Desk",
        "is_active" => 1,
        "created_at" => "2026-07-03 10:05:00",
        "title_before" => "",
        "first_name" => "",
        "middle_name" => "",
        "last_name" => "",
        "title_after" => "",
        "birth_name" => "",
        "birth_number" => "",
        "birth_date" => "",
        "death_date" => ""
    ),
    array(
        "subject_id" => 103,
        "subject_type" => "organization",
        "subject_name" => "Inactive Organization",
        "subject_sort_name" => "Inactive Organization",
        "is_active" => 0,
        "created_at" => "2026-07-03 10:10:00",
        "title_before" => "",
        "first_name" => "",
        "middle_name" => "",
        "last_name" => "",
        "title_after" => "",
        "birth_name" => "",
        "birth_number" => "",
        "birth_date" => "",
        "death_date" => ""
    ),
    array(
        "subject_id" => 104,
        "subject_type" => "other",
        "subject_name" => "Empty Dummy Subject",
        "subject_sort_name" => "Empty Dummy Subject",
        "is_active" => 1,
        "created_at" => "2026-07-03 10:15:00",
        "title_before" => "",
        "first_name" => "",
        "middle_name" => "",
        "last_name" => "",
        "title_after" => "",
        "birth_name" => "",
        "birth_number" => "",
        "birth_date" => "",
        "death_date" => ""
    ),
    array(
        "subject_id" => 105,
        "subject_type" => "person",
        "subject_name" => "Ing. Bruno K. Sample",
        "subject_sort_name" => "Sample Bruno",
        "is_active" => 1,
        "created_at" => "2026-07-03 10:20:00",
        "title_before" => "Ing.",
        "first_name" => "Bruno",
        "middle_name" => "K.",
        "last_name" => "Sample",
        "title_after" => "",
        "birth_name" => "",
        "birth_number" => "771105/1234",
        "birth_date" => "1977-11-05",
        "death_date" => "",
        "birthday_served_at" => "",
        "inter_served_at" => "2026-05-10 14:30:00"
    ),
    array(
        "subject_id" => 106,
        "subject_type" => "service",
        "subject_name" => "Global Social Profiles",
        "subject_sort_name" => "Global Social Profiles",
        "is_active" => 1,
        "created_at" => "2026-07-03 10:25:00",
        "title_before" => "",
        "first_name" => "",
        "middle_name" => "",
        "last_name" => "",
        "title_after" => "",
        "birth_name" => "",
        "birth_number" => "",
        "birth_date" => "",
        "death_date" => ""
    ),
    array(
        "subject_id" => 107,
        "subject_type" => "organization",
        "subject_name" => "Foreign Archive Office",
        "subject_sort_name" => "Foreign Archive Office",
        "is_active" => 1,
        "created_at" => "2026-07-03 10:30:00",
        "title_before" => "",
        "first_name" => "",
        "middle_name" => "",
        "last_name" => "",
        "title_after" => "",
        "birth_name" => "",
        "birth_number" => "",
        "birth_date" => "",
        "death_date" => ""
    )
);

$aContacts = array(
    101 => array(
        array("subject_contact_id" => 1001, "contact_id" => 2001, "contact_type" => "cell", "contact_value" => "+420731000001", "note" => "personal", "is_primary" => 1, "is_active" => 1),
        array("subject_contact_id" => 1002, "contact_id" => 2002, "contact_type" => "email", "contact_value" => "ada.example@example.invalid", "note" => "", "is_primary" => 0, "is_active" => 1),
        array("subject_contact_id" => 1003, "contact_id" => 2003, "contact_type" => "icq", "contact_value" => "123456", "note" => "old", "is_primary" => 0, "is_active" => 0)
    ),
    102 => array(
        array("subject_contact_id" => 1004, "contact_id" => 2004, "contact_type" => "web", "contact_value" => "example.invalid/support", "note" => "support portal", "is_primary" => 1, "is_active" => 1),
        array("subject_contact_id" => 1005, "contact_id" => 2005, "contact_type" => "telegram", "contact_value" => "@example_support", "note" => "", "is_primary" => 0, "is_active" => 1),
        array("subject_contact_id" => 1006, "contact_id" => 2006, "contact_type" => "pager", "contact_value" => "+420777000999", "note" => "legacy", "is_primary" => 0, "is_active" => 0)
    ),
    103 => array(
        array("subject_contact_id" => 1007, "contact_id" => 2007, "contact_type" => "fax", "contact_value" => "+420222000003", "note" => "inactive", "is_primary" => 0, "is_active" => 0)
    ),
    105 => array(
        array("subject_contact_id" => 1009, "contact_id" => 2009, "contact_type" => "whatsapp", "contact_value" => "+420731000105", "note" => "", "is_primary" => 1, "is_active" => 1),
        array("subject_contact_id" => 1008, "contact_id" => 2008, "contact_type" => "landline", "contact_value" => "+420222111222", "note" => "office", "is_primary" => 0, "is_active" => 1),
        array("subject_contact_id" => 1010, "contact_id" => 2010, "contact_type" => "skype", "contact_value" => "bruno.sample", "note" => "legacy", "is_primary" => 0, "is_active" => 0)
    ),
    106 => array(
        array("subject_contact_id" => 1011, "contact_id" => 2011, "contact_type" => "github", "contact_value" => "global-social", "note" => "code", "is_primary" => 0, "is_active" => 1),
        array("subject_contact_id" => 1012, "contact_id" => 2012, "contact_type" => "mastodon", "contact_value" => "@global@example.social", "note" => "", "is_primary" => 0, "is_active" => 1),
        array("subject_contact_id" => 1013, "contact_id" => 2013, "contact_type" => "youtube", "contact_value" => "@globaldemo", "note" => "videos", "is_primary" => 0, "is_active" => 1),
        array("subject_contact_id" => 1018, "contact_id" => 2018, "contact_type" => "bankaccount", "contact_value" => "123456789/0100", "note" => "bank contact", "is_primary" => 0, "is_active" => 1),
        array("subject_contact_id" => 1014, "contact_id" => 2014, "contact_type" => "paypal", "contact_value" => "globaldemo", "note" => "payments", "is_primary" => 0, "is_active" => 0)
    ),
    107 => array(
        array("subject_contact_id" => 1015, "contact_id" => 2015, "contact_type" => "email", "contact_value" => "archive@example.invalid", "note" => "registry", "is_primary" => 1, "is_active" => 1),
        array("subject_contact_id" => 1016, "contact_id" => 2016, "contact_type" => "orcid", "contact_value" => "0000-0002-1825-0097", "note" => "sample researcher id", "is_primary" => 0, "is_active" => 1),
        array("subject_contact_id" => 1017, "contact_id" => 2017, "contact_type" => "viber", "contact_value" => "+421900000107", "note" => "old", "is_primary" => 0, "is_active" => 0)
    )
);

$aNicknames = array(
    101 => array(
        array("id" => 3001, "subject_id" => 101, "nickname" => "Ada", "context" => "common", "note" => "", "is_primary" => 1, "is_active" => 1),
        array("id" => 3002, "subject_id" => 101, "nickname" => "A-Test", "context" => "old", "note" => "kept for test", "is_primary" => 0, "is_active" => 0)
    ),
    102 => array(
        array("id" => 3003, "subject_id" => 102, "nickname" => "Helpdesk", "context" => "internal", "note" => "", "is_primary" => 1, "is_active" => 1)
    ),
    105 => array(
        array("id" => 3004, "subject_id" => 105, "nickname" => "BKS", "context" => "initials", "note" => "", "is_primary" => 1, "is_active" => 1),
        array("id" => 3005, "subject_id" => 105, "nickname" => "Bruno Old", "context" => "archive", "note" => "inactive alias", "is_primary" => 0, "is_active" => 0)
    ),
    106 => array(
        array("id" => 3006, "subject_id" => 106, "nickname" => "Social Hub", "context" => "public", "note" => "", "is_primary" => 1, "is_active" => 1)
    ),
    107 => array(
        array("id" => 3007, "subject_id" => 107, "nickname" => "Archive Office", "context" => "short", "note" => "", "is_primary" => 1, "is_active" => 1)
    )
);

$aAddresses = array(
    101 => array(
        array(
            "id" => 4001,
            "subject_id" => 101,
            "address_type" => "home",
            "organization_name" => "",
            "department_name" => "",
            "care_of" => "",
            "street_name" => "Example Street",
            "house_number" => "12",
            "evidence_number" => "",
            "orientation_number" => "4",
            "orientation_suffix" => "B",
            "address_line2" => "",
            "city" => "Prague",
            "city_part" => "Vinohrady",
            "postal_code" => "120 00",
            "region" => "Prague",
            "country" => "CZ",
            "note" => "doorbell Example",
            "is_primary" => 1,
            "is_active" => 1
        ),
        array(
            "id" => 4002,
            "subject_id" => 101,
            "address_type" => "old",
            "organization_name" => "",
            "department_name" => "",
            "care_of" => "",
            "street_name" => "Old Street",
            "house_number" => "8",
            "evidence_number" => "",
            "orientation_number" => "",
            "orientation_suffix" => "",
            "address_line2" => "",
            "city" => "Brno",
            "city_part" => "",
            "postal_code" => "602 00",
            "region" => "South Moravia",
            "country" => "CS",
            "note" => "inactive Czechoslovak address",
            "is_primary" => 0,
            "is_active" => 0
        )
    ),
    102 => array(
        array(
            "id" => 4003,
            "subject_id" => 102,
            "address_type" => "work",
            "organization_name" => "Example Service Desk",
            "department_name" => "Support",
            "care_of" => "Reception",
            "street_name" => "Support Avenue",
            "house_number" => "34",
            "evidence_number" => "",
            "orientation_number" => "",
            "orientation_suffix" => "",
            "address_line2" => "2nd floor",
            "city" => "Ostrava",
            "city_part" => "Center",
            "postal_code" => "702 00",
            "region" => "Moravia-Silesia",
            "country" => "CZ",
            "note" => "",
            "is_primary" => 1,
            "is_active" => 1
        )
    ),
    105 => array(
        array(
            "id" => 4004,
            "subject_id" => 105,
            "address_type" => "cottage",
            "organization_name" => "",
            "department_name" => "",
            "care_of" => "",
            "street_name" => "River Road",
            "house_number" => "",
            "evidence_number" => "58",
            "orientation_number" => "",
            "orientation_suffix" => "",
            "address_line2" => "near the bridge",
            "city" => "Cesky Krumlov",
            "city_part" => "",
            "postal_code" => "381 01",
            "region" => "South Bohemia",
            "country" => "CZ",
            "note" => "seasonal",
            "is_primary" => 0,
            "is_active" => 1
        )
    ),
    106 => array(
        array(
            "id" => 4005,
            "subject_id" => 106,
            "address_type" => "billing",
            "organization_name" => "Global Social Profiles",
            "department_name" => "Accounts",
            "care_of" => "",
            "street_name" => "Market Street",
            "house_number" => "1",
            "evidence_number" => "",
            "orientation_number" => "",
            "orientation_suffix" => "",
            "address_line2" => "Suite 200",
            "city" => "San Francisco",
            "city_part" => "",
            "postal_code" => "94105",
            "region" => "California",
            "country" => "US",
            "note" => "foreign billing address",
            "is_primary" => 1,
            "is_active" => 1
        ),
        array(
            "id" => 4006,
            "subject_id" => 106,
            "address_type" => "delivery",
            "organization_name" => "Global Social Profiles",
            "department_name" => "Logistics",
            "care_of" => "Demo Recipient",
            "street_name" => "Old Dock",
            "house_number" => "7",
            "evidence_number" => "",
            "orientation_number" => "",
            "orientation_suffix" => "",
            "address_line2" => "",
            "city" => "Hamburg",
            "city_part" => "HafenCity",
            "postal_code" => "20457",
            "region" => "Hamburg",
            "country" => "DE",
            "note" => "inactive delivery address",
            "is_primary" => 0,
            "is_active" => 0
        )
    ),
    107 => array(
        array(
            "id" => 4007,
            "subject_id" => 107,
            "address_type" => "foreign",
            "organization_name" => "Foreign Archive Office",
            "department_name" => "Reading Room",
            "care_of" => "",
            "street_name" => "Archive Lane",
            "house_number" => "9",
            "evidence_number" => "",
            "orientation_number" => "11",
            "orientation_suffix" => "A",
            "address_line2" => "Box 42",
            "city" => "Bratislava",
            "city_part" => "Stare Mesto",
            "postal_code" => "811 01",
            "region" => "Bratislava",
            "country" => "SK",
            "note" => "",
            "is_primary" => 1,
            "is_active" => 1
        )
    )
);

$aGroups = array(
    101 => array(
        array("subject_id" => 101, "group_id" => 1, "name" => "Users"),
        array("subject_id" => 101, "group_id" => 2, "name" => "Dummy Contacts")
    ),
    102 => array(
        array("subject_id" => 102, "group_id" => 3, "name" => "Services")
    ),
    105 => array(
        array("subject_id" => 105, "group_id" => 2, "name" => "Dummy Contacts"),
        array("subject_id" => 105, "group_id" => 4, "name" => "Researchers")
    ),
    106 => array(
        array("subject_id" => 106, "group_id" => 3, "name" => "Services"),
        array("subject_id" => 106, "group_id" => 5, "name" => "Social")
    ),
    107 => array(
        array("subject_id" => 107, "group_id" => 6, "name" => "Foreign Offices"),
        array("subject_id" => 107, "group_id" => 7, "name" => "Archive")
    )
);

$aNotes = array(
    101 => array(
        array("id" => 5001, "subject_id" => 101, "note_text" => "Visible dummy note.", "is_primary" => 1, "is_active" => 1),
        array("id" => 5002, "subject_id" => 101, "note_text" => "Inactive dummy note.", "is_primary" => 0, "is_active" => 0)
    ),
    103 => array(
        array("id" => 5003, "subject_id" => 103, "note_text" => "Inactive organization note.", "is_primary" => 0, "is_active" => 0)
    ),
    105 => array(
        array("id" => 5004, "subject_id" => 105, "note_text" => "Person with mixed phone and messenger contacts.", "is_primary" => 0, "is_active" => 1)
    ),
    106 => array(
        array("id" => 5005, "subject_id" => 106, "note_text" => "Service row showing social and payment contact types.", "is_primary" => 1, "is_active" => 1),
        array("id" => 5006, "subject_id" => 106, "note_text" => "Old payment profile kept inactive.", "is_primary" => 0, "is_active" => 0)
    ),
    107 => array(
        array("id" => 5007, "subject_id" => 107, "note_text" => "Foreign address exercises country display.", "is_primary" => 0, "is_active" => 1)
    )
);

$aRows = applyDemoFullListComplexFilter($aRows, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aFullListSettings, $aFullListComplexFilter, $aFullListComplexFilterFields);
$aHiddenInactive = getHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aFullListSettings);
applySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aFullListSettings);

$aAllGroups = array(
    array("name" => "Users"),
    array("name" => "Dummy Contacts"),
    array("name" => "Services"),
    array("name" => "Researchers"),
    array("name" => "Social"),
    array("name" => "Foreign Offices"),
    array("name" => "Archive")
);

$aFullListComplexFilterGroups = array();
foreach ($aAllGroups as $aGroup) {
    $aFullListComplexFilterGroups[] = (string)$aGroup["name"];
}
$aFullListComplexFilterAddressTypes = array();
foreach (getAddressTypes() as $sAddressType) {
    $aFullListComplexFilterAddressTypes[] = array(
        "value" => $sAddressType,
        "label" => addressTypeLabel($sAddressType)
    );
}

$aDummySubjectEditors = array(
    101 => array("subject_id" => 101, "subject_type" => "person", "is_active" => 1, "subject_name_value" => "", "title_before" => "Mgr.", "first_name" => "Ada", "middle_name" => "M.", "last_name" => "Example", "title_after" => "Ph.D.", "birth_name" => "Tester", "birth_date" => "1985-04-12", "death_date" => ""),
    102 => array("subject_id" => 102, "subject_type" => "service", "is_active" => 1, "subject_name_value" => "Example Service Desk", "title_before" => "", "first_name" => "", "middle_name" => "", "last_name" => "", "title_after" => "", "birth_name" => "", "birth_date" => "", "death_date" => ""),
    103 => array("subject_id" => 103, "subject_type" => "organization", "is_active" => 0, "subject_name_value" => "Inactive Organization", "title_before" => "", "first_name" => "", "middle_name" => "", "last_name" => "", "title_after" => "", "birth_name" => "", "birth_date" => "", "death_date" => ""),
    104 => array("subject_id" => 104, "subject_type" => "other", "is_active" => 1, "subject_name_value" => "Empty Dummy Subject", "title_before" => "", "first_name" => "", "middle_name" => "", "last_name" => "", "title_after" => "", "birth_name" => "", "birth_date" => "", "death_date" => ""),
    105 => array("subject_id" => 105, "subject_type" => "person", "is_active" => 1, "subject_name_value" => "", "title_before" => "Ing.", "first_name" => "Bruno", "middle_name" => "K.", "last_name" => "Sample", "title_after" => "", "birth_name" => "", "birth_date" => "1977-11-05", "death_date" => ""),
    106 => array("subject_id" => 106, "subject_type" => "service", "is_active" => 1, "subject_name_value" => "Global Social Profiles", "title_before" => "", "first_name" => "", "middle_name" => "", "last_name" => "", "title_after" => "", "birth_name" => "", "birth_date" => "", "death_date" => ""),
    107 => array("subject_id" => 107, "subject_type" => "organization", "is_active" => 1, "subject_name_value" => "Foreign Archive Office", "title_before" => "", "first_name" => "", "middle_name" => "", "last_name" => "", "title_after" => "", "birth_name" => "", "birth_date" => "", "death_date" => "")
);

$aDummyPortalPermissions = array(
    array("permission_key" => "portal.view", "name" => "Portal View", "note" => "Dummy view permission."),
    array("permission_key" => "portal.full", "name" => "Portal Full", "note" => "Dummy full permission."),
    array("permission_key" => "ex.view", "name" => "EX View", "note" => "Dummy EX view permission."),
    array("permission_key" => "ex.full", "name" => "EX Full", "note" => "Dummy EX full permission."),
    array("permission_key" => "kf.view", "name" => "KF View", "note" => "Dummy KF view permission."),
    array("permission_key" => "kf.full", "name" => "KF Full", "note" => "Dummy KF full permission.")
);

$aDummySubjectPortals = array(
    101 => array("subject_id" => 101, "subject_name" => "Mgr. Ada M. Example, Ph.D.", "subject_type" => "person", "portal_user" => array("has_user" => 1, "user_name" => "ada", "is_active" => 1, "direct_permission_keys" => array("portal.view"), "effective_permission_keys" => array("portal.view", "portal.full")), "portal_permissions" => $aDummyPortalPermissions),
    102 => array("subject_id" => 102, "subject_name" => "Example Service Desk", "subject_type" => "service", "portal_user" => array("has_user" => 1, "user_name" => "service", "is_active" => 1, "direct_permission_keys" => array("portal.full"), "effective_permission_keys" => array("portal.full")), "portal_permissions" => $aDummyPortalPermissions),
    103 => array("subject_id" => 103, "subject_name" => "Inactive Organization", "subject_type" => "organization", "portal_user" => array("has_user" => 0, "user_name" => "", "is_active" => 1, "direct_permission_keys" => array(), "effective_permission_keys" => array()), "portal_permissions" => $aDummyPortalPermissions),
    104 => array("subject_id" => 104, "subject_name" => "Empty Dummy Subject", "subject_type" => "other", "portal_user" => array("has_user" => 0, "user_name" => "", "is_active" => 1, "direct_permission_keys" => array(), "effective_permission_keys" => array()), "portal_permissions" => $aDummyPortalPermissions),
    105 => array("subject_id" => 105, "subject_name" => "Ing. Bruno K. Sample", "subject_type" => "person", "portal_user" => array("has_user" => 0, "user_name" => "", "is_active" => 1, "direct_permission_keys" => array(), "effective_permission_keys" => array()), "portal_permissions" => $aDummyPortalPermissions),
    106 => array("subject_id" => 106, "subject_name" => "Global Social Profiles", "subject_type" => "service", "portal_user" => array("has_user" => 1, "user_name" => "social", "is_active" => 0, "direct_permission_keys" => array("portal.view"), "effective_permission_keys" => array("portal.view")), "portal_permissions" => $aDummyPortalPermissions),
    107 => array("subject_id" => 107, "subject_name" => "Foreign Archive Office", "subject_type" => "organization", "portal_user" => array("has_user" => 0, "user_name" => "", "is_active" => 1, "direct_permission_keys" => array(), "effective_permission_keys" => array()), "portal_permissions" => $aDummyPortalPermissions)
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
  <title><?php echo html(getPageTitleText("Demo Subjects", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("ex_csrf_token")); ?>">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body data-calendar-first-day="<?php echo html($iCalendarFirstDay); ?>" data-date-input-format="<?php echo html($sDateInputFormat); ?>" data-date-input-pattern="<?php echo html($sDateInputPattern); ?>" data-hide-subject-birth-number="1">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="nx-subjects-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-complex-filter-open<?php echo $blFullListComplexFilterActive ? " complex-filter-active" : ""; ?>" aria-pressed="<?php echo $blFullListComplexFilterActive ? "true" : "false"; ?>">Complex</button>
    <button type="submit" class="button-link js-complex-filter-page-reset<?php echo $blFullListComplexFilterActive ? " complex-filter-active" : ""; ?>" form="complex-filter-reset-form" title="Reset complex filter">Reset</button>
    <button type="button" class="button-link js-index-settings-open">Settings</button>
    <button type="button" class="button-link js-add-subject">New</button>
  </p>
  <form id="complex-filter-reset-form" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded" hidden>
    <input type="hidden" name="action" value="reset_full_list_complex_filter">
    <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken("ex_csrf_token")); ?>">
  </form>
<?php

echo renderCountryDatalist();

?>
  <div class="confirm-dialog complex-filter-dialog" id="complex-filter-dialog" hidden>
    <form class="confirm-dialog-box complex-filter-form" method="post" action="<?php echo htmlspecialchars($sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="save_full_list_complex_filter">
      <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken("ex_csrf_token")); ?>">
      <div class="confirm-dialog-header">
        <strong>Complex Filter</strong>
        <button type="button" class="confirm-dialog-close js-complex-filter-close" aria-label="Close">&times;</button>
      </div>
      <div class="complex-filter-options">
        <div class="complex-filter-match">
          <label><input type="radio" name="complex_filter_match" value="all"<?php echo $aFullListComplexFilterDraft["match"] == "all" ? " checked" : ""; ?>> Match all conditions</label>
          <label><input type="radio" name="complex_filter_match" value="any"<?php echo $aFullListComplexFilterDraft["match"] == "any" ? " checked" : ""; ?>> Match any condition</label>
        </div>
        <div class="complex-filter-rows js-complex-filter-rows" data-empty-row-count="1" data-group-options="<?php echo html(json_encode($aFullListComplexFilterGroups)); ?>" data-address-type-options="<?php echo html(json_encode($aFullListComplexFilterAddressTypes)); ?>">
<?php

foreach ($aFullListComplexFilterRows as $aCondition) {
    $sComplexField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "subject_name";
    if ($sComplexField != "" && !isset($aFullListComplexFilterFields[$sComplexField])) {
        $sComplexField = "subject_name";
    }
    $sComplexOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "contains";
    if ($sComplexOperator != "" && !isset($aFullListComplexFilterOperators[$sComplexOperator])) {
        $sComplexOperator = "contains";
    }
    $sComplexValueType = $sComplexField != "" && isset($aFullListComplexFilterFields[$sComplexField]["value_type"]) ? (string)$aFullListComplexFilterFields[$sComplexField]["value_type"] : "text";
    if ($sComplexValueType == "boolean") {
        $sComplexOperator = "equals";
    }
    $sComplexValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
    $blComplexNeedsValue = $sComplexOperator == "" || !empty($aFullListComplexFilterOperators[$sComplexOperator]["needs_value"]);
    $blComplexOperatorHidden = $sComplexValueType == "boolean";
    echo "          <div class=\"complex-filter-row js-complex-filter-row\">\n",
        "            <select name=\"complex_filter_field[]\" class=\"js-complex-filter-field\">" . renderFullListComplexFilterFieldOptions($aFullListComplexFilterFields, $sComplexField) . "</select>\n",
        "            <select name=\"complex_filter_operator[]\" class=\"js-complex-filter-operator\"" . ($blComplexOperatorHidden ? " disabled aria-hidden=\"true\" tabindex=\"-1\"" : "") . ">" . renderDemoFullListComplexFilterOperatorOptions($aFullListComplexFilterOperators, $sComplexOperator) . "</select>\n",
        "            <input type=\"text\" name=\"complex_filter_value[]\" class=\"js-complex-filter-value\" value=\"" . html($sComplexValue) . "\" autocomplete=\"off\"" . ($blComplexNeedsValue ? "" : " disabled") . ">\n",
        "            <button type=\"button\" class=\"complex-filter-remove js-complex-filter-remove\" title=\"Remove condition\" aria-label=\"Remove condition\">&times;</button>\n",
        "          </div>\n";
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
      <input type="hidden" name="ex_csrf_token" value="<?php echo html(getCsrfToken("ex_csrf_token")); ?>">
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
        <label><input type="checkbox" name="show_czechia_country_in_czech" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aFullListSettings["show_czechia_country_in_czech"] ? "1" : "0") . "\"" . ($aFullListSettings["show_czechia_country"] && $aFullListSettings["show_czechia_country_in_czech"] ? " checked" : "") . ($aFullListSettings["show_czechia_country"] ? "" : " disabled"); ?>> Show the country Czechia in Czech</label>
        <label><input type="checkbox" name="show_czechia_country_as_czech_republic" value="1" class="js-czechia-country-dependent"<?php echo " data-czechia-stored=\"" . ($aFullListSettings["show_czechia_country_as_czech_republic"] ? "1" : "0") . "\"" . ($aFullListSettings["show_czechia_country"] && $aFullListSettings["show_czechia_country_as_czech_republic"] ? " checked" : "") . ($aFullListSettings["show_czechia_country"] ? "" : " disabled"); ?>> Show &#268;esk&#225; republika instead of &#268;esko</label>
      </div>
      <?php echo renderSettingsScopeNote(); ?>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Save</button>
        <button type="button" class="confirm-dialog-button js-index-settings-cancel">Cancel</button>
      </div>
    </form>
  </div>
<?php

echo "  <datalist id=\"nx-group-list\">\n";
foreach ($aAllGroups as $aGroup) {
    echo "    <option value=\"" . html($aGroup["name"]) . "\"></option>\n";
}
echo "  </datalist>\n";
if (!$aRows) {
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
        $iSubjectId = (int)$aRow["subject_id"];
        $sSubjectJson = htmlspecialchars(json_encode($aDummySubjectEditors[$iSubjectId], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $sPortalJson = htmlspecialchars(json_encode($aDummySubjectPortals[$iSubjectId], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $sRowHtml = renderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, true, $aHiddenInactive, $aFullListSettings);
        $sRowHtml = str_replace(
            "class=\"nx-item-action js-edit-subject\" data-subject-id=\"" . html($iSubjectId) . "\"",
            "class=\"nx-item-action js-edit-subject\" data-subject-id=\"" . html($iSubjectId) . "\" data-test-subject=\"" . $sSubjectJson . "\"",
            $sRowHtml
        );
        $sRowHtml = str_replace(
            "class=\"nx-item-action js-edit-subject-portal\" data-subject-id=\"" . html($iSubjectId) . "\"",
            "class=\"nx-item-action js-edit-subject-portal\" data-subject-id=\"" . html($iSubjectId) . "\" data-test-subject-portal=\"" . $sPortalJson . "\"",
            $sRowHtml
        );
        echo $sRowHtml;
    }

    echo "    </tbody>\n",
        "  </table>\n";
}
echo renderEmojiData();

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
