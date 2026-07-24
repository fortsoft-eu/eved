<?php

function redirect($sPath) {
    sendSecurityHeaders();
    header("Location: " . $sPath, true, 303);
    exit;
}

function getSettingsDefaults() {
    return array("use_european_amount_format" => 0);
}

function getCurrentSettingsPageKey() {
    return getCurrentScriptName();
}

function getPageSettingsDefaults($sPageKey = "") {
    if ($sPageKey == "") {
        $sPageKey = getCurrentSettingsPageKey();
    }
    return array("display_currency" => $sPageKey == "subscr.php" ? "" : "CZK");
}

function getDefaultCurrency() {
    return "USD";
}

function getNewSubscriptionDefaults($oPdo) {
    $aDefaults = array(
        "finance_type_id" => 0,
        "currency" => getDefaultCurrency(),
        "billing_period" => "monthly"
    );
    if (!isset($_SESSION["kf_new_subscription_defaults"]) || !is_array($_SESSION["kf_new_subscription_defaults"])) {
        return $aDefaults;
    }
    $iFinanceTypeId = isset($_SESSION["kf_new_subscription_defaults"]["finance_type_id"]) ? (int)$_SESSION["kf_new_subscription_defaults"]["finance_type_id"] : 0;
    if ($iFinanceTypeId > 0) {
        foreach (getFinanceTypes(false) as $aType) {
            if ((int)$aType["id"] == $iFinanceTypeId) {
                $aDefaults["finance_type_id"] = $iFinanceTypeId;
                break;
            }
        }
    }
    $sCurrency = isset($_SESSION["kf_new_subscription_defaults"]["currency"]) ? normalizeCurrency($_SESSION["kf_new_subscription_defaults"]["currency"]) : "";
    if ($sCurrency != "" && isCurrencyAvailable($oPdo, $sCurrency)) {
        $aDefaults["currency"] = $sCurrency;
    }
    $sBillingPeriod = isset($_SESSION["kf_new_subscription_defaults"]["billing_period"]) ? (string)$_SESSION["kf_new_subscription_defaults"]["billing_period"] : "";
    $aBillingPeriods = getSubscriptionBillingPeriods();
    if (isset($aBillingPeriods[$sBillingPeriod])) {
        $aDefaults["billing_period"] = $sBillingPeriod;
    }
    return $aDefaults;
}

function saveNewSubscriptionDefaults($iFinanceTypeId, $sCurrency, $sBillingPeriod) {
    if (!isset($_SESSION["kf_new_subscription_defaults"]) || !is_array($_SESSION["kf_new_subscription_defaults"])) {
        $_SESSION["kf_new_subscription_defaults"] = array();
    }
    $_SESSION["kf_new_subscription_defaults"]["finance_type_id"] = (int)$iFinanceTypeId;
    $_SESSION["kf_new_subscription_defaults"]["currency"] = normalizeStoredCurrency($sCurrency);
    $_SESSION["kf_new_subscription_defaults"]["billing_period"] = (string)$sBillingPeriod;
}

