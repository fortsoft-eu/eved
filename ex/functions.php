<?php

function formatTimestampTooltipValue($mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return "";
    }
    if (preg_match("/^([0-9]{4}-[0-9]{2}-[0-9]{2})[ T]([0-9]{2}:[0-9]{2}:[0-9]{2})/", $sValue, $aMatches)) {
        return $aMatches[1] . " " . $aMatches[2];
    }
    return str_replace("T", " ", substr($sValue, 0, 19));
}

function timestampTooltipText($aRow) {
    if (!is_array($aRow) || !array_key_exists("created_at", $aRow) || !array_key_exists("updated_at", $aRow)) {
        return "";
    }
    $sCreated = formatTimestampTooltipValue($aRow["created_at"]);
    $sUpdated = formatTimestampTooltipValue($aRow["updated_at"]);
    if ($sCreated == $sUpdated) {
        return "Created: " . $sCreated;
    }
    return "Created: " . $sCreated . "\n"
        . "Updated: " . $sUpdated;
}

function renderTimestampTooltipDataAttribute($aRow) {
    $sText = timestampTooltipText($aRow);
    if ($sText == "") {
        return "";
    }
    return " data-timestamp-tooltip=\"" . str_replace("\n", "&#10;", html($sText)) . "\"";
}

function renderEmojiData() {
    global $sEditEmoji, $sDeleteEmoji, $sAddEmoji, $sHiddenInactiveEmoji, $sPortalEmoji, $sEmptyValueEmoji;
    global $sThrobberEmoji, $sFilterFocusEmoji, $sCopyEmoji, $sCopySuccessEmoji, $sCopyFailureEmoji;
    global $sPrimaryEmoji, $sInactiveEmoji, $sMergeEmoji, $sMoveUpEmoji, $sMoveDownEmoji;
    global $sBirthdayServedEmoji, $sCommunicationServedEmoji, $sContactEmailEmoji, $sContactLandlineEmoji;
    global $sContactCellEmoji, $sContactFaxEmoji, $sContactPagerEmoji, $sContactWebEmoji;
    global $sContactTelegramEmoji, $sContactMessageEmoji, $sContactYouTubeEmoji;

    $aValues = array(
        "edit" => $sEditEmoji,
        "delete" => $sDeleteEmoji,
        "add" => $sAddEmoji,
        "hidden-inactive" => $sHiddenInactiveEmoji,
        "portal" => $sPortalEmoji,
        "empty-value" => $sEmptyValueEmoji,
        "throbber" => $sThrobberEmoji,
        "filter-focus" => $sFilterFocusEmoji,
        "copy" => $sCopyEmoji,
        "copy-success" => $sCopySuccessEmoji,
        "copy-failure" => $sCopyFailureEmoji,
        "primary" => $sPrimaryEmoji,
        "inactive" => $sInactiveEmoji,
        "merge" => $sMergeEmoji,
        "move-up" => $sMoveUpEmoji,
        "move-down" => $sMoveDownEmoji,
        "birthday-served" => $sBirthdayServedEmoji,
        "communication-served" => $sCommunicationServedEmoji,
        "contact-email" => $sContactEmailEmoji,
        "contact-landline" => $sContactLandlineEmoji,
        "contact-cell" => $sContactCellEmoji,
        "contact-fax" => $sContactFaxEmoji,
        "contact-pager" => $sContactPagerEmoji,
        "contact-web" => $sContactWebEmoji,
        "contact-telegram" => $sContactTelegramEmoji,
        "contact-message" => $sContactMessageEmoji,
        "contact-youtube" => $sContactYouTubeEmoji
    );
    $sHtml = "  <span id=\"emoji-data\" hidden";
    foreach ($aValues as $sKey => $sValue) {
        $sHtml .= " data-" . $sKey . "=\"" . html(html_entity_decode((string)$sValue, ENT_QUOTES | ENT_HTML5, "UTF-8")) . "\"";
    }
    return $sHtml . "></span>\n";
}


function renderPageThrobber() {
    global $sThrobberEmoji;

    return "  <div class=\"render-throbber js-render-throbber\" role=\"status\" aria-live=\"polite\">\n"
        . "    <div class=\"render-throbber-box\">\n"
        . "      <span class=\"render-throbber-icon\" aria-hidden=\"true\">" . $sThrobberEmoji . "</span>\n"
        . "    </div>\n"
        . "  </div>\n";
}

function getRenderThrobberHtmlAttributes($blUseRenderThrobberLock) {
    $sAttributes = "";
    $sUserAgent = isset($_SERVER["HTTP_USER_AGENT"]) ? (string)$_SERVER["HTTP_USER_AGENT"] : "";
    if ($blUseRenderThrobberLock) {
        $blIsThrobberLockTarget = isThrobberLockTarget($sUserAgent);
        $sAttributes = " data-render-throbber-lock-target=\"" . html($blIsThrobberLockTarget ? "html" : "body") . "\" data-render-throbber-lock-active=\"1\"";
        if ($blIsThrobberLockTarget) {
            $sAttributes .= " data-render-throbber-zoom-lock=\"1\" data-render-throbber-viewport-content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no\"";
        }
    }
    return $sAttributes;
}


function renderSubjectCellCopyAction($aValues, $blShowSingleItem = false) {
    $aCopyValues = array();
    foreach ($aValues as $mValue) {
        $sValue = trim((string)$mValue);
        if ($sValue != "") {
            $aCopyValues[] = $sValue;
        }
    }
    if (!$aCopyValues || (!$blShowSingleItem && count($aCopyValues) < 2)) {
        return "";
    }
    return renderCopyAction(implode("\n", $aCopyValues), "Copy items");
}

function htmlMultiline($mValue) {
    global $sEmptyValueEmoji;

    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return $sEmptyValueEmoji;
    }
    return str_replace("\n", "<br>", html($sValue));
}

function getDefaultContactTypeRows() {
    return array(
        array("contact_type" => "landline", "name" => "Landline", "is_active" => 1, "order" => 10),
        array("contact_type" => "cell", "name" => "Cell", "is_active" => 1, "order" => 20),
        array("contact_type" => "fax", "name" => "Fax", "is_active" => 1, "order" => 30),
        array("contact_type" => "pager", "name" => "Pager", "is_active" => 1, "order" => 40),
        array("contact_type" => "email", "name" => "E-mail", "is_active" => 1, "order" => 50),
        array("contact_type" => "jabber", "name" => "Jabber", "is_active" => 1, "order" => 60),
        array("contact_type" => "icq", "name" => "ICQ", "is_active" => 1, "order" => 70),
        array("contact_type" => "skype", "name" => "Skype", "is_active" => 1, "order" => 80),
        array("contact_type" => "web", "name" => "Web", "is_active" => 1, "order" => 90),
        array("contact_type" => "signal", "name" => "Signal", "is_active" => 1, "order" => 100),
        array("contact_type" => "whatsapp", "name" => "WhatsApp", "is_active" => 1, "order" => 110),
        array("contact_type" => "telegram", "name" => "Telegram", "is_active" => 1, "order" => 120),
        array("contact_type" => "messenger", "name" => "Messenger", "is_active" => 1, "order" => 130),
        array("contact_type" => "viber", "name" => "Viber", "is_active" => 1, "order" => 140),
        array("contact_type" => "discord", "name" => "Discord", "is_active" => 1, "order" => 150),
        array("contact_type" => "matrix", "name" => "Matrix", "is_active" => 1, "order" => 160),
        array("contact_type" => "session", "name" => "Session", "is_active" => 1, "order" => 170),
        array("contact_type" => "twitter", "name" => "Twitter", "is_active" => 1, "order" => 180),
        array("contact_type" => "mastodon", "name" => "Mastodon", "is_active" => 1, "order" => 190),
        array("contact_type" => "bluesky", "name" => "Bluesky", "is_active" => 1, "order" => 200),
        array("contact_type" => "threads", "name" => "Threads", "is_active" => 1, "order" => 210),
        array("contact_type" => "facebook", "name" => "Facebook", "is_active" => 1, "order" => 220),
        array("contact_type" => "instagram", "name" => "Instagram", "is_active" => 1, "order" => 230),
        array("contact_type" => "tiktok", "name" => "TikTok", "is_active" => 1, "order" => 240),
        array("contact_type" => "linkedin", "name" => "LinkedIn", "is_active" => 1, "order" => 250),
        array("contact_type" => "github", "name" => "GitHub", "is_active" => 1, "order" => 260),
        array("contact_type" => "gitlab", "name" => "GitLab", "is_active" => 1, "order" => 270),
        array("contact_type" => "bitbucket", "name" => "Bitbucket", "is_active" => 1, "order" => 280),
        array("contact_type" => "stackoverflow", "name" => "Stack Overflow", "is_active" => 1, "order" => 290),
        array("contact_type" => "deviantart", "name" => "DeviantArt", "is_active" => 1, "order" => 300),
        array("contact_type" => "furaffinity", "name" => "Fur Affinity", "is_active" => 1, "order" => 310),
        array("contact_type" => "furryamino", "name" => "Furry Amino", "is_active" => 1, "order" => 320),
        array("contact_type" => "sofurry", "name" => "SoFurry", "is_active" => 1, "order" => 330),
        array("contact_type" => "wikifur", "name" => "WikiFur", "is_active" => 1, "order" => 335),
        array("contact_type" => "artstation", "name" => "ArtStation", "is_active" => 1, "order" => 340),
        array("contact_type" => "behance", "name" => "Behance", "is_active" => 1, "order" => 350),
        array("contact_type" => "dribbble", "name" => "Dribbble", "is_active" => 1, "order" => 360),
        array("contact_type" => "youtube", "name" => "YouTube", "is_active" => 1, "order" => 370),
        array("contact_type" => "twitch", "name" => "Twitch", "is_active" => 1, "order" => 380),
        array("contact_type" => "kick", "name" => "Kick", "is_active" => 1, "order" => 390),
        array("contact_type" => "vimeo", "name" => "Vimeo", "is_active" => 1, "order" => 400),
        array("contact_type" => "reddit", "name" => "Reddit", "is_active" => 1, "order" => 410),
        array("contact_type" => "lemmy", "name" => "Lemmy", "is_active" => 1, "order" => 420),
        array("contact_type" => "steam", "name" => "Steam", "is_active" => 1, "order" => 430),
        array("contact_type" => "xbox", "name" => "Xbox", "is_active" => 1, "order" => 440),
        array("contact_type" => "playstation", "name" => "PlayStation", "is_active" => 1, "order" => 450),
        array("contact_type" => "nintendo", "name" => "Nintendo", "is_active" => 1, "order" => 460),
        array("contact_type" => "npm", "name" => "npm", "is_active" => 1, "order" => 470),
        array("contact_type" => "pypi", "name" => "PyPI", "is_active" => 1, "order" => 480),
        array("contact_type" => "docker", "name" => "Docker", "is_active" => 1, "order" => 490),
        array("contact_type" => "codeberg", "name" => "Codeberg", "is_active" => 1, "order" => 500),
        array("contact_type" => "paypal", "name" => "PayPal", "is_active" => 1, "order" => 510),
        array("contact_type" => "revolut", "name" => "Revolut", "is_active" => 1, "order" => 520),
        array("contact_type" => "wise", "name" => "Wise", "is_active" => 1, "order" => 530),
        array("contact_type" => "bankaccount", "name" => "Bank Account", "is_active" => 1, "order" => 540),
        array("contact_type" => "orcid", "name" => "ORCID", "is_active" => 1, "order" => 550),
        array("contact_type" => "goodreads", "name" => "Goodreads", "is_active" => 1, "order" => 560),
        array("contact_type" => "lastfm", "name" => "Last.fm", "is_active" => 1, "order" => 570),
        array("contact_type" => "signaly", "name" => "Signaly", "is_active" => 1, "order" => 580),
        array("contact_type" => "other", "name" => "Other", "is_active" => 1, "order" => 990)
    );
}

function fetchContactTypes($oPdo = null, $blActiveOnly = true) {
    static $aCache = array();

    $sCacheKey = ($blActiveOnly ? "active" : "all") . ":db";
    if (isset($aCache[$sCacheKey])) {
        return $aCache[$sCacheKey];
    }
    $aRows = array();
    if ($oPdo) {
        $sSql = "SELECT id, contact_type, name, is_active, `order` FROM ex_contact_types";
        if ($blActiveOnly) {
            $sSql .= " WHERE is_active = 1";
        }
        $sSql .= " ORDER BY `order` ASC, id ASC";
        $oStatement = $oPdo->query($sSql);
        while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
            $aRows[] = $aRow;
        }
    }
    if (!$aRows) {
        $iDefaultContactTypeId = 1;
        foreach (getDefaultContactTypeRows() as $aRow) {
            if (!$blActiveOnly || (int)$aRow["is_active"] == 1) {
                if (!isset($aRow["id"])) {
                    $aRow["id"] = $iDefaultContactTypeId;
                }
                $aRows[] = $aRow;
            }
            $iDefaultContactTypeId++;
        }
    }
    $aCache[$sCacheKey] = $aRows;
    return $aRows;
}

function getContactTypeById($iContactTypeId, $oPdo = null, $blActiveOnly = true) {
    $iContactTypeId = (int)$iContactTypeId;
    foreach (fetchContactTypes($oPdo, $blActiveOnly) as $aType) {
        if ((int)$aType["id"] == $iContactTypeId) {
            return $aType;
        }
    }
    return null;
}

function getNewContactDefaultTypeId($aContactTypes) {
    $iContactTypeId = 0;
    if (isset($_SESSION["ex_new_contact_defaults"]) && is_array($_SESSION["ex_new_contact_defaults"]) && isset($_SESSION["ex_new_contact_defaults"]["contact_type_id"])) {
        $iContactTypeId = (int)$_SESSION["ex_new_contact_defaults"]["contact_type_id"];
    }
    if ($iContactTypeId < 1) {
        return 0;
    }
    foreach ($aContactTypes as $aContactType) {
        if ((int)$aContactType["id"] == $iContactTypeId && (int)$aContactType["is_active"] == 1) {
            return $iContactTypeId;
        }
    }
    return 0;
}

function saveNewContactDefaultTypeId($iContactTypeId) {
    if ((int)$iContactTypeId < 1) {
        return;
    }
    if (!isset($_SESSION["ex_new_contact_defaults"]) || !is_array($_SESSION["ex_new_contact_defaults"])) {
        $_SESSION["ex_new_contact_defaults"] = array();
    }
    $_SESSION["ex_new_contact_defaults"]["contact_type_id"] = (int)$iContactTypeId;
}

function contactTypeLabel($sType, $oPdo = null) {
    $sType = (string)$sType;
    foreach (fetchContactTypes($oPdo, false) as $aType) {
        if ((string)$aType["contact_type"] == $sType) {
            return (string)$aType["name"];
        }
    }
    if ($sType == "phone") {
        return "Landline";
    }
    if ($sType == "mobile") {
        return "Cell";
    }
    return "Other";
}

function originalContactTypeMap() {
    static $aMap = null;

    if ($aMap !== null) {
        return $aMap;
    }
    $aMap = array();
    foreach (getDefaultContactTypeRows() as $aType) {
        $aMap[(string)$aType["contact_type"]] = true;
    }
    return $aMap;
}

function isOriginalContactType($sContactType) {
    $aMap = originalContactTypeMap();
    return isset($aMap[(string)$sContactType]);
}

function buildContactTypeKeyBase($sName) {
    $sKey = trim((string)$sName);
    if (function_exists("iconv")) {
        $sConverted = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $sKey);
        if ($sConverted !== false) {
            $sKey = $sConverted;
        }
    }
    $sKey = strtolower($sKey);
    $sKey = preg_replace("/[^a-z0-9]+/", "", $sKey);
    return $sKey != "" ? $sKey : "type";
}

function generateContactTypeKey($oPdo, $sName, $iExcludeId = 0) {
    $sBaseKey = buildContactTypeKeyBase($sName);
    $sContactType = $sBaseKey;
    $iSuffix = 2;
    while (true) {
        $sSql = "SELECT id FROM ex_contact_types WHERE contact_type = :contact_type";
        $aParams = array("contact_type" => $sContactType);
        if ($iExcludeId > 0) {
            $sSql .= " AND id <> :id";
            $aParams["id"] = $iExcludeId;
        }
        $oStatement = $oPdo->prepare($sSql);
        $oStatement->execute($aParams);
        if (!$oStatement->fetch(PDO::FETCH_ASSOC)) {
            return $sContactType;
        }
        $sContactType = $sBaseKey . $iSuffix;
        $iSuffix++;
    }
}

function fetchContactTypeAdminRows($oPdo, $iContactTypeId = 0) {
    $sSql = "SELECT ct.id, ct.contact_type, ct.name, ct.is_active, ct.`order`, COUNT(c.id) AS contact_count FROM ex_contact_types AS ct LEFT JOIN ex_contacts AS c ON c.contact_type_id = ct.id";
    if ($iContactTypeId > 0) {
        $sSql .= " WHERE ct.id = :id";
    }
    $sSql .= " GROUP BY ct.id, ct.contact_type, ct.name, ct.is_active, ct.`order`";
    if ($iContactTypeId < 1) {
        $sSql .= " ORDER BY ct.`order` ASC, ct.id ASC";
    }
    $oStatement = $oPdo->prepare($sSql);
    if ($iContactTypeId > 0) {
        $oStatement->execute(array("id" => $iContactTypeId));
    } else {
        $oStatement->execute();
    }
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function renderContactTypeAdminRow($aContactType, $blShowActions = true) {
    global $sDeleteEmoji, $sEditEmoji, $sMergeEmoji, $sMoveUpEmoji, $sMoveDownEmoji;

    $blIsActive = (int)$aContactType["is_active"] == 1;
    return "      <tr data-contact-type-id=\"" . html($aContactType["id"]) . "\" data-contact-type-name=\"" . html($aContactType["name"]) . "\" data-contact-type-active=\"" . ($blIsActive ? "1" : "0") . "\" data-contact-type-order=\"" . html($aContactType["order"]) . "\">\n"
        . "        <td>" . html($aContactType["name"]) . "</td>\n"
        . "        <td>" . html($aContactType["contact_count"]) . "</td>\n"
        . "        <td>" . ($blIsActive ? "Yes" : "No") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"item-action js-move-contact-type-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-move-contact-type-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"item-action js-merge-contact-type\" title=\"Merge into this contact type\" aria-label=\"Merge into this contact type\">" . $sMergeEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"item-action js-edit-contact-type\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-contact-type\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "") . "</td>\n"
        . "      </tr>\n";
}

function normalizeContactTypeOrder($oPdo) {
    $oStatement = $oPdo->query("SELECT id FROM ex_contact_types ORDER BY `order` ASC, id ASC FOR UPDATE");
    $aIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
    $iOrder = 10;
    $oUpdateStatement = $oPdo->prepare("UPDATE ex_contact_types SET `order` = :order WHERE id = :id");
    foreach ($aIds as $iContactTypeId) {
        $oUpdateStatement->execute(array("order" => $iOrder, "id" => (int)$iContactTypeId));
        $iOrder += 10;
    }
}

function moveContactTypeOrder($oPdo, $iContactTypeId, $sDirection) {
    normalizeContactTypeOrder($oPdo);
    $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_contact_types WHERE id = :id FOR UPDATE");
    $oStatement->execute(array("id" => $iContactTypeId));
    $aCurrent = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aCurrent) {
        throw new Exception("Contact type was not found.");
    }
    if ($sDirection == "up") {
        $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_contact_types WHERE `order` < :order ORDER BY `order` DESC, id DESC LIMIT 1 FOR UPDATE");
    } else {
        $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_contact_types WHERE `order` > :order ORDER BY `order` ASC, id ASC LIMIT 1 FOR UPDATE");
    }
    $oStatement->execute(array("order" => (int)$aCurrent["order"]));
    $aOther = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aOther) {
        return;
    }
    $oStatement = $oPdo->prepare("UPDATE ex_contact_types SET `order` = :order WHERE id = :id");
    $oStatement->execute(array("order" => (int)$aOther["order"], "id" => (int)$aCurrent["id"]));
    $oStatement->execute(array("order" => (int)$aCurrent["order"], "id" => (int)$aOther["id"]));
}

function mergeContactTypeContacts($oPdo, $iTargetContactTypeId, $iSourceContactTypeId) {
    $oStatement = $oPdo->prepare("SELECT c.id, c.contact_value, tc.id AS target_contact_id FROM ex_contacts AS c LEFT JOIN ex_contacts AS tc ON tc.contact_type_id = :target_contact_type_id AND tc.contact_value = c.contact_value WHERE c.contact_type_id = :source_contact_type_id FOR UPDATE");
    $oStatement->execute(array(
        "target_contact_type_id" => $iTargetContactTypeId,
        "source_contact_type_id" => $iSourceContactTypeId
    ));
    $aContacts = $oStatement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($aContacts as $aContact) {
        $iSourceContactId = (int)$aContact["id"];
        $iTargetContactId = (int)$aContact["target_contact_id"];
        if ($iTargetContactId > 0) {
            $oSubjectStatement = $oPdo->prepare("SELECT id, subject_id FROM ex_subject_contacts WHERE contact_id = :contact_id FOR UPDATE");
            $oSubjectStatement->execute(array("contact_id" => $iSourceContactId));
            $aSubjectContacts = $oSubjectStatement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($aSubjectContacts as $aSubjectContact) {
                $oDuplicateStatement = $oPdo->prepare("SELECT id FROM ex_subject_contacts WHERE subject_id = :subject_id AND contact_id = :contact_id");
                $oDuplicateStatement->execute(array(
                    "subject_id" => (int)$aSubjectContact["subject_id"],
                    "contact_id" => $iTargetContactId
                ));
                if ($oDuplicateStatement->fetch(PDO::FETCH_ASSOC)) {
                    $oDeleteStatement = $oPdo->prepare("DELETE FROM ex_subject_contacts WHERE id = :id");
                    $oDeleteStatement->execute(array("id" => (int)$aSubjectContact["id"]));
                } else {
                    $oUpdateStatement = $oPdo->prepare("UPDATE ex_subject_contacts SET contact_id = :target_contact_id WHERE id = :id");
                    $oUpdateStatement->execute(array(
                        "target_contact_id" => $iTargetContactId,
                        "id" => (int)$aSubjectContact["id"]
                    ));
                }
            }
            $oDeleteStatement = $oPdo->prepare("DELETE FROM ex_contacts WHERE id = :id");
            $oDeleteStatement->execute(array("id" => $iSourceContactId));
        } else {
            $oUpdateStatement = $oPdo->prepare("UPDATE ex_contacts SET contact_type_id = :target_contact_type_id WHERE id = :id");
            $oUpdateStatement->execute(array(
                "target_contact_type_id" => $iTargetContactTypeId,
                "id" => $iSourceContactId
            ));
        }
    }
}

function normalizeYouTubeContactValue($sValue, $blRejectNonYouTubeLink = false) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sPath = "";
    $sHost = "";
    $blLooksLikeUrl = false;
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    $blLooksLikeUrl = preg_match("#^https?://#i", $sText) || preg_match("#^www\\.#i", $sText) || preg_match("#^(?:youtube\\.com|www\\.youtube\\.com)(?:[/:?\\#].*)?$#i", $sText) || preg_match("#^[A-Za-z0-9.-]+\\.[A-Za-z]{2,}[/:?\\#].*$#", $sText);
    if ($blLooksLikeUrl) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower((string)$aParts["host"]) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost != "youtube.com" && $sHost != "www.youtube.com") {
            if ($blRejectNonYouTubeLink) {
                return false;
            }
            return "https://www.youtube.com/@" . rawurlencode(preg_replace("/^@+/", "", $sText));
        }
        if (preg_match("#^(user|channel)/([^/]+)$#i", $sPath, $aMatches)) {
            return "https://www.youtube.com/" . strtolower($aMatches[1]) . "/" . rawurlencode(rawurldecode($aMatches[2]));
        }
        if (preg_match("#^@([^/]+)$#", $sPath, $aMatches)) {
            return "https://www.youtube.com/@" . rawurlencode(preg_replace("/^@+/", "", rawurldecode($aMatches[1])));
        }
        if ($sPath != "") {
            return "https://www.youtube.com/" . $sPath;
        }
        return $blRejectNonYouTubeLink ? false : "https://www.youtube.com/";
    }
    if (preg_match("#^(user|channel)/([^/?\\#]+)/*$#i", $sText, $aMatches)) {
        return "https://www.youtube.com/" . strtolower($aMatches[1]) . "/" . rawurlencode(rawurldecode($aMatches[2]));
    }
    if (preg_match("#^@([^/?\\#]+)/*$#", $sText, $aMatches)) {
        return "https://www.youtube.com/@" . rawurlencode(rawurldecode($aMatches[1]));
    }
    if ($blRejectNonYouTubeLink && preg_match("#[/:?\\#]#", $sText)) {
        return false;
    }
    return "https://www.youtube.com/@" . rawurlencode(preg_replace("/^@+/", "", $sText));
}

function telegramContactHost($sHost) {
    $sHost = strtolower(preg_replace("/^www\\./", "", (string)$sHost));
    if ($sHost == "t.me" || $sHost == "telegram.me" || $sHost == "telegram.dog") {
        return $sHost;
    }
    return false;
}

function telegramInviteToken($sValue, $blRequireMarker = false) {
    $sText = rawurldecode((string)$sValue);
    $blMarked = false;
    if (substr($sText, 0, 1) == "+") {
        $sText = substr($sText, 1);
        $blMarked = true;
    } elseif (substr($sText, 0, 1) == " ") {
        $sText = substr($sText, 1);
        $blMarked = true;
    }
    $sText = trim($sText);
    if ($blRequireMarker && !$blMarked) {
        return false;
    }
    if (!preg_match("/^[A-Za-z0-9_-]{6,128}$/", $sText)) {
        return false;
    }
    return $sText;
}

function telegramSlug($sValue) {
    $sText = trim(rawurldecode((string)$sValue));
    if (!preg_match("/^[A-Za-z0-9_]{1,128}$/", $sText)) {
        return false;
    }
    return $sText;
}

function normalizeTelegramContactPath($sHost, $sPath) {
    $sHost = telegramContactHost($sHost);
    $sPath = trim((string)$sPath, "/");
    $aSegments = !$sPath ? array() : explode("/", $sPath);
    $sHandle = "";
    $sKind = "";
    $sToken = "";
    if ($sHost === false || count($aSegments) < 1 || count($aSegments) > 2) {
        return false;
    }
    if (count($aSegments) == 1) {
        $sToken = telegramInviteToken($aSegments[0], true);
        if ($sToken !== false) {
            return "https://" . $sHost . "/joinchat/" . rawurlencode($sToken);
        }
        $sHandle = preg_replace("/^@+/", "", rawurldecode($aSegments[0]));
        if (!preg_match("/^[A-Za-z0-9_]{5,32}$/", $sHandle)) {
            return false;
        }
        return "https://" . $sHost . "/" . rawurlencode($sHandle);
    }
    $sKind = strtolower(rawurldecode($aSegments[0]));
    if ($sKind == "joinchat") {
        $sToken = telegramInviteToken($aSegments[1]);
        return $sToken !== false ? "https://" . $sHost . "/joinchat/" . rawurlencode($sToken) : false;
    }
    if ($sKind == "addstickers" || $sKind == "setlanguage") {
        $sToken = telegramSlug($aSegments[1]);
        return $sToken !== false ? "https://" . $sHost . "/" . $sKind . "/" . rawurlencode($sToken) : false;
    }
    return false;
}

function normalizeTelegramContactValue($sValue) {
    $sRawText = (string)$sValue;
    $sText = trim($sRawText);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sToken = "";
    $sHandle = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?(?:t\\.me|telegram\\.me|telegram\\.dog)(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? telegramContactHost($aParts["host"]) : false;
        $sPath = isset($aParts["path"]) ? (string)$aParts["path"] : "";
        return $sHost !== false ? normalizeTelegramContactPath($sHost, $sPath) : false;
    }
    if (preg_match("#^(joinchat|addstickers|setlanguage)/(.+)$#i", $sText, $aMatches)) {
        return normalizeTelegramContactPath("t.me", $aMatches[1] . "/" . $aMatches[2]);
    }
    if (substr($sRawText, 0, 1) == " " || substr($sText, 0, 1) == "+" || preg_match("/^%20/i", $sText)) {
        $sToken = telegramInviteToken(substr($sRawText, 0, 1) == " " ? $sRawText : $sText, true);
        return $sToken !== false ? "https://t.me/joinchat/" . rawurlencode($sToken) : false;
    }
    $sHandle = preg_replace("/^@+/", "", $sText);
    if (!preg_match("/^[A-Za-z0-9_]{5,32}$/", $sHandle)) {
        return false;
    }
    return "https://t.me/" . rawurlencode($sHandle);
}

function normalizeIcqContactValue($sValue) {
    $sText = trim((string)$sValue);
    $sDigits = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("/^[0-9]{5,9}$/", $sText)) {
        $sDigits = $sText;
    } elseif (preg_match("/^[0-9]{1,3}(?:-[0-9]{3}){1,2}$/", $sText)) {
        $sDigits = str_replace("-", "", $sText);
    } else {
        return false;
    }
    if (strlen($sDigits) < 5 || strlen($sDigits) > 9) {
        return false;
    }
    if (strlen($sDigits) < 7) {
        $sText = substr($sDigits, 0, -3) . "-" . substr($sDigits, -3);
    } else {
        $sText = substr($sDigits, 0, -6) . "-" . substr($sDigits, -6, 3) . "-" . substr($sDigits, -3);
    }
    return strpos((string)$sValue, "-") === false || trim((string)$sValue) == $sText ? $sText : false;
}

function normalizeEmailContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText == "") {
        return "";
    }
    return filter_var($sText, FILTER_VALIDATE_EMAIL) !== false ? $sText : false;
}

function normalizeSkypeContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText == "") {
        return "";
    }
    if (preg_match("/^[A-Za-z][A-Za-z0-9._,-]{5,31}$/", $sText)) {
        return $sText;
    }
    if (preg_match("/^live:[A-Za-z0-9._-]{1,64}$/i", $sText)) {
        return $sText;
    }
    return false;
}

function normalizeContactInputForStorage($sContactType, $sContactValue) {
    $mKnownValue = null;
    $sContactType = contactTypeKey($sContactType);
    if (isPhoneContactType($sContactType)) {
        return normalizePhoneContactValue($sContactValue);
    }
    if ((string)$sContactType == "youtube") {
        return normalizeYouTubeContactValue($sContactValue, true);
    }
    if ((string)$sContactType == "telegram") {
        return normalizeTelegramContactValue($sContactValue);
    }
    if ((string)$sContactType == "email") {
        return normalizeEmailContactValue($sContactValue);
    }
    if ((string)$sContactType == "icq") {
        return normalizeIcqContactValue($sContactValue);
    }
    if ((string)$sContactType == "skype") {
        return normalizeSkypeContactValue($sContactValue);
    }
    $mKnownValue = normalizeKnownContactValue($sContactType, $sContactValue);
    if ($mKnownValue !== null) {
        return $mKnownValue;
    }
    return trim((string)$sContactValue);
}

function contactCanonicalValue($sContactType, $sContactValue) {
    $mKnownValue = null;
    $sContactType = contactTypeKey($sContactType);
    if (isPhoneContactType($sContactType)) {
        $mKnownValue = normalizePhoneContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "youtube") {
        $mKnownValue = normalizeYouTubeContactValue($sContactValue, true);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "telegram") {
        $mKnownValue = normalizeTelegramContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "email") {
        $mKnownValue = normalizeEmailContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "icq") {
        $mKnownValue = normalizeIcqContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "skype") {
        $mKnownValue = normalizeSkypeContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    $mKnownValue = normalizeKnownContactValue($sContactType, $sContactValue);
    if ($mKnownValue !== null) {
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    return (string)$sContactValue;
}

function contactInputErrorMessage($sContactType) {
    $sContactType = contactTypeKey($sContactType);
    if (isPhoneContactType($sContactType)) {
        return "Phone number must be a valid international number.";
    }
    if ((string)$sContactType == "youtube") {
        return "YouTube contact must be a YouTube link or handle.";
    }
    if ((string)$sContactType == "telegram") {
        return "Telegram contact must be a valid Telegram link, handle, invite link, sticker set or language link.";
    }
    if ((string)$sContactType == "email") {
        return "E-mail address is invalid.";
    }
    if ((string)$sContactType == "icq") {
        return "ICQ must have 5 to 9 digits, either without hyphens or grouped from the right.";
    }
    if ((string)$sContactType == "skype") {
        return "Skype name must start with a letter and have 6 to 32 valid characters, or use a valid live: name.";
    }
    if (normalizeKnownContactValue($sContactType, "") !== null) {
        return "Contact value has invalid format for this contact type.";
    }
    return "Contact value is invalid.";
}

function contactValueIsInvalid($sType, $sValue) {
    $mKnownValue = null;
    $sType = contactTypeKey($sType);
    if (trim((string)$sValue) == "") {
        return false;
    }
    if (isPhoneContactType($sType)) {
        return normalizePhoneContactValue($sValue) === false;
    }
    if ((string)$sType == "youtube") {
        return normalizeYouTubeContactValue($sValue, true) === false;
    }
    if ((string)$sType == "telegram") {
        return normalizeTelegramContactValue($sValue) === false;
    }
    if ((string)$sType == "email") {
        return normalizeEmailContactValue($sValue) === false;
    }
    if ((string)$sType == "icq") {
        return normalizeIcqContactValue($sValue) === false;
    }
    if ((string)$sType == "skype") {
        return normalizeSkypeContactValue($sValue) === false;
    }
    $mKnownValue = normalizeKnownContactValue($sType, $sValue);
    if ($mKnownValue !== null) {
        return $mKnownValue === false;
    }
    return false;
}

function youTubeContactHref($sValue) {
    $sValue = trim((string)$sValue);
    if ($sValue == "") {
        return "";
    }
    $sValue = normalizeYouTubeContactValue($sValue, true);
    return $sValue !== false ? $sValue : "";
}

function normalizeWebContactValue($sValue) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sScheme = "";
    $sHost = "";
    $sUrl = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    } elseif (!preg_match("#^[A-Za-z][A-Za-z0-9+.-]*://#", $sText)) {
        $sText = "https://" . $sText;
    }
    $aParts = parse_url($sText);
    if (!is_array($aParts) || empty($aParts["scheme"]) || empty($aParts["host"])) {
        return false;
    }
    $sScheme = strtolower((string)$aParts["scheme"]);
    $sHost = strtolower((string)$aParts["host"]);
    if ($sScheme != "http" && $sScheme != "https") {
        return false;
    }
    if (!preg_match("/^[A-Za-z0-9.-]+$/", $sHost) && !filter_var($sHost, FILTER_VALIDATE_IP)) {
        return false;
    }
    $sUrl = $sScheme . "://" . $sHost;
    if (isset($aParts["port"])) {
        $sUrl .= ":" . (int)$aParts["port"];
    }
    if (isset($aParts["path"])) {
        $sUrl .= (string)$aParts["path"];
    }
    if (isset($aParts["query"])) {
        $sUrl .= "?" . (string)$aParts["query"];
    }
    if (isset($aParts["fragment"])) {
        $sUrl .= "#" . (string)$aParts["fragment"];
    }
    return $sUrl;
}

