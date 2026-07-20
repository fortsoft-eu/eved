<?php

include "main.php";


requireFullAccess($aAllowedIps, "film", "film_csrf_token");

$aIniVariables = ini_get_all();

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
  <title><?php echo htmlspecialchars(getPageTitleText("PHP Configuration Options", $aAllowedIps), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="configuration-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="configuration-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Configuration Option Name</th>
        <th>Global Value</th>
        <th>Local Value</th>
        <th>Access</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aIniVariables as $sVariableName => $aDetails) {
    $sGlobalValue = is_string($aDetails["global_value"]) ? wordwrap($aDetails["global_value"], 50, "\n", true) : (string)$aDetails["global_value"];
    $sLocalValue = is_string($aDetails["local_value"]) ? wordwrap($aDetails["local_value"], 50, "\n", true) : (string)$aDetails["local_value"];
    echo "      <tr>\n",
        "        <td style=\"vertical-align: top;\">" . htmlspecialchars($sVariableName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td style=\"vertical-align: top; white-space: pre-wrap;\">" . htmlspecialchars($sGlobalValue, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td style=\"vertical-align: top; white-space: pre-wrap;\">" . htmlspecialchars($sLocalValue, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td style=\"vertical-align: top;\">" . htmlspecialchars((string)$aDetails["access"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "      </tr>\n";
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/common.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/common.js")); ?>"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js"></script>
</body>
</html>
