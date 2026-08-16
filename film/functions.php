<?php

function startFilmUaPageRequest($iRequestedFilmScanId) {
    global $iVisitTimeout;

    $iRequestedFilmScanId = $iRequestedFilmScanId !== null ? (int)$iRequestedFilmScanId : null;
    $iNow = time();
    $aPageVisits = array();
    if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
        $_SESSION["film"] = array();
    }
    if (!isset($_SESSION["film"]["ua"]) || !is_array($_SESSION["film"]["ua"])) {
        $_SESSION["film"]["ua"] = array();
    }
    if (isset($_SESSION["film"]["ua"]["visits"]) && is_array($_SESSION["film"]["ua"]["visits"])) {
        $aPageVisits = $_SESSION["film"]["ua"]["visits"];
    }
    foreach ($aPageVisits as $iFilmScanId => $iPageVisitTime) {
        if (!is_int($iPageVisitTime) || $iPageVisitTime < $iNow - $iVisitTimeout) {
            unset($aPageVisits[$iFilmScanId]);
        }
    }
    if ($iRequestedFilmScanId !== null && $iRequestedFilmScanId > 0) {
        $aPageVisits[$iRequestedFilmScanId] = $iNow;
    }
    if (count($aPageVisits) > 0) {
        $_SESSION["film"]["ua"]["visits"] = $aPageVisits;
    } else {
        unset($_SESSION["film"]["ua"]["visits"]);
    }
    $_SESSION["film"]["ua"]["request"] = array(
        "requested_film_scan_id" => $iRequestedFilmScanId !== null && $iRequestedFilmScanId > 0 ? $iRequestedFilmScanId : null,
        "request_uri"            => $_SERVER["REQUEST_URI"],
        "referer"                => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "",
        "requested_img"          => null
    );
}

function markFilmUaImageRequest($oPdo, $sImgParam, $sExtension) {
    global $iVisitTimeout;

    if (!$oPdo || isAllowedIp()) {
        return;
    }
    $sRequestedImg = basename($sImgParam);
    if ($sRequestedImg == "") {
        return;
    }
    $sSubdir = substr(pathinfo($sRequestedImg, PATHINFO_FILENAME), 0, 8);
    $sFileName = $sRequestedImg . $sExtension;
    $iRequestedFilmScanId = null;
    try {
        $oPdoStatement = $oPdo->prepare("SELECT scan_id FROM fs_film_photos WHERE subdir = :subdir AND filename = :filename LIMIT 1");
        $oPdoStatement->execute(array("subdir" => $sSubdir, "filename" => $sFileName));
        $mFilmScanId = $oPdoStatement->fetchColumn();
        if ($mFilmScanId !== false) {
            $iRequestedFilmScanId = (int)$mFilmScanId;
        }
    } catch (PDOException $oException) {
        error_log((string)$oException);
        return;
    }
    $iNow = time();
    $aPageVisits = array();
    if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
        $_SESSION["film"] = array();
    }
    if (!isset($_SESSION["film"]["ua"]) || !is_array($_SESSION["film"]["ua"])) {
        $_SESSION["film"]["ua"] = array();
    }
    if (isset($_SESSION["film"]["ua"]["visits"]) && is_array($_SESSION["film"]["ua"]["visits"])) {
        $aPageVisits = $_SESSION["film"]["ua"]["visits"];
    }
    foreach ($aPageVisits as $iFilmScanId => $iPageVisitTime) {
        if (!is_int($iPageVisitTime) || $iPageVisitTime < $iNow - $iVisitTimeout) {
            unset($aPageVisits[$iFilmScanId]);
        }
    }
    if (count($aPageVisits) > 0) {
        $_SESSION["film"]["ua"]["visits"] = $aPageVisits;
    } else {
        unset($_SESSION["film"]["ua"]["visits"]);
    }
    if ($iRequestedFilmScanId !== null && isset($aPageVisits[$iRequestedFilmScanId])) {
        return;
    }
    $aData = array();
    if (isset($_SESSION["film"]["ua"]["fingerprint"]) && is_array($_SESSION["film"]["ua"]["fingerprint"])) {
        $aData = $_SESSION["film"]["ua"]["fingerprint"];
    }
    insertFilmUaRequest($oPdo, array(
        "request_uri"            => $_SERVER["REQUEST_URI"],
        "requested_film_scan_id" => $iRequestedFilmScanId,
        "requested_img"          => substr($sRequestedImg, 0, 64),
        "referer"                => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : ""
    ), $aData);
}