function contactProfileRules() {
    return array(
        "telegram" => array("hosts" => array("t.me", "telegram.me"), "base" => "https://t.me/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_]{5,32}$/"),
        "messenger" => array("hosts" => array("m.me", "messenger.com"), "base" => "https://m.me/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9.]{5,50}$/"),
        "twitter" => array("hosts" => array("x.com", "twitter.com"), "base" => "https://x.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_]{1,15}$/"),
        "threads" => array("hosts" => array("threads.net"), "base" => "https://www.threads.net/@", "prefix" => "@", "strip_at" => true, "pattern" => "/^[A-Za-z0-9._]{1,30}$/"),
        "facebook" => array("hosts" => array("facebook.com", "fb.com"), "base" => "https://www.facebook.com/", "prefix" => "", "strip_at" => false, "pattern" => "/^[A-Za-z0-9.]{5,50}$/"),
        "instagram" => array("hosts" => array("instagram.com"), "base" => "https://www.instagram.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^(?!.*\\.\\.)(?!.*\\.$)[A-Za-z0-9._]{1,30}$/"),
        "tiktok" => array("hosts" => array("tiktok.com"), "base" => "https://www.tiktok.com/@", "prefix" => "@", "strip_at" => true, "pattern" => "/^[A-Za-z0-9._]{2,24}$/"),
        "github" => array("hosts" => array("github.com"), "base" => "https://github.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,37}[A-Za-z0-9])?$/"),
        "gitlab" => array("hosts" => array("gitlab.com"), "base" => "https://gitlab.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9](?:[A-Za-z0-9._-]{0,253}[A-Za-z0-9])?$/"),
        "bitbucket" => array("hosts" => array("bitbucket.org"), "base" => "https://bitbucket.org/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9](?:[A-Za-z0-9_-]{0,61}[A-Za-z0-9])?$/"),
        "deviantart" => array("hosts" => array("deviantart.com"), "base" => "https://www.deviantart.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9-]{3,20}$/"),
        "furaffinity" => array("hosts" => array("furaffinity.net"), "base" => "https://www.furaffinity.net/user/", "prefix" => "user", "strip_at" => true, "pattern" => "/^[A-Za-z0-9~._-]{1,30}$/"),
        "sofurry" => array("hosts" => array("sofurry.com"), "base" => "https://www.sofurry.com/user/", "prefix" => "user", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_-]{1,64}$/"),
        "wikifur" => array("hosts" => array("wikifur.com", "en.wikifur.com"), "base" => "https://en.wikifur.com/wiki/", "prefix" => "wiki", "strip_at" => false, "pattern" => "/^[^\\/\\?#]{1,255}$/"),
        "artstation" => array("hosts" => array("artstation.com"), "base" => "https://www.artstation.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_-]{3,32}$/"),
        "behance" => array("hosts" => array("behance.net"), "base" => "https://www.behance.net/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_-]{1,64}$/"),
        "dribbble" => array("hosts" => array("dribbble.com"), "base" => "https://dribbble.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_-]{1,64}$/"),
        "twitch" => array("hosts" => array("twitch.tv"), "base" => "https://www.twitch.tv/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_]{4,25}$/"),
        "kick" => array("hosts" => array("kick.com"), "base" => "https://kick.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_]{3,25}$/"),
        "vimeo" => array("hosts" => array("vimeo.com"), "base" => "https://vimeo.com/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_-]{1,64}$/"),
        "reddit" => array("hosts" => array("reddit.com"), "base" => "https://www.reddit.com/user/", "prefix" => "user", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_-]{3,20}$/"),
        "npm" => array("hosts" => array("npmjs.com"), "base" => "https://www.npmjs.com/~", "prefix" => "~", "strip_at" => true, "pattern" => "/^[a-z0-9][a-z0-9._-]{0,213}$/"),
        "pypi" => array("hosts" => array("pypi.org"), "base" => "https://pypi.org/user/", "prefix" => "user", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_.-]{1,64}$/"),
        "docker" => array("hosts" => array("hub.docker.com"), "base" => "https://hub.docker.com/u/", "prefix" => "u", "strip_at" => true, "pattern" => "/^[a-z0-9][a-z0-9_-]{3,29}$/"),
        "codeberg" => array("hosts" => array("codeberg.org"), "base" => "https://codeberg.org/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9](?:[A-Za-z0-9._-]{0,38}[A-Za-z0-9])?$/"),
        "paypal" => array("hosts" => array("paypal.me"), "base" => "https://paypal.me/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9._-]{3,64}$/"),
        "revolut" => array("hosts" => array("revolut.me"), "base" => "https://revolut.me/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9._-]{3,64}$/"),
        "lastfm" => array("hosts" => array("last.fm"), "base" => "https://www.last.fm/user/", "prefix" => "user", "strip_at" => true, "pattern" => "/^[A-Za-z0-9_-]{2,64}$/"),
        "signaly" => array("hosts" => array("signaly.cz"), "base" => "https://www.signaly.cz/", "prefix" => "", "strip_at" => true, "pattern" => "/^[A-Za-z0-9._-]{1,64}$/")
    );
}

function normalizeProfileContactValue($sContactType, $sValue) {
    $aRules = contactProfileRules();
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sHandle = "";
    $sPrefix = "";
    $blLooksLikeUrl = false;
    if (!isset($aRules[(string)$sContactType])) {
        return null;
    }
    if ($sText == "") {
        return "";
    }
    $aRule = $aRules[(string)$sContactType];
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    $blLooksLikeUrl = preg_match("#^https?://#i", $sText) || preg_match("#^www\\.#i", $sText) || preg_match("#^[A-Za-z0-9.-]+\\.[A-Za-z]{2,}[/:?\\#].*$#", $sText);
    foreach ($aRule["hosts"] as $sAllowedHost) {
        if ($sText == $sAllowedHost || strpos($sText, $sAllowedHost . "/") === 0 || $sText == "www." . $sAllowedHost || strpos($sText, "www." . $sAllowedHost . "/") === 0) {
            $blLooksLikeUrl = true;
        }
    }
    if ($blLooksLikeUrl) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        if (!in_array($sHost, $aRule["hosts"], true)) {
            return false;
        }
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if (!$sPath) {
            return false;
        }
        $aSegments = explode("/", $sPath);
        $sPrefix = isset($aRule["prefix"]) ? (string)$aRule["prefix"] : "";
        if ($sPrefix == "~") {
            $sHandle = preg_replace("/^~/", "", rawurldecode($aSegments[0]));
        } elseif ($sPrefix == "@") {
            $sHandle = preg_replace("/^@/", "", rawurldecode($aSegments[0]));
        } elseif ($sPrefix != "") {
            if (count($aSegments) < 2 || strtolower($aSegments[0]) !== strtolower($sPrefix)) {
                return false;
            }
            $sHandle = rawurldecode($aSegments[1]);
        } else {
            $sHandle = rawurldecode($aSegments[0]);
        }
    } else {
        $sHandle = $sText;
    }
    if (!empty($aRule["strip_at"])) {
        $sHandle = preg_replace("/^@+/", "", $sHandle);
    }
    return preg_match($aRule["pattern"], $sHandle) ? (string)$aRule["base"] . rawurlencode($sHandle) : false;
}

function normalizeLinkedInContactValue($sValue) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sKind = "in";
    $sHandle = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?linkedin\\.com(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        if ($sHost != "linkedin.com") {
            return false;
        }
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if (!preg_match("#^(in|company)/([^/]+)$#i", $sPath, $aMatches)) {
            return false;
        }
        $sKind = strtolower($aMatches[1]);
        $sHandle = rawurldecode($aMatches[2]);
    } else {
        if (preg_match("#^(in|company)/([^/]+)$#i", $sText, $aMatches)) {
            $sKind = strtolower($aMatches[1]);
            $sHandle = rawurldecode($aMatches[2]);
        } else {
            $sHandle = $sText;
        }
    }
    return preg_match("/^[A-Za-z0-9_-]{2,100}$/", $sHandle) ? "https://www.linkedin.com/" . $sKind . "/" . rawurlencode($sHandle) : false;
}

function normalizeStackOverflowContactValue($sValue) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sUserId = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?stackoverflow\\.com(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost != "stackoverflow.com" || !preg_match("#^users/([0-9]+)(?:/.*)?$#i", $sPath, $aMatches)) {
            return false;
        }
        $sUserId = $aMatches[1];
    } else {
        $sUserId = $sText;
    }
    return preg_match("/^[0-9]+$/", $sUserId) ? "https://stackoverflow.com/users/" . $sUserId : false;
}

function normalizeSteamContactValue($sValue) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sKind = "";
    $sValuePart = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?steamcommunity\\.com(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost != "steamcommunity.com" || !preg_match("#^(id|profiles)/([^/]+)$#i", $sPath, $aMatches)) {
            return false;
        }
        $sKind = strtolower($aMatches[1]);
        $sValuePart = rawurldecode($aMatches[2]);
    } else {
        $sKind = preg_match("/^[0-9]{17}$/", $sText) ? "profiles" : "id";
        $sValuePart = $sText;
    }
    if ($sKind == "profiles" && !preg_match("/^[0-9]{17}$/", $sValuePart)) {
        return false;
    }
    return $sKind == "id" && !preg_match("/^[A-Za-z0-9_-]{2,64}$/", $sValuePart) ? false : "https://steamcommunity.com/" . $sKind . "/" . rawurlencode($sValuePart);
}

function normalizeGoodreadsContactValue($sValue) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?goodreads\\.com(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost != "goodreads.com" || !preg_match("#^user/show/([0-9]+)(?:[.-].*)?$#i", $sPath, $aMatches)) {
            return false;
        }
        return "https://www.goodreads.com/user/show/" . $aMatches[1];
    }
    return preg_match("/^[0-9]+$/", $sText) ? "https://www.goodreads.com/user/show/" . $sText : false;
}

function normalizeFederatedContactValue($sValue, $sPathPrefix) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sUser = "";
    $sDomain = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText)) {
        $aParts = parse_url($sText);
        $sHost = isset($aParts["host"]) ? strtolower((string)$aParts["host"]) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sPathPrefix == "@" && preg_match("#^@([^/]+)$#", $sPath, $aMatches)) {
            $sUser = rawurldecode($aMatches[1]);
        } elseif ($sPathPrefix != "@" && preg_match("#^" . preg_quote($sPathPrefix, "#") . "/([^/]+)$#i", $sPath, $aMatches)) {
            $sUser = rawurldecode($aMatches[1]);
        } else {
            return false;
        }
        $sDomain = $sHost;
    } elseif (preg_match("/^@?([A-Za-z0-9_][A-Za-z0-9_.-]{0,29})@([A-Za-z0-9.-]+\\.[A-Za-z]{2,})$/", $sText, $aMatches)) {
        $sUser = $aMatches[1];
        $sDomain = strtolower($aMatches[2]);
    } else {
        return false;
    }
    if (!preg_match("/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,29}$/", $sUser) || !preg_match("/^[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$/", $sDomain)) {
        return false;
    }
    return "https://" . $sDomain . "/" . ($sPathPrefix == "@" ? "@" : $sPathPrefix . "/") . rawurlencode($sUser);
}

function isAtprotoHandle($sHandle) {
    return preg_match("/^([A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\\.)+[A-Za-z](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/", (string)$sHandle);
}

function normalizeBlueskyContactValue($sValue) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sHandle = "";
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?bsky\\.app(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost != "bsky.app" || !preg_match("#^profile/([^/]+)$#i", $sPath, $aMatches)) {
            return false;
        }
        $sHandle = strtolower(rawurldecode($aMatches[1]));
    } else {
        $sHandle = strtolower(preg_replace("/^@+/", "", $sText));
    }
    return isAtprotoHandle($sHandle) ? "https://bsky.app/profile/" . rawurlencode($sHandle) : false;
}

function normalizeMatrixContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^https?://matrix\\.to/\\#/(@[^?\\#]+)#i", $sText, $aMatches)) {
        $sText = rawurldecode($aMatches[1]);
    }
    return preg_match("/^@[a-z0-9._=\\-\\/+]+:[A-Za-z0-9.-]+(?::[0-9]+)?$/", $sText) ? $sText : false;
}

function normalizeJabberContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText == "") {
        return "";
    }
    $sText = preg_replace("#^xmpp:#i", "", $sText);
    return preg_match("#^[^@\\s/]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}(?:/[^\\s]+)?$#", $sText) ? $sText : false;
}

function orcidCheckDigit($sDigits) {
    $iTotal = 0;
    for ($iI = 0; $iI < strlen($sDigits); $iI++) {
        $iTotal = ($iTotal + (int)$sDigits[$iI]) * 2;
    }
    $iResult = (12 - ($iTotal % 11)) % 11;
    return $iResult === 10 ? "X" : (string)$iResult;
}

function normalizeOrcidContactValue($sValue) {
    $sText = strtoupper(trim((string)$sValue));
    $sId = "";
    if ($sText == "") {
        return "";
    }
    $sText = preg_replace("#^HTTPS?://ORCID\\.ORG/#", "", $sText);
    $sId = preg_replace("/[^0-9X]/", "", $sText);
    if (!preg_match("/^[0-9]{15}[0-9X]$/", $sId)) {
        return false;
    }
    if (orcidCheckDigit(substr($sId, 0, 15)) !== substr($sId, 15, 1)) {
        return false;
    }
    return "https://orcid.org/" . substr($sId, 0, 4) . "-" . substr($sId, 4, 4) . "-" . substr($sId, 8, 4) . "-" . substr($sId, 12, 4);
}

function normalizeMessagingPhoneContactValue($sValue, $sContactType) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sDigits = "";
    if ($sText == "") {
        return "";
    }
    if ((string)$sContactType == "whatsapp") {
        if (preg_match("#^//#", $sText)) {
            $sText = "https:" . $sText;
        }
        if (preg_match("#^https?://#i", $sText)) {
            $aParts = parse_url($sText);
            $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
            $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
            if ($sHost == "wa.me" && preg_match("/^[0-9]+$/", $sPath)) {
                $sText = "+" . $sPath;
            } elseif (($sHost == "api.whatsapp.com" || $sHost == "whatsapp.com") && isset($aParts["query"]) && preg_match("/(?:^|&)phone=([0-9]+)/", (string)$aParts["query"], $aMatches)) {
                $sText = "+" . $aMatches[1];
            }
        }
    }
    $sDigits = normalizePhoneContactValue($sText);
    return $sDigits !== false ? $sDigits : false;
}

function normalizeKnownContactValue($sContactType, $sContactValue) {
    $sContactType = contactTypeKey($sContactType);
    $mProfileValue = normalizeProfileContactValue($sContactType, $sContactValue);
    if (!isOriginalContactType($sContactType)) {
        return null;
    }
    if ((string)$sContactType == "telegram") {
        return normalizeTelegramContactValue($sContactValue);
    }
    if ($mProfileValue !== null) {
        return $mProfileValue;
    }
    if ((string)$sContactType == "web") {
        return normalizeWebContactValue($sContactValue);
    }
    if ((string)$sContactType == "jabber") {
        return normalizeJabberContactValue($sContactValue);
    }
    if ((string)$sContactType == "matrix") {
        return normalizeMatrixContactValue($sContactValue);
    }
    if ((string)$sContactType == "mastodon") {
        return normalizeFederatedContactValue($sContactValue, "@");
    }
    if ((string)$sContactType == "lemmy") {
        return normalizeFederatedContactValue($sContactValue, "u");
    }
    if ((string)$sContactType == "bluesky") {
        return normalizeBlueskyContactValue($sContactValue);
    }
    if ((string)$sContactType == "linkedin") {
        return normalizeLinkedInContactValue($sContactValue);
    }
    if ((string)$sContactType == "stackoverflow") {
        return normalizeStackOverflowContactValue($sContactValue);
    }
    if ((string)$sContactType == "steam") {
        return normalizeSteamContactValue($sContactValue);
    }
    if ((string)$sContactType == "goodreads") {
        return normalizeGoodreadsContactValue($sContactValue);
    }
    if ((string)$sContactType == "orcid") {
        return normalizeOrcidContactValue($sContactValue);
    }
    if ((string)$sContactType == "whatsapp" || (string)$sContactType == "viber") {
        return normalizeMessagingPhoneContactValue($sContactValue, $sContactType);
    }
    return null;
}

function knownContactLinkTypes() {
    return array(
        "web" => true,
        "jabber" => true,
        "whatsapp" => true,
        "telegram" => true,
        "messenger" => true,
        "viber" => true,
        "matrix" => true,
        "twitter" => true,
        "mastodon" => true,
        "bluesky" => true,
        "threads" => true,
        "facebook" => true,
        "instagram" => true,
        "tiktok" => true,
        "linkedin" => true,
        "github" => true,
        "gitlab" => true,
        "bitbucket" => true,
        "stackoverflow" => true,
        "deviantart" => true,
        "furaffinity" => true,
        "sofurry" => true,
        "wikifur" => true,
        "artstation" => true,
        "behance" => true,
        "dribbble" => true,
        "youtube" => true,
        "twitch" => true,
        "kick" => true,
        "vimeo" => true,
        "reddit" => true,
        "lemmy" => true,
        "steam" => true,
        "npm" => true,
        "pypi" => true,
        "docker" => true,
        "codeberg" => true,
        "paypal" => true,
        "revolut" => true,
        "orcid" => true,
        "goodreads" => true,
        "lastfm" => true,
        "signaly" => true
    );
}

function contactTypeHasKnownLink($sType) {
    $aTypes = knownContactLinkTypes();
    return isset($aTypes[contactTypeKey($sType)]);
}

function contactDisplayValue($sType, $sValue) {
    $sType = contactTypeKey($sType);
    $sCanonicalValue = contactCanonicalValue($sType, $sValue);
    if (isPhoneContactType($sType) || (string)$sType == "whatsapp" || (string)$sType == "viber") {
        return phoneContactDisplayValue($sCanonicalValue);
    }
    return $sCanonicalValue;
}

function contactHref($sType, $sValue, $blAllowExternalLinks = false) {
    $sType = contactTypeKey($sType);
    $sText = trim((string)$sValue);
    $mKnownValue = normalizeKnownContactValue($sType, $sValue);
    if (isPhoneContactType($sType)) {
        return phoneContactHref($sValue);
    }
    if ($sType == "email") {
        $sText = normalizeEmailContactValue($sValue);
        return $sText !== false && $sText != "" ? "mailto:" . $sText : "";
    }
    if ($sType == "jabber") {
        $sText = normalizeJabberContactValue($sValue);
        return $sText !== false && $sText != "" ? "xmpp:" . $sText : "";
    }
    if ($sType == "matrix") {
        $sText = normalizeMatrixContactValue($sValue);
        return $sText !== false && $sText != "" ? "https://matrix.to/#/" . rawurlencode($sText) : "";
    }
    if ($sType == "whatsapp") {
        $sText = normalizeMessagingPhoneContactValue($sValue, $sType);
        return $sText !== false && $sText != "" ? "https://wa.me/" . preg_replace("/\\D/", "", $sText) : "";
    }
    if ($sType == "viber") {
        $sText = normalizeMessagingPhoneContactValue($sValue, $sType);
        return $sText !== false && $sText != "" ? "viber://chat?number=%2B" . preg_replace("/\\D/", "", $sText) : "";
    }
    if ($blAllowExternalLinks && $mKnownValue !== null && $mKnownValue !== false && preg_match("#^https?://#i", (string)$mKnownValue)) {
        return (string)$mKnownValue;
    }
    if ($blAllowExternalLinks && $sType == "web") {
        $sText = normalizeWebContactValue($sValue);
        if ($sText === false || $sText == "") {
            return "";
        }
        return $sText;
    }
    if ($blAllowExternalLinks && $sType == "telegram") {
        $sText = normalizeTelegramContactValue($sValue);
        return $sText !== false ? $sText : "";
    }
    if ($blAllowExternalLinks && $sType == "youtube") {
        return youTubeContactHref($sValue);
    }
    return "";
}

