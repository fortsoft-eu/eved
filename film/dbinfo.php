<?php

include "main.php";


if (!isAllowedIp($aAllowedIps)) {
    send403AndExit();
}

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aDatabaseInfo = array();
try {
    $oStatement = $oPdo->query("SELECT VERSION() AS server_version, DATABASE() AS database_name, @@version_comment AS version_comment, @@character_set_server AS character_set_server, @@collation_server AS collation_server, @@sql_mode AS sql_mode, @@time_zone AS time_zone, @@system_time_zone AS system_time_zone");
    $aRow = $oStatement->fetch(PDO::FETCH_ASSOC);
    foreach ($aRow as $sName => $mValue) {
        $aDatabaseInfo[] = array($sName, $mValue);
    }
    $aDatabaseInfo[] = array("pdo_server_version", $oPdo->getAttribute(PDO::ATTR_SERVER_VERSION));
    $aDatabaseInfo[] = array("pdo_client_version", $oPdo->getAttribute(PDO::ATTR_CLIENT_VERSION));
    $aDatabaseInfo[] = array("pdo_connection_status", $oPdo->getAttribute(PDO::ATTR_CONNECTION_STATUS));
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
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>Database Information</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderFilmMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="database-info-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="database-info-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Name</th>
        <th>Value</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aDatabaseInfo as $aRow) {
    echo "      <tr>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow[0], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top; white-space: pre-wrap;\">" . htmlspecialchars((string)$aRow[1], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
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
