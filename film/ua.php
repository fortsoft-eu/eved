<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "film", "film_csrf_token");


$aRows = array();
try {
    $oStatement = $oPdo->prepare("SELECT u.id, u.ip_address, u.x_geo_continent_code, u.x_geo_country_code, u.user_agent, u.browser_name, u.browser_version, u.os_name, u.os_version, u.platform_type, u.device_vendor, u.device_model, u.architecture, u.bitness, u.is_mobile, u.ua_brands, u.requested_film_scan_id, f.folder_name, u.requested_img, u.gpu_info, u.fonts, u.screen_resolution, u.screen_physical, u.color_depth, u.timezone, u.language, u.platform, u.plugins, u.mime_types, u.`timestamp` FROM fs_film_ua AS u LEFT JOIN fs_film_scans AS f ON f.id = u.requested_film_scan_id ORDER BY u.`timestamp` DESC, u.id DESC LIMIT 100");
    $oStatement->execute();
    $aRows = $oStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$iLatestId = count($aRows) > 0 ? (int)$aRows[0]["id"] : 0;
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
  <title><?php echo htmlspecialchars(getPageTitleText("Film Access Log", $aAllowedIps), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="film-ua-table" value="<?php echo htmlspecialchars(getQuickTableFilterValue("table-filter"), ENT_QUOTES, "UTF-8"); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <input type="checkbox" id="auto-refresh" class="js-auto-refresh" data-latest-id="<?php echo $iLatestId; ?>" data-refresh-interval="300000">
    <label for="auto-refresh">Auto-refresh every 5 minutes</label>
  </p>
<?php

if (!$aRows) {
    echo "  <p>No records found in <code>fs_film_ua</code>.</p>\n";
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
        $sCountryFlag = formatFilmUaCountryFlag($sCountryCode);
        $sCountry = $sCountryFlag . ($sCountryFlag != "" ? " " : "") . htmlspecialchars($sCountryCode, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $sUserAgentRaw = (string)$aRow["user_agent"];
        $sUserAgent = formatFilmUaUserAgent($sUserAgentRaw);
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
        $sGpu = formatFilmUaGpu($sGpuRaw);
        $sFilmRoll = "";
        if ($aRow["folder_name"] !== null && $aRow["folder_name"] != "") {
            $sFilmRoll = (string)$aRow["folder_name"];
        } elseif ($aRow["requested_film_scan_id"] !== null) {
            $sFilmRoll = (string)$aRow["requested_film_scan_id"];
        }
        $sScreenResolution = htmlspecialchars((string)$aRow["screen_resolution"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $sScreenPhysical = htmlspecialchars((string)$aRow["screen_physical"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $sScreenResolution = preg_replace("#^\\s*([0-9]+)\\s*[xX]\\s*([0-9]+)\\s*$#", "$1 &times; $2", $sScreenResolution);
        $sScreenPhysical = preg_replace("#^\\s*([0-9]+)\\s*[xX]\\s*([0-9]+)\\s*$#", "$1 &times; $2", $sScreenPhysical);
        $sTimestampRaw = (string)$aRow["timestamp"];
        $sTimestamp = substr($sTimestampRaw, 0, 19);
        $sTimestamp = htmlspecialchars($sTimestamp, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");

        echo "      <tr>\n",
            "        <td title=\"" . htmlspecialchars($sTimestampRaw, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">" . $sTimestamp . "</td>\n",
            "        <td>" . htmlspecialchars((string)$aRow["ip_address"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td>" . htmlspecialchars(strtoupper((string)$aRow["x_geo_continent_code"]), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td>" . ($sCountry != "" ? $sCountry : "<em>&mdash;</em>") . "</td>\n",
            "        <td class=\"js-user-agent\" data-user-agent=\"" . htmlspecialchars($sUserAgentRaw, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" data-browser-name=\"" . htmlspecialchars((string)$aRow["browser_name"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" data-browser-version=\"" . htmlspecialchars((string)$aRow["browser_version"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" data-os-name=\"" . htmlspecialchars((string)$aRow["os_name"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" data-os-version=\"" . htmlspecialchars((string)$aRow["os_version"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" data-platform-type=\"" . htmlspecialchars((string)$aRow["platform_type"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" data-device-vendor=\"" . htmlspecialchars((string)$aRow["device_vendor"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" data-device-model=\"" . htmlspecialchars((string)$aRow["device_model"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" title=\"" . htmlspecialchars($sUserAgentTitle, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">" . htmlspecialchars($sUserAgent, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td>" . ($sFilmRoll != "" ? htmlspecialchars($sFilmRoll, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($aRow["requested_img"] !== null ? htmlspecialchars((string)$aRow["requested_img"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : "<em>&mdash;</em>") . "</td>\n",
            "        <td title=\"" . htmlspecialchars($sGpuRaw, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">" . ($sGpu != "" ? htmlspecialchars($sGpu, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : "<em>&mdash;</em>") . "</td>\n",
            "        <td class=\"ua-clipped\" title=\"" . htmlspecialchars((string)$aRow["fonts"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">" . htmlspecialchars((string)$aRow["fonts"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td>" . ($sScreenResolution != "" ? $sScreenResolution : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . ($sScreenPhysical != "" ? $sScreenPhysical : "<em>&mdash;</em>") . "</td>\n",
            "        <td>" . htmlspecialchars((string)$aRow["color_depth"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td>" . htmlspecialchars((string)$aRow["timezone"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td>" . htmlspecialchars((string)$aRow["language"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td>" . htmlspecialchars((string)$aRow["platform"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td class=\"ua-clipped\" title=\"" . htmlspecialchars((string)$aRow["plugins"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">" . htmlspecialchars((string)$aRow["plugins"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "        <td class=\"ua-clipped\" title=\"" . htmlspecialchars((string)$aRow["mime_types"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">" . htmlspecialchars((string)$aRow["mime_types"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</td>\n",
            "      </tr>\n";
    }
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/bowser-2.14.1/es5.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