function contactLinkEmoji($sType) {
    global $sContactEmailEmoji, $sContactLandlineEmoji, $sContactCellEmoji, $sContactFaxEmoji, $sContactPagerEmoji, $sContactWebEmoji, $sContactTelegramEmoji, $sContactMessageEmoji, $sContactYouTubeEmoji;

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
    if ($sType == "web") {
        return $sContactWebEmoji;
    }
    if ($sType == "telegram") {
        return $sContactTelegramEmoji;
    }
    if ($sType == "whatsapp") {
        return $sContactMessageEmoji;
    }
    if ($sType == "viber") {
        return $sContactMessageEmoji;
    }
    if ($sType == "jabber" || $sType == "matrix") {
        return $sContactMessageEmoji;
    }
    if ($sType == "youtube") {
        return $sContactYouTubeEmoji;
    }
    if (contactTypeHasKnownLink($sType)) {
        return $sContactWebEmoji;
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
    if ($sType == "web") {
        return "Open web";
    }
    if ($sType == "telegram") {
        return "Open Telegram";
    }
    if ($sType == "whatsapp") {
        return "Open WhatsApp";
    }
    if ($sType == "viber") {
        return "Open Viber";
    }
    if ($sType == "jabber") {
        return "Open Jabber";
    }
    if ($sType == "matrix") {
        return "Open Matrix";
    }
    if ($sType == "youtube") {
        return "Open YouTube";
    }
    if (contactTypeHasKnownLink($sType)) {
        return "Open web";
    }
    return "";
}

function postalCodeMetadata() {
    static $aMetadata = null;

    if ($aMetadata !== null) {
        return $aMetadata;
    }
    $sFile = __DIR__ . "/lib/postal_code_metadata.json";
    $aMetadata = array();
    if (is_file($sFile)) {
        $sJson = file_get_contents($sFile);
        $sJson = preg_replace("/^\\xEF\\xBB\\xBF/", "", (string)$sJson);
        $aDecoded = json_decode($sJson, true);
        if (is_array($aDecoded)) {
            $aMetadata = $aDecoded;
        }
    }
    return $aMetadata;
}

function postalCodePatternMatches($sPattern, $sPostalCode) {
    $sPattern = trim((string)$sPattern);
    if ($sPattern == "") {
        return true;
    }
    return @preg_match("~^(?:" . str_replace("~", "\\~", $sPattern) . ")$~i", (string)$sPostalCode);
}

function postalCodeAlnum($sPostalCode) {
    return preg_replace("/[^A-Z0-9]/", "", strtoupper((string)$sPostalCode));
}

function addressCountryCode($sCountry) {
    $sCountry = strtoupper(trim((string)$sCountry));
    return $sCountry == "CS" ? "CZ" : $sCountry;
}

function postalCodeFormatByExample($sPostalCode, $sExamples) {
    $sAlnum = postalCodeAlnum($sPostalCode);
    $aExamples = explode(",", (string)$sExamples);
    $sExample = "";
    $sFormatted = "";
    $iIndex = 0;
    if ($sAlnum == "") {
        return "";
    }
    foreach ($aExamples as $sExampleCandidate) {
        if (strlen(postalCodeAlnum($sExampleCandidate)) == strlen($sAlnum)) {
            $sExample = trim((string)$sExampleCandidate);
            break;
        }
    }
    if ($sExample == "") {
        return strtoupper(trim((string)$sPostalCode));
    }
    for ($iChar = 0; $iChar < strlen($sExample); $iChar++) {
        $sChar = substr($sExample, $iChar, 1);
        if (preg_match("/[A-Za-z0-9]/", $sChar)) {
            if ($iIndex < strlen($sAlnum)) {
                $sFormatted .= substr($sAlnum, $iIndex, 1);
                $iIndex++;
            }
        } else {
            $sFormatted .= $sChar;
        }
    }
    return $sFormatted;
}

function analyzePostalCode($sCountry, $sPostalCode) {
    $sCountry = addressCountryCode($sCountry);
    $sText = strtoupper(trim((string)$sPostalCode));
    $aMetadata = postalCodeMetadata();
    $sPattern = isset($aMetadata[$sCountry]["zip"]) ? (string)$aMetadata[$sCountry]["zip"] : "";
    $sExamples = isset($aMetadata[$sCountry]["zipex"]) ? (string)$aMetadata[$sCountry]["zipex"] : "";
    $aCandidates = array();
    if ($sText == "") {
        return array("valid" => true, "value" => "");
    }
    if ($sCountry == "CZ" || $sCountry == "SK") {
        $sDigits = preg_replace("/\\D/", "", $sText);
        if (strlen($sDigits) == 5) {
            $sText = substr($sDigits, 0, 3) . " " . substr($sDigits, 3, 2);
        }
    }
    if (!preg_match("/^[A-Z0-9\\s\\-]+$/", $sText)) {
        return array("valid" => false, "value" => $sText);
    }
    if ($sPattern == "") {
        return array("valid" => true, "value" => preg_replace("/\\s+/", " ", $sText));
    }
    $aCandidates[] = preg_replace("/\\s+/", " ", $sText);
    $aCandidates[] = postalCodeAlnum($sText);
    $aCandidates[] = postalCodeFormatByExample($sText, $sExamples);
    foreach ($aCandidates as $sCandidate) {
        $sCandidate = trim((string)$sCandidate);
        if ($sCandidate != "" && postalCodePatternMatches($sPattern, $sCandidate)) {
            return array("valid" => true, "value" => postalCodeFormatByExample($sCandidate, $sExamples));
        }
    }
    return array("valid" => false, "value" => $sText);
}

function normalizePostalCode($sCountry, $sPostalCode) {
    $aPostalCode = analyzePostalCode($sCountry, $sPostalCode);
    return !empty($aPostalCode["valid"]) ? (string)$aPostalCode["value"] : false;
}

function postalCodeDisplayValue($sCountry, $sPostalCode) {
    $aPostalCode = analyzePostalCode($sCountry, $sPostalCode);
    return !empty($aPostalCode["valid"]) ? (string)$aPostalCode["value"] : (string)$sPostalCode;
}

function getPostedValues($sName) {
    $sEncodedName = $sName . "_b64";
    $aRawValues = array();
    if (isset($_POST[$sName]) && is_array($_POST[$sName])) {
        foreach ($_POST[$sName] as $mValue) {
            $aRawValues[] = (string)$mValue;
        }
    }
    $aValues = array();
    if (isset($_POST[$sEncodedName]) && is_array($_POST[$sEncodedName])) {
        if (count($aRawValues) > 0 && count($_POST[$sEncodedName]) != count($aRawValues)) {
            return $aRawValues;
        }
        foreach ($_POST[$sEncodedName] as $mValue) {
            $aValues[] = decodePostedBase64Value($mValue);
        }
        return $aValues;
    }
    return $aRawValues;
}

function renderAddSubjectItemAction($sClass, $sTitle, $iSubjectId, $sPrefix = "", $sSuffix = "") {
    global $sAddEmoji, $sEmptyValueEmoji;

    if ((int)$iSubjectId < 1) {
        return $sEmptyValueEmoji;
    }
    return "<div class=\"add-item-row\">" . $sPrefix . "<a href=\"#\" class=\"item-action add-item-action " . html($sClass) . "\" data-subject-id=\"" . html($iSubjectId) . "\" title=\"" . html($sTitle) . "\" aria-label=\"" . html($sTitle) . "\">" . $sAddEmoji . "</a>" . $sSuffix . "</div>";
}

function renderSubjectCellActionRow($sFirstAction, $sSecondAction = "") {
    if ($sFirstAction == "") {
        return $sSecondAction;
    }
    if ($sSecondAction == "") {
        return $sFirstAction;
    }
    return "<div class=\"add-item-row\">" . $sFirstAction . $sSecondAction . "</div>";
}

function renderHiddenInactiveIndicator() {
    global $sHiddenInactiveEmoji;

    return "<span class=\"hidden-inactive-indicator\" title=\"Hidden inactive content\" aria-label=\"Hidden inactive content\">" . $sHiddenInactiveEmoji . "</span>";
}

function renderEmptySubjectItemCell($blShowActions, $sClass, $sTitle, $iSubjectId, $blHasHiddenInactive, $blShowAddAction = true) {
    global $sEmptyValueEmoji;

    $sHiddenInactive = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    if ($blShowActions && $blShowAddAction) {
        return renderAddSubjectItemAction($sClass, $sTitle, $iSubjectId, $sHiddenInactive);
    }
    return $sHiddenInactive != "" ? $sHiddenInactive : $sEmptyValueEmoji;
}

function renderContactList($aContacts, $blShowActions = true, $iSubjectId = 0, $blShowCopy = true, $blAllowExternalLinks = true, $blHasHiddenInactive = false, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aContacts) {
        return renderEmptySubjectItemCell($blShowActions, "js-add-subject-contact", "New contact", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"contact-list\">";
    $aCellCopyValues = array();
    $sHiddenInactiveAction = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    foreach ($aContacts as $aContact) {
        $sNote = trim((string)$aContact["note"]);
        $blIsPrimary = (int)$aContact["is_primary"] == 1;
        $blIsActive = (int)$aContact["is_active"] == 1;
        $sContactType = isset($aContact["contact_type"]) ? (string)$aContact["contact_type"] : "";
        $sContactTypeName = isset($aContact["contact_type_name"]) && trim((string)$aContact["contact_type_name"]) != "" ? (string)$aContact["contact_type_name"] : contactTypeLabel($sContactType);
        $sContactValue = contactDisplayValue($sContactType, $aContact["contact_value"]);
        $sTimestampTooltipText = timestampTooltipText($aContact);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $aCellCopyValues[] = $sContactTypeName . ": " . $sContactValue . ($sNote != "" ? " (" . $sNote . ")" : "");
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"list-item-actions\">"
                . "<a href=\"#\" class=\"item-action js-edit-subject-contact\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"item-action js-delete-subject-contact\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"contact-item list-item" . ($blIsActive ? "" : " contact-item-inactive") . "\""
            . " data-subject-contact-id=\"" . html($aContact["subject_contact_id"]) . "\""
            . " data-contact-id=\"" . html($aContact["contact_id"]) . "\""
            . " data-contact-type-id=\"" . html(isset($aContact["contact_type_id"]) ? $aContact["contact_type_id"] : "") . "\""
            . " data-contact-type=\"" . html($sContactType) . "\""
            . " data-contact-type-name=\"" . html($sContactTypeName) . "\""
            . " data-contact-value=\"" . html($sContactValue) . "\""
            . " data-contact-note=\"" . html($sNote) . "\""
            . " data-contact-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-contact-active=\"" . ($blIsActive ? "1" : "0") . "\""
            . renderTimestampTooltipDataAttribute($aContact) . ">"
            . "<span class=\"contact-db-values\"" . $sTimestampTooltipAttribute . "><span class=\"contact-type\">" . html($sContactTypeName) . "</span>: "
            . renderContactValueText($sContactType, $aContact["contact_value"]) . "</span>"
            . renderContactValueActions($sContactType, $aContact["contact_value"], $blShowCopy, $blAllowExternalLinks)
            . "<span class=\"contact-note\">" . ($sNote != "" ? "(" . html($sNote) . ")" : "") . "</span>"
            . "<span class=\"contact-flags\">"
            . "<span class=\"contact-primary\" title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span>"
            . "<span class=\"contact-inactive-label\" title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span>"
            . "</span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? renderSubjectCellCopyAction($aCellCopyValues) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= renderAddSubjectItemAction("js-add-subject-contact", "New contact", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= renderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function renderNicknameList($aNicknames, $blShowActions = true, $iSubjectId = 0, $blHasHiddenInactive = false, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aNicknames) {
        return renderEmptySubjectItemCell($blShowActions, "js-add-subject-nickname", "New nickname", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"subject-item-list\">";
    $aCellCopyValues = array();
    $sHiddenInactiveAction = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    foreach ($aNicknames as $aNickname) {
        $sContext = trim((string)$aNickname["context"]);
        $sNote = trim((string)$aNickname["note"]);
        $sCopyText = $aNickname["nickname"] . ($sContext != "" ? " [" . $sContext . "]" : "") . ($sNote != "" ? " (" . $sNote . ")" : "");
        $aCellCopyValues[] = $sCopyText;
        $blIsPrimary = (int)$aNickname["is_primary"] == 1;
        $blIsActive = (int)$aNickname["is_active"] == 1;
        $sTimestampTooltipText = timestampTooltipText($aNickname);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"list-item-actions\">"
                . "<a href=\"#\" class=\"item-action js-edit-subject-nickname\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"item-action js-delete-subject-nickname\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"subject-item list-item subject-nickname-item" . ($blIsActive ? "" : " subject-item-inactive") . "\""
            . " data-nickname-id=\"" . html($aNickname["id"]) . "\""
            . " data-subject-id=\"" . html($aNickname["subject_id"]) . "\""
            . " data-nickname=\"" . html($aNickname["nickname"]) . "\""
            . " data-context=\"" . html($sContext) . "\""
            . " data-note=\"" . html($sNote) . "\""
            . " data-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"subject-nickname-values\"" . $sTimestampTooltipAttribute . ">"
            . "<span class=\"subject-item-value\">" . html($aNickname["nickname"]) . "</span>"
            . "<span class=\"subject-item-context\">" . ($sContext != "" ? " [" . html($sContext) . "]" : "") . "</span>"
            . "</span>"
            . "<span class=\"subject-item-note\">" . ($sNote != "" ? " (" . html($sNote) . ")" : "") . "</span>"
            . renderCopyAction($sCopyText)
            . "<span class=\"subject-item-flags\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? renderSubjectCellCopyAction($aCellCopyValues) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= renderAddSubjectItemAction("js-add-subject-nickname", "New nickname", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= renderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function appendAddressCopyLine(&$aLines, $mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue != "") {
        $aLines[] = $sValue;
    }
}

function cleanAddressLine($sLine) {
    $sLine = preg_replace("/[ \\t]+/", " ", trim((string)$sLine));
    $sLine = preg_replace("/\\s+,/", ",", $sLine);
    $sLine = preg_replace("/,\\s*,+/", ",", $sLine);
    return trim($sLine, " ,");
}

function appendAddressTemplateValue(&$aLines, $sValue) {
    $aValueLines = preg_split("/\\r\\n|\\r|\\n/", (string)$sValue);
    $iIndex = 0;
    if (!$aLines) {
        $aLines[] = "";
    }
    foreach ($aValueLines as $sValueLine) {
        $sValueLine = trim((string)$sValueLine);
        if ($iIndex == 0) {
            $aLines[count($aLines) - 1] .= $sValueLine;
        } else {
            $aLines[] = $sValueLine;
        }
        $iIndex++;
    }
}

function addressMetadata($sCountry) {
    $sCountry = addressCountryCode($sCountry);
    $aMetadata = postalCodeMetadata();
    return isset($aMetadata[$sCountry]) && is_array($aMetadata[$sCountry]) ? $aMetadata[$sCountry] : array();
}

function addressStreetLine($aAddress, $sCountryCode) {
    $sStreetName = trim((string)$aAddress["street_name"]);
    $sHouseNumber = trim((string)$aAddress["house_number"]);
    $sEvidenceNumber = trim((string)$aAddress["evidence_number"]);
    $sOrientationNumber = trim((string)$aAddress["orientation_number"]);
    $sOrientationSuffix = trim((string)$aAddress["orientation_suffix"]);
    $sOrientation = trim($sOrientationNumber . $sOrientationSuffix);
    $sHouse = trim($sHouseNumber . ($sHouseNumber != "" && $sOrientation != "" ? "/" : "") . $sOrientation);
    if ($sEvidenceNumber != "") {
        $sHouse = trim($sHouse . ($sHouse != "" ? ", " : "") . "ev. " . $sEvidenceNumber);
    }
    return $sCountryCode == "US"
        ? trim($sHouse . ($sHouse != "" && $sStreetName != "" ? " " : "") . $sStreetName)
        : trim($sStreetName . ($sStreetName != "" && $sHouse != "" ? " " : "") . $sHouse);
}

function addressCityLine($aAddress) {
    $sCity = trim((string)$aAddress["city"]);
    $sCityPart = trim((string)$aAddress["city_part"]);
    return trim($sCity . ($sCity != "" && $sCityPart != "" ? "-" : "") . $sCityPart);
}

function addressOrganizationLine($aAddress) {
    $aLines = array();
    appendAddressCopyLine($aLines, $aAddress["organization_name"]);
    appendAddressCopyLine($aLines, $aAddress["department_name"]);
    return implode("\n", $aLines);
}

function addressAddressLine($aAddress, $sCountryCode) {
    $aLines = array();
    appendAddressCopyLine($aLines, trim((string)$aAddress["care_of"]) != "" ? "c/o " . trim((string)$aAddress["care_of"]) : "");
    appendAddressCopyLine($aLines, addressStreetLine($aAddress, $sCountryCode));
    appendAddressCopyLine($aLines, $aAddress["address_line2"]);
    return implode("\n", $aLines);
}

function addressFormatTemplate($sCountryCode) {
    $aMetadata = addressMetadata($sCountryCode);
    $sFormat = isset($aMetadata["fmt"]) ? trim((string)$aMetadata["fmt"]) : "";
    return $sFormat != "" ? $sFormat : "%N%n%O%n%A%n%Z %C";
}

function buildAddressLines($aAddress, $sSubjectName = "", $aSettings = null, $blDisplayCountry = true) {
    $sCountryCode = addressCountryCode($aAddress["country"]);
    $sPostalCode = postalCodeDisplayValue($sCountryCode, $aAddress["postal_code"]);
    $sFormat = addressFormatTemplate($sCountryCode);
    $sCity = trim((string)$aAddress["city"]);
    $sCityPart = trim((string)$aAddress["city_part"]);
    $aFields = array(
        "N" => trim((string)$sSubjectName),
        "O" => addressOrganizationLine($aAddress),
        "A" => addressAddressLine($aAddress, $sCountryCode),
        "C" => strpos($sFormat, "%D") !== false ? $sCity : addressCityLine($aAddress),
        "S" => trim((string)$aAddress["region"]),
        "Z" => $sPostalCode,
        "X" => "",
        "D" => $sCityPart
    );
    $aLines = array("");
    for ($iIndex = 0; $iIndex < strlen($sFormat); $iIndex++) {
        $sChar = substr($sFormat, $iIndex, 1);
        if ($sChar == "%" && $iIndex + 1 < strlen($sFormat)) {
            $iIndex++;
            $sToken = substr($sFormat, $iIndex, 1);
            if ($sToken == "n") {
                $aLines[] = "";
            } elseif (isset($aFields[$sToken])) {
                appendAddressTemplateValue($aLines, $aFields[$sToken]);
            }
        } else {
            $aLines[count($aLines) - 1] .= $sChar;
        }
    }
    $aCleanLines = array();
    foreach ($aLines as $sLine) {
        $sLine = cleanAddressLine($sLine);
        if ($sLine != "") {
            $aCleanLines[] = $sLine;
        }
    }
    if ($blDisplayCountry) {
        $sCountry = is_array($aSettings) ? countryCodeToDisplayName($aAddress["country"], $aSettings) : countryCodeToName($aAddress["country"]);
        appendAddressCopyLine($aCleanLines, $sCountry);
    }
    return $aCleanLines;
}

function renderAddressText($aAddress, $aSettings = null) {
    return implode(", ", buildAddressLines($aAddress, "", $aSettings, true));
}

function renderAddressCopyText($aAddress, $sSubjectName = "", $aSettings = null) {
    $aLines = buildAddressLines($aAddress, $sSubjectName, $aSettings, true);
    return implode("\n", $aLines);
}

function renderAddressList($aAddresses, $blShowActions = true, $iSubjectId = 0, $sSubjectName = "", $blHasHiddenInactive = false, $aAddressDisplaySettings = null, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aAddresses) {
        return renderEmptySubjectItemCell($blShowActions, "js-add-subject-address", "New address", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"subject-item-list\">";
    $aCellCopyValues = array();
    $sHiddenInactiveAction = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    foreach ($aAddresses as $aAddress) {
        $sText = renderAddressText($aAddress, $aAddressDisplaySettings);
        $sNote = trim((string)$aAddress["note"]);
        $sCopyText = renderAddressCopyText($aAddress, $sSubjectName, $aAddressDisplaySettings);
        $aCellCopyValues[] = $sText . ($sNote != "" ? " (" . $sNote . ")" : "");
        $blIsPrimary = (int)$aAddress["is_primary"] == 1;
        $blIsActive = (int)$aAddress["is_active"] == 1;
        $sValueClass = (string)$aAddress["address_type"] == "main" ? " subject-address-main-value" : "";
        $sTimestampTooltipText = timestampTooltipText($aAddress);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"list-item-actions\">"
                . "<a href=\"#\" class=\"item-action js-edit-subject-address\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"item-action js-delete-subject-address\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"subject-item list-item subject-address-item" . ($blIsActive ? "" : " subject-item-inactive") . "\""
            . " data-address-id=\"" . html($aAddress["id"]) . "\""
            . " data-subject-id=\"" . html($aAddress["subject_id"]) . "\""
            . " data-address-type=\"" . html($aAddress["address_type"]) . "\""
            . " data-organization-name=\"" . html($aAddress["organization_name"]) . "\""
            . " data-department-name=\"" . html($aAddress["department_name"]) . "\""
            . " data-care-of=\"" . html($aAddress["care_of"]) . "\""
            . " data-street-name=\"" . html($aAddress["street_name"]) . "\""
            . " data-house-number=\"" . html($aAddress["house_number"]) . "\""
            . " data-evidence-number=\"" . html($aAddress["evidence_number"]) . "\""
            . " data-orientation-number=\"" . html($aAddress["orientation_number"]) . "\""
            . " data-orientation-suffix=\"" . html($aAddress["orientation_suffix"]) . "\""
            . " data-address-line2=\"" . html($aAddress["address_line2"]) . "\""
            . " data-city=\"" . html($aAddress["city"]) . "\""
            . " data-city-part=\"" . html($aAddress["city_part"]) . "\""
            . " data-postal-code=\"" . html(postalCodeDisplayValue($aAddress["country"], $aAddress["postal_code"])) . "\""
            . " data-region=\"" . html($aAddress["region"]) . "\""
            . " data-country=\"" . html($aAddress["country"]) . "\""
            . " data-note=\"" . html($sNote) . "\""
            . " data-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"subject-item-value" . $sValueClass . "\"" . $sTimestampTooltipAttribute . ">" . ($sText != "" ? html($sText) : $sEmptyValueEmoji) . "</span>"
            . renderCopyAction($sCopyText)
            . "<span class=\"subject-item-note\">" . ($sNote != "" ? "(" . html($sNote) . ")" : "") . "</span>"
            . "<span class=\"subject-item-flags\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? renderSubjectCellCopyAction($aCellCopyValues, true) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= renderAddSubjectItemAction("js-add-subject-address", "New address", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= renderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function renderGroupList($aGroups, $blShowActions = true, $iSubjectId = 0, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji;

    if (!$aGroups) {
        return $blShowActions && $blShowAddAction ? renderAddSubjectItemAction("js-add-subject-group", "Assign group", $iSubjectId) : $sEmptyValueEmoji;
    }
    $sHtml = "<div class=\"subject-item-list\">";
    $aCellCopyValues = array();
    foreach ($aGroups as $aGroup) {
        $aCellCopyValues[] = $aGroup["name"];
        $sTimestampTooltipText = timestampTooltipText($aGroup);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"list-item-actions\">"
                . "<a href=\"#\" class=\"item-action js-edit-subject-group\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"item-action js-delete-subject-group\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"subject-item list-item subject-group-item\""
            . " data-subject-id=\"" . html($aGroup["subject_id"]) . "\""
            . " data-group-id=\"" . html($aGroup["group_id"]) . "\""
            . " data-group-name=\"" . html($aGroup["name"]) . "\""
            . renderTimestampTooltipDataAttribute($aGroup) . ">"
            . "<span class=\"subject-item-value\"" . $sTimestampTooltipAttribute . ">" . html($aGroup["name"]) . "</span>"
            . renderCopyAction($aGroup["name"])
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? renderSubjectCellCopyAction($aCellCopyValues) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= renderAddSubjectItemAction("js-add-subject-group", "Assign group", $iSubjectId, $blCellCopyBeforeAddAction ? $sCellCopyAction : "", $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= $sCellCopyAction;
    }
    return $sHtml . "</div>";
}

function renderNoteList($aNotes, $blShowActions = true, $iSubjectId = 0, $blHasHiddenInactive = false, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aNotes) {
        return renderEmptySubjectItemCell($blShowActions, "js-add-subject-note", "New note", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"subject-item-list\">";
    $aCellCopyValues = array();
    $sHiddenInactiveAction = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    foreach ($aNotes as $aNote) {
        $aCellCopyValues[] = $aNote["note_text"];
        $blIsActive = (int)$aNote["is_active"] == 1;
        $blIsPrimary = (int)$aNote["is_primary"] == 1;
        $sTimestampTooltipText = timestampTooltipText($aNote);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"list-item-actions\">"
                . "<a href=\"#\" class=\"item-action js-edit-subject-note\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"item-action js-delete-subject-note\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"subject-item list-item subject-note-item" . ($blIsActive ? "" : " subject-item-inactive") . "\""
            . " data-note-id=\"" . html($aNote["id"]) . "\""
            . " data-subject-id=\"" . html($aNote["subject_id"]) . "\""
            . " data-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"subject-item-value\"" . $sTimestampTooltipAttribute . ">" . htmlMultiline($aNote["note_text"]) . "</span>"
            . renderCopyAction($aNote["note_text"])
            . "<span class=\"subject-item-flags\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . "<span class=\"subject-note-source\">" . html($aNote["note_text"]) . "</span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? renderSubjectCellCopyAction($aCellCopyValues) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= renderAddSubjectItemAction("js-add-subject-note", "New note", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= renderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function getSubjectTypes() {
    return array("person", "organization", "service", "other");
}

function getAddressTypes() {
    return array("main", "home", "cottage", "work", "office", "registered", "delivery", "billing", "foreign", "temporary", "old", "other");
}

function addressTypeLabel($sType) {
    switch ($sType) {
        case "main":
            return "Main";
        case "home":
            return "Home";
        case "cottage":
            return "Cottage";
        case "work":
            return "Work";
        case "office":
            return "Office";
        case "registered":
            return "Registered";
        case "delivery":
            return "Delivery";
        case "billing":
            return "Billing";
        case "foreign":
            return "Foreign";
        case "temporary":
            return "Temporary";
        case "old":
            return "Old";
        case "other":
            return "Other";
    }
    return "Other";
}

function getCountryCodes() {
    return array("AD", "AE", "AF", "AG", "AI", "AL", "AM", "AO", "AQ", "AR", "AS", "AT", "AU", "AW", "AX", "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BL", "BM", "BN", "BO", "BQ", "BR", "BS", "BT", "BV", "BW", "BY", "BZ", "CA", "CC", "CD", "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN", "CO", "CR", "CS", "CU", "CV", "CW", "CX", "CY", "CZ", "DE", "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG", "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FM", "FO", "FR", "GA", "GB", "GD", "GE", "GF", "GG", "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR", "GS", "GT", "GU", "GW", "GY", "HK", "HM", "HN", "HR", "HT", "HU", "ID", "IE", "IL", "IM", "IN", "IO", "IQ", "IR", "IS", "IT", "JE", "JM", "JO", "JP", "KE", "KG", "KH", "KI", "KM", "KN", "KP", "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI", "LK", "LR", "LS", "LT", "LU", "LV", "LY", "MA", "MC", "MD", "ME", "MF", "MG", "MH", "MK", "ML", "MM", "MN", "MO", "MP", "MQ", "MR", "MS", "MT", "MU", "MV", "MW", "MX", "MY", "MZ", "NA", "NC", "NE", "NF", "NG", "NI", "NL", "NO", "NP", "NR", "NU", "NZ", "OM", "PA", "PE", "PF", "PG", "PH", "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PW", "PY", "QA", "RE", "RO", "RS", "RU", "RW", "SA", "SB", "SC", "SD", "SE", "SG", "SH", "SI", "SJ", "SK", "SL", "SM", "SN", "SO", "SR", "SS", "ST", "SV", "SX", "SY", "SZ", "TC", "TD", "TF", "TG", "TH", "TJ", "TK", "TL", "TM", "TN", "TO", "TR", "TT", "TV", "TW", "TZ", "UA", "UG", "UM", "US", "UY", "UZ", "VA", "VC", "VE", "VG", "VI", "VN", "VU", "WF", "WS", "YE", "YT", "ZA", "ZM", "ZW");
}

function getCountryNames() {
    return array("AD" => "Andorra", "AE" => "United Arab Emirates", "AF" => "Afghanistan", "AG" => "Antigua & Barbuda", "AI" => "Anguilla", "AL" => "Albania", "AM" => "Armenia", "AO" => "Angola", "AQ" => "Antarctica", "AR" => "Argentina", "AS" => "American Samoa", "AT" => "Austria", "AU" => "Australia", "AW" => "Aruba", "AX" => "Åland Islands", "AZ" => "Azerbaijan", "BA" => "Bosnia & Herzegovina", "BB" => "Barbados", "BD" => "Bangladesh", "BE" => "Belgium", "BF" => "Burkina Faso", "BG" => "Bulgaria", "BH" => "Bahrain", "BI" => "Burundi", "BJ" => "Benin", "BL" => "St. Barthélemy", "BM" => "Bermuda", "BN" => "Brunei", "BO" => "Bolivia", "BQ" => "Caribbean Netherlands", "BR" => "Brazil", "BS" => "Bahamas", "BT" => "Bhutan", "BV" => "Bouvet Island", "BW" => "Botswana", "BY" => "Belarus", "BZ" => "Belize", "CA" => "Canada", "CC" => "Cocos (Keeling) Islands", "CD" => "Congo - Kinshasa", "CF" => "Central African Republic", "CG" => "Congo - Brazzaville", "CH" => "Switzerland", "CI" => "Côte d’Ivoire", "CK" => "Cook Islands", "CL" => "Chile", "CM" => "Cameroon", "CN" => "China", "CO" => "Colombia", "CR" => "Costa Rica", "CS" => "Czechoslovakia", "CU" => "Cuba", "CV" => "Cape Verde", "CW" => "Curaçao", "CX" => "Christmas Island", "CY" => "Cyprus", "CZ" => "Czechia", "DE" => "Germany", "DJ" => "Djibouti", "DK" => "Denmark", "DM" => "Dominica", "DO" => "Dominican Republic", "DZ" => "Algeria", "EC" => "Ecuador", "EE" => "Estonia", "EG" => "Egypt", "EH" => "Western Sahara", "ER" => "Eritrea", "ES" => "Spain", "ET" => "Ethiopia", "FI" => "Finland", "FJ" => "Fiji", "FK" => "Falkland Islands", "FM" => "Micronesia", "FO" => "Faroe Islands", "FR" => "France", "GA" => "Gabon", "GB" => "United Kingdom", "GD" => "Grenada", "GE" => "Georgia", "GF" => "French Guiana", "GG" => "Guernsey", "GH" => "Ghana", "GI" => "Gibraltar", "GL" => "Greenland", "GM" => "Gambia", "GN" => "Guinea", "GP" => "Guadeloupe", "GQ" => "Equatorial Guinea", "GR" => "Greece", "GS" => "South Georgia & South Sandwich Islands", "GT" => "Guatemala", "GU" => "Guam", "GW" => "Guinea-Bissau", "GY" => "Guyana", "HK" => "Hong Kong SAR China", "HM" => "Heard & McDonald Islands", "HN" => "Honduras", "HR" => "Croatia", "HT" => "Haiti", "HU" => "Hungary", "ID" => "Indonesia", "IE" => "Ireland", "IL" => "Israel", "IM" => "Isle of Man", "IN" => "India", "IO" => "British Indian Ocean Territory", "IQ" => "Iraq", "IR" => "Iran", "IS" => "Iceland", "IT" => "Italy", "JE" => "Jersey", "JM" => "Jamaica", "JO" => "Jordan", "JP" => "Japan", "KE" => "Kenya", "KG" => "Kyrgyzstan", "KH" => "Cambodia", "KI" => "Kiribati", "KM" => "Comoros", "KN" => "St. Kitts & Nevis", "KP" => "North Korea", "KR" => "South Korea", "KW" => "Kuwait", "KY" => "Cayman Islands", "KZ" => "Kazakhstan", "LA" => "Laos", "LB" => "Lebanon", "LC" => "St. Lucia", "LI" => "Liechtenstein", "LK" => "Sri Lanka", "LR" => "Liberia", "LS" => "Lesotho", "LT" => "Lithuania", "LU" => "Luxembourg", "LV" => "Latvia", "LY" => "Libya", "MA" => "Morocco", "MC" => "Monaco", "MD" => "Moldova", "ME" => "Montenegro", "MF" => "St. Martin", "MG" => "Madagascar", "MH" => "Marshall Islands", "MK" => "North Macedonia", "ML" => "Mali", "MM" => "Myanmar (Burma)", "MN" => "Mongolia", "MO" => "Macao SAR China", "MP" => "Northern Mariana Islands", "MQ" => "Martinique", "MR" => "Mauritania", "MS" => "Montserrat", "MT" => "Malta", "MU" => "Mauritius", "MV" => "Maldives", "MW" => "Malawi", "MX" => "Mexico", "MY" => "Malaysia", "MZ" => "Mozambique", "NA" => "Namibia", "NC" => "New Caledonia", "NE" => "Niger", "NF" => "Norfolk Island", "NG" => "Nigeria", "NI" => "Nicaragua", "NL" => "Netherlands", "NO" => "Norway", "NP" => "Nepal", "NR" => "Nauru", "NU" => "Niue", "NZ" => "New Zealand", "OM" => "Oman", "PA" => "Panama", "PE" => "Peru", "PF" => "French Polynesia", "PG" => "Papua New Guinea", "PH" => "Philippines", "PK" => "Pakistan", "PL" => "Poland", "PM" => "St. Pierre & Miquelon", "PN" => "Pitcairn Islands", "PR" => "Puerto Rico", "PS" => "Palestinian Territories", "PT" => "Portugal", "PW" => "Palau", "PY" => "Paraguay", "QA" => "Qatar", "RE" => "Réunion", "RO" => "Romania", "RS" => "Serbia", "RU" => "Russia", "RW" => "Rwanda", "SA" => "Saudi Arabia", "SB" => "Solomon Islands", "SC" => "Seychelles", "SD" => "Sudan", "SE" => "Sweden", "SG" => "Singapore", "SH" => "St. Helena", "SI" => "Slovenia", "SJ" => "Svalbard & Jan Mayen", "SK" => "Slovakia", "SL" => "Sierra Leone", "SM" => "San Marino", "SN" => "Senegal", "SO" => "Somalia", "SR" => "Suriname", "SS" => "South Sudan", "ST" => "São Tomé & Príncipe", "SV" => "El Salvador", "SX" => "Sint Maarten", "SY" => "Syria", "SZ" => "Eswatini", "TC" => "Turks & Caicos Islands", "TD" => "Chad", "TF" => "French Southern Territories", "TG" => "Togo", "TH" => "Thailand", "TJ" => "Tajikistan", "TK" => "Tokelau", "TL" => "Timor-Leste", "TM" => "Turkmenistan", "TN" => "Tunisia", "TO" => "Tonga", "TR" => "Türkiye", "TT" => "Trinidad & Tobago", "TV" => "Tuvalu", "TW" => "Taiwan", "TZ" => "Tanzania", "UA" => "Ukraine", "UG" => "Uganda", "UM" => "U.S. Outlying Islands", "US" => "United States", "UY" => "Uruguay", "UZ" => "Uzbekistan", "VA" => "Vatican City", "VC" => "St. Vincent & Grenadines", "VE" => "Venezuela", "VG" => "British Virgin Islands", "VI" => "U.S. Virgin Islands", "VN" => "Vietnam", "VU" => "Vanuatu", "WF" => "Wallis & Futuna", "WS" => "Samoa", "YE" => "Yemen", "YT" => "Mayotte", "ZA" => "South Africa", "ZM" => "Zambia", "ZW" => "Zimbabwe");
}

function countryCodeToName($sCountry) {
    $sCountry = strtoupper(trim((string)$sCountry));
    $aCountryNames = getCountryNames();
    return isset($aCountryNames[$sCountry]) ? $aCountryNames[$sCountry] : $sCountry;
}

function countryDashPattern() {
    return "(?:-|\\x{2010}|\\x{2011}|\\x{2012}|\\x{2013}|\\x{2014}|\\x{2015}|\\x{2212})";
}

function normalizeCountrySearchText($sCountry) {
    $sCountry = html_entity_decode((string)$sCountry, ENT_QUOTES | ENT_HTML5, "UTF-8");
    $sCountry = str_replace(array("\xc2\xa0", "\xe2\x80\x8b", "&"), array(" ", "", " and "), $sCountry);
    $sCountry = preg_replace("/" . countryDashPattern() . "/u", " ", $sCountry);
    $aCzechChars = array(
        html_entity_decode("&#193;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#201;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#205;", ENT_QUOTES, "UTF-8") => "i",
        html_entity_decode("&#211;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#218;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#221;", ENT_QUOTES, "UTF-8") => "y",
        html_entity_decode("&#192;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#194;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#195;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#196;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#197;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#198;", ENT_QUOTES, "UTF-8") => "ae",
        html_entity_decode("&#199;", ENT_QUOTES, "UTF-8") => "c",
        html_entity_decode("&#200;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#202;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#203;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#204;", ENT_QUOTES, "UTF-8") => "i",
        html_entity_decode("&#206;", ENT_QUOTES, "UTF-8") => "i",
        html_entity_decode("&#207;", ENT_QUOTES, "UTF-8") => "i",
        html_entity_decode("&#209;", ENT_QUOTES, "UTF-8") => "n",
        html_entity_decode("&#210;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#212;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#213;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#214;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#216;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#217;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#219;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#220;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#225;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#224;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#226;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#227;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#228;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#229;", ENT_QUOTES, "UTF-8") => "a",
        html_entity_decode("&#230;", ENT_QUOTES, "UTF-8") => "ae",
        html_entity_decode("&#231;", ENT_QUOTES, "UTF-8") => "c",
        html_entity_decode("&#233;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#232;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#234;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#235;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#237;", ENT_QUOTES, "UTF-8") => "i",
        html_entity_decode("&#236;", ENT_QUOTES, "UTF-8") => "i",
        html_entity_decode("&#238;", ENT_QUOTES, "UTF-8") => "i",
        html_entity_decode("&#239;", ENT_QUOTES, "UTF-8") => "i",
        html_entity_decode("&#241;", ENT_QUOTES, "UTF-8") => "n",
        html_entity_decode("&#243;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#242;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#244;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#245;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#246;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#248;", ENT_QUOTES, "UTF-8") => "o",
        html_entity_decode("&#250;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#249;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#251;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#252;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#253;", ENT_QUOTES, "UTF-8") => "y",
        html_entity_decode("&#255;", ENT_QUOTES, "UTF-8") => "y",
        html_entity_decode("&#338;", ENT_QUOTES, "UTF-8") => "oe",
        html_entity_decode("&#339;", ENT_QUOTES, "UTF-8") => "oe",
        html_entity_decode("&#376;", ENT_QUOTES, "UTF-8") => "y",
        html_entity_decode("&#223;", ENT_QUOTES, "UTF-8") => "ss",
        html_entity_decode("&#268;", ENT_QUOTES, "UTF-8") => "c",
        html_entity_decode("&#269;", ENT_QUOTES, "UTF-8") => "c",
        html_entity_decode("&#270;", ENT_QUOTES, "UTF-8") => "d",
        html_entity_decode("&#271;", ENT_QUOTES, "UTF-8") => "d",
        html_entity_decode("&#282;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#283;", ENT_QUOTES, "UTF-8") => "e",
        html_entity_decode("&#327;", ENT_QUOTES, "UTF-8") => "n",
        html_entity_decode("&#328;", ENT_QUOTES, "UTF-8") => "n",
        html_entity_decode("&#344;", ENT_QUOTES, "UTF-8") => "r",
        html_entity_decode("&#345;", ENT_QUOTES, "UTF-8") => "r",
        html_entity_decode("&#352;", ENT_QUOTES, "UTF-8") => "s",
        html_entity_decode("&#353;", ENT_QUOTES, "UTF-8") => "s",
        html_entity_decode("&#356;", ENT_QUOTES, "UTF-8") => "t",
        html_entity_decode("&#357;", ENT_QUOTES, "UTF-8") => "t",
        html_entity_decode("&#366;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#367;", ENT_QUOTES, "UTF-8") => "u",
        html_entity_decode("&#381;", ENT_QUOTES, "UTF-8") => "z",
        html_entity_decode("&#382;", ENT_QUOTES, "UTF-8") => "z"
    );
    $sCountry = strtr($sCountry, $aCzechChars);
    if (function_exists("iconv")) {
        $sConverted = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $sCountry);
        if ($sConverted !== false) {
            $sCountry = $sConverted;
        }
    }
    $sCountry = strtolower($sCountry);
    $sCountry = preg_replace("/[^a-z0-9]+/", " ", $sCountry);
    return trim(preg_replace("/ +/", " ", $sCountry));
}

function getCountryNameAliases() {
    return array(
        "CS" => array(
            "Czechoslovakia",
            "Ceskoslovensko",
            html_entity_decode("&#268;eskoslovensko", ENT_QUOTES, "UTF-8")
        ),
        "CI" => array(
            "Ivory Coast"
        ),
        "CZ" => array(
            "Czech Republic",
            "Cesko",
            "Ceska republika",
            html_entity_decode("&#268;esko", ENT_QUOTES, "UTF-8"),
            html_entity_decode("&#268;esk&#225; republika", ENT_QUOTES, "UTF-8")
        ),
        "GB" => array(
            "Great Britain",
            "Britain",
            "UK",
            "U.K."
        ),
        "US" => array(
            "United States of America",
            "USA",
            "U.S.A."
        )
    );
}

function countryAliasToCode($sCountry) {
    $sCountryNormalized = normalizeCountrySearchText($sCountry);
    if ($sCountryNormalized == "") {
        return "";
    }
    foreach (getCountryNameAliases() as $sCode => $aAliases) {
        foreach ($aAliases as $sAlias) {
            if ($sCountryNormalized == normalizeCountrySearchText($sAlias)) {
                return $sCode;
            }
        }
    }
    return "";
}

function delimitedCountryCode($sCountry, $aCountryCodes) {
    $sPattern = countryDashPattern();
    if (preg_match("/^\\s*([A-Za-z]{2})\\s*" . $sPattern . "\\s*(.*?)\\s*$/u", (string)$sCountry, $aMatches)) {
        $sCountryCode = strtoupper($aMatches[1]);
        if (in_array($sCountryCode, $aCountryCodes, true)) {
            return $sCountryCode;
        }
    }
    if (preg_match("/^\\s*(.*?)\\s*" . $sPattern . "\\s*([A-Za-z]{2})\\s*$/u", (string)$sCountry, $aMatches)) {
        $sCountryCode = strtoupper($aMatches[2]);
        if (in_array($sCountryCode, $aCountryCodes, true)) {
            return $sCountryCode;
        }
    }
    return "";
}

function countryNameToCode($sCountry) {
    $sCountry = trim((string)$sCountry);
    $sCountryUpper = strtoupper($sCountry);
    $sCountryNormalized = normalizeCountrySearchText($sCountry);
    $aCountryCodes = getCountryCodes();
    $aCountryNames = getCountryNames();
    if ($sCountry == "") {
        return "";
    }
    $sCountryCode = delimitedCountryCode($sCountry, $aCountryCodes);
    if ($sCountryCode != "") {
        return $sCountryCode;
    }
    if (preg_match("/^[A-Z]{2}$/", $sCountryUpper) && in_array($sCountryUpper, $aCountryCodes, true)) {
        return $sCountryUpper;
    }
    $sCountryCode = countryAliasToCode($sCountry);
    if ($sCountryCode != "") {
        return $sCountryCode;
    }
    foreach ($aCountryNames as $sCode => $sName) {
        if ($sCountryNormalized == normalizeCountrySearchText($sName)) {
            return $sCode;
        }
    }
    return $sCountry;
}

function renderCountryDatalist($sId = "country-list") {
    $sHtml = "<datalist id=\"" . html($sId) . "\">\n";

    foreach (getCountryNames() as $sCode => $sName) {
        $sHtml .= "    <option value=\"" . html($sCode) . " &#8212; " . html($sName) . "\"></option>\n";
    }
    return $sHtml . "  </datalist>\n";
}

function countryCodeToDisplayName($sCountry, $aSettings = null) {
    $sCountry = strtoupper(trim((string)$sCountry));
    if ($sCountry == "") {
        return "";
    }
    if ($sCountry == "CS" && is_array($aSettings) && !empty($aSettings["show_czechia_country_in_czech"])) {
        return "Československo";
    }
    if ($sCountry == "CZ" && is_array($aSettings)) {
        if (empty($aSettings["show_czechia_country"])) {
            return "";
        }
        if (!empty($aSettings["show_czechia_country_in_czech"])) {
            return !empty($aSettings["show_czechia_country_as_czech_republic"]) ? "Česká republika" : "Česko";
        }
        if (!empty($aSettings["show_czechia_country_as_czech_republic"])) {
            return "Czech Republic";
        }
    }
    return countryCodeToName($sCountry);
}

function dbValue($mValue) {
    $sValue = trim((string)$mValue);
    return $sValue != "" ? $sValue : null;
}

function payloadValue($aPayload, $sName) {
    return isset($aPayload[$sName]) ? trim((string)$aPayload[$sName]) : "";
}

function payloadFlag($aPayload, $sName) {
    return isset($aPayload[$sName]) && ((string)$aPayload[$sName] == "1" || $aPayload[$sName] === 1 || $aPayload[$sName] === true) ? 1 : 0;
}

function getCountrySettingsDefaults() {
    return array(
        "show_czechia_country" => 1,
        "show_czechia_country_in_czech" => 1,
        "show_czechia_country_as_czech_republic" => 1
    );
}

function applyCountrySettings($aSettings) {
    $aCountrySettingsDefaults = getCountrySettingsDefaults();
    if (!isset($_SESSION["ex_country_settings"]) || !is_array($_SESSION["ex_country_settings"])) {
        $_SESSION["ex_country_settings"] = array();
    }
    foreach ($aCountrySettingsDefaults as $sCountrySettingName => $iCountrySettingDefault) {
        if (isset($_SESSION["ex_country_settings"][$sCountrySettingName])) {
            $aSettings[$sCountrySettingName] = (int)$_SESSION["ex_country_settings"][$sCountrySettingName] == 1 ? 1 : 0;
        } else {
            $aSettings[$sCountrySettingName] = $iCountrySettingDefault;
        }
    }
    return $aSettings;
}

function saveCountrySettings($aSettings, $aPayload) {
    $aCountrySettingsDefaults = getCountrySettingsDefaults();
    $aPreviousCountrySettings = applyCountrySettings(array());
    $aCountrySettings = array();
    foreach ($aCountrySettingsDefaults as $sCountrySettingName => $iCountrySettingDefault) {
        $aCountrySettings[$sCountrySettingName] = isset($aPayload[$sCountrySettingName]) && (string)$aPayload[$sCountrySettingName] == "1" ? 1 : 0;
    }
    if (!$aCountrySettings["show_czechia_country"]) {
        $aCountrySettings["show_czechia_country_in_czech"] = isset($aPreviousCountrySettings["show_czechia_country_in_czech"]) ? $aPreviousCountrySettings["show_czechia_country_in_czech"] : $aCountrySettingsDefaults["show_czechia_country_in_czech"];
        $aCountrySettings["show_czechia_country_as_czech_republic"] = isset($aPreviousCountrySettings["show_czechia_country_as_czech_republic"]) ? $aPreviousCountrySettings["show_czechia_country_as_czech_republic"] : $aCountrySettingsDefaults["show_czechia_country_as_czech_republic"];
    }
    $_SESSION["ex_country_settings"] = $aCountrySettings;
    foreach ($aCountrySettings as $sCountrySettingName => $iCountrySettingValue) {
        $aSettings[$sCountrySettingName] = $iCountrySettingValue;
    }
    return $aSettings;
}

function removeCountrySettings($aSettings) {
    foreach (getCountrySettingsDefaults() as $sCountrySettingName => $iCountrySettingDefault) {
        unset($aSettings[$sCountrySettingName]);
    }
    return $aSettings;
}

function renderSettingsScopeNote() {
    return "<p class=\"index-settings-note\">Options above the line apply only to this listing. Country options below the line are shared across the EX subproject.</p>";
}

function normalizeBirthNumber($mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return "";
    }
    $sDigits = preg_replace("/[^0-9]/", "", $sValue);
    $iLength = strlen($sDigits);
    if ($iLength !== 9 && $iLength !== 10) {
        return false;
    }
    return substr($sDigits, 0, 6) . "/" . substr($sDigits, 6);
}

function birthNumberModulo($sDigits, $iDivisor) {
    $iModulo = 0;
    for ($iI = 0; $iI < strlen($sDigits); $iI++) {
        $iModulo = ($iModulo * 10 + (int)$sDigits[$iI]) % $iDivisor;
    }
    return $iModulo;
}

function analyzeBirthNumber($mValue) {
    $sNormalized = normalizeBirthNumber($mValue);
    if ($sNormalized == "") {
        return array("normalized" => "", "valid" => true, "birth_date" => "");
    }
    if ($sNormalized === false) {
        return array("normalized" => false, "valid" => false, "birth_date" => "");
    }
    $sDigits = preg_replace("/[^0-9]/", "", $sNormalized);
    $iLength = strlen($sDigits);
    $iYear = (int)substr($sDigits, 0, 2);
    $iMonth = (int)substr($sDigits, 2, 2);
    $iDay = (int)substr($sDigits, 4, 2);
    $sEnding = substr($sDigits, 6);
    $blValid = true;
    $sBirthDate = "";

    if ($iLength === 9 && $sEnding == "000") {
        $blValid = false;
    }
    if ($iMonth > 50) {
        $iMonth -= 50;
    }
    if ($iMonth > 20) {
        $iMonth -= 20;
    }
    if ($iLength === 9) {
        $iFullYear = 1900 + $iYear;
        if ($iYear > 53) {
            $blValid = false;
        }
    } else {
        $iFullYear = $iYear > 53 ? 1900 + $iYear : 2000 + $iYear;
    }
    if ($iMonth < 1 || $iMonth > 12 || !checkdate($iMonth, $iDay, $iFullYear)) {
        $blValid = false;
    } else {
        $sBirthDate = sprintf("%04d-%02d-%02d", $iFullYear, $iMonth, $iDay);
    }
    if ($iLength === 10 && birthNumberModulo($sDigits, 11) !== 0) {
        $blValid = false;
    }
    return array("normalized" => $sNormalized, "valid" => $blValid, "birth_date" => $sBirthDate);
}

function isValidBirthNumber($mValue) {
    $aAnalysis = analyzeBirthNumber($mValue);
    return !empty($aAnalysis["valid"]);
}

function birthNumberBirthDate($mValue) {
    $aAnalysis = analyzeBirthNumber($mValue);
    return isset($aAnalysis["birth_date"]) ? $aAnalysis["birth_date"] : "";
}

function isInvalidBirthNumber($mValue) {
    $sValue = trim((string)$mValue);
    return $sValue != "" && !isValidBirthNumber($sValue);
}

function birthNumberClass($mValue, $sClass = "") {
    $sClass = trim((string)$sClass);
    if (isInvalidBirthNumber($mValue)) {
        $sClass = trim($sClass . " invalid-birth-number");
    }
    return $sClass;
}

function birthDateClass($mBirthNumber, $mBirthDate, $sClass = "") {
    $sClass = trim((string)$sClass);
    $sBirthDate = trim((string)$mBirthDate);
    $sBirthNumberDate = birthNumberBirthDate($mBirthNumber);
    if ($sBirthDate != "" && $sBirthNumberDate != "" && $sBirthDate != $sBirthNumberDate) {
        $sClass = trim($sClass . " invalid-birth-number");
    }
    return $sClass;
}

function dateFromIsoDate($mValue) {
    $sValue = trim((string)$mValue);
    $oDate = false;
    if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $sValue) || $sValue == "0000-00-00") {
        return false;
    }
    $oDate = DateTimeImmutable::createFromFormat("!Y-m-d", $sValue);
    if (!$oDate || $oDate->format("Y-m-d") != $sValue) {
        return false;
    }
    return $oDate;
}

function ageInYears($mStartDate, $mEndDate = null) {
    $oStartDate = dateFromIsoDate($mStartDate);
    $oEndDate = $mEndDate === null ? new DateTimeImmutable("today") : dateFromIsoDate($mEndDate);
    if (!$oStartDate || !$oEndDate || $oEndDate < $oStartDate) {
        return null;
    }
    return (int)$oStartDate->diff($oEndDate)->y;
}

function subjectAgeLabel($iAge, $sPrefix = "") {
    if ($iAge === null) {
        return "";
    }
    return ($sPrefix != "" ? $sPrefix . " " : "") . ((int)$iAge == 1 ? "1 year" : (string)(int)$iAge . " years");
}

function renderSubjectDateValue($mDate, $sAgeLabel = "") {
    $sHtml = htmlValue($mDate);
    if ($sAgeLabel != "") {
        $sHtml .= "<span class=\"subject-date-age\">" . html($sAgeLabel) . "</span>";
    }
    return $sHtml;
}

function renderBirthNumberValue($mValue) {
    $sValue = trim((string)$mValue);
    $sNormalized = normalizeBirthNumber($sValue);
    if ($sNormalized !== false) {
        $sValue = $sNormalized;
    }
    return htmlValue($sValue);
}

function fetchSubjectRows($oPdo, $iSubjectId = 0, $aFilterSql = null) {
    $sPersonDisplayBase = "NULLIF(TRIM(CONCAT_WS(' ', NULLIF(p.title_before, ''), NULLIF(p.first_name, ''), NULLIF(p.middle_name, ''), NULLIF(p.last_name, ''))), '')";
    $sPersonDisplayName = "NULLIF(TRIM(CONCAT(COALESCE(" . $sPersonDisplayBase . ", ''), IF(NULLIF(p.title_after, '') IS NULL, '', IF(" . $sPersonDisplayBase . " IS NULL, p.title_after, CONCAT(', ', p.title_after))))), '')";
    $sPersonSortName = "NULLIF(TRIM(CONCAT_WS(' ', NULLIF(p.last_name, ''), NULLIF(p.first_name, ''))), '')";
    $sContactTypeJoinSql = " LEFT JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id";
    $sContactTypeNameSql = "COALESCE(ct.name, '')";
    $sSql = "SELECT s.id AS subject_id, s.subject_type, COALESCE(IF(s.subject_type = 'person', " . $sPersonDisplayName . ", NULL), NULLIF(subn.name, ''), n.primary_nickname, c.primary_contact, 'Unnamed subject') AS subject_name, COALESCE(IF(s.subject_type = 'person', " . $sPersonSortName . ", NULL), NULLIF(subn.name, ''), n.primary_nickname, c.primary_contact, 'Unnamed subject') AS subject_sort_name, s.is_active, s.created_at, s.updated_at, p.title_before, p.first_name, p.middle_name, p.last_name, p.title_after, p.birth_name, p.birth_number, p.birth_date, p.death_date, p.birthday_served_at, p.inter_served_at, c.contacts, a.addresses, n.nicknames, g.group_names, sn.notes FROM ex_subjects AS s
        LEFT JOIN ex_persons AS p ON p.subject_id = s.id
        LEFT JOIN ex_subject_names AS subn ON subn.subject_id = s.id
        LEFT JOIN (SELECT sc.subject_id, GROUP_CONCAT(CONCAT(" . $sContactTypeNameSql . ", ': ', c.contact_value, IF(sc.note IS NULL OR sc.note = '', '', CONCAT(' (', sc.note, ')'))) ORDER BY sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC SEPARATOR '\n') AS contacts, SUBSTRING_INDEX(GROUP_CONCAT(c.contact_value ORDER BY sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC SEPARATOR '\n'), '\n', 1) AS primary_contact FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id" . $sContactTypeJoinSql . " GROUP BY sc.subject_id) AS c ON c.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(NULLIF(CONCAT_WS(', ', NULLIF(TRIM(CONCAT_WS(' ', NULLIF(street_name, ''), NULLIF(CONCAT_WS('/', NULLIF(house_number, ''), NULLIF(orientation_number, '')), ''))), ''), NULLIF(city, ''), NULLIF(postal_code, ''), NULLIF(country, '')), '') ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n') AS addresses FROM ex_subject_addresses GROUP BY subject_id) AS a ON a.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(CONCAT(nickname, IF(context IS NULL OR context = '', '', CONCAT(' [', context, ']')), IF(note IS NULL OR note = '', '', CONCAT(' (', note, ')'))) ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n') AS nicknames, SUBSTRING_INDEX(GROUP_CONCAT(nickname ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n'), '\n', 1) AS primary_nickname FROM ex_subject_nicknames GROUP BY subject_id) AS n ON n.subject_id = s.id
        LEFT JOIN (SELECT sg.subject_id, GROUP_CONCAT(g.name ORDER BY g.`order` ASC, g.id ASC SEPARATOR '\n') AS group_names FROM ex_subject_groups AS sg INNER JOIN ex_groups AS g ON g.id = sg.group_id GROUP BY sg.subject_id) AS g ON g.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(note_text ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n') AS notes FROM ex_subject_notes GROUP BY subject_id) AS sn ON sn.subject_id = s.id";
    if ($iSubjectId > 0) {
        $sSql .= " WHERE s.id = :subject_id";
    }
    if (is_array($aFilterSql) && !empty($aFilterSql["sql"])) {
        $sSql .= " HAVING " . $aFilterSql["sql"];
    }
    $sSql .= " ORDER BY subject_sort_name ASC, s.subject_type ASC";
    $oStatement = $oPdo->prepare($sSql);
    $aParams = is_array($aFilterSql) && isset($aFilterSql["params"]) && is_array($aFilterSql["params"]) ? $aFilterSql["params"] : array();
    if ($iSubjectId > 0) {
        $aParams["subject_id"] = $iSubjectId;
        $oStatement->execute($aParams);
    } else {
        $oStatement->execute($aParams);
    }
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSubjectContacts($oPdo, $iSubjectId = 0) {
    $aContacts = array();
    $sContactTypeJoinSql = " LEFT JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id";
    $sContactTypeNameSql = "COALESCE(ct.name, '')";
    $sSql = "SELECT sc.id AS subject_contact_id, sc.subject_id, sc.contact_id, sc.is_primary, sc.is_active, sc.note, c.contact_type_id, COALESCE(ct.contact_type, '') AS contact_type, " . $sContactTypeNameSql . " AS contact_type_name, c.contact_value, c.created_at, c.updated_at FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id" . $sContactTypeJoinSql;
    if ($iSubjectId > 0) {
        $sSql .= " WHERE sc.subject_id = :subject_id";
    }
    $sSql .= " ORDER BY sc.subject_id ASC, sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC";
    $oStatement = $oPdo->prepare($sSql);
    if ($iSubjectId > 0) {
        $oStatement->execute(array("subject_id" => $iSubjectId));
    } else {
        $oStatement->execute();
    }
    while ($aContact = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $iCurrentSubjectId = (int)$aContact["subject_id"];
        if (!isset($aContacts[$iCurrentSubjectId])) {
            $aContacts[$iCurrentSubjectId] = array();
        }
        $aContacts[$iCurrentSubjectId][] = $aContact;
    }
    return $aContacts;
}

function fetchSubjectNicknames($oPdo, $iSubjectId = 0) {
    $aNicknames = array();
    $sSql = "SELECT id, subject_id, nickname, context, is_primary, is_active, note, created_at, updated_at FROM ex_subject_nicknames";
    if ($iSubjectId > 0) {
        $sSql .= " WHERE subject_id = :subject_id";
    }
    $sSql .= " ORDER BY subject_id ASC, is_active DESC, is_primary DESC, id ASC";
    $oStatement = $oPdo->prepare($sSql);
    if ($iSubjectId > 0) {
        $oStatement->execute(array("subject_id" => $iSubjectId));
    } else {
        $oStatement->execute();
    }
    while ($aNickname = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $iCurrentSubjectId = (int)$aNickname["subject_id"];
        if (!isset($aNicknames[$iCurrentSubjectId])) {
            $aNicknames[$iCurrentSubjectId] = array();
        }
        $aNicknames[$iCurrentSubjectId][] = $aNickname;
    }
    return $aNicknames;
}

function fetchSubjectAddresses($oPdo, $iSubjectId = 0) {
    $aAddresses = array();
    $sSql = "SELECT id, subject_id, address_type, organization_name, department_name, care_of, street_name, house_number, evidence_number, orientation_number, orientation_suffix, address_line2, city, city_part, postal_code, region, country, is_primary, is_active, note, created_at, updated_at FROM ex_subject_addresses";
    if ($iSubjectId > 0) {
        $sSql .= " WHERE subject_id = :subject_id";
    }
    $sSql .= " ORDER BY subject_id ASC, is_active DESC, is_primary DESC, id ASC";
    $oStatement = $oPdo->prepare($sSql);
    if ($iSubjectId > 0) {
        $oStatement->execute(array("subject_id" => $iSubjectId));
    } else {
        $oStatement->execute();
    }
    while ($aAddress = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $iCurrentSubjectId = (int)$aAddress["subject_id"];
        if (!isset($aAddresses[$iCurrentSubjectId])) {
            $aAddresses[$iCurrentSubjectId] = array();
        }
        $aAddresses[$iCurrentSubjectId][] = $aAddress;
    }
    return $aAddresses;
}

function fetchSubjectGroups($oPdo, $iSubjectId = 0) {
    $aGroups = array();
    $sSql = "SELECT sg.subject_id, sg.group_id, g.name, g.created_at, g.updated_at FROM ex_subject_groups AS sg INNER JOIN ex_groups AS g ON g.id = sg.group_id";
    if ($iSubjectId > 0) {
        $sSql .= " WHERE sg.subject_id = :subject_id";
    }
    $sSql .= " ORDER BY sg.subject_id ASC, g.`order` ASC, g.id ASC";
    $oStatement = $oPdo->prepare($sSql);
    if ($iSubjectId > 0) {
        $oStatement->execute(array("subject_id" => $iSubjectId));
    } else {
        $oStatement->execute();
    }
    while ($aGroup = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $iCurrentSubjectId = (int)$aGroup["subject_id"];
        if (!isset($aGroups[$iCurrentSubjectId])) {
            $aGroups[$iCurrentSubjectId] = array();
        }
        $aGroups[$iCurrentSubjectId][] = $aGroup;
    }
    return $aGroups;
}

function fetchGroupAjaxData($oPdo, $iGroupId, $sName = "") {
    $oStatement = $oPdo->prepare("SELECT id AS group_id, name, created_at, updated_at FROM ex_groups WHERE id = :id");
    $oStatement->execute(array("id" => $iGroupId));
    $aGroup = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aGroup) {
        return array(
            "group_id" => $iGroupId,
            "name" => $sName
        );
    }
    return array(
        "group_id" => (int)$aGroup["group_id"],
        "name" => (string)$aGroup["name"],
        "timestamp_tooltip" => timestampTooltipText($aGroup)
    );
}

function fetchGroups($oPdo) {
    $oStatement = $oPdo->query("SELECT id, name, legacy_id, `order` FROM ex_groups ORDER BY `order` ASC, id ASC");
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function fetchGroupAdminRows($oPdo, $iGroupId = 0) {
    $sSql = "SELECT g.id, g.name, g.`order`, g.created_at, g.updated_at, COUNT(DISTINCT sg.subject_id) AS subject_count, GROUP_CONCAT(DISTINCT p.permission_key ORDER BY p.permission_key ASC SEPARATOR ',') AS permission_keys, GROUP_CONCAT(DISTINCT p.name ORDER BY p.permission_key ASC SEPARATOR ',') AS permission_names FROM ex_groups AS g LEFT JOIN ex_subject_groups AS sg ON sg.group_id = g.id LEFT JOIN ex_group_permissions AS gp ON gp.group_id = g.id AND gp.is_allowed = 1 LEFT JOIN ex_permissions AS p ON p.id = gp.permission_id AND p.is_active = 1";
    if ($iGroupId > 0) {
        $sSql .= " WHERE g.id = :id";
    }
    $sSql .= " GROUP BY g.id, g.name, g.`order`, g.created_at, g.updated_at";
    if ($iGroupId < 1) {
        $sSql .= " ORDER BY g.`order` ASC, g.id ASC";
    }
    $oStatement = $oPdo->prepare($sSql);
    if ($iGroupId > 0) {
        $oStatement->execute(array("id" => $iGroupId));
    } else {
        $oStatement->execute();
    }
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function fetchPortalPermissions($oPdo) {
    $oStatement = $oPdo->query("SELECT permission_key, name, note FROM ex_permissions WHERE is_active = 1 ORDER BY permission_key ASC");
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSubjectPortalUser($oPdo, $iSubjectId) {
    $aPortalUser = array(
        "has_user" => 0,
        "user_name" => "",
        "is_active" => 1,
        "direct_permission_keys" => array(),
        "effective_permission_keys" => array()
    );
    $oStatement = $oPdo->prepare("SELECT id, user_name, is_active, created_at, updated_at FROM ex_users WHERE subject_id = :subject_id");
    $oStatement->execute(array("subject_id" => $iSubjectId));
    $aUser = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aUser) {
        return $aPortalUser;
    }
    $aPortalUser["has_user"] = 1;
    $aPortalUser["user_name"] = (string)$aUser["user_name"];
    $aPortalUser["is_active"] = (int)$aUser["is_active"];
    $aPortalUser["created_at"] = (string)$aUser["created_at"];
    $aPortalUser["updated_at"] = (string)$aUser["updated_at"];
    $aPortalUser["timestamp_tooltip"] = timestampTooltipText($aUser);
    $oStatement = $oPdo->prepare("SELECT p.permission_key FROM ex_user_permissions AS up INNER JOIN ex_permissions AS p ON p.id = up.permission_id WHERE up.user_id = :user_id AND up.is_allowed = 1 AND p.is_active = 1 ORDER BY p.permission_key ASC");
    $oStatement->execute(array("user_id" => (int)$aUser["id"]));
    while ($sPermissionKey = $oStatement->fetchColumn()) {
        $aPortalUser["direct_permission_keys"][] = (string)$sPermissionKey;
    }
    $aEffectivePermissions = fetchUserEffectivePermissions($oPdo, (int)$aUser["id"], $iSubjectId);
    foreach ($aEffectivePermissions as $sPermissionKey => $blAllowed) {
        if ($blAllowed) {
            $aPortalUser["effective_permission_keys"][] = (string)$sPermissionKey;
        }
    }
    sort($aPortalUser["effective_permission_keys"]);
    return $aPortalUser;
}

function normalizePortalPermissionKeys($oPdo, $aPermissionKeys) {
    $aKeys = array();
    $aNormalizedKeys = array();
    if (!is_array($aPermissionKeys) || !$aPermissionKeys) {
        return $aNormalizedKeys;
    }
    foreach ($aPermissionKeys as $sPermissionKey) {
        $sPermissionKey = trim((string)$sPermissionKey);
        if ($sPermissionKey != "" && !isset($aKeys[$sPermissionKey])) {
            $aKeys[$sPermissionKey] = true;
        }
    }
    if (!$aKeys) {
        return $aNormalizedKeys;
    }
    $aPlaceholders = array();
    $aParams = array();
    $iIndex = 0;
    foreach ($aKeys as $sPermissionKey => $blAllowed) {
        $sParam = "permission_key_" . $iIndex;
        $aPlaceholders[] = ":" . $sParam;
        $aParams[$sParam] = $sPermissionKey;
        $iIndex++;
    }
    $oStatement = $oPdo->prepare("SELECT id, permission_key FROM ex_permissions WHERE is_active = 1 AND permission_key IN (" . implode(", ", $aPlaceholders) . ")");
    $oStatement->execute($aParams);
    while ($aPermission = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $aNormalizedKeys[(string)$aPermission["permission_key"]] = (int)$aPermission["id"];
    }
    return $aNormalizedKeys;
}

function savePortalUserPermissions($oPdo, $iUserId, $aPermissionKeys) {
    $aPermissions = normalizePortalPermissionKeys($oPdo, $aPermissionKeys);
    $oStatement = $oPdo->prepare("DELETE FROM ex_user_permissions WHERE user_id = :user_id");
    $oStatement->execute(array("user_id" => $iUserId));
    foreach ($aPermissions as $sPermissionKey => $iPermissionId) {
        $oStatement = $oPdo->prepare("INSERT INTO ex_user_permissions (user_id, permission_id, is_allowed) VALUES (:user_id, :permission_id, 1)");
        $oStatement->execute(array(
            "user_id" => $iUserId,
            "permission_id" => $iPermissionId
        ));
    }
}

function saveGroupPortalPermissions($oPdo, $iGroupId, $aPermissionKeys) {
    $aPermissions = normalizePortalPermissionKeys($oPdo, $aPermissionKeys);
    $oStatement = $oPdo->prepare("DELETE FROM ex_group_permissions WHERE group_id = :group_id");
    $oStatement->execute(array("group_id" => $iGroupId));
    foreach ($aPermissions as $sPermissionKey => $iPermissionId) {
        $oStatement = $oPdo->prepare("INSERT INTO ex_group_permissions (group_id, permission_id, is_allowed) VALUES (:group_id, :permission_id, 1)");
        $oStatement->execute(array(
            "group_id" => $iGroupId,
            "permission_id" => $iPermissionId
        ));
    }
}

function normalizeGroupOrder($oPdo) {
    $oStatement = $oPdo->query("SELECT id FROM ex_groups ORDER BY `order` ASC, id ASC FOR UPDATE");
    $aIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
    $iOrder = 10;
    $oUpdateStatement = $oPdo->prepare("UPDATE ex_groups SET `order` = :order WHERE id = :id");
    foreach ($aIds as $iGroupId) {
        $oUpdateStatement->execute(array("order" => $iOrder, "id" => (int)$iGroupId));
        $iOrder += 10;
    }
}

function moveGroupOrder($oPdo, $iGroupId, $sDirection) {
    normalizeGroupOrder($oPdo);
    $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_groups WHERE id = :id FOR UPDATE");
    $oStatement->execute(array("id" => $iGroupId));
    $aCurrent = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aCurrent) {
        throw new Exception("Group was not found.");
    }
    if ($sDirection == "up") {
        $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_groups WHERE `order` < :order ORDER BY `order` DESC, id DESC LIMIT 1 FOR UPDATE");
    } else {
        $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_groups WHERE `order` > :order ORDER BY `order` ASC, id ASC LIMIT 1 FOR UPDATE");
    }
    $oStatement->execute(array("order" => (int)$aCurrent["order"]));
    $aOther = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aOther) {
        return;
    }
    $oStatement = $oPdo->prepare("UPDATE ex_groups SET `order` = :order WHERE id = :id");
    $oStatement->execute(array("order" => (int)$aOther["order"], "id" => (int)$aCurrent["id"]));
    $oStatement->execute(array("order" => (int)$aCurrent["order"], "id" => (int)$aOther["id"]));
}

function saveSubjectPortalAccess($oPdo, $iSubjectId, $sSubjectType, $aPayload) {
    if (!isset($aPayload["portal_user_enabled"])
        && !isset($aPayload["portal_user_name"])
        && !isset($aPayload["portal_password"])
        && !isset($aPayload["portal_permission_keys"])) {
        return;
    }
    $iEnabled = payloadFlag($aPayload, "portal_user_enabled");
    $oStatement = $oPdo->prepare("SELECT id, password_hash FROM ex_users WHERE subject_id = :subject_id FOR UPDATE");
    $oStatement->execute(array("subject_id" => $iSubjectId));
    $aUser = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$iEnabled) {
        if ($aUser) {
            $oStatement = $oPdo->prepare("DELETE FROM ex_user_permissions WHERE user_id = :user_id");
            $oStatement->execute(array("user_id" => (int)$aUser["id"]));
            $oStatement = $oPdo->prepare("DELETE FROM ex_users WHERE id = :id");
            $oStatement->execute(array("id" => (int)$aUser["id"]));
        }
        return;
    }

    if (!in_array($sSubjectType, array("person", "service"), true)) {
        throw new Exception("Portal access can be granted only to person or service subjects.");
    }
    $sUserName = payloadValue($aPayload, "portal_user_name");
    $sPassword = isset($aPayload["portal_password"]) ? (string)$aPayload["portal_password"] : "";
    if ($sUserName == "") {
        throw new Exception("Portal user name is required.");
    }
    if (!$aUser && $sPassword == "") {
        throw new Exception("Password is required for a new portal user.");
    }
    if ($aUser) {
        if ($sPassword != "") {
            $oStatement = $oPdo->prepare("UPDATE ex_users SET user_name = :user_name, password_hash = :password_hash, is_active = :is_active WHERE id = :id");
            $oStatement->execute(array(
                "user_name" => $sUserName,
                "password_hash" => password_hash($sPassword, PASSWORD_DEFAULT),
                "is_active" => payloadFlag($aPayload, "portal_user_active"),
                "id" => (int)$aUser["id"]
            ));
        } else {
            $oStatement = $oPdo->prepare("UPDATE ex_users SET user_name = :user_name, is_active = :is_active WHERE id = :id");
            $oStatement->execute(array(
                "user_name" => $sUserName,
                "is_active" => payloadFlag($aPayload, "portal_user_active"),
                "id" => (int)$aUser["id"]
            ));
        }
        $iUserId = (int)$aUser["id"];
    } else {
        $oStatement = $oPdo->prepare("INSERT INTO ex_users (subject_id, user_name, password_hash, is_active) VALUES (:subject_id, :user_name, :password_hash, :is_active)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "user_name" => $sUserName,
            "password_hash" => password_hash($sPassword, PASSWORD_DEFAULT),
            "is_active" => payloadFlag($aPayload, "portal_user_active")
        ));
        $iUserId = (int)$oPdo->lastInsertId();
    }
    $aPermissionKeys = isset($aPayload["portal_permission_keys"]) && is_array($aPayload["portal_permission_keys"]) ? $aPayload["portal_permission_keys"] : array();
    savePortalUserPermissions($oPdo, $iUserId, $aPermissionKeys);
}

function renderGroupAdminRow($aGroup, $blShowActions = true) {
    global $sDeleteEmoji, $sEditEmoji, $sEmptyValueEmoji, $sMergeEmoji, $sMoveUpEmoji, $sMoveDownEmoji;

    $sPermissionKeys = isset($aGroup["permission_keys"]) ? (string)$aGroup["permission_keys"] : "";
    $sPermissionNames = isset($aGroup["permission_names"]) ? (string)$aGroup["permission_names"] : "";
    $sTimestampTooltipText = timestampTooltipText($aGroup);
    $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
    return "      <tr data-group-id=\"" . html($aGroup["id"]) . "\" data-group-name=\"" . html($aGroup["name"]) . "\" data-group-order=\"" . html($aGroup["order"]) . "\" data-permission-keys=\"" . html($sPermissionKeys) . "\">\n"
        . "        <td><span" . $sTimestampTooltipAttribute . ">" . html($aGroup["name"]) . "</span></td>\n"
        . "        <td>" . html($aGroup["subject_count"]) . "</td>\n"
        . "        <td>" . ($sPermissionNames != "" ? nl2br(html(str_replace(",", "\n", $sPermissionNames)), false) : $sEmptyValueEmoji) . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"item-action js-move-group-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-move-group-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"item-action js-merge-group\" title=\"Merge into this group\" aria-label=\"Merge into this group\">" . $sMergeEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"item-action js-edit-group\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"item-action js-delete-group\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "") . "</td>\n"
        . "      </tr>\n";
}

function fetchSubjectNotes($oPdo, $iSubjectId = 0) {
    $aNotes = array();
    $sSql = "SELECT id, subject_id, note_text, is_primary, is_active, created_at, updated_at FROM ex_subject_notes";
    if ($iSubjectId > 0) {
        $sSql .= " WHERE subject_id = :subject_id";
    }
    $sSql .= " ORDER BY subject_id ASC, is_active DESC, is_primary DESC, id ASC";
    $oStatement = $oPdo->prepare($sSql);
    if ($iSubjectId > 0) {
        $oStatement->execute(array("subject_id" => $iSubjectId));
    } else {
        $oStatement->execute();
    }
    while ($aNote = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $iCurrentSubjectId = (int)$aNote["subject_id"];
        if (!isset($aNotes[$iCurrentSubjectId])) {
            $aNotes[$iCurrentSubjectId] = array();
        }
        $aNotes[$iCurrentSubjectId][] = $aNote;
    }
    return $aNotes;
}

function updateSubjectContactTarget($oPdo, $iSubjectContactId, $iContactTypeId, $sContactValue, $aContactType) {
    $oStatement = $oPdo->prepare("SELECT sc.id, sc.subject_id, sc.contact_id, sc.is_active AS current_is_active, c.contact_type_id AS current_contact_type_id, c.contact_value AS current_contact_value FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id WHERE sc.id = :id FOR UPDATE");
    $oStatement->execute(array("id" => $iSubjectContactId));
    $aSubjectContact = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aSubjectContact) {
        return null;
    }
    $iOriginalContactId = (int)$aSubjectContact["contact_id"];
    $blContactTypeChanged = (int)$aSubjectContact["current_contact_type_id"] != $iContactTypeId;
    $blContactValueChanged = (string)$aSubjectContact["current_contact_value"] != $sContactValue;
    $blContactIdentityChanged = $blContactTypeChanged || $blContactValueChanged;
    if ($blContactIdentityChanged) {
        $oStatement = $oPdo->prepare("SELECT id FROM ex_subject_contacts WHERE contact_id = :contact_id FOR UPDATE");
        $oStatement->execute(array("contact_id" => $iOriginalContactId));
        $aContactLinkIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
        $blCurrentContactShared = count($aContactLinkIds) > 1;

        $oStatement = $oPdo->prepare("SELECT id FROM ex_contacts WHERE contact_type_id = :contact_type_id AND contact_value = :contact_value FOR UPDATE");
        $oStatement->execute(array(
            "contact_type_id" => $iContactTypeId,
            "contact_value" => $sContactValue
        ));
        $iTargetContactId = (int)$oStatement->fetchColumn();
        if ($iTargetContactId > 0 && $iTargetContactId != $iOriginalContactId) {
            $oStatement = $oPdo->prepare("UPDATE ex_subject_contacts SET contact_id = :contact_id WHERE id = :id");
            $oStatement->execute(array(
                "contact_id" => $iTargetContactId,
                "id" => $iSubjectContactId
            ));
            if (!$blCurrentContactShared) {
                $oStatement = $oPdo->prepare("DELETE FROM ex_contacts WHERE id = :id");
                $oStatement->execute(array("id" => $iOriginalContactId));
            }
        } elseif ($blCurrentContactShared) {
            $oStatement = $oPdo->prepare("INSERT INTO ex_contacts (contact_type_id, contact_value) VALUES (:contact_type_id, :contact_value)");
            $oStatement->execute(array(
                "contact_type_id" => $iContactTypeId,
                "contact_value" => $sContactValue
            ));
            $iNewContactId = (int)$oPdo->lastInsertId();
            $oStatement = $oPdo->prepare("UPDATE ex_subject_contacts SET contact_id = :contact_id WHERE id = :id");
            $oStatement->execute(array(
                "contact_id" => $iNewContactId,
                "id" => $iSubjectContactId
            ));
        } else {
            $oStatement = $oPdo->prepare("UPDATE ex_contacts SET contact_type_id = :contact_type_id, contact_value = :contact_value WHERE id = :id");
            $oStatement->execute(array(
                "contact_type_id" => $iContactTypeId,
                "contact_value" => $sContactValue,
                "id" => $iOriginalContactId
            ));
        }
    }
    $aContact = array(
        "subject_id" => (int)$aSubjectContact["subject_id"],
        "current_is_active" => (int)$aSubjectContact["current_is_active"],
        "contact_identity_changed" => $blContactIdentityChanged ? 1 : 0
    );
    return $aContact;
}

function collectHiddenInactiveSubjectItems(&$aHiddenInactive, $aItems) {
    foreach ($aItems as $iSubjectId => $aSubjectItems) {
        foreach ($aSubjectItems as $aItem) {
            if (isset($aItem["is_active"]) && (int)$aItem["is_active"] != 1) {
                $aHiddenInactive[(int)$iSubjectId] = true;
                break;
            }
        }
    }
}

function getHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aSettings) {
    $aHiddenInactive = array(
        "contacts" => array(),
        "nicknames" => array(),
        "addresses" => array(),
        "notes" => array()
    );
    if (empty($aSettings["show_inactive_contacts"])) {
        collectHiddenInactiveSubjectItems($aHiddenInactive["contacts"], $aContacts);
    }
    if (empty($aSettings["show_inactive_nicknames"])) {
        collectHiddenInactiveSubjectItems($aHiddenInactive["nicknames"], $aNicknames);
    }
    if (empty($aSettings["show_inactive_addresses"])) {
        collectHiddenInactiveSubjectItems($aHiddenInactive["addresses"], $aAddresses);
    }
    if (empty($aSettings["show_inactive_notes"])) {
        collectHiddenInactiveSubjectItems($aHiddenInactive["notes"], $aNotes);
    }
    return $aHiddenInactive;
}

function applySubjectVisibilitySettings(&$aRows, &$aContacts, &$aNicknames, &$aAddresses, &$aNotes, $aSettings) {
    if (empty($aSettings["show_inactive_subjects"])) {
        $aActiveRows = array();
        foreach ($aRows as $aRow) {
            if ((int)$aRow["is_active"] == 1) {
                $aActiveRows[] = $aRow;
            }
        }
        $aRows = $aActiveRows;
    }
    if (empty($aSettings["show_inactive_nicknames"])) {
        foreach ($aNicknames as $iSubjectId => $aSubjectNicknames) {
            $aActiveNicknames = array();
            foreach ($aSubjectNicknames as $aNickname) {
                if (!isset($aNickname["is_active"]) || (int)$aNickname["is_active"] == 1) {
                    $aActiveNicknames[] = $aNickname;
                }
            }
            $aNicknames[$iSubjectId] = $aActiveNicknames;
        }
    }
    if (empty($aSettings["show_inactive_addresses"])) {
        foreach ($aAddresses as $iSubjectId => $aSubjectAddresses) {
            $aActiveAddresses = array();
            foreach ($aSubjectAddresses as $aAddress) {
                if (!isset($aAddress["is_active"]) || (int)$aAddress["is_active"] == 1) {
                    $aActiveAddresses[] = $aAddress;
                }
            }
            $aAddresses[$iSubjectId] = $aActiveAddresses;
        }
    }
    if (empty($aSettings["show_inactive_contacts"])) {
        foreach ($aContacts as $iSubjectId => $aSubjectContacts) {
            $aActiveContacts = array();
            foreach ($aSubjectContacts as $aContact) {
                if ((int)$aContact["is_active"] == 1) {
                    $aActiveContacts[] = $aContact;
                }
            }
            $aContacts[$iSubjectId] = $aActiveContacts;
        }
    }
    if (empty($aSettings["show_inactive_notes"])) {
        foreach ($aNotes as $iSubjectId => $aSubjectNotes) {
            $aActiveNotes = array();
            foreach ($aSubjectNotes as $aNote) {
                if (!isset($aNote["is_active"]) || (int)$aNote["is_active"] == 1) {
                    $aActiveNotes[] = $aNote;
                }
            }
            $aNotes[$iSubjectId] = $aActiveNotes;
        }
    }
}

function renderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions = true, $aHiddenInactive = array(), $aDisplaySettings = null) {
    global $sEditEmoji, $sDeleteEmoji, $sPortalEmoji;

    $iSubjectId = (int)$aRow["subject_id"];
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aRow["subject_type"]));
    $blIsActive = (int)$aRow["is_active"] == 1;
    $blShowBirthNumber = !is_array($aDisplaySettings) || empty($aDisplaySettings["hide_personal_number"]);
    $sBirthNumberClass = birthNumberClass($aRow["birth_number"]);
    $sBirthNumberClassAttribute = $sBirthNumberClass != "" ? " class=\"" . html($sBirthNumberClass) . "\"" : "";
    $sBirthDateClass = birthDateClass($aRow["birth_number"], $aRow["birth_date"]);
    $sBirthDateClassAttribute = $sBirthDateClass != "" ? " class=\"" . html($sBirthDateClass) . "\"" : "";
    $sBirthDateAgeLabel = trim((string)$aRow["death_date"]) == "" ? subjectAgeLabel(ageInYears($aRow["birth_date"]), "*") : "";
    $sDeathDateAgeLabel = trim((string)$aRow["death_date"]) != "" ? subjectAgeLabel(ageInYears($aRow["birth_date"], $aRow["death_date"]), "†") : "";
    $sTimestampTooltipText = timestampTooltipText($aRow);
    $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
    $sActions = "";
    if ($blShowActions) {
        $sActions = "<span class=\"list-item-actions\">"
            . "<a href=\"#\" class=\"item-action js-edit-subject\" data-subject-id=\"" . html($iSubjectId) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
            . "<a href=\"#\" class=\"item-action js-edit-subject-portal\" data-subject-id=\"" . html($iSubjectId) . "\" title=\"Portal account\" aria-label=\"Portal account\">" . $sPortalEmoji . "</a>"
            . "<a href=\"#\" class=\"item-action js-delete-subject\" data-subject-id=\"" . html($iSubjectId) . "\" data-subject-name=\"" . html($aRow["subject_name"]) . "\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
            . "</span>";
    }
    return "      <tr class=\"subject-row subject-row-type-" . html($sSubjectType) . ($blIsActive ? " subject-row-active" : " subject-row-inactive") . "\" data-subject-id=\"" . html($iSubjectId) . "\" data-subject-type=\"" . html($aRow["subject_type"]) . "\" data-subject-active=\"" . ($blIsActive ? "1" : "0") . "\">\n"
        . "        <td class=\"subject-type-column\" style=\"vertical-align: top;\">" . html($aRow["subject_type"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\"><span class=\"subject-item-value\"" . $sTimestampTooltipAttribute . ">" . htmlValue($aRow["subject_name"]) . "</span>"
        . renderCopyAction($aRow["subject_name"])
        . $sActions . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlValue($aRow["first_name"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlValue($aRow["last_name"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . htmlValue($aRow["birth_name"]) . "</td>\n"
        . ($blShowBirthNumber ? "        <td" . $sBirthNumberClassAttribute . " style=\"vertical-align: top;\">" . renderBirthNumberValue($aRow["birth_number"]) . "</td>\n" : "")
        . "        <td" . $sBirthDateClassAttribute . " style=\"vertical-align: top;\">" . renderSubjectDateValue($aRow["birth_date"], $sBirthDateAgeLabel) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . renderSubjectDateValue($aRow["death_date"], $sDeathDateAgeLabel) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . renderNicknameList(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(), $blShowActions, $iSubjectId, !empty($aHiddenInactive["nicknames"][$iSubjectId]), true, true, true) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . renderAddressList(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $blShowActions, $iSubjectId, $aRow["subject_name"], !empty($aHiddenInactive["addresses"][$iSubjectId]), $aDisplaySettings, true, true, true) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . renderContactList(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(), $blShowActions, $iSubjectId, true, true, !empty($aHiddenInactive["contacts"][$iSubjectId]), true, true, true) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . renderGroupList(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(), $blShowActions, $iSubjectId, true, true, true) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . renderNoteList(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array(), $blShowActions, $iSubjectId, !empty($aHiddenInactive["notes"][$iSubjectId]), true, true, true) . "</td>\n"
        . "      </tr>\n";
}

function subjectRowOption($aOptions, $sName, $mDefault) {
    return is_array($aOptions) && array_key_exists($sName, $aOptions) ? $aOptions[$sName] : $mDefault;
}

function renderSubjectTableCell($sHtml, $sClass = "", $sStyle = "") {
    $sAttributes = "";
    if ($sClass != "") {
        $sAttributes .= " class=\"" . html($sClass) . "\"";
    }
    if ($sStyle != "") {
        $sAttributes .= " style=\"" . html($sStyle) . "\"";
    }
    return "        <td" . $sAttributes . ">" . $sHtml . "</td>\n";
}

function renderResponsiveSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aHiddenInactive = array(), $aDisplaySettings = null, $aOptions = array()) {
    $iSubjectId = (int)$aRow["subject_id"];
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aRow["subject_type"]));
    $blIsActive = (int)$aRow["is_active"] == 1;
    $blShowActions = subjectRowOption($aOptions, "show_actions", false);
    $iItemSubjectId = (int)subjectRowOption($aOptions, "item_subject_id", 0);
    $sNoWrapStyle = "overflow-wrap: normal; white-space: nowrap; word-break: normal;";
    $sBirthNumberClass = birthNumberClass($aRow["birth_number"], subjectRowOption($aOptions, "birth_number_class", "column-hidden"));
    $sBirthDateClass = birthDateClass($aRow["birth_number"], $aRow["birth_date"], subjectRowOption($aOptions, "birth_date_class", "column-step-two"));
    $sDeathDateClass = subjectRowOption($aOptions, "death_date_class", "column-hidden");
    $blDeathDateHidden = strpos(" " . trim((string)$sDeathDateClass) . " ", " column-hidden ") !== false;
    $sBirthDateAgeLabel = trim((string)$aRow["death_date"]) == "" ? subjectAgeLabel(ageInYears($aRow["birth_date"]), "*") : ($blDeathDateHidden ? subjectAgeLabel(ageInYears($aRow["birth_date"], $aRow["death_date"]), "†") : "");
    $sDeathDateAgeLabel = trim((string)$aRow["death_date"]) != "" && !$blDeathDateHidden ? subjectAgeLabel(ageInYears($aRow["birth_date"], $aRow["death_date"]), "†") : "";
    $sTimestampTooltipText = timestampTooltipText($aRow);
    $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
    $aBeforeNameCells = subjectRowOption($aOptions, "before_name_cells", array());
    $sHtml = "      <tr class=\"subject-row subject-row-type-" . html($sSubjectType) . ($blIsActive ? " subject-row-active" : " subject-row-inactive") . "\" data-subject-id=\"" . html($iSubjectId) . "\" data-subject-type=\"" . html($aRow["subject_type"]) . "\" data-subject-active=\"" . ($blIsActive ? "1" : "0") . "\">\n"
        . renderSubjectTableCell(html($aRow["subject_type"]), subjectRowOption($aOptions, "type_class", "column-hidden"), subjectRowOption($aOptions, "type_style", ""));
    if (is_array($aBeforeNameCells)) {
        foreach ($aBeforeNameCells as $sCellHtml) {
            $sHtml .= $sCellHtml;
        }
    }
    $sHtml .= renderSubjectTableCell(
            "<span class=\"subject-item-value\"" . $sTimestampTooltipAttribute . ">" . htmlValue($aRow["subject_name"]) . "</span>"
            . renderCopyAction($aRow["subject_name"])
            . subjectRowOption($aOptions, "name_actions", ""),
            subjectRowOption($aOptions, "name_class", ""),
            subjectRowOption($aOptions, "name_style", "")
        )
        . renderSubjectTableCell(htmlValue($aRow["first_name"]), subjectRowOption($aOptions, "first_name_class", "column-hidden"), subjectRowOption($aOptions, "first_name_style", ""))
        . renderSubjectTableCell(htmlValue($aRow["last_name"]), subjectRowOption($aOptions, "last_name_class", "column-hidden"), subjectRowOption($aOptions, "last_name_style", ""))
        . renderSubjectTableCell(htmlValue($aRow["birth_name"]), subjectRowOption($aOptions, "birth_name_class", "column-step-one"), subjectRowOption($aOptions, "birth_name_style", ""))
        . renderSubjectTableCell(renderBirthNumberValue($aRow["birth_number"]), $sBirthNumberClass, subjectRowOption($aOptions, "birth_number_style", ""))
        . renderSubjectTableCell(renderSubjectDateValue($aRow["birth_date"], $sBirthDateAgeLabel), $sBirthDateClass, subjectRowOption($aOptions, "birth_date_style", $sNoWrapStyle))
        . renderSubjectTableCell(renderSubjectDateValue($aRow["death_date"], $sDeathDateAgeLabel), $sDeathDateClass, subjectRowOption($aOptions, "death_date_style", ""))
        . renderSubjectTableCell(renderNicknameList(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, !empty($aHiddenInactive["nicknames"][$iSubjectId]), subjectRowOption($aOptions, "nickname_show_add_action", false), subjectRowOption($aOptions, "nickname_show_cell_copy_action", true), subjectRowOption($aOptions, "nickname_cell_copy_before_add_action", true)), subjectRowOption($aOptions, "nickname_class", "column-step-one"), subjectRowOption($aOptions, "nickname_style", ""))
        . renderSubjectTableCell(renderAddressList(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, $aRow["subject_name"], !empty($aHiddenInactive["addresses"][$iSubjectId]), $aDisplaySettings, subjectRowOption($aOptions, "address_show_add_action", false), subjectRowOption($aOptions, "address_show_cell_copy_action", true), subjectRowOption($aOptions, "address_cell_copy_before_add_action", true)), subjectRowOption($aOptions, "address_class", ""), subjectRowOption($aOptions, "address_style", ""))
        . renderSubjectTableCell(renderContactList(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, true, true, !empty($aHiddenInactive["contacts"][$iSubjectId]), subjectRowOption($aOptions, "contact_show_add_action", false), subjectRowOption($aOptions, "contact_show_cell_copy_action", true), subjectRowOption($aOptions, "contact_cell_copy_before_add_action", true)), subjectRowOption($aOptions, "contact_class", ""), subjectRowOption($aOptions, "contact_style", ""))
        . renderSubjectTableCell(renderGroupList(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, subjectRowOption($aOptions, "group_show_add_action", false), subjectRowOption($aOptions, "group_show_cell_copy_action", true), subjectRowOption($aOptions, "group_cell_copy_before_add_action", true)), subjectRowOption($aOptions, "group_class", "column-step-three"), subjectRowOption($aOptions, "group_style", ""))
        . renderSubjectTableCell(renderNoteList(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, !empty($aHiddenInactive["notes"][$iSubjectId]), subjectRowOption($aOptions, "note_show_add_action", false), subjectRowOption($aOptions, "note_show_cell_copy_action", true), subjectRowOption($aOptions, "note_cell_copy_before_add_action", true)), subjectRowOption($aOptions, "note_class", "column-step-three"), subjectRowOption($aOptions, "note_style", ""))
        . "      </tr>\n";
    return $sHtml;
}

function renderUpdatedSubjectRow($oPdo, $iSubjectId, $aVisibilitySettings = null, $aFilterSql = null) {
    $aRows = fetchSubjectRows($oPdo, $iSubjectId, $aFilterSql);
    if (!$aRows) {
        return "";
    }
    $aContacts = fetchSubjectContacts($oPdo, $iSubjectId);
    $aNicknames = fetchSubjectNicknames($oPdo, $iSubjectId);
    $aAddresses = fetchSubjectAddresses($oPdo, $iSubjectId);
    $aGroups = fetchSubjectGroups($oPdo, $iSubjectId);
    $aNotes = fetchSubjectNotes($oPdo, $iSubjectId);
    $aHiddenInactive = array();
    if (is_array($aVisibilitySettings)) {
        $aHiddenInactive = getHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aVisibilitySettings);
        applySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aVisibilitySettings);
        if (!$aRows) {
            return "";
        }
    }
    return renderSubjectRow(
        $aRows[0],
        $aContacts,
        $aNicknames,
        $aAddresses,
        $aGroups,
        $aNotes,
        true,
        $aHiddenInactive,
        is_array($aVisibilitySettings) ? $aVisibilitySettings : null
    );
}

function getUpdatedSubjectResponse($oPdo, $iSubjectId, $aVisibilitySettings = null, $aFilterSql = null) {
    $sRowHtml = renderUpdatedSubjectRow($oPdo, $iSubjectId, $aVisibilitySettings, $aFilterSql);
    if ($sRowHtml == "") {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    return array("success" => true, "subject_id" => $iSubjectId, "row_html" => $sRowHtml);
}

function fetchSubjectEditorData($oPdo, $iSubjectId) {
    $oStatement = $oPdo->prepare("SELECT s.id AS subject_id, s.subject_type, s.is_active, subn.name AS subject_name_value, p.title_before, p.first_name, p.middle_name, p.last_name, p.title_after, p.birth_name, p.birth_number, p.birth_date, p.death_date FROM ex_subjects AS s LEFT JOIN ex_persons AS p ON p.subject_id = s.id LEFT JOIN ex_subject_names AS subn ON subn.subject_id = s.id WHERE s.id = :subject_id");
    $oStatement->execute(array("subject_id" => $iSubjectId));
    $aSubject = $oStatement->fetch(PDO::FETCH_ASSOC);
    return $aSubject ? $aSubject : null;
}

function fetchSubjectPortalEditorData($oPdo, $iSubjectId) {
    $aRows = fetchSubjectRows($oPdo, $iSubjectId);
    if (!$aRows) {
        return null;
    }
    return array(
        "subject_id" => (int)$aRows[0]["subject_id"],
        "subject_name" => (string)$aRows[0]["subject_name"],
        "subject_type" => (string)$aRows[0]["subject_type"],
        "portal_user" => fetchSubjectPortalUser($oPdo, $iSubjectId),
        "portal_permissions" => fetchPortalPermissions($oPdo)
    );
}


function addressesNormalizeKey($sValue) {
    $sValue = str_replace("\r\n", "\n", (string)$sValue);
    $sValue = str_replace("\r", "\n", $sValue);
    if (function_exists("mb_strtolower")) {
        return mb_strtolower($sValue, "UTF-8");
    }
    return strtolower($sValue);
}

function addressesCompareRows($aFirst, $aSecond) {
    $iResult = strcmp((string)$aFirst["address_sort"], (string)$aSecond["address_sort"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return strcmp((string)$aFirst["address_text"], (string)$aSecond["address_text"]);
}

function addressesCompareSubjectNames($sFirst, $sSecond) {
    return strcmp((string)$sFirst, (string)$sSecond);
}

function addressesCompareSubjects($aFirst, $aSecond) {
    $iResult = addressesCompareSubjectNames($aFirst["subject_name"], $aSecond["subject_name"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["address_id"] - (int)$aSecond["address_id"];
}

function addressesAddressFields() {
    return array(
        "organization_name",
        "department_name",
        "care_of",
        "street_name",
        "house_number",
        "evidence_number",
        "orientation_number",
        "orientation_suffix",
        "address_line2",
        "city",
        "city_part",
        "postal_code",
        "region",
        "country"
    );
}

function addressesRequiredAddressFields() {
    return array("country");
}

function addressesSubjectAddressFields() {
    return array_merge(array("address_type"), addressesAddressFields(), array("note"));
}

function addressesBuildMatch($aAddress) {
    $aMatch = array();
    foreach (addressesAddressFields() as $sField) {
        $aMatch[$sField] = array_key_exists($sField, $aAddress) && $aAddress[$sField] !== null ? (string)$aAddress[$sField] : null;
    }
    return $aMatch;
}

function addressesEncodeMatch($aMatch) {
    return base64_encode(json_encode($aMatch));
}

function addressesDecodeMatch($sMatch) {
    $sJson = base64_decode((string)$sMatch, true);
    $aMatch = $sJson !== false ? json_decode($sJson, true) : null;
    $aFields = addressesAddressFields();
    if (!is_array($aMatch)) {
        return null;
    }
    foreach ($aFields as $sField) {
        if (!array_key_exists($sField, $aMatch)) {
            return null;
        }
        if ($aMatch[$sField] !== null) {
            $aMatch[$sField] = (string)$aMatch[$sField];
        }
    }
    return $aMatch;
}

function addressesNullValue($sField, $sValue) {
    return in_array($sField, addressesRequiredAddressFields(), true) || $sValue != "" ? (string)$sValue : null;
}

function addressesMatchSql($sPrefix) {
    $aSql = array();
    foreach (addressesAddressFields() as $sField) {
        $aSql[] = "`" . $sField . "` <=> :" . $sPrefix . $sField;
    }
    return implode(" AND ", $aSql);
}

function addressesMatchParams($aMatch, $sPrefix) {
    $aParams = array();
    foreach (addressesAddressFields() as $sField) {
        $aParams[$sPrefix . $sField] = array_key_exists($sField, $aMatch) ? $aMatch[$sField] : null;
    }
    return $aParams;
}

function addressesPostedAddressValues() {
    $sOrganizationName = getPostedTrimmedValue("organization_name");
    $sDepartmentName = getPostedTrimmedValue("department_name");
    $sCareOf = getPostedTrimmedValue("care_of");
    $sStreetName = getPostedTrimmedValue("street_name");
    $sHouseNumber = getPostedTrimmedValue("house_number");
    $sEvidenceNumber = getPostedTrimmedValue("evidence_number");
    $sOrientationNumber = getPostedTrimmedValue("orientation_number");
    $sOrientationSuffix = getPostedTrimmedValue("orientation_suffix");
    $sAddressLine2 = getPostedTrimmedValue("address_line2");
    $sCity = getPostedTrimmedValue("city");
    $sCityPart = getPostedTrimmedValue("city_part");
    $sPostalCode = getPostedTrimmedValue("postal_code");
    $sRegion = getPostedTrimmedValue("region");
    $sCountry = countryNameToCode(getPostedTrimmedValue("country"));
    if ($sCountry != "") {
        $sCountry = strtoupper($sCountry);
    }
    if ($sCountry == "") {
        sendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry != "" && !in_array($sCountry, getCountryCodes(), true)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = normalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName == "" && $sDepartmentName == "" && $sCareOf == "" && $sStreetName == "" && $sHouseNumber == "" && $sEvidenceNumber == "" && $sOrientationNumber == "" && $sOrientationSuffix == "" && $sAddressLine2 == "" && $sCity == "" && $sCityPart == "" && $sPostalCode == "" && $sRegion == "" && $sCountry == "") {
        sendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }
    return array(
        "organization_name" => addressesNullValue("organization_name", $sOrganizationName),
        "department_name" => addressesNullValue("department_name", $sDepartmentName),
        "care_of" => addressesNullValue("care_of", $sCareOf),
        "street_name" => addressesNullValue("street_name", $sStreetName),
        "house_number" => addressesNullValue("house_number", $sHouseNumber),
        "evidence_number" => addressesNullValue("evidence_number", $sEvidenceNumber),
        "orientation_number" => addressesNullValue("orientation_number", $sOrientationNumber),
        "orientation_suffix" => addressesNullValue("orientation_suffix", $sOrientationSuffix),
        "address_line2" => addressesNullValue("address_line2", $sAddressLine2),
        "city" => addressesNullValue("city", $sCity),
        "city_part" => addressesNullValue("city_part", $sCityPart),
        "postal_code" => addressesNullValue("postal_code", $sPostalCode),
        "region" => addressesNullValue("region", $sRegion),
        "country" => $sCountry
    );
}

function addressesPostedSubjectAddressValues() {
    $sAddressType = getPostedTrimmedValue("address_type");
    $sNote = getPostedTrimmedValue("note");
    $aAddress = addressesPostedAddressValues();
    if ($sAddressType == "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, getAddressTypes(), true)) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    $aAddress["address_type"] = $sAddressType;
    $aAddress["note"] = addressesNullValue("note", $sNote);
    return $aAddress;
}

function addressesRenderDataAttributes($aAddressRow) {
    $sHtml = " data-address-match=\"" . html($aAddressRow["address_match"]) . "\""
        . renderTimestampTooltipDataAttribute($aAddressRow);
    foreach (addressesAddressFields() as $sField) {
        $sAttribute = str_replace("_", "-", $sField);
        $sValue = isset($aAddressRow["address_values"][$sField]) && $aAddressRow["address_values"][$sField] !== null ? (string)$aAddressRow["address_values"][$sField] : "";
        if ($sField == "postal_code") {
            $sValue = postalCodeDisplayValue($aAddressRow["address_values"]["country"], $sValue);
        } elseif ($sField == "country") {
            $sHtml .= " data-country-name=\"" . html(countryCodeToName($sValue)) . "\"";
        }
        $sHtml .= " data-" . $sAttribute . "=\"" . html($sValue) . "\"";
    }
    return $sHtml;
}

function addressesRenderSubjectDataAttributes($aSubject) {
    $sHtml = " data-address-id=\"" . html($aSubject["address_id"]) . "\"";
    foreach (addressesSubjectAddressFields() as $sField) {
        $sAttribute = str_replace("_", "-", $sField);
        $sValue = isset($aSubject["address_values"][$sField]) && $aSubject["address_values"][$sField] !== null ? (string)$aSubject["address_values"][$sField] : "";
        if ($sField == "postal_code") {
            $sValue = postalCodeDisplayValue($aSubject["address_values"]["country"], $sValue);
        } elseif ($sField == "country") {
            $sHtml .= " data-country-name=\"" . html(countryCodeToName($sValue)) . "\"";
        }
        $sHtml .= " data-" . $sAttribute . "=\"" . html($sValue) . "\"";
    }
    $sHtml .= " data-primary=\"" . ((int)$aSubject["is_primary"] == 1 ? "1" : "0") . "\"";
    $sHtml .= " data-active=\"" . ((int)$aSubject["address_is_active"] == 1 ? "1" : "0") . "\"";
    $sHtml .= " data-subject-active=\"" . (!empty($aSubject["is_active"]) ? "1" : "0") . "\"";
    return $sHtml;
}

function addressesSubjectCellClass($aSubject) {
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aSubject["subject_type"]));
    return "address-subject-cell address-subject-type-" . $sSubjectType . (!empty($aSubject["is_active"]) && (int)$aSubject["address_is_active"] == 1 ? " address-subject-active" : " address-subject-inactive");
}

function addressesFilterText($aAddressRow) {
    $sAddressFilterText = (string)$aAddressRow["address_text"];
    foreach ($aAddressRow["subjects"] as $aFilterSubject) {
        $sAddressFilterText .= " " . (string)$aFilterSubject["subject_name"];
    }
    return $sAddressFilterText;
}

function addressesRenderAddressCell($aAddressRow, $iSubjectCount, $blCanEdit) {
    global $sEditEmoji, $sDeleteEmoji;

    $sAddressTimestampTooltipText = timestampTooltipText($aAddressRow);
    $sAddressTimestampTooltipAttribute = $sAddressTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sAddressTimestampTooltipText)) . "\"" : "";
    $sAddressActions = $blCanEdit ? "<span class=\"list-item-actions\"><a href=\"#\" class=\"item-action js-edit-shared-address\" title=\"Edit shared address\" aria-label=\"Edit shared address\">" . $sEditEmoji . "</a><a href=\"#\" class=\"item-action js-delete-shared-address\" title=\"Delete shared address\" aria-label=\"Delete shared address\">" . $sDeleteEmoji . "</a></span>" : "";
    return "        <td class=\"address-cell\" rowspan=\"" . html($iSubjectCount) . "\"" . addressesRenderDataAttributes($aAddressRow) . ">"
        . "<span class=\"subject-item-value\"" . $sAddressTimestampTooltipAttribute . ">" . htmlValue($aAddressRow["address_text"]) . "</span>"
        . renderCopyAction($aAddressRow["address_copy_text"])
        . $sAddressActions
        . renderSubjectCellCopyAction(array($aAddressRow["address_text"]), true)
        . "</td>\n";
}

function addressesRenderSubjectCell($aSubject, $sAddressFilterText, $blCanEdit) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    $sSubjectTimestampTooltipText = timestampTooltipText($aSubject);
    $sSubjectTimestampTooltipAttribute = $sSubjectTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sSubjectTimestampTooltipText)) . "\"" : "";
    $sSubjectActions = $blCanEdit ? "<span class=\"list-item-actions\"><a href=\"#\" class=\"item-action js-edit-subject-address-local\" title=\"Edit subject address\" aria-label=\"Edit subject address\">" . $sEditEmoji . "</a><a href=\"#\" class=\"item-action js-delete-subject-address-local\" title=\"Delete subject address\" aria-label=\"Delete subject address\">" . $sDeleteEmoji . "</a></span>" : "";
    $sSubjectEditAction = $blCanEdit ? "<span class=\"list-item-actions\"><a href=\"#\" class=\"item-action js-edit-subject\" data-subject-id=\"" . html($aSubject["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a></span>" : "";
    $sSubjectValueClass = "subject-item-value" . ((string)$aSubject["address_values"]["address_type"] == "main" ? " subject-address-main-value" : "");
    $sSubjectPrimaryFlag = "<span class=\"subject-item-flags\"><span title=\"Primary\">" . ((int)$aSubject["is_primary"] == 1 ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ((int)$aSubject["address_is_active"] == 1 ? "" : $sInactiveEmoji) . "</span></span>";
    return "        <td class=\"" . html(addressesSubjectCellClass($aSubject)) . " list-item subject-address-item\"" . addressesRenderSubjectDataAttributes($aSubject) . "><span class=\"column-hidden\">" . htmlValue($sAddressFilterText) . "</span><span class=\"" . html($sSubjectValueClass) . "\"" . $sSubjectTimestampTooltipAttribute . ">" . htmlValue($aSubject["subject_name"]) . "</span>" . renderCopyAction($aSubject["subject_name"]) . $sSubjectEditAction . $sSubjectPrimaryFlag . $sSubjectActions . "</td>\n";
}

function addressesTypeLabel($sAddressType) {
    return ucwords(str_replace("_", " ", (string)$sAddressType));
}

function addressesFetchRows($oPdo, $aAddressSettings) {
    $aRows = array();
    $aSubjectNames = array();
    $aSubjectRows = fetchSubjectRows($oPdo);
    foreach ($aSubjectRows as $aSubjectRow) {
        if (empty($aAddressSettings["show_inactive_subjects"]) && (int)$aSubjectRow["is_active"] != 1) {
            continue;
        }
        $aSubjectNames[(int)$aSubjectRow["subject_id"]] = array(
            "subject_id" => (int)$aSubjectRow["subject_id"],
            "subject_name" => (string)$aSubjectRow["subject_name"],
            "subject_type" => (string)$aSubjectRow["subject_type"],
            "is_active" => (int)$aSubjectRow["is_active"] == 1,
            "created_at" => (string)$aSubjectRow["created_at"],
            "updated_at" => (string)$aSubjectRow["updated_at"]
        );
    }
    $aSubjectAddresses = fetchSubjectAddresses($oPdo);
    foreach ($aSubjectAddresses as $iSubjectId => $aAddresses) {
        $iSubjectId = (int)$iSubjectId;
        if (!isset($aSubjectNames[$iSubjectId])) {
            continue;
        }
        foreach ($aAddresses as $aAddress) {
            if (empty($aAddressSettings["show_inactive_addresses"]) && (int)$aAddress["is_active"] != 1) {
                continue;
            }
            $aAddressMatch = addressesBuildMatch($aAddress);
            $sAddressKey = json_encode($aAddressMatch);
            $sAddressCopyText = renderAddressCopyText($aAddress, "", $aAddressSettings);
            $sAddressText = renderAddressText($aAddress, $aAddressSettings);
            if (trim($sAddressText) == "") {
                continue;
            }
            if (!isset($aRows[$sAddressKey])) {
                $aRows[$sAddressKey] = array(
                    "address_text" => $sAddressText,
                    "address_copy_text" => $sAddressCopyText,
                    "address_sort" => addressesNormalizeKey($sAddressText),
                    "address_match" => addressesEncodeMatch($aAddressMatch),
                    "address_values" => $aAddressMatch,
                    "subjects" => array()
                );
            }
            $aRows[$sAddressKey]["subjects"][] = array_merge($aSubjectNames[$iSubjectId], array(
                "address_id" => (int)$aAddress["id"],
                "address_values" => array(
                    "address_type" => (string)$aAddress["address_type"],
                    "organization_name" => $aAddress["organization_name"],
                    "department_name" => $aAddress["department_name"],
                    "care_of" => $aAddress["care_of"],
                    "street_name" => $aAddress["street_name"],
                    "house_number" => $aAddress["house_number"],
                    "evidence_number" => $aAddress["evidence_number"],
                    "orientation_number" => $aAddress["orientation_number"],
                    "orientation_suffix" => $aAddress["orientation_suffix"],
                    "address_line2" => $aAddress["address_line2"],
                    "city" => $aAddress["city"],
                    "city_part" => $aAddress["city_part"],
                    "postal_code" => $aAddress["postal_code"],
                    "region" => $aAddress["region"],
                    "country" => $aAddress["country"],
                    "note" => $aAddress["note"]
                ),
                "is_primary" => (int)$aAddress["is_primary"],
                "address_is_active" => (int)$aAddress["is_active"],
                "address_created_at" => (string)$aAddress["created_at"],
                "address_updated_at" => (string)$aAddress["updated_at"]
            ));
        }
    }
    foreach ($aRows as $sKey => $aRow) {
        if (count($aRows[$sKey]["subjects"]) == 1) {
            $aRows[$sKey]["created_at"] = (string)$aRows[$sKey]["subjects"][0]["address_created_at"];
            $aRows[$sKey]["updated_at"] = (string)$aRows[$sKey]["subjects"][0]["address_updated_at"];
        }
        usort($aRows[$sKey]["subjects"], "addressesCompareSubjects");
    }
    uasort($aRows, "addressesCompareRows");
    return $aRows;
}

function bdGetBirthdayInfo($sBirthDate) {
    global $iBirthdayDisplayMinDays, $iBirthdayDisplayMaxDays;

    $sBirthDate = trim((string)$sBirthDate);
    if ($sBirthDate == "" || $sBirthDate == "0000-00-00") {
        return null;
    }
    if (!preg_match("/^[0-9]{4}-([0-9]{2})-([0-9]{2})$/", $sBirthDate, $aMatches)) {
        return null;
    }
    $iMonth = (int)$aMatches[1];
    $iDay = (int)$aMatches[2];
    if ($iMonth < 1 || $iMonth > 12 || $iDay < 1 || $iDay > 31) {
        return null;
    }
    $oToday = new DateTimeImmutable("today");
    $iCurrentYear = (int)$oToday->format("Y");
    $aYears = array($iCurrentYear - 1, $iCurrentYear, $iCurrentYear + 1);
    foreach ($aYears as $iYear) {
        if (!checkdate($iMonth, $iDay, $iYear)) {
            continue;
        }
        $oBirthday = DateTimeImmutable::createFromFormat("!Y-m-d", sprintf("%04d-%02d-%02d", $iYear, $iMonth, $iDay));
        if (!$oBirthday) {
            continue;
        }
        $iDaysToBirthday = (int)$oToday->diff($oBirthday)->format("%r%a");
        if ($iDaysToBirthday < $iBirthdayDisplayMinDays || $iDaysToBirthday > $iBirthdayDisplayMaxDays) {
            continue;
        }
        return array(
            "days_to_birthday" => $iDaysToBirthday,
            "birthday_date" => $oBirthday->format("Y-m-d")
        );
    }
    return null;
}

function fetchPersonServedRows($oPdo, $sServedColumn) {
    if (!in_array($sServedColumn, array("birthday_served_at", "inter_served_at"), true)) {
        return array();
    }
    $aServedRows = array();
    $oStatement = $oPdo->query("SELECT subject_id, " . $sServedColumn . " FROM ex_persons");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $aServedRows[(int)$aRow["subject_id"]] = $aRow;
    }
    return $aServedRows;
}

function bdFetchBirthdayServedRows($oPdo) {
    return fetchPersonServedRows($oPdo, "birthday_served_at");
}

function bdIsBirthdayServed($aServedRows, $iSubjectId, $sBirthdayDate) {
    global $iBirthdayDisplayMinDays, $iBirthdayDisplayMaxDays;

    if (!isset($aServedRows[$iSubjectId])) {
        return false;
    }
    $sServedAt = isset($aServedRows[$iSubjectId]["birthday_served_at"]) ? trim((string)$aServedRows[$iSubjectId]["birthday_served_at"]) : "";
    if ($sServedAt == "") {
        return false;
    }
    try {
        $oServedAt = new DateTimeImmutable($sServedAt);
        $oBirthday = new DateTimeImmutable((string)$sBirthdayDate . " 00:00:00");
    } catch (Exception $oException) {
        error_log((string)$oException);
        return false;
    }
    return $oServedAt >= $oBirthday->modify(sprintf("%+d days", -(int)$iBirthdayDisplayMaxDays)) && $oServedAt < $oBirthday->modify(sprintf("%+d days", 1 - (int)$iBirthdayDisplayMinDays));
}

function bdCompareRows($aFirst, $aSecond) {
    $iFirstCountdown = isset($aFirst["days_to_birthday"]) ? (int)$aFirst["days_to_birthday"] : 0;
    $iSecondCountdown = isset($aSecond["days_to_birthday"]) ? (int)$aSecond["days_to_birthday"] : 0;
    if ($iFirstCountdown === $iSecondCountdown) {
        $iResult = strcmp((string)(isset($aFirst["subject_sort_name"]) ? $aFirst["subject_sort_name"] : $aFirst["subject_name"]), (string)(isset($aSecond["subject_sort_name"]) ? $aSecond["subject_sort_name"] : $aSecond["subject_name"]));
        if ($iResult !== 0) {
            return $iResult;
        }
        $iResult = strcmp((string)$aFirst["subject_type"], (string)$aSecond["subject_type"]);
        if ($iResult !== 0) {
            return $iResult;
        }
        return (int)$aFirst["subject_id"] - (int)$aSecond["subject_id"];
    }
    return $iFirstCountdown < $iSecondCountdown ? -1 : 1;
}

function bdRenderSubjectActions($aRow, $blShowActions) {
    global $sDeleteEmoji, $sEditEmoji, $sPortalEmoji;

    if (!$blShowActions) {
        return "";
    }
    return "<span class=\"list-item-actions\">"
        . "<a href=\"#\" class=\"item-action js-edit-subject\" data-subject-id=\"" . html($aRow["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
        . "<a href=\"#\" class=\"item-action js-edit-subject-portal\" data-subject-id=\"" . html($aRow["subject_id"]) . "\" title=\"Portal account\" aria-label=\"Portal account\">" . $sPortalEmoji . "</a>"
        . "<a href=\"#\" class=\"item-action js-delete-subject\" data-subject-id=\"" . html($aRow["subject_id"]) . "\" data-subject-name=\"" . html($aRow["subject_name"]) . "\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
        . "</span>";
}

function renderServedSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aDisplaySettings, $sServedActionClass, $sServedActionLabel, $sServedActionEmoji, $aOptions = array()) {
    $iSubjectId = (int)$aRow["subject_id"];
    $iServedDays = (int)$aRow["days_to_birthday"];
    $sServedDays = $iServedDays < 0 ? "&#8722;" . html(abs($iServedDays)) : htmlValue($aRow["days_to_birthday"]);
    $sServedAction = $blShowActions ? "<a class=\"item-action birthday-served-action " . html($sServedActionClass) . "\" href=\"#\" data-subject-id=\"" . html($iSubjectId) . "\" title=\"" . html($sServedActionLabel) . "\" aria-label=\"" . html($sServedActionLabel) . "\"><span class=\"copy-action-box\">" . $sServedActionEmoji . "</span></a>" : "";
    $sServedInCell = $sServedDays . ($sServedAction != "" ? "&#8288;" . $sServedAction : "");
    return renderResponsiveSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aHiddenInactive, $aDisplaySettings, array_merge(array(
        "show_actions" => $blShowActions,
        "item_subject_id" => $iSubjectId,
        "before_name_cells" => array(renderSubjectTableCell($sServedInCell, "birthday-in-column")),
        "name_actions" => bdRenderSubjectActions($aRow, $blShowActions)
    ), $aOptions));
}

function bdRenderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aBirthdaySettings) {
    global $sBirthdayServedEmoji;

    return renderServedSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aBirthdaySettings, "js-birthday-served", "Mark birthday served", $sBirthdayServedEmoji, array(
        "nickname_show_add_action" => true,
        "nickname_show_cell_copy_action" => true,
        "nickname_cell_copy_before_add_action" => false,
        "address_show_add_action" => true,
        "address_show_cell_copy_action" => true,
        "address_cell_copy_before_add_action" => false,
        "contact_show_add_action" => true,
        "contact_show_cell_copy_action" => true,
        "contact_cell_copy_before_add_action" => false,
        "group_show_add_action" => true,
        "group_show_cell_copy_action" => true,
        "group_cell_copy_before_add_action" => false,
        "note_show_add_action" => true,
        "note_show_cell_copy_action" => true,
        "note_cell_copy_before_add_action" => false
    ));
}

