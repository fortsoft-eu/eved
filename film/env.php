<?php

include "main.php";


if (!isAllowedIp($aAllowedIps)) {
    send403AndExit();
}

$aEnvironment = array(
    array("PHP Environment", "PHP Version", PHP_VERSION),
    array("PHP Environment", "PHP SAPI", PHP_SAPI),
    array("PHP Environment", "Operating System", PHP_OS),
    array("PHP Environment", "Operating System Family", PHP_OS_FAMILY),
    array("PHP Environment", "Architecture", (PHP_INT_SIZE * 8) . "-bit"),
    array("PHP Environment", "Server Time Zone", date_default_timezone_get()),
    array("PHP Environment", "Locale", setlocale(LC_ALL, 0)),
    array("PHP Environment", "Local Time", date("Y-m-d H:i:s T")),
    array("PHP Environment", "UTC Time", gmdate("Y-m-d H:i:s") . " UTC"),
    array("Configuration Files", "Loaded php.ini", php_ini_loaded_file() ?: "Not loaded"),
    array("Configuration Files", "Additional INI Files", php_ini_scanned_files() ?: "None"),
    array("PDO", "Available Drivers", implode(", ", PDO::getAvailableDrivers())),
    array("Resource Limits", "memory_limit", ini_get("memory_limit")),
    array("Resource Limits", "max_execution_time", ini_get("max_execution_time")),
    array("Resource Limits", "max_input_time", ini_get("max_input_time")),
    array("Resource Limits", "post_max_size", ini_get("post_max_size")),
    array("Resource Limits", "upload_max_filesize", ini_get("upload_max_filesize")),
    array("Resource Limits", "max_file_uploads", ini_get("max_file_uploads")),
    array("Security Configuration", "disable_functions", ini_get("disable_functions")),
    array("Security Configuration", "disable_classes", ini_get("disable_classes")),
    array("Security Configuration", "open_basedir", ini_get("open_basedir")),
    array("Security Configuration", "allow_url_fopen", ini_get("allow_url_fopen")),
    array("Security Configuration", "allow_url_include", ini_get("allow_url_include")),
    array("Security Configuration", "display_errors", ini_get("display_errors")),
    array("Security Configuration", "expose_php", ini_get("expose_php")),
    array("Security Configuration", "session.cookie_httponly", ini_get("session.cookie_httponly")),
    array("Security Configuration", "session.cookie_secure", ini_get("session.cookie_secure")),
    array("Security Configuration", "session.cookie_samesite", ini_get("session.cookie_samesite"))
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>PHP Environment</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderFilmMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="environment-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="environment-table" class="table-filter-target">
    <thead>
      <tr>
        <th>Category</th>
        <th>Name</th>
        <th>Value</th>
      </tr>
    </thead>
    <tbody>
<?php

foreach ($aEnvironment as $aRow) {
    echo "      <tr>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow[0], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlspecialchars($aRow[1], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
        . "        <td style=\"vertical-align: top; white-space: pre-wrap;\">" . htmlspecialchars((string)$aRow[2], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n"
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
