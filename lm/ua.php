<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$sCsrfTokenName = "csrf_token";
$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
requireFullAccess("portal", $sCsrfTokenName, $blJsonResponse);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["admin_auto_refresh_action"])) {
    requireNamedCsrfToken($sCsrfTokenName, true);
    handleAdminAutoRefreshRequest();
}

$aRows = array();
try {
    $oStatement = $oPdo->prepare("SELECT id, ip_address, x_geo_continent_code, x_geo_country_code, user_agent, browser_name, browser_version, os_name, os_version, platform_type, device_vendor, device_model, architecture, bitness, is_mobile, ua_brands, gpu_info, fonts, screen_resolution, screen_physical, color_depth, timezone, language, platform, plugins, mime_types, `timestamp` FROM fs_eved_ua ORDER BY `timestamp` DESC, id DESC LIMIT 100");
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
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/admin-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/admin-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/admin-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/admin-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/admin-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/admin-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/admin-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/admin-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/admin-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/admin-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/admin-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/admin-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/admin-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/admin-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/admin-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/admin-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/admin-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/admin-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/admin-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/admin-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="eved-ua-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
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
  <table id="eved-ua-table" class="ua-table table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Date and Time</th>
        <th>IP Address</th>
        <th>Continent</th>
        <th>Country</th>
        <th>User Agent</th>
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
        $sScreenResolution = html((string)$aRow["screen_resolution"]);
        $sScreenPhysical = html((string)$aRow["screen_physical"]);
        $sScreenResolution = preg_replace("#^\\s*([0-9]+)\\s*[xX]\\s*([0-9]+)\\s*$#", "$1 &times; $2", $sScreenResolution);
        $sScreenPhysical = preg_replace("#^\\s*([0-9]+)\\s*[xX]\\s*([0-9]+)\\s*$#", "$1 &times; $2", $sScreenPhysical);
        $sTimestampRaw = (string)$aRow["timestamp"];
        $sTimestamp = html(substr($sTimestampRaw, 0, 19));
        echo "      <tr>\n",
            "        <td title=\"" . html($sTimestampRaw) . "\">" . $sTimestamp . "</td>\n",
            "        <td>" . html((string)$aRow["ip_address"]) . "</td>\n",
            "        <td>" . ($aRow["x_geo_continent_code"] !== null && $aRow["x_geo_continent_code"] != "" ? html(strtoupper((string)$aRow["x_geo_continent_code"])) : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($sCountry != "" ? $sCountry : "<em>&mdash;</em>") . "</td>\n",
            "        <td class=\"js-user-agent\" data-user-agent=\"" . html($sUserAgentRaw) . "\" data-browser-name=\"" . html((string)$aRow["browser_name"]) . "\" data-browser-version=\"" . html((string)$aRow["browser_version"]) . "\" data-os-name=\"" . html((string)$aRow["os_name"]) . "\" data-os-version=\"" . html((string)$aRow["os_version"]) . "\" data-platform-type=\"" . html((string)$aRow["platform_type"]) . "\" data-device-vendor=\"" . html((string)$aRow["device_vendor"]) . "\" data-device-model=\"" . html((string)$aRow["device_model"]) . "\" title=\"" . html($sUserAgentTitle) . "\">" . html($sUserAgent) . "</td>\n",
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

?>
    </tbody>
  </table>
<?php

}

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/bowser-2.14.1/es5.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