function bdGetSubjectServedInfo($oPdo, $iSubjectId, $aRow) {
    $aBirthdayInfo = bdGetBirthdayInfo(isset($aRow["birth_date"]) ? $aRow["birth_date"] : "");
    if (!is_array($aBirthdayInfo)) {
        return null;
    }
    if (bdIsBirthdayServed(bdFetchBirthdayServedRows($oPdo), $iSubjectId, $aBirthdayInfo["birthday_date"])) {
        return null;
    }
    return $aBirthdayInfo;
}

function getUpdatedServedSubjectResponse($oPdo, $iSubjectId, $aDisplaySettings, $blShowActions, $sInfoFunction, $sRenderFunction) {
    $aRows = fetchSubjectRows($oPdo, $iSubjectId);
    if (!$aRows) {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aContacts = fetchSubjectContacts($oPdo, $iSubjectId);
    $aNicknames = fetchSubjectNicknames($oPdo, $iSubjectId);
    $aAddresses = fetchSubjectAddresses($oPdo, $iSubjectId);
    $aGroups = fetchSubjectGroups($oPdo, $iSubjectId);
    $aNotes = fetchSubjectNotes($oPdo, $iSubjectId);
    $aHiddenInactive = getHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aDisplaySettings);
    applySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aDisplaySettings);
    if (!$aRows || (string)$aRows[0]["subject_type"] != "person") {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aBirthdayInfo = $sInfoFunction($oPdo, $iSubjectId, $aRows[0]);
    if (!is_array($aBirthdayInfo)) {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aRows[0]["days_to_birthday"] = $aBirthdayInfo["days_to_birthday"];
    $aRows[0]["birthday_date"] = $aBirthdayInfo["birthday_date"];
    return array(
        "success" => true,
        "subject_id" => $iSubjectId,
        "row_html" => $sRenderFunction($aRows[0], $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aDisplaySettings)
    );
}

function bdGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blShowActions) {
    return getUpdatedServedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blShowActions, "bdGetSubjectServedInfo", "bdRenderSubjectRow");
}