function getSettings() {
    $aSettingsDefaults = getSettingsDefaults();
    $sPageKey = getCurrentSettingsPageKey();
    $aPageSettingsDefaults = getPageSettingsDefaults($sPageKey);
    $aSettings = array();
    if (!isset($_SESSION["kf_settings"]) || !is_array($_SESSION["kf_settings"])) {
        $_SESSION["kf_settings"] = array();
    }
    if (!isset($_SESSION["kf_page_settings"]) || !is_array($_SESSION["kf_page_settings"])) {
        $_SESSION["kf_page_settings"] = array();
    }
    if (!isset($_SESSION["kf_page_settings"][$sPageKey]) || !is_array($_SESSION["kf_page_settings"][$sPageKey])) {
        $_SESSION["kf_page_settings"][$sPageKey] = array();
    }
    if (isset($_SESSION["kf_debts_settings"]) && is_array($_SESSION["kf_debts_settings"])) {
        foreach ($aSettingsDefaults as $sSettingName => $iSettingDefault) {
            if (isset($_SESSION["kf_debts_settings"][$sSettingName])
                && (!isset($_SESSION["kf_settings"][$sSettingName]) || (int)$_SESSION["kf_settings"][$sSettingName] == (int)$iSettingDefault)) {
                $_SESSION["kf_settings"][$sSettingName] = (int)$_SESSION["kf_debts_settings"][$sSettingName] == 1 ? 1 : 0;
            }
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
    foreach ($aPageSettingsDefaults as $sSettingName => $sSettingDefault) {
        if (isset($_SESSION["kf_page_settings"][$sPageKey][$sSettingName])) {
            $aSettings[$sSettingName] = normalizeCurrency($_SESSION["kf_page_settings"][$sPageKey][$sSettingName]);
        } else {
            $aSettings[$sSettingName] = $sSettingDefault;
        }
    }
    unset($_SESSION["kf_debts_settings"]);
    return $aSettings;
}

function saveSettings($aPayload) {
    global $oPdo;

    $aSettings = array();
    $sPageKey = getCurrentSettingsPageKey();
    $aPageSettings = array();
    foreach (getSettingsDefaults() as $sSettingName => $iSettingDefault) {
        $aSettings[$sSettingName] = isset($aPayload[$sSettingName]) && (string)$aPayload[$sSettingName] == "1" ? 1 : 0;
    }
    if (!isset($_SESSION["kf_page_settings"]) || !is_array($_SESSION["kf_page_settings"])) {
        $_SESSION["kf_page_settings"] = array();
    }
    $aPageSettings["display_currency"] = isset($aPayload["display_currency"]) ? normalizeCurrency($aPayload["display_currency"]) : "";
    if ($aPageSettings["display_currency"] != "" && !isCurrencyAvailable($oPdo, $aPageSettings["display_currency"])) {
        $aPageSettingsDefaults = getPageSettingsDefaults($sPageKey);
        $aPageSettings["display_currency"] = $aPageSettingsDefaults["display_currency"];
    }
    $_SESSION["kf_settings"] = $aSettings;
    $_SESSION["kf_page_settings"][$sPageKey] = $aPageSettings;
    unset($_SESSION["kf_debts_settings"]);
    return $aSettings;
}

function handleSettingsPost() {
    global $sBaseUrl;

    if ($_SERVER["REQUEST_METHOD"] != "POST" || getPostedTrimmedValue("action") != "save_settings") {
        return;
    }
    requireNamedCsrfToken("kf_csrf_token");
    saveSettings($_POST);
    session_write_close();
    $sScriptName = basename($_SERVER["SCRIPT_NAME"]);
    redirect($sBaseUrl . ($sScriptName == "index.php" ? "" : $sScriptName));
}

function renderSettingsModal($aSettings = null) {
    global $oPdo, $sBaseUrl;

    if (!is_array($aSettings)) {
        $aSettings = getSettings();
    }
    $sScriptName = basename($_SERVER["SCRIPT_NAME"]);
    $sAction = $sBaseUrl . ($sScriptName == "index.php" ? "" : $sScriptName);
    $aCurrencyOptions = getCurrencyOptions($oPdo, $aSettings["display_currency"]);
    $sCurrencyOptionsHtml = "          <option value=\"\"" . ($aSettings["display_currency"] == "" ? " selected" : "") . ">As entered</option>\n";
    foreach ($aCurrencyOptions as $aCurrencyOption) {
        $sCurrency = (string)$aCurrencyOption["currency"];
        $sCurrencyOptionsHtml .= "          <option value=\"" . html($sCurrency) . "\"" . ($aSettings["display_currency"] == $sCurrency ? " selected" : "") . ">" . html($aCurrencyOption["label"]) . "</option>\n";
    }
    return "  <div class=\"confirm-dialog index-settings-dialog\" id=\"index-settings-dialog\" hidden>\n"
        . "    <form class=\"confirm-dialog-box index-settings-form\" method=\"post\" action=\"" . html($sAction) . "\" enctype=\"application/x-www-form-urlencoded\">\n"
        . "      <input type=\"hidden\" name=\"action\" value=\"save_settings\">\n"
        . "      <input type=\"hidden\" name=\"kf_csrf_token\" value=\"" . html(getCsrfToken("kf_csrf_token")) . "\">\n"
        . "      <div class=\"confirm-dialog-header\">\n"
        . "        <strong>Settings</strong>\n"
        . "        <button type=\"button\" class=\"confirm-dialog-close js-index-settings-close\" aria-label=\"Close\">&times;</button>\n"
        . "      </div>\n"
        . "      <div class=\"index-settings-options\">\n"
        . "        <label for=\"display-currency\">Display currency</label>\n"
        . "        <select id=\"display-currency\" name=\"display_currency\" class=\"currency-select\">\n"
        . $sCurrencyOptionsHtml
        . "        </select>\n"
        . "        <div class=\"index-settings-separator\"></div>\n"
        . "        <label><input type=\"checkbox\" name=\"use_european_amount_format\" value=\"1\"" . ($aSettings["use_european_amount_format"] ? " checked" : "") . "> Use European number format for amounts</label>\n"
        . "      </div>\n"
        . "      <p class=\"index-settings-note\">Options above the line apply only to this listing. Options below the line are shared across the KF subproject.</p>\n"
        . "      <div class=\"confirm-dialog-actions\">\n"
        . "        <button type=\"submit\" class=\"confirm-dialog-button\">Save</button>\n"
        . "        <button type=\"button\" class=\"confirm-dialog-button js-index-settings-cancel\">Cancel</button>\n"
        . "      </div>\n"
        . "    </form>\n"
        . "  </div>\n";
}

function normalizeCurrency($sCurrency) {
    $sCurrency = strtoupper(trim((string)$sCurrency));
    return preg_match("/^[A-Z]{3}$/", $sCurrency) ? $sCurrency : "";
}

function normalizeStoredCurrency($sCurrency) {
    $sCurrency = normalizeCurrency($sCurrency);
    return $sCurrency != "" ? $sCurrency : getDefaultCurrency();
}

function getCurrencyOptions($oPdo, $sSelectedCurrency = "") {
    $sCurrencySeparator = " " . html_entity_decode("&#8212;", ENT_QUOTES, "UTF-8") . " ";
    $aCurrencies = array(
        "CZK" => array("currency" => "CZK", "label" => "CZK" . $sCurrencySeparator . "Czech koruna")
    );
    if ($oPdo) {
        try {
            $oStatement = $oPdo->query("SELECT currency_code, MIN(currency) AS currency_name FROM kf_exchange_rates GROUP BY currency_code ORDER BY currency_code ASC");
            while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
                $sCurrency = normalizeCurrency($aRow["currency_code"]);
                if ($sCurrency == "") {
                    continue;
                }
                $sCurrencyName = trim((string)$aRow["currency_name"]);
                $aCurrencies[$sCurrency] = array(
                    "currency" => $sCurrency,
                    "label" => $sCurrencyName != "" ? $sCurrency . $sCurrencySeparator . $sCurrencyName : $sCurrency
                );
            }
        } catch (Exception $oException) {
            error_log((string)$oException);
        }
    }
    $sSelectedCurrency = normalizeCurrency($sSelectedCurrency);
    if ($sSelectedCurrency != "" && !isset($aCurrencies[$sSelectedCurrency])) {
        $aCurrencies[$sSelectedCurrency] = array("currency" => $sSelectedCurrency, "label" => $sSelectedCurrency);
    }
    ksort($aCurrencies);
    return array_values($aCurrencies);
}

function isCurrencyAvailable($oPdo, $sCurrency) {
    $sCurrency = normalizeCurrency($sCurrency);
    if ($sCurrency == "") {
        return false;
    }
    if ($sCurrency == "CZK") {
        return true;
    }
    if (!$oPdo) {
        return false;
    }
    try {
        $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM kf_exchange_rates WHERE currency_code = :currency_code");
        $oStatement->execute(array("currency_code" => $sCurrency));
        return (int)$oStatement->fetchColumn() > 0;
    } catch (Exception $oException) {
        error_log((string)$oException);
        return false;
    }
}

function getCurrencyOptionsJson($oPdo, $sSelectedCurrency = "") {
    return json_encode(getCurrencyOptions($oPdo, $sSelectedCurrency));
}

function getPostedCurrency($sName = "currency") {
    $sCurrency = normalizeCurrency(getPostedTrimmedValue($sName, getDefaultCurrency()));
    return $sCurrency != "" ? $sCurrency : getDefaultCurrency();
}

function getCurrencyRateToCzk($oPdo, $sCurrency, $sDate) {
    static $aRates = array();

    $sCurrency = normalizeStoredCurrency($sCurrency);
    $sDate = formatDate($sDate);
    if ($sCurrency == "CZK") {
        return 1.0;
    }
    if (!$oPdo) {
        return null;
    }
    $sKey = $sCurrency . "|" . $sDate;
    if (array_key_exists($sKey, $aRates)) {
        return $aRates[$sKey];
    }
    if ($sDate == "") {
        $oStatement = $oPdo->prepare("SELECT amount, rate FROM kf_exchange_rates WHERE currency_code = :currency_code ORDER BY valid_for DESC, id DESC LIMIT 1");
        $oStatement->execute(array(
            "currency_code" => $sCurrency
        ));
        $aRate = $oStatement->fetch(PDO::FETCH_ASSOC);
    } else {
        $oStatement = $oPdo->prepare("SELECT amount, rate FROM kf_exchange_rates WHERE currency_code = :currency_code AND valid_for <= :valid_for ORDER BY valid_for DESC, id DESC LIMIT 1");
        $oStatement->execute(array(
            "currency_code" => $sCurrency,
            "valid_for" => $sDate
        ));
        $aRate = $oStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aRate) {
            $oStatement = $oPdo->prepare("SELECT amount, rate FROM kf_exchange_rates WHERE currency_code = :currency_code AND valid_for > :valid_for ORDER BY valid_for ASC, id ASC LIMIT 1");
            $oStatement->execute(array(
                "currency_code" => $sCurrency,
                "valid_for" => $sDate
            ));
            $aRate = $oStatement->fetch(PDO::FETCH_ASSOC);
        }
    }
    if (!$aRate || (int)$aRate["amount"] < 1) {
        $aRates[$sKey] = null;
        return null;
    }
    $aRates[$sKey] = (float)$aRate["rate"] / (int)$aRate["amount"];
    return $aRates[$sKey];
}

