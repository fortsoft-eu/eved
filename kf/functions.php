<?php

function getMenuItems() {
    global $oPdo;

    return getMenuItemsFromDatabase($oPdo);
}

function getPageTitle($sFallbackTitle) {
    global $oPdo;
    global $aAllowedIps;

    $sTitle = $sFallbackTitle;
    if (!$oPdo) {
        return getPageTitleText($sTitle, $aAllowedIps);
    }
    $sMenuTitle = getCurrentMenuNameFromDatabase($oPdo);
    $sTitle = $sMenuTitle != "" ? $sMenuTitle : $sFallbackTitle;
    return getPageTitleText($sTitle, $aAllowedIps);
}

function renderMenu() {
    global $sBaseUrl, $sMenuEmoji;

    $aItems = getMenuItems();
    if (!$aItems) {
        return;
    }
    $sCurrentPath = getCurrentMenuPath();
    echo "    <span class=\"menu\" data-menu>\n",
        "      <button type=\"button\" class=\"menu-button\" data-menu-button aria-haspopup=\"true\" aria-expanded=\"false\" title=\"Menu\" aria-label=\"Menu\">" . $sMenuEmoji . "</button>\n",
        "      <span class=\"menu-panel\" data-menu-panel hidden>\n";
    foreach ($aItems as $aItem) {
        if ($aItem["separator"]) {
            echo "        <span class=\"menu-separator\"></span>\n";
            continue;
        }
        $sClass = "menu-link";
        $sCurrent = "";
        if ($aItem["path"] === $sCurrentPath) {
            $sClass .= " menu-link-active";
            $sCurrent = " aria-current=\"page\"";
        }
        $sTitle = trim((string)$aItem["title"]);
        $sTarget = trim((string)$aItem["target"]);
        $sTitleAttribute = $sTitle != "" ? " title=\"" . html($sTitle) . "\"" : "";
        $sTargetAttribute = $sTarget != "" ? " target=\"" . html($sTarget) . "\"" : "";
        $sRelAttribute = $sTarget == "_blank" ? " rel=\"noopener noreferrer\"" : "";
        echo "        <a class=\"" . html($sClass) . "\" href=\"" . html($sBaseUrl . encodeMenuPath($aItem["relative_path"])) . "\"" . $sTitleAttribute . $sTargetAttribute . $sRelAttribute . $sCurrent . "><span class=\"menu-icon\" aria-hidden=\"true\">" . html($aItem["icon"]) . "</span><span class=\"menu-text\">" . html($aItem["name"]) . "</span></a>\n";
    }
    echo "      </span>\n",
        "    </span>\n";
}

function setMessage($sMessage, $sType = "success") {
    $_SESSION["kf_message"] = array("message" => $sMessage, "type" => $sType);
}

function renderMessage() {
    if (!isset($_SESSION["kf_message"]) || !is_array($_SESSION["kf_message"])) {
        return;
    }
    $aMessage = $_SESSION["kf_message"];
    unset($_SESSION["kf_message"]);
    $sType = isset($aMessage["type"]) && $aMessage["type"] == "error" ? "error" : "success";
    echo "  <p class=\"message-box message-" . html($sType) . "\">" . html($aMessage["message"]) . "</p>\n";
}

function redirect($sPath) {
    sendSecurityHeaders();
    header("Location: " . $sPath, true, 303);
    exit;
}

function parseAmount($sValue) {
    $sValue = str_replace(array(" ", ",", "−"), array("", ".", "-"), trim((string)$sValue));
    return is_numeric($sValue) ? (float)$sValue : null;
}

function formatAmount($mAmount) {
    $fAmount = round((float)$mAmount, 2);
    $sAmount = number_format(abs($fAmount), 2, ".", " ");
    return $fAmount < 0 ? "−" . $sAmount : $sAmount;
}

function formatDate($sDate) {
    $iTime = strtotime((string)$sDate);
    return $iTime ? date("Y-m-d", $iTime) : "";
}

function monthLabel($sMonth) {
    $iTime = strtotime($sMonth . "-01");
    return $iTime ? date("F Y", $iTime) : $sMonth;
}

function getFinanceTypes($blIncludeGroups = false) {
    global $oPdo;

    $sWhere = $blIncludeGroups ? "" : "WHERE type_kind IN ('income', 'expense')";
    $oStatement = $oPdo->query("SELECT id, type_kind, name FROM kf_fin_types " . $sWhere . " ORDER BY FIELD(type_kind, 'income', 'expense', 'group'), name ASC, id ASC");
    return $oStatement->fetchAll();
}

function getFinanceTypeOptionsHtml($iSelectedId = 0) {
    $sHtml = "";
    foreach (getFinanceTypes(false) as $aType) {
        $sLabel = ($aType["type_kind"] == "income" ? "Income: " : "Expense: ") . $aType["name"];
        $sHtml .= "          <option value=\"" . (int)$aType["id"] . "\"" . ((int)$aType["id"] == $iSelectedId ? " selected" : "") . ">" . html($sLabel) . "</option>\n";
    }
    return $sHtml;
}