function cardDavSendCommonHeaders() {
    header("DAV: 1, 3, addressbook", true);
    header("MS-Author-Via: DAV", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();
}

function cardDavSendTextAndExit($iStatusCode, $sText) {
    $sBody = (string)$sText . "\r\n";
    http_response_code($iStatusCode);
    header("Content-Type: text/plain; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    cardDavSendCommonHeaders();
    echo $sBody;
    exit;
}

function cardDavSendAuthChallengeAndExit() {
    $sBody = "Authentication required.\r\n";
    http_response_code(401);
    header("WWW-Authenticate: Basic realm=\"" . str_replace("\"", "", "EVED CardDAV") . "\", charset=\"UTF-8\"", true);
    header("Content-Type: text/plain; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    cardDavSendCommonHeaders();
    echo $sBody;
    exit;
}

function cardDavSendOptionsAndExit() {
    http_response_code(204);
    header("Allow: OPTIONS, PROPFIND, REPORT, GET, HEAD", true);
    header("Content-Length: 0", true);
    cardDavSendCommonHeaders();
    exit;
}

function cardDavHeaderValue($sName) {
    $sKey = "HTTP_" . strtoupper(str_replace("-", "_", $sName));
    if (isset($_SERVER[$sKey])) {
        return (string)$_SERVER[$sKey];
    }
    if ($sKey == "HTTP_AUTHORIZATION" && isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
        return (string)$_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
    }
    if (function_exists("apache_request_headers")) {
        $aHeaders = apache_request_headers();
        if (is_array($aHeaders)) {
            foreach ($aHeaders as $sHeaderName => $sHeaderValue) {
                if (strtolower((string)$sHeaderName) == strtolower($sName)) {
                    return (string)$sHeaderValue;
                }
            }
        }
    }
    return "";
}

function cardDavBasicCredentials() {
    $sUserName = isset($_SERVER["PHP_AUTH_USER"]) ? (string)$_SERVER["PHP_AUTH_USER"] : "";
    $sPassword = isset($_SERVER["PHP_AUTH_PW"]) ? (string)$_SERVER["PHP_AUTH_PW"] : "";
    $sAuthorization = "";
    $sDecoded = "";
    $iColon = 0;
    if ($sUserName != "" || $sPassword != "") {
        return array($sUserName, $sPassword);
    }
    $sAuthorization = trim(cardDavHeaderValue("Authorization"));
    if (!preg_match("/^Basic\\s+(.+)$/i", $sAuthorization, $aMatches)) {
        return array("", "");
    }
    $sDecoded = base64_decode($aMatches[1], true);
    if ($sDecoded === false) {
        return array("", "");
    }
    $iColon = strpos($sDecoded, ":");
    if ($iColon === false) {
        return array("", "");
    }
    return array(substr($sDecoded, 0, $iColon), substr($sDecoded, $iColon + 1));
}

function cardDavRequireUser($oPdo) {
    list($sUserName, $sPassword) = cardDavBasicCredentials();
    $aUser = null;
    if (trim($sUserName) == "" || $sPassword == "") {
        cardDavSendAuthChallengeAndExit();
    }
    try {
        $aUser = fetchPortalLoginUser($oPdo, trim($sUserName));
    } catch (Exception $oException) {
        error_log((string)$oException);
        cardDavSendTextAndExit(500, "Database error.");
    }
    if (!$aUser || (int)$aUser["is_active"] != 1 || (int)$aUser["subject_active"] != 1 || !in_array((string)$aUser["subject_type"], array("person", "service"), true) || !password_verify($sPassword, (string)$aUser["password_hash"])) {
        cardDavSendAuthChallengeAndExit();
    }
    if (!permissionArrayAllowsProjectView(fetchUserEffectivePermissions($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"]), "ex")) {
        cardDavSendAuthChallengeAndExit();
    }
    return $aUser;
}

function cardDavPathInfo() {
    $sPath = isset($_SERVER["PATH_INFO"]) ? (string)$_SERVER["PATH_INFO"] : "";
    $sRequestPath = "";
    $sScriptPath = "";
    if (isset($_GET["addressbook"])) {
        return "/addressbook/";
    }
    if (isset($_GET["principals"])) {
        return "/principals/";
    }
    if (isset($_GET["principal"])) {
        return "/principals/" . rawurlencode((string)$_GET["principal"]) . "/";
    }
    if (isset($_GET["card"])) {
        return "/ex-subject-" . (int)$_GET["card"] . ".vcf";
    }
    if (!$sPath) {
        $sRequestPath = isset($_SERVER["REQUEST_URI"]) ? (string)parse_url((string)$_SERVER["REQUEST_URI"], PHP_URL_PATH) : "";
        $sScriptPath = cardDavScriptPath();
        if ($sRequestPath != "" && strpos($sRequestPath, $sScriptPath) === 0) {
            $sPath = substr($sRequestPath, strlen($sScriptPath));
        }
    }
    if (!$sPath) {
        $sPath = "/";
    }
    $sPath = "/" . ltrim(str_replace("\\", "/", $sPath), "/");
    $sPath = preg_replace("#/+#", "/", $sPath);
    return $sPath;
}

function cardDavScriptPath() {
    $sPath = isset($_SERVER["SCRIPT_NAME"]) ? (string)$_SERVER["SCRIPT_NAME"] : "/carddav.php";
    $sRequestPath = "";
    $iPhpPos = false;
    $sPath = str_replace("\\", "/", $sPath);
    $iPhpPos = stripos($sPath, ".php");
    if ($iPhpPos !== false) {
        $sPath = substr($sPath, 0, $iPhpPos + 4);
    }
    if (!$sPath) {
        $sRequestPath = isset($_SERVER["REQUEST_URI"]) ? (string)parse_url((string)$_SERVER["REQUEST_URI"], PHP_URL_PATH) : "";
        $sRequestPath = str_replace("\\", "/", $sRequestPath);
        $iPhpPos = stripos($sRequestPath, ".php");
        $sPath = $iPhpPos !== false ? substr($sRequestPath, 0, $iPhpPos + 4) : "/carddav.php";
    }
    return $sPath;
}

function cardDavHref($aQuery) {
    $sHref = cardDavScriptPath();
    if (is_array($aQuery) && count($aQuery) > 0) {
        $sHref .= "?" . http_build_query($aQuery, "", "&");
    }
    return $sHref;
}

function cardDavIsAddressBookPath($sPath) {
    return (string)$sPath == "/addressbook" || (string)$sPath == "/addressbook/";
}

function cardDavIsPrincipalCollectionPath($sPath) {
    return (string)$sPath == "/principals" || (string)$sPath == "/principals/";
}

function cardDavXml($mValue) {
    return htmlspecialchars((string)$mValue, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, "UTF-8");
}

function cardDavVCardEscape($mValue) {
    $sValue = (string)$mValue;
    $sValue = str_replace("\\", "\\\\", $sValue);
    $sValue = str_replace("\r\n", "\\n", $sValue);
    $sValue = str_replace("\r", "\\n", $sValue);
    $sValue = str_replace("\n", "\\n", $sValue);
    $sValue = str_replace(";", "\\;", $sValue);
    $sValue = str_replace(",", "\\,", $sValue);
    return $sValue;
}

function cardDavVCardList($aValues) {
    $aEscaped = array();
    foreach ($aValues as $sValue) {
        $sValue = trim((string)$sValue);
        if ($sValue != "") {
            $aEscaped[] = cardDavVCardEscape($sValue);
        }
    }
    return implode(",", $aEscaped);
}

function cardDavVCardLine($sName, $mValue, $sParams = "") {
    $sLine = strtoupper((string)$sName) . (trim((string)$sParams) != "" ? ";" . trim((string)$sParams) : "") . ":" . cardDavVCardEscape($mValue);
    return $sLine;
}

function cardDavVCardRawLine($sName, $mValue, $sParams = "") {
    return strtoupper((string)$sName) . (trim((string)$sParams) != "" ? ";" . trim((string)$sParams) : "") . ":" . (string)$mValue;
}

function cardDavCleanTypeToken($sValue) {
    $sValue = strtoupper(preg_replace("/[^A-Za-z0-9\\-]/", "-", (string)$sValue));
    $sValue = trim($sValue, "-");
    return $sValue != "" ? $sValue : "OTHER";
}

function cardDavAddressType($sAddressType) {
    $sAddressType = (string)$sAddressType;
    if ($sAddressType == "home" || $sAddressType == "cottage" || $sAddressType == "temporary") {
        return "HOME";
    }
    if ($sAddressType == "work" || $sAddressType == "office") {
        return "WORK";
    }
    if ($sAddressType == "delivery") {
        return "POSTAL";
    }
    if ($sAddressType == "billing") {
        return "PARCEL";
    }
    return "OTHER";
}

function cardDavPhoneType($sContactType) {
    $sContactType = (string)$sContactType;
    if ($sContactType == "cell" || $sContactType == "mobile" || $sContactType == "whatsapp" || $sContactType == "viber") {
        return "CELL";
    }
    if ($sContactType == "fax") {
        return "FAX";
    }
    if ($sContactType == "pager") {
        return "PAGER";
    }
    return "VOICE";
}

function cardDavAddressStreet($aAddress) {
    $aNumbers = array();
    $sHouseNumber = trim((string)$aAddress["house_number"]);
    $sEvidenceNumber = trim((string)$aAddress["evidence_number"]);
    $sOrientationNumber = trim((string)$aAddress["orientation_number"]);
    $sOrientationSuffix = trim((string)$aAddress["orientation_suffix"]);
    $sStreet = trim((string)$aAddress["street_name"]);
    if ($sHouseNumber != "") {
        $aNumbers[] = $sHouseNumber;
    } elseif ($sEvidenceNumber != "") {
        $aNumbers[] = $sEvidenceNumber;
    }
    if ($sOrientationNumber != "") {
        $aNumbers[] = $sOrientationNumber . $sOrientationSuffix;
    }
    if (count($aNumbers) > 0) {
        $sStreet = trim($sStreet . " " . implode("/", $aNumbers));
    }
    return $sStreet;
}

function cardDavAddressExtended($aAddress) {
    $aParts = array();
    foreach (array("organization_name", "department_name", "care_of", "address_line2") as $sKey) {
        $sValue = trim((string)$aAddress[$sKey]);
        if ($sValue != "") {
            $aParts[] = $sValue;
        }
    }
    return implode(", ", $aParts);
}

function cardDavAddressLabel($aAddress) {
    $aLines = array();
    $sExtended = cardDavAddressExtended($aAddress);
    $sStreet = cardDavAddressStreet($aAddress);
    $sCity = trim((string)$aAddress["city"]);
    $sCityPart = trim((string)$aAddress["city_part"]);
    $sPostalCode = postalCodeDisplayValue($aAddress["country"], $aAddress["postal_code"]);
    $sRegion = trim((string)$aAddress["region"]);
    $sCountry = countryCodeToName($aAddress["country"]);
    if ($sExtended != "") {
        $aLines[] = $sExtended;
    }
    if ($sStreet != "") {
        $aLines[] = $sStreet;
    }
    if ($sCityPart != "" && $sCityPart != $sCity) {
        $aLines[] = $sCityPart;
    }
    $aLines[] = trim($sPostalCode . " " . $sCity);
    if ($sRegion != "") {
        $aLines[] = $sRegion;
    }
    if ($sCountry != "") {
        $aLines[] = $sCountry;
    }
    $aResult = array();
    foreach ($aLines as $sLine) {
        $sLine = trim((string)$sLine);
        if ($sLine != "") {
            $aResult[] = $sLine;
        }
    }
    return implode("\n", $aResult);
}

function cardDavAddVCardContactLines(&$aLines, $aContact) {
    $sType = (string)$aContact["contact_type"];
    $sTypeName = trim((string)$aContact["contact_type_name"]);
    $sValue = contactDisplayValue($sType, $aContact["contact_value"]);
    $sHref = contactHref($sType, $aContact["contact_value"], true);
    $sPref = (int)$aContact["is_primary"] == 1 ? ",PREF" : "";
    if ($sValue == "") {
        return;
    }
    if ($sType == "email") {
        $aLines[] = cardDavVCardLine("EMAIL", $sValue, "TYPE=INTERNET" . $sPref);
        return;
    }
    if (isPhoneContactType($sType) || $sType == "whatsapp" || $sType == "viber") {
        $aLines[] = cardDavVCardLine("TEL", $sValue, "TYPE=" . cardDavPhoneType($sType) . $sPref);
        return;
    }
    if ($sType == "web" || preg_match("#^https?://#i", $sHref)) {
        $aLines[] = cardDavVCardLine("URL", $sHref != "" ? $sHref : $sValue, "TYPE=" . cardDavCleanTypeToken($sTypeName != "" ? $sTypeName : $sType));
        return;
    }
    if ($sType == "jabber") {
        $aLines[] = cardDavVCardLine("X-JABBER", $sValue);
        $aLines[] = cardDavVCardLine("IMPP", "xmpp:" . $sValue, "TYPE=" . cardDavCleanTypeToken($sTypeName != "" ? $sTypeName : $sType));
        return;
    }
    if ($sHref != "") {
        $aLines[] = cardDavVCardLine("IMPP", $sHref, "TYPE=" . cardDavCleanTypeToken($sTypeName != "" ? $sTypeName : $sType));
        return;
    }
    $aLines[] = cardDavVCardLine("X-EVED-CONTACT", ($sTypeName != "" ? $sTypeName : $sType) . ": " . $sValue);
}

function cardDavBuildCard($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes) {
    $iSubjectId = (int)$aRow["subject_id"];
    $sSubjectType = (string)$aRow["subject_type"];
    $sFullName = trim((string)$aRow["subject_name"]);
    $aLines = array();
    $aActiveNicknames = array();
    $aActiveGroups = array();
    $aActiveNotes = array();
    $sUid = "ex-subject-" . $iSubjectId;
    if ($sFullName == "") {
        $sFullName = "Unnamed subject";
    }
    $aLines[] = "BEGIN:VCARD";
    $aLines[] = "VERSION:3.0";
    $aLines[] = cardDavVCardLine("PRODID", "-//EVED//Readonly CardDAV//EN");
    $aLines[] = cardDavVCardLine("UID", $sUid);
    $aLines[] = cardDavVCardLine("FN", $sFullName);
    if ($sSubjectType == "person") {
        $aLines[] = cardDavVCardRawLine(
            "N",
            cardDavVCardEscape($aRow["last_name"]) . ";"
            . cardDavVCardEscape($aRow["first_name"]) . ";"
            . cardDavVCardEscape($aRow["middle_name"]) . ";"
            . cardDavVCardEscape($aRow["title_before"]) . ";"
            . cardDavVCardEscape($aRow["title_after"])
        );
        if (trim((string)$aRow["birth_date"]) != "") {
            $aLines[] = cardDavVCardLine("BDAY", $aRow["birth_date"]);
        }
        if (trim((string)$aRow["death_date"]) != "") {
            $aLines[] = cardDavVCardLine("X-DEATHDATE", $aRow["death_date"]);
        }
    } else {
        $aLines[] = cardDavVCardRawLine("N", ";" . cardDavVCardEscape($sFullName) . ";;;");
        $aLines[] = cardDavVCardLine("ORG", $sFullName);
    }
    foreach ($aNicknames as $aNickname) {
        if ((int)$aNickname["is_active"] == 1 && trim((string)$aNickname["nickname"]) != "") {
            $aActiveNicknames[] = (string)$aNickname["nickname"];
        }
    }
    if (count($aActiveNicknames) > 0) {
        $aLines[] = cardDavVCardRawLine("NICKNAME", cardDavVCardList($aActiveNicknames));
    }
    foreach ($aContacts as $aContact) {
        if ((int)$aContact["is_active"] == 1) {
            cardDavAddVCardContactLines($aLines, $aContact);
        }
    }
    foreach ($aAddresses as $aAddress) {
        if ((int)$aAddress["is_active"] != 1) {
            continue;
        }
        $sAdrType = cardDavAddressType($aAddress["address_type"]) . ((int)$aAddress["is_primary"] == 1 ? ",PREF" : "");
        $sCountry = countryCodeToName($aAddress["country"]);
        $sAdrValue = ";"
            . cardDavVCardEscape(cardDavAddressExtended($aAddress)) . ";"
            . cardDavVCardEscape(cardDavAddressStreet($aAddress)) . ";"
            . cardDavVCardEscape($aAddress["city"]) . ";"
            . cardDavVCardEscape($aAddress["region"]) . ";"
            . cardDavVCardEscape(postalCodeDisplayValue($aAddress["country"], $aAddress["postal_code"])) . ";"
            . cardDavVCardEscape($sCountry);
        $aLines[] = cardDavVCardRawLine("ADR", $sAdrValue, "TYPE=" . $sAdrType);
        $aLines[] = cardDavVCardLine("LABEL", cardDavAddressLabel($aAddress), "TYPE=" . $sAdrType);
    }
    foreach ($aGroups as $aGroup) {
        if (trim((string)$aGroup["name"]) != "") {
            $aActiveGroups[] = (string)$aGroup["name"];
        }
    }
    if (count($aActiveGroups) > 0) {
        $aLines[] = cardDavVCardRawLine("CATEGORIES", cardDavVCardList($aActiveGroups));
    }
    foreach ($aNotes as $aNote) {
        if ((int)$aNote["is_active"] == 1 && trim((string)$aNote["note_text"]) != "") {
            $aActiveNotes[] = (string)$aNote["note_text"];
        }
    }
    if (count($aActiveNotes) > 0) {
        $aLines[] = cardDavVCardLine("NOTE", implode("\n\n", $aActiveNotes));
    }
    $aLines[] = cardDavVCardLine("X-EVED-SUBJECT-ID", $iSubjectId);
    $aLines[] = cardDavVCardLine("X-EVED-SUBJECT-TYPE", $sSubjectType);
    $aLines[] = "END:VCARD";
    return implode("\r\n", $aLines) . "\r\n";
}

function cardDavFetchCards($oPdo) {
    $aCards = array();
    $aRows = array();
    $aContacts = array();
    $aNicknames = array();
    $aAddresses = array();
    $aGroups = array();
    $aNotes = array();
    $oPdo->query("SET SESSION group_concat_max_len = 1048576");
    $aRows = fetchSubjectRows($oPdo);
    $aContacts = fetchSubjectContacts($oPdo);
    $aNicknames = fetchSubjectNicknames($oPdo);
    $aAddresses = fetchSubjectAddresses($oPdo);
    $aGroups = fetchSubjectGroups($oPdo);
    $aNotes = fetchSubjectNotes($oPdo);
    foreach ($aRows as $aRow) {
        $iSubjectId = (int)$aRow["subject_id"];
        $sBody = "";
        if ((int)$aRow["is_active"] != 1) {
            continue;
        }
        $sBody = cardDavBuildCard(
            $aRow,
            isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(),
            isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(),
            isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(),
            isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(),
            isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array()
        );
        $aCards[$iSubjectId] = array(
            "subject_id" => $iSubjectId,
            "display_name" => (string)$aRow["subject_name"],
            "href" => cardDavHref(array("card" => (int)$iSubjectId)),
            "body" => $sBody,
            "etag" => "\"" . sha1($sBody) . "\"",
            "last_modified" => trim((string)$aRow["created_at"]) != "" ? strtotime((string)$aRow["created_at"]) : time()
        );
    }
    return $aCards;
}

function cardDavCollectionTag($aCards) {
    $aEtags = array();
    foreach ($aCards as $aCard) {
        $aEtags[] = (string)$aCard["etag"];
    }
    sort($aEtags);
    return sha1(implode("\n", $aEtags));
}

function cardDavResponseStart($sHref) {
    return "  <d:response>\r\n"
        . "    <d:href>" . cardDavXml($sHref) . "</d:href>\r\n"
        . "    <d:propstat>\r\n"
        . "      <d:prop>\r\n";
}

function cardDavResponseEnd() {
    return "      </d:prop>\r\n"
        . "      <d:status>HTTP/1.1 200 OK</d:status>\r\n"
        . "    </d:propstat>\r\n"
        . "  </d:response>\r\n";
}

function cardDavCollectionPropsXml($aCards, $aUser) {
    $sHomeHref = cardDavHref(array());
    $sPrincipalHref = cardDavHref(array("principal" => (string)$aUser["user_name"]));
    $sPrincipalCollectionHref = cardDavHref(array("principals" => "1"));
    $sCollectionHref = cardDavHref(array());
    return "        <d:resourcetype><d:collection/><card:addressbook/></d:resourcetype>\r\n"
        . "        <d:displayname>EVED Contacts</d:displayname>\r\n"
        . "        <d:current-user-principal><d:href>" . cardDavXml($sPrincipalHref) . "</d:href></d:current-user-principal>\r\n"
        . "        <d:principal-URL><d:href>" . cardDavXml($sPrincipalHref) . "</d:href></d:principal-URL>\r\n"
        . "        <d:principal-collection-set><d:href>" . cardDavXml($sPrincipalCollectionHref) . "</d:href></d:principal-collection-set>\r\n"
        . "        <d:owner><d:href>" . cardDavXml($sPrincipalHref) . "</d:href></d:owner>\r\n"
        . "        <card:addressbook-home-set><d:href>" . cardDavXml($sHomeHref) . "</d:href></card:addressbook-home-set>\r\n"
        . "        <card:addressbook-description>EVED readonly contacts</card:addressbook-description>\r\n"
        . "        <card:supported-address-data><card:address-data content-type=\"text/vcard\" version=\"3.0\"/></card:supported-address-data>\r\n"
        . "        <cs:getctag>" . cardDavXml(cardDavCollectionTag($aCards)) . "</cs:getctag>\r\n"
        . "        <d:sync-token>" . cardDavXml($sCollectionHref . cardDavCollectionTag($aCards)) . "</d:sync-token>\r\n"
        . "        <d:current-user-privilege-set><d:privilege><d:read/></d:privilege></d:current-user-privilege-set>\r\n"
        . "        <d:supported-report-set>\r\n"
        . "          <d:supported-report><d:report><card:addressbook-query/></d:report></d:supported-report>\r\n"
        . "          <d:supported-report><d:report><card:addressbook-multiget/></d:report></d:supported-report>\r\n"
        . "        </d:supported-report-set>\r\n";
}

function cardDavPrincipalPropsXml($aUser) {
    $sHomeHref = cardDavHref(array());
    $sPrincipalHref = cardDavHref(array("principal" => (string)$aUser["user_name"]));
    $sPrincipalCollectionHref = cardDavHref(array("principals" => "1"));
    return "        <d:resourcetype><d:collection/><d:principal/></d:resourcetype>\r\n"
        . "        <d:displayname>" . cardDavXml($aUser["user_name"]) . "</d:displayname>\r\n"
        . "        <d:current-user-principal><d:href>" . cardDavXml($sPrincipalHref) . "</d:href></d:current-user-principal>\r\n"
        . "        <d:principal-URL><d:href>" . cardDavXml($sPrincipalHref) . "</d:href></d:principal-URL>\r\n"
        . "        <d:principal-collection-set><d:href>" . cardDavXml($sPrincipalCollectionHref) . "</d:href></d:principal-collection-set>\r\n"
        . "        <card:addressbook-home-set><d:href>" . cardDavXml($sHomeHref) . "</d:href></card:addressbook-home-set>\r\n";
}

function cardDavPrincipalCollectionPropsXml($aUser) {
    $sPrincipalHref = cardDavHref(array("principal" => (string)$aUser["user_name"]));
    $sPrincipalCollectionHref = cardDavHref(array("principals" => "1"));
    return "        <d:resourcetype><d:collection/></d:resourcetype>\r\n"
        . "        <d:displayname>EVED Principals</d:displayname>\r\n"
        . "        <d:current-user-principal><d:href>" . cardDavXml($sPrincipalHref) . "</d:href></d:current-user-principal>\r\n"
        . "        <d:principal-collection-set><d:href>" . cardDavXml($sPrincipalCollectionHref) . "</d:href></d:principal-collection-set>\r\n";
}

function cardDavCardPropsXml($aCard, $blIncludeAddressData) {
    $sXml = "        <d:resourcetype/>\r\n"
        . "        <d:getcontenttype>text/vcard; charset=utf-8</d:getcontenttype>\r\n"
        . "        <d:getcontentlength>" . strlen($aCard["body"]) . "</d:getcontentlength>\r\n"
        . "        <d:getetag>" . cardDavXml($aCard["etag"]) . "</d:getetag>\r\n"
        . "        <d:getlastmodified>" . gmdate("D, d M Y H:i:s", (int)$aCard["last_modified"]) . " GMT</d:getlastmodified>\r\n";
    if ($blIncludeAddressData) {
        $sXml .= "        <card:address-data>" . cardDavXml($aCard["body"]) . "</card:address-data>\r\n";
    }
    return $sXml;
}

function cardDavMultistatusAndExit($sInnerXml) {
    $sBody = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n"
        . "<d:multistatus xmlns:d=\"DAV:\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cs=\"http://calendarserver.org/ns/\">\r\n"
        . $sInnerXml . "</d:multistatus>\r\n";
    http_response_code(207);
    header("Content-Type: application/xml; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    cardDavSendCommonHeaders();
    echo $sBody;
    exit;
}

function cardDavSubjectIdFromPath($sPath) {
    return preg_match("#^/(?:addressbook/)?ex-subject-([0-9]+)\\.vcf$#", $sPath, $aMatches) ? (int)$aMatches[1] : 0;
}

function cardDavSubjectIdFromHref($sHref) {
    $sPath = (string)$sHref;
    $aParts = parse_url($sPath);
    $aQuery = array();
    if (is_array($aParts) && isset($aParts["query"])) {
        parse_str((string)$aParts["query"], $aQuery);
        if (isset($aQuery["card"])) {
            return (int)$aQuery["card"];
        }
    }
    if (is_array($aParts) && isset($aParts["path"])) {
        $sPath = (string)$aParts["path"];
    }
    $sPath = rawurldecode($sPath);
    if (strpos($sPath, cardDavScriptPath()) === 0) {
        $sPath = substr($sPath, strlen(cardDavScriptPath()));
    }
    if (!$sPath) {
        $sPath = "/";
    }
    if ($sPath[0] != "/") {
        $sPath = "/" . $sPath;
    }
    return cardDavSubjectIdFromPath($sPath);
}

function cardDavRequestBody() {
    $sBody = file_get_contents("php://input");
    return $sBody !== false ? $sBody : "";
}

function cardDavRequestHrefs($sBody) {
    $aHrefs = array();
    $oDom = null;
    $oNodes = null;
    if (trim($sBody) == "" || !class_exists("DOMDocument")) {
        return $aHrefs;
    }
    $oDom = new DOMDocument();
    if (!@$oDom->loadXML($sBody)) {
        return $aHrefs;
    }
    $oNodes = $oDom->getElementsByTagNameNS("DAV:", "href");
    foreach ($oNodes as $oNode) {
        $aHrefs[] = (string)$oNode->textContent;
    }
    return $aHrefs;
}

function cardDavSendPropfindAndExit($aCards, $aUser, $sPath) {
    $sDepth = isset($_SERVER["HTTP_DEPTH"]) ? (string)$_SERVER["HTTP_DEPTH"] : "infinity";
    $sXml = "";
    if ((string)$sPath == "/") {
        $sXml .= cardDavResponseStart(cardDavHref(array()))
            . cardDavCollectionPropsXml($aCards, $aUser)
            . cardDavResponseEnd();
        if ($sDepth != "0") {
            foreach ($aCards as $aCard) {
                $sXml .= cardDavResponseStart($aCard["href"])
                    . cardDavCardPropsXml($aCard, false)
                    . cardDavResponseEnd();
            }
        }
        cardDavMultistatusAndExit($sXml);
    }
    if (cardDavIsAddressBookPath($sPath)) {
        $sXml .= cardDavResponseStart(cardDavHref(array()))
            . cardDavCollectionPropsXml($aCards, $aUser)
            . cardDavResponseEnd();
        if ($sDepth != "0") {
            foreach ($aCards as $aCard) {
                $sXml .= cardDavResponseStart($aCard["href"])
                    . cardDavCardPropsXml($aCard, false)
                    . cardDavResponseEnd();
            }
        }
        cardDavMultistatusAndExit($sXml);
    }
    if (cardDavIsPrincipalCollectionPath($sPath)) {
        $sXml .= cardDavResponseStart(cardDavHref(array("principals" => "1")))
            . cardDavPrincipalCollectionPropsXml($aUser)
            . cardDavResponseEnd();
        if ($sDepth != "0") {
            $sXml .= cardDavResponseStart(cardDavHref(array("principal" => (string)$aUser["user_name"])))
                . cardDavPrincipalPropsXml($aUser)
                . cardDavResponseEnd();
        }
        cardDavMultistatusAndExit($sXml);
    }
    if (preg_match("#^/principals/[^/]+/?$#", $sPath)) {
        $sXml .= cardDavResponseStart(cardDavHref(array("principal" => (string)$aUser["user_name"])))
            . cardDavPrincipalPropsXml($aUser)
            . cardDavResponseEnd();
        cardDavMultistatusAndExit($sXml);
    }
    $iSubjectId = cardDavSubjectIdFromPath($sPath);
    if ($iSubjectId > 0 && isset($aCards[$iSubjectId])) {
        $sXml .= cardDavResponseStart($aCards[$iSubjectId]["href"])
            . cardDavCardPropsXml($aCards[$iSubjectId], false)
            . cardDavResponseEnd();
        cardDavMultistatusAndExit($sXml);
    }
    cardDavSendTextAndExit(404, "Not found.");
}

function cardDavSendReportAndExit($aCards, $sPath) {
    $sBody = cardDavRequestBody();
    $aHrefs = cardDavRequestHrefs($sBody);
    $aWantedIds = array();
    $sXml = "";
    $blIncludeAddressData = stripos($sBody, "address-data") !== false;
    if ((string)$sPath != "/" && !cardDavIsAddressBookPath($sPath)) {
        cardDavSendTextAndExit(404, "Not found.");
    }
    foreach ($aHrefs as $sHref) {
        $iSubjectId = cardDavSubjectIdFromHref($sHref);
        if ($iSubjectId > 0) {
            $aWantedIds[$iSubjectId] = true;
        }
    }
    foreach ($aCards as $iSubjectId => $aCard) {
        if (count($aWantedIds) > 0 && empty($aWantedIds[$iSubjectId])) {
            continue;
        }
        $sXml .= cardDavResponseStart($aCard["href"])
            . cardDavCardPropsXml($aCard, $blIncludeAddressData)
            . cardDavResponseEnd();
    }
    cardDavMultistatusAndExit($sXml);
}

function cardDavSendGetAndExit($aCards, $sPath, $blHeadOnly) {
    $iSubjectId = cardDavSubjectIdFromPath($sPath);
    $aCard = null;
    if ((string)$sPath == "/" || cardDavIsAddressBookPath($sPath)) {
        cardDavSendCollectionGetAndExit($aCards, $sPath, $blHeadOnly);
    }
    if ($iSubjectId < 1 || !isset($aCards[$iSubjectId])) {
        cardDavSendTextAndExit(404, "Not found.");
    }
    $aCard = $aCards[$iSubjectId];
    http_response_code(200);
    header("Content-Type: text/vcard; charset=utf-8", true);
    header("Content-Length: " . strlen($aCard["body"]), true);
    header("ETag: " . $aCard["etag"], true);
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", (int)$aCard["last_modified"]) . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    cardDavSendCommonHeaders();
    if (!$blHeadOnly) {
        echo $aCard["body"];
    }
    exit;
}

function cardDavSendCollectionGetAndExit($aCards, $sPath, $blHeadOnly) {
    $sBody = "EVED CardDAV endpoint\r\n"
        . "\r\n"
        . "CardDAV home: " . cardDavHref(array()) . "\r\n"
        . "Address book: " . cardDavHref(array()) . "\r\n"
        . "Contacts: " . count($aCards) . "\r\n"
        . "\r\n"
        . "Use a CardDAV client such as Thunderbird. This endpoint is read-only.\r\n";
    http_response_code(200);
    header("Content-Type: text/plain; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    cardDavSendCommonHeaders();
    if (!$blHeadOnly) {
        echo $sBody;
    }
    exit;
}

function contactsNormalizeKey($sValue) {
    if (function_exists("mb_strtolower")) {
        return mb_strtolower((string)$sValue, "UTF-8");
    }
    return strtolower((string)$sValue);
}

function contactsCompareRows($aFirst, $aSecond) {
    $iResult = strcmp((string)$aFirst["contact_sort"], (string)$aSecond["contact_sort"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    $iResult = (int)$aFirst["contact_type_order"] - (int)$aSecond["contact_type_order"];
    if ($iResult !== 0) {
        return $iResult;
    }
    $iResult = strcmp((string)$aFirst["contact_type_sort"], (string)$aSecond["contact_type_sort"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["contact_id"] - (int)$aSecond["contact_id"];
}

function contactsCompareSubjects($aFirst, $aSecond) {
    $iResult = strcmp((string)$aFirst["subject_name"], (string)$aSecond["subject_name"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["subject_contact_id"] - (int)$aSecond["subject_contact_id"];
}

function contactsSubjectCellClass($aSubject) {
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aSubject["subject_type"]));
    return "contact-subject-cell contact-subject-type-" . $sSubjectType . (!empty($aSubject["is_active"]) && (int)$aSubject["contact_is_active"] == 1 ? " contact-subject-active" : " contact-subject-inactive");
}

function contactsRenderContactDataAttributes($aContactRow) {
    return " data-contact-id=\"" . html($aContactRow["contact_id"]) . "\""
        . " data-contact-type-id=\"" . html($aContactRow["contact_type_id"]) . "\""
        . " data-contact-type=\"" . html($aContactRow["contact_type"]) . "\""
        . " data-contact-type-name=\"" . html($aContactRow["contact_type_name"]) . "\""
        . " data-contact-value=\"" . html($aContactRow["contact_display_value"]) . "\""
        . renderTimestampTooltipDataAttribute($aContactRow);
}

function contactsRenderSubjectDataAttributes($aSubject) {
    return " data-subject-contact-id=\"" . html($aSubject["subject_contact_id"]) . "\""
        . " data-subject-id=\"" . html($aSubject["subject_id"]) . "\""
        . " data-contact-id=\"" . html($aSubject["contact_id"]) . "\""
        . " data-contact-type-id=\"" . html($aSubject["contact_type_id"]) . "\""
        . " data-contact-type=\"" . html($aSubject["contact_type"]) . "\""
        . " data-contact-type-name=\"" . html($aSubject["contact_type_name"]) . "\""
        . " data-contact-value=\"" . html($aSubject["contact_display_value"]) . "\""
        . " data-contact-note=\"" . html($aSubject["note"]) . "\""
        . " data-contact-primary=\"" . ((int)$aSubject["is_primary"] == 1 ? "1" : "0") . "\""
        . " data-contact-active=\"" . ((int)$aSubject["contact_is_active"] == 1 ? "1" : "0") . "\""
        . " data-subject-active=\"" . (!empty($aSubject["is_active"]) ? "1" : "0") . "\"";
}

function contactsFilterText($aContactRow) {
    $sContactFilterText = (string)$aContactRow["contact_type_name"] . " " . (string)$aContactRow["contact_display_value"];
    foreach ($aContactRow["subjects"] as $aFilterSubject) {
        $sContactFilterText .= " " . (string)$aFilterSubject["subject_name"];
    }
    return $sContactFilterText;
}

function contactsRenderSubjectCell($aSubject, $sContactFilterText, $blCanEdit) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    $sSubjectTimestampTooltipText = timestampTooltipText($aSubject);
    $sSubjectTimestampTooltipAttribute = $sSubjectTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sSubjectTimestampTooltipText)) . "\"" : "";
    $sSubjectActions = $blCanEdit ? "<span class=\"list-item-actions\"><a href=\"#\" class=\"item-action js-edit-subject-contact\" title=\"Edit subject contact\" aria-label=\"Edit subject contact\">" . $sEditEmoji . "</a><a href=\"#\" class=\"item-action js-delete-subject-contact\" title=\"Delete subject contact\" aria-label=\"Delete subject contact\">" . $sDeleteEmoji . "</a></span>" : "";
    $sSubjectEditAction = $blCanEdit ? "<span class=\"list-item-actions\"><a href=\"#\" class=\"item-action js-edit-subject\" data-subject-id=\"" . html($aSubject["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a></span>" : "";
    $sSubjectPrimaryFlag = "<span class=\"contact-flags\"><span class=\"contact-primary\" title=\"Primary\">" . ((int)$aSubject["is_primary"] == 1 ? $sPrimaryEmoji : "") . "</span><span class=\"contact-inactive-label\" title=\"Inactive\">" . ((int)$aSubject["contact_is_active"] == 1 ? "" : $sInactiveEmoji) . "</span></span>";
    return "        <td class=\"" . html(contactsSubjectCellClass($aSubject)) . " list-item\"" . contactsRenderSubjectDataAttributes($aSubject) . "><span class=\"column-hidden\">" . htmlValue($sContactFilterText) . "</span><span class=\"subject-item-value\"" . $sSubjectTimestampTooltipAttribute . ">" . htmlValue($aSubject["subject_name"]) . "</span>" . renderCopyAction($aSubject["subject_name"]) . $sSubjectEditAction . "<span class=\"contact-item contact-subject-item\"" . contactsRenderSubjectDataAttributes($aSubject) . "><span class=\"contact-note\">" . ($aSubject["note"] != "" ? "(" . html($aSubject["note"]) . ")" : "") . "</span>" . $sSubjectPrimaryFlag . $sSubjectActions . "</span></td>\n";
}

function contactsFetchRows($oPdo, $aContactSettings) {
    $aRows = array();
    $aSubjectNames = array();
    $aSubjectRows = fetchSubjectRows($oPdo);
    foreach ($aSubjectRows as $aSubjectRow) {
        if (empty($aContactSettings["show_inactive_subjects"]) && (int)$aSubjectRow["is_active"] != 1) {
            continue;
        }
        $aSubjectNames[(int)$aSubjectRow["subject_id"]] = array(
            "subject_id" => (int)$aSubjectRow["subject_id"],
            "subject_name" => (string)$aSubjectRow["subject_name"],
            "subject_type" => (string)$aSubjectRow["subject_type"],
            "is_active" => (int)$aSubjectRow["is_active"] == 1,
            "created_at" => (string)$aSubjectRow["created_at"],
            "updated_at" => (string)$aSubjectRow["updated_at"]
        );
    }
    $sSql = "SELECT c.id AS contact_id, c.contact_type_id, c.contact_value, c.created_at, c.updated_at, COALESCE(ct.contact_type, '') AS contact_type, COALESCE(ct.name, '') AS contact_type_name, COALESCE(ct.`order`, 999999) AS contact_type_order, sc.id AS subject_contact_id, sc.subject_id, sc.is_primary, sc.is_active AS contact_is_active, sc.note FROM ex_contacts AS c LEFT JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id LEFT JOIN ex_subject_contacts AS sc ON sc.contact_id = c.id ORDER BY c.contact_value ASC, COALESCE(ct.`order`, 999999) ASC, COALESCE(ct.name, '') ASC, c.id ASC, sc.is_active DESC, sc.is_primary DESC, sc.id ASC";
    $oStatement = $oPdo->prepare($sSql);
    $oStatement->execute();
    while ($aContact = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $iSubjectId = (int)$aContact["subject_id"];
        $iContactId = (int)$aContact["contact_id"];
        $sContactType = (string)$aContact["contact_type"];
        $sContactDisplayValue = contactDisplayValue($sContactType, $aContact["contact_value"]);
        if (!isset($aRows[$iContactId])) {
            $aRows[$iContactId] = array(
                "contact_id" => $iContactId,
                "contact_type_id" => (int)$aContact["contact_type_id"],
                "contact_type" => $sContactType,
                "contact_type_name" => (string)$aContact["contact_type_name"],
                "contact_type_order" => (int)$aContact["contact_type_order"],
                "contact_type_sort" => contactsNormalizeKey((string)$aContact["contact_type_name"]),
                "contact_value" => (string)$aContact["contact_value"],
                "contact_display_value" => $sContactDisplayValue,
                "contact_sort" => contactsNormalizeKey($sContactDisplayValue),
                "created_at" => (string)$aContact["created_at"],
                "updated_at" => (string)$aContact["updated_at"],
                "subject_link_count" => 0,
                "subjects" => array()
            );
        }
        if ((int)$aContact["subject_contact_id"] < 1) {
            continue;
        }
        $aRows[$iContactId]["subject_link_count"] += 1;
        if (!isset($aSubjectNames[$iSubjectId])) {
            continue;
        }
        if (empty($aContactSettings["show_inactive_contacts"]) && (int)$aContact["contact_is_active"] != 1) {
            continue;
        }
        $aRows[$iContactId]["subjects"][] = array_merge($aSubjectNames[$iSubjectId], array(
            "subject_contact_id" => (int)$aContact["subject_contact_id"],
            "contact_id" => $iContactId,
            "contact_type_id" => (int)$aContact["contact_type_id"],
            "contact_type" => $sContactType,
            "contact_type_name" => (string)$aContact["contact_type_name"],
            "contact_value" => (string)$aContact["contact_value"],
            "contact_display_value" => $sContactDisplayValue,
            "note" => (string)$aContact["note"],
            "is_primary" => (int)$aContact["is_primary"],
            "contact_is_active" => (int)$aContact["contact_is_active"]
        ));
    }
    foreach ($aRows as $iContactId => $aRow) {
        if (!$aRow["subjects"] && (int)$aRow["subject_link_count"] > 0) {
            unset($aRows[$iContactId]);
            continue;
        }
        usort($aRows[$iContactId]["subjects"], "contactsCompareSubjects");
    }
    uasort($aRows, "contactsCompareRows");
    return $aRows;
}

function renderContactTypeAdminRows($oPdo, $blCanEdit) {
    $sHtml = "";
    foreach (fetchContactTypeAdminRows($oPdo) as $aContactType) {
        $sHtml .= renderContactTypeAdminRow($aContactType, $blCanEdit);
    }
    return $sHtml;
}

function getDemoFullListComplexFilterFields() {
    return array(
        "subject_type" => array("label" => "Type", "value_type" => "subject_type"),
        "subject_name" => array("label" => "Name"),
        "title_before" => array("label" => "Title Before", "scope_type" => "person"),
        "first_name" => array("label" => "First Name", "scope_type" => "person"),
        "middle_name" => array("label" => "Middle Name", "scope_type" => "person"),
        "last_name" => array("label" => "Last Name", "scope_type" => "person"),
        "title_after" => array("label" => "Title After", "scope_type" => "person"),
        "birth_name" => array("label" => "Birth Name", "scope_type" => "person"),
        "birth_number" => array("label" => "Birth Number", "value_type" => "birth_number", "scope_type" => "person"),
        "birth_date" => array("label" => "Birth Date", "value_type" => "date", "scope_type" => "person"),
        "death_date" => array("label" => "Death Date", "value_type" => "date", "scope_type" => "person"),
        "birthday_served_at" => array("label" => "Birthday Served At", "value_type" => "datetime", "scope_type" => "person"),
        "inter_served_at" => array("label" => "Interaction Served At", "value_type" => "datetime", "scope_type" => "person"),
        "nicknames" => array("label" => "Nicknames"),
        "addresses" => array("label" => "Addresses"),
        "address_type" => array("label" => "Address Type", "address_column" => "address_type", "value_type" => "address_type"),
        "organization_name" => array("label" => "Organization Name", "address_column" => "organization_name"),
        "department_name" => array("label" => "Department Name", "address_column" => "department_name"),
        "care_of" => array("label" => "Care Of", "address_column" => "care_of"),
        "street_name" => array("label" => "Street Name", "address_column" => "street_name"),
        "house_number" => array("label" => "House Number", "address_column" => "house_number"),
        "evidence_number" => array("label" => "Evidence Number", "address_column" => "evidence_number"),
        "orientation_number" => array("label" => "Orientation Number", "address_column" => "orientation_number"),
        "orientation_suffix" => array("label" => "Orientation Suffix", "address_column" => "orientation_suffix"),
        "address_line2" => array("label" => "Address Line 2", "address_column" => "address_line2"),
        "city" => array("label" => "City", "address_column" => "city"),
        "city_part" => array("label" => "City Part", "address_column" => "city_part"),
        "postal_code" => array("label" => "Postal Code", "address_column" => "postal_code"),
        "region" => array("label" => "Region", "address_column" => "region"),
        "country" => array("label" => "Country", "address_column" => "country", "value_type" => "country"),
        "address_is_primary" => array("label" => "Address Is Primary", "address_column" => "is_primary", "value_type" => "boolean"),
        "address_is_active" => array("label" => "Address Is Active", "address_column" => "is_active", "value_type" => "boolean"),
        "address_note" => array("label" => "Address Note", "address_column" => "note"),
        "contacts" => array("label" => "Contacts"),
        "group_names" => array("label" => "Groups", "value_type" => "group"),
        "notes" => array("label" => "Subject Notes"),
        "is_active" => array("label" => "Active", "value_type" => "boolean"),
        "created_at" => array("label" => "Created At", "value_type" => "datetime")
    );
}

function normalizeDemoFullListComplexFilter($aPayload, $aFields, $aOperators) {
    $aFilter = getDefaultFullListComplexFilter();
    if (isset($aPayload["match"]) && (string)$aPayload["match"] == "any") {
        $aFilter["match"] = "any";
    } elseif (isset($aPayload["complex_filter_match"]) && (string)$aPayload["complex_filter_match"] == "any") {
        $aFilter["match"] = "any";
    }
    if (isset($aPayload["conditions"]) && is_array($aPayload["conditions"])) {
        $iCount = 0;
        foreach ($aPayload["conditions"] as $aCondition) {
            if ($iCount >= 25) {
                break;
            }
            $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
            $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
            $sValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
            if (!isset($aFields[$sField])) {
                continue;
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] == "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                continue;
            }
            if (!isFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                continue;
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            } else {
                $sValue = normalizeFullListComplexFilterInputValue($aFields[$sField], $sValue);
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
            $iCount += 1;
        }
        return $aFilter;
    }
    $aInputFields = isset($aPayload["complex_filter_field"]) && is_array($aPayload["complex_filter_field"]) ? $aPayload["complex_filter_field"] : array();
    $aInputOperators = isset($aPayload["complex_filter_operator"]) && is_array($aPayload["complex_filter_operator"]) ? $aPayload["complex_filter_operator"] : array();
    $aInputValues = isset($aPayload["complex_filter_value"]) && is_array($aPayload["complex_filter_value"]) ? $aPayload["complex_filter_value"] : array();
    $iCount = max(count($aInputFields), count($aInputOperators), count($aInputValues));
    for ($iI = 0; $iI < $iCount && $iI < 25; $iI += 1) {
        $sField = isset($aInputFields[$iI]) ? (string)$aInputFields[$iI] : "";
        $sOperator = isset($aInputOperators[$iI]) ? (string)$aInputOperators[$iI] : "";
        $sValue = isset($aInputValues[$iI]) ? (string)$aInputValues[$iI] : "";
        if (!isset($aFields[$sField])) {
            continue;
        }
        if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] == "boolean") {
            $sOperator = "equals";
        } elseif (!isset($aOperators[$sOperator])) {
            continue;
        }
        if (!isFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
            continue;
        }
        if (empty($aOperators[$sOperator]["needs_value"])) {
            $sValue = "";
        } else {
            $sValue = normalizeFullListComplexFilterInputValue($aFields[$sField], $sValue);
        }
        $aFilter["conditions"][] = array(
            "field" => $sField,
            "operator" => $sOperator,
            "value" => $sValue
        );
    }
    return $aFilter;
}

function normalizeDemoFullListComplexFilterDraft($aPayload, $aFields, $aOperators) {
    $aFilter = getDefaultFullListComplexFilterDraft();
    $aFilter["conditions"] = array();
    if (isset($aPayload["match"]) && (string)$aPayload["match"] == "any") {
        $aFilter["match"] = "any";
    } elseif (isset($aPayload["complex_filter_match"]) && (string)$aPayload["complex_filter_match"] == "any") {
        $aFilter["match"] = "any";
    }
    if (isset($aPayload["conditions"]) && is_array($aPayload["conditions"])) {
        $iCount = 0;
        foreach ($aPayload["conditions"] as $aCondition) {
            if ($iCount >= 25) {
                break;
            }
            $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
            $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
            $sValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
            if ($sField == "" || $sOperator == "") {
                $aFilter["conditions"][] = array(
                    "field" => $sField,
                    "operator" => $sOperator,
                    "value" => $sValue
                );
                $iCount += 1;
                continue;
            }
            if (!isset($aFields[$sField])) {
                $sField = "subject_name";
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] == "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                $sOperator = getFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (!isFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                $sOperator = getFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            } else {
                $sValue = normalizeFullListComplexFilterInputValue($aFields[$sField], $sValue);
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
            $iCount += 1;
        }
    } else {
        $aInputFields = isset($aPayload["complex_filter_field"]) && is_array($aPayload["complex_filter_field"]) ? $aPayload["complex_filter_field"] : array();
        $aInputOperators = isset($aPayload["complex_filter_operator"]) && is_array($aPayload["complex_filter_operator"]) ? $aPayload["complex_filter_operator"] : array();
        $aInputValues = isset($aPayload["complex_filter_value"]) && is_array($aPayload["complex_filter_value"]) ? $aPayload["complex_filter_value"] : array();
        $iCount = max(count($aInputFields), count($aInputOperators), count($aInputValues));
        for ($iI = 0; $iI < $iCount && $iI < 25; $iI += 1) {
            $sField = isset($aInputFields[$iI]) ? (string)$aInputFields[$iI] : "";
            $sOperator = isset($aInputOperators[$iI]) ? (string)$aInputOperators[$iI] : "";
            $sValue = isset($aInputValues[$iI]) ? (string)$aInputValues[$iI] : "";
            if ($sField == "" || $sOperator == "") {
                $aFilter["conditions"][] = array(
                    "field" => $sField,
                    "operator" => $sOperator,
                    "value" => $sValue
                );
                continue;
            }
            if (!isset($aFields[$sField])) {
                $sField = "subject_name";
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] == "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                $sOperator = getFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (!isFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                $sOperator = getFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            } else {
                $sValue = normalizeFullListComplexFilterInputValue($aFields[$sField], $sValue);
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
        }
    }
    if (!$aFilter["conditions"]) {
        $aFilter = getDefaultFullListComplexFilterDraft();
    }
    return $aFilter;
}

function demoFullListLower($sValue) {
    return function_exists("mb_strtolower") ? mb_strtolower((string)$sValue, "UTF-8") : strtolower((string)$sValue);
}

function demoFullListJoinContacts($aContacts) {
    $aValues = array();
    foreach ($aContacts as $aContact) {
        $sValue = contactTypeLabel($aContact["contact_type"]) . ": " . (string)$aContact["contact_value"];
        if (isset($aContact["note"]) && $aContact["note"] != "") {
            $sValue .= " (" . (string)$aContact["note"] . ")";
        }
        $aValues[] = $sValue;
    }
    return implode("\n", $aValues);
}

function demoFullListJoinNicknames($aNicknames) {
    $aValues = array();
    foreach ($aNicknames as $aNickname) {
        $sValue = (string)$aNickname["nickname"];
        if (isset($aNickname["context"]) && $aNickname["context"] != "") {
            $sValue .= " [" . (string)$aNickname["context"] . "]";
        }
        if (isset($aNickname["note"]) && $aNickname["note"] != "") {
            $sValue .= " (" . (string)$aNickname["note"] . ")";
        }
        $aValues[] = $sValue;
    }
    return implode("\n", $aValues);
}

function demoFullListJoinAddresses($aAddresses, $aSettings) {
    $aValues = array();
    foreach ($aAddresses as $aAddress) {
        $aValues[] = renderAddressText($aAddress, $aSettings);
    }
    return implode("\n", $aValues);
}

function demoFullListJoinGroups($aGroups) {
    $aValues = array();
    foreach ($aGroups as $aGroup) {
        $aValues[] = (string)$aGroup["name"];
    }
    return implode("\n", $aValues);
}

function demoFullListJoinNotes($aNotes) {
    $aValues = array();
    foreach ($aNotes as $aNote) {
        $aValues[] = (string)$aNote["note_text"];
    }
    return implode("\n", $aValues);
}

function demoFullListComplexFilterValue($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aSettings, $sField) {
    $iSubjectId = (int)$aRow["subject_id"];
    if ($sField == "contacts") {
        return demoFullListJoinContacts(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array());
    }
    if ($sField == "nicknames") {
        return demoFullListJoinNicknames(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array());
    }
    if ($sField == "addresses") {
        return demoFullListJoinAddresses(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $aSettings);
    }
    if ($sField == "group_names") {
        return demoFullListJoinGroups(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array());
    }
    if ($sField == "notes") {
        return demoFullListJoinNotes(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array());
    }
    return isset($aRow[$sField]) ? (string)$aRow[$sField] : "";
}

function demoFullListComplexFilterAddressValues($aAddresses, $sColumn) {
    $aValues = array();
    foreach ($aAddresses as $aAddress) {
        if (array_key_exists($sColumn, $aAddress) && $aAddress[$sColumn] !== null && $aAddress[$sColumn] != "") {
            $aValues[] = (string)$aAddress[$sColumn];
        }
    }
    return $aValues;
}

function normalizeDemoFullListComplexFilterValue($aField, $sValue) {
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "boolean") {
        $sNormalized = strtolower(trim((string)$sValue));
        if ($sNormalized == "0" || $sNormalized == "false" || $sNormalized == "no" || $sNormalized == "off") {
            return "0";
        }
        return "1";
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "birth_number") {
        $sNormalized = normalizeBirthNumber($sValue);
        return $sNormalized === false ? (string)$sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "date") {
        $sNormalized = normalizeInputDate($sValue);
        return $sNormalized === false ? (string)$sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "datetime") {
        $sNormalized = normalizeInputDateTime($sValue);
        return $sNormalized === false ? (string)$sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "country") {
        return countryNameToCode($sValue);
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "subject_type") {
        $sNormalized = strtolower(trim((string)$sValue));
        foreach (getSubjectTypes() as $sSubjectType) {
            if ($sNormalized == $sSubjectType || $sNormalized == strtolower(ucfirst($sSubjectType))) {
                return $sSubjectType;
            }
        }
        return $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "address_type") {
        $sNormalized = strtolower(trim((string)$sValue));
        foreach (getAddressTypes() as $sAddressType) {
            if ($sNormalized == $sAddressType || $sNormalized == strtolower(addressTypeLabel($sAddressType))) {
                return $sAddressType;
            }
        }
        return $sNormalized;
    }
    return (string)$sValue;
}

function demoFullListComplexFilterAddressConditionMatches($aValues, $blHasAddressRows, $aCondition, $aField) {
    $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
    $sFilterValue = normalizeDemoFullListComplexFilterValue($aField, isset($aCondition["value"]) ? (string)$aCondition["value"] : "");
    $sLowerFilterValue = demoFullListLower($sFilterValue);
    $blHasValue = count($aValues) > 0;
    $blExact = $blHasValue;
    $blAnyContains = false;
    $blAnyStarts = false;
    $blAnyEnds = false;
    $blAnyLower = false;
    $blAnyLowerOrEqual = false;
    $blAnyGreater = false;
    $blAnyGreaterOrEqual = false;
    $sLowerValue;
    foreach ($aValues as $sValue) {
        $sValue = normalizeDemoFullListComplexFilterValue($aField, $sValue);
        $sLowerValue = demoFullListLower($sValue);
        if ($sLowerValue != $sLowerFilterValue) {
            $blExact = false;
        }
        if (strpos($sLowerValue, $sLowerFilterValue) !== false) {
            $blAnyContains = true;
        }
        if (substr($sLowerValue, 0, strlen($sLowerFilterValue)) == $sLowerFilterValue) {
            $blAnyStarts = true;
        }
        if ($sLowerFilterValue == "" || substr($sLowerValue, -strlen($sLowerFilterValue)) == $sLowerFilterValue) {
            $blAnyEnds = true;
        }
        if ($sLowerValue < $sLowerFilterValue) {
            $blAnyLower = true;
        }
        if ($sLowerValue <= $sLowerFilterValue) {
            $blAnyLowerOrEqual = true;
        }
        if ($sLowerValue > $sLowerFilterValue) {
            $blAnyGreater = true;
        }
        if ($sLowerValue >= $sLowerFilterValue) {
            $blAnyGreaterOrEqual = true;
        }
    }
    if ($sOperator == "empty") {
        return $blHasAddressRows && !$blHasValue;
    }
    if ($sOperator == "not_empty") {
        return $blHasValue;
    }
    if ($sOperator == "equals") {
        if ($sFilterValue == "") {
            return $blHasAddressRows && !$blHasValue;
        }
        return $blExact;
    }
    if ($sOperator == "not_equals") {
        if ($sFilterValue == "") {
            return $blHasValue;
        }
        return $blHasAddressRows && !$blExact;
    }
    if ($sOperator == "is_lower_than") {
        return $blAnyLower;
    }
    if ($sOperator == "is_lower_than_or_equal") {
        return $blAnyLowerOrEqual;
    }
    if ($sOperator == "is_greater_than") {
        return $blAnyGreater;
    }
    if ($sOperator == "is_greater_than_or_equal") {
        return $blAnyGreaterOrEqual;
    }
    if ($sOperator == "contains") {
        if ($sFilterValue == "") {
            return $blHasAddressRows;
        }
        return $blAnyContains;
    }
    if ($sOperator == "not_contains") {
        if ($sFilterValue == "") {
            return false;
        }
        return $blHasAddressRows && !$blAnyContains;
    }
    if ($sOperator == "starts") {
        if ($sFilterValue == "") {
            return $blHasAddressRows;
        }
        return $blAnyStarts;
    }
    if ($sOperator == "not_starts") {
        if ($sFilterValue == "") {
            return false;
        }
        return $blHasAddressRows && !$blAnyStarts;
    }
    if ($sOperator == "ends") {
        if ($sFilterValue == "") {
            return $blHasAddressRows;
        }
        return $blAnyEnds;
    }
    if ($sOperator == "not_ends") {
        if ($sFilterValue == "") {
            return false;
        }
        return $blHasAddressRows && !$blAnyEnds;
    }
    return false;
}

function demoFullListComplexFilterConditionMatches($sValue, $aCondition, $aField) {
    $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
    $sFilterValue = normalizeDemoFullListComplexFilterValue($aField, isset($aCondition["value"]) ? (string)$aCondition["value"] : "");
    $sValue = normalizeDemoFullListComplexFilterValue($aField, $sValue);
    $sLowerValue = demoFullListLower($sValue);
    $sLowerFilterValue = demoFullListLower($sFilterValue);
    if ($sOperator == "empty") {
        return $sValue == "";
    }
    if ($sOperator == "not_empty") {
        return $sValue != "";
    }
    if ($sOperator == "equals") {
        return $sLowerValue == $sLowerFilterValue;
    }
    if ($sOperator == "not_equals") {
        return $sLowerValue != $sLowerFilterValue;
    }
    if ($sOperator == "is_lower_than") {
        return $sLowerValue < $sLowerFilterValue;
    }
    if ($sOperator == "is_lower_than_or_equal") {
        return $sLowerValue <= $sLowerFilterValue;
    }
    if ($sOperator == "is_greater_than") {
        return $sLowerValue > $sLowerFilterValue;
    }
    if ($sOperator == "is_greater_than_or_equal") {
        return $sLowerValue >= $sLowerFilterValue;
    }
    if ($sOperator == "contains") {
        return strpos($sLowerValue, $sLowerFilterValue) !== false;
    }
    if ($sOperator == "not_contains") {
        return strpos($sLowerValue, $sLowerFilterValue) === false;
    }
    if ($sOperator == "starts") {
        return substr($sLowerValue, 0, strlen($sLowerFilterValue)) == $sLowerFilterValue;
    }
    if ($sOperator == "not_starts") {
        return substr($sLowerValue, 0, strlen($sLowerFilterValue)) != $sLowerFilterValue;
    }
    if ($sOperator == "ends") {
        return $sLowerFilterValue == "" || substr($sLowerValue, -strlen($sLowerFilterValue)) == $sLowerFilterValue;
    }
    if ($sOperator == "not_ends") {
        return $sLowerFilterValue != "" && substr($sLowerValue, -strlen($sLowerFilterValue)) != $sLowerFilterValue;
    }
    return false;
}

function applyDemoFullListComplexFilter($aRows, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aSettings, $aFilter, $aFields) {
    $aFilteredRows = array();
    if (!is_array($aFilter) || empty($aFilter["conditions"]) || !is_array($aFilter["conditions"])) {
        return $aRows;
    }
    foreach ($aRows as $aRow) {
        $blMatched = !isset($aFilter["match"]) || $aFilter["match"] != "any";
        foreach ($aFilter["conditions"] as $aCondition) {
            $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
            if (isset($aFields[$sField]["address_column"])) {
                $aSubjectAddresses = isset($aAddresses[(int)$aRow["subject_id"]]) ? $aAddresses[(int)$aRow["subject_id"]] : array();
                $blConditionMatched = demoFullListComplexFilterAddressConditionMatches(demoFullListComplexFilterAddressValues($aSubjectAddresses, $aFields[$sField]["address_column"]), count($aSubjectAddresses) > 0, $aCondition, $aFields[$sField]);
            } elseif (isset($aFields[$sField]["scope_type"]) && (string)$aFields[$sField]["scope_type"] == "person" && (string)$aRow["subject_type"] != "person") {
                $blConditionMatched = false;
            } else {
                $sValue = demoFullListComplexFilterValue($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aSettings, $sField);
                $blConditionMatched = demoFullListComplexFilterConditionMatches($sValue, $aCondition, isset($aFields[$sField]) ? $aFields[$sField] : array());
            }
            if (isset($aFilter["match"]) && $aFilter["match"] == "any") {
                if ($blConditionMatched) {
                    $blMatched = true;
                    break;
                }
            } elseif (!$blConditionMatched) {
                $blMatched = false;
                break;
            }
        }
        if ($blMatched) {
            $aFilteredRows[] = $aRow;
        }
    }
    return $aFilteredRows;
}

function externalLibraryPermissions($sPath) {
    $iPerms = @fileperms($sPath);
    if (!$iPerms) {
        return "";
    }
    if (($iPerms & 0xC000) == 0xC000) {
        $sInfo = "s";
    } elseif (($iPerms & 0xA000) == 0xA000) {
        $sInfo = "l";
    } elseif (($iPerms & 0x8000) == 0x8000) {
        $sInfo = "-";
    } elseif (($iPerms & 0x6000) == 0x6000) {
        $sInfo = "b";
    } elseif (($iPerms & 0x4000) == 0x4000) {
        $sInfo = "d";
    } elseif (($iPerms & 0x2000) == 0x2000) {
        $sInfo = "c";
    } elseif (($iPerms & 0x1000) == 0x1000) {
        $sInfo = "p";
    } else {
        $sInfo = "u";
    }
    $sInfo .= ($iPerms & 0x0100) ? "r" : "-";
    $sInfo .= ($iPerms & 0x0080) ? "w" : "-";
    $sInfo .= ($iPerms & 0x0040) ? (($iPerms & 0x0800) ? "s" : "x") : (($iPerms & 0x0800) ? "S" : "-");
    $sInfo .= ($iPerms & 0x0020) ? "r" : "-";
    $sInfo .= ($iPerms & 0x0010) ? "w" : "-";
    $sInfo .= ($iPerms & 0x0008) ? (($iPerms & 0x0400) ? "s" : "x") : (($iPerms & 0x0400) ? "S" : "-");
    $sInfo .= ($iPerms & 0x0004) ? "r" : "-";
    $sInfo .= ($iPerms & 0x0002) ? "w" : "-";
    $sInfo .= ($iPerms & 0x0001) ? (($iPerms & 0x0200) ? "t" : "x") : (($iPerms & 0x0200) ? "T" : "-");
    return $sInfo;
}

function externalLibraryOwner($sPath) {
    $iOwner = @fileowner($sPath);
    if ($iOwner === false) {
        return "";
    }
    if (function_exists("posix_getpwuid")) {
        $aOwner = @posix_getpwuid($iOwner);
        if (is_array($aOwner) && isset($aOwner["name"])) {
            return (string)$aOwner["name"];
        }
    }
    return (string)$iOwner;
}

function renderGroupAdminRows($oPdo, $blCanEdit) {
    $sHtml = "";
    foreach (fetchGroupAdminRows($oPdo) as $aGroup) {
        $sHtml .= renderGroupAdminRow($aGroup, $blCanEdit);
    }
    return $sHtml;
}

function getFullListComplexFilterFields($aContactTypes) {
    $aFields = array(
        "subject_type" => array("label" => "Subject: Type", "sql" => "`subject_type`", "value_type" => "subject_type"),
        "subject_name" => array("label" => "Subject: Name", "sql" => "`subject_name`"),
        "title_before" => array("label" => "Person: Title Before", "sql" => "`title_before`", "scope_sql" => "`subject_type` = 'person'"),
        "first_name" => array("label" => "Person: First Name", "sql" => "`first_name`", "scope_sql" => "`subject_type` = 'person'"),
        "middle_name" => array("label" => "Person: Middle Name", "sql" => "`middle_name`", "scope_sql" => "`subject_type` = 'person'"),
        "last_name" => array("label" => "Person: Last Name", "sql" => "`last_name`", "scope_sql" => "`subject_type` = 'person'"),
        "title_after" => array("label" => "Person: Title After", "sql" => "`title_after`", "scope_sql" => "`subject_type` = 'person'"),
        "birth_name" => array("label" => "Person: Birth Name", "sql" => "`birth_name`", "scope_sql" => "`subject_type` = 'person'"),
        "birth_number" => array("label" => "Person: Birth Number", "sql" => "`birth_number`", "value_type" => "birth_number", "scope_sql" => "`subject_type` = 'person'"),
        "birth_date" => array("label" => "Person: Birth Date", "sql" => "`birth_date`", "value_type" => "date", "scope_sql" => "`subject_type` = 'person'"),
        "death_date" => array("label" => "Person: Death Date", "sql" => "`death_date`", "value_type" => "date", "scope_sql" => "`subject_type` = 'person'"),
        "birthday_served_at" => array("label" => "Person: Birthday Served At", "sql" => "`birthday_served_at`", "value_type" => "datetime", "scope_sql" => "`subject_type` = 'person'"),
        "inter_served_at" => array("label" => "Person: Interaction Served At", "sql" => "`inter_served_at`", "value_type" => "datetime", "scope_sql" => "`subject_type` = 'person'"),
        "nicknames" => array("label" => "Subject: Nicknames", "sql" => "`nicknames`"),
        "addresses" => array("label" => "Subject: Addresses", "sql" => "`addresses`"),
        "address_type" => array("label" => "Address: Type", "address_column" => "address_type", "value_type" => "address_type"),
        "organization_name" => array("label" => "Address: Organization Name", "address_column" => "organization_name"),
        "department_name" => array("label" => "Address: Department Name", "address_column" => "department_name"),
        "care_of" => array("label" => "Address: Care Of", "address_column" => "care_of"),
        "street_name" => array("label" => "Address: Street Name", "address_column" => "street_name"),
        "house_number" => array("label" => "Address: House Number", "address_column" => "house_number"),
        "evidence_number" => array("label" => "Address: Evidence Number", "address_column" => "evidence_number"),
        "orientation_number" => array("label" => "Address: Orientation Number", "address_column" => "orientation_number"),
        "orientation_suffix" => array("label" => "Address: Orientation Suffix", "address_column" => "orientation_suffix"),
        "address_line2" => array("label" => "Address: Address Line 2", "address_column" => "address_line2"),
        "city" => array("label" => "Address: City", "address_column" => "city"),
        "city_part" => array("label" => "Address: City Part", "address_column" => "city_part"),
        "postal_code" => array("label" => "Address: Postal Code", "address_column" => "postal_code"),
        "region" => array("label" => "Address: Region", "address_column" => "region"),
        "country" => array("label" => "Address: Country", "address_column" => "country", "value_type" => "country"),
        "address_is_primary" => array("label" => "Address: Primary", "address_column" => "is_primary", "value_type" => "boolean"),
        "address_is_active" => array("label" => "Address: Active", "address_column" => "is_active", "value_type" => "boolean"),
        "address_note" => array("label" => "Address: Note", "address_column" => "note"),
        "contacts" => array("label" => "Subject: Contacts", "sql" => "`contacts`")
    );
    foreach ($aContactTypes as $aContactType) {
        $iContactTypeId = isset($aContactType["id"]) ? (int)$aContactType["id"] : 0;
        $sContactType = trim((string)(isset($aContactType["contact_type"]) ? $aContactType["contact_type"] : ""));
        $sContactTypeName = trim((string)(isset($aContactType["name"]) ? $aContactType["name"] : ""));
        if ($sContactTypeName == "") {
            $sContactTypeName = trim((string)(isset($aContactType["contact_type"]) ? $aContactType["contact_type"] : ""));
        }
        if ($iContactTypeId > 0 && $sContactTypeName != "") {
            $aFields["contact_type_" . $iContactTypeId] = array("label" => "Contact: " . $sContactTypeName, "contact_type_id" => $iContactTypeId, "contact_type" => $sContactType);
        }
    }
    $aFields += array(
        "group_names" => array("label" => "Subject: Groups", "sql" => "`group_names`", "value_type" => "group"),
        "notes" => array("label" => "Subject: Notes", "sql" => "`notes`"),
        "is_active" => array("label" => "Subject: Active", "sql" => "`is_active`", "value_type" => "boolean"),
        "created_at" => array("label" => "Subject: Created At", "sql" => "`created_at`", "value_type" => "datetime")
    );
    return $aFields;
}

function getFullListComplexFilterOperators() {
    return array(
        "equals" => array("label" => "is equal to", "needs_value" => 1),
        "not_equals" => array("label" => "is not equal to", "needs_value" => 1),
        "is_lower_than" => array("label" => "is lower than", "needs_value" => 1),
        "is_lower_than_or_equal" => array("label" => "is lower than or equal to", "needs_value" => 1),
        "is_greater_than" => array("label" => "is greater than", "needs_value" => 1),
        "is_greater_than_or_equal" => array("label" => "is greater than or equal to", "needs_value" => 1),
        "contains" => array("label" => "contains", "needs_value" => 1),
        "not_contains" => array("label" => "does not contain", "needs_value" => 1),
        "starts" => array("label" => "starts with", "needs_value" => 1),
        "not_starts" => array("label" => "does not start with", "needs_value" => 1),
        "ends" => array("label" => "ends with", "needs_value" => 1),
        "not_ends" => array("label" => "does not end with", "needs_value" => 1),
        "empty" => array("label" => "is empty", "needs_value" => 0),
        "not_empty" => array("label" => "is not empty", "needs_value" => 0)
    );
}

function getDefaultFullListComplexFilter() {
    return array(
        "match" => "all",
        "conditions" => array()
    );
}

function getDefaultFullListComplexFilterDraft() {
    return array(
        "match" => "all",
        "conditions" => array(
            array(
                "field" => "subject_name",
                "operator" => "contains",
                "value" => ""
            )
        )
    );
}

function isFullListComplexFilterOperatorAllowed($aField, $sOperator) {
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "group") {
        return in_array($sOperator, array("equals", "not_equals", "contains", "not_contains", "empty", "not_empty"), true);
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "country") {
        return in_array($sOperator, array("equals", "not_equals", "contains", "not_contains", "empty", "not_empty"), true);
    }
    if (isset($aField["value_type"]) && ((string)$aField["value_type"] == "address_type" || (string)$aField["value_type"] == "subject_type")) {
        return in_array($sOperator, array("equals", "not_equals", "contains", "not_contains", "empty", "not_empty"), true);
    }
    return true;
}

function getFullListComplexFilterDefaultOperator($aField) {
    if (isset($aField["value_type"]) && ((string)$aField["value_type"] == "boolean" || (string)$aField["value_type"] == "country")) {
        return "equals";
    }
    return "contains";
}

function normalizeFullListComplexFilterInputValue($aField, $sValue) {
    $sNormalized = false;
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "date") {
        $sNormalized = normalizeInputDate($sValue);
        return $sNormalized !== false ? $sNormalized : (string)$sValue;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "datetime") {
        $sNormalized = normalizeInputDateTime($sValue);
        return $sNormalized !== false ? $sNormalized : (string)$sValue;
    }
    return (string)$sValue;
}

function normalizeFullListComplexFilter($aPayload, $aFields, $aOperators) {
    $aFilter = getDefaultFullListComplexFilter();
    if (isset($aPayload["match"]) && (string)$aPayload["match"] == "any") {
        $aFilter["match"] = "any";
    } elseif (isset($aPayload["complex_filter_match"]) && (string)$aPayload["complex_filter_match"] == "any") {
        $aFilter["match"] = "any";
    }
    if (isset($aPayload["conditions"]) && is_array($aPayload["conditions"])) {
        $iCount = 0;
        foreach ($aPayload["conditions"] as $aCondition) {
            if ($iCount >= 25) {
                break;
            }
            $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
            $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
            $sValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
            if (!isset($aFields[$sField])) {
                continue;
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] == "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                continue;
            }
            if (!isFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                continue;
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            } else {
                $sValue = normalizeFullListComplexFilterInputValue($aFields[$sField], $sValue);
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
            $iCount += 1;
        }
        return $aFilter;
    }
    $aInputFields = isset($aPayload["complex_filter_field"]) && is_array($aPayload["complex_filter_field"]) ? $aPayload["complex_filter_field"] : array();
    $aInputOperators = isset($aPayload["complex_filter_operator"]) && is_array($aPayload["complex_filter_operator"]) ? $aPayload["complex_filter_operator"] : array();
    $aInputValues = isset($aPayload["complex_filter_value"]) && is_array($aPayload["complex_filter_value"]) ? $aPayload["complex_filter_value"] : array();
    $iCount = max(count($aInputFields), count($aInputOperators), count($aInputValues));
    for ($iI = 0; $iI < $iCount && $iI < 25; $iI += 1) {
        $sField = isset($aInputFields[$iI]) ? (string)$aInputFields[$iI] : "";
        $sOperator = isset($aInputOperators[$iI]) ? (string)$aInputOperators[$iI] : "";
        $sValue = isset($aInputValues[$iI]) ? (string)$aInputValues[$iI] : "";
        if (!isset($aFields[$sField])) {
            continue;
        }
        if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] == "boolean") {
            $sOperator = "equals";
        } elseif (!isset($aOperators[$sOperator])) {
            continue;
        }
        if (!isFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
            continue;
        }
        if (empty($aOperators[$sOperator]["needs_value"])) {
            $sValue = "";
        } else {
            $sValue = normalizeFullListComplexFilterInputValue($aFields[$sField], $sValue);
        }
        $aFilter["conditions"][] = array(
            "field" => $sField,
            "operator" => $sOperator,
            "value" => $sValue
        );
    }
    return $aFilter;
}

function normalizeFullListComplexFilterDraft($aPayload, $aFields, $aOperators) {
    $aFilter = getDefaultFullListComplexFilterDraft();
    $aFilter["conditions"] = array();
    if (isset($aPayload["match"]) && (string)$aPayload["match"] == "any") {
        $aFilter["match"] = "any";
    } elseif (isset($aPayload["complex_filter_match"]) && (string)$aPayload["complex_filter_match"] == "any") {
        $aFilter["match"] = "any";
    }
    if (isset($aPayload["conditions"]) && is_array($aPayload["conditions"])) {
        $iCount = 0;
        foreach ($aPayload["conditions"] as $aCondition) {
            if ($iCount >= 25) {
                break;
            }
            $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
            $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
            $sValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
            if ($sField == "" || $sOperator == "") {
                $aFilter["conditions"][] = array(
                    "field" => $sField,
                    "operator" => $sOperator,
                    "value" => $sValue
                );
                $iCount += 1;
                continue;
            }
            if (!isset($aFields[$sField])) {
                $sField = "subject_name";
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] == "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                $sOperator = getFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (!isFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                $sOperator = getFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            } else {
                $sValue = normalizeFullListComplexFilterInputValue($aFields[$sField], $sValue);
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
            $iCount += 1;
        }
    } else {
        $aInputFields = isset($aPayload["complex_filter_field"]) && is_array($aPayload["complex_filter_field"]) ? $aPayload["complex_filter_field"] : array();
        $aInputOperators = isset($aPayload["complex_filter_operator"]) && is_array($aPayload["complex_filter_operator"]) ? $aPayload["complex_filter_operator"] : array();
        $aInputValues = isset($aPayload["complex_filter_value"]) && is_array($aPayload["complex_filter_value"]) ? $aPayload["complex_filter_value"] : array();
        $iCount = max(count($aInputFields), count($aInputOperators), count($aInputValues));
        for ($iI = 0; $iI < $iCount && $iI < 25; $iI += 1) {
            $sField = isset($aInputFields[$iI]) ? (string)$aInputFields[$iI] : "";
            $sOperator = isset($aInputOperators[$iI]) ? (string)$aInputOperators[$iI] : "";
            $sValue = isset($aInputValues[$iI]) ? (string)$aInputValues[$iI] : "";
            if ($sField == "" || $sOperator == "") {
                $aFilter["conditions"][] = array(
                    "field" => $sField,
                    "operator" => $sOperator,
                    "value" => $sValue
                );
                continue;
            }
            if (!isset($aFields[$sField])) {
                $sField = "subject_name";
            }
            if (isset($aFields[$sField]["value_type"]) && (string)$aFields[$sField]["value_type"] == "boolean") {
                $sOperator = "equals";
            } elseif (!isset($aOperators[$sOperator])) {
                $sOperator = getFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (!isFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                $sOperator = getFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            } else {
                $sValue = normalizeFullListComplexFilterInputValue($aFields[$sField], $sValue);
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
        }
    }
    if (!$aFilter["conditions"]) {
        $aFilter = getDefaultFullListComplexFilterDraft();
    }
    return $aFilter;
}

function escapeFullListComplexFilterLike($sValue) {
    return str_replace(array("!", "%", "_"), array("!!", "!%", "!_"), $sValue);
}

function normalizeFullListComplexFilterSqlValue($aField, $sValue) {
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "boolean") {
        $sNormalized = strtolower(trim((string)$sValue));
        if ($sNormalized == "0" || $sNormalized == "false" || $sNormalized == "no" || $sNormalized == "off") {
            return "0";
        }
        return "1";
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "birth_number") {
        $sNormalized = normalizeBirthNumber($sValue);
        return $sNormalized === false ? (string)$sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "country") {
        return countryNameToCode($sValue);
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "subject_type") {
        $sNormalized = strtolower(trim((string)$sValue));
        foreach (getSubjectTypes() as $sSubjectType) {
            if ($sNormalized == $sSubjectType) {
                return $sSubjectType;
            }
        }
        return $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "address_type") {
        $sNormalized = strtolower(trim((string)$sValue));
        foreach (getAddressTypes() as $sAddressType) {
            if ($sNormalized == $sAddressType || $sNormalized == strtolower(addressTypeLabel($sAddressType))) {
                return $sAddressType;
            }
        }
        return $sNormalized;
    }
    if (isset($aField["contact_type"])) {
        return contactCanonicalValue($aField["contact_type"], $sValue);
    }
    return (string)$sValue;
}

function buildFullListComplexAddressFilterSql($sColumn, $sOperator, $sParam, $sValue) {
    $sColumnSql = "COALESCE(CAST(a_cf.`" . $sColumn . "` AS CHAR), '')";
    $sColumnLowerSql = "LOWER(" . $sColumnSql . ")";
    $sNonEmptySql = $sColumnSql . " <> ''";
    $sHasRowSql = "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id)";
    $sHasValueSql = "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . ")";
    $sExactSql = $sHasValueSql . " AND NOT EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " <> LOWER(:" . $sParam . "))";
    if ($sOperator == "empty") {
        return $sHasRowSql . " AND NOT " . $sHasValueSql;
    }
    if ($sOperator == "not_empty") {
        return $sHasValueSql;
    }
    if ($sOperator == "equals") {
        if ($sValue == "") {
            return $sHasRowSql . " AND NOT " . $sHasValueSql;
        }
        return $sExactSql;
    }
    if ($sOperator == "not_equals") {
        if ($sValue == "") {
            return $sHasValueSql;
        }
        return $sHasRowSql . " AND NOT (" . $sExactSql . ")";
    }
    if ($sOperator == "is_lower_than") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " < LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "is_lower_than_or_equal") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " <= LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "is_greater_than") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " > LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "is_greater_than_or_equal") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " >= LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "contains") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "not_contains") {
        return $sHasRowSql . " AND NOT EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "starts") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "not_starts") {
        return $sHasRowSql . " AND NOT EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "ends") {
        return "EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "not_ends") {
        return $sHasRowSql . " AND NOT EXISTS (SELECT 1 FROM ex_subject_addresses AS a_cf WHERE a_cf.subject_id = s.id AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    return "";
}

