<?php

include "main.php";


$sCsrfTokenName = "csrf_token";
$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess("film", $sCsrfTokenName, $blJsonResponse);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["admin_auto_refresh_action"])) {
    requireNamedCsrfToken($sCsrfTokenName, true);
    handleAdminAutoRefreshRequest();
}


$aRows = array();
try {
    $oStatement = $oPdo->prepare("SELECT u.id, u.ip_address, u.x_geo_continent_code, u.x_geo_country_code, u.user_agent, u.browser_name, u.browser_version, u.os_name, u.os_version, u.platform_type, u.device_vendor, u.device_model, u.architecture, u.bitness, u.is_mobile, u.ua_brands, u.requested_film_scan_id, f.folder_name, u.requested_img, u.gpu_info, u.fonts, u.screen_resolution, u.screen_physical, u.color_depth, u.timezone, u.language, u.platform, u.plugins, u.mime_types, u.`timestamp` FROM fs_film_ua AS u LEFT JOIN fs_film_scans AS f ON f.id = u.requested_film_scan_id ORDER BY u.`timestamp` DESC, u.id DESC LIMIT 100");
    $oStatement->execute();
    $aRows = $oStatement->fetchAll();
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$iLatestId = count($aRows) > 0 ? (int)$aRows[0]["id"] : 0;
$blAutoRefresh = getAdminAutoRefreshValue("auto-refresh");
$sCsrfToken = getCsrfToken($sCsrfTokenName);
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
  <meta name="csrf-token" content="<?php echo html($sCsrfToken); ?>">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="film-ua-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <input type="checkbox" id="auto-refresh" class="js-auto-refresh" data-latest-id="<?php echo $iLatestId; ?>" data-refresh-interval="300000"<?php echo $blAutoRefresh ? " checked" : ""; ?>>
    <label for="auto-refresh">Auto-refresh every 5 minutes</label>
  </p>
<?php

if (!$aRows) {
    echo "  <p>No records found.</p>\n";
} else {

?>
  <table id="film-ua-table" class="ua-table table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Date and Time</th>
        <th>IP Address</th>
        <th>Continent</th>
        <th>Country</th>
        <th>User Agent</th>
        <th>Film Roll (Archive Folder Name)</th>
        <th>Requested Image</th>
        <th>GPU</th>
        <th>Fonts</th>
        <th>Screen Resolution</th>
        <th>Physical Resolution</th>
        <th>Color Depth</th>
        <th>Timezone</th>
        <th>Language</th>
        <th>Platform</th>
        <th>Plugins</th>
        <th>MIME Types</th>
      </tr>
    </thead>
    <tbody>
<?php

    foreach ($aRows as $aRow) {
        $sCountryCode = strtoupper(trim((string)$aRow["x_geo_country_code"]));
        $sCountryFlag = formatUaCountryFlag($sCountryCode);
        $sCountry = $sCountryFlag . ($sCountryFlag != "" ? " " : "") . html($sCountryCode);
        $sUserAgentRaw = (string)$aRow["user_agent"];
        $sUserAgent = formatUaUserAgent($sUserAgentRaw);
        $sBrowser = trim((string)$aRow["browser_name"] . " " . (string)$aRow["browser_version"]);
        $sOperatingSystem = trim((string)$aRow["os_name"] . " " . (string)$aRow["os_version"]);
        $sDevice = trim((string)$aRow["device_vendor"] . " " . (string)$aRow["device_model"]);
        $aUserAgentParts = array();
        if ($sBrowser != "") {
            $aUserAgentParts[] = $sBrowser;
        }
        if ($sOperatingSystem != "") {
            $aUserAgentParts[] = $sOperatingSystem;
        }
        if ($aRow["platform_type"] !== null && $aRow["platform_type"] != "") {
            $aUserAgentParts[] = ucfirst((string)$aRow["platform_type"]);
        }
        if ($sDevice != "") {
            $aUserAgentParts[] = $sDevice;
        }
        if (count($aUserAgentParts) > 0) {
            $sUserAgent = implode(" / ", $aUserAgentParts);
        }
        $sUaBrandsRaw = (string)$aRow["ua_brands"];
        $sUaBrands = "";
        $aUaBrands = json_decode($sUaBrandsRaw, true);
        if (is_array($aUaBrands)) {
            $aUaBrandLabels = array();
            foreach ($aUaBrands as $aUaBrand) {
                if (is_array($aUaBrand) && isset($aUaBrand["brand"])) {
                    $sUaBrandLabel = (string)$aUaBrand["brand"];
                    if (isset($aUaBrand["version"]) && $aUaBrand["version"] != "") {
                        $sUaBrandLabel .= " " . $aUaBrand["version"];
                    }
                    $aUaBrandLabels[] = $sUaBrandLabel;
                }
            }
            $sUaBrands = implode(", ", $aUaBrandLabels);
        }
        $aUserAgentDetails = array($sUserAgentRaw);
        if ($sBrowser != "") {
            $aUserAgentDetails[] = "Browser: " . $sBrowser;
        }
        if ($sOperatingSystem != "") {
            $aUserAgentDetails[] = "Operating system: " . $sOperatingSystem;
        }
        if ($aRow["platform_type"] !== null && $aRow["platform_type"] != "") {
            $aUserAgentDetails[] = "Platform type: " . $aRow["platform_type"];
        }
        if ($sDevice != "") {
            $aUserAgentDetails[] = "Device: " . $sDevice;
        }
        if ($aRow["architecture"] !== null && $aRow["architecture"] != "") {
            $aUserAgentDetails[] = "Architecture: " . $aRow["architecture"];
        }
        if ($aRow["bitness"] !== null && $aRow["bitness"] != "") {
            $aUserAgentDetails[] = "Bitness: " . $aRow["bitness"];
        }
        if ($aRow["is_mobile"] !== null) {
            $aUserAgentDetails[] = "Mobile: " . ((int)$aRow["is_mobile"] == 1 ? "Yes" : "No");
        }
        if ($sUaBrands != "") {
            $aUserAgentDetails[] = "UA brands: " . $sUaBrands;
        }
        $sUserAgentTitle = implode("\n", $aUserAgentDetails);
        $sGpuRaw = (string)$aRow["gpu_info"];
        $sGpu = formatUaGpu($sGpuRaw);
        $sFilmRoll = "";
        if ($aRow["folder_name"] !== null && $aRow["folder_name"] != "") {
            $sFilmRoll = (string)$aRow["folder_name"];
        } elseif ($aRow["requested_film_scan_id"] !== null) {
            $sFilmRoll = (string)$aRow["requested_film_scan_id"];
        }
        $sScreenResolution = html((string)$aRow["screen_resolution"]);
        $sScreenPhysical = html((string)$aRow["screen_physical"]);
        $sScreenResolution = preg_replace("#^\\s*([0-9]+)\\s*[xX]\\s*([0-9]+)\\s*$#", "$1 &times; $2", $sScreenResolution);
        $sScreenPhysical = preg_replace("#^\\s*([0-9]+)\\s*[xX]\\s*([0-9]+)\\s*$#", "$1 &times; $2", $sScreenPhysical);
        $sTimestampRaw = (string)$aRow["timestamp"];
        $sTimestamp = substr($sTimestampRaw, 0, 19);
        $sTimestamp = html($sTimestamp);

        echo "      <tr>\n",
            "        <td title=\"" . html($sTimestampRaw) . "\">" . $sTimestamp . "</td>\n",
            "        <td>" . html((string)$aRow["ip_address"]) . "</td>\n",
            "        <td>" . ($aRow["x_geo_continent_code"] !== null && $aRow["x_geo_continent_code"] != "" ? html(strtoupper((string)$aRow["x_geo_continent_code"])) : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($sCountry != "" ? $sCountry : "<em>&mdash;</em>") . "</td>\n",
            "        <td class=\"js-user-agent\" data-user-agent=\"" . html($sUserAgentRaw) . "\" data-browser-name=\"" . html((string)$aRow["browser_name"]) . "\" data-browser-version=\"" . html((string)$aRow["browser_version"]) . "\" data-os-name=\"" . html((string)$aRow["os_name"]) . "\" data-os-version=\"" . html((string)$aRow["os_version"]) . "\" data-platform-type=\"" . html((string)$aRow["platform_type"]) . "\" data-device-vendor=\"" . html((string)$aRow["device_vendor"]) . "\" data-device-model=\"" . html((string)$aRow["device_model"]) . "\" title=\"" . html($sUserAgentTitle) . "\">" . html($sUserAgent) . "</td>\n",
            "        <td>" . ($sFilmRoll != "" ? html($sFilmRoll) : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($aRow["requested_img"] !== null && (string)$aRow["requested_img"] != "" ? html((string)$aRow["requested_img"]) : "<em>&mdash;</em>") . "</td>\n",
            "        <td title=\"" . html($sGpuRaw) . "\">" . ($sGpu != "" ? html($sGpu) : "<em>&mdash;</em>") . "</td>\n",
            "        <td class=\"ua-clipped\" title=\"" . html((string)$aRow["fonts"]) . "\">" . ($aRow["fonts"] !== null && $aRow["fonts"] != "" ? html((string)$aRow["fonts"]) : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($sScreenResolution != "" ? $sScreenResolution : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($sScreenPhysical != "" ? $sScreenPhysical : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($aRow["color_depth"] !== null && $aRow["color_depth"] != "" ? html((string)$aRow["color_depth"]) : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($aRow["timezone"] !== null && $aRow["timezone"] != "" ? html((string)$aRow["timezone"]) : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($aRow["language"] !== null && $aRow["language"] != "" ? html((string)$aRow["language"]) : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($aRow["platform"] !== null && $aRow["platform"] != "" ? html((string)$aRow["platform"]) : "<em>&mdash;</em>") . "</td>\n",
            "        <td class=\"ua-clipped\" title=\"" . html((string)$aRow["plugins"]) . "\">" . ($aRow["plugins"] !== null && $aRow["plugins"] != "" ? html((string)$aRow["plugins"]) : "<em>&mdash;</em>") . "</td>\n",
            "        <td class=\"ua-clipped\" title=\"" . html((string)$aRow["mime_types"]) . "\">" . ($aRow["mime_types"] !== null && $aRow["mime_types"] != "" ? html((string)$aRow["mime_types"]) : "<em>&mdash;</em>") . "</td>\n",
            "      </tr>\n";
    }
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/bowser-2.14.1/es5.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
