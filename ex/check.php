<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess("ex", "csrf_token");


$aChecks = array(
    array(
        "title" => "Subject contact links with missing subjects",
        "type" => "error",
        "sql" => "SELECT sc.id AS subject_contact_id, sc.subject_id, sc.contact_id FROM ex_subject_contacts AS sc LEFT JOIN ex_subjects AS s ON s.id = sc.subject_id WHERE s.id IS NULL ORDER BY sc.id ASC"
    ),
    array(
        "title" => "Subject contact links with missing contacts",
        "type" => "error",
        "sql" => "SELECT sc.id AS subject_contact_id, sc.subject_id, sc.contact_id FROM ex_subject_contacts AS sc LEFT JOIN ex_contacts AS c ON c.id = sc.contact_id WHERE c.id IS NULL ORDER BY sc.id ASC"
    ),
    array(
        "title" => "Person rows with missing subjects",
        "type" => "error",
        "sql" => "SELECT p.subject_id, p.title_before, p.first_name, p.middle_name, p.last_name, p.title_after, p.birth_name, p.birth_number, p.birth_date, p.death_date FROM ex_persons AS p LEFT JOIN ex_subjects AS s ON s.id = p.subject_id WHERE s.id IS NULL ORDER BY p.subject_id ASC"
    ),
    array(
        "title" => "Person rows assigned to non-person subjects",
        "type" => "error",
        "sql" => "SELECT p.subject_id, s.subject_type, p.title_before, p.first_name, p.middle_name, p.last_name, p.title_after, p.birth_name, p.birth_number, p.birth_date, p.death_date FROM ex_persons AS p INNER JOIN ex_subjects AS s ON s.id = p.subject_id WHERE s.subject_type <> 'person' ORDER BY p.subject_id ASC"
    ),
    array(
        "title" => "Subject names with missing subjects",
        "type" => "error",
        "sql" => "SELECT sn.subject_id, sn.name FROM ex_subject_names AS sn LEFT JOIN ex_subjects AS s ON s.id = sn.subject_id WHERE s.id IS NULL ORDER BY sn.subject_id ASC"
    ),
    array(
        "title" => "Subject names assigned to person subjects",
        "type" => "error",
        "sql" => "SELECT sn.subject_id, s.subject_type, sn.name FROM ex_subject_names AS sn INNER JOIN ex_subjects AS s ON s.id = sn.subject_id WHERE s.subject_type = 'person' ORDER BY sn.subject_id ASC"
    ),
    array(
        "title" => "Addresses with missing subjects",
        "type" => "error",
        "sql" => "SELECT a.id AS address_id, a.subject_id, a.address_type, a.organization_name, a.department_name, a.care_of, a.street_name, a.house_number, a.evidence_number, a.orientation_number, a.orientation_suffix, a.address_line2, a.city, a.city_part, a.postal_code, a.region, a.country, a.note FROM ex_subject_addresses AS a LEFT JOIN ex_subjects AS s ON s.id = a.subject_id WHERE s.id IS NULL ORDER BY a.id ASC"
    ),
    array(
        "title" => "Nicknames with missing subjects",
        "type" => "error",
        "sql" => "SELECT n.id AS nickname_id, n.subject_id, n.nickname, n.context, n.note FROM ex_subject_nicknames AS n LEFT JOIN ex_subjects AS s ON s.id = n.subject_id WHERE s.id IS NULL ORDER BY n.id ASC"
    ),
    array(
        "title" => "Notes with missing subjects",
        "type" => "error",
        "sql" => "SELECT n.id AS note_id, n.subject_id, n.note_text FROM ex_subject_notes AS n LEFT JOIN ex_subjects AS s ON s.id = n.subject_id WHERE s.id IS NULL ORDER BY n.id ASC"
    ),
    array(
        "title" => "Group links with missing subjects",
        "type" => "error",
        "sql" => "SELECT sg.subject_id, sg.group_id FROM ex_group_subject AS sg LEFT JOIN ex_subjects AS s ON s.id = sg.subject_id WHERE s.id IS NULL ORDER BY sg.subject_id ASC, sg.group_id ASC"
    ),
    array(
        "title" => "Group links with missing groups",
        "type" => "error",
        "sql" => "SELECT sg.subject_id, sg.group_id FROM ex_group_subject AS sg LEFT JOIN ex_groups AS g ON g.id = sg.group_id WHERE g.id IS NULL ORDER BY sg.subject_id ASC, sg.group_id ASC"
    ),
    array(
        "title" => "Subjects with no or very little data",
        "type" => "warning",
        "sql" => "SELECT s.id AS subject_id, s.subject_type, COALESCE(IF(s.subject_type = 'person', NULLIF(TRIM(CONCAT_WS(' ', NULLIF(p.title_before, ''), NULLIF(p.first_name, ''), NULLIF(p.middle_name, ''), NULLIF(p.last_name, ''), NULLIF(p.title_after, ''))), ''), NULL), NULLIF(sn.name, ''), 'Unnamed subject') AS subject_name, s.is_active, s.legacy_id, p.first_name, p.middle_name, p.last_name, sn.name AS subject_name_value, (IF(s.subject_type = 'person', IF(NULLIF(TRIM(COALESCE(p.title_before, '')), '') IS NULL, 0, 1) + IF(NULLIF(TRIM(COALESCE(p.middle_name, '')), '') IS NULL, 0, 1) + IF(NULLIF(TRIM(COALESCE(p.title_after, '')), '') IS NULL, 0, 1) + IF(NULLIF(TRIM(COALESCE(p.birth_name, '')), '') IS NULL, 0, 1) + IF(NULLIF(TRIM(COALESCE(p.birth_number, '')), '') IS NULL, 0, 1) + IF(p.birth_date IS NULL, 0, 1) + IF(p.death_date IS NULL, 0, 1) + IF(p.birthday_served_at IS NULL, 0, 1) + IF(p.inter_served_at IS NULL, 0, 1), 0) + COALESCE(sc.contact_count, 0) + COALESCE(a.address_count, 0) + COALESCE(n.nickname_count, 0) + COALESCE(nt.note_count, 0) + COALESCE(g.group_count, 0) + COALESCE(u.user_count, 0)) AS additional_data_count, COALESCE(sc.contact_count, 0) AS contact_count, COALESCE(a.address_count, 0) AS address_count, COALESCE(n.nickname_count, 0) AS nickname_count, COALESCE(nt.note_count, 0) AS note_count, COALESCE(g.group_count, 0) AS group_count, COALESCE(u.user_count, 0) AS portal_user_count, s.created_at, s.updated_at FROM ex_subjects AS s LEFT JOIN ex_persons AS p ON p.subject_id = s.id LEFT JOIN ex_subject_names AS sn ON sn.subject_id = s.id LEFT JOIN (SELECT subject_id, COUNT(*) AS contact_count FROM ex_subject_contacts GROUP BY subject_id) AS sc ON sc.subject_id = s.id LEFT JOIN (SELECT subject_id, COUNT(*) AS address_count FROM ex_subject_addresses GROUP BY subject_id) AS a ON a.subject_id = s.id LEFT JOIN (SELECT subject_id, COUNT(*) AS nickname_count FROM ex_subject_nicknames GROUP BY subject_id) AS n ON n.subject_id = s.id LEFT JOIN (SELECT subject_id, COUNT(*) AS note_count FROM ex_subject_notes GROUP BY subject_id) AS nt ON nt.subject_id = s.id LEFT JOIN (SELECT subject_id, COUNT(*) AS group_count FROM ex_group_subject GROUP BY subject_id) AS g ON g.subject_id = s.id LEFT JOIN (SELECT subject_id, COUNT(*) AS user_count FROM ex_users GROUP BY subject_id) AS u ON u.subject_id = s.id HAVING additional_data_count <= 1 ORDER BY additional_data_count ASC, s.subject_type ASC, subject_name COLLATE utf8mb4_czech_ci ASC, s.id ASC"
    ),
    array(
        "title" => "Unassigned contacts kept for review",
        "type" => "warning",
        "sql" => "SELECT c.id AS contact_id, ct.name AS contact_type, c.contact_value FROM ex_contacts AS c LEFT JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id LEFT JOIN ex_subject_contacts AS sc ON sc.contact_id = c.id WHERE sc.contact_id IS NULL ORDER BY ct.name ASC, c.contact_value ASC, c.id ASC"
    )
);


