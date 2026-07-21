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

function redirect($sPath) {
    sendSecurityHeaders();
    header("Location: " . $sPath, true, 303);
    exit;
}

function getSettingsDefaults() {
    return array(
        "use_european_amount_format" => 0
    );
}

function getSettings() {
    $aSettingsDefaults = getSettingsDefaults();
    $aSettings = array();
    if (!isset($_SESSION["kf_settings"]) || !is_array($_SESSION["kf_settings"])) {
        $_SESSION["kf_settings"] = array();
        if (isset($_SESSION["kf_debts_settings"]) && is_array($_SESSION["kf_debts_settings"])) {
            $_SESSION["kf_settings"] = $_SESSION["kf_debts_settings"];
        }
    }
    foreach ($aSettingsDefaults as $sSettingName => $iSettingDefault) {
        if (isset($_SESSION["kf_settings"][$sSettingName])) {
            $aSettings[$sSettingName] = (int)$_SESSION["kf_settings"][$sSettingName] == 1 ? 1 : 0;
        } else {
            $aSettings[$sSettingName] = $iSettingDefault;
        }
    }
    $_SESSION["kf_settings"] = $aSettings;
    unset($_SESSION["kf_debts_settings"]);
    return $aSettings;
}

function saveSettings($aPayload) {
    $aSettings = array();
    foreach (getSettingsDefaults() as $sSettingName => $iSettingDefault) {
        $aSettings[$sSettingName] = isset($aPayload[$sSettingName]) && (string)$aPayload[$sSettingName] == "1" ? 1 : 0;
    }
    $_SESSION["kf_settings"] = $aSettings;
    unset($_SESSION["kf_debts_settings"]);
    return $aSettings;
}

function useEuropeanAmountFormat() {
    $aSettings = getSettings();
    return (int)$aSettings["use_european_amount_format"] == 1;
}

function getCurrentPagePath() {
    $sPath = isset($_SERVER["REQUEST_URI"]) ? (string)parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) : "";
    return $sPath != "" ? $sPath : getCurrentScriptName();
}

function handleSettingsPost() {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || getPostedTrimmedValue("action") != "save_settings") {
        return;
    }
    requireNamedCsrfToken("kf_csrf_token");
    saveSettings($_POST);
    session_write_close();
    redirect(getCurrentPagePath());
}

function renderSettingsButton() {
    return "    <button type=\"button\" class=\"button-link js-index-settings-open\">Settings</button>\n";
}

function renderSettingsModal($aSettings = null) {
    if (!is_array($aSettings)) {
        $aSettings = getSettings();
    }
    return "  <div class=\"confirm-dialog index-settings-dialog\" id=\"index-settings-dialog\" hidden>\n"
        . "    <form class=\"confirm-dialog-box index-settings-form\" method=\"post\" action=\"" . html(getCurrentPagePath()) . "\" enctype=\"application/x-www-form-urlencoded\">\n"
        . "      <input type=\"hidden\" name=\"action\" value=\"save_settings\">\n"
        . "      <input type=\"hidden\" name=\"kf_csrf_token\" value=\"" . html(getCsrfToken("kf_csrf_token")) . "\">\n"
        . "      <div class=\"confirm-dialog-header\">\n"
        . "        <strong>Settings</strong>\n"
        . "        <button type=\"button\" class=\"confirm-dialog-close js-index-settings-close\" aria-label=\"Close\">&times;</button>\n"
        . "      </div>\n"
        . "      <div class=\"index-settings-options\">\n"
        . "        <label><input type=\"checkbox\" name=\"use_european_amount_format\" value=\"1\"" . ($aSettings["use_european_amount_format"] ? " checked" : "") . "> Use European number format for amounts</label>\n"
        . "      </div>\n"
        . "      <div class=\"confirm-dialog-actions\">\n"
        . "        <button type=\"submit\" class=\"confirm-dialog-button\">Save</button>\n"
        . "        <button type=\"button\" class=\"confirm-dialog-button js-index-settings-cancel\">Cancel</button>\n"
        . "      </div>\n"
        . "    </form>\n"
        . "  </div>\n";
}

function parseAmount($sValue) {
    $sValue = str_replace(array(" ", "\xc2\xa0", "−"), array("", "", "-"), trim((string)$sValue));
    $iCommaPosition = strrpos($sValue, ",");
    $iDotPosition = strrpos($sValue, ".");
    if ($iCommaPosition !== false && $iDotPosition !== false) {
        if ($iCommaPosition > $iDotPosition) {
            $sValue = str_replace(".", "", $sValue);
            $sValue = str_replace(",", ".", $sValue);
        } else {
            $sValue = str_replace(",", "", $sValue);
        }
    } elseif ($iCommaPosition !== false) {
        $sValue = str_replace(",", ".", $sValue);
    }
    return is_numeric($sValue) ? (float)$sValue : null;
}