function buildFullListComplexContactTypeFilterSql($iContactTypeId, $sOperator, $sParam, $sValue) {
    $iContactTypeId = (int)$iContactTypeId;
    $sColumnSql = "COALESCE(CAST(c_cf.contact_value AS CHAR), '')";
    $sColumnLowerSql = "LOWER(" . $sColumnSql . ")";
    $sNonEmptySql = $sColumnSql . " <> ''";
    $sTypeSql = "sc_cf.subject_id = s.id AND c_cf.contact_type_id = " . $iContactTypeId;
    $sHasValueSql = "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sNonEmptySql . ")";
    if ($iContactTypeId < 1) {
        return "";
    }
    if ($sOperator == "empty") {
        return "NOT " . $sHasValueSql;
    }
    if ($sOperator == "not_empty") {
        return $sHasValueSql;
    }
    if ($sOperator == "equals") {
        if ($sValue == "") {
            return "NOT " . $sHasValueSql;
        }
        return "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sColumnLowerSql . " = LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "not_equals") {
        if ($sValue == "") {
            return $sHasValueSql;
        }
        return "NOT EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sColumnLowerSql . " = LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "is_lower_than") {
        return "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " < LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "is_lower_than_or_equal") {
        return "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " <= LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "is_greater_than") {
        return "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " > LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "is_greater_than_or_equal") {
        return "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sNonEmptySql . " AND " . $sColumnLowerSql . " >= LOWER(:" . $sParam . "))";
    }
    if ($sOperator == "contains") {
        return "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "not_contains") {
        return "NOT EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "starts") {
        return "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "not_starts") {
        return "NOT EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "ends") {
        return "EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    if ($sOperator == "not_ends") {
        return "NOT EXISTS (SELECT 1 FROM ex_subject_contacts AS sc_cf INNER JOIN ex_contacts AS c_cf ON c_cf.id = sc_cf.contact_id WHERE " . $sTypeSql . " AND " . $sColumnLowerSql . " LIKE LOWER(:" . $sParam . ") ESCAPE '!')";
    }
    return "";
}