function insertFilmUaRequest($oPdo, $aRequest, $aData) {
    if (!$oPdo) {
        return false;
    }
    try {
        $mIsMobile = null;
        if (array_key_exists("is_mobile", $aData) && is_scalar($aData["is_mobile"]) && $aData["is_mobile"] != "") {
            $mIsMobile = $aData["is_mobile"] ? 1 : 0;
        }
        $oPdoStatement = $oPdo->prepare("INSERT INTO fs_film_ua (ip_address, x_real_ip, x_forwarded_for, x_web_id, x_geo_provider, x_geo_continent_code, x_geo_country_code, user_agent, browser_name, browser_version, os_name, os_version, platform_type, device_vendor, device_model, architecture, bitness, is_mobile, ua_brands, request_uri, requested_film_scan_id, requested_img, referer, gpu_info, fonts, screen_resolution, screen_physical, color_depth, timezone, language, platform, plugins, mime_types, `timestamp`) VALUES (:ip_address, :x_real_ip, :x_forwarded_for, :x_web_id, :x_geo_provider, :x_geo_continent_code, :x_geo_country_code, :user_agent, :browser_name, :browser_version, :os_name, :os_version, :platform_type, :device_vendor, :device_model, :architecture, :bitness, :is_mobile, :ua_brands, :request_uri, :requested_film_scan_id, :requested_img, :referer, :gpu_info, :fonts, :screen_resolution, :screen_physical, :color_depth, :timezone, :language, :platform, :plugins, :mime_types, CURRENT_TIMESTAMP(6))");
        $oPdoStatement->execute(array(
            "ip_address"             => isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "",
            "x_real_ip"              => isset($_SERVER["HTTP_X_REAL_IP"]) ? substr($_SERVER["HTTP_X_REAL_IP"], 0, 45) : null,
            "x_forwarded_for"        => isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? substr($_SERVER["HTTP_X_FORWARDED_FOR"], 0, 1024) : null,
            "x_web_id"               => isset($_SERVER["HTTP_X_WEB_ID"]) ? substr($_SERVER["HTTP_X_WEB_ID"], 0, 255) : null,
            "x_geo_provider"         => isset($_SERVER["HTTP_X_GEO_PROVIDER"]) ? substr($_SERVER["HTTP_X_GEO_PROVIDER"], 0, 100) : null,
            "x_geo_continent_code"   => isset($_SERVER["HTTP_X_GEO_CONTINENT_CODE"]) ? substr($_SERVER["HTTP_X_GEO_CONTINENT_CODE"], 0, 2) : null,
            "x_geo_country_code"     => isset($_SERVER["HTTP_X_GEO_COUNTRY_CODE"]) ? substr($_SERVER["HTTP_X_GEO_COUNTRY_CODE"], 0, 2) : null,
            "user_agent"             => isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "",
            "browser_name"           => getUaFingerprintNullableText($aData, "browser_name", 100),
            "browser_version"        => getUaFingerprintNullableText($aData, "browser_version", 100),
            "os_name"                => getUaFingerprintNullableText($aData, "os_name", 100),
            "os_version"             => getUaFingerprintNullableText($aData, "os_version", 100),
            "platform_type"          => getUaFingerprintNullableText($aData, "platform_type", 32),
            "device_vendor"          => getUaFingerprintNullableText($aData, "device_vendor", 100),
            "device_model"           => getUaFingerprintNullableText($aData, "device_model", 191),
            "architecture"           => getUaFingerprintNullableText($aData, "architecture", 32),
            "bitness"                => getUaFingerprintNullableText($aData, "bitness", 16),
            "is_mobile"              => $mIsMobile,
            "ua_brands"              => getUaFingerprintNullableText($aData, "ua_brands"),
            "request_uri"            => isset($aRequest["request_uri"]) && is_scalar($aRequest["request_uri"]) ? (string)$aRequest["request_uri"] : "",
            "requested_film_scan_id" => isset($aRequest["requested_film_scan_id"]) && is_int($aRequest["requested_film_scan_id"]) ? $aRequest["requested_film_scan_id"] : null,
            "requested_img"          => isset($aRequest["requested_img"]) && is_scalar($aRequest["requested_img"]) ? (string)$aRequest["requested_img"] : null,
            "referer"                => isset($aRequest["referer"]) && is_scalar($aRequest["referer"]) ? (string)$aRequest["referer"] : "",
            "gpu_info"               => getUaFingerprintText($aData, "gpu"),
            "fonts"                  => getUaFingerprintText($aData, "fonts"),
            "screen_resolution"      => getUaFingerprintText($aData, "screen"),
            "screen_physical"        => getUaFingerprintText($aData, "screen_physical"),
            "color_depth"            => getUaFingerprintText($aData, "depth"),
            "timezone"               => getUaFingerprintText($aData, "tz"),
            "language"               => getUaFingerprintText($aData, "lang"),
            "platform"               => getUaFingerprintText($aData, "platform"),
            "plugins"                => getUaFingerprintText($aData, "plugins"),
            "mime_types"             => getUaFingerprintText($aData, "mimes")
        ));
    } catch (PDOException $oException) {
        error_log((string)$oException);
        return false;
    }
    return true;
}