function formatAmount($mAmount, $blUseEuropeanAmountFormat = null) {
    if ($blUseEuropeanAmountFormat === null) {
        $blUseEuropeanAmountFormat = useEuropeanAmountFormat();
    }
    $fAmount = round((float)$mAmount, 2);
    $sDecimalSeparator = $blUseEuropeanAmountFormat ? "," : ".";
    $sThousandsSeparator = $blUseEuropeanAmountFormat ? " " : ",";
    $sAmount = number_format(abs($fAmount), 2, $sDecimalSeparator, $sThousandsSeparator);
    return $fAmount < 0 ? "−" . $sAmount : $sAmount;
}

function formatDebtAmount($mAmount, $blUseEuropeanAmountFormat = null) {
    return formatAmount($mAmount, $blUseEuropeanAmountFormat);
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

function renderEmojiData() {
    global $sCopyEmoji, $sCopySuccessEmoji, $sCopyFailureEmoji;

    $aValues = array(
        "copy" => $sCopyEmoji,
        "copy-success" => $sCopySuccessEmoji,
        "copy-failure" => $sCopyFailureEmoji
    );
    $sHtml = "  <span id=\"emoji-data\" hidden";
    foreach ($aValues as $sKey => $sValue) {
        $sHtml .= " data-" . $sKey . "=\"" . html(html_entity_decode((string)$sValue, ENT_QUOTES | ENT_HTML5, "UTF-8")) . "\"";
    }
    return $sHtml . "></span>\n";
}

function renderCopyAction($mValue, $sTitle = "Copy") {
    global $sCopyEmoji;

    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return "";
    }
    return "<a class=\"copy-action\" href=\"#\" data-copy-value=\"" . html($sValue) . "\" title=\"" . html($sTitle) . "\" aria-label=\"" . html($sTitle) . "\"><span class=\"copy-action-box\">" . $sCopyEmoji . "</span></a>";
}

function getSubjectNameSelectSql() {
    $sPersonDisplayBase = "NULLIF(TRIM(CONCAT_WS(' ', NULLIF(p.title_before, ''), NULLIF(p.first_name, ''), NULLIF(p.middle_name, ''), NULLIF(p.last_name, ''))), '')";
    $sPersonDisplayName = "NULLIF(TRIM(CONCAT(COALESCE(" . $sPersonDisplayBase . ", ''), IF(NULLIF(p.title_after, '') IS NULL, '', IF(" . $sPersonDisplayBase . " IS NULL, p.title_after, CONCAT(', ', p.title_after))))), '')";
    $sPersonSortName = "NULLIF(TRIM(CONCAT_WS(' ', NULLIF(p.last_name, ''), NULLIF(p.first_name, ''))), '')";
    return "SELECT s.id AS subject_id, COALESCE(IF(s.subject_type = 'person', " . $sPersonDisplayName . ", NULL), NULLIF(subn.name, ''), n.primary_nickname, c.primary_contact, 'Unnamed subject') AS subject_name, COALESCE(IF(s.subject_type = 'person', " . $sPersonSortName . ", NULL), NULLIF(subn.name, ''), n.primary_nickname, c.primary_contact, 'Unnamed subject') AS subject_sort_name FROM ex_subjects AS s LEFT JOIN ex_persons AS p ON p.subject_id = s.id LEFT JOIN ex_subject_names AS subn ON subn.subject_id = s.id LEFT JOIN (SELECT sc.subject_id, SUBSTRING_INDEX(GROUP_CONCAT(c.contact_value ORDER BY sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC SEPARATOR '\n'), '\n', 1) AS primary_contact FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id LEFT JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id GROUP BY sc.subject_id) AS c ON c.subject_id = s.id LEFT JOIN (SELECT subject_id, SUBSTRING_INDEX(GROUP_CONCAT(nickname ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n'), '\n', 1) AS primary_nickname FROM ex_subject_nicknames GROUP BY subject_id) AS n ON n.subject_id = s.id";
}

function fetchSubjectNameRow($oPdo, $iSubjectId) {
    if ((int)$iSubjectId < 1) {
        return null;
    }
    $oStatement = $oPdo->prepare("SELECT subject_id, subject_name, subject_sort_name FROM (" . getSubjectNameSelectSql() . ") AS subject_rows WHERE subject_id = :subject_id");
    $oStatement->execute(array("subject_id" => (int)$iSubjectId));
    $aRow = $oStatement->fetch(PDO::FETCH_ASSOC);
    return $aRow ? $aRow : null;
}