function applyFullListComplexFilterScopeSql($sSql, $aField) {
    if ($sSql != "" && isset($aField["scope_sql"]) && $aField["scope_sql"] != "") {
        return "(" . (string)$aField["scope_sql"] . " AND " . $sSql . ")";
    }
    return $sSql;
}

function buildFullListComplexFilterSql($aFilter, $aFields, $aOperators) {
    $aSql = array();
    $aParams = array();
    $iIndex = 0;
    if (!is_array($aFilter) || empty($aFilter["conditions"]) || !is_array($aFilter["conditions"])) {
        return array("sql" => "", "params" => array());
    }
    foreach ($aFilter["conditions"] as $aCondition) {
        $sField = isset($aCondition["field"]) ? (string)$aCondition["field"] : "";
        $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
        $sValue = isset($aCondition["value"]) ? (string)$aCondition["value"] : "";
        if (!isset($aFields[$sField]) || !isset($aOperators[$sOperator])) {
            continue;
        }
        $sValue = normalizeFullListComplexFilterSqlValue($aFields[$sField], $sValue);
        if (isset($aFields[$sField]["address_column"])) {
            $sParam = "complex_filter_" . $iIndex;
            $sAddressSql = buildFullListComplexAddressFilterSql($aFields[$sField]["address_column"], $sOperator, $sParam, $sValue);
            if ($sAddressSql == "") {
                continue;
            }
            $aSql[] = $sAddressSql;
            if ($sOperator != "empty" && $sOperator != "not_empty") {
                if ($sOperator == "contains" || $sOperator == "not_contains") {
                    $aParams[$sParam] = "%" . escapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator == "starts" || $sOperator == "not_starts") {
                    $aParams[$sParam] = escapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator == "ends" || $sOperator == "not_ends") {
                    $aParams[$sParam] = "%" . escapeFullListComplexFilterLike($sValue);
                } else {
                    $aParams[$sParam] = $sValue;
                }
                $iIndex += 1;
            }
            continue;
        }
        if (isset($aFields[$sField]["contact_type_id"])) {
            $sParam = "complex_filter_" . $iIndex;
            $sContactTypeSql = buildFullListComplexContactTypeFilterSql($aFields[$sField]["contact_type_id"], $sOperator, $sParam, $sValue);
            if ($sContactTypeSql == "") {
                continue;
            }
            $aSql[] = $sContactTypeSql;
            if ($sOperator != "empty" && $sOperator != "not_empty") {
                if ($sOperator == "contains" || $sOperator == "not_contains") {
                    $aParams[$sParam] = "%" . escapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator == "starts" || $sOperator == "not_starts") {
                    $aParams[$sParam] = escapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator == "ends" || $sOperator == "not_ends") {
                    $aParams[$sParam] = "%" . escapeFullListComplexFilterLike($sValue);
                } else {
                    $aParams[$sParam] = $sValue;
                }
                $iIndex += 1;
            }
            continue;
        }
        if (isset($aFields[$sField]["value_type"]) && $aFields[$sField]["value_type"] == "datetime") {
            $sSqlValueBase = preg_match("/:[0-9]{2}:[0-9]{2}$/", $sValue) ? "DATE_FORMAT(" . $aFields[$sField]["sql"] . ", '%Y-%m-%d %H:%i:%s')" : "DATE_FORMAT(" . $aFields[$sField]["sql"] . ", '%Y-%m-%d %H:%i')";
        } else {
            $sSqlValueBase = "CAST(" . $aFields[$sField]["sql"] . " AS CHAR)";
        }
        $sSqlValue = "LOWER(COALESCE(" . $sSqlValueBase . ", ''))";
        $sSqlTrimmedValue = "COALESCE(CAST(" . $aFields[$sField]["sql"] . " AS CHAR), '')";
        $sConditionSql = "";
        if ($sOperator == "empty") {
            $sConditionSql = $sSqlTrimmedValue . " = ''";
        } elseif ($sOperator == "not_empty") {
            $sConditionSql = $sSqlTrimmedValue . " <> ''";
        } else {
            $sParam = "complex_filter_" . $iIndex;
            if ($sOperator == "equals") {
                $sConditionSql = $sSqlValue . " = LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator == "not_equals") {
                $sConditionSql = $sSqlValue . " <> LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator == "is_lower_than") {
                $sConditionSql = $sSqlValue . " < LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator == "is_lower_than_or_equal") {
                $sConditionSql = $sSqlValue . " <= LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator == "is_greater_than") {
                $sConditionSql = $sSqlValue . " > LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator == "is_greater_than_or_equal") {
                $sConditionSql = $sSqlValue . " >= LOWER(:" . $sParam . ")";
                $aParams[$sParam] = $sValue;
            } elseif ($sOperator == "contains") {
                $sConditionSql = $sSqlValue . " LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . escapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator == "not_contains") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . escapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator == "starts") {
                $sConditionSql = $sSqlValue . " LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = escapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator == "not_starts") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = escapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator == "ends") {
                $sConditionSql = $sSqlValue . " LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . escapeFullListComplexFilterLike($sValue);
            } elseif ($sOperator == "not_ends") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . escapeFullListComplexFilterLike($sValue);
            }
            $iIndex += 1;
        }
        if ($sConditionSql != "") {
            $aSql[] = applyFullListComplexFilterScopeSql($sConditionSql, $aFields[$sField]);
        }
    }
    if (!$aSql) {
        return array("sql" => "", "params" => array());
    }
    return array(
        "sql" => "(" . implode(!empty($aFilter["match"]) && $aFilter["match"] == "any" ? ") OR (" : ") AND (", $aSql) . ")",
        "params" => $aParams
    );
}

