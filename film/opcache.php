<?php

include "main.php";


requireFullAccess($aAllowedIps, "film", "film_csrf_token");

$aOpcache = array();
if (!function_exists("opcache_get_status")) {
    $aOpcache[] = array("Status", "OPcache", "Not available", "string");
} else {
    $aOpcacheData = array(
        "Status" => opcache_get_status(false),
        "Configuration" => opcache_get_configuration()
    );
    foreach ($aOpcacheData as $sCategory => $aValues) {
        if ($aValues === false) {
            $aOpcache[] = array($sCategory, "OPcache", "Disabled", "boolean");
            continue;
        }
        foreach ($aValues as $sName => $mValue) {
            if (is_array($mValue)) {
                foreach ($mValue as $sChildName => $mChildValue) {
                    $sValue = is_array($mChildValue) ? print_r($mChildValue, true) : (is_bool($mChildValue) ? ($mChildValue ? "true" : "false") : (string)$mChildValue);
                    $aOpcache[] = array($sCategory, $sName . "." . $sChildName, $sValue, gettype($mChildValue));
                }
            } else {
                $sValue = is_bool($mValue) ? ($mValue ? "true" : "false") : (string)$mValue;
                $aOpcache[] = array($sCategory, $sName, $sValue, gettype($mValue));
            }
        }
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo htmlspecialchars(getPageTitleText("PHP OPcache Status", $aAllowedIps), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderFilmMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="opcache-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="opcache-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Category</th>
        <th>Name</th>
        <th>Value</th>
        <th>Type</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aOpcache as $aRow) {
    echo "      <tr>\n",
        "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow[0], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow[1], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td style=\"vertical-align: top; white-space: pre-wrap;\">" . htmlspecialchars($aRow[2], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
        "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow[3], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
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