function fetchSubjectSuggestions($oPdo, $sTerm, $iLimit = 12) {
    $sTerm = trim((string)$sTerm);
    if (strlen($sTerm) < 3) {
        return array();
    }
    $iLimit = (int)$iLimit;
    if ($iLimit < 1) {
        $iLimit = 12;
    }
    if ($iLimit > 30) {
        $iLimit = 30;
    }
    $sLike = "%" . strtr($sTerm, array("!" => "!!", "%" => "!%", "_" => "!_")) . "%";
    $oStatement = $oPdo->prepare("SELECT subject_id, subject_name FROM (" . getSubjectNameSelectSql() . ") AS subject_rows WHERE LOWER(subject_name) LIKE LOWER(:subject_name_term) ESCAPE '!' OR LOWER(subject_sort_name) LIKE LOWER(:subject_sort_name_term) ESCAPE '!' ORDER BY subject_sort_name ASC, subject_id ASC LIMIT " . $iLimit);
    $oStatement->execute(array(
        "subject_name_term" => $sLike,
        "subject_sort_name_term" => $sLike
    ));
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function getDebtContactSelectSql() {
    $sContactOrder = "sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC";
    return "SELECT sc.subject_id, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type = 'bankaccount' THEN c.contact_value END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS account_number, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type = 'bankaccount' THEN COALESCE(sc.note, '') END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS account_note, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type = 'bankaccount' THEN sc.is_primary END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS account_primary, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type = 'email' THEN c.contact_value END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS email, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type = 'email' THEN COALESCE(sc.note, '') END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS email_note, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type = 'email' THEN sc.is_primary END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS email_primary, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type IN ('landline', 'cell', 'fax', 'pager') THEN ct.contact_type END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS phone_type, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type IN ('landline', 'cell', 'fax', 'pager') THEN c.contact_value END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS phone, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type IN ('landline', 'cell', 'fax', 'pager') THEN COALESCE(sc.note, '') END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS phone_note, SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN ct.contact_type IN ('landline', 'cell', 'fax', 'pager') THEN sc.is_primary END ORDER BY " . $sContactOrder . " SEPARATOR '\n'), '\n', 1) AS phone_primary FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id INNER JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id WHERE ct.contact_type IN ('bankaccount', 'email', 'landline', 'cell', 'fax', 'pager') GROUP BY sc.subject_id";
}

function fetchDebtMovementRowsByDebtIds($oPdo, $aDebtIds) {
    $aPlaceholders = array();
    $aParams = array();
    $aRowsByDebtId = array();
    foreach ($aDebtIds as $iIndex => $iDebtId) {
        $iDebtId = (int)$iDebtId;
        if ($iDebtId < 1) {
            continue;
        }
        $sParamName = "debt_id_" . $iIndex;
        $aPlaceholders[] = ":" . $sParamName;
        $aParams[$sParamName] = $iDebtId;
    }
    if (!$aPlaceholders) {
        return array();
    }
    $oStatement = $oPdo->prepare("SELECT id, debt_id, movement_date, amount, note FROM kf_debt_movements WHERE debt_id IN (" . implode(", ", $aPlaceholders) . ") ORDER BY movement_date ASC, id ASC");
    $oStatement->execute($aParams);
    foreach ($oStatement->fetchAll(PDO::FETCH_ASSOC) as $aRow) {
        $iDebtId = (int)$aRow["debt_id"];
        if (!isset($aRowsByDebtId[$iDebtId])) {
            $aRowsByDebtId[$iDebtId] = array();
        }
        $aRowsByDebtId[$iDebtId][] = $aRow;
    }
    return $aRowsByDebtId;
}

function fetchDebtAdminRows($oPdo, $iDebtId = 0) {
    $sSql = "SELECT d.id, d.ex_subjects_id, s.subject_name, d.note, dc.account_number, dc.account_note, dc.account_primary, dc.email, dc.email_note, dc.email_primary, dc.phone_type, dc.phone, dc.phone_note, dc.phone_primary FROM kf_debts AS d LEFT JOIN (" . getSubjectNameSelectSql() . ") AS s ON s.subject_id = d.ex_subjects_id LEFT JOIN (" . getDebtContactSelectSql() . ") AS dc ON dc.subject_id = d.ex_subjects_id";
    if ((int)$iDebtId > 0) {
        $sSql .= " WHERE d.id = :id";
    }
    $sSql .= " ORDER BY COALESCE(s.subject_sort_name, '') ASC, s.subject_name ASC, d.id ASC";
    if ((int)$iDebtId > 0) {
        $oStatement = $oPdo->prepare($sSql);
        $oStatement->execute(array("id" => (int)$iDebtId));
    } else {
        $oStatement = $oPdo->query($sSql);
    }
    $aRows = $oStatement->fetchAll(PDO::FETCH_ASSOC);
    $aDebtIds = array();
    foreach ($aRows as $aRow) {
        $aDebtIds[] = (int)$aRow["id"];
    }
    $aMovementRowsByDebtId = fetchDebtMovementRowsByDebtIds($oPdo, $aDebtIds);
    foreach ($aRows as $iIndex => $aRow) {
        $iCurrentDebtId = (int)$aRow["id"];
        $aMovements = isset($aMovementRowsByDebtId[$iCurrentDebtId]) ? $aMovementRowsByDebtId[$iCurrentDebtId] : array();
        $fAmount = 0.0;
        foreach ($aMovements as $aMovement) {
            $fAmount += (float)$aMovement["amount"];
        }
        $aRows[$iIndex]["amount"] = $fAmount;
        $aRows[$iIndex]["debt_movements"] = $aMovements;
    }
    return $aRows;
}

function renderDebtMovementValues($aMovements, $blShowActions = true, $blUseEuropeanAmountFormat = false) {
    global $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji;

    if (!$aMovements) {
        return $sEmptyValueEmoji;
    }
    $sHtml = "<span class=\"debt-movements\">";
    foreach ($aMovements as $aMovement) {
        $fAmount = (float)$aMovement["amount"];
        $sAmountClass = $fAmount < 0 ? "amount-negative" : ($fAmount > 0 ? "amount-positive" : "amount-zero");
        $sFormattedAmount = formatDebtAmount($aMovement["amount"], $blUseEuropeanAmountFormat);
        $sNote = trim((string)$aMovement["note"]);
        $sActions = $blShowActions ? "<span class=\"list-item-actions\"><a href=\"#\" class=\"item-action js-edit-debt-movement\" title=\"Edit movement\" aria-label=\"Edit movement\">" . $sEditEmoji . "</a><a href=\"#\" class=\"item-action js-delete-debt-movement\" title=\"Delete movement\" aria-label=\"Delete movement\">" . $sDeleteEmoji . "</a></span>" : "";
        $sHtml .= "<span class=\"debt-movement\" data-debt-movement-id=\"" . (int)$aMovement["id"] . "\" data-movement-date=\"" . html(formatDate($aMovement["movement_date"])) . "\" data-amount=\"" . html($sFormattedAmount) . "\" data-note=\"" . html($sNote) . "\">"
            . "<span class=\"debt-movement-amount " . $sAmountClass . "\">" . html($sFormattedAmount) . "</span>"
            . renderCopyAction($sFormattedAmount)
            . "<span class=\"debt-movement-date\">" . html(formatDate($aMovement["movement_date"])) . "</span>"
            . ($sNote != "" ? "<span class=\"contact-note\"> (" . html($sNote) . ")</span>" : "")
            . $sActions
            . "</span>";
    }
    return $sHtml . "</span>";
}

function renderDebtAdminRow($aRow, $blShowActions = true, $blUseEuropeanAmountFormat = false) {
    global $sAddEmoji, $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji;

    $sFormattedAmount = formatDebtAmount($aRow["amount"], $blUseEuropeanAmountFormat);
    $sAmountClass = (float)$aRow["amount"] < 0 ? "amount-negative" : ((float)$aRow["amount"] > 0 ? "amount-positive" : "amount-zero");
    $sSubjectId = (int)$aRow["ex_subjects_id"] > 0 && (string)$aRow["subject_name"] != "" ? (string)(int)$aRow["ex_subjects_id"] : "";
    $sActionCell = $blShowActions ? "<a href=\"#\" class=\"item-action js-add-debt-movement\" title=\"New movement\" aria-label=\"New movement\">" . $sAddEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-edit-debt\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-debt\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "";
    return "      <tr data-debt-id=\"" . (int)$aRow["id"] . "\" data-ex-subjects-id=\"" . html($sSubjectId) . "\" data-subject-name=\"" . html($aRow["subject_name"]) . "\" data-amount=\"" . html($sFormattedAmount) . "\" data-note=\"" . html($aRow["note"]) . "\">\n"
        . "        <td><span class=\"subject-item-value\">" . htmlValue($aRow["subject_name"], $sEmptyValueEmoji) . "</span>" . renderCopyAction($aRow["subject_name"]) . "</td>\n"
        . "        <td class=\"numeric " . $sAmountClass . "\">" . html($sFormattedAmount) . renderCopyAction($sFormattedAmount) . "</td>\n"
        . "        <td class=\"debt-movements-cell\">" . renderDebtMovementValues($aRow["debt_movements"], $blShowActions, $blUseEuropeanAmountFormat) . "</td>\n"
        . "        <td>" . renderDebtContactValue("bankaccount", $aRow["account_number"], $aRow["account_note"], (int)$aRow["account_primary"] == 1) . "</td>\n"
        . "        <td>" . renderDebtContactValue("email", $aRow["email"], $aRow["email_note"], (int)$aRow["email_primary"] == 1) . "</td>\n"
        . "        <td>" . renderDebtContactValue($aRow["phone_type"], $aRow["phone"], $aRow["phone_note"], (int)$aRow["phone_primary"] == 1) . "</td>\n"
        . "        <td>" . renderDebtNoteValue($aRow["note"]) . "</td>\n"
        . ($blShowActions ? "        <td class=\"admin-action-column\">" . $sActionCell . "</td>\n" : "")
        . "      </tr>\n";
}

function renderDebtAdminRows($aRows, $blShowActions = true, $blUseEuropeanAmountFormat = false) {
    $sHtml = "";
    $fTotal = 0;
    foreach ($aRows as $aRow) {
        $fTotal += (float)$aRow["amount"];
        $sHtml .= renderDebtAdminRow($aRow, $blShowActions, $blUseEuropeanAmountFormat);
    }
    if ($aRows) {
        $sFormattedTotal = formatDebtAmount($fTotal, $blUseEuropeanAmountFormat);
        $sHtml .= "      <tr><td class=\"debt-total\">Total</td><td class=\"numeric debt-total\">" . html($sFormattedTotal) . renderCopyAction($sFormattedTotal) . "</td><td colspan=\"" . ($blShowActions ? 6 : 5) . "\"></td></tr>\n";
    } else {
        $sHtml .= "      <tr><td colspan=\"" . ($blShowActions ? 8 : 7) . "\">No debts found.</td></tr>\n";
    }
    return $sHtml;
}

function fetchTransactionAdminRows($oPdo, $iTransactionId = 0) {
    $sSql = "SELECT t.id, t.transaction_date, t.amount, t.counterparty, t.note, ft.id AS finance_type_id, ft.name AS type_name, ft.type_kind FROM kf_fin_trans t JOIN kf_fin_types ft ON ft.id = t.finance_type_id";
    if ((int)$iTransactionId > 0) {
        $sSql .= " WHERE t.id = :id";
    }
    $sSql .= " ORDER BY t.transaction_date DESC, t.id DESC";
    if ((int)$iTransactionId > 0) {
        $oStatement = $oPdo->prepare($sSql);
        $oStatement->execute(array("id" => (int)$iTransactionId));
    } else {
        $oStatement = $oPdo->query($sSql);
    }
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function renderTransactionAdminRow($aRow, $blShowActions = true) {
    global $sEditEmoji, $sDeleteEmoji;

    $sAmountClass = $aRow["amount"] < 0 ? "amount-negative" : ($aRow["amount"] > 0 ? "amount-positive" : "amount-zero");
    $sActionCell = $blShowActions ? "<a href=\"#\" class=\"item-action js-edit-transaction\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-transaction\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "";
    return "      <tr data-transaction-id=\"" . (int)$aRow["id"] . "\" data-transaction-date=\"" . html(formatDate($aRow["transaction_date"])) . "\" data-finance-type-id=\"" . (int)$aRow["finance_type_id"] . "\" data-amount=\"" . html(formatAmount(abs($aRow["amount"]))) . "\" data-counterparty=\"" . html($aRow["counterparty"]) . "\" data-note=\"" . html($aRow["note"]) . "\">\n"
        . "        <td class=\"nowrap\">" . html(formatDate($aRow["transaction_date"])) . "</td>\n"
        . "        <td>" . html($aRow["type_name"]) . "</td>\n"
        . "        <td class=\"numeric " . $sAmountClass . "\">" . html(formatAmount($aRow["amount"])) . "</td>\n"
        . "        <td>" . htmlValue($aRow["counterparty"], "&mdash;") . "</td>\n"
        . "        <td>" . htmlValue($aRow["note"], "&mdash;") . "</td>\n"
        . ($blShowActions ? "        <td class=\"admin-action-column\">" . $sActionCell . "</td>\n" : "")
        . "      </tr>\n";
}

function renderTransactionAdminRows($aRows, $blShowActions = true) {
    $sHtml = "";
    foreach ($aRows as $aRow) {
        $sHtml .= renderTransactionAdminRow($aRow, $blShowActions);
    }
    if (!$aRows) {
        $sHtml .= "      <tr><td colspan=\"" . ($blShowActions ? 6 : 5) . "\">No transactions found.</td></tr>\n";
    }
    return $sHtml;
}

function fetchFinanceTypeAdminRows($oPdo, $iTypeId = 0) {
    $sSql = "SELECT ft.id, ft.name, ft.type_kind, GROUP_CONCAT(m.id ORDER BY m.name SEPARATOR ',') AS member_ids, GROUP_CONCAT(m.name ORDER BY m.name SEPARATOR ', ') AS member_names FROM kf_fin_types ft LEFT JOIN kf_fin_groups gi ON gi.group_type_id = ft.id LEFT JOIN kf_fin_types m ON m.id = gi.member_type_id";
    if ((int)$iTypeId > 0) {
        $sSql .= " WHERE ft.id = :id";
    }
    $sSql .= " GROUP BY ft.id, ft.name, ft.type_kind ORDER BY FIELD(ft.type_kind, 'income', 'expense', 'group'), ft.name ASC, ft.id ASC";
    if ((int)$iTypeId > 0) {
        $oStatement = $oPdo->prepare($sSql);
        $oStatement->execute(array("id" => (int)$iTypeId));
    } else {
        $oStatement = $oPdo->query($sSql);
    }
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function renderFinanceTypeAdminRow($aRow, $blShowActions = true) {
    global $sEditEmoji, $sDeleteEmoji;

    $sActionCell = $blShowActions ? "<a href=\"#\" class=\"item-action js-edit-type\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-type\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "";
    return "      <tr data-type-id=\"" . (int)$aRow["id"] . "\" data-type-name=\"" . html($aRow["name"]) . "\" data-type-kind=\"" . html($aRow["type_kind"]) . "\" data-members=\"" . html($aRow["member_ids"]) . "\">\n"
        . "        <td>" . html(ucfirst($aRow["type_kind"])) . "</td>\n"
        . "        <td>" . html($aRow["name"]) . "</td>\n"
        . "        <td>" . htmlValue($aRow["member_names"], "&mdash;") . "</td>\n"
        . ($blShowActions ? "        <td class=\"admin-action-column\">" . $sActionCell . "</td>\n" : "")
        . "      </tr>\n";
}

function renderFinanceTypeAdminRows($aRows, $blShowActions = true) {
    $sHtml = "";
    foreach ($aRows as $aRow) {
        $sHtml .= renderFinanceTypeAdminRow($aRow, $blShowActions);
    }
    if (!$aRows) {
        $sHtml .= "      <tr><td colspan=\"" . ($blShowActions ? 4 : 3) . "\">No types found.</td></tr>\n";
    }
    return $sHtml;
}

function phoneContactTypes() {
    return array(
        "landline" => true,
        "cell" => true,
        "fax" => true,
        "pager" => true
    );
}

function isPhoneContactType($sContactType) {
    $aPhoneTypes = phoneContactTypes();
    return isset($aPhoneTypes[(string)$sContactType]);
}

function phoneMetadataRegex($sPattern) {
    return preg_replace("/\\s+/", "", trim((string)$sPattern));
}

function phonePatternMatches($sPattern, $sValue, $blFullMatch = true, &$aMatches = null) {
    $sPattern = phoneMetadataRegex($sPattern);
    $aMatches = array();
    if ($sPattern == "") {
        return false;
    }
    $sRegex = $blFullMatch ? "~^(?:" . str_replace("~", "\\~", $sPattern) . ")$~" : "~^(?:" . str_replace("~", "\\~", $sPattern) . ")~";
    return @preg_match($sRegex, (string)$sValue, $aMatches);
}

function phoneMetadata() {
    static $aMetadata = null;

    if ($aMetadata !== null) {
        return $aMetadata;
    }
    $aMetadata = array("codes" => array());
    if (!function_exists("simplexml_load_file")) {
        return $aMetadata;
    }
    $sFile = __DIR__ . "/../ex/lib/phone_metadata.xml";
    $blPreviousLibxmlState = libxml_use_internal_errors(true);
    $sXml = is_file($sFile) ? file_get_contents($sFile) : "";
    $sXml = preg_replace("/^\\xEF\\xBB\\xBF/", "", (string)$sXml);
    $oXml = $sXml != "" ? simplexml_load_string($sXml) : false;
    libxml_clear_errors();
    libxml_use_internal_errors($blPreviousLibxmlState);
    if (!$oXml || !isset($oXml->territories->territory)) {
        return $aMetadata;
    }
    foreach ($oXml->territories->territory as $oTerritory) {
        $sCountryCode = (string)$oTerritory["countryCode"];
        if ($sCountryCode == "") {
            continue;
        }
        $aFormats = array();
        if (isset($oTerritory->availableFormats->numberFormat)) {
            foreach ($oTerritory->availableFormats->numberFormat as $oFormat) {
                $aLeadingDigits = array();
                foreach ($oFormat->leadingDigits as $oLeadingDigits) {
                    $aLeadingDigits[] = phoneMetadataRegex((string)$oLeadingDigits);
                }
                $aFormats[] = array(
                    "pattern" => phoneMetadataRegex((string)$oFormat["pattern"]),
                    "format" => (string)$oFormat->format,
                    "leading_digits" => $aLeadingDigits
                );
            }
        }
        if (!isset($aMetadata["codes"][$sCountryCode])) {
            $aMetadata["codes"][$sCountryCode] = array();
        }
        $aMetadata["codes"][$sCountryCode][] = array(
            "id" => (string)$oTerritory["id"],
            "main" => (string)$oTerritory["mainCountryForCode"] == "true",
            "leading_digits" => phoneMetadataRegex((string)$oTerritory["leadingDigits"]),
            "national_prefix" => preg_replace("/\\D/", "", (string)$oTerritory["nationalPrefix"]),
            "pattern" => phoneMetadataRegex((string)$oTerritory->generalDesc->nationalNumberPattern),
            "formats" => $aFormats
        );
    }
    return $aMetadata;
}

function findPhoneTerritory($sDigits) {
    $aMetadata = phoneMetadata();
    $iMaxCountryCodeLength = min(3, strlen((string)$sDigits) - 1);
    for ($iLength = $iMaxCountryCodeLength; $iLength >= 1; $iLength--) {
        $sCountryCode = substr((string)$sDigits, 0, $iLength);
        if (!isset($aMetadata["codes"][$sCountryCode])) {
            continue;
        }
        $sNationalNumber = substr((string)$sDigits, $iLength);
        foreach ($aMetadata["codes"][$sCountryCode] as $aTerritory) {
            $aNationalNumbers = array($sNationalNumber);
            if ($aTerritory["national_prefix"] != "" && strpos($sNationalNumber, (string)$aTerritory["national_prefix"]) === 0) {
                $aNationalNumbers[] = substr($sNationalNumber, strlen((string)$aTerritory["national_prefix"]));
            }
            foreach ($aNationalNumbers as $sCandidateNationalNumber) {
                if ($aTerritory["leading_digits"] != "" && !phonePatternMatches((string)$aTerritory["leading_digits"], $sCandidateNationalNumber, false)) {
                    continue;
                }
                if (phonePatternMatches((string)$aTerritory["pattern"], $sCandidateNationalNumber, true)) {
                    return array(
                        "country_code" => $sCountryCode,
                        "national_number" => $sCandidateNationalNumber,
                        "territory" => $aTerritory
                    );
                }
            }
        }
        return false;
    }
    return false;
}

function phoneDefaultFormats($sCountryCode) {
    $aMetadata = phoneMetadata();
    $aFallbackFormats = array();
    if (!isset($aMetadata["codes"][(string)$sCountryCode])) {
        return array();
    }
    foreach ($aMetadata["codes"][(string)$sCountryCode] as $aTerritory) {
        if (count($aTerritory["formats"]) > 0 && !empty($aTerritory["main"])) {
            return $aTerritory["formats"];
        }
        if (!$aFallbackFormats && count($aTerritory["formats"]) > 0) {
            $aFallbackFormats = $aTerritory["formats"];
        }
    }
    return $aFallbackFormats;
}

function applyPhoneNumberFormat($sPattern, $sFormat, $sNationalNumber) {
    $aMatches = array();
    $sFormatted = (string)$sFormat;
    if ($sFormatted == "" || !phonePatternMatches($sPattern, $sNationalNumber, true, $aMatches)) {
        return "";
    }
    for ($iIndex = 1; $iIndex < count($aMatches); $iIndex++) {
        $sFormatted = str_replace("$" . $iIndex, $aMatches[$iIndex], $sFormatted);
    }
    return $sFormatted;
}

function formatPhoneContactDisplayValue($sCountryCode, $sNationalNumber, $aTerritory) {
    $aFormats = count($aTerritory["formats"]) > 0 ? $aTerritory["formats"] : phoneDefaultFormats($sCountryCode);
    foreach ($aFormats as $aFormat) {
        $aLeadingDigits = $aFormat["leading_digits"];
        if (count($aLeadingDigits) > 0 && !phonePatternMatches($aLeadingDigits[count($aLeadingDigits) - 1], $sNationalNumber, false)) {
            continue;
        }
        $sFormatted = applyPhoneNumberFormat((string)$aFormat["pattern"], (string)$aFormat["format"], $sNationalNumber);
        if ($sFormatted != "") {
            return "+" . (string)$sCountryCode . " " . $sFormatted;
        }
    }
    return "+" . (string)$sCountryCode . " " . (string)$sNationalNumber;
}

function analyzePhoneContactValue($sValue) {
    $sText = trim((string)$sValue);
    $sDigits = "";
    $aPhone = array();
    if ($sText == "") {
        return array("valid" => true, "canonical" => "", "display" => "");
    }
    if (!preg_match("/^(?:\\+|00)/", $sText) && preg_match("/^[0-9().\\s\\-]+$/", $sText)) {
        $sDigits = preg_replace("/\\D/", "", $sText);
        $sText = "+420" . $sDigits;
    }
    if (!preg_match("/^(?:\\+|00)[0-9().\\s\\-]+$/", $sText)) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    if (strpos($sText, "+") === 0) {
        $sDigits = preg_replace("/\\D/", "", substr($sText, 1));
    } else {
        $sDigits = preg_replace("/\\D/", "", substr($sText, 2));
    }
    if (!preg_match("/^[1-9][0-9]{5,14}$/", $sDigits)) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    $aPhone = findPhoneTerritory($sDigits);
    if ($aPhone === false) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    return array(
        "valid" => true,
        "canonical" => "+" . (string)$aPhone["country_code"] . "." . (string)$aPhone["national_number"],
        "display" => formatPhoneContactDisplayValue((string)$aPhone["country_code"], (string)$aPhone["national_number"], $aPhone["territory"])
    );
}

function normalizePhoneContactValue($sValue) {
    $aPhone = analyzePhoneContactValue($sValue);
    if (empty($aPhone["valid"])) {
        return false;
    }
    if (strpos((string)$aPhone["canonical"], "00") === 0) {
        return "+" . substr((string)$aPhone["canonical"], 2);
    }
    return (string)$aPhone["canonical"];
}

function phoneContactDisplayValue($sValue) {
    $aPhone = analyzePhoneContactValue($sValue);
    return !empty($aPhone["valid"]) ? (string)$aPhone["display"] : (string)$sValue;
}

function phoneContactHref($sValue) {
    $aPhone = analyzePhoneContactValue($sValue);
    return !empty($aPhone["valid"]) && $aPhone["canonical"] != "" ? "tel:" . str_replace(".", "", (string)$aPhone["canonical"]) : "";
}

function normalizeEmailContactValue($sValue) {
    $sText = strtolower(trim((string)$sValue));
    if ($sText == "") {
        return "";
    }
    return filter_var($sText, FILTER_VALIDATE_EMAIL) ? $sText : false;
}

function contactTypeKey($sContactType) {
    return strtolower(trim((string)$sContactType));
}

function contactValueIsInvalid($sType, $sValue) {
    $sType = contactTypeKey($sType);
    if (trim((string)$sValue) == "") {
        return false;
    }
    if (isPhoneContactType($sType)) {
        return normalizePhoneContactValue($sValue) === false;
    }
    if ((string)$sType == "email") {
        return normalizeEmailContactValue($sValue) === false;
    }
    return false;
}

function contactDisplayValue($sType, $sValue) {
    $sType = contactTypeKey($sType);
    if (isPhoneContactType($sType)) {
        return phoneContactDisplayValue($sValue);
    }
    if ((string)$sType == "email") {
        $mEmail = normalizeEmailContactValue($sValue);
        return $mEmail !== false ? (string)$mEmail : (string)$sValue;
    }
    return (string)$sValue;
}

function contactHref($sType, $sValue, $blAllowExternalLinks = false) {
    $sType = contactTypeKey($sType);
    if (isPhoneContactType($sType)) {
        return phoneContactHref($sValue);
    }
    if ($sType == "email") {
        $sText = normalizeEmailContactValue($sValue);
        return $sText !== false && $sText != "" ? "mailto:" . $sText : "";
    }
    return "";
}

function contactLinkEmoji($sType) {
    global $sContactEmailEmoji, $sContactLandlineEmoji, $sContactCellEmoji, $sContactFaxEmoji, $sContactPagerEmoji;

    $sType = contactTypeKey($sType);
    if ($sType == "email") {
        return $sContactEmailEmoji;
    }
    if ($sType == "landline") {
        return $sContactLandlineEmoji;
    }
    if ($sType == "cell") {
        return $sContactCellEmoji;
    }
    if ($sType == "fax") {
        return $sContactFaxEmoji;
    }
    if ($sType == "pager") {
        return $sContactPagerEmoji;
    }
    return "";
}

function contactLinkTitle($sType) {
    $sType = contactTypeKey($sType);
    if ($sType == "email") {
        return "Send e-mail";
    }
    if ($sType == "landline") {
        return "Call landline";
    }
    if ($sType == "cell") {
        return "Call cell phone";
    }
    if ($sType == "fax") {
        return "Call fax";
    }
    if ($sType == "pager") {
        return "Call pager";
    }
    return "";
}

function renderContactValue($sType, $sValue, $blShowCopy = false, $blAllowExternalLinks = false, $sTooltipAttribute = "") {
    return renderContactValueText($sType, $sValue, $sTooltipAttribute) . renderContactValueActions($sType, $sValue, $blShowCopy, $blAllowExternalLinks);
}

function renderContactValueText($sType, $sValue, $sTooltipAttribute = "") {
    $sDisplayValue = contactDisplayValue($sType, $sValue);
    $sClass = "contact-value" . (contactValueIsInvalid($sType, $sValue) ? " invalid-contact-value" : "");
    return "<span class=\"" . html($sClass) . "\"" . $sTooltipAttribute . ">" . html($sDisplayValue) . "</span>";
}

function renderContactValueActions($sType, $sValue, $blShowCopy = false, $blAllowExternalLinks = false) {
    global $sCopyEmoji;

    $sDisplayValue = contactDisplayValue($sType, $sValue);
    $sHref = contactHref($sType, $sValue, $blAllowExternalLinks);
    $sHtml = "";
    $sLinkTitle = "";
    $blHasIcon = false;
    if ($blShowCopy && $sDisplayValue != "") {
        $sHtml .= "<a class=\"contact-copy\" href=\"#\" title=\"Copy\" aria-label=\"Copy\"><span class=\"copy-action-box\">" . $sCopyEmoji . "</span></a>";
        $blHasIcon = true;
    }
    if ($sHref != "") {
        $sLinkTitle = contactLinkTitle($sType);
        return $sHtml . ($blHasIcon ? "" : " ") . "<a class=\"contact-link\" href=\"" . html($sHref) . "\" title=\"" . html($sLinkTitle) . "\" aria-label=\"" . html($sLinkTitle) . "\">" . contactLinkEmoji($sType) . "</a>";
    }
    return $sHtml;
}

function renderDebtPhoneValue($sType, $sValue) {
    return renderDebtContactValue($sType, $sValue, "", false);
}

function renderDebtNoteValue($sNote) {
    global $sEmptyValueEmoji;

    $sNote = trim((string)$sNote);
    if ($sNote == "") {
        return $sEmptyValueEmoji;
    }
    return "<span class=\"subject-item-value\">" . nl2br(html($sNote), false) . "</span>" . renderCopyAction($sNote);
}

function renderDebtContactValue($sType, $sValue, $sNote, $blIsPrimary = false) {
    global $sEmptyValueEmoji, $sPrimaryEmoji;

    $sDisplayValue = contactDisplayValue($sType, $sValue);
    $sNote = trim((string)$sNote);
    if (trim($sDisplayValue) == "") {
        return $sEmptyValueEmoji;
    }
    return "<span class=\"contact-item\" data-contact-value=\"" . html($sDisplayValue) . "\">"
        . "<span class=\"contact-db-values\">" . renderContactValueText($sType, $sValue) . "</span>"
        . renderContactValueActions($sType, $sValue, true, false)
        . "<span class=\"contact-note\">" . ($sNote != "" ? "(" . html($sNote) . ")" : "") . "</span>"
        . "<span class=\"contact-flags\"><span class=\"contact-primary\" title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span></span>"
        . "</span>";
}