$blHasErrors = false;
$blHasWarnings = false;
try {
    foreach ($aChecks as $iCheckIndex => $aCheck) {
        $oStatement = $oPdo->prepare($aCheck["sql"]);
        $oStatement->execute();
        $aRows = $oStatement->fetchAll();
        $aChecks[$iCheckIndex]["rows"] = $aRows;
        if (count($aRows) > 0 && $aCheck["type"] == "error") {
            $blHasErrors = true;
        }
        if (count($aRows) > 0 && $aCheck["type"] == "warning") {
            $blHasWarnings = true;
        }
    }
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}


$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link rel="icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" title="Original">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" title="Graphite">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" title="Midnight">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" title="Slate">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" title="Sepia">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" title="Sand">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" title="Forest">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" title="Moss">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" title="Ocean">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" title="Ice">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" title="Lavender">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" title="Rose">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" title="Copper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" title="Burgundy">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" title="Monochrome">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" title="High Contrast">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" title="Soft">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" title="Paper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" title="Terminal">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" title="Cobalt">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();
echo "  </p>\n",
    "  <h1>Database Consistency</h1>\n";
if ($blHasErrors) {
    echo "  <p class=\"consistency-status consistency-status-error\">Database inconsistencies were found.</p>\n";
} elseif ($blHasWarnings) {
    echo "  <p class=\"consistency-status consistency-status-warning\">No broken required links were found. Some records are listed for review.</p>\n";
} else {
    echo "  <p class=\"consistency-status consistency-status-ok\">No database inconsistencies were found.</p>\n";
}

foreach ($aChecks as $aCheck) {
    $aRows = isset($aCheck["rows"]) ? $aCheck["rows"] : array();
    echo "  <h2>" . html($aCheck["title"]) . " (" . count($aRows) . ")</h2>\n";
    if (!$aRows) {
        echo "  <p>" . $sEmptyValueEmoji . "</p>\n";
        continue;
    }
    $aColumns = array_keys($aRows[0]);
    echo "  <table class=\"consistency-table\">\n",
        "    <thead>\n",
        "      <tr>\n";
    foreach ($aColumns as $sColumn) {
        echo "        <th>" . html($sColumn) . "</th>\n";
    }
    echo "      </tr>\n",
        "    </thead>\n",
        "    <tbody>\n";
    foreach ($aRows as $aRow) {
        echo "      <tr>\n";
        foreach ($aColumns as $sColumn) {
            echo "        <td>" . htmlValue($aRow[$sColumn]) . "</td>\n";
        }
        echo "      </tr>\n";
    }
    echo "    </tbody>\n",
        "  </table>\n";
}

?>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