function sendFilmUaFingerprintResponse($oPdo) {
    if (!isset($_SESSION["film"]) || !is_array($_SESSION["film"])) {
        $_SESSION["film"] = array();
    }
    if (isAllowedIp()) {
        unset($_SESSION["film"]["ua"]);
        sendUaJsonAndExit(array("status" => "ignored"));
    }
    if (!$oPdo) {
        sendUaJsonAndExit(array("status" => "error"), 500);
    }
    if (!isset($_SESSION["film"]["ua"]["request"]) || !is_array($_SESSION["film"]["ua"]["request"])) {
        unset($_SESSION["film"]["ua"]["request"]);
        sendUaJsonAndExit(array("status" => "ignored"));
    }
    $sInput = file_get_contents("php://input");
    $aData = json_decode($sInput, true);
    if (!is_array($aData)) {
        $aData = array();
    }
    $_SESSION["film"]["ua"]["fingerprint"] = $aData;
    if (!insertFilmUaRequest($oPdo, $_SESSION["film"]["ua"]["request"], $aData)) {
        sendUaJsonAndExit(array("status" => "error"), 500);
    }
    unset($_SESSION["film"]["ua"]["request"]);
    sendUaJsonAndExit(array("status" => "ok"));
}

function printPhpFileLinks($sBaseUrl) {
    global $oPdo;

    $aItems = getMenuItems($oPdo);
    if (!$aItems) {
        return;
    }
    foreach ($aItems as $iItemIndex => $aItem) {
        if ($iItemIndex < 2) {
            continue;
        }
        if ($aItem["separator"]) {
            echo "          <hr>\n";
            continue;
        }
        $sTitle = trim((string)$aItem["title"]);
        $sTarget = trim((string)$aItem["target"]);
        $sTitleAttribute = $sTitle != "" ? " title=\"" . html($sTitle) . "\"" : "";
        $sTargetAttribute = $sTarget != "" && preg_match("#^(_blank|_self|_parent|_top|[A-Za-z][A-Za-z0-9_\\-]*)$#", $sTarget) ? " target=\"" . html($sTarget) . "\"" : "";
        $sRelAttribute = $sTarget == "_blank" ? " rel=\"noopener noreferrer\"" : "";
        echo "          <p><a href=\"" . html($sBaseUrl . encodeMenuPath($aItem["relative_path"])) . "\"" . $sTitleAttribute . $sTargetAttribute . $sRelAttribute . " data-admin-link=\"1\">" . html($aItem["name"]) . "</a></p>\n";
    }
}


function loadExposureDates(PDO $oPdo, $iId) {
    $sSql = "SELECT NULLIF(exposure_date,'0000-00-00') AS exposure_date FROM fs_film_exposure_dates WHERE film_scan_id = :id ORDER BY exposure_date";
    $oPdoStatement = $oPdo->prepare($sSql);
    $oPdoStatement->execute(array(":id" => $iId));
    $aDates = array();
    while ($sDate = $oPdoStatement->fetchColumn()) {
        if ($sDate) {
            $aDates[] = $sDate;
        }
    }
    return $aDates;
}

function formatExpirationDate($sDate) {
    if (!$sDate) {
        return "Unknown";
    }
    $oDateTime = DateTime::createFromFormat("Y-m-d", $sDate);
    if (!$oDateTime) {
        return "Unknown";
    }
    $iYear = (int)$oDateTime->format("Y");
    $iMonth = (int)$oDateTime->format("m");
    $iDay = (int)$oDateTime->format("d");
    if ($iMonth == 12 && $iDay == 31) {
        return (string)$iYear;
    }
    $iLastDay = cal_days_in_month(CAL_GREGORIAN, $iMonth, $iYear);
    if ($iDay == $iLastDay) {
        return sprintf("%02d/%d", $iMonth, $iYear);
    }
    return $oDateTime->format("Y-m-d");
}

function formatPushPull($iValue) {
    if (!$iValue) {
        return "None";
    }
    $iSteps = abs($iValue);
    if ($iValue > 0) {
        return "Push +" . $iSteps;
    }
    return "Pull −" . $iSteps;
}

