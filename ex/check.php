<?php

include "main.php";


requireExFullAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

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
        "sql" => "SELECT sg.subject_id, sg.group_id FROM ex_subject_groups AS sg LEFT JOIN ex_subjects AS s ON s.id = sg.subject_id WHERE s.id IS NULL ORDER BY sg.subject_id ASC, sg.group_id ASC"
    ),
    array(
        "title" => "Group links with missing groups",
        "type" => "error",
        "sql" => "SELECT sg.subject_id, sg.group_id FROM ex_subject_groups AS sg LEFT JOIN ex_groups AS g ON g.id = sg.group_id WHERE g.id IS NULL ORDER BY sg.subject_id ASC, sg.group_id ASC"
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
        $aRows = $oStatement->fetchAll(PDO::FETCH_ASSOC);
        $aChecks[$iCheckIndex]["rows"] = $aRows;
        if (count($aRows) > 0 && $aCheck["type"] == "error") {
            $blHasErrors = true;
        }
        if (count($aRows) > 0 && $aCheck["type"] == "warning") {
            $blHasWarnings = true;
        }
    }
} catch (Exception $oException) {
    send500AndExit("Database error: " . $oException->getMessage());
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
  <title><?php echo nxHtml(getExPageTitleText("Database Consistency", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

nxRenderExMenu();
echo "  </p>\n";
echo "  <h1>Database Consistency</h1>\n";

if ($blHasErrors) {
    echo "  <p class=\"consistency-status consistency-status-error\">Database inconsistencies were found.</p>\n";
} elseif ($blHasWarnings) {
    echo "  <p class=\"consistency-status consistency-status-warning\">No broken required links were found. Some contacts are unassigned and kept for review.</p>\n";
} else {
    echo "  <p class=\"consistency-status consistency-status-ok\">No database inconsistencies were found.</p>\n";
}

foreach ($aChecks as $aCheck) {
    $aRows = isset($aCheck["rows"]) ? $aCheck["rows"] : array();
    echo "  <h2>" . nxHtml($aCheck["title"]) . " (" . count($aRows) . ")</h2>\n";
    if (!$aRows) {
        echo "  <p><em>&mdash;</em></p>\n";
        continue;
    }
    $aColumns = array_keys($aRows[0]);
    echo "  <table class=\"consistency-table\">\n";
    echo "    <thead>\n";
    echo "      <tr>\n";
    foreach ($aColumns as $sColumn) {
        echo "        <th>" . nxHtml($sColumn) . "</th>\n";
    }
    echo "      </tr>\n";
    echo "    </thead>\n";
    echo "    <tbody>\n";
    foreach ($aRows as $aRow) {
        echo "      <tr>\n";
        foreach ($aColumns as $sColumn) {
            echo "        <td>" . nxHtmlValue($aRow[$sColumn]) . "</td>\n";
        }
        echo "      </tr>\n";
    }
    echo "    </tbody>\n";
    echo "  </table>\n";
}

echo nxRenderAdminScript($sBaseUrl);

?>
</body>
</html>