function convertCurrencyAmount($oPdo, $mAmount, $sSourceCurrency, $sTargetCurrency, $sDate) {
    $sSourceCurrency = normalizeStoredCurrency($sSourceCurrency);
    $sTargetCurrency = normalizeCurrency($sTargetCurrency);
    if ($sTargetCurrency == "" || $sSourceCurrency == $sTargetCurrency) {
        return (float)$mAmount;
    }
    $fSourceRate = getCurrencyRateToCzk($oPdo, $sSourceCurrency, $sDate);
    $fTargetRate = getCurrencyRateToCzk($oPdo, $sTargetCurrency, $sDate);
    if ($fSourceRate === null || $fTargetRate === null || $fTargetRate == 0.0) {
        return null;
    }
    return (float)$mAmount * $fSourceRate / $fTargetRate;
}

function getDisplayCurrencyAmount($oPdo, $mAmount, $sSourceCurrency, $sDate, $sDisplayCurrency) {
    $sSourceCurrency = normalizeStoredCurrency($sSourceCurrency);
    $sDisplayCurrency = normalizeCurrency($sDisplayCurrency);
    if ($sDisplayCurrency == "") {
        return array(
            "amount" => (float)$mAmount,
            "currency" => $sSourceCurrency,
            "converted" => false
        );
    }
    $mConvertedAmount = convertCurrencyAmount($oPdo, $mAmount, $sSourceCurrency, $sDisplayCurrency, $sDate);
    if ($mConvertedAmount === null) {
        return array(
            "amount" => (float)$mAmount,
            "currency" => $sSourceCurrency,
            "converted" => false
        );
    }
    return array(
        "amount" => (float)$mConvertedAmount,
        "currency" => $sDisplayCurrency,
        "converted" => $sSourceCurrency != $sDisplayCurrency
    );
}

function formatCurrencyAmount($oPdo, $mAmount, $sSourceCurrency, $sDate, $sDisplayCurrency, $blUseEuropeanAmountFormat = false) {
    $aDisplayAmount = getDisplayCurrencyAmount($oPdo, $mAmount, $sSourceCurrency, $sDate, $sDisplayCurrency);
    return formatAmount($aDisplayAmount["amount"], $blUseEuropeanAmountFormat) . " " . $aDisplayAmount["currency"];
}