function renderFilmScanHtml($oPdo, $aRow) {
    $aDates = loadExposureDates($oPdo, $aRow["id"]);
    $sDates = count($aDates) > 0 ? implode(", ", $aDates) : "Unknown";
    $sLabRoll = "";
    $aParts = preg_split("/\s+/", trim($aRow["folder_name"]));
    if (isset($aParts[1])) {
        $sLabRoll = $aParts[1] > 0 ? substr($aParts[1], -4) : "Unknown";
    }
    $iValue = $aRow["exposure_index"];
    if ($iValue <= 0) {
        $iValue = "Unknown";
    }
    $sValue = $aRow["corrections"] ?? "None";
    if (strtolower($sValue) == "none") {
        $sValue .= " (does not apply to preview display)";
    }
    $aFields = array(
        "Archive number"      => $aRow["archive_no"] > 0 ? $aRow["archive_no"] : "Unknown",
        "Lab roll number"     => $sLabRoll,
        "Film stock"          => $aRow["film_stock"],
        "Expiration date"     => formatExpirationDate($aRow["expiration_date"]),
        "Exposure index"      => $iValue,
        "Exposure correction" => $aRow["exposure_correction"] ?? "None",
        "Camera"              => $aRow["camera"],
        "Lens"                => $aRow["lens"],
        "Filter"              => $aRow["filter"],
        "Development process" => $aRow["development_process"],
        "Push/Pull"           => formatPushPull($aRow["push_pull"]),
        "Lab"                 => $aRow["lab"],
        "Exposure date"       => $sDates,
        "Scan date"           => substr($aRow["scanned_at"], 0, 16),
        "Scan format"         => $aRow["scan_format"],
        "Scan resolution"     => sprintf("%d × %d", (int)$aRow["scan_width"], (int)$aRow["scan_height"]),
        "Archive format"      => $aRow["archive_format"],
        "Corrections"         => $sValue
    );
    $sHtml = "          <table class=\"film-metadata\">\n";
    foreach ($aFields as $sLabel => $sValue) {
        $sHtml .= "            <tr><th>" . html($sLabel) . "</th><td>" . html($sValue) . "</td></tr>\n";
    }
    $sHtml .= "          </table>\n";
    return $sHtml;
}

function sendFilmMetadataTxt($oPdo, $aRow) {
    $aDates = loadExposureDates($oPdo, $aRow["id"]);
    $sDates = count($aDates) > 0 ? implode(", ", $aDates) : "Unknown";
    $sLabRoll = "";
    $sCode = "";
    $aParts = preg_split("/\s+/", trim($aRow["folder_name"]));
    if (isset($aParts[1])) {
        $sLabRoll = $aParts[1] > 0 ? substr($aParts[1], -4) : "Unknown";
        $sCode = $aParts[1];
    }
    $iValue = $aRow["exposure_index"];
    if ($iValue <= 0) {
        $iValue = "Unknown";
    }
    $aLines = array(
        sprintf("Archive number:      %s", $aRow["archive_no"] > 0 ? (string)$aRow["archive_no"] : "Unknown"),
        sprintf("Lab roll number:     %s", $sLabRoll),
        sprintf("Film stock:          %s", $aRow["film_stock"] ?? ""),
        sprintf("Expiration date:     %s", formatExpirationDate($aRow["expiration_date"] ?? null)),
        sprintf("Exposure index:      %s", $iValue),
        sprintf("Exposure correction: %s", $aRow["exposure_correction"] ?? "None"),
        sprintf("Camera:              %s", $aRow["camera"] ?? ""),
        sprintf("Lens:                %s", $aRow["lens"] ?? ""),
        sprintf("Filter:              %s", $aRow["filter"] ?? ""),
        sprintf("Development process: %s", $aRow["development_process"] ?? ""),
        sprintf("Push/Pull:           %s", formatPushPull($aRow["push_pull"])),
        sprintf("Lab:                 %s", $aRow["lab"] ?? ""),
        sprintf("Exposure date:       %s", $sDates),
        sprintf("Scan date:           %s", substr($aRow["scanned_at"] ?? "", 0, 16)),
        sprintf("Scan format:         %s", $aRow["scan_format"] ?? ""),
        sprintf("Scan resolution:     %d × %d", (int)$aRow["scan_width"], (int)$aRow["scan_height"]),
        sprintf("Archive format:      %s", $aRow["archive_format"] ?? ""),
        sprintf("Corrections:         %s", $aRow["corrections"] ?? "None")
    );
    $sContent = "";
    foreach ($aLines as $aLine) {
        $sContent .= trim($aLine) . "\r\n";
    }
    if (!$sCode) {
        $sCode = "film_" . $aRow["archive_no"];
    }
    $sFileName = $sCode . "_RAW.txt";
    $sBody = $sContent;
    $sDate = gmdate("D, d M Y H:i:s", time());
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Content-Disposition: attachment; filename=\"" . rawurlencode($sFileName) . "\"", true);
    header("Content-Transfer-Encoding: binary", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();
    echo $sBody;
    exit;
}

