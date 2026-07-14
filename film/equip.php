<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aEquipment = array();
try {
    $oStatement = $oPdo->prepare("SELECT equip_type, equip_name, acquired_at, retired_at, disposition_note FROM fs_photo_equip ORDER BY acquired_at ASC");
    $oStatement->execute();
    $aEquipment = $oStatement->fetchAll(PDO::FETCH_ASSOC);
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
  <meta name="viewport" content="<?php echo htmlspecialchars(getAdminViewportContent(), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>Photographic Equipment</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderFilmMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="equipment-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="equipment-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Equipment Type</th>
        <th>Equipment Name</th>
        <th>Acquired Date</th>
        <th>Retired Date</th>
        <th>Disposition Note</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aEquipment as $aRow) {
    $sAcquiredAt = substr((string)$aRow["acquired_at"], 0, 10);
    $sRetiredAt = $aRow["retired_at"] !== null ? substr((string)$aRow["retired_at"], 0, 10) : "";
    $sAcquiredAt = htmlspecialchars($sAcquiredAt, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sRetiredAt = htmlspecialchars($sRetiredAt, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    echo "      <tr>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars(ucfirst($aRow["equip_type"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow["equip_name"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . $sAcquiredAt . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . $sRetiredAt . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow["disposition_note"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "      </tr>\n";
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/common.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/common.js")); ?>"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js"></script>
</body>
</html>