function getDisplayCurrencyTotalAmount($oPdo, $aRows, $sDateColumn, $sDisplayCurrency) {
    $sDisplayCurrency = normalizeCurrency($sDisplayCurrency);
    $sTargetCurrency = $sDisplayCurrency;
    $sStoredCurrency = "";
    $blMixedCurrencies = false;
    $blConversionFailed = false;
    foreach ($aRows as $aRow) {
        $sCurrency = normalizeStoredCurrency($aRow["currency"]);
        if ($sStoredCurrency == "") {
            $sStoredCurrency = $sCurrency;
        } elseif ($sStoredCurrency != $sCurrency) {
            $blMixedCurrencies = true;
        }
    }
    if ($sTargetCurrency == "") {
        $sTargetCurrency = $blMixedCurrencies ? "CZK" : ($sStoredCurrency != "" ? $sStoredCurrency : getDefaultCurrency());
    }
    $fTotalAmount = 0.0;
    foreach ($aRows as $aRow) {
        $mAmount = convertCurrencyAmount($oPdo, $aRow["amount"], $aRow["currency"], $sTargetCurrency, $aRow[$sDateColumn]);
        if ($mAmount === null && normalizeStoredCurrency($aRow["currency"]) != $sTargetCurrency) {
            $blConversionFailed = true;
        }
        $fTotalAmount += $mAmount === null ? (float)$aRow["amount"] : (float)$mAmount;
    }
    return array(
        "amount" => $fTotalAmount,
        "currency" => $sTargetCurrency,
        "source_currency" => $sStoredCurrency,
        "mixed_currencies" => $blMixedCurrencies,
        "conversion_failed" => $blConversionFailed
    );
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

function formatAmount($mAmount, $blUseEuropeanAmountFormat = false) {
    $fAmount = round((float)$mAmount, 2);
    $sDecimalSeparator = $blUseEuropeanAmountFormat ? "," : ".";
    $sThousandsSeparator = $blUseEuropeanAmountFormat ? " " : ",";
    $sAmount = number_format(abs($fAmount), 2, $sDecimalSeparator, $sThousandsSeparator);
    return $fAmount < 0 ? "−" . $sAmount : $sAmount;
}

function formatDate($sDate) {
    $sNormalized = normalizeInputDate($sDate);
    return $sNormalized !== false ? $sNormalized : "";
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
    $oStatement = $oPdo->prepare("SELECT id, debt_id, movement_date, amount, currency, note FROM kf_debt_movements WHERE debt_id IN (" . implode(", ", $aPlaceholders) . ") ORDER BY movement_date ASC, id ASC");
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

function renderDebtMovementValues($aMovements, $blShowActions = true, $blUseEuropeanAmountFormat = false, $sDisplayCurrency = "") {
    global $oPdo, $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji;

    if (!$aMovements) {
        return $sEmptyValueEmoji;
    }
    $sHtml = "<span class=\"debt-movements\">";
    foreach ($aMovements as $aMovement) {
        $fAmount = (float)$aMovement["amount"];
        $sAmountClass = $fAmount < 0 ? "amount-negative" : ($fAmount > 0 ? "amount-positive" : "amount-zero");
        $sCurrency = normalizeStoredCurrency($aMovement["currency"]);
        $sFormattedAmount = formatAmount($aMovement["amount"], $blUseEuropeanAmountFormat);
        $sDisplayedAmount = formatCurrencyAmount($oPdo, $aMovement["amount"], $sCurrency, $aMovement["movement_date"], $sDisplayCurrency, $blUseEuropeanAmountFormat);
        $sNote = trim((string)$aMovement["note"]);
        $sActions = $blShowActions ? "<span class=\"list-item-actions\"><a href=\"#\" class=\"item-action js-edit-debt-movement\" title=\"Edit movement\" aria-label=\"Edit movement\">" . $sEditEmoji . "</a><a href=\"#\" class=\"item-action js-delete-debt-movement\" title=\"Delete movement\" aria-label=\"Delete movement\">" . $sDeleteEmoji . "</a></span>" : "";
        $sHtml .= "<span class=\"debt-movement\" data-debt-movement-id=\"" . (int)$aMovement["id"] . "\" data-movement-date=\"" . html(formatDate($aMovement["movement_date"])) . "\" data-amount=\"" . html($sFormattedAmount) . "\" data-currency=\"" . html($sCurrency) . "\" data-note=\"" . html($sNote) . "\">"
            . "<span class=\"debt-movement-amount " . $sAmountClass . "\">" . html($sDisplayedAmount) . "</span>"
            . renderCopyAction($sDisplayedAmount)
            . "<span class=\"debt-movement-date\">" . html(formatDate($aMovement["movement_date"])) . "</span>"
            . ($sNote != "" ? "<span class=\"contact-note\"> (" . html($sNote) . ")</span>" : "")
            . $sActions
            . "</span>";
    }
    return $sHtml . "</span>";
}

function renderDebtAdminRow($aRow, $blShowActions = true, $blUseEuropeanAmountFormat = false, $sDisplayCurrency = "") {
    global $oPdo, $sAddEmoji, $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji;

    $aDisplayAmount = getDisplayCurrencyTotalAmount($oPdo, $aRow["debt_movements"], "movement_date", $sDisplayCurrency);
    $sCurrency = $aDisplayAmount["conversion_failed"] && !$aDisplayAmount["mixed_currencies"] ? $aDisplayAmount["source_currency"] : $aDisplayAmount["currency"];
    $sFormattedAmount = formatAmount($aDisplayAmount["amount"], $blUseEuropeanAmountFormat) . ($aDisplayAmount["conversion_failed"] && $aDisplayAmount["mixed_currencies"] ? "" : " " . $sCurrency);
    $sAmountClass = (float)$aDisplayAmount["amount"] < 0 ? "amount-negative" : ((float)$aDisplayAmount["amount"] > 0 ? "amount-positive" : "amount-zero");
    $sSubjectId = (int)$aRow["ex_subjects_id"] > 0 && (string)$aRow["subject_name"] != "" ? (string)(int)$aRow["ex_subjects_id"] : "";
    $sActionCell = $blShowActions ? "<a href=\"#\" class=\"item-action js-add-debt-movement\" title=\"New movement\" aria-label=\"New movement\">" . $sAddEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-edit-debt\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-debt\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "";
    return "      <tr data-debt-id=\"" . (int)$aRow["id"] . "\" data-ex-subjects-id=\"" . html($sSubjectId) . "\" data-subject-name=\"" . html($aRow["subject_name"]) . "\" data-amount=\"" . html($sFormattedAmount) . "\" data-note=\"" . html($aRow["note"]) . "\">\n"
        . "        <td><span class=\"subject-item-value\">" . htmlValue($aRow["subject_name"], $sEmptyValueEmoji) . "</span>" . renderCopyAction($aRow["subject_name"]) . "</td>\n"
        . "        <td class=\"numeric " . $sAmountClass . "\">" . html($sFormattedAmount) . renderCopyAction($sFormattedAmount) . "</td>\n"
        . "        <td class=\"debt-movements-cell\">" . renderDebtMovementValues($aRow["debt_movements"], $blShowActions, $blUseEuropeanAmountFormat, $sDisplayCurrency) . "</td>\n"
        . "        <td>" . renderDebtContactValue("bankaccount", $aRow["account_number"], $aRow["account_note"], (int)$aRow["account_primary"] == 1) . "</td>\n"
        . "        <td>" . renderDebtContactValue("email", $aRow["email"], $aRow["email_note"], (int)$aRow["email_primary"] == 1) . "</td>\n"
        . "        <td>" . renderDebtContactValue($aRow["phone_type"], $aRow["phone"], $aRow["phone_note"], (int)$aRow["phone_primary"] == 1) . "</td>\n"
        . "        <td>" . renderDebtNoteValue($aRow["note"]) . "</td>\n"
        . ($blShowActions ? "        <td class=\"admin-action-column\">" . $sActionCell . "</td>\n" : "")
        . "      </tr>\n";
}

function renderDebtAdminRows($aRows, $blShowActions = true, $blUseEuropeanAmountFormat = false, $sDisplayCurrency = "") {
    global $oPdo;

    $sHtml = "";
    $fTotal = 0;
    $sTotalCurrency = normalizeCurrency($sDisplayCurrency);
    if ($sTotalCurrency == "") {
        $sTotalCurrency = "CZK";
    }
    $blTotalConversionFailed = false;
    foreach ($aRows as $aRow) {
        $aDisplayAmount = getDisplayCurrencyTotalAmount($oPdo, $aRow["debt_movements"], "movement_date", $sTotalCurrency);
        $fTotal += (float)$aDisplayAmount["amount"];
        if ($aDisplayAmount["conversion_failed"]) {
            $blTotalConversionFailed = true;
        }
        $sHtml .= renderDebtAdminRow($aRow, $blShowActions, $blUseEuropeanAmountFormat, $sDisplayCurrency);
    }
    if ($aRows) {
        $sFormattedTotal = formatAmount($fTotal, $blUseEuropeanAmountFormat) . ($blTotalConversionFailed ? "" : " " . $sTotalCurrency);
        $sHtml .= "      <tr><td class=\"debt-total\">Total</td><td class=\"numeric debt-total\">" . html($sFormattedTotal) . renderCopyAction($sFormattedTotal) . "</td><td colspan=\"" . ($blShowActions ? 6 : 5) . "\"></td></tr>\n";
    } else {
        $sHtml .= "      <tr><td colspan=\"" . ($blShowActions ? 8 : 7) . "\">No debts found.</td></tr>\n";
    }
    return $sHtml;
}

function fetchTransactionAdminRows($oPdo, $iTransactionId = 0) {
    $sSql = "SELECT t.id, t.transaction_date, t.amount, t.currency, t.counterparty, t.note, ft.id AS finance_type_id, ft.name AS type_name, ft.type_kind FROM kf_fin_transactions t JOIN kf_fin_types ft ON ft.id = t.finance_type_id";
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

function renderTransactionAdminRow($aRow, $blShowActions = true, $blUseEuropeanAmountFormat = false, $sDisplayCurrency = "") {
    global $oPdo, $sEditEmoji, $sDeleteEmoji;

    $sAmountClass = $aRow["amount"] < 0 ? "amount-negative" : ($aRow["amount"] > 0 ? "amount-positive" : "amount-zero");
    $sCurrency = normalizeStoredCurrency($aRow["currency"]);
    $sFormattedAmount = formatCurrencyAmount($oPdo, $aRow["amount"], $sCurrency, $aRow["transaction_date"], $sDisplayCurrency, $blUseEuropeanAmountFormat);
    $sFormattedAbsoluteAmount = formatAmount(abs($aRow["amount"]), $blUseEuropeanAmountFormat);
    $sActionCell = $blShowActions ? "<a href=\"#\" class=\"item-action js-edit-transaction\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-transaction\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "";
    return "      <tr data-transaction-id=\"" . (int)$aRow["id"] . "\" data-transaction-date=\"" . html(formatDate($aRow["transaction_date"])) . "\" data-finance-type-id=\"" . (int)$aRow["finance_type_id"] . "\" data-amount=\"" . html($sFormattedAbsoluteAmount) . "\" data-currency=\"" . html($sCurrency) . "\" data-counterparty=\"" . html($aRow["counterparty"]) . "\" data-note=\"" . html($aRow["note"]) . "\">\n"
        . "        <td class=\"nowrap\">" . html(formatDate($aRow["transaction_date"])) . "</td>\n"
        . "        <td>" . html($aRow["type_name"]) . "</td>\n"
        . "        <td class=\"numeric " . $sAmountClass . "\">" . html($sFormattedAmount) . "</td>\n"
        . "        <td>" . htmlValue($aRow["counterparty"], "&mdash;") . "</td>\n"
        . "        <td>" . htmlValue($aRow["note"], "&mdash;") . "</td>\n"
        . ($blShowActions ? "        <td class=\"admin-action-column\">" . $sActionCell . "</td>\n" : "")
        . "      </tr>\n";
}

function renderTransactionAdminRows($aRows, $blShowActions = true, $blUseEuropeanAmountFormat = false, $sDisplayCurrency = "") {
    $sHtml = "";
    foreach ($aRows as $aRow) {
        $sHtml .= renderTransactionAdminRow($aRow, $blShowActions, $blUseEuropeanAmountFormat, $sDisplayCurrency);
    }
    if (!$aRows) {
        $sHtml .= "      <tr><td colspan=\"" . ($blShowActions ? 6 : 5) . "\">No transactions found.</td></tr>\n";
    }
    return $sHtml;
}

function getPostedAdditionalTransactions() {
    $aRows = array();
    if (!isset($_POST["additional_transactions"]) || !is_array($_POST["additional_transactions"])) {
        return $aRows;
    }
    foreach ($_POST["additional_transactions"] as $aInput) {
        if (!is_array($aInput)) {
            continue;
        }
        $iFinanceTypeId = isset($aInput["finance_type_id"]) ? (int)$aInput["finance_type_id"] : 0;
        $sAmount = isset($aInput["amount"]) ? trim((string)$aInput["amount"]) : "";
        $sCurrency = isset($aInput["currency"]) ? normalizeStoredCurrency($aInput["currency"]) : getDefaultCurrency();
        if ($iFinanceTypeId < 1 && $sAmount == "") {
            continue;
        }
        if ($sAmount == "") {
            continue;
        }
        $aRows[] = array(
            "finance_type_id" => $iFinanceTypeId,
            "amount" => parseAmount($sAmount),
            "currency" => $sCurrency
        );
    }
    return $aRows;
}

function validateAdditionalTransactions($oPdo, $aRows, $iMainFinanceTypeId, $sTypeKind, $fMainAmount, $sMainCurrency, $sDate) {
    $aValidatedRows = array();
    $aFinanceTypeIds = array((int)$iMainFinanceTypeId);
    $fTotalAmount = 0.0;
    foreach ($aRows as $aRow) {
        $iFinanceTypeId = (int)$aRow["finance_type_id"];
        $fAmount = $aRow["amount"];
        $sCurrency = normalizeStoredCurrency($aRow["currency"]);
        if ($iFinanceTypeId < 1 || in_array($iFinanceTypeId, $aFinanceTypeIds, true) || $fAmount === null || $fAmount <= 0 || !isCurrencyAvailable($oPdo, $sCurrency)) {
            return false;
        }
        $oStatement = $oPdo->prepare("SELECT id, type_kind FROM kf_fin_types WHERE id = :id AND type_kind IN ('income', 'expense')");
        $oStatement->execute(array("id" => $iFinanceTypeId));
        $aType = $oStatement->fetch();
        if (!$aType || $aType["type_kind"] != $sTypeKind) {
            return false;
        }
        $mConvertedAmount = convertCurrencyAmount($oPdo, $fAmount, $sCurrency, $sMainCurrency, $sDate);
        if ($mConvertedAmount === null) {
            return false;
        }
        $fTotalAmount += (float)$mConvertedAmount;
        if (round($fTotalAmount, 2) >= round(abs($fMainAmount), 2)) {
            return false;
        }
        $aValidatedRows[] = array(
            "finance_type_id" => $iFinanceTypeId,
            "amount" => (float)$fAmount,
            "currency" => $sCurrency,
            "converted_amount" => (float)$mConvertedAmount
        );
        $aFinanceTypeIds[] = $iFinanceTypeId;
    }
    return $aValidatedRows;
}

function getAdditionalTransactionsTotal($aRows) {
    $fTotalAmount = 0.0;
    foreach ($aRows as $aRow) {
        $fTotalAmount += abs((float)$aRow["converted_amount"]);
    }
    return $fTotalAmount;
}

function insertAdditionalTransactions($oPdo, $aRows, $sTypeKind, $sDate, $sCounterparty, $sNote) {
    if (!$aRows) {
        return;
    }
    $oStatement = $oPdo->prepare("INSERT INTO kf_fin_transactions (transaction_date, finance_type_id, amount, currency, counterparty, note) VALUES (:transaction_date, :finance_type_id, :amount, :currency, :counterparty, :note)");
    foreach ($aRows as $aRow) {
        $fSignedAmount = $sTypeKind == "expense" ? -abs((float)$aRow["amount"]) : abs((float)$aRow["amount"]);
        $oStatement->execute(array(
            "transaction_date" => $sDate,
            "finance_type_id" => (int)$aRow["finance_type_id"],
            "amount" => $fSignedAmount,
            "currency" => normalizeStoredCurrency($aRow["currency"]),
            "counterparty" => $sCounterparty != "" ? $sCounterparty : null,
            "note" => $sNote != "" ? $sNote : null
        ));
    }
}

function getSubscriptionBillingPeriods() {
    return array(
        "weekly" => "Weekly",
        "monthly" => "Monthly",
        "quarterly" => "Quarterly",
        "yearly" => "Yearly",
        "other" => "Other"
    );
}

function getSubscriptionBillingPeriodLabel($sBillingPeriod) {
    $aBillingPeriods = getSubscriptionBillingPeriods();
    return isset($aBillingPeriods[$sBillingPeriod]) ? $aBillingPeriods[$sBillingPeriod] : $sBillingPeriod;
}

function isSubscriptionAnchoredBillingPeriod($sBillingPeriod) {
    return in_array($sBillingPeriod, array("monthly", "quarterly", "yearly"), true);
}

function getSubscriptionBillingPeriodDateModifier($sBillingPeriod) {
    $aModifiers = array(
        "weekly" => "+1 week",
        "monthly" => "+1 month",
        "quarterly" => "+3 months",
        "yearly" => "+1 year"
    );
    return isset($aModifiers[$sBillingPeriod]) ? $aModifiers[$sBillingPeriod] : "";
}

function parseSubscriptionDueAt($sDueAt) {
    return parseInputDateTime($sDueAt);
}

function formatSubscriptionDueAt($sDueAt) {
    $oDueAt = parseSubscriptionDueAt($sDueAt);
    return $oDueAt ? $oDueAt->format("Y-m-d H:i") : "";
}

function formatSubscriptionDueInput($sDueAt) {
    $sNormalized = normalizeInputDateTime($sDueAt);
    return $sNormalized !== false ? $sNormalized : "";
}

function formatSubscriptionDueForDatabase($sDueAt) {
    $sNormalized = normalizeInputDateTimeForDatabase($sDueAt);
    return $sNormalized !== false && $sNormalized != "" ? $sNormalized : null;
}

function getSubscriptionBillingDayFromDueAt($sDueAt) {
    $oDueAt = parseSubscriptionDueAt($sDueAt);
    return $oDueAt ? (int)$oDueAt->format("j") : null;
}

function getSubscriptionBillingDayForSave($sNextDueAt, $sBillingPeriod, $aCurrentSubscription = null) {
    if (!isSubscriptionAnchoredBillingPeriod($sBillingPeriod) || !parseSubscriptionDueAt($sNextDueAt)) {
        return null;
    }
    if (is_array($aCurrentSubscription)
        && formatSubscriptionDueInput($aCurrentSubscription["next_due_at"]) != ""
        && substr(formatSubscriptionDueInput($aCurrentSubscription["next_due_at"]), 0, 10) == substr(formatSubscriptionDueInput($sNextDueAt), 0, 10)
        && isset($aCurrentSubscription["billing_day"])
        && (int)$aCurrentSubscription["billing_day"] >= 1
        && (int)$aCurrentSubscription["billing_day"] <= 31) {
        return (int)$aCurrentSubscription["billing_day"];
    }
    return getSubscriptionBillingDayFromDueAt($sNextDueAt);
}

function getSubscriptionDueAtAfterMonths($sDueAt, $iMonths, $iBillingDay = 0) {
    $oDueAt = parseSubscriptionDueAt($sDueAt);
    if (!$oDueAt) {
        return "";
    }
    $iYear = (int)$oDueAt->format("Y");
    $iMonth = (int)$oDueAt->format("n");
    $iDay = (int)$oDueAt->format("j");
    if ((int)$iBillingDay >= 1 && (int)$iBillingDay <= 31) {
        $iDay = (int)$iBillingDay;
    }
    $iZeroBasedMonth = $iMonth - 1 + (int)$iMonths;
    $iYear += (int)floor($iZeroBasedMonth / 12);
    $iMonth = $iZeroBasedMonth % 12 + 1;
    $oFirstDay = DateTimeImmutable::createFromFormat("!Y-m-d", sprintf("%04d-%02d-01", $iYear, $iMonth));
    if (!$oFirstDay) {
        return "";
    }
    $iMaxDay = (int)$oFirstDay->format("t");
    if ($iDay > $iMaxDay) {
        $iDay = $iMaxDay;
    }
    return sprintf("%04d-%02d-%02d %s", $iYear, $iMonth, $iDay, $oDueAt->format("H:i:s"));
}

function getSubscriptionNextDueAt($sNextDueAt, $sBillingPeriod, $iBillingDay = 0) {
    if ($sBillingPeriod == "monthly") {
        return getSubscriptionDueAtAfterMonths($sNextDueAt, 1, $iBillingDay);
    }
    if ($sBillingPeriod == "quarterly") {
        return getSubscriptionDueAtAfterMonths($sNextDueAt, 3, $iBillingDay);
    }
    if ($sBillingPeriod == "yearly") {
        return getSubscriptionDueAtAfterMonths($sNextDueAt, 12, $iBillingDay);
    }
    $sModifier = getSubscriptionBillingPeriodDateModifier($sBillingPeriod);
    if ($sModifier == "") {
        return "";
    }
    try {
        $oNextDueAt = parseSubscriptionDueAt($sNextDueAt);
        if (!$oNextDueAt) {
            return "";
        }
        return $oNextDueAt->modify($sModifier)->format("Y-m-d H:i:s");
    } catch (Exception $oException) {
        error_log((string)$oException);
        return "";
    }
}

function formatSubscriptionDueInDays($sNextDueAt) {
    $sNextDueAt = trim((string)$sNextDueAt);
    if ($sNextDueAt == "") {
        return "&mdash;";
    }
    try {
        $oToday = new DateTimeImmutable("today");
        $oNextDueAt = parseSubscriptionDueAt($sNextDueAt);
        if (!$oNextDueAt) {
            return "&mdash;";
        }
        $oNextDueDate = $oNextDueAt->setTime(0, 0, 0);
    } catch (Exception $oException) {
        error_log((string)$oException);
        return "&mdash;";
    }
    $iDays = (int)$oToday->diff($oNextDueDate)->format("%r%a");
    return $iDays < 0 ? "&#8722;" . html(abs($iDays)) : html($iDays);
}

function fetchSubscriptionAdminRows($oPdo, $iSubscriptionId = 0) {
    $sSql = "SELECT s.id, s.name, s.finance_type_id, s.amount, s.currency, s.billing_period, s.billing_day, s.next_due_at, s.counterparty, s.note, s.is_active, ft.name AS type_name, ft.type_kind FROM kf_subscriptions s JOIN kf_fin_types ft ON ft.id = s.finance_type_id";
    if ((int)$iSubscriptionId > 0) {
        $sSql .= " WHERE s.id = :id";
    }
    $sSql .= " ORDER BY s.is_active DESC, s.next_due_at IS NULL ASC, s.next_due_at ASC, s.name ASC, s.id ASC";
    if ((int)$iSubscriptionId > 0) {
        $oStatement = $oPdo->prepare($sSql);
        $oStatement->execute(array("id" => (int)$iSubscriptionId));
    } else {
        $oStatement = $oPdo->query($sSql);
    }
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function renderSubscriptionAdminRow($aRow, $blShowActions = true, $blUseEuropeanAmountFormat = false, $sDisplayCurrency = "") {
    global $oPdo, $sEditEmoji, $sDeleteEmoji, $sSubscriptionServedEmoji;

    $sAmountClass = $aRow["amount"] < 0 ? "amount-negative" : ($aRow["amount"] > 0 ? "amount-positive" : "amount-zero");
    $sCurrency = normalizeStoredCurrency($aRow["currency"]);
    $sFormattedAmount = formatCurrencyAmount($oPdo, $aRow["amount"], $sCurrency, $aRow["next_due_at"], $sDisplayCurrency, $blUseEuropeanAmountFormat);
    $sNextDueAtDisplay = formatSubscriptionDueAt($aRow["next_due_at"]);
    $sNextDueAtDisplay = $sNextDueAtDisplay != "" ? str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", html($sNextDueAtDisplay)) : "&mdash;";
    $sServedAction = "";
    if ($blShowActions && trim((string)$aRow["next_due_at"]) != "" && getSubscriptionBillingPeriodDateModifier($aRow["billing_period"]) != "") {
        $sServedAction = "<a class=\"item-action subscription-served-action js-subscription-served\" href=\"#\" data-subscription-id=\"" . (int)$aRow["id"] . "\" title=\"Mark subscription served\" aria-label=\"Mark subscription served\"><span class=\"copy-action-box\">" . $sSubscriptionServedEmoji . "</span></a>";
    }
    $sServedInCell = formatSubscriptionDueInDays($aRow["next_due_at"]) . ($sServedAction != "" ? "&#8288;" . $sServedAction : "");
    $sActionCell = $blShowActions ? "<a href=\"#\" class=\"item-action js-edit-subscription\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-subscription\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "";
    return "      <tr data-subscription-id=\"" . (int)$aRow["id"] . "\" data-name=\"" . html($aRow["name"]) . "\" data-finance-type-id=\"" . (int)$aRow["finance_type_id"] . "\" data-amount=\"" . html(formatAmount(abs($aRow["amount"]), $blUseEuropeanAmountFormat)) . "\" data-currency=\"" . html($sCurrency) . "\" data-billing-period=\"" . html($aRow["billing_period"]) . "\" data-billing-day=\"" . html($aRow["billing_day"]) . "\" data-next-due-at=\"" . html(formatSubscriptionDueInput($aRow["next_due_at"])) . "\" data-counterparty=\"" . html($aRow["counterparty"]) . "\" data-note=\"" . html($aRow["note"]) . "\" data-is-active=\"" . ((int)$aRow["is_active"] == 1 ? "1" : "0") . "\">\n"
        . "        <td class=\"subscription-in-column\">" . $sServedInCell . "</td>\n"
        . "        <td>" . html($aRow["name"]) . "</td>\n"
        . "        <td>" . html($aRow["type_name"]) . "</td>\n"
        . "        <td class=\"numeric " . $sAmountClass . "\">" . html($sFormattedAmount) . "</td>\n"
        . "        <td>" . html(getSubscriptionBillingPeriodLabel($aRow["billing_period"])) . "</td>\n"
        . "        <td class=\"nowrap\">" . $sNextDueAtDisplay . "</td>\n"
        . "        <td>" . htmlValue($aRow["counterparty"], "&mdash;") . "</td>\n"
        . "        <td>" . htmlValue($aRow["note"], "&mdash;") . "</td>\n"
        . "        <td>" . ((int)$aRow["is_active"] == 1 ? "Active" : "Inactive") . "</td>\n"
        . ($blShowActions ? "        <td class=\"admin-action-column\">" . $sActionCell . "</td>\n" : "")
        . "      </tr>\n";
}

function renderSubscriptionAdminRows($aRows, $blShowActions = true, $blUseEuropeanAmountFormat = false, $sDisplayCurrency = "") {
    $sHtml = "";
    foreach ($aRows as $aRow) {
        $sHtml .= renderSubscriptionAdminRow($aRow, $blShowActions, $blUseEuropeanAmountFormat, $sDisplayCurrency);
    }
    if (!$aRows) {
        $sHtml .= "      <tr><td colspan=\"" . ($blShowActions ? 10 : 9) . "\">No subscriptions found.</td></tr>\n";
    }
    return $sHtml;
}

function fetchLatestExchangeRateRows($oPdo) {
    $oStatement = $oPdo->query("SELECT MAX(valid_for) FROM kf_exchange_rates");
    $sValidFor = (string)$oStatement->fetchColumn();
    if ($sValidFor == "") {
        return array();
    }
    $oStatement = $oPdo->prepare("SELECT id, valid_for, `order` AS rate_order, country, currency, currency_code, amount, rate, fetched_at FROM kf_exchange_rates WHERE valid_for = :valid_for ORDER BY `order` ASC, country ASC, currency_code ASC, id ASC");
    $oStatement->execute(array("valid_for" => $sValidFor));
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function formatExchangeRateValue($mRate) {
    $sRate = number_format((float)$mRate, 6, ".", "");
    $sRate = rtrim(rtrim($sRate, "0"), ".");
    return $sRate != "" ? $sRate : "0";
}

function formatExchangeRateDateTime($sDateTime) {
    $iTime = strtotime((string)$sDateTime);
    return $iTime ? date("Y-m-d H:i:s", $iTime) : "";
}

function renderExchangeRateRows($aRows) {
    $sHtml = "";
    foreach ($aRows as $aRow) {
        $sFetchedAtDisplay = formatExchangeRateDateTime($aRow["fetched_at"]);
        $sFetchedAtDisplay = $sFetchedAtDisplay != "" ? str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", html($sFetchedAtDisplay)) : "&mdash;";
        $sHtml .= "      <tr data-exchange-rate-id=\"" . (int)$aRow["id"] . "\">\n"
            . "        <td class=\"nowrap\">" . html(formatDate($aRow["valid_for"])) . "</td>\n"
            . "        <td class=\"numeric\">" . html((int)$aRow["rate_order"]) . "</td>\n"
            . "        <td>" . html($aRow["country"]) . "</td>\n"
            . "        <td>" . html($aRow["currency"]) . "</td>\n"
            . "        <td class=\"nowrap\">" . html($aRow["currency_code"]) . "</td>\n"
            . "        <td class=\"numeric\">" . html((int)$aRow["amount"] . " " . $aRow["currency_code"]) . "</td>\n"
            . "        <td class=\"numeric\">" . html(formatExchangeRateValue($aRow["rate"]) . " CZK") . "</td>\n"
            . "        <td class=\"nowrap\">" . $sFetchedAtDisplay . "</td>\n"
            . "      </tr>\n";
    }
    if (!$aRows) {
        $sHtml .= "      <tr><td colspan=\"8\">No exchange rates found.</td></tr>\n";
    }
    return $sHtml;
}

function sendProcResultAndExit($sResult, $iStatusCode = 200) {
    sendSecurityHeaders();
    http_response_code($iStatusCode);
    header("Content-Type: text/plain; charset=utf-8", true);
    header("Cache-Control: no-store", true);
    echo $sResult . "\n";
    exit;
}

function getExchangeRateRequestedFor() {
    $oTimeZone = new DateTimeZone("Europe/Prague");
    $oNow = new DateTimeImmutable("now", $oTimeZone);
    $oPublishTime = $oNow->setTime(14, 45, 0);
    if ($oNow < $oPublishTime) {
        $oNow = $oNow->modify("-1 day");
    }
    return $oNow->format("Y-m-d");
}

function hasExchangeRatesForDate($oPdo, $sValidFor) {
    $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM kf_exchange_rates WHERE valid_for = :valid_for");
    $oStatement->execute(array("valid_for" => $sValidFor));
    return (int)$oStatement->fetchColumn() > 0;
}

function reserveExchangeRateFetchAttempt($oPdo, $sRequestedFor) {
    $oPdo->beginTransaction();
    try {
        if (hasExchangeRatesForDate($oPdo, $sRequestedFor)) {
            $oPdo->commit();
            return false;
        }
        $oStatement = $oPdo->prepare("INSERT INTO kf_exrates_fetches (requested_for, status) VALUES (:requested_for, 'pending') ON DUPLICATE KEY UPDATE requested_for = requested_for");
        $oStatement->execute(array("requested_for" => $sRequestedFor));
        $oStatement = $oPdo->prepare("SELECT last_attempt_at, succeeded_at FROM kf_exrates_fetches WHERE requested_for = :requested_for FOR UPDATE");
        $oStatement->execute(array("requested_for" => $sRequestedFor));
        $aFetch = $oStatement->fetch(PDO::FETCH_ASSOC);
        if ($aFetch && trim((string)$aFetch["succeeded_at"]) != "") {
            $oPdo->commit();
            return false;
        }
        if ($aFetch && trim((string)$aFetch["last_attempt_at"]) != "") {
            $iLastAttempt = strtotime((string)$aFetch["last_attempt_at"]);
            if ($iLastAttempt !== false && $iLastAttempt > time() - 3600) {
                $oPdo->commit();
                return false;
            }
        }
        $oStatement = $oPdo->prepare("UPDATE kf_exrates_fetches SET status = 'running', last_attempt_at = current_timestamp(6), attempt_count = attempt_count + 1, http_status_code = NULL, error_message = NULL WHERE requested_for = :requested_for");
        $oStatement->execute(array("requested_for" => $sRequestedFor));
        $oPdo->commit();
        return true;
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        throw $oException;
    }
}

function getExchangeRateHttpStatusCode($aHeaders) {
    $iStatusCode = 0;
    foreach ($aHeaders as $sHeader) {
        if (preg_match("#^HTTP/\\S+\\s+([0-9]{3})\\b#i", (string)$sHeader, $aMatches)) {
            $iStatusCode = (int)$aMatches[1];
        }
    }
    return $iStatusCode;
}

function fetchExchangeRateApiResponse($sRequestedFor) {
    $sUrl = "https://api.cnb.cz/cnbapi/exrates/daily?" . http_build_query(array("date" => $sRequestedFor, "lang" => "EN"), "", "&");
    if (function_exists("curl_init")) {
        $oCurl = curl_init($sUrl);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 30);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array("Accept: application/json"));
        curl_setopt($oCurl, CURLOPT_USERAGENT, "eved-kf");
        $sBody = curl_exec($oCurl);
        $iStatusCode = (int)curl_getinfo($oCurl, CURLINFO_RESPONSE_CODE);
        $sError = curl_errno($oCurl) ? curl_error($oCurl) : "";
        if (PHP_VERSION_ID < 80000) {
            curl_close($oCurl);
        }
        return array(
            "success" => $sBody !== false && $iStatusCode >= 200 && $iStatusCode < 300,
            "status_code" => $iStatusCode,
            "body" => $sBody !== false ? (string)$sBody : "",
            "error" => $sError
        );
    }
    $aContext = array(
        "http" => array(
            "method" => "GET",
            "header" => "Accept: application/json\r\nUser-Agent: eved-kf\r\n",
            "timeout" => 30,
            "ignore_errors" => true
        )
    );
    $sBody = @file_get_contents($sUrl, false, stream_context_create($aContext));
    $aHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
    $iStatusCode = getExchangeRateHttpStatusCode($aHeaders);
    $aError = error_get_last();
    return array(
        "success" => $sBody !== false && $iStatusCode >= 200 && $iStatusCode < 300,
        "status_code" => $iStatusCode,
        "body" => $sBody !== false ? (string)$sBody : "",
        "error" => $sBody !== false ? "" : (isset($aError["message"]) ? (string)$aError["message"] : "HTTP request failed.")
    );
}

function normalizeExchangeRateDecimal($mValue) {
    if (is_int($mValue)) {
        $sValue = (string)$mValue;
    } elseif (is_float($mValue)) {
        $sValue = rtrim(rtrim(sprintf("%.10F", $mValue), "0"), ".");
    } else {
        $sValue = trim((string)$mValue);
    }
    $sValue = str_replace(",", ".", $sValue);
    if (!preg_match("/^[0-9]+(?:\\.[0-9]+)?$/", $sValue)) {
        return false;
    }
    return $sValue;
}

function parseExchangeRateApiResponse($sBody, &$sError) {
    $aData = json_decode((string)$sBody, true);
    if (!is_array($aData) || !isset($aData["rates"]) || !is_array($aData["rates"]) || !count($aData["rates"])) {
        $sError = "CNB response does not contain rates.";
        return false;
    }
    $aRows = array();
    foreach ($aData["rates"] as $aRate) {
        if (!is_array($aRate)) {
            $sError = "CNB rate row is invalid.";
            return false;
        }
        $sValidFor = isset($aRate["validFor"]) ? trim((string)$aRate["validFor"]) : "";
        $sCountry = isset($aRate["country"]) ? trim((string)$aRate["country"]) : "";
        $sCurrency = isset($aRate["currency"]) ? trim((string)$aRate["currency"]) : "";
        $sCurrencyCode = isset($aRate["currencyCode"]) ? strtoupper(trim((string)$aRate["currencyCode"])) : "";
        $mAmount = isset($aRate["amount"]) ? $aRate["amount"] : null;
        $mOrder = isset($aRate["order"]) ? $aRate["order"] : null;
        $mRate = isset($aRate["rate"]) ? $aRate["rate"] : null;
        $sRate = normalizeExchangeRateDecimal($mRate);
        if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sValidFor) || $sCountry == "" || $sCurrency == "" || !preg_match("/^[A-Z]{3}$/", $sCurrencyCode)
            || !is_numeric($mAmount) || !is_numeric($mOrder) || $sRate === false) {
            $sError = "CNB rate row contains invalid data.";
            return false;
        }
        $aRows[] = array(
            "valid_for" => $sValidFor,
            "order" => (int)$mOrder,
            "country" => $sCountry,
            "currency" => $sCurrency,
            "currency_code" => $sCurrencyCode,
            "amount" => (int)$mAmount,
            "rate" => $sRate
        );
    }
    return $aRows;
}

function getExchangeRateApiErrorMessage($aResponse) {
    if (trim((string)$aResponse["error"]) != "") {
        return (string)$aResponse["error"];
    }
    $aData = json_decode((string)$aResponse["body"], true);
    if (is_array($aData)) {
        $aParts = array();
        foreach (array("description", "errorCode", "messageId", "happenedAt", "endPoint") as $sKey) {
            if (isset($aData[$sKey]) && trim((string)$aData[$sKey]) != "") {
                $aParts[] = $sKey . ": " . trim((string)$aData[$sKey]);
            }
        }
        if ($aParts) {
            return implode("; ", $aParts);
        }
    }
    return "CNB API returned HTTP " . (int)$aResponse["status_code"] . ".";
}

function recordExchangeRateFetchError($oPdo, $sRequestedFor, $iHttpStatusCode, $sErrorMessage) {
    $sErrorMessage = substr((string)$sErrorMessage, 0, 1000);
    $oStatement = $oPdo->prepare("UPDATE kf_exrates_fetches SET status = 'error', http_status_code = :http_status_code, error_message = :error_message WHERE requested_for = :requested_for");
    $oStatement->execute(array(
        "http_status_code" => $iHttpStatusCode > 0 ? $iHttpStatusCode : null,
        "error_message" => $sErrorMessage,
        "requested_for" => $sRequestedFor
    ));
}

function saveExchangeRates($oPdo, $sRequestedFor, $aRows, $iHttpStatusCode) {
    $sResponseValidFor = (string)$aRows[0]["valid_for"];
    $oPdo->beginTransaction();
    try {
        $oStatement = $oPdo->prepare("SELECT id FROM kf_exrates_fetches WHERE requested_for = :requested_for FOR UPDATE");
        $oStatement->execute(array("requested_for" => $sRequestedFor));
        $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM kf_exchange_rates WHERE valid_for = :valid_for");
        $oStatement->execute(array("valid_for" => $sResponseValidFor));
        $iExistingRows = (int)$oStatement->fetchColumn();
        if ($iExistingRows < 1) {
            $oStatement = $oPdo->prepare("INSERT INTO kf_exchange_rates (valid_for, `order`, country, currency, currency_code, amount, rate, fetched_at) VALUES (:valid_for, :rate_order, :country, :currency, :currency_code, :amount, :rate, current_timestamp(6))");
            foreach ($aRows as $aRow) {
                $oStatement->execute(array(
                    "valid_for" => $aRow["valid_for"],
                    "rate_order" => (int)$aRow["order"],
                    "country" => $aRow["country"],
                    "currency" => $aRow["currency"],
                    "currency_code" => $aRow["currency_code"],
                    "amount" => (int)$aRow["amount"],
                    "rate" => $aRow["rate"]
                ));
            }
            $iExistingRows = count($aRows);
        }
        $oStatement = $oPdo->prepare("UPDATE kf_exrates_fetches SET status = 'success', succeeded_at = current_timestamp(6), response_valid_for = :response_valid_for, http_status_code = :http_status_code, rates_count = :rates_count, error_message = NULL WHERE requested_for = :requested_for");
        $oStatement->execute(array(
            "response_valid_for" => $sResponseValidFor,
            "http_status_code" => $iHttpStatusCode > 0 ? $iHttpStatusCode : null,
            "rates_count" => $iExistingRows,
            "requested_for" => $sRequestedFor
        ));
        $oPdo->commit();
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        throw $oException;
    }
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

function normalizeEmailContactValue($sValue) {
    $sText = strtolower(trim((string)$sValue));
    if ($sText == "") {
        return "";
    }
    return filter_var($sText, FILTER_VALIDATE_EMAIL) ? $sText : false;
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