function renderFullListComplexFilterFieldOptions($aFields, $sSelected) {
    $sHtml = "<option value=\"\" data-value-type=\"text\"" . ($sSelected == "" ? " selected" : "") . "></option>";
    foreach ($aFields as $sField => $aField) {
        $sValueType = isset($aField["value_type"]) ? (string)$aField["value_type"] : "text";
        $sHtml .= "<option value=\"" . html($sField) . "\" data-value-type=\"" . html($sValueType) . "\"" . ($sSelected == $sField ? " selected" : "") . ">" . html($aField["label"]) . "</option>";
    }
    return $sHtml;
}

function renderFullListComplexFilterOperatorOptions($aOperators, $sSelected, $aField = null) {
    $sHtml = "<option value=\"\" data-needs-value=\"1\"" . ($sSelected == "" ? " selected" : "") . "></option>";
    foreach ($aOperators as $sOperator => $aOperator) {
        $sDisabled = is_array($aField) && !isFullListComplexFilterOperatorAllowed($aField, $sOperator) ? " hidden disabled" : "";
        $sHtml .= "<option value=\"" . html($sOperator) . "\" data-needs-value=\"" . (!empty($aOperator["needs_value"]) ? "1" : "0") . "\"" . ($sSelected == $sOperator ? " selected" : "") . $sDisabled . ">" . html($aOperator["label"]) . "</option>";
    }
    return $sHtml;
}

function getFullListComplexFilterPostPayload() {
    $aPayload = $_POST;
    if (isset($_POST["complex_filter_value_b64"]) && is_array($_POST["complex_filter_value_b64"])) {
        $aPayload["complex_filter_value"] = getPostedValues("complex_filter_value");
    }
    return $aPayload;
}

function interGetBirthdayInfo($sCommunicationServedAt) {
    $sCommunicationServedAt = trim((string)$sCommunicationServedAt);
    $oToday = new DateTimeImmutable("today");
    if ($sCommunicationServedAt == "" || strpos($sCommunicationServedAt, "0000-00-00") === 0) {
        return array(
            "days_to_birthday" => 0,
            "birthday_date" => $oToday->format("Y-m-d")
        );
    }
    try {
        $oCommunicationDue = (new DateTimeImmutable($sCommunicationServedAt))->modify("+2 months")->setTime(0, 0, 0);
    } catch (Exception $oException) {
        error_log((string)$oException);
        return null;
    }
    $iDaysToCommunication = (int)$oToday->diff($oCommunicationDue)->format("%r%a");
    if ($iDaysToCommunication < 0 || $iDaysToCommunication > 20) {
        return null;
    }
    return array(
        "days_to_birthday" => $iDaysToCommunication,
        "birthday_date" => $oCommunicationDue->format("Y-m-d")
    );
}

function interFetchBirthdayServedRows($oPdo) {
    return fetchPersonServedRows($oPdo, "inter_served_at");
}

function interRenderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aBirthdaySettings) {
    global $sCommunicationServedEmoji;

    return renderServedSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aBirthdaySettings, "js-communication-served", "Mark communication served", $sCommunicationServedEmoji, array(
        "nickname_show_add_action" => true,
        "address_show_add_action" => true,
        "contact_show_add_action" => true,
        "group_show_add_action" => true,
        "note_show_add_action" => true
    ));
}

function interGetSubjectServedInfo($oPdo, $iSubjectId, $aRow) {
    $aBirthdayServedRows = interFetchBirthdayServedRows($oPdo);
    $sCommunicationServedAt = isset($aBirthdayServedRows[$iSubjectId]["inter_served_at"]) ? $aBirthdayServedRows[$iSubjectId]["inter_served_at"] : "";
    return interGetBirthdayInfo($sCommunicationServedAt);
}

function interGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blShowActions) {
    return getUpdatedServedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blShowActions, "interGetSubjectServedInfo", "interRenderSubjectRow");
}

function diffEnsureDumpTable(&$aDump, $sTableName) {
    if (!isset($aDump["tables"][$sTableName])) {
        $aDump["tables"][$sTableName] = array(
            "create" => "",
            "primary_keys" => array(),
            "columns" => array(),
            "rows" => array()
        );
        $aDump["table_order"][] = $sTableName;
    }
}

function diffDecodeSqlIdentifier($sIdentifier) {
    return str_replace("``", "`", $sIdentifier);
}

function diffParseSqlIdentifierList($sSql) {
    $aIdentifiers = array();
    if (preg_match_all("/`((?:``|[^`])*)`/", $sSql, $aMatches)) {
        foreach ($aMatches[1] as $sIdentifier) {
            $aIdentifiers[] = diffDecodeSqlIdentifier($sIdentifier);
        }
    }
    return $aIdentifiers;
}

function diffNormalizeCreateSql($sSql) {
    $sSql = trim((string)$sSql);
    $sSql = preg_replace("/\s+AUTO_INCREMENT=\d+\b/i", "", $sSql);
    return preg_replace("/\r\n|\r|\n/", "\n", $sSql);
}

function diffGetPrimaryKeyColumns($sCreateSql) {
    return preg_match("/PRIMARY\s+KEY\s+\(([^)]*)\)/is", $sCreateSql, $aMatches) ? diffParseSqlIdentifierList($aMatches[1]) : array();
}

function diffSplitSqlStatements($sSql) {
    $aStatements = array();
    $sStatement = "";
    $sMode = "";
    $iLength = strlen($sSql);
    for ($i = 0; $i < $iLength; $i++) {
        $sChar = $sSql[$i];
        $sStatement .= $sChar;
        if ($sMode == "string") {
            if ($sChar == "\\") {
                if ($i + 1 < $iLength) {
                    $i++;
                    $sStatement .= $sSql[$i];
                }
                continue;
            }
            if ($sChar == "'") {
                if ($i + 1 < $iLength && $sSql[$i + 1] == "'") {
                    $i++;
                    $sStatement .= $sSql[$i];
                    continue;
                }
                $sMode = "";
            }
            continue;
        }
        if ($sMode == "identifier") {
            if ($sChar == "`") {
                if ($i + 1 < $iLength && $sSql[$i + 1] == "`") {
                    $i++;
                    $sStatement .= $sSql[$i];
                    continue;
                }
                $sMode = "";
            }
            continue;
        }
        if ($sChar == "'") {
            $sMode = "string";
        } elseif ($sChar == "`") {
            $sMode = "identifier";
        } elseif ($sChar == ";") {
            $sStatement = trim(substr($sStatement, 0, -1));
            if ($sStatement != "") {
                $aStatements[] = $sStatement;
            }
            $sStatement = "";
        }
    }
    $sStatement = trim($sStatement);
    if ($sStatement != "") {
        $aStatements[] = $sStatement;
    }
    return $aStatements;
}

function diffDecodeSqlString($sValue) {
    $sResult = "";
    $iLength = strlen($sValue);
    for ($i = 0; $i < $iLength; $i++) {
        $sChar = $sValue[$i];
        if ($sChar == "\\") {
            if ($i + 1 >= $iLength) {
                $sResult .= $sChar;
                continue;
            }
            $i++;
            $sNext = $sValue[$i];
            if ($sNext == "n") {
                $sResult .= "\n";
            } elseif ($sNext == "r") {
                $sResult .= "\r";
            } elseif ($sNext == "t") {
                $sResult .= "\t";
            } elseif ($sNext == "0") {
                $sResult .= chr(0);
            } elseif ($sNext == "b") {
                $sResult .= chr(8);
            } elseif ($sNext == "Z") {
                $sResult .= chr(26);
            } else {
                $sResult .= $sNext;
            }
        } elseif ($sChar == "'" && $i + 1 < $iLength && $sValue[$i + 1] == "'") {
            $sResult .= "'";
            $i++;
        } else {
            $sResult .= $sChar;
        }
    }
    return $sResult;
}

function diffDecodeSqlValue($sToken) {
    $sToken = trim((string)$sToken);
    if (strcasecmp($sToken, "NULL") === 0) {
        return null;
    }
    if (strlen($sToken) >= 2 && $sToken[0] == "'" && substr($sToken, -1) == "'") {
        return diffDecodeSqlString(substr($sToken, 1, -1));
    }
    return $sToken;
}

function diffParseSqlValues($sSql) {
    $aValues = array();
    $sToken = "";
    $sMode = "";
    $iLength = strlen($sSql);
    for ($i = 0; $i < $iLength; $i++) {
        $sChar = $sSql[$i];
        if ($sMode == "string") {
            $sToken .= $sChar;
            if ($sChar == "\\") {
                if ($i + 1 < $iLength) {
                    $i++;
                    $sToken .= $sSql[$i];
                }
                continue;
            }
            if ($sChar == "'") {
                if ($i + 1 < $iLength && $sSql[$i + 1] == "'") {
                    $i++;
                    $sToken .= $sSql[$i];
                    continue;
                }
                $sMode = "";
            }
            continue;
        }
        if ($sChar == "'") {
            $sMode = "string";
            $sToken .= $sChar;
        } elseif ($sChar == ",") {
            $aValues[] = diffDecodeSqlValue($sToken);
            $sToken = "";
        } else {
            $sToken .= $sChar;
        }
    }
    if (trim($sToken) != "" || $sSql != "") {
        $aValues[] = diffDecodeSqlValue($sToken);
    }
    return $aValues;
}

function diffParseDatabaseSql($sSql) {
    $aDump = array(
        "tables" => array(),
        "table_order" => array()
    );
    foreach (diffSplitSqlStatements($sSql) as $sStatement) {
        if (preg_match("/^CREATE\s+TABLE\s+`((?:``|[^`])+)`/is", $sStatement, $aMatches)) {
            $sTableName = diffDecodeSqlIdentifier($aMatches[1]);
            if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
                continue;
            }
            diffEnsureDumpTable($aDump, $sTableName);
            $sCreateSql = diffNormalizeCreateSql($sStatement);
            $aDump["tables"][$sTableName]["create"] = $sCreateSql;
            $aDump["tables"][$sTableName]["primary_keys"] = diffGetPrimaryKeyColumns($sCreateSql);
        } elseif (preg_match("/^INSERT\s+INTO\s+`((?:``|[^`])+)`\s*\((.*)\)\s+VALUES\s*\((.*)\)$/is", $sStatement, $aMatches)) {
            $sTableName = diffDecodeSqlIdentifier($aMatches[1]);
            if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
                continue;
            }
            diffEnsureDumpTable($aDump, $sTableName);
            $aColumns = diffParseSqlIdentifierList($aMatches[2]);
            $aValues = diffParseSqlValues($aMatches[3]);
            if (count($aColumns) != count($aValues)) {
                throw new Exception("Invalid INSERT statement in table " . $sTableName . ".");
            }
            if (!$aDump["tables"][$sTableName]["columns"]) {
                $aDump["tables"][$sTableName]["columns"] = $aColumns;
            }
            $aRow = array();
            foreach ($aColumns as $iIndex => $sColumnName) {
                $aRow[$sColumnName] = $aValues[$iIndex];
            }
            $aDump["tables"][$sTableName]["rows"][] = $aRow;
        }
    }
    if (!$aDump["tables"]) {
        throw new Exception("The uploaded file does not look like a database backup generated by db.php.");
    }
    return $aDump;
}

function diffFetchDatabaseTables($oPdo) {
    $aTables = array();
    $oStatement = $oPdo->query("SHOW TABLES");
    $aTableNames = $oStatement->fetchAll(PDO::FETCH_COLUMN);
    foreach ($aTableNames as $sTableName) {
        if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
            continue;
        }
        $oStatement = $oPdo->query("SHOW CREATE TABLE `" . $sTableName . "`");
        $aTable = $oStatement->fetch(PDO::FETCH_NUM);
        if (isset($aTable[0], $aTable[1])) {
            $aTable[1] = preg_replace("/\s+AUTO_INCREMENT=\d+\b/i", "", $aTable[1]);
            $aTables[] = $aTable;
        }
    }
    $aTableRows = array();
    $aDependencies = array();
    foreach ($aTables as $aTable) {
        $aTableRows[$aTable[0]] = $aTable;
        $aDependencies[$aTable[0]] = array();
    }
    $oStatement = $oPdo->query("SELECT TABLE_NAME, REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        if (isset($aDependencies[$aRow["TABLE_NAME"]], $aDependencies[$aRow["REFERENCED_TABLE_NAME"]])
            && $aRow["TABLE_NAME"] !== $aRow["REFERENCED_TABLE_NAME"]) {
            $aDependencies[$aRow["TABLE_NAME"]][$aRow["REFERENCED_TABLE_NAME"]] = true;
        }
    }
    $aSortedTables = array();
    $aTableStates = array();
    foreach ($aTables as $aTable) {
        $aStack = array($aTable[0]);
        while (count($aStack) > 0) {
            $sTableName = end($aStack);
            if (isset($aTableStates[$sTableName]) && $aTableStates[$sTableName] == "done") {
                array_pop($aStack);
                continue;
            }
            if (!isset($aTableStates[$sTableName])) {
                $aTableStates[$sTableName] = "visiting";
            }
            $blDependencyAdded = false;
            foreach ($aDependencies[$sTableName] as $sReferencedTableName => $blDependency) {
                if (!isset($aTableStates[$sReferencedTableName])) {
                    $aStack[] = $sReferencedTableName;
                    $blDependencyAdded = true;
                    break;
                }
            }
            if ($blDependencyAdded) {
                continue;
            }
            $aSortedTables[] = $aTableRows[$sTableName];
            $aTableStates[$sTableName] = "done";
            array_pop($aStack);
        }
    }
    return $aSortedTables;
}

function diffGetCurrentDump($oPdo) {
    $aTables = diffFetchDatabaseTables($oPdo);
    return diffParseDatabaseSql(getDatabaseBackupSql($oPdo, $aTables));
}

function diffGetTableRows($aDump, $sTableName) {
    return isset($aDump["tables"][$sTableName]) ? $aDump["tables"][$sTableName]["rows"] : array();
}

function diffRowsByColumn($aDump, $sTableName, $sColumnName) {
    $aRows = array();
    foreach (diffGetTableRows($aDump, $sTableName) as $aRow) {
        if (array_key_exists($sColumnName, $aRow) && $aRow[$sColumnName] !== null) {
            $aRows[(string)$aRow[$sColumnName]] = $aRow;
        }
    }
    return $aRows;
}

function diffRowsGroupedByColumn($aDump, $sTableName, $sColumnName) {
    $aRows = array();
    foreach (diffGetTableRows($aDump, $sTableName) as $aRow) {
        if (array_key_exists($sColumnName, $aRow) && $aRow[$sColumnName] !== null) {
            $sKey = (string)$aRow[$sColumnName];
            if (!isset($aRows[$sKey])) {
                $aRows[$sKey] = array();
            }
            $aRows[$sKey][] = $aRow;
        }
    }
    return $aRows;
}

function diffRowValue($aRow, $sColumnName) {
    if (!is_array($aRow) || !array_key_exists($sColumnName, $aRow)) {
        return null;
    }
    return $aRow[$sColumnName];
}

function diffTrimmedValue($aRow, $sColumnName) {
    $mValue = diffRowValue($aRow, $sColumnName);
    return $mValue === null ? "" : trim((string)$mValue);
}

function diffJoinNonEmptyValues($aValues, $sSeparator) {
    $aResult = array();
    foreach ($aValues as $mValue) {
        $sValue = trim((string)$mValue);
        if ($sValue != "") {
            $aResult[] = $sValue;
        }
    }
    return implode($sSeparator, $aResult);
}

function diffCompareSubjectItems($aFirst, $aSecond) {
    $iFirstActive = (int)diffRowValue($aFirst, "is_active");
    $iSecondActive = (int)diffRowValue($aSecond, "is_active");
    if ($iFirstActive != $iSecondActive) {
        return $iSecondActive - $iFirstActive;
    }
    $iFirstPrimary = (int)diffRowValue($aFirst, "is_primary");
    $iSecondPrimary = (int)diffRowValue($aSecond, "is_primary");
    if ($iFirstPrimary != $iSecondPrimary) {
        return $iSecondPrimary - $iFirstPrimary;
    }
    return (int)diffRowValue($aFirst, "id") - (int)diffRowValue($aSecond, "id");
}

function diffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts) {
    if (isset($aSubjectNames[$sSubjectId])) {
        $sName = diffTrimmedValue($aSubjectNames[$sSubjectId], "name");
        if ($sName != "") {
            return $sName;
        }
    }
    if (isset($aNicknames[$sSubjectId])) {
        $aRows = $aNicknames[$sSubjectId];
        usort($aRows, "diffCompareSubjectItems");
        foreach ($aRows as $aRow) {
            $sName = diffTrimmedValue($aRow, "nickname");
            if ($sName != "") {
                return $sName;
            }
        }
    }
    if (isset($aSubjectContacts[$sSubjectId])) {
        $aRows = $aSubjectContacts[$sSubjectId];
        usort($aRows, "diffCompareSubjectItems");
        foreach ($aRows as $aRow) {
            $sContactId = diffTrimmedValue($aRow, "contact_id");
            if (isset($aContacts[$sContactId])) {
                $sName = diffTrimmedValue($aContacts[$sContactId], "contact_value");
                if ($sName != "") {
                    return $sName;
                }
            }
        }
    }
    return "Unnamed subject";
}

function diffBuildPersonDisplayName($aPerson, $sFallbackName) {
    $sBase = diffJoinNonEmptyValues(array(
        diffRowValue($aPerson, "title_before"),
        diffRowValue($aPerson, "first_name"),
        diffRowValue($aPerson, "middle_name"),
        diffRowValue($aPerson, "last_name")
    ), " ");
    $sTitleAfter = diffTrimmedValue($aPerson, "title_after");
    if ($sTitleAfter != "") {
        $sBase = $sBase != "" ? $sBase . ", " . $sTitleAfter : $sTitleAfter;
    }
    return $sBase != "" ? $sBase : $sFallbackName;
}

function diffBuildPersonRows($aDump) {
    $aSubjects = diffRowsByColumn($aDump, "ex_subjects", "id");
    $aPersons = diffRowsByColumn($aDump, "ex_persons", "subject_id");
    $aSubjectNames = diffRowsByColumn($aDump, "ex_subject_names", "subject_id");
    $aNicknames = diffRowsGroupedByColumn($aDump, "ex_subject_nicknames", "subject_id");
    $aSubjectContacts = diffRowsGroupedByColumn($aDump, "ex_subject_contacts", "subject_id");
    $aContacts = diffRowsByColumn($aDump, "ex_contacts", "id");
    $aIds = array();
    foreach ($aSubjects as $sSubjectId => $aSubject) {
        if (diffTrimmedValue($aSubject, "subject_type") == "person") {
            $aIds[$sSubjectId] = true;
        }
    }
    foreach ($aPersons as $sSubjectId => $aPerson) {
        $aIds[$sSubjectId] = true;
    }
    ksort($aIds, SORT_NUMERIC);
    $aRows = array();
    foreach ($aIds as $sSubjectId => $blUsed) {
        $aSubject = isset($aSubjects[$sSubjectId]) ? $aSubjects[$sSubjectId] : array();
        $aPerson = isset($aPersons[$sSubjectId]) ? $aPersons[$sSubjectId] : array();
        $sFallbackName = diffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts);
        $aRows[$sSubjectId] = array(
            "subject_id" => $sSubjectId,
            "name" => diffBuildPersonDisplayName($aPerson, $sFallbackName),
            "subject_type" => diffRowValue($aSubject, "subject_type"),
            "is_active" => diffRowValue($aSubject, "is_active"),
            "legacy_id" => diffRowValue($aSubject, "legacy_id"),
            "person_row" => isset($aPersons[$sSubjectId]) ? "yes" : "no",
            "title_before" => diffRowValue($aPerson, "title_before"),
            "first_name" => diffRowValue($aPerson, "first_name"),
            "middle_name" => diffRowValue($aPerson, "middle_name"),
            "last_name" => diffRowValue($aPerson, "last_name"),
            "title_after" => diffRowValue($aPerson, "title_after"),
            "birth_name" => diffRowValue($aPerson, "birth_name"),
            "birth_number" => diffRowValue($aPerson, "birth_number"),
            "birth_date" => diffRowValue($aPerson, "birth_date"),
            "death_date" => diffRowValue($aPerson, "death_date")
        );
    }
    return $aRows;
}

function diffBuildSubjectRows($aDump) {
    $aSubjects = diffRowsByColumn($aDump, "ex_subjects", "id");
    $aPersons = diffRowsByColumn($aDump, "ex_persons", "subject_id");
    $aSubjectNames = diffRowsByColumn($aDump, "ex_subject_names", "subject_id");
    $aNicknames = diffRowsGroupedByColumn($aDump, "ex_subject_nicknames", "subject_id");
    $aSubjectContacts = diffRowsGroupedByColumn($aDump, "ex_subject_contacts", "subject_id");
    $aContacts = diffRowsByColumn($aDump, "ex_contacts", "id");
    ksort($aSubjects, SORT_NUMERIC);
    $aRows = array();
    foreach ($aSubjects as $sSubjectId => $aSubject) {
        $sFallbackName = diffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts);
        if (diffTrimmedValue($aSubject, "subject_type") == "person" && isset($aPersons[$sSubjectId])) {
            $sName = diffBuildPersonDisplayName($aPersons[$sSubjectId], $sFallbackName);
        } else {
            $sName = $sFallbackName;
        }
        $aRows[$sSubjectId] = array(
            "subject_id" => $sSubjectId,
            "name" => $sName,
            "subject_type" => diffRowValue($aSubject, "subject_type"),
            "is_active" => diffRowValue($aSubject, "is_active"),
            "legacy_id" => diffRowValue($aSubject, "legacy_id")
        );
    }
    return $aRows;
}

function diffGetFieldChanges($aBackupRow, $aCurrentRow, $aFields) {
    $aChanges = array();
    foreach ($aFields as $sField => $sLabel) {
        $mBackupValue = diffRowValue($aBackupRow, $sField);
        $mCurrentValue = diffRowValue($aCurrentRow, $sField);
        if ($mBackupValue !== $mCurrentValue) {
            $aChanges[] = array(
                "field" => $sLabel,
                "backup" => $mBackupValue,
                "current" => $mCurrentValue
            );
        }
    }
    return $aChanges;
}

function diffCompareEntityRows($aBackupRows, $aCurrentRows, $aFields) {
    $aResult = array(
        "missing" => array(),
        "added" => array(),
        "changed" => array()
    );
    foreach ($aBackupRows as $sKey => $aBackupRow) {
        if (!isset($aCurrentRows[$sKey])) {
            $aResult["missing"][] = $aBackupRow;
            continue;
        }
        $aChanges = diffGetFieldChanges($aBackupRow, $aCurrentRows[$sKey], $aFields);
        if ($aChanges) {
            $aResult["changed"][] = array(
                "backup" => $aBackupRow,
                "current" => $aCurrentRows[$sKey],
                "changes" => $aChanges
            );
        }
    }
    foreach ($aCurrentRows as $sKey => $aCurrentRow) {
        if (!isset($aBackupRows[$sKey])) {
            $aResult["added"][] = $aCurrentRow;
        }
    }
    return $aResult;
}

function diffNormalizeRowForHash($aRow) {
    ksort($aRow, SORT_STRING);
    return $aRow;
}

function diffGetRowHash($aRow) {
    return sha1(json_encode(diffNormalizeRowForHash($aRow)));
}

function diffBuildRowKey($aRow, $aPrimaryKeys, $iIndex) {
    if (!$aPrimaryKeys) {
        return "row:" . $iIndex . ":" . diffGetRowHash($aRow);
    }
    $aParts = array();
    foreach ($aPrimaryKeys as $sColumnName) {
        $aParts[$sColumnName] = diffRowValue($aRow, $sColumnName);
    }
    return json_encode($aParts);
}

function diffBuildTableRowMap($aDump, $sTableName) {
    $aRows = array();
    if (!isset($aDump["tables"][$sTableName])) {
        return $aRows;
    }
    $aPrimaryKeys = $aDump["tables"][$sTableName]["primary_keys"];
    foreach ($aDump["tables"][$sTableName]["rows"] as $iIndex => $aRow) {
        $sKey = diffBuildRowKey($aRow, $aPrimaryKeys, $iIndex);
        $aRows[$sKey] = array(
            "row" => $aRow,
            "hash" => diffGetRowHash($aRow)
        );
    }
    return $aRows;
}

function diffCompareTableRows($aBackupDump, $aCurrentDump) {
    $aNames = array();
    foreach ($aBackupDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    foreach ($aCurrentDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    ksort($aNames, SORT_STRING);
    $aRows = array();
    foreach ($aNames as $sTableName => $blUsed) {
        $aBackupRows = isset($aBackupDump["tables"][$sTableName]) ? $aBackupDump["tables"][$sTableName]["rows"] : array();
        $aCurrentRows = isset($aCurrentDump["tables"][$sTableName]) ? $aCurrentDump["tables"][$sTableName]["rows"] : array();
        $aBackupMap = diffBuildTableRowMap($aBackupDump, $sTableName);
        $aCurrentMap = diffBuildTableRowMap($aCurrentDump, $sTableName);
        $iMissingRows = 0;
        $iAddedRows = 0;
        $iChangedRows = 0;
        foreach ($aBackupMap as $sKey => $aBackupRow) {
            if (!isset($aCurrentMap[$sKey])) {
                $iMissingRows++;
            } elseif ($aBackupRow["hash"] !== $aCurrentMap[$sKey]["hash"]) {
                $iChangedRows++;
            }
        }
        foreach ($aCurrentMap as $sKey => $aCurrentRow) {
            if (!isset($aBackupMap[$sKey])) {
                $iAddedRows++;
            }
        }
        $aRows[] = array(
            "table" => $sTableName,
            "backup_rows" => count($aBackupRows),
            "current_rows" => count($aCurrentRows),
            "missing_rows" => $iMissingRows,
            "added_rows" => $iAddedRows,
            "changed_rows" => $iChangedRows
        );
    }
    return $aRows;
}

function diffCompareStructure($aBackupDump, $aCurrentDump) {
    $aNames = array();
    foreach ($aBackupDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    foreach ($aCurrentDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    ksort($aNames, SORT_STRING);
    $aRows = array();
    foreach ($aNames as $sTableName => $blUsed) {
        if (!isset($aBackupDump["tables"][$sTableName])) {
            $aRows[] = array("table" => $sTableName, "difference" => "Table exists only in the current database.");
        } elseif (!isset($aCurrentDump["tables"][$sTableName])) {
            $aRows[] = array("table" => $sTableName, "difference" => "Table exists only in the uploaded backup.");
        } elseif ($aBackupDump["tables"][$sTableName]["create"] !== $aCurrentDump["tables"][$sTableName]["create"]) {
            $aRows[] = array("table" => $sTableName, "difference" => "Table structure is different.");
        }
    }
    return $aRows;
}

function diffUploadErrorMessage($iError) {
    if ($iError == UPLOAD_ERR_INI_SIZE || $iError == UPLOAD_ERR_FORM_SIZE) {
        return "The uploaded file is too large.";
    }
    if ($iError == UPLOAD_ERR_PARTIAL) {
        return "The uploaded file was received only partially.";
    }
    if ($iError == UPLOAD_ERR_NO_FILE) {
        return "No backup file was uploaded.";
    }
    if ($iError == UPLOAD_ERR_NO_TMP_DIR) {
        return "The server upload directory is missing.";
    }
    if ($iError == UPLOAD_ERR_CANT_WRITE) {
        return "The uploaded file could not be saved.";
    }
    if ($iError == UPLOAD_ERR_EXTENSION) {
        return "The upload was stopped by a PHP extension.";
    }
    return "The backup file could not be uploaded.";
}

function diffTextValue($mValue) {
    if ($mValue === null) {
        return "NULL";
    }
    $sValue = (string)$mValue;
    return $sValue != "" ? $sValue : "(empty)";
}

function diffRenderChangeList($aChanges) {
    $aItems = array();
    foreach ($aChanges as $aChange) {
        $aItems[] = html($aChange["field"] . ": " . diffTextValue($aChange["backup"]) . " -> " . diffTextValue($aChange["current"]));
    }
    return implode("<br>", $aItems);
}

function diffRenderEntityTable($aRows, $aColumns) {
    global $sEmptyValueEmoji;

    if (!$aRows) {
        echo "  <p>" . $sEmptyValueEmoji . "</p>\n";
        return;
    }
    echo "  <table class=\"consistency-table\">\n",
        "    <thead>\n",
        "      <tr>\n";
    foreach ($aColumns as $sColumn => $sLabel) {
        echo "        <th>" . html($sLabel) . "</th>\n";
    }
    echo "      </tr>\n",
        "    </thead>\n",
        "    <tbody>\n";
    foreach ($aRows as $aRow) {
        echo "      <tr>\n";
        foreach ($aColumns as $sColumn => $sLabel) {
            echo "        <td>" . htmlValue(diffRowValue($aRow, $sColumn)) . "</td>\n";
        }
        echo "      </tr>\n";
    }
    echo "    </tbody>\n",
        "  </table>\n";
}

function diffRenderChangedEntityTable($aRows) {
    global $sEmptyValueEmoji;

    if (!$aRows) {
        echo "  <p>" . $sEmptyValueEmoji . "</p>\n";
        return;
    }
    echo "  <table class=\"consistency-table\">\n",
        "    <thead>\n",
        "      <tr>\n",
        "        <th>Subject ID</th>\n",
        "        <th>Backup Name</th>\n",
        "        <th>Current Name</th>\n",
        "        <th>Changed Fields</th>\n",
        "      </tr>\n",
        "    </thead>\n",
        "    <tbody>\n";
    foreach ($aRows as $aRow) {
        echo "      <tr>\n",
            "        <td>" . htmlValue(diffRowValue($aRow["backup"], "subject_id")) . "</td>\n",
            "        <td>" . htmlValue(diffRowValue($aRow["backup"], "name")) . "</td>\n",
            "        <td>" . htmlValue(diffRowValue($aRow["current"], "name")) . "</td>\n",
            "        <td>" . diffRenderChangeList($aRow["changes"]) . "</td>\n",
            "      </tr>\n";
    }
    echo "    </tbody>\n",
        "  </table>\n";
}

function isThrobberLockTarget($sUserAgent) {
    $blThrobberGeckoEngine = preg_match("/Gecko\/\d+/i", $sUserAgent) && preg_match("/Firefox\/\d+/i", $sUserAgent);
    $blThrobberPmdLike = preg_match("/(?:Android|iPhone|iPad|iPod|Mobile|Tablet|Silk|Kindle)/i", $sUserAgent);
    $blThrobberChromiumEngine = preg_match("/(?:Chrome|Chromium|CriOS|EdgA|SamsungBrowser|OPR|Opera)/i", $sUserAgent);
    return !$blThrobberGeckoEngine && $blThrobberPmdLike && $blThrobberChromiumEngine;
}
