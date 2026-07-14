<?php

include "main.php";


if (!isAllowedIp($aAllowedIps)) {
    send403AndExit();
}

$aConstants = get_defined_constants(true);

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
  <title>Defined PHP Constants</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderFilmMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="constants-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="constants-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Group</th>
        <th>Constant</th>
        <th>Value</th>
        <th>Type</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aConstants as $sGroup => $aGroupConstants) {
    foreach ($aGroupConstants as $sName => $mValue) {
        if ($sName == "PHP_EOL") {
            $sValue = "";
            for ($i = 0; $i < strlen($mValue); $i++) {
                if ($sValue != "") {
                    $sValue .= ".";
                }
                $sValue .= "chr(" . ord($mValue[$i]) . ")";
            }
        } elseif (is_bool($mValue)) {
            $sValue = $mValue ? "true" : "false";
        } elseif ($mValue === null) {
            $sValue = "null";
        } elseif (is_array($mValue)) {
            $sValue = print_r($mValue, true);
        } elseif (is_float($mValue) && is_nan($mValue)) {
            $sValue = "NAN";
        } elseif (is_float($mValue) && is_infinite($mValue)) {
            $sValue = $mValue > 0 ? "INF" : "-INF";
        } else {
            $sValue = (string)$mValue;
        }
        echo "      <tr>\n"
            . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($sGroup, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
            . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($sName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
            . "        <td style=\"vertical-align: top; white-space: pre-wrap;\">" . htmlspecialchars($sValue, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
            . "        <td style=\"vertical-align: top;\">" . htmlspecialchars(gettype($mValue), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
            . "      </tr>\n";
    }
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/common.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/common.js")); ?>"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js"></script>
</body>
</html>