function renderCell($mValue, $blError) {
    $sValue = html($mValue);
    if ($blError) {
        echo "      <td class=\"error-cell\">" . $sValue . "</td>\n";
    } else {
        echo "      <td>" . $sValue . "</td>\n";
    }
}

function formatFilmOptionLabel($aFilm) {
    return html($aFilm["archive_no"] . " – " . $aFilm["folder_name"]);
}

function formatOrderOptionLabel($aOrder) {
    $sLabel = "";
    if ($aOrder["bag_no"] !== null && $aOrder["bag_no"] != "") {
        $sLabel .= $aOrder["bag_no"];
    }
    if ($aOrder["order_no"] !== null && $aOrder["order_no"] != "") {
        $sLabel .= " (" . $aOrder["order_no"] . ")";
    }
    return html($sLabel);
}

function formatEquipmentOptionLabel($aEquipment) {
    return html(ucfirst($aEquipment["equip_type"]) . ": " . $aEquipment["equip_name"]);
}

function filmEquipmentTypeOrder($sType) {
    $aTypeOrder = array("camera" => 10, "lens" => 20, "filter" => 30, "hood" => 40, "case" => 50, "bag" => 60, "tripod" => 70, "level" => 80);
    return isset($aTypeOrder[$sType]) ? $aTypeOrder[$sType] : 999;
}

function filmEquipmentDateSortValue($sValue) {
    return $sValue !== null && $sValue != "" ? substr($sValue, 0, 10) : "9999-12-31";
}

function filmEquipmentMemberCompare($aFirst, $aSecond) {
    $iResult = filmEquipmentTypeOrder($aFirst["equip_type"]) - filmEquipmentTypeOrder($aSecond["equip_type"]);
    if ($iResult != 0) {
        return $iResult;
    }
    $iResult = strcmp(filmEquipmentDateSortValue($aFirst["acquired_at"]), filmEquipmentDateSortValue($aSecond["acquired_at"]));
    if ($iResult != 0) {
        return $iResult;
    }
    $iResult = strcasecmp($aFirst["equip_name"], $aSecond["equip_name"]);
    if ($iResult != 0) {
        return $iResult;
    }
    return (int)$aFirst["id"] - (int)$aSecond["id"];
}

function filmEquipmentRowCompare($aFirst, $aSecond) {
    $iResult = (int)$aFirst["equipment_group_order"] - (int)$aSecond["equipment_group_order"];
    if ($iResult != 0) {
        return $iResult;
    }
    if ((int)$aFirst["equipment_group_id"] == (int)$aSecond["equipment_group_id"] && (int)$aFirst["equipment_group_size"] > 1) {
        return filmEquipmentMemberCompare($aFirst, $aSecond);
    }
    return (int)$aFirst["id"] - (int)$aSecond["id"];
}

function filmEquipmentGroupFind(&$aGroupParents, $iEquipmentId) {
    if (!isset($aGroupParents[$iEquipmentId])) {
        $aGroupParents[$iEquipmentId] = $iEquipmentId;
    }
    if ($aGroupParents[$iEquipmentId] != $iEquipmentId) {
        $aGroupParents[$iEquipmentId] = filmEquipmentGroupFind($aGroupParents, $aGroupParents[$iEquipmentId]);
    }
    return $aGroupParents[$iEquipmentId];
}

function filmEquipmentGroupUnion(&$aGroupParents, $iFirstEquipmentId, $iSecondEquipmentId) {
    $iFirstGroupId = filmEquipmentGroupFind($aGroupParents, $iFirstEquipmentId);
    $iSecondGroupId = filmEquipmentGroupFind($aGroupParents, $iSecondEquipmentId);
    if ($iFirstGroupId == $iSecondGroupId) {
        return;
    }
    if ($iFirstGroupId < $iSecondGroupId) {
        $aGroupParents[$iSecondGroupId] = $iFirstGroupId;
    } else {
        $aGroupParents[$iFirstGroupId] = $iSecondGroupId;
    }
}
