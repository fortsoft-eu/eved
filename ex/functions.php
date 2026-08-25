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
        array("contact_type" => "idds", "name" => "IDDS", "is_active" => 1, "order" => 55),
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
        while ($aRow = $oStatement->fetch()) {
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
    foreach (fetchContactTypes($oPdo, false) as $aType) {
        if ($aType["contact_type"] == $sType) {
            return $aType["name"];
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
        $aMap[$aType["contact_type"]] = true;
    }
    return $aMap;
}

function isOriginalContactType($sContactType) {
    $aMap = originalContactTypeMap();
    return isset($aMap[$sContactType]);
}

function buildContactTypeKeyBase($sName) {
    $sKey = trim($sName);
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
        if (!$oStatement->fetch()) {
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
    return $oStatement->fetchAll();
}

function renderContactTypeAdminRow($aContactType, $blShowActions = true) {
    global $sDeleteEmoji, $sEditEmoji, $sMergeEmoji, $sMoveUpEmoji, $sMoveDownEmoji;

    $blIsActive = (int)$aContactType["is_active"] == 1;
    return "      <tr data-contact-type-id=\"" . html($aContactType["id"]) . "\" data-contact-type-name=\"" . html($aContactType["name"]) . "\" data-contact-type-active=\"" . ($blIsActive ? "1" : "0") . "\" data-contact-type-order=\"" . html($aContactType["order"]) . "\">\n"
        . "        <td>" . html($aContactType["name"]) . "</td>\n"
        . "        <td>" . html($aContactType["contact_count"]) . "</td>\n"
        . "        <td>" . ($blIsActive ? "Yes" : "No") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"ia js-move-contact-type-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"ia js-move-contact-type-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"ia js-merge-contact-type\" title=\"Merge into this contact type\" aria-label=\"Merge into this contact type\">" . $sMergeEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"ia js-edit-contact-type\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"ia js-delete-contact-type\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "") . "</td>\n"
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
    $aCurrent = $oStatement->fetch();
    if (!$aCurrent) {
        throw new Exception("Contact type was not found.");
    }
    if ($sDirection == "up") {
        $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_contact_types WHERE `order` < :order ORDER BY `order` DESC, id DESC LIMIT 1 FOR UPDATE");
    } else {
        $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_contact_types WHERE `order` > :order ORDER BY `order` ASC, id ASC LIMIT 1 FOR UPDATE");
    }
    $oStatement->execute(array("order" => (int)$aCurrent["order"]));
    $aOther = $oStatement->fetch();
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
    $aContacts = $oStatement->fetchAll();
    foreach ($aContacts as $aContact) {
        $iSourceContactId = (int)$aContact["id"];
        $iTargetContactId = (int)$aContact["target_contact_id"];
        if ($iTargetContactId > 0) {
            $oSubjectStatement = $oPdo->prepare("SELECT id, subject_id FROM ex_subject_contacts WHERE contact_id = :contact_id FOR UPDATE");
            $oSubjectStatement->execute(array("contact_id" => $iSourceContactId));
            $aSubjectContacts = $oSubjectStatement->fetchAll();
            foreach ($aSubjectContacts as $aSubjectContact) {
                $oDuplicateStatement = $oPdo->prepare("SELECT id FROM ex_subject_contacts WHERE subject_id = :subject_id AND contact_id = :contact_id");
                $oDuplicateStatement->execute(array(
                    "subject_id" => (int)$aSubjectContact["subject_id"],
                    "contact_id" => $iTargetContactId
                ));
                if ($oDuplicateStatement->fetch()) {
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
    $sText = trim($sValue);
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
        $sHost = isset($aParts["host"]) ? strtolower($aParts["host"]) : "";
        $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
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

function normalizeIcqContactValue($sValue) {
    $sText = trim($sValue);
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
    return strpos($sValue, "-") === false || trim($sValue) == $sText ? $sText : false;
}

function normalizeEmailContactValue($sValue) {
    $sText = trim($sValue);
    if ($sText == "") {
        return "";
    }
    return filter_var($sText, FILTER_VALIDATE_EMAIL) !== false ? $sText : false;
}

function normalizeIddsContactValue($sValue) {
    $sText = trim($sValue);
    if ($sText == "") {
        return "";
    }
    return preg_match("/^[a-zA-Z0-9]{7}$/", $sText) ? strtolower($sText) : false;
}

function normalizeSkypeContactValue($sValue) {
    $sText = trim($sValue);
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
    if ($sContactType == "youtube") {
        return normalizeYouTubeContactValue($sContactValue, true);
    }
    if ($sContactType == "telegram") {
        return normalizeTelegramContactValue($sContactValue);
    }
    if ($sContactType == "email") {
        return normalizeEmailContactValue($sContactValue);
    }
    if ($sContactType == "idds") {
        return normalizeIddsContactValue($sContactValue);
    }
    if ($sContactType == "icq") {
        return normalizeIcqContactValue($sContactValue);
    }
    if ($sContactType == "skype") {
        return normalizeSkypeContactValue($sContactValue);
    }
    $mKnownValue = normalizeKnownContactValue($sContactType, $sContactValue);
    if ($mKnownValue !== null) {
        return $mKnownValue;
    }
    return trim($sContactValue);
}

function contactCanonicalValue($sContactType, $sContactValue) {
    $mKnownValue = null;
    $sContactType = contactTypeKey($sContactType);
    if (isPhoneContactType($sContactType)) {
        $mKnownValue = normalizePhoneContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : $sContactValue;
    }
    if ($sContactType == "youtube") {
        $mKnownValue = normalizeYouTubeContactValue($sContactValue, true);
        return $mKnownValue !== false ? (string)$mKnownValue : $sContactValue;
    }
    if ($sContactType == "telegram") {
        $mKnownValue = normalizeTelegramContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : $sContactValue;
    }
    if ($sContactType == "email") {
        $mKnownValue = normalizeEmailContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : $sContactValue;
    }
    if ($sContactType == "idds") {
        $mKnownValue = normalizeIddsContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : $sContactValue;
    }
    if ($sContactType == "icq") {
        $mKnownValue = normalizeIcqContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : $sContactValue;
    }
    if ($sContactType == "skype") {
        $mKnownValue = normalizeSkypeContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : $sContactValue;
    }
    $mKnownValue = normalizeKnownContactValue($sContactType, $sContactValue);
    if ($mKnownValue !== null) {
        return $mKnownValue !== false ? (string)$mKnownValue : $sContactValue;
    }
    return $sContactValue;
}

function contactInputErrorMessage($sContactType) {
    $sContactType = contactTypeKey($sContactType);
    if (isPhoneContactType($sContactType)) {
        return "Phone number must be a valid international number.";
    }
    if ($sContactType == "youtube") {
        return "YouTube contact must be a YouTube link or handle.";
    }
    if ($sContactType == "telegram") {
        return "Telegram contact must be a valid Telegram link, handle, invite link, sticker set or language link.";
    }
    if ($sContactType == "email") {
        return "E-mail address is invalid.";
    }
    if ($sContactType == "idds") {
        return "IDDS must have exactly 7 letters or digits.";
    }
    if ($sContactType == "icq") {
        return "ICQ must have 5 to 9 digits, either without hyphens or grouped from the right.";
    }
    if ($sContactType == "skype") {
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
    if (trim($sValue) == "") {
        return false;
    }
    if (isPhoneContactType($sType)) {
        return normalizePhoneContactValue($sValue) === false;
    }
    if ($sType == "youtube") {
        return normalizeYouTubeContactValue($sValue, true) === false;
    }
    if ($sType == "telegram") {
        return normalizeTelegramContactValue($sValue) === false;
    }
    if ($sType == "email") {
        return normalizeEmailContactValue($sValue) === false;
    }
    if ($sType == "idds") {
        return normalizeIddsContactValue($sValue) === false;
    }
    if ($sType == "icq") {
        return normalizeIcqContactValue($sValue) === false;
    }
    if ($sType == "skype") {
        return normalizeSkypeContactValue($sValue) === false;
    }
    $mKnownValue = normalizeKnownContactValue($sType, $sValue);
    if ($mKnownValue !== null) {
        return $mKnownValue === false;
    }
    return false;
}

function youTubeContactHref($sValue) {
    $sValue = trim($sValue);
    if ($sValue == "") {
        return "";
    }
    $sValue = normalizeYouTubeContactValue($sValue, true);
    return $sValue !== false ? $sValue : "";
}

function normalizeWebContactValue($sValue) {
    $sText = trim($sValue);
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
    $sScheme = strtolower($aParts["scheme"]);
    $sHost = strtolower($aParts["host"]);
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
        $sUrl .= $aParts["path"];
    }
    if (isset($aParts["query"])) {
        $sUrl .= "?" . $aParts["query"];
    }
    if (isset($aParts["fragment"])) {
        $sUrl .= "#" . $aParts["fragment"];
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
    $sText = trim($sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sHandle = "";
    $sPrefix = "";
    $blLooksLikeUrl = false;
    if (!isset($aRules[$sContactType])) {
        return null;
    }
    if ($sText == "") {
        return "";
    }
    $aRule = $aRules[$sContactType];
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
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", $aParts["host"])) : "";
        if (!in_array($sHost, $aRule["hosts"], true)) {
            return false;
        }
        $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
        if (!$sPath) {
            return false;
        }
        $aSegments = explode("/", $sPath);
        $sPrefix = isset($aRule["prefix"]) ? $aRule["prefix"] : "";
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
    return preg_match($aRule["pattern"], $sHandle) ? $aRule["base"] . rawurlencode($sHandle) : false;
}

function normalizeLinkedInContactValue($sValue) {
    $sText = trim($sValue);
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
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", $aParts["host"])) : "";
        if ($sHost != "linkedin.com") {
            return false;
        }
        $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
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
    $sText = trim($sValue);
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
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", $aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
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
    $sText = trim($sValue);
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
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", $aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
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
    $sText = trim($sValue);
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
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", $aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
        if ($sHost != "goodreads.com" || !preg_match("#^user/show/([0-9]+)(?:[.-].*)?$#i", $sPath, $aMatches)) {
            return false;
        }
        return "https://www.goodreads.com/user/show/" . $aMatches[1];
    }
    return preg_match("/^[0-9]+$/", $sText) ? "https://www.goodreads.com/user/show/" . $sText : false;
}

function normalizeFederatedContactValue($sValue, $sPathPrefix) {
    $sText = trim($sValue);
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
        $sHost = isset($aParts["host"]) ? strtolower($aParts["host"]) : "";
        $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
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
    return preg_match("/^([A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\\.)+[A-Za-z](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/", $sHandle);
}

function normalizeBlueskyContactValue($sValue) {
    $sText = trim($sValue);
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
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", $aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
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
    $sText = trim($sValue);
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^https?://matrix\\.to/\\#/(@[^?\\#]+)#i", $sText, $aMatches)) {
        $sText = rawurldecode($aMatches[1]);
    }
    return preg_match("/^@[a-z0-9._=\\-\\/+]+:[A-Za-z0-9.-]+(?::[0-9]+)?$/", $sText) ? $sText : false;
}

function normalizeJabberContactValue($sValue) {
    $sText = trim($sValue);
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
    $sText = strtoupper(trim($sValue));
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
    $sText = trim($sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    $sDigits = "";
    if ($sText == "") {
        return "";
    }
    if ($sContactType == "whatsapp") {
        if (preg_match("#^//#", $sText)) {
            $sText = "https:" . $sText;
        }
        if (preg_match("#^https?://#i", $sText)) {
            $aParts = parse_url($sText);
            $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", $aParts["host"])) : "";
            $sPath = isset($aParts["path"]) ? trim($aParts["path"], "/") : "";
            if ($sHost == "wa.me" && preg_match("/^[0-9]+$/", $sPath)) {
                $sText = "+" . $sPath;
            } elseif (($sHost == "api.whatsapp.com" || $sHost == "whatsapp.com") && isset($aParts["query"]) && preg_match("/(?:^|&)phone=([0-9]+)/", $aParts["query"], $aMatches)) {
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
    if ($sContactType == "telegram") {
        return normalizeTelegramContactValue($sContactValue);
    }
    if ($mProfileValue !== null) {
        return $mProfileValue;
    }
    if ($sContactType == "web") {
        return normalizeWebContactValue($sContactValue);
    }
    if ($sContactType == "jabber") {
        return normalizeJabberContactValue($sContactValue);
    }
    if ($sContactType == "matrix") {
        return normalizeMatrixContactValue($sContactValue);
    }
    if ($sContactType == "mastodon") {
        return normalizeFederatedContactValue($sContactValue, "@");
    }
    if ($sContactType == "lemmy") {
        return normalizeFederatedContactValue($sContactValue, "u");
    }
    if ($sContactType == "bluesky") {
        return normalizeBlueskyContactValue($sContactValue);
    }
    if ($sContactType == "linkedin") {
        return normalizeLinkedInContactValue($sContactValue);
    }
    if ($sContactType == "stackoverflow") {
        return normalizeStackOverflowContactValue($sContactValue);
    }
    if ($sContactType == "steam") {
        return normalizeSteamContactValue($sContactValue);
    }
    if ($sContactType == "goodreads") {
        return normalizeGoodreadsContactValue($sContactValue);
    }
    if ($sContactType == "orcid") {
        return normalizeOrcidContactValue($sContactValue);
    }
    if ($sContactType == "whatsapp" || $sContactType == "viber") {
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
    if (isPhoneContactType($sType) || $sType == "whatsapp" || $sType == "viber") {
        return phoneContactDisplayValue($sCanonicalValue);
    }
    return $sCanonicalValue;
}

function contactHref($sType, $sValue, $blAllowExternalLinks = false) {
    $sType = contactTypeKey($sType);
    $sText = trim($sValue);
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

function renderContactValueText($sType, $sValue, $sTooltipAttribute = "") {
    $sDisplayValue = contactDisplayValue($sType, $sValue);
    $sClass = "cv" . (contactValueIsInvalid($sType, $sValue) ? " vx" : "");
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
        $sHtml .= "<a class=\"cc\" href=\"#\" title=\"Copy\" aria-label=\"Copy\"><span class=\"cb\">" . $sCopyEmoji . "</span></a>";
        $blHasIcon = true;
    }
    if ($sHref != "") {
        $sTarget = $blAllowExternalLinks && preg_match("#^https?://#i", $sHref) ? " target=\"_blank\" rel=\"noopener noreferrer\"" : "";
        $sLinkTitle = contactLinkTitle($sType);
        return $sHtml . ($blHasIcon ? "" : " ") . "<a class=\"lk\" href=\"" . html($sHref) . "\"" . $sTarget . " title=\"" . html($sLinkTitle) . "\" aria-label=\"" . html($sLinkTitle) . "\">" . contactLinkEmoji($sType) . "</a>";
    }
    return $sHtml;
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
        $sJson = preg_replace("/^\\xEF\\xBB\\xBF/", "", $sJson);
        $aDecoded = json_decode($sJson, true);
        if (is_array($aDecoded)) {
            $aMetadata = $aDecoded;
        }
    }
    return $aMetadata;
}

function postalCodePatternMatches($sPattern, $sPostalCode) {
    $sPattern = trim($sPattern);
    if ($sPattern == "") {
        return true;
    }
    return @preg_match("~^(?:" . str_replace("~", "\\~", $sPattern) . ")$~i", $sPostalCode);
}

function postalCodeAlnum($sPostalCode) {
    return preg_replace("/[^A-Z0-9]/", "", strtoupper($sPostalCode));
}

function addressCountryCode($sCountry) {
    $sCountry = strtoupper(trim($sCountry ?? ""));
    return $sCountry == "CS" ? "CZ" : $sCountry;
}

function postalCodeFormatByExample($sPostalCode, $sExamples) {
    $sAlnum = postalCodeAlnum($sPostalCode);
    $aExamples = explode(",", $sExamples);
    $sExample = "";
    $sFormatted = "";
    $iIndex = 0;
    if ($sAlnum == "") {
        return "";
    }
    foreach ($aExamples as $sExampleCandidate) {
        if (strlen(postalCodeAlnum($sExampleCandidate)) == strlen($sAlnum)) {
            $sExample = trim($sExampleCandidate);
            break;
        }
    }
    if ($sExample == "") {
        return strtoupper(trim($sPostalCode));
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
    $sText = strtoupper(trim($sPostalCode ?? ""));
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
        $sCandidate = trim($sCandidate);
        if ($sCandidate != "" && postalCodePatternMatches($sPattern, $sCandidate)) {
            return array("valid" => true, "value" => postalCodeFormatByExample($sCandidate, $sExamples));
        }
    }
    return array("valid" => false, "value" => $sText);
}

function normalizePostalCode($sCountry, $sPostalCode) {
    $aPostalCode = analyzePostalCode($sCountry, $sPostalCode);
    return !empty($aPostalCode["valid"]) ? $aPostalCode["value"] : false;
}

function postalCodeDisplayValue($sCountry, $sPostalCode) {
    $aPostalCode = analyzePostalCode($sCountry, $sPostalCode);
    return !empty($aPostalCode["valid"]) ? $aPostalCode["value"] : $sPostalCode;
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
    return "<div class=\"ar\">" . $sPrefix . "<a href=\"#\" class=\"ia aa " . html($sClass) . "\" data-subject-id=\"" . html($iSubjectId) . "\" title=\"" . html($sTitle) . "\" aria-label=\"" . html($sTitle) . "\">" . $sAddEmoji . "</a>" . $sSuffix . "</div>";
}

function renderSubjectCellActionRow($sFirstAction, $sSecondAction = "") {
    if ($sFirstAction == "") {
        return $sSecondAction;
    }
    if ($sSecondAction == "") {
        return $sFirstAction;
    }
    return "<div class=\"ar\">" . $sFirstAction . $sSecondAction . "</div>";
}

function renderHiddenInactiveIndicator() {
    global $sHiddenInactiveEmoji;

    return "<span class=\"hi\" title=\"Hidden inactive content\" aria-label=\"Hidden inactive content\">" . $sHiddenInactiveEmoji . "</span>";
}

function renderEmptySubjectItemCell($blShowActions, $sClass, $sTitle, $iSubjectId, $blHasHiddenInactive, $blShowAddAction = true) {
    global $sEmptyValueEmoji;

    $sHiddenInactive = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    if ($blShowActions && $blShowAddAction) {
        return renderAddSubjectItemAction($sClass, $sTitle, $iSubjectId, $sHiddenInactive);
    }
    return $sHiddenInactive != "" ? $sHiddenInactive : $sEmptyValueEmoji;
}

function renderContactList($aContacts, $blShowActions = true, $iSubjectId = 0, $blShowCopy = true, $blAllowExternalLinks = true, $blHasHiddenInactive = false, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true, $blDeferData = false) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aContacts) {
        return renderEmptySubjectItemCell($blShowActions, "js-add-subject-contact", "New contact", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"cl\">";
    $aCellCopyValues = array();
    $iCellCopyValueCount = 0;
    $sHiddenInactiveAction = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    foreach ($aContacts as $aContact) {
        $sNote = trim((string)$aContact["note"]);
        $blIsPrimary = (int)$aContact["is_primary"] == 1;
        $blIsActive = (int)$aContact["is_active"] == 1;
        $sContactType = isset($aContact["contact_type"]) ? $aContact["contact_type"] : "";
        $sContactTypeName = isset($aContact["contact_type_name"]) && trim($aContact["contact_type_name"]) != "" ? $aContact["contact_type_name"] : contactTypeLabel($sContactType);
        $sContactValue = contactDisplayValue($sContactType, $aContact["contact_value"]);
        $sTimestampTooltipText = timestampTooltipText($aContact);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sCellCopyValue = $sContactTypeName . ": " . $sContactValue . ($sNote != "" ? " (" . $sNote . ")" : "");
        if (trim($sCellCopyValue) != "") {
            $iCellCopyValueCount++;
            if (!$blDeferData) {
                $aCellCopyValues[] = $sCellCopyValue;
            }
        }
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"la\">"
                . "<a href=\"#\" class=\"ia js-edit-subject-contact\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"ia js-delete-subject-contact\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sDataAttributes = "";
        if (!$blDeferData || $blShowActions) {
            $sDataAttributes = " data-subject-contact-id=\"" . html($aContact["subject_contact_id"]) . "\"";
        }
        if (!$blDeferData) {
            $sDataAttributes .= " data-contact-id=\"" . html($aContact["contact_id"]) . "\""
                . " data-contact-type-id=\"" . html(isset($aContact["contact_type_id"]) ? $aContact["contact_type_id"] : "") . "\""
                . " data-contact-type=\"" . html($sContactType) . "\""
                . " data-contact-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
                . " data-contact-active=\"" . ($blIsActive ? "1" : "0") . "\""
                . renderTimestampTooltipDataAttribute($aContact);
        }
        $sHtml .= "<div class=\"ci li" . ($blDeferData && $blShowActions ? " cd" : "") . ($blIsActive ? "" : " cx") . "\"" . $sDataAttributes . ">"
            . "<span class=\"dv\"" . $sTimestampTooltipAttribute . "><span class=\"ct\">" . html($sContactTypeName) . "</span>: "
            . renderContactValueText($sContactType, $aContact["contact_value"]) . "</span>"
            . renderContactValueActions($sContactType, $aContact["contact_value"], $blShowCopy, $blAllowExternalLinks)
            . "<span class=\"cn\">" . ($sNote != "" ? "(" . html($sNote) . ")" : "") . "</span>"
            . "<span class=\"cf\">"
            . "<span class=\"cp\" title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span>"
            . "<span class=\"cz\" title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span>"
            . "</span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? ($blDeferData && $iCellCopyValueCount > 1 ? renderDeferredCopyAction("js-copy-subject-contacts", "Copy items") : renderSubjectCellCopyAction($aCellCopyValues)) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= renderAddSubjectItemAction("js-add-subject-contact", "New contact", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= renderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function renderNicknameList($aNicknames, $blShowActions = true, $iSubjectId = 0, $blHasHiddenInactive = false, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true, $blDeferData = false) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aNicknames) {
        return renderEmptySubjectItemCell($blShowActions, "js-add-subject-nickname", "New nickname", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"il\">";
    $aCellCopyValues = array();
    $iCellCopyValueCount = 0;
    $sHiddenInactiveAction = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    foreach ($aNicknames as $aNickname) {
        $sContext = trim((string)$aNickname["context"]);
        $sNote = trim((string)$aNickname["note"]);
        $sCopyText = $aNickname["nickname"] . ($sContext != "" ? " [" . $sContext . "]" : "") . ($sNote != "" ? " (" . $sNote . ")" : "");
        if (trim($sCopyText) != "") {
            $iCellCopyValueCount++;
            if (!$blDeferData) {
                $aCellCopyValues[] = $sCopyText;
            }
        }
        $blIsPrimary = (int)$aNickname["is_primary"] == 1;
        $blIsActive = (int)$aNickname["is_active"] == 1;
        $sTimestampTooltipText = timestampTooltipText($aNickname);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"la\">"
                . "<a href=\"#\" class=\"ia js-edit-subject-nickname\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"ia js-delete-subject-nickname\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sDataAttributes = "";
        if ($blDeferData) {
            if ($blShowActions) {
                $sDataAttributes = " data-nickname-id=\"" . html($aNickname["id"]) . "\"";
            }
        } else {
            $sDataAttributes = " data-nickname-id=\"" . html($aNickname["id"]) . "\""
                . " data-subject-id=\"" . html($aNickname["subject_id"]) . "\""
                . " data-nickname=\"" . html($aNickname["nickname"]) . "\""
                . " data-context=\"" . html($sContext) . "\""
                . " data-note=\"" . html($sNote) . "\""
                . " data-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
                . " data-active=\"" . ($blIsActive ? "1" : "0") . "\"";
        }
        $sHtml .= "<div class=\"si li ni" . ($blDeferData && $blShowActions ? " nd" : "") . ($blIsActive ? "" : " ii") . "\"" . $sDataAttributes . ">"
            . "<span class=\"ns\"" . $sTimestampTooltipAttribute . ">"
            . "<span class=\"iv\">" . html($aNickname["nickname"]) . "</span>"
            . "<span class=\"ix\">" . ($sContext != "" ? " [" . html($sContext) . "]" : "") . "</span>"
            . "</span>"
            . "<span class=\"nt\">" . ($sNote != "" ? " (" . html($sNote) . ")" : "") . "</span>"
            . ($blDeferData ? (trim($sCopyText) != "" ? renderDeferredCopyAction("js-copy-subject-nickname") : "") : renderCopyAction($sCopyText))
            . "<span class=\"fl\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? ($blDeferData && $iCellCopyValueCount > 1 ? renderDeferredCopyAction("js-copy-subject-nicknames", "Copy items") : renderSubjectCellCopyAction($aCellCopyValues)) : "";
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
    $sLine = preg_replace("/[ \\t]+/", " ", trim($sLine));
    $sLine = preg_replace("/\\s+,/", ",", $sLine);
    $sLine = preg_replace("/,\\s*,+/", ",", $sLine);
    return trim($sLine, " ,");
}

function appendAddressTemplateValue(&$aLines, $sValue) {
    $aValueLines = preg_split("/\\r\\n|\\r|\\n/", $sValue);
    $iIndex = 0;
    if (!$aLines) {
        $aLines[] = "";
    }
    foreach ($aValueLines as $sValueLine) {
        $sValueLine = trim($sValueLine);
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
        "N" => trim($sSubjectName),
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

function renderAddressList($aAddresses, $blShowActions = true, $iSubjectId = 0, $sSubjectName = "", $blHasHiddenInactive = false, $aAddressDisplaySettings = null, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true, $blDeferData = false) {
    global $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aAddresses) {
        return renderEmptySubjectItemCell($blShowActions, "js-add-subject-address", "New address", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"il\">";
    $aCellCopyValues = array();
    $iCellCopyValueCount = 0;
    $sHiddenInactiveAction = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    foreach ($aAddresses as $aAddress) {
        $sText = renderAddressText($aAddress, $aAddressDisplaySettings);
        $sNote = trim((string)$aAddress["note"]);
        $sCopyText = $blDeferData ? "" : renderAddressCopyText($aAddress, $sSubjectName, $aAddressDisplaySettings);
        $sCellCopyValue = $sText . ($sNote != "" ? " (" . $sNote . ")" : "");
        if (trim($sCellCopyValue) != "") {
            $iCellCopyValueCount++;
            if (!$blDeferData) {
                $aCellCopyValues[] = $sCellCopyValue;
            }
        }
        $blIsPrimary = (int)$aAddress["is_primary"] == 1;
        $blIsActive = (int)$aAddress["is_active"] == 1;
        $sValueClass = $aAddress["address_type"] == "main" ? " am" : "";
        $sTimestampTooltipText = timestampTooltipText($aAddress);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"la\">"
                . "<a href=\"#\" class=\"ia js-edit-subject-address\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"ia js-delete-subject-address\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sDataAttributes = " data-address-id=\"" . html($aAddress["id"]) . "\"";
        if (!$blDeferData) {
            $sDataAttributes .= " data-subject-id=\"" . html($aAddress["subject_id"]) . "\""
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
                . " data-active=\"" . ($blIsActive ? "1" : "0") . "\"";
        }
        $sHtml .= "<div class=\"si li ai" . ($blDeferData ? " ad" : "") . ($blIsActive ? "" : " ii") . "\"" . $sDataAttributes . ">"
            . "<span class=\"iv" . $sValueClass . "\"" . $sTimestampTooltipAttribute . ">" . ($sText != "" ? html($sText) : $sEmptyValueEmoji) . "</span>"
            . ($blDeferData ? renderDeferredCopyAction("js-copy-subject-address") : renderCopyAction($sCopyText))
            . "<span class=\"nt\">" . ($sNote != "" ? "(" . html($sNote) . ")" : "") . "</span>"
            . "<span class=\"fl\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? ($blDeferData && $iCellCopyValueCount > 0 ? renderDeferredCopyAction("js-copy-subject-addresses", "Copy items") : renderSubjectCellCopyAction($aCellCopyValues, true)) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= renderAddSubjectItemAction("js-add-subject-address", "New address", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= renderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function renderGroupList($aGroups, $blShowActions = true, $iSubjectId = 0, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true, $blDeferData = false) {
    global $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji;

    if (!$aGroups) {
        return $blShowActions && $blShowAddAction ? renderAddSubjectItemAction("js-add-subject-group", "Assign group", $iSubjectId) : $sEmptyValueEmoji;
    }
    $sHtml = "<div class=\"il\">";
    $aCellCopyValues = array();
    $iCellCopyValueCount = 0;
    foreach ($aGroups as $aGroup) {
        if (trim($aGroup["name"]) != "") {
            $iCellCopyValueCount++;
            if (!$blDeferData) {
                $aCellCopyValues[] = $aGroup["name"];
            }
        }
        $sTimestampTooltipText = timestampTooltipText($aGroup);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"la\">"
                . "<a href=\"#\" class=\"ia js-edit-subject-group\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"ia js-delete-subject-group\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sDataAttributes = "";
        if ($blDeferData) {
            if ($blShowActions) {
                $sDataAttributes = " data-group-id=\"" . html($aGroup["group_id"]) . "\"";
            }
        } else {
            $sDataAttributes = " data-subject-id=\"" . html($aGroup["subject_id"]) . "\""
                . " data-group-id=\"" . html($aGroup["group_id"]) . "\""
                . " data-group-name=\"" . html($aGroup["name"]) . "\""
                . renderTimestampTooltipDataAttribute($aGroup);
        }
        $sHtml .= "<div class=\"si li gi\"" . $sDataAttributes . ">"
            . "<span class=\"iv\"" . $sTimestampTooltipAttribute . ">" . html($aGroup["name"]) . "</span>"
            . ($blDeferData ? (trim($aGroup["name"]) != "" ? renderDeferredCopyAction("js-copy-subject-group") : "") : renderCopyAction($aGroup["name"]))
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? ($blDeferData && $iCellCopyValueCount > 1 ? renderDeferredCopyAction("js-copy-subject-groups", "Copy items") : renderSubjectCellCopyAction($aCellCopyValues)) : "";
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
    $sHtml = "<div class=\"il\">";
    $iCellCopyValueCount = 0;
    $sHiddenInactiveAction = $blHasHiddenInactive ? renderHiddenInactiveIndicator() : "";
    foreach ($aNotes as $aNote) {
        $sNoteText = trim($aNote["note_text"]);
        if ($sNoteText != "") {
            $iCellCopyValueCount++;
        }
        $blIsActive = (int)$aNote["is_active"] == 1;
        $blIsPrimary = (int)$aNote["is_primary"] == 1;
        $sTimestampTooltipText = timestampTooltipText($aNote);
        $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"la\">"
                . "<a href=\"#\" class=\"ia js-edit-subject-note\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"ia js-delete-subject-note\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"si li no" . ($blIsActive ? "" : " ii") . "\""
            . " data-note-id=\"" . html($aNote["id"]) . "\""
            . " data-subject-id=\"" . html($aNote["subject_id"]) . "\""
            . " data-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"iv\"" . $sTimestampTooltipAttribute . ">" . htmlMultiline($aNote["note_text"]) . "</span>"
            . ($sNoteText != "" ? renderDeferredCopyAction("js-copy-subject-note") : "")
            . "<span class=\"fl\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction && $iCellCopyValueCount > 1 ? renderDeferredCopyAction("js-copy-subject-notes", "Copy items") : "";
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
    $sCountry = strtoupper(trim($sCountry ?? ""));
    $aCountryNames = getCountryNames();
    return isset($aCountryNames[$sCountry]) ? $aCountryNames[$sCountry] : $sCountry;
}

function countryDashPattern() {
    return "(?:-|\\x{2010}|\\x{2011}|\\x{2012}|\\x{2013}|\\x{2014}|\\x{2015}|\\x{2212})";
}

function normalizeCountrySearchText($sCountry) {
    $sCountry = html_entity_decode($sCountry, ENT_QUOTES | ENT_HTML5, "UTF-8");
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
    if (preg_match("/^\\s*([A-Za-z]{2})\\s*" . $sPattern . "\\s*(.*?)\\s*$/u", $sCountry, $aMatches)) {
        $sCountryCode = strtoupper($aMatches[1]);
        if (in_array($sCountryCode, $aCountryCodes, true)) {
            return $sCountryCode;
        }
    }
    if (preg_match("/^\\s*(.*?)\\s*" . $sPattern . "\\s*([A-Za-z]{2})\\s*$/u", $sCountry, $aMatches)) {
        $sCountryCode = strtoupper($aMatches[2]);
        if (in_array($sCountryCode, $aCountryCodes, true)) {
            return $sCountryCode;
        }
    }
    return "";
}

function countryNameToCode($sCountry) {
    $sCountry = trim($sCountry);
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
    $sHtml = "  <datalist id=\"" . html($sId) . "\">\n";

    foreach (getCountryNames() as $sCode => $sName) {
        $sHtml .= "    <option value=\"" . html($sCode) . " &#8212; " . html($sName) . "\"></option>\n";
    }
    return $sHtml . "  </datalist>\n";
}

function countryCodeToDisplayName($sCountry, $aSettings = null) {
    $sCountry = strtoupper(trim($sCountry ?? ""));
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
    return "      <p class=\"index-settings-note\">Options above the line apply only to this listing. Country options below the line are shared across the EX subproject.</p>\n";
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
        $iFullYear = $iYear < 54 ? 1900 + $iYear : 1800 + $iYear;
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
    $sClass = trim($sClass);
    if (isInvalidBirthNumber($mValue)) {
        $sClass = trim($sClass . " bx");
    }
    return $sClass;
}

function birthDateClass($mBirthNumber, $mBirthDate, $sClass = "") {
    $sClass = trim($sClass);
    $sBirthDate = trim((string)$mBirthDate);
    $sBirthNumberDate = birthNumberBirthDate($mBirthNumber);
    if ($sBirthDate != "" && $sBirthNumberDate != "" && $sBirthDate != $sBirthNumberDate) {
        $sClass = trim($sClass . " bx");
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

function exCalendarGetYear() {
    $iCurrentYear = (int)date("Y");
    $iYear = isset($_SESSION["ex_calendar"]) && is_array($_SESSION["ex_calendar"]) && isset($_SESSION["ex_calendar"]["iYear"]) ? (int)$_SESSION["ex_calendar"]["iYear"] : $iCurrentYear;
    if ($iYear < 1583 || $iYear > 9999) {
        $iYear = $iCurrentYear;
    }
    if (isset($_GET["iYear"])) {
        $sYear = trim((string)$_GET["iYear"]);
        if (!preg_match("/^[0-9]{1,4}$/", $sYear)) {
            send404AndExit();
        }
        $iYear = (int)$sYear;
        if ($iYear < 1583 || $iYear > 9999) {
            send404AndExit();
        }
    }
    return $iYear;
}

function exCalendarFetchNameDayGroups($oPdo) {
    $oStatement = $oPdo->query("SELECT id, name FROM ex_name_day_groups ORDER BY `order` ASC, id ASC");
    return $oStatement->fetchAll();
}

function exCalendarFetchExternalCalendars($oPdo) {
    $oStatement = $oPdo->query("SELECT id, calendar_name, calendar_url_hash, calendar_url FROM ex_calendar_fetches ORDER BY `order` ASC, id ASC");
    return $oStatement->fetchAll();
}

function exCalendarGetCalendarGroups($aExternalCalendars, $aNameDayGroups) {
    $aExternalCalendarNames = array();
    $aNameDayCalendars = array();
    $iCal = 3;
    foreach ($aExternalCalendars as $aExternalCalendar) {
        $aExternalCalendarNames[$iCal] = (string)$aExternalCalendar["calendar_name"];
        $iCal++;
    }
    foreach ($aNameDayGroups as $aNameDayGroup) {
        $aNameDayCalendars[$iCal] = (string)$aNameDayGroup["name"];
        $iCal++;
    }
    return array(
        "Persons" => array(
            1 => "Birthdays",
            2 => "Name Days"
        ),
        "Calendars" => $aExternalCalendarNames,
        "Name Days" => $aNameDayCalendars
    );
}

function exCalendarGetCalendars($aCalendarGroups) {
    $aCalendars = array();
    foreach ($aCalendarGroups as $aCalendarGroup) {
        foreach ($aCalendarGroup as $iCal => $sCalendar) {
            $aCalendars[$iCal] = $sCalendar;
        }
    }
    return $aCalendars;
}

function exCalendarGetICal($aCalendars, $iDefaultCal) {
    if (!isset($aCalendars[$iDefaultCal])) {
        send404AndExit();
    }
    $iCal = isset($_SESSION["ex_calendar"]) && is_array($_SESSION["ex_calendar"]) && isset($_SESSION["ex_calendar"]["iCal"]) ? (int)$_SESSION["ex_calendar"]["iCal"] : $iDefaultCal;
    if (!isset($aCalendars[$iCal])) {
        $iCal = $iDefaultCal;
    }
    if (isset($_GET["iCal"])) {
        $sCal = trim((string)$_GET["iCal"]);
        if (!preg_match("/^[1-9][0-9]*$/", $sCal)) {
            send404AndExit();
        }
        $iCal = (int)$sCal;
        if (!isset($aCalendars[$iCal])) {
            send404AndExit();
        }
    }
    return $iCal;
}

function exCalendarDateKey($iYear, $iMonth, $iDay) {
    return sprintf("%04d-%02d-%02d", (int)$iYear, (int)$iMonth, (int)$iDay);
}

function exCalendarDate($iYear, $iMonth, $iDay) {
    return DateTimeImmutable::createFromFormat("!Y-m-d", exCalendarDateKey($iYear, $iMonth, $iDay));
}

function exCalendarGetEasterSunday($iYear) {
    $iA = $iYear % 19;
    $iB = (int)($iYear / 100);
    $iC = $iYear % 100;
    $iD = (int)($iB / 4);
    $iE = $iB % 4;
    $iF = (int)(($iB + 8) / 25);
    $iG = (int)(($iB - $iF + 1) / 3);
    $iH = (19 * $iA + $iB - $iD - $iG + 15) % 30;
    $iI = (int)($iC / 4);
    $iK = $iC % 4;
    $iL = (32 + 2 * $iE + 2 * $iI - $iH - $iK) % 7;
    $iM = (int)(($iA + 11 * $iH + 22 * $iL) / 451);
    $iN = $iH + $iL - 7 * $iM + 114;
    $iMonth = (int)($iN / 31);
    $iDay = $iN % 31 + 1;
    return exCalendarDate($iYear, $iMonth, $iDay);
}

function exCalendarAddHoliday(&$aHolidays, $sDate, $sType, $sName, $sTime = "", $sLocation = "", $sBoxName = "") {
    if (!isset($aHolidays[$sDate])) {
        $aHolidays[$sDate] = array();
    }
    $aHolidays[$sDate][] = array(
        "type" => $sType,
        "name" => $sName,
        "time" => $sTime,
        "location" => $sLocation,
        "box_name" => $sBoxName
    );
}

function exCalendarGetHolidays($iYear) {
    $aHolidays = array();
    $aStateHolidays = array(
        "01-01" => "Den obnovy samostatného českého státu",
        "05-08" => "Den vítězství",
        "07-05" => "Den slovanských věrozvěstů Cyrila a Metoděje",
        "07-06" => "Den upálení mistra Jana Husa",
        "09-28" => "Den české státnosti",
        "10-28" => "Den vzniku samostatného československého státu",
        "11-17" => "Den boje za svobodu a demokracii a Mezinárodní den studentstva"
    );
    $aOtherHolidays = array(
        "01-01" => "Nový rok",
        "05-01" => "Svátek práce",
        "12-24" => "Štědrý den",
        "12-25" => "1. svátek vánoční",
        "12-26" => "2. svátek vánoční"
    );
    $oEasterSunday = exCalendarGetEasterSunday($iYear);
    foreach ($aStateHolidays as $sDay => $sName) {
        exCalendarAddHoliday($aHolidays, sprintf("%04d-%s", $iYear, $sDay), "state", $sName);
    }
    foreach ($aOtherHolidays as $sDay => $sName) {
        exCalendarAddHoliday($aHolidays, sprintf("%04d-%s", $iYear, $sDay), "other", $sName);
    }
    exCalendarAddHoliday($aHolidays, $oEasterSunday->modify("-2 days")->format("Y-m-d"), "moving", "Velký pátek");
    exCalendarAddHoliday($aHolidays, $oEasterSunday->modify("+1 day")->format("Y-m-d"), "moving", "Velikonoční pondělí");
    ksort($aHolidays);
    return $aHolidays;
}

function exCalendarFetchNameDays($oPdo, $iGroupId) {
    $oStatement = $oPdo->prepare("SELECT `date`, `name` FROM ex_name_days WHERE group_id = :group_id ORDER BY `date` ASC, id ASC");
    $oStatement->execute(array("group_id" => $iGroupId));
    return $oStatement->fetchAll();
}

function exCalendarFetchAllNameDays($oPdo) {
    $oStatement = $oPdo->prepare("SELECT group_id, `date`, `name` FROM ex_name_days ORDER BY group_id ASC, id ASC");
    $oStatement->execute();
    return $oStatement->fetchAll();
}

function exCalendarAddNameDays(&$aHolidays, $oPdo, $iYear, $iGroupId) {
    foreach (exCalendarFetchNameDays($oPdo, $iGroupId) as $aNameDay) {
        exCalendarAddHoliday($aHolidays, sprintf("%04d-%s", $iYear, $aNameDay["date"]), "name-day", $aNameDay["name"]);
    }
    ksort($aHolidays);
}

function exCalendarFetchPersonRows($oPdo) {
    $sSql = "SELECT p.subject_id, p.first_name, p.last_name, p.birth_date, subject_rows.subject_name, subject_rows.subject_sort_name FROM ex_persons AS p INNER JOIN ex_subjects AS s ON s.id = p.subject_id INNER JOIN (" . getSubjectNameSelectSql() . ") AS subject_rows ON subject_rows.subject_id = p.subject_id WHERE s.subject_type = :subject_type AND s.is_active = :is_active ORDER BY subject_rows.subject_sort_name COLLATE utf8mb4_czech_ci ASC, p.subject_id ASC";
    $oStatement = $oPdo->prepare($sSql);
    $oStatement->execute(array(
        "subject_type" => "person",
        "is_active" => 1
    ));
    return $oStatement->fetchAll();
}

function exCalendarGetPersonBoxName($aPerson) {
    return trim(preg_replace("/\s+/u", " ", trim((string)$aPerson["first_name"]) . " " . trim((string)$aPerson["last_name"])));
}

function exCalendarAddPersonBirthdays(&$aHolidays, $oPdo, $iYear) {
    foreach (exCalendarFetchPersonRows($oPdo) as $aPerson) {
        $sBirthDate = trim((string)$aPerson["birth_date"]);
        if (!preg_match("/^[0-9]{4}-([0-9]{2})-([0-9]{2})$/", $sBirthDate, $aMatches)) {
            continue;
        }
        $iMonth = (int)$aMatches[1];
        $iDay = (int)$aMatches[2];
        if (!checkdate($iMonth, $iDay, $iYear)) {
            continue;
        }
        exCalendarAddHoliday($aHolidays, exCalendarDateKey($iYear, $iMonth, $iDay), "person", $aPerson["subject_name"], "", "", exCalendarGetPersonBoxName($aPerson));
    }
    ksort($aHolidays);
}

function exCalendarAddPersonNameDays(&$aHolidays, $oPdo, $iYear) {
    $aNameDaysByName = array();
    foreach (exCalendarFetchAllNameDays($oPdo) as $aNameDay) {
        $sNameKey = mb_strtolower(trim((string)$aNameDay["name"]), "UTF-8");
        if (!isset($aNameDaysByName[$sNameKey])) {
            $aNameDaysByName[$sNameKey] = $aNameDay["date"];
        }
    }
    foreach (exCalendarFetchPersonRows($oPdo) as $aPerson) {
        $sNameKey = mb_strtolower(trim((string)$aPerson["first_name"]), "UTF-8");
        if ($sNameKey == "" || !isset($aNameDaysByName[$sNameKey])) {
            continue;
        }
        exCalendarAddHoliday($aHolidays, sprintf("%04d-%s", $iYear, $aNameDaysByName[$sNameKey]), "person", $aPerson["subject_name"], "", "", exCalendarGetPersonBoxName($aPerson));
    }
    ksort($aHolidays);
}

function exCalendarFetchExternalCalendarResponse($sUrl) {
    if (function_exists("curl_init")) {
        $oCurl = curl_init($sUrl);
        if (!$oCurl) {
            return array(
                "success" => false,
                "status_code" => 0,
                "body" => "",
                "error" => "Cannot initialize cURL."
            );
        }
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 20);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array("Accept: text/calendar,text/plain,*/*"));
        curl_setopt($oCurl, CURLOPT_USERAGENT, "eved-ex-calendar");
        @curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, true);
        @curl_setopt($oCurl, CURLOPT_MAXREDIRS, 5);
        $sBody = curl_exec($oCurl);
        $iStatusCode = (int)curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        $sError = curl_errno($oCurl) ? curl_error($oCurl) : "";
        return array(
            "success" => $sBody !== false && $iStatusCode >= 200 && $iStatusCode < 300,
            "status_code" => $iStatusCode,
            "body" => $sBody !== false ? $sBody : "",
            "error" => $sError
        );
    }
    $aContext = array(
        "http" => array(
            "method" => "GET",
            "header" => "Accept: text/calendar,text/plain,*/*\r\nUser-Agent: eved-ex-calendar\r\n",
            "timeout" => 20,
            "ignore_errors" => true
        )
    );
    $sBody = @file_get_contents($sUrl, false, stream_context_create($aContext));
    $aResponseHeaders = http_get_last_response_headers();
    if (!$aResponseHeaders) {
        $aResponseHeaders = array();
    }
    $iStatusCode = getHttpStatusCode($aResponseHeaders);
    $aError = error_get_last();
    return array(
        "success" => $sBody !== false && $iStatusCode >= 200 && $iStatusCode < 300,
        "status_code" => $iStatusCode,
        "body" => $sBody !== false ? $sBody : "",
        "error" => $sBody !== false ? "" : (isset($aError["message"]) ? (string)$aError["message"] : "HTTP request failed.")
    );
}

function exCalendarUnfoldICalContent($sContent) {
    $aLines = array();
    $aRawLines = explode("\n", str_replace(array("\r\n", "\r"), "\n", $sContent));
    foreach ($aRawLines as $sLine) {
        if ($sLine != "" && preg_match("/^[ \t]/", $sLine) && $aLines) {
            $aLines[count($aLines) - 1] .= substr($sLine, 1);
        } else {
            $aLines[] = $sLine;
        }
    }
    return $aLines;
}

function exCalendarParseICalLine($sLine) {
    $iSeparatorPosition = strpos($sLine, ":");
    if ($iSeparatorPosition === false) {
        return null;
    }
    $aNameParts = explode(";", substr($sLine, 0, $iSeparatorPosition));
    $sName = strtoupper(array_shift($aNameParts));
    $aParams = array();
    foreach ($aNameParts as $sNamePart) {
        $iParamSeparatorPosition = strpos($sNamePart, "=");
        if ($iParamSeparatorPosition !== false) {
            $aParams[strtoupper(substr($sNamePart, 0, $iParamSeparatorPosition))] = trim(substr($sNamePart, $iParamSeparatorPosition + 1), "\"");
        }
    }
    return array(
        "name" => $sName,
        "params" => $aParams,
        "value" => substr($sLine, $iSeparatorPosition + 1)
    );
}

function exCalendarDecodeICalText($sValue) {
    $sValue = str_replace(array('\n', '\N'), "\n", $sValue);
    $sValue = str_replace(array('\,', '\;'), array(",", ";"), $sValue);
    return str_replace('\\\\', '\\', $sValue);
}

function exCalendarParseICalDateTimeValue($sValue, $aParams) {
    $sValue = trim($sValue);
    if ($sValue == "") {
        return null;
    }
    $oPragueTimeZone = new DateTimeZone("Europe/Prague");
    if ((isset($aParams["VALUE"]) && strtoupper($aParams["VALUE"]) == "DATE") || preg_match("/^[0-9]{8}$/", $sValue)) {
        $oDate = DateTimeImmutable::createFromFormat("!Ymd", $sValue, $oPragueTimeZone);
        if (!$oDate || $oDate->format("Ymd") != $sValue) {
            return null;
        }
        return array(
            "date" => $oDate->format("Y-m-d"),
            "time" => ""
        );
    }
    $oSourceTimeZone = $oPragueTimeZone;
    if (substr($sValue, -1) == "Z") {
        $oSourceTimeZone = new DateTimeZone("UTC");
        $sValue = substr($sValue, 0, -1);
    } elseif (isset($aParams["TZID"]) && trim((string)$aParams["TZID"]) != "") {
        try {
            $oSourceTimeZone = new DateTimeZone($aParams["TZID"]);
        } catch (Exception $oException) {
            return null;
        }
    }
    if (preg_match("/^[0-9]{8}T[0-9]{6}$/", $sValue)) {
        $oDateTime = DateTimeImmutable::createFromFormat("!Ymd\\THis", $sValue, $oSourceTimeZone);
    } elseif (preg_match("/^[0-9]{8}T[0-9]{4}$/", $sValue)) {
        $oDateTime = DateTimeImmutable::createFromFormat("!Ymd\\THi", $sValue, $oSourceTimeZone);
    } else {
        return null;
    }
    if (!$oDateTime) {
        return null;
    }
    $oPragueDateTime = $oDateTime->setTimezone($oPragueTimeZone);
    return array(
        "date" => $oPragueDateTime->format("Y-m-d"),
        "time" => $oPragueDateTime->format("H:i")
    );
}

function exCalendarParseICalEvents($sContent) {
    $aEvents = array();
    $aEvent = null;
    foreach (exCalendarUnfoldICalContent($sContent) as $sLine) {
        $aLine = exCalendarParseICalLine($sLine);
        if ($aLine && $aLine["name"] == "BEGIN" && strtoupper($aLine["value"]) == "VEVENT") {
            $aEvent = array(
                "_raw_lines" => array($sLine)
            );
            continue;
        }
        if ($aEvent !== null) {
            $aEvent["_raw_lines"][] = $sLine;
        }
        if (!$aLine) {
            continue;
        }
        if ($aLine["name"] == "END" && strtoupper($aLine["value"]) == "VEVENT") {
            if ($aEvent !== null) {
                $aEvent["_raw_event"] = implode("\r\n", $aEvent["_raw_lines"]) . "\r\n";
                $aEvents[] = $aEvent;
            }
            $aEvent = null;
            continue;
        }
        if ($aEvent !== null && in_array($aLine["name"], array("CREATED", "DESCRIPTION", "DTEND", "DTSTAMP", "DTSTART", "LAST-MODIFIED", "LOCATION", "RECURRENCE-ID", "SEQUENCE", "STATUS", "SUMMARY", "TRANSP", "UID"), true)) {
            $aEvent[$aLine["name"]] = array(
                "params" => $aLine["params"],
                "value" => $aLine["value"]
            );
        }
    }
    return $aEvents;
}

function exCalendarGetICalEventText($aEvent, $sName) {
    return isset($aEvent[$sName]) ? trim(exCalendarDecodeICalText($aEvent[$sName]["value"])) : "";
}

function exCalendarGetICalEventValue($aEvent, $sName) {
    return isset($aEvent[$sName]) ? trim((string)$aEvent[$sName]["value"]) : "";
}

function exCalendarGetICalEventTimezone($aEvent, $sName) {
    return isset($aEvent[$sName]) && isset($aEvent[$sName]["params"]["TZID"]) ? trim((string)$aEvent[$sName]["params"]["TZID"]) : "";
}

function exCalendarBuildExternalCalendarEventRows($sContent, &$sError) {
    $aRows = array();
    $iEventOrder = 0;
    if ($sContent == "") {
        $sError = "ICS response is empty.";
        return false;
    }
    foreach (exCalendarParseICalEvents($sContent) as $aEvent) {
        $iEventOrder += 1;
        $aStart = isset($aEvent["DTSTART"]) ? exCalendarParseICalDateTimeValue($aEvent["DTSTART"]["value"], $aEvent["DTSTART"]["params"]) : null;
        $aEnd = isset($aEvent["DTEND"]) ? exCalendarParseICalDateTimeValue($aEvent["DTEND"]["value"], $aEvent["DTEND"]["params"]) : null;
        $aRows[] = array(
            "event_order" => $iEventOrder,
            "uid" => exCalendarGetICalEventValue($aEvent, "UID"),
            "recurrence_id_raw" => exCalendarGetICalEventValue($aEvent, "RECURRENCE-ID"),
            "status" => exCalendarGetICalEventValue($aEvent, "STATUS"),
            "summary" => exCalendarGetICalEventText($aEvent, "SUMMARY"),
            "description" => exCalendarGetICalEventText($aEvent, "DESCRIPTION"),
            "location" => exCalendarGetICalEventText($aEvent, "LOCATION"),
            "start_date" => $aStart ? $aStart["date"] : null,
            "start_time" => $aStart && $aStart["time"] != "" ? $aStart["time"] : null,
            "start_raw" => exCalendarGetICalEventValue($aEvent, "DTSTART"),
            "start_timezone" => exCalendarGetICalEventTimezone($aEvent, "DTSTART"),
            "end_date" => $aEnd ? $aEnd["date"] : null,
            "end_time" => $aEnd && $aEnd["time"] != "" ? $aEnd["time"] : null,
            "end_raw" => exCalendarGetICalEventValue($aEvent, "DTEND"),
            "end_timezone" => exCalendarGetICalEventTimezone($aEvent, "DTEND"),
            "dtstamp_raw" => exCalendarGetICalEventValue($aEvent, "DTSTAMP"),
            "created_raw" => exCalendarGetICalEventValue($aEvent, "CREATED"),
            "last_modified_raw" => exCalendarGetICalEventValue($aEvent, "LAST-MODIFIED"),
            "sequence_no" => exCalendarGetICalEventValue($aEvent, "SEQUENCE") != "" ? (int)exCalendarGetICalEventValue($aEvent, "SEQUENCE") : null,
            "transp" => exCalendarGetICalEventValue($aEvent, "TRANSP"),
            "raw_event" => isset($aEvent["_raw_event"]) ? $aEvent["_raw_event"] : ""
        );
    }
    if (!$aRows) {
        $sError = "ICS response does not contain events.";
        return false;
    }
    return $aRows;
}

function exCalendarGetExternalCalendarEventSourceKey($aRow) {
    $sUid = trim((string)$aRow["uid"]);
    if ($sUid != "") {
        return hash("sha256", "uid\n" . $sUid . "\n" . trim((string)$aRow["recurrence_id_raw"]));
    }
    return hash("sha256", "raw\n" . $aRow["raw_event"]);
}

function reserveExternalCalendarFetchAttempt($oPdo, $iCalendarFetchId) {
    $oPdo->beginTransaction();
    try {
        $oStatement = $oPdo->prepare("SELECT id, status, last_attempt_at FROM ex_calendar_fetches WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iCalendarFetchId));
        $aFetch = $oStatement->fetch();
        if (!$aFetch) {
            $oPdo->commit();
            return false;
        }
        if ($aFetch && trim((string)$aFetch["last_attempt_at"]) != "") {
            $iLastAttempt = strtotime((string)$aFetch["last_attempt_at"]);
            if ($iLastAttempt !== false && $iLastAttempt > time() - 14400) {
                $oPdo->commit();
                return false;
            }
        }
        $oStatement = $oPdo->prepare("UPDATE ex_calendar_fetches SET status = 'running', last_attempt_at = current_timestamp(6), attempt_count = attempt_count + 1, http_status_code = NULL, error_message = NULL WHERE id = :id");
        $oStatement->execute(array("id" => (int)$aFetch["id"]));
        $oPdo->commit();
        return true;
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        throw $oException;
    }
}

function recordExternalCalendarFetchError($oPdo, $iCalendarFetchId, $iHttpStatusCode, $sErrorMessage) {
    $sErrorMessage = substr($sErrorMessage, 0, 1000);
    $oStatement = $oPdo->prepare("UPDATE ex_calendar_fetches SET status = 'error', http_status_code = :http_status_code, error_message = :error_message WHERE id = :id");
    $oStatement->execute(array(
        "http_status_code" => $iHttpStatusCode > 0 ? $iHttpStatusCode : null,
        "error_message" => $sErrorMessage,
        "id" => $iCalendarFetchId
    ));
}

function saveExternalCalendarEvents($oPdo, $iCalendarFetchId, $sCalendarUrlHash, $aRows, $sRawContent, $iHttpStatusCode) {
    $oPdo->beginTransaction();
    try {
        $oStatement = $oPdo->prepare("SELECT id FROM ex_calendar_fetches WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iCalendarFetchId));
        $iFetchId = (int)$oStatement->fetchColumn();
        $oStatement = $oPdo->prepare("UPDATE ex_calendar_events SET is_active = 0 WHERE calendar_url_hash = :calendar_url_hash");
        $oStatement->execute(array("calendar_url_hash" => $sCalendarUrlHash));
        $oSelectEventStatement = $oPdo->prepare("SELECT id FROM ex_calendar_events WHERE calendar_url_hash = :calendar_url_hash AND source_event_key = :source_event_key ORDER BY id ASC LIMIT 1 FOR UPDATE");
        $oUpdateEventStatement = $oPdo->prepare("UPDATE ex_calendar_events SET last_fetch_id = :last_fetch_id, event_order = :event_order, uid = :uid, recurrence_id_raw = :recurrence_id_raw, status = :status, summary = :summary, description = :description, location = :location, start_date = :start_date, start_time = :start_time, start_raw = :start_raw, start_timezone = :start_timezone, end_date = :end_date, end_time = :end_time, end_raw = :end_raw, end_timezone = :end_timezone, dtstamp_raw = :dtstamp_raw, created_raw = :created_raw, last_modified_raw = :last_modified_raw, sequence_no = :sequence_no, transp = :transp, raw_event = :raw_event, is_active = 1, seen_count = seen_count + 1, last_seen_at = current_timestamp(6) WHERE id = :id");
        $oInsertEventStatement = $oPdo->prepare("INSERT INTO ex_calendar_events (calendar_url_hash, source_event_key, first_fetch_id, last_fetch_id, event_order, uid, recurrence_id_raw, status, summary, description, location, start_date, start_time, start_raw, start_timezone, end_date, end_time, end_raw, end_timezone, dtstamp_raw, created_raw, last_modified_raw, sequence_no, transp, raw_event, is_active, seen_count, first_seen_at, last_seen_at) VALUES (:calendar_url_hash, :source_event_key, :first_fetch_id, :last_fetch_id, :event_order, :uid, :recurrence_id_raw, :status, :summary, :description, :location, :start_date, :start_time, :start_raw, :start_timezone, :end_date, :end_time, :end_raw, :end_timezone, :dtstamp_raw, :created_raw, :last_modified_raw, :sequence_no, :transp, :raw_event, 1, 1, current_timestamp(6), current_timestamp(6)) ON DUPLICATE KEY UPDATE last_fetch_id = VALUES(last_fetch_id), event_order = VALUES(event_order), uid = VALUES(uid), recurrence_id_raw = VALUES(recurrence_id_raw), status = VALUES(status), summary = VALUES(summary), description = VALUES(description), location = VALUES(location), start_date = VALUES(start_date), start_time = VALUES(start_time), start_raw = VALUES(start_raw), start_timezone = VALUES(start_timezone), end_date = VALUES(end_date), end_time = VALUES(end_time), end_raw = VALUES(end_raw), end_timezone = VALUES(end_timezone), dtstamp_raw = VALUES(dtstamp_raw), created_raw = VALUES(created_raw), last_modified_raw = VALUES(last_modified_raw), sequence_no = VALUES(sequence_no), transp = VALUES(transp), raw_event = VALUES(raw_event), is_active = 1, seen_count = seen_count + 1, last_seen_at = current_timestamp(6)");
        foreach ($aRows as $aRow) {
            $sSourceEventKey = exCalendarGetExternalCalendarEventSourceKey($aRow);
            $aEventParameters = array(
                "calendar_url_hash" => $sCalendarUrlHash,
                "source_event_key" => $sSourceEventKey,
                "first_fetch_id" => $iFetchId,
                "last_fetch_id" => $iFetchId,
                "event_order" => (int)$aRow["event_order"],
                "uid" => $aRow["uid"] != "" ? $aRow["uid"] : null,
                "recurrence_id_raw" => $aRow["recurrence_id_raw"] != "" ? $aRow["recurrence_id_raw"] : null,
                "status" => $aRow["status"] != "" ? $aRow["status"] : null,
                "summary" => $aRow["summary"] != "" ? $aRow["summary"] : null,
                "description" => $aRow["description"] != "" ? $aRow["description"] : null,
                "location" => $aRow["location"] != "" ? $aRow["location"] : null,
                "start_date" => $aRow["start_date"],
                "start_time" => $aRow["start_time"],
                "start_raw" => $aRow["start_raw"] != "" ? $aRow["start_raw"] : null,
                "start_timezone" => $aRow["start_timezone"] != "" ? $aRow["start_timezone"] : null,
                "end_date" => $aRow["end_date"],
                "end_time" => $aRow["end_time"],
                "end_raw" => $aRow["end_raw"] != "" ? $aRow["end_raw"] : null,
                "end_timezone" => $aRow["end_timezone"] != "" ? $aRow["end_timezone"] : null,
                "dtstamp_raw" => $aRow["dtstamp_raw"] != "" ? $aRow["dtstamp_raw"] : null,
                "created_raw" => $aRow["created_raw"] != "" ? $aRow["created_raw"] : null,
                "last_modified_raw" => $aRow["last_modified_raw"] != "" ? $aRow["last_modified_raw"] : null,
                "sequence_no" => $aRow["sequence_no"],
                "transp" => $aRow["transp"] != "" ? $aRow["transp"] : null,
                "raw_event" => $aRow["raw_event"]
            );
            $oSelectEventStatement->execute(array(
                "calendar_url_hash" => $sCalendarUrlHash,
                "source_event_key" => $sSourceEventKey
            ));
            $iEventId = (int)$oSelectEventStatement->fetchColumn();
            if ($iEventId > 0) {
                unset($aEventParameters["calendar_url_hash"], $aEventParameters["source_event_key"], $aEventParameters["first_fetch_id"]);
                $aEventParameters["id"] = $iEventId;
                $oUpdateEventStatement->execute($aEventParameters);
            } else {
                $oInsertEventStatement->execute($aEventParameters);
            }
        }
        $oStatement = $oPdo->prepare("UPDATE ex_calendar_fetches SET status = 'success', succeeded_at = current_timestamp(6), http_status_code = :http_status_code, events_count = :events_count, raw_content = :raw_content, error_message = NULL WHERE id = :id");
        $oStatement->execute(array(
            "http_status_code" => $iHttpStatusCode > 0 ? $iHttpStatusCode : null,
            "events_count" => count($aRows),
            "raw_content" => $sRawContent,
            "id" => $iFetchId
        ));
        $oPdo->commit();
    } catch (Exception $oException) {
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        throw $oException;
    }
}

function exCalendarAddExternalCalendarDatabaseEvents(&$aHolidays, $oPdo, $iYear, $sCalendarUrlHash) {
    $aEvents = array();
    $sFromDate = sprintf("%04d-01-01", (int)$iYear);
    $sToDate = sprintf("%04d-12-31", (int)$iYear);
    $oStatement = $oPdo->prepare("SELECT id, event_order, start_date, start_time, summary, location, status FROM ex_calendar_events WHERE calendar_url_hash = :calendar_url_hash AND is_active = 1 AND start_date BETWEEN :from_date AND :to_date ORDER BY start_date ASC, start_time ASC, event_order ASC, id ASC");
    $oStatement->execute(array(
        "calendar_url_hash" => $sCalendarUrlHash,
        "from_date" => $sFromDate,
        "to_date" => $sToDate
    ));
    while ($aRow = $oStatement->fetch()) {
        if (strtoupper(trim((string)$aRow["status"])) == "CANCELLED") {
            continue;
        }
        $sDate = trim((string)$aRow["start_date"]);
        $sName = trim((string)$aRow["summary"]);
        if ($sDate == "" || $sName == "") {
            continue;
        }
        $sTime = substr(trim((string)$aRow["start_time"]), 0, 5);
        $sLocation = trim((string)$aRow["location"]);
        $sEventKey = $sDate . "\n" . $sName;
        if (!isset($aEvents[$sEventKey]) || (int)$aEvents[$sEventKey]["event_order"] < (int)$aRow["event_order"] || ((int)$aEvents[$sEventKey]["event_order"] == (int)$aRow["event_order"] && (int)$aEvents[$sEventKey]["id"] < (int)$aRow["id"])) {
            $aEvents[$sEventKey] = array(
                "id" => (int)$aRow["id"],
                "event_order" => (int)$aRow["event_order"],
                "date" => $sDate,
                "time" => $sTime,
                "name" => $sName,
                "location" => $sLocation
            );
        }
    }
    $aEvents = array_values($aEvents);
    if ($aEvents) {
        $aSortDates = array();
        $aSortTimes = array();
        $aSortOrders = array();
        $aSortIds = array();
        foreach ($aEvents as $aEvent) {
            $aSortDates[] = $aEvent["date"];
            $aSortTimes[] = $aEvent["time"];
            $aSortOrders[] = $aEvent["event_order"];
            $aSortIds[] = $aEvent["id"];
        }
        array_multisort($aSortDates, SORT_ASC, SORT_STRING, $aSortTimes, SORT_ASC, SORT_STRING, $aSortOrders, SORT_ASC, SORT_NUMERIC, $aSortIds, SORT_ASC, SORT_NUMERIC, $aEvents);
    }
    foreach ($aEvents as $aEvent) {
        exCalendarAddHoliday($aHolidays, $aEvent["date"], "external", $aEvent["name"], $aEvent["time"], $aEvent["location"]);
    }
    ksort($aHolidays);
}

function exCalendarGetMonthNames() {
    return array(
        1 => "leden",
        2 => "únor",
        3 => "březen",
        4 => "duben",
        5 => "květen",
        6 => "červen",
        7 => "červenec",
        8 => "srpen",
        9 => "září",
        10 => "říjen",
        11 => "listopad",
        12 => "prosinec"
    );
}

function exCalendarGetDayNames() {
    return array("Po", "Út", "St", "Čt", "Pá", "So", "Ne");
}

function exCalendarGetHolidayTypes($aHolidayItems) {
    $aDayTypes = array();
    $aTypes = array("state", "moving", "other", "external");
    foreach ($aTypes as $sType) {
        foreach ($aHolidayItems as $aHolidayItem) {
            if ($aHolidayItem["type"] == $sType) {
                $aDayTypes[] = $sType;
                break;
            }
        }
    }
    return $aDayTypes;
}

function exCalendarGetHolidayClass($aHolidayItems) {
    $aDayTypes = exCalendarGetHolidayTypes($aHolidayItems);
    if ($aDayTypes) {
        return "holiday-day-" . $aDayTypes[0];
    }
    return "";
}

function exCalendarGetHolidayTypeBackgroundColors() {
    return array(
        "state" => "var(--holiday-state-background, #F8D4CC)",
        "moving" => "var(--holiday-moving-background, #D6EEF5)",
        "other" => "var(--holiday-other-background, #FFF1BF)",
        "external" => "var(--holiday-external-background, #DDE8FF)"
    );
}

function exCalendarGetHolidayBackgroundImage($aHolidayItems) {
    $aDayTypes = exCalendarGetHolidayTypes($aHolidayItems);
    if (count($aDayTypes) < 2) {
        return "";
    }
    $aColors = exCalendarGetHolidayTypeBackgroundColors();
    $aStops = array();
    $iStopSize = 8;
    $iStopPosition = 0;
    foreach ($aDayTypes as $sType) {
        if (!isset($aColors[$sType])) {
            continue;
        }
        $iNextStopPosition = $iStopPosition + $iStopSize;
        $aStops[] = $aColors[$sType] . " " . $iStopPosition . "px";
        $aStops[] = $aColors[$sType] . " " . $iNextStopPosition . "px";
        $iStopPosition = $iNextStopPosition;
    }
    if (count($aStops) < 4) {
        return "";
    }
    return "repeating-linear-gradient(135deg, " . implode(", ", $aStops) . ")";
}

function exCalendarUcfirst($sText) {
    if ($sText == "" || !preg_match("/^(.)(.*)$/us", $sText, $aMatches)) {
        return $sText;
    }
    $sFirst = strtoupper($aMatches[1]);
    $sFirst = strtr($sFirst, array(
        "á" => "Á",
        "č" => "Č",
        "ď" => "Ď",
        "é" => "É",
        "ě" => "Ě",
        "í" => "Í",
        "ň" => "Ň",
        "ó" => "Ó",
        "ř" => "Ř",
        "š" => "Š",
        "ť" => "Ť",
        "ú" => "Ú",
        "ů" => "Ů",
        "ý" => "Ý",
        "ž" => "Ž"
    ));
    return $sFirst . $aMatches[2];
}

function exCalendarBoxName($sName) {
    $sName = preg_replace("/[\x{FE00}-\x{FE0F}\x{200D}\x{20E3}\x{E0100}-\x{E01EF}]/u", "", $sName);
    $sName = preg_replace("/[\x{1F000}-\x{1FAFF}\x{1FC00}-\x{1FFFF}]/u", "", $sName);
    $sName = trim(preg_replace("/\s+/u", " ", $sName));
    return exCalendarUcfirst($sName);
}

function exCalendarGetHolidayLabelLength($sName) {
    preg_match_all("/./u", $sName, $aMatches);
    return count($aMatches[0]);
}

function exCalendarGetHolidayBoxName($aHolidayItem) {
    $sName = isset($aHolidayItem["box_name"]) && $aHolidayItem["box_name"] != "" ? $aHolidayItem["box_name"] : $aHolidayItem["name"];
    return exCalendarBoxName($sName);
}

function exCalendarRenderHolidayLabels($aHolidayItems, $sDate) {
    $sHtml = "";
    $aHolidayItems = array_slice($aHolidayItems, 0, 3);
    if (count($aHolidayItems) == 2) {
        $iLongLabelIndex = exCalendarGetHolidayLabelLength(exCalendarGetHolidayBoxName($aHolidayItems[1])) > exCalendarGetHolidayLabelLength(exCalendarGetHolidayBoxName($aHolidayItems[0])) ? 1 : 0;
        foreach ($aHolidayItems as $iIndex => $aHolidayItem) {
            $sClass = $iIndex == $iLongLabelIndex ? "holiday-day-label" : "holiday-day-label holiday-day-label-single";
            $sHtml .= "<span class=\"" . $sClass . "\">" . html(exCalendarGetHolidayBoxName($aHolidayItem)) . "</span>";
        }
        return $sHtml;
    }
    if (count($aHolidayItems) > 2) {
        foreach ($aHolidayItems as $aHolidayItem) {
            $sHtml .= "<span class=\"holiday-day-label holiday-day-label-single\">" . html(exCalendarGetHolidayBoxName($aHolidayItem)) . "</span>";
        }
        return $sHtml;
    }
    foreach ($aHolidayItems as $aHolidayItem) {
        $sHtml .= "<span class=\"holiday-day-label holiday-day-label-triple\">" . html(exCalendarGetHolidayBoxName($aHolidayItem)) . "</span>";
    }
    return $sHtml;
}

function exCalendarGetHolidayTooltip($aHolidayItems) {
    $aLines = array();
    foreach ($aHolidayItems as $aHolidayItem) {
        $sName = $aHolidayItem["name"];
        $sLine = isset($aHolidayItem["time"]) && $aHolidayItem["time"] != "" ? $aHolidayItem["time"] . " " . $sName : $sName;
        if (isset($aHolidayItem["location"]) && $aHolidayItem["location"] != "") {
            $sLine .= " (" . $aHolidayItem["location"] . ")";
        }
        $aLines[] = $sLine;
    }
    return implode("\n", $aLines);
}

function exCalendarGetHolidayTooltipTitle($iDay, $sMonthName, $iYear, $aHolidayItems) {
    return (int)$iDay . ". " . $sMonthName . " " . (int)$iYear;
}

function exCalendarRenderMonth($iYear, $iMonth, $aHolidays) {
    $aMonthNames = exCalendarGetMonthNames();
    $aDayNames = exCalendarGetDayNames();
    $oFirstDay = exCalendarDate($iYear, $iMonth, 1);
    $iFirstWeekday = (int)$oFirstDay->format("N");
    $iDaysInMonth = (int)$oFirstDay->modify("last day of this month")->format("j");
    $sToday = date("Y-m-d");
    echo "    <section class=\"holiday-month\">\n",
        "      <h2>" . html($aMonthNames[$iMonth]) . "</h2>\n",
        "      <div class=\"holiday-month-grid\">\n";
    foreach ($aDayNames as $sDayName) {
        echo "        <div class=\"holiday-weekday\">" . html($sDayName) . "</div>\n";
    }
    for ($iBlank = 1; $iBlank < $iFirstWeekday; $iBlank += 1) {
        echo "        <div class=\"holiday-empty\"></div>\n";
    }
    for ($iDay = 1; $iDay <= $iDaysInMonth; $iDay += 1) {
        $sDate = exCalendarDateKey($iYear, $iMonth, $iDay);
        $aHolidayItems = isset($aHolidays[$sDate]) ? $aHolidays[$sDate] : array();
        $oDate = exCalendarDate($iYear, $iMonth, $iDay);
        $sClass = "holiday-day";
        if ((int)$oDate->format("N") >= 6) {
            $sClass .= " holiday-day-weekend";
        }
        if ($sDate == $sToday) {
            $sClass .= " holiday-day-today";
        }
        $sHolidayClass = exCalendarGetHolidayClass($aHolidayItems);
        if ($sHolidayClass != "") {
            $sClass .= " " . $sHolidayClass;
        }
        $sBackgroundImage = exCalendarGetHolidayBackgroundImage($aHolidayItems);
        $sBackgroundAttribute = $sBackgroundImage != "" ? " data-calendar-background-image=\"" . html($sBackgroundImage) . "\"" : "";
        $sTooltipAttribute = " data-calendar-tooltip-title=\"" . htmlTooltip(exCalendarGetHolidayTooltipTitle($iDay, $aMonthNames[$iMonth], $iYear, $aHolidayItems)) . "\"";
        if ($aHolidayItems) {
            $sTooltipAttribute .= " data-calendar-tooltip=\"" . str_replace("\n", "&#10;", htmlTooltip(exCalendarGetHolidayTooltip($aHolidayItems))) . "\"";
        }
        echo "        <div class=\"" . html($sClass) . "\"" . $sBackgroundAttribute . $sTooltipAttribute . ">",
            "<span class=\"holiday-day-number\">" . $iDay . "</span>",
            exCalendarRenderHolidayLabels($aHolidayItems, $sDate),
            "</div>\n";
    }
    echo "      </div>\n",
        "    </section>\n";
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
    return ($sPrefix != "" ? $sPrefix . " " : "") . ((int)$iAge == 1 ? "1 year" : (int)$iAge . " years");
}

function renderSubjectDateValue($mDate, $sAgeLabel = "") {
    $sHtml = htmlValue($mDate);
    if ($sAgeLabel != "") {
        $sHtml .= "<span class=\"da\">" . html($sAgeLabel) . "</span>";
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
    $sSql = "SELECT s.id AS subject_id, s.subject_type, COALESCE(IF(s.subject_type = 'person', " . $sPersonDisplayName . ", NULL), NULLIF(subn.name, ''), n.primary_nickname, c.primary_contact, 'Unnamed subject') AS subject_name, COALESCE(IF(s.subject_type = 'person', " . $sPersonSortName . ", NULL), NULLIF(subn.name, ''), n.primary_nickname, c.primary_contact, 'Unnamed subject') AS subject_sort_name, IF(s.subject_type = 'person', " . $sPersonDisplayName . ", NULL) AS person_display_name, subn.name AS subject_name_value, n.primary_nickname, c.primary_contact, c.primary_contact_type, s.is_active, s.created_at, s.updated_at, p.title_before, p.first_name, p.middle_name, p.last_name, p.title_after, p.birth_name, p.birth_number, p.birth_date, p.death_date, p.birthday_served_at, p.inter_served_at, c.contacts, a.addresses, n.nicknames, g.group_names, sn.notes FROM ex_subjects AS s
        LEFT JOIN ex_persons AS p ON p.subject_id = s.id
        LEFT JOIN ex_subject_names AS subn ON subn.subject_id = s.id
        LEFT JOIN (SELECT sc.subject_id, GROUP_CONCAT(CONCAT(" . $sContactTypeNameSql . ", ': ', c.contact_value, IF(sc.note IS NULL OR sc.note = '', '', CONCAT(' (', sc.note, ')'))) ORDER BY sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC SEPARATOR '\n') AS contacts, SUBSTRING_INDEX(GROUP_CONCAT(c.contact_value ORDER BY sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC SEPARATOR '\n'), '\n', 1) AS primary_contact, SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(ct.contact_type, '') ORDER BY sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC SEPARATOR '\n'), '\n', 1) AS primary_contact_type FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id" . $sContactTypeJoinSql . " GROUP BY sc.subject_id) AS c ON c.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(NULLIF(CONCAT_WS(', ', NULLIF(TRIM(CONCAT_WS(' ', NULLIF(street_name, ''), NULLIF(CONCAT_WS('/', NULLIF(house_number, ''), NULLIF(orientation_number, '')), ''))), ''), NULLIF(city, ''), NULLIF(postal_code, ''), NULLIF(country, '')), '') ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n') AS addresses FROM ex_subject_addresses GROUP BY subject_id) AS a ON a.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(CONCAT(nickname, IF(context IS NULL OR context = '', '', CONCAT(' [', context, ']')), IF(note IS NULL OR note = '', '', CONCAT(' (', note, ')'))) ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n') AS nicknames, SUBSTRING_INDEX(GROUP_CONCAT(nickname ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n'), '\n', 1) AS primary_nickname FROM ex_subject_nicknames GROUP BY subject_id) AS n ON n.subject_id = s.id
        LEFT JOIN (SELECT sg.subject_id, GROUP_CONCAT(g.name ORDER BY g.`order` ASC, g.id ASC SEPARATOR '\n') AS group_names FROM ex_group_subject AS sg INNER JOIN ex_groups AS g ON g.id = sg.group_id GROUP BY sg.subject_id) AS g ON g.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(note_text ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n') AS notes FROM ex_subject_notes GROUP BY subject_id) AS sn ON sn.subject_id = s.id";
    if ($iSubjectId > 0) {
        $sSql .= " WHERE s.id = :subject_id";
    }
    if (is_array($aFilterSql) && !empty($aFilterSql["sql"])) {
        $sSql .= " HAVING " . $aFilterSql["sql"];
    }
    $sSql .= " ORDER BY subject_sort_name COLLATE utf8mb4_czech_ci ASC, s.subject_type ASC";
    $oStatement = $oPdo->prepare($sSql);
    $aParams = is_array($aFilterSql) && isset($aFilterSql["params"]) && is_array($aFilterSql["params"]) ? $aFilterSql["params"] : array();
    if ($iSubjectId > 0) {
        $aParams["subject_id"] = $iSubjectId;
        $oStatement->execute($aParams);
    } else {
        $oStatement->execute($aParams);
    }
    $aRows = $oStatement->fetchAll();
    foreach ($aRows as $iRow => $aRow) {
        if (trim((string)$aRow["person_display_name"]) == "" && trim((string)$aRow["subject_name_value"]) == "" && trim((string)$aRow["primary_nickname"]) == "" && trim((string)$aRow["primary_contact"]) != "") {
            $aRows[$iRow]["subject_name"] = contactDisplayValue($aRow["primary_contact_type"], $aRow["primary_contact"]);
            $aRows[$iRow]["subject_sort_name"] = $aRows[$iRow]["subject_name"];
        }
    }
    return $aRows;
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
    while ($aContact = $oStatement->fetch()) {
        $iCurrentSubjectId = (int)$aContact["subject_id"];
        if (!isset($aContacts[$iCurrentSubjectId])) {
            $aContacts[$iCurrentSubjectId] = array();
        }
        $aContacts[$iCurrentSubjectId][] = $aContact;
    }
    return $aContacts;
}

function subjectContactData($aContact) {
    $sContactType = isset($aContact["contact_type"]) ? $aContact["contact_type"] : "";
    return array(
        "subject_contact_id" => (int)$aContact["subject_contact_id"],
        "subject_id" => (int)$aContact["subject_id"],
        "contact_id" => (int)$aContact["contact_id"],
        "contact_type_id" => (int)$aContact["contact_type_id"],
        "contact_type" => $sContactType,
        "contact_value" => contactDisplayValue($sContactType, $aContact["contact_value"]),
        "note" => (string)$aContact["note"],
        "is_primary" => (int)$aContact["is_primary"],
        "is_active" => (int)$aContact["is_active"]
    );
}

function handleSubjectContactDataRequest($oPdo) {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["action"]) || $_POST["action"] != "get_subject_contact") {
        return;
    }
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iSubjectContactId = isset($_POST["subject_contact_id"]) ? (int)$_POST["subject_contact_id"] : 0;
    if ($iSubjectId < 1 || $iSubjectContactId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid contact request."), 400);
    }
    try {
        $aContacts = fetchSubjectContacts($oPdo, $iSubjectId);
        $aSubjectContacts = isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array();
        foreach ($aSubjectContacts as $aContact) {
            if ((int)$aContact["subject_contact_id"] == $iSubjectContactId) {
                sendJsonAndExit(array("success" => true, "contact" => subjectContactData($aContact)));
            }
        }
        sendJsonAndExit(array("success" => false, "message" => "Contact was not found."), 404);
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
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
    while ($aNickname = $oStatement->fetch()) {
        $iCurrentSubjectId = (int)$aNickname["subject_id"];
        if (!isset($aNicknames[$iCurrentSubjectId])) {
            $aNicknames[$iCurrentSubjectId] = array();
        }
        $aNicknames[$iCurrentSubjectId][] = $aNickname;
    }
    return $aNicknames;
}

function subjectNicknameData($aNickname) {
    return array(
        "id" => (int)$aNickname["id"],
        "subject_id" => (int)$aNickname["subject_id"],
        "nickname" => $aNickname["nickname"],
        "context" => trim((string)$aNickname["context"]),
        "note" => trim((string)$aNickname["note"]),
        "is_primary" => (int)$aNickname["is_primary"],
        "is_active" => (int)$aNickname["is_active"]
    );
}

function handleSubjectNicknameDataRequest($oPdo) {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["action"]) || $_POST["action"] != "get_subject_nickname") {
        return;
    }
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $iNicknameId = isset($_POST["nickname_id"]) ? (int)$_POST["nickname_id"] : 0;
    if ($iSubjectId < 1 || $iNicknameId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid nickname request."), 400);
    }
    try {
        $aNicknames = fetchSubjectNicknames($oPdo, $iSubjectId);
        $aSubjectNicknames = isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array();
        foreach ($aSubjectNicknames as $aNickname) {
            if ((int)$aNickname["id"] == $iNicknameId) {
                sendJsonAndExit(array("success" => true, "nickname" => subjectNicknameData($aNickname)));
            }
        }
        sendJsonAndExit(array("success" => false, "message" => "Nickname was not found."), 404);
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
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
    while ($aAddress = $oStatement->fetch()) {
        $iCurrentSubjectId = (int)$aAddress["subject_id"];
        if (!isset($aAddresses[$iCurrentSubjectId])) {
            $aAddresses[$iCurrentSubjectId] = array();
        }
        $aAddresses[$iCurrentSubjectId][] = $aAddress;
    }
    return $aAddresses;
}

function subjectAddressData($aAddress, $sSubjectName, $aDisplaySettings = null) {
    $aFields = array(
        "address_type",
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
        "country",
        "note"
    );
    $aData = array(
        "id" => (int)$aAddress["id"],
        "subject_id" => (int)$aAddress["subject_id"],
        "is_primary" => (int)$aAddress["is_primary"],
        "is_active" => (int)$aAddress["is_active"]
    );
    foreach ($aFields as $sField) {
        $aData[$sField] = isset($aAddress[$sField]) ? (string)$aAddress[$sField] : "";
    }
    $aData["postal_code"] = postalCodeDisplayValue($aData["country"], $aData["postal_code"]);
    $aData["copy_value"] = renderAddressCopyText($aAddress, $sSubjectName, $aDisplaySettings);
    $sText = renderAddressText($aAddress, $aDisplaySettings);
    $sNote = trim((string)$aAddress["note"]);
    $aData["cell_copy_value"] = $sText . ($sNote != "" ? " (" . $sNote . ")" : "");
    return $aData;
}

function handleSubjectAddressDataRequest($oPdo, $aDisplaySettings = null) {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["action"]) || $_POST["action"] != "get_subject_addresses") {
        return;
    }
    $iSubjectId = isset($_POST["subject_id"]) ? (int)$_POST["subject_id"] : 0;
    $sSubjectName = getPostedValue("subject_name");
    $aPostedAddressIds = isset($_POST["address_ids"]) && is_array($_POST["address_ids"]) ? $_POST["address_ids"] : array();
    $aAddressIds = array();
    $aUsedAddressIds = array();
    foreach ($aPostedAddressIds as $mAddressId) {
        $iAddressId = (int)$mAddressId;
        if ($iAddressId > 0 && !isset($aUsedAddressIds[$iAddressId])) {
            $aAddressIds[] = $iAddressId;
            $aUsedAddressIds[$iAddressId] = true;
        }
    }
    if ($iSubjectId < 1 || !$aAddressIds) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid address request."), 400);
    }
    try {
        $aAddresses = fetchSubjectAddresses($oPdo, $iSubjectId);
        $aSubjectAddresses = isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array();
        $aAddressesById = array();
        foreach ($aSubjectAddresses as $aAddress) {
            $aAddressesById[(int)$aAddress["id"]] = $aAddress;
        }
        $aData = array();
        foreach ($aAddressIds as $iAddressId) {
            if (!isset($aAddressesById[$iAddressId])) {
                sendJsonAndExit(array("success" => false, "message" => "Address was not found."), 404);
            }
            $aData[] = subjectAddressData($aAddressesById[$iAddressId], $sSubjectName, $aDisplaySettings);
        }
        sendJsonAndExit(array("success" => true, "addresses" => $aData));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

function fetchSubjectGroups($oPdo, $iSubjectId = 0) {
    $aGroups = array();
    $sSql = "SELECT sg.subject_id, sg.group_id, g.name, g.created_at, g.updated_at FROM ex_group_subject AS sg INNER JOIN ex_groups AS g ON g.id = sg.group_id";
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
    while ($aGroup = $oStatement->fetch()) {
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
    $aGroup = $oStatement->fetch();
    if (!$aGroup) {
        return array(
            "group_id" => $iGroupId,
            "name" => $sName
        );
    }
    return array(
        "group_id" => (int)$aGroup["group_id"],
        "name" => $aGroup["name"],
        "timestamp_tooltip" => timestampTooltipText($aGroup)
    );
}

function fetchGroups($oPdo) {
    $oStatement = $oPdo->query("SELECT id, name, legacy_id, `order` FROM ex_groups ORDER BY `order` ASC, id ASC");
    return $oStatement->fetchAll();
}

function fetchGroupAdminRows($oPdo, $iGroupId = 0) {
    $sSql = "SELECT g.id, g.name, g.`order`, g.created_at, g.updated_at, COUNT(DISTINCT sg.subject_id) AS subject_count, GROUP_CONCAT(DISTINCT p.permission_key ORDER BY p.permission_key ASC SEPARATOR ',') AS permission_keys, GROUP_CONCAT(DISTINCT p.name ORDER BY p.permission_key ASC SEPARATOR ',') AS permission_names FROM ex_groups AS g LEFT JOIN ex_group_subject AS sg ON sg.group_id = g.id LEFT JOIN ex_group_permissions AS gp ON gp.group_id = g.id AND gp.is_allowed = 1 LEFT JOIN ex_permissions AS p ON p.id = gp.permission_id AND p.is_active = 1";
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
    return $oStatement->fetchAll();
}

function fetchPortalPermissions($oPdo) {
    $oStatement = $oPdo->query("SELECT permission_key, name, note FROM ex_permissions WHERE is_active = 1 ORDER BY permission_key ASC");
    return $oStatement->fetchAll();
}

function fetchSubjectPortalUser($oPdo, $iSubjectId) {
    $aPortalUser = array(
        "has_user" => 0,
        "user_name" => "",
        "is_active" => 1,
        "session_timeout" => 1200,
        "direct_permission_keys" => array(),
        "effective_permission_keys" => array()
    );
    $oStatement = $oPdo->prepare("SELECT id, user_name, is_active, session_timeout, created_at, updated_at FROM ex_users WHERE subject_id = :subject_id");
    $oStatement->execute(array("subject_id" => $iSubjectId));
    $aUser = $oStatement->fetch();
    if (!$aUser) {
        return $aPortalUser;
    }
    $aPortalUser["has_user"] = 1;
    $aPortalUser["user_name"] = $aUser["user_name"];
    $aPortalUser["is_active"] = (int)$aUser["is_active"];
    $aPortalUser["session_timeout"] = (int)$aUser["session_timeout"];
    $aPortalUser["created_at"] = $aUser["created_at"];
    $aPortalUser["updated_at"] = $aUser["updated_at"];
    $aPortalUser["timestamp_tooltip"] = timestampTooltipText($aUser);
    $oStatement = $oPdo->prepare("SELECT p.permission_key FROM ex_user_permission AS up INNER JOIN ex_permissions AS p ON p.id = up.permission_id WHERE up.user_id = :user_id AND up.is_allowed = 1 AND p.is_active = 1 ORDER BY p.permission_key ASC");
    $oStatement->execute(array("user_id" => (int)$aUser["id"]));
    while ($sPermissionKey = $oStatement->fetchColumn()) {
        $aPortalUser["direct_permission_keys"][] = $sPermissionKey;
    }
    $aEffectivePermissions = fetchUserEffectivePermissions($oPdo, (int)$aUser["id"], $iSubjectId);
    foreach ($aEffectivePermissions as $sPermissionKey => $blAllowed) {
        if ($blAllowed) {
            $aPortalUser["effective_permission_keys"][] = $sPermissionKey;
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
        $sPermissionKey = trim($sPermissionKey);
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
    while ($aPermission = $oStatement->fetch()) {
        $aNormalizedKeys[(string)$aPermission["permission_key"]] = (int)$aPermission["id"];
    }
    return $aNormalizedKeys;
}

function savePortalUserPermissions($oPdo, $iUserId, $aPermissionKeys) {
    $aPermissions = normalizePortalPermissionKeys($oPdo, $aPermissionKeys);
    $oStatement = $oPdo->prepare("DELETE FROM ex_user_permission WHERE user_id = :user_id");
    $oStatement->execute(array("user_id" => $iUserId));
    foreach ($aPermissions as $sPermissionKey => $iPermissionId) {
        $oStatement = $oPdo->prepare("INSERT INTO ex_user_permission (user_id, permission_id, is_allowed) VALUES (:user_id, :permission_id, 1)");
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
    $aCurrent = $oStatement->fetch();
    if (!$aCurrent) {
        throw new Exception("Group was not found.");
    }
    if ($sDirection == "up") {
        $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_groups WHERE `order` < :order ORDER BY `order` DESC, id DESC LIMIT 1 FOR UPDATE");
    } else {
        $oStatement = $oPdo->prepare("SELECT id, `order` FROM ex_groups WHERE `order` > :order ORDER BY `order` ASC, id ASC LIMIT 1 FOR UPDATE");
    }
    $oStatement->execute(array("order" => (int)$aCurrent["order"]));
    $aOther = $oStatement->fetch();
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
        && !isset($aPayload["portal_session_timeout"])
        && !isset($aPayload["portal_permission_keys"])) {
        return;
    }
    $iEnabled = payloadFlag($aPayload, "portal_user_enabled");
    $oStatement = $oPdo->prepare("SELECT id, password_hash FROM ex_users WHERE subject_id = :subject_id FOR UPDATE");
    $oStatement->execute(array("subject_id" => $iSubjectId));
    $aUser = $oStatement->fetch();
    if (!$iEnabled) {
        if ($aUser) {
            $oStatement = $oPdo->prepare("DELETE FROM ex_user_permission WHERE user_id = :user_id");
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
    $iSessionTimeout = (int)payloadValue($aPayload, "portal_session_timeout");
    if ($iSessionTimeout < 60) {
        $iSessionTimeout = 60;
    }
    if ($aUser) {
        if ($sPassword != "") {
            $oStatement = $oPdo->prepare("UPDATE ex_users SET user_name = :user_name, password_hash = :password_hash, is_active = :is_active, session_timeout = :session_timeout WHERE id = :id");
            $oStatement->execute(array(
                "user_name" => $sUserName,
                "password_hash" => password_hash($sPassword, PASSWORD_DEFAULT),
                "is_active" => payloadFlag($aPayload, "portal_user_active"),
                "session_timeout" => $iSessionTimeout,
                "id" => (int)$aUser["id"]
            ));
        } else {
            $oStatement = $oPdo->prepare("UPDATE ex_users SET user_name = :user_name, is_active = :is_active, session_timeout = :session_timeout WHERE id = :id");
            $oStatement->execute(array(
                "user_name" => $sUserName,
                "is_active" => payloadFlag($aPayload, "portal_user_active"),
                "session_timeout" => $iSessionTimeout,
                "id" => (int)$aUser["id"]
            ));
        }
        $iUserId = (int)$aUser["id"];
    } else {
        $oStatement = $oPdo->prepare("INSERT INTO ex_users (subject_id, user_name, password_hash, is_active, session_timeout) VALUES (:subject_id, :user_name, :password_hash, :is_active, :session_timeout)");
        $oStatement->execute(array(
            "subject_id" => $iSubjectId,
            "user_name" => $sUserName,
            "password_hash" => password_hash($sPassword, PASSWORD_DEFAULT),
            "is_active" => payloadFlag($aPayload, "portal_user_active"),
            "session_timeout" => $iSessionTimeout
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
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"ia js-move-group-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"ia js-move-group-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"ia js-merge-group\" title=\"Merge into this group\" aria-label=\"Merge into this group\">" . $sMergeEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"ia js-edit-group\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"ia js-delete-group\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "") . "</td>\n"
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
    while ($aNote = $oStatement->fetch()) {
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
    $aSubjectContact = $oStatement->fetch();
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

function renderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions = true, $aHiddenInactive = array(), $aDisplaySettings = null, $blDeferAddressData = false, $blDeferContactData = false, $blDeferNicknameData = false, $blDeferGroupData = false, $blDeferNameCopy = false, $blAriaAttributes = true) {
    global $sEditEmoji, $sDeleteEmoji, $sPortalEmoji;

    $iSubjectId = (int)$aRow["subject_id"];
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
        $sActions = "<span class=\"la\">"
            . "<a href=\"#\" class=\"ia js-edit-subject\" data-subject-id=\"" . html($iSubjectId) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
            . "<a href=\"#\" class=\"ia js-edit-subject-portal\" data-subject-id=\"" . html($iSubjectId) . "\" title=\"Portal account\" aria-label=\"Portal account\">" . $sPortalEmoji . "</a>"
            . "<a href=\"#\" class=\"ia js-delete-subject\" data-subject-id=\"" . html($iSubjectId) . "\" data-subject-name=\"" . html($aRow["subject_name"]) . "\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
            . "</span>";
    }
    $sHtml = "<tr class=\"sr" . ($blIsActive ? " ra" : " ri") . "\" data-subject-id=\"" . html($iSubjectId) . "\" data-subject-type=\"" . html($aRow["subject_type"]) . "\" data-subject-active=\"" . ($blIsActive ? "1" : "0") . "\">"
        . "<td class=\"tc\">" . html($aRow["subject_type"]) . "</td>"
        . "<td><span class=\"iv nv\"" . $sTimestampTooltipAttribute . ">" . htmlValue($aRow["subject_name"]) . "</span>"
        . ($blDeferNameCopy ? renderDeferredCopyAction("js-copy-subject-name") : renderCopyAction($aRow["subject_name"]))
        . $sActions . "</td>"
        . "<td>" . htmlValue($aRow["first_name"]) . "</td>"
        . "<td>" . htmlValue($aRow["last_name"]) . "</td>"
        . "<td>" . htmlValue($aRow["birth_name"]) . "</td>"
        . ($blShowBirthNumber ? "<td" . $sBirthNumberClassAttribute . ">" . renderBirthNumberValue($aRow["birth_number"]) . "</td>" : "")
        . "<td" . $sBirthDateClassAttribute . ">" . renderSubjectDateValue($aRow["birth_date"], $sBirthDateAgeLabel) . "</td>"
        . "<td>" . renderSubjectDateValue($aRow["death_date"], $sDeathDateAgeLabel) . "</td>"
        . "<td>" . renderNicknameList(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(), $blShowActions, $iSubjectId, !empty($aHiddenInactive["nicknames"][$iSubjectId]), true, true, true, $blDeferNicknameData) . "</td>"
        . "<td>" . renderAddressList(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $blShowActions, $iSubjectId, $aRow["subject_name"], !empty($aHiddenInactive["addresses"][$iSubjectId]), $aDisplaySettings, true, true, true, $blDeferAddressData) . "</td>"
        . "<td>" . renderContactList(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(), $blShowActions, $iSubjectId, true, true, !empty($aHiddenInactive["contacts"][$iSubjectId]), true, true, true, $blDeferContactData) . "</td>"
        . "<td>" . renderGroupList(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(), $blShowActions, $iSubjectId, true, true, true, $blDeferGroupData) . "</td>"
        . "<td>" . renderNoteList(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array(), $blShowActions, $iSubjectId, !empty($aHiddenInactive["notes"][$iSubjectId]), true, true, true) . "</td>"
        . "</tr>\n";
    return $blAriaAttributes ? $sHtml : removeAriaAttributes($sHtml);
}

function subjectRowOption($aOptions, $sName, $mDefault) {
    return is_array($aOptions) && array_key_exists($sName, $aOptions) ? $aOptions[$sName] : $mDefault;
}

function renderSubjectTableCell($sHtml, $sClass = "", $sStyle = "") {
    $sAttributes = "";
    if ($sStyle != "") {
        $sClass = trim($sClass . " cell-nowrap");
    }
    if ($sClass != "") {
        $sAttributes .= " class=\"" . html($sClass) . "\"";
    }
    return "<td" . $sAttributes . ">" . $sHtml . "</td>";
}

function renderResponsiveSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aHiddenInactive = array(), $aDisplaySettings = null, $aOptions = array()) {
    $iSubjectId = (int)$aRow["subject_id"];
    $blIsActive = (int)$aRow["is_active"] == 1;
    $blShowActions = subjectRowOption($aOptions, "show_actions", false);
    $blAriaAttributes = subjectRowOption($aOptions, "aria_attributes", true);
    $iItemSubjectId = (int)subjectRowOption($aOptions, "item_subject_id", 0);
    $sNoWrapStyle = "overflow-wrap: normal; white-space: nowrap; word-break: normal;";
    $sBirthNumberClass = birthNumberClass($aRow["birth_number"], subjectRowOption($aOptions, "birth_number_class", "ch"));
    $sBirthDateClass = birthDateClass($aRow["birth_number"], $aRow["birth_date"], subjectRowOption($aOptions, "birth_date_class", "c2"));
    $sDeathDateClass = subjectRowOption($aOptions, "death_date_class", "ch");
    $blDeathDateHidden = strpos(" " . trim($sDeathDateClass) . " ", " ch ") !== false;
    $sBirthDateAgeLabel = trim((string)$aRow["death_date"]) == "" ? subjectAgeLabel(ageInYears($aRow["birth_date"]), "*") : ($blDeathDateHidden ? subjectAgeLabel(ageInYears($aRow["birth_date"], $aRow["death_date"]), "†") : "");
    $sDeathDateAgeLabel = trim((string)$aRow["death_date"]) != "" && !$blDeathDateHidden ? subjectAgeLabel(ageInYears($aRow["birth_date"], $aRow["death_date"]), "†") : "";
    $sTimestampTooltipText = timestampTooltipText($aRow);
    $sTimestampTooltipAttribute = $sTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sTimestampTooltipText)) . "\"" : "";
    $aBeforeNameCells = subjectRowOption($aOptions, "before_name_cells", array());
    $sHtml = "<tr class=\"sr" . ($blIsActive ? " ra" : " ri") . "\" data-subject-id=\"" . html($iSubjectId) . "\" data-subject-type=\"" . html($aRow["subject_type"]) . "\" data-subject-active=\"" . ($blIsActive ? "1" : "0") . "\">"
        . renderSubjectTableCell(html($aRow["subject_type"]), subjectRowOption($aOptions, "type_class", "ch"), subjectRowOption($aOptions, "type_style", ""));
    if (is_array($aBeforeNameCells)) {
        foreach ($aBeforeNameCells as $sCellHtml) {
            $sHtml .= $sCellHtml;
        }
    }
    $sHtml .= renderSubjectTableCell(
            "<span class=\"iv nv\"" . $sTimestampTooltipAttribute . ">" . htmlValue($aRow["subject_name"]) . "</span>"
            . (subjectRowOption($aOptions, "name_defer_copy", false) ? renderDeferredCopyAction("js-copy-subject-name") : renderCopyAction($aRow["subject_name"]))
            . subjectRowOption($aOptions, "name_actions", ""),
            subjectRowOption($aOptions, "name_class", ""),
            subjectRowOption($aOptions, "name_style", "")
        )
        . renderSubjectTableCell(htmlValue($aRow["first_name"]), subjectRowOption($aOptions, "first_name_class", "ch"), subjectRowOption($aOptions, "first_name_style", ""))
        . renderSubjectTableCell(htmlValue($aRow["last_name"]), subjectRowOption($aOptions, "last_name_class", "ch"), subjectRowOption($aOptions, "last_name_style", ""))
        . renderSubjectTableCell(htmlValue($aRow["birth_name"]), subjectRowOption($aOptions, "birth_name_class", "c1"), subjectRowOption($aOptions, "birth_name_style", ""))
        . renderSubjectTableCell(renderBirthNumberValue($aRow["birth_number"]), $sBirthNumberClass, subjectRowOption($aOptions, "birth_number_style", ""))
        . renderSubjectTableCell(renderSubjectDateValue($aRow["birth_date"], $sBirthDateAgeLabel), $sBirthDateClass, subjectRowOption($aOptions, "birth_date_style", $sNoWrapStyle))
        . renderSubjectTableCell(renderSubjectDateValue($aRow["death_date"], $sDeathDateAgeLabel), $sDeathDateClass, subjectRowOption($aOptions, "death_date_style", ""))
        . renderSubjectTableCell(renderNicknameList(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, !empty($aHiddenInactive["nicknames"][$iSubjectId]), subjectRowOption($aOptions, "nickname_show_add_action", false), subjectRowOption($aOptions, "nickname_show_cell_copy_action", true), subjectRowOption($aOptions, "nickname_cell_copy_before_add_action", true), subjectRowOption($aOptions, "nickname_defer_data", false)), subjectRowOption($aOptions, "nickname_class", "c1"), subjectRowOption($aOptions, "nickname_style", ""))
        . renderSubjectTableCell(renderAddressList(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, $aRow["subject_name"], !empty($aHiddenInactive["addresses"][$iSubjectId]), $aDisplaySettings, subjectRowOption($aOptions, "address_show_add_action", false), subjectRowOption($aOptions, "address_show_cell_copy_action", true), subjectRowOption($aOptions, "address_cell_copy_before_add_action", true), subjectRowOption($aOptions, "address_defer_data", false)), subjectRowOption($aOptions, "address_class", ""), subjectRowOption($aOptions, "address_style", ""))
        . renderSubjectTableCell(renderContactList(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, true, true, !empty($aHiddenInactive["contacts"][$iSubjectId]), subjectRowOption($aOptions, "contact_show_add_action", false), subjectRowOption($aOptions, "contact_show_cell_copy_action", true), subjectRowOption($aOptions, "contact_cell_copy_before_add_action", true), subjectRowOption($aOptions, "contact_defer_data", false)), subjectRowOption($aOptions, "contact_class", ""), subjectRowOption($aOptions, "contact_style", ""))
        . renderSubjectTableCell(renderGroupList(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, subjectRowOption($aOptions, "group_show_add_action", false), subjectRowOption($aOptions, "group_show_cell_copy_action", true), subjectRowOption($aOptions, "group_cell_copy_before_add_action", true), subjectRowOption($aOptions, "group_defer_data", false)), subjectRowOption($aOptions, "group_class", "c3"), subjectRowOption($aOptions, "group_style", ""))
        . renderSubjectTableCell(renderNoteList(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, !empty($aHiddenInactive["notes"][$iSubjectId]), subjectRowOption($aOptions, "note_show_add_action", false), subjectRowOption($aOptions, "note_show_cell_copy_action", true), subjectRowOption($aOptions, "note_cell_copy_before_add_action", true)), subjectRowOption($aOptions, "note_class", "c3"), subjectRowOption($aOptions, "note_style", ""))
        . "</tr>\n";
    return $blAriaAttributes ? $sHtml : removeAriaAttributes($sHtml);
}

function renderUpdatedSubjectRow($oPdo, $iSubjectId, $aVisibilitySettings = null, $aFilterSql = null) {
    global $blSubjectsAriaAttributes;

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
    return renderSubjectRow($aRows[0], $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, true, $aHiddenInactive, is_array($aVisibilitySettings) ? $aVisibilitySettings : null, true, true, true, true, true, $blSubjectsAriaAttributes);
}

function getUpdatedSubjectResponse($oPdo, $iSubjectId, $aVisibilitySettings = null, $aFilterSql = null) {
    $sRowHtml = renderUpdatedSubjectRow($oPdo, $iSubjectId, $aVisibilitySettings, $aFilterSql);
    if ($sRowHtml == "") {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    return array("success" => true, "subject_id" => $iSubjectId, "row_html" => $sRowHtml);
}

function subjectFilterText($aSubjectRow) {
    $aFields = array("subject_name", "subject_type", "title_before", "first_name", "middle_name", "last_name", "title_after", "birth_name", "birth_number", "birth_date", "death_date");
    $aText = array();
    foreach ($aFields as $sField) {
        if (isset($aSubjectRow[$sField]) && trim((string)$aSubjectRow[$sField]) != "") {
            $aText[] = (string)$aSubjectRow[$sField];
        }
    }
    return implode(" ", $aText);
}

function fetchSubjectEditorData($oPdo, $iSubjectId) {
    $oStatement = $oPdo->prepare("SELECT s.id AS subject_id, s.subject_type, s.is_active, subn.name AS subject_name_value, p.title_before, p.first_name, p.middle_name, p.last_name, p.title_after, p.birth_name, p.birth_number, p.birth_date, p.death_date FROM ex_subjects AS s LEFT JOIN ex_persons AS p ON p.subject_id = s.id LEFT JOIN ex_subject_names AS subn ON subn.subject_id = s.id WHERE s.id = :subject_id");
    $oStatement->execute(array("subject_id" => $iSubjectId));
    $aSubject = $oStatement->fetch();
    if (!$aSubject) {
        return null;
    }
    $aSubjectRows = fetchSubjectRows($oPdo, $iSubjectId);
    $aSubject["subject_name"] = $aSubjectRows ? $aSubjectRows[0]["subject_name"] : "";
    return $aSubject;
}

function fetchSubjectPortalEditorData($oPdo, $iSubjectId) {
    $aRows = fetchSubjectRows($oPdo, $iSubjectId);
    if (!$aRows) {
        return null;
    }
    return array(
        "subject_id" => (int)$aRows[0]["subject_id"],
        "subject_name" => $aRows[0]["subject_name"],
        "subject_type" => $aRows[0]["subject_type"],
        "portal_user" => fetchSubjectPortalUser($oPdo, $iSubjectId),
        "portal_permissions" => fetchPortalPermissions($oPdo)
    );
}

function addressesNormalizeKey($sValue) {
    $sValue = str_replace("\r\n", "\n", $sValue);
    $sValue = str_replace("\r", "\n", $sValue);
    if (function_exists("mb_strtolower")) {
        return mb_strtolower($sValue, "UTF-8");
    }
    return strtolower($sValue);
}

function addressesCompareRows($aFirst, $aSecond) {
    $iResult = strcmp($aFirst["address_sort"], $aSecond["address_sort"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return strcmp($aFirst["address_text"], $aSecond["address_text"]);
}

function addressesCompareSubjects($aFirst, $aSecond) {
    $iResult = strcmp($aFirst["subject_name"], $aSecond["subject_name"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["address_id"] - (int)$aSecond["address_id"];
}

function addressesAddressFields() {
    return array("organization_name", "department_name", "care_of", "street_name", "house_number", "evidence_number", "orientation_number", "orientation_suffix", "address_line2", "city", "city_part", "postal_code", "region", "country");
}

function addressesBuildMatch($aAddress) {
    $aMatch = array();
    foreach (addressesAddressFields() as $sField) {
        $aMatch[$sField] = array_key_exists($sField, $aAddress) && $aAddress[$sField] !== null ? (string)$aAddress[$sField] : null;
    }
    return $aMatch;
}

function addressesDecodeMatch($sMatch) {
    $sJson = base64_decode($sMatch, true);
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

function addressesSubjectAddressFields() {
    return array_merge(array("address_type"), addressesAddressFields(), array("note"));
}

function addressesNullValue($sField, $sValue) {
    return in_array($sField, array("country"), true) || $sValue != "" ? $sValue : null;
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
    $sHtml = " data-address-match=\"" . html($aAddressRow["address_match"]) . "\"" . renderTimestampTooltipDataAttribute($aAddressRow);
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
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower($aSubject["subject_type"]));
    return "address-subject-cell address-subject-type-" . $sSubjectType . (!empty($aSubject["is_active"]) && (int)$aSubject["address_is_active"] == 1 ? " address-subject-active" : " address-subject-inactive");
}

function addressesFilterText($aAddressRow) {
    $sAddressFilterText = (string)$aAddressRow["address_text"];
    foreach ($aAddressRow["subjects"] as $aFilterSubject) {
        $sAddressFilterText .= " " . (string)$aFilterSubject["subject_filter_text"];
    }
    return $sAddressFilterText;
}

function addressesRenderAddressCell($aAddressRow, $iSubjectCount, $blCanEdit) {
    global $sEditEmoji, $sDeleteEmoji;

    $sAddressTimestampTooltipText = timestampTooltipText($aAddressRow);
    $sAddressTimestampTooltipAttribute = $sAddressTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sAddressTimestampTooltipText)) . "\"" : "";
    $sAddressActions = $blCanEdit ? "<span class=\"la\"><a href=\"#\" class=\"ia js-edit-shared-address\" title=\"Edit shared address\" aria-label=\"Edit shared address\">" . $sEditEmoji . "</a><a href=\"#\" class=\"ia js-delete-shared-address\" title=\"Delete shared address\" aria-label=\"Delete shared address\">" . $sDeleteEmoji . "</a></span>" : "";
    return "        <td class=\"address-cell\" rowspan=\"" . html($iSubjectCount) . "\"" . addressesRenderDataAttributes($aAddressRow) . ">"
        . "<span class=\"iv\"" . $sAddressTimestampTooltipAttribute . ">" . htmlValue($aAddressRow["address_text"]) . "</span>"
        . renderCopyAction($aAddressRow["address_copy_text"])
        . $sAddressActions
        . renderSubjectCellCopyAction(array($aAddressRow["address_text"]), true)
        . "</td>\n";
}

function addressesRenderSubjectCell($aSubject, $sAddressFilterText, $blCanEdit) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    $sSubjectTimestampTooltipText = timestampTooltipText($aSubject);
    $sSubjectTimestampTooltipAttribute = $sSubjectTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sSubjectTimestampTooltipText)) . "\"" : "";
    $sSubjectActions = $blCanEdit ? "<span class=\"la\"><a href=\"#\" class=\"ia js-edit-subject-address-local\" title=\"Edit subject address\" aria-label=\"Edit subject address\">" . $sEditEmoji . "</a><a href=\"#\" class=\"ia js-delete-subject-address-local\" title=\"Delete subject address\" aria-label=\"Delete subject address\">" . $sDeleteEmoji . "</a></span>" : "";
    $sSubjectEditAction = $blCanEdit ? "<span class=\"la\"><a href=\"#\" class=\"ia js-edit-subject\" data-subject-id=\"" . html($aSubject["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a></span>" : "";
    $sSubjectValueClass = "iv" . ((string)$aSubject["address_values"]["address_type"] == "main" ? " am" : "");
    $sSubjectPrimaryFlag = "<span class=\"fl\"><span title=\"Primary\">" . ((int)$aSubject["is_primary"] == 1 ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ((int)$aSubject["address_is_active"] == 1 ? "" : $sInactiveEmoji) . "</span></span>";
    return "        <td class=\"" . html(addressesSubjectCellClass($aSubject)) . " li ai\"" . addressesRenderSubjectDataAttributes($aSubject) . "><span class=\"ch\">" . htmlValue($sAddressFilterText) . "</span><span class=\"" . html($sSubjectValueClass) . "\"" . $sSubjectTimestampTooltipAttribute . ">" . htmlValue($aSubject["subject_name"]) . "</span>" . renderCopyAction($aSubject["subject_name"]) . $sSubjectEditAction . $sSubjectPrimaryFlag . $sSubjectActions . "</td>\n";
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
            "subject_name" => $aSubjectRow["subject_name"],
            "subject_filter_text" => subjectFilterText($aSubjectRow),
            "subject_type" => $aSubjectRow["subject_type"],
            "is_active" => (int)$aSubjectRow["is_active"] == 1,
            "created_at" => $aSubjectRow["created_at"],
            "updated_at" => $aSubjectRow["updated_at"]
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
                    "address_match" => base64_encode(json_encode($aAddressMatch)),
                    "address_values" => $aAddressMatch,
                    "subjects" => array()
                );
            }
            $aRows[$sAddressKey]["subjects"][] = array_merge($aSubjectNames[$iSubjectId], array(
                "address_id" => (int)$aAddress["id"],
                "address_values" => array(
                    "address_type" => $aAddress["address_type"],
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
            $aRows[$sKey]["created_at"] = $aRows[$sKey]["subjects"][0]["address_created_at"];
            $aRows[$sKey]["updated_at"] = $aRows[$sKey]["subjects"][0]["address_updated_at"];
        }
        usort($aRows[$sKey]["subjects"], "addressesCompareSubjects");
    }
    uasort($aRows, "addressesCompareRows");
    return $aRows;
}

function bdGetBirthdayInfo($sBirthDate) {
    global $iBirthdayDisplayMinDays, $iBirthdayDisplayMaxDays;

    $sBirthDate = trim($sBirthDate);
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
    $iTodayTimestamp = strtotime("today 12:00:00");
    if ($iTodayTimestamp === false) {
        return null;
    }
    $iWindowMinTimestamp = strtotime(sprintf("%+d days", (int)$iBirthdayDisplayMinDays), $iTodayTimestamp);
    $iWindowMaxTimestamp = strtotime(sprintf("%+d days", (int)$iBirthdayDisplayMaxDays), $iTodayTimestamp);
    if ($iWindowMinTimestamp === false || $iWindowMaxTimestamp === false) {
        return null;
    }
    $iCurrentYear = (int)date("Y", $iTodayTimestamp);
    $aYears = array($iCurrentYear - 1, $iCurrentYear, $iCurrentYear + 1);
    $iBirthdayTimestamp = null;
    $iBirthdayDistance = null;
    foreach ($aYears as $iYear) {
        if (!checkdate($iMonth, $iDay, $iYear)) {
            continue;
        }
        $iCurrentBirthdayTimestamp = strtotime(sprintf("%04d-%02d-%02d 12:00:00", $iYear, $iMonth, $iDay));
        if ($iCurrentBirthdayTimestamp === false) {
            continue;
        }
        if ($iCurrentBirthdayTimestamp < $iWindowMinTimestamp || $iCurrentBirthdayTimestamp > $iWindowMaxTimestamp) {
            continue;
        }
        $iCurrentBirthdayDistance = abs($iCurrentBirthdayTimestamp - $iTodayTimestamp);
        if ($iBirthdayDistance !== null && $iCurrentBirthdayDistance > $iBirthdayDistance) {
            continue;
        }
        if ($iBirthdayDistance !== null && $iCurrentBirthdayDistance == $iBirthdayDistance && $iCurrentBirthdayTimestamp < $iBirthdayTimestamp) {
            continue;
        }
        $iBirthdayTimestamp = $iCurrentBirthdayTimestamp;
        $iBirthdayDistance = $iCurrentBirthdayDistance;
    }
    if ($iBirthdayTimestamp === null) {
        return null;
    }
    return array(
        "days_to_served" => (int)round(($iBirthdayTimestamp - $iTodayTimestamp) / 86400),
        "served_date" => date("Y-m-d", $iBirthdayTimestamp)
    );
}

function fetchPersonServedRows($oPdo, $sServedColumn) {
    if (!in_array($sServedColumn, array("birthday_served_at", "inter_served_at"), true)) {
        return array();
    }
    $aServedRows = array();
    $oStatement = $oPdo->query("SELECT subject_id, " . $sServedColumn . " FROM ex_persons");
    while ($aRow = $oStatement->fetch()) {
        $aServedRows[(int)$aRow["subject_id"]] = $aRow;
    }
    return $aServedRows;
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
        $oBirthday = new DateTimeImmutable($sBirthdayDate . " 00:00:00");
    } catch (Exception $oException) {
        error_log((string)$oException);
        return false;
    }
    return $oServedAt >= $oBirthday->modify(sprintf("%+d days", -(int)$iBirthdayDisplayMaxDays)) && $oServedAt < $oBirthday->modify(sprintf("%+d days", 1 - (int)$iBirthdayDisplayMinDays));
}

function servedCompareRows($aFirst, $aSecond) {
    $iFirstCountdown = isset($aFirst["days_to_served"]) ? (int)$aFirst["days_to_served"] : 0;
    $iSecondCountdown = isset($aSecond["days_to_served"]) ? (int)$aSecond["days_to_served"] : 0;
    if ($iFirstCountdown === $iSecondCountdown) {
        $iResult = strcmp(isset($aFirst["subject_sort_name"]) ? $aFirst["subject_sort_name"] : $aFirst["subject_name"], isset($aSecond["subject_sort_name"]) ? $aSecond["subject_sort_name"] : $aSecond["subject_name"]);
        if ($iResult !== 0) {
            return $iResult;
        }
        $iResult = strcmp($aFirst["subject_type"], $aSecond["subject_type"]);
        if ($iResult !== 0) {
            return $iResult;
        }
        return (int)$aFirst["subject_id"] - (int)$aSecond["subject_id"];
    }
    return $iFirstCountdown < $iSecondCountdown ? -1 : 1;
}

function bdCompareRows($aFirst, $aSecond) {
    return servedCompareRows($aFirst, $aSecond);
}

function bdRenderSubjectActions($aRow, $blShowActions) {
    global $sDeleteEmoji, $sEditEmoji, $sPortalEmoji;

    if (!$blShowActions) {
        return "";
    }
    return "<span class=\"la\">"
        . "<a href=\"#\" class=\"ia js-edit-subject\" data-subject-id=\"" . html($aRow["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
        . "<a href=\"#\" class=\"ia js-edit-subject-portal\" data-subject-id=\"" . html($aRow["subject_id"]) . "\" title=\"Portal account\" aria-label=\"Portal account\">" . $sPortalEmoji . "</a>"
        . "<a href=\"#\" class=\"ia js-delete-subject\" data-subject-id=\"" . html($aRow["subject_id"]) . "\" data-subject-name=\"" . html($aRow["subject_name"]) . "\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
        . "</span>";
}

function renderServedSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aDisplaySettings, $sServedActionClass, $sServedActionLabel, $sServedActionEmoji, $aOptions = array()) {
    $iSubjectId = (int)$aRow["subject_id"];
    $iServedDays = (int)$aRow["days_to_served"];
    $sServedDays = $iServedDays < 0 ? "&#8722;" . html(abs($iServedDays)) : htmlValue($aRow["days_to_served"]);
    $sServedAction = $blShowActions ? "<a class=\"ia served-action " . html($sServedActionClass) . "\" href=\"#\" data-subject-id=\"" . html($iSubjectId) . "\" title=\"" . html($sServedActionLabel) . "\" aria-label=\"" . html($sServedActionLabel) . "\"><span class=\"cb\">" . $sServedActionEmoji . "</span></a>" : "";
    $sServedInCell = $sServedDays . ($sServedAction != "" ? "&#8288;" . $sServedAction : "");
    return renderResponsiveSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aHiddenInactive, $aDisplaySettings, array_merge(array(
        "show_actions" => $blShowActions,
        "item_subject_id" => $iSubjectId,
        "before_name_cells" => array(renderSubjectTableCell($sServedInCell, "served-in-column")),
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
    if (bdIsBirthdayServed(fetchPersonServedRows($oPdo, "birthday_served_at"), $iSubjectId, $aBirthdayInfo["served_date"])) {
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
    if (!$aRows || $aRows[0]["subject_type"] != "person") {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aServedInfo = $sInfoFunction($oPdo, $iSubjectId, $aRows[0]);
    if (!is_array($aServedInfo)) {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aRows[0]["days_to_served"] = $aServedInfo["days_to_served"];
    $aRows[0]["served_date"] = $aServedInfo["served_date"];
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
    $sBody = $sText . "\r\n";
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
                if (strtolower($sHeaderName) == strtolower($sName)) {
                    return $sHeaderValue;
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
    if (!$aUser || (int)$aUser["is_active"] != 1 || (int)$aUser["subject_active"] != 1 || !in_array($aUser["subject_type"], array("person", "service"), true) || !password_verify($sPassword, $aUser["password_hash"])) {
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
    return $sPath == "/addressbook" || $sPath == "/addressbook/";
}

function cardDavIsPrincipalCollectionPath($sPath) {
    return $sPath == "/principals" || $sPath == "/principals/";
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
        $sValue = trim($sValue);
        if ($sValue != "") {
            $aEscaped[] = cardDavVCardEscape($sValue);
        }
    }
    return implode(",", $aEscaped);
}

function cardDavVCardLine($sName, $mValue, $sParams = "") {
    $sLine = strtoupper($sName) . (trim($sParams) != "" ? ";" . trim($sParams) : "") . ":" . cardDavVCardEscape($mValue);
    return $sLine;
}

function cardDavVCardRawLine($sName, $mValue, $sParams = "") {
    return strtoupper($sName) . (trim($sParams) != "" ? ";" . trim($sParams) : "") . ":" . (string)$mValue;
}

function cardDavCleanTypeToken($sValue) {
    $sValue = strtoupper(preg_replace("/[^A-Za-z0-9\\-]/", "-", $sValue));
    $sValue = trim($sValue, "-");
    return $sValue != "" ? $sValue : "OTHER";
}

function cardDavAddressType($sAddressType) {
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
        $sLine = trim($sLine);
        if ($sLine != "") {
            $aResult[] = $sLine;
        }
    }
    return implode("\n", $aResult);
}

function cardDavAddVCardContactLines(&$aLines, $aContact) {
    $sType = $aContact["contact_type"];
    $sTypeName = trim($aContact["contact_type_name"]);
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
    $sSubjectType = $aRow["subject_type"];
    $sFullName = trim($aRow["subject_name"]);
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
        if ((int)$aNickname["is_active"] == 1 && trim($aNickname["nickname"]) != "") {
            $aActiveNicknames[] = $aNickname["nickname"];
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
        if (trim($aGroup["name"]) != "") {
            $aActiveGroups[] = $aGroup["name"];
        }
    }
    if (count($aActiveGroups) > 0) {
        $aLines[] = cardDavVCardRawLine("CATEGORIES", cardDavVCardList($aActiveGroups));
    }
    foreach ($aNotes as $aNote) {
        if ((int)$aNote["is_active"] == 1 && trim($aNote["note_text"]) != "") {
            $aActiveNotes[] = $aNote["note_text"];
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
            "display_name" => $aRow["subject_name"],
            "href" => cardDavHref(array("card" => (int)$iSubjectId)),
            "body" => $sBody,
            "etag" => "\"" . sha1($sBody) . "\"",
            "last_modified" => trim($aRow["created_at"]) != "" ? strtotime($aRow["created_at"]) : time()
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
    $sPrincipalHref = cardDavHref(array("principal" => $aUser["user_name"]));
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
    $sPrincipalHref = cardDavHref(array("principal" => $aUser["user_name"]));
    $sPrincipalCollectionHref = cardDavHref(array("principals" => "1"));
    return "        <d:resourcetype><d:collection/><d:principal/></d:resourcetype>\r\n"
        . "        <d:displayname>" . cardDavXml($aUser["user_name"]) . "</d:displayname>\r\n"
        . "        <d:current-user-principal><d:href>" . cardDavXml($sPrincipalHref) . "</d:href></d:current-user-principal>\r\n"
        . "        <d:principal-URL><d:href>" . cardDavXml($sPrincipalHref) . "</d:href></d:principal-URL>\r\n"
        . "        <d:principal-collection-set><d:href>" . cardDavXml($sPrincipalCollectionHref) . "</d:href></d:principal-collection-set>\r\n"
        . "        <card:addressbook-home-set><d:href>" . cardDavXml($sHomeHref) . "</d:href></card:addressbook-home-set>\r\n";
}

function cardDavPrincipalCollectionPropsXml($aUser) {
    $sPrincipalHref = cardDavHref(array("principal" => $aUser["user_name"]));
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
    $sPath = $sHref;
    $aParts = parse_url($sPath);
    $aQuery = array();
    if (is_array($aParts) && isset($aParts["query"])) {
        parse_str($aParts["query"], $aQuery);
        if (isset($aQuery["card"])) {
            return (int)$aQuery["card"];
        }
    }
    if (is_array($aParts) && isset($aParts["path"])) {
        $sPath = $aParts["path"];
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
        $aHrefs[] = $oNode->textContent;
    }
    return $aHrefs;
}

function cardDavSendPropfindAndExit($aCards, $aUser, $sPath) {
    $sDepth = isset($_SERVER["HTTP_DEPTH"]) ? (string)$_SERVER["HTTP_DEPTH"] : "infinity";
    $sXml = "";
    if ($sPath == "/") {
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
            $sXml .= cardDavResponseStart(cardDavHref(array("principal" => $aUser["user_name"])))
                . cardDavPrincipalPropsXml($aUser)
                . cardDavResponseEnd();
        }
        cardDavMultistatusAndExit($sXml);
    }
    if (preg_match("#^/principals/[^/]+/?$#", $sPath)) {
        $sXml .= cardDavResponseStart(cardDavHref(array("principal" => $aUser["user_name"])))
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
    if ($sPath != "/" && !cardDavIsAddressBookPath($sPath)) {
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
    if ($sPath == "/" || cardDavIsAddressBookPath($sPath)) {
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
        return mb_strtolower($sValue, "UTF-8");
    }
    return strtolower($sValue);
}

function contactsCompareRows($aFirst, $aSecond) {
    $iResult = strcmp($aFirst["contact_sort"], $aSecond["contact_sort"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    $iResult = (int)$aFirst["contact_type_order"] - (int)$aSecond["contact_type_order"];
    if ($iResult !== 0) {
        return $iResult;
    }
    $iResult = strcmp($aFirst["contact_type_sort"], $aSecond["contact_type_sort"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["contact_id"] - (int)$aSecond["contact_id"];
}

function contactsCompareSubjects($aFirst, $aSecond) {
    $iResult = strcmp($aFirst["subject_name"], $aSecond["subject_name"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["subject_contact_id"] - (int)$aSecond["subject_contact_id"];
}

function contactsSubjectCellClass($aSubject) {
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower($aSubject["subject_type"]));
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
        $sContactFilterText .= " " . (string)$aFilterSubject["subject_filter_text"];
    }
    return $sContactFilterText;
}

function addPhoneBookContact($oPdo, $iSubjectContactId, $iPhoneBook = 0) {
    $oStatement = $oPdo->prepare("SELECT sc.id, COALESCE(ct.contact_type, '') AS contact_type FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id LEFT JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id WHERE sc.id = :id");
    $oStatement->execute(array("id" => (int)$iSubjectContactId));
    $aRow = $oStatement->fetch();
    if (!$aRow) {
        return false;
    }
    if (!isPhoneContactType(contactTypeKey($aRow["contact_type"]))) {
        return false;
    }
    $oStatement = $oPdo->prepare("INSERT IGNORE INTO ex_phone_book (phone_book, subject_contact_id) VALUES (:phone_book, :subject_contact_id)");
    $oStatement->execute(array("phone_book" => max(0, (int)$iPhoneBook), "subject_contact_id" => (int)$iSubjectContactId));
    return true;
}

function removePhoneBookContact($oPdo, $iPhoneBookId) {
    $oStatement = $oPdo->prepare("DELETE FROM ex_phone_book WHERE id = :id");
    $oStatement->execute(array("id" => (int)$iPhoneBookId));
}

function phoneBookSubjectDisplayName($aRow) {
    $sLastName = trim((string)$aRow["last_name"]);
    $sFirstName = trim((string)$aRow["first_name"]);
    if ($sLastName != "" && $sFirstName != "") {
        return $sLastName . ", " . $sFirstName;
    }
    if ($sLastName != "") {
        return $sLastName;
    }
    if ($sFirstName != "") {
        return $sFirstName;
    }
    if (trim((string)$aRow["person_display_name"]) == "" && trim((string)$aRow["subject_name_value"]) == "" && trim((string)$aRow["contact_value"]) != "") {
        return contactDisplayValue($aRow["contact_type"], $aRow["contact_value"]);
    }
    return $aRow["subject_name"];
}

function fetchPhoneBookRows($oPdo, $iPhoneBook = 0) {
    $sPersonDisplayBase = "NULLIF(TRIM(CONCAT_WS(' ', NULLIF(p.title_before, ''), NULLIF(p.first_name, ''), NULLIF(p.middle_name, ''), NULLIF(p.last_name, ''))), '')";
    $sPersonDisplayName = "NULLIF(TRIM(CONCAT(COALESCE(" . $sPersonDisplayBase . ", ''), IF(NULLIF(p.title_after, '') IS NULL, '', IF(" . $sPersonDisplayBase . " IS NULL, p.title_after, CONCAT(', ', p.title_after))))), '')";
    $sPersonSortName = "NULLIF(TRIM(CONCAT_WS(' ', NULLIF(p.last_name, ''), NULLIF(p.first_name, ''))), '')";
    $sSql = "SELECT pbc.id AS phone_book_id, pbc.phone_book, pbc.subject_contact_id, pbc.created_at AS phone_book_created_at, sc.subject_id, sc.contact_id, sc.is_primary, sc.is_active AS contact_is_active, sc.note, c.contact_type_id, c.contact_value, c.created_at, c.updated_at, COALESCE(ct.contact_type, '') AS contact_type, COALESCE(ct.name, '') AS contact_type_name, COALESCE(ct.`order`, 999999) AS contact_type_order, s.subject_type, s.is_active AS subject_is_active, COALESCE(IF(s.subject_type = 'person', " . $sPersonDisplayName . ", NULL), NULLIF(subn.name, ''), c.contact_value, 'Unnamed subject') AS subject_name, COALESCE(IF(s.subject_type = 'person', " . $sPersonSortName . ", NULL), NULLIF(subn.name, ''), c.contact_value, 'Unnamed subject') AS subject_sort_name, IF(s.subject_type = 'person', " . $sPersonDisplayName . ", NULL) AS person_display_name, subn.name AS subject_name_value, p.first_name, p.last_name FROM ex_phone_book AS pbc INNER JOIN ex_subject_contacts AS sc ON sc.id = pbc.subject_contact_id INNER JOIN ex_contacts AS c ON c.id = sc.contact_id LEFT JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id INNER JOIN ex_subjects AS s ON s.id = sc.subject_id LEFT JOIN ex_persons AS p ON p.subject_id = s.id LEFT JOIN ex_subject_names AS subn ON subn.subject_id = s.id WHERE pbc.phone_book = :phone_book AND COALESCE(ct.contact_type, '') IN ('landline', 'cell', 'fax', 'pager') ORDER BY subject_sort_name COLLATE utf8mb4_czech_ci ASC, sc.subject_id ASC, contact_type_order ASC, c.contact_value ASC, pbc.subject_contact_id ASC";
    $oStatement = $oPdo->prepare($sSql);
    $oStatement->execute(array("phone_book" => max(0, (int)$iPhoneBook)));
    return $oStatement->fetchAll();
}

function contactsRenderPhoneBookAction($aSubject) {
    global $sPhoneBookEmoji;

    if (!isPhoneContactType(contactTypeKey($aSubject["contact_type"])) || !empty($aSubject["phone_book_contact"]) || (int)$aSubject["is_active"] != 1 || (int)$aSubject["contact_is_active"] != 1) {
        return "";
    }
    return "<a href=\"#\" class=\"ia js-add-phone-book-contact\" data-subject-contact-id=\"" . html($aSubject["subject_contact_id"]) . "\" title=\"Add to Phone Book\" aria-label=\"Add to Phone Book\">" . $sPhoneBookEmoji . "</a>";
}

function contactsRenderSubjectCell($aSubject, $sContactFilterText, $blCanEdit) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    $sSubjectTimestampTooltipText = timestampTooltipText($aSubject);
    $sSubjectTimestampTooltipAttribute = $sSubjectTimestampTooltipText != "" ? " title=\"" . str_replace("\n", "&#10;", html($sSubjectTimestampTooltipText)) . "\"" : "";
    $sPhoneBookAction = $blCanEdit ? contactsRenderPhoneBookAction($aSubject) : "";
    $sSubjectActions = $blCanEdit ? "<span class=\"la\">" . $sPhoneBookAction . "<a href=\"#\" class=\"ia js-edit-subject-contact\" title=\"Edit subject contact\" aria-label=\"Edit subject contact\">" . $sEditEmoji . "</a><a href=\"#\" class=\"ia js-delete-subject-contact\" title=\"Delete subject contact\" aria-label=\"Delete subject contact\">" . $sDeleteEmoji . "</a></span>" : "";
    $sSubjectEditAction = $blCanEdit ? "<span class=\"la\"><a href=\"#\" class=\"ia js-edit-subject\" data-subject-id=\"" . html($aSubject["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a></span>" : "";
    $sSubjectPrimaryFlag = "<span class=\"cf\"><span class=\"cp\" title=\"Primary\">" . ((int)$aSubject["is_primary"] == 1 ? $sPrimaryEmoji : "") . "</span><span class=\"cz\" title=\"Inactive\">" . ((int)$aSubject["contact_is_active"] == 1 ? "" : $sInactiveEmoji) . "</span></span>";
    return "        <td class=\"" . html(contactsSubjectCellClass($aSubject)) . " li\"" . contactsRenderSubjectDataAttributes($aSubject) . "><span class=\"ch\">" . htmlValue($sContactFilterText) . "</span><span class=\"iv\"" . $sSubjectTimestampTooltipAttribute . ">" . htmlValue($aSubject["subject_name"]) . "</span>" . renderCopyAction($aSubject["subject_name"]) . $sSubjectEditAction . "<span class=\"ci contact-subject-item\"" . contactsRenderSubjectDataAttributes($aSubject) . "><span class=\"cn\">" . ($aSubject["note"] != "" ? "(" . html($aSubject["note"]) . ")" : "") . "</span>" . $sSubjectPrimaryFlag . $sSubjectActions . "</span></td>\n";
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
            "subject_name" => $aSubjectRow["subject_name"],
            "subject_filter_text" => subjectFilterText($aSubjectRow),
            "subject_type" => $aSubjectRow["subject_type"],
            "is_active" => (int)$aSubjectRow["is_active"] == 1,
            "created_at" => $aSubjectRow["created_at"],
            "updated_at" => $aSubjectRow["updated_at"]
        );
    }
    $sSql = "SELECT c.id AS contact_id, c.contact_type_id, c.contact_value, c.created_at, c.updated_at, COALESCE(ct.contact_type, '') AS contact_type, COALESCE(ct.name, '') AS contact_type_name, COALESCE(ct.`order`, 999999) AS contact_type_order, sc.id AS subject_contact_id, sc.subject_id, sc.is_primary, sc.is_active AS contact_is_active, sc.note, IF(pbc.subject_contact_id IS NULL, 0, 1) AS phone_book_contact FROM ex_contacts AS c LEFT JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id LEFT JOIN ex_subject_contacts AS sc ON sc.contact_id = c.id LEFT JOIN ex_phone_book AS pbc ON pbc.subject_contact_id = sc.id AND pbc.phone_book = 0 ORDER BY c.contact_value ASC, COALESCE(ct.`order`, 999999) ASC, COALESCE(ct.name, '') ASC, c.id ASC, sc.is_active DESC, sc.is_primary DESC, sc.id ASC";
    $oStatement = $oPdo->prepare($sSql);
    $oStatement->execute();
    while ($aContact = $oStatement->fetch()) {
        $iSubjectId = (int)$aContact["subject_id"];
        $iContactId = (int)$aContact["contact_id"];
        $sContactType = $aContact["contact_type"];
        $sContactDisplayValue = contactDisplayValue($sContactType, $aContact["contact_value"]);
        if (!isset($aRows[$iContactId])) {
            $aRows[$iContactId] = array(
                "contact_id" => $iContactId,
                "contact_type_id" => (int)$aContact["contact_type_id"],
                "contact_type" => $sContactType,
                "contact_type_name" => $aContact["contact_type_name"],
                "contact_type_order" => (int)$aContact["contact_type_order"],
                "contact_type_sort" => contactsNormalizeKey($aContact["contact_type_name"]),
                "contact_value" => $aContact["contact_value"],
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
            "contact_type_name" => $aContact["contact_type_name"],
            "contact_value" => $aContact["contact_value"],
            "contact_display_value" => $sContactDisplayValue,
            "note" => (string)$aContact["note"],
            "is_primary" => (int)$aContact["is_primary"],
            "contact_is_active" => (int)$aContact["contact_is_active"],
            "phone_book_contact" => (int)$aContact["phone_book_contact"]
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

function demoFullListLower($sValue) {
    return function_exists("mb_strtolower") ? mb_strtolower($sValue, "UTF-8") : strtolower($sValue);
}

function demoFullListJoinContacts($aContacts) {
    $aValues = array();
    foreach ($aContacts as $aContact) {
        $sValue = contactTypeLabel($aContact["contact_type"]) . ": " . $aContact["contact_value"];
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
        $sValue = $aNickname["nickname"];
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
        $aValues[] = $aGroup["name"];
    }
    return implode("\n", $aValues);
}

function demoFullListJoinNotes($aNotes) {
    $aValues = array();
    foreach ($aNotes as $aNote) {
        $aValues[] = $aNote["note_text"];
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
    if (isset($aField["value_type"]) && $aField["value_type"] == "boolean") {
        $sNormalized = strtolower(trim($sValue));
        if ($sNormalized == "0" || $sNormalized == "false" || $sNormalized == "no" || $sNormalized == "off") {
            return "0";
        }
        return "1";
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "birth_number") {
        $sNormalized = normalizeBirthNumber($sValue);
        return $sNormalized === false ? $sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "date") {
        $sNormalized = normalizeInputDate($sValue);
        return $sNormalized === false ? $sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "datetime") {
        $sNormalized = normalizeInputDateTime($sValue);
        return $sNormalized === false ? $sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "country") {
        return countryNameToCode($sValue);
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "subject_type") {
        $sNormalized = strtolower(trim($sValue));
        foreach (getSubjectTypes() as $sSubjectType) {
            if ($sNormalized == $sSubjectType || $sNormalized == strtolower(ucfirst($sSubjectType))) {
                return $sSubjectType;
            }
        }
        return $sNormalized;
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "address_type") {
        $sNormalized = strtolower(trim($sValue));
        foreach (getAddressTypes() as $sAddressType) {
            if ($sNormalized == $sAddressType || $sNormalized == strtolower(addressTypeLabel($sAddressType))) {
                return $sAddressType;
            }
        }
        return $sNormalized;
    }
    return $sValue;
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
            } elseif (isset($aFields[$sField]["scope_type"]) && $aFields[$sField]["scope_type"] == "person" && $aRow["subject_type"] != "person") {
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
        $sContactType = trim(isset($aContactType["contact_type"]) ? $aContactType["contact_type"] : "");
        $sContactTypeName = trim(isset($aContactType["name"]) ? $aContactType["name"] : "");
        if ($sContactTypeName == "") {
            $sContactTypeName = trim(isset($aContactType["contact_type"]) ? $aContactType["contact_type"] : "");
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
    if (isset($aField["value_type"]) && $aField["value_type"] == "group") {
        return in_array($sOperator, array("equals", "not_equals", "contains", "not_contains", "empty", "not_empty"), true);
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "country") {
        return in_array($sOperator, array("equals", "not_equals", "contains", "not_contains", "empty", "not_empty"), true);
    }
    if (isset($aField["value_type"]) && ($aField["value_type"] == "address_type" || $aField["value_type"] == "subject_type")) {
        return in_array($sOperator, array("equals", "not_equals", "contains", "not_contains", "empty", "not_empty"), true);
    }
    return true;
}

function getFullListComplexFilterDefaultOperator($aField) {
    if (isset($aField["value_type"]) && ($aField["value_type"] == "boolean" || $aField["value_type"] == "country")) {
        return "equals";
    }
    return "contains";
}

function normalizeFullListComplexFilterInputValue($aField, $sValue) {
    $sNormalized = false;
    if (isset($aField["value_type"]) && $aField["value_type"] == "date") {
        $sNormalized = normalizeInputDate($sValue);
        return $sNormalized !== false ? $sNormalized : $sValue;
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "datetime") {
        $sNormalized = normalizeInputDateTime($sValue);
        return $sNormalized !== false ? $sNormalized : $sValue;
    }
    return $sValue;
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
            if (isset($aFields[$sField]["value_type"]) && $aFields[$sField]["value_type"] == "boolean") {
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
        if (isset($aFields[$sField]["value_type"]) && $aFields[$sField]["value_type"] == "boolean") {
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
            if (isset($aFields[$sField]["value_type"]) && $aFields[$sField]["value_type"] == "boolean") {
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
            if (isset($aFields[$sField]["value_type"]) && $aFields[$sField]["value_type"] == "boolean") {
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
    if (isset($aField["value_type"]) && $aField["value_type"] == "boolean") {
        $sNormalized = strtolower(trim($sValue));
        if ($sNormalized == "0" || $sNormalized == "false" || $sNormalized == "no" || $sNormalized == "off") {
            return "0";
        }
        return "1";
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "birth_number") {
        $sNormalized = normalizeBirthNumber($sValue);
        return $sNormalized === false ? $sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "country") {
        return countryNameToCode($sValue);
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "subject_type") {
        $sNormalized = strtolower(trim($sValue));
        foreach (getSubjectTypes() as $sSubjectType) {
            if ($sNormalized == $sSubjectType) {
                return $sSubjectType;
            }
        }
        return $sNormalized;
    }
    if (isset($aField["value_type"]) && $aField["value_type"] == "address_type") {
        $sNormalized = strtolower(trim($sValue));
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
    return $sValue;
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
        return "(" . $aField["scope_sql"] . " AND " . $sSql . ")";
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
    $aUngroupedOptions = array();
    $aGroupedOptions = array();
    $aGroupOrder = array();
    foreach ($aFields as $sField => $aField) {
        $sLabel = $aField["label"];
        $sOptionLabel = $sLabel;
        $sGroup = "";
        $iColonPosition = strpos($sLabel, ":");
        if ($iColonPosition !== false) {
            $sGroup = trim(substr($sLabel, 0, $iColonPosition));
        }
        $sValueType = isset($aField["value_type"]) ? $aField["value_type"] : "text";
        $sOptionHtml = "<option value=\"" . html($sField) . "\" data-value-type=\"" . html($sValueType) . "\"" . ($sSelected == $sField ? " selected" : "") . ">" . html($sOptionLabel) . "</option>";
        if ($sGroup == "") {
            $aUngroupedOptions[] = $sOptionHtml;
            continue;
        }
        if (!isset($aGroupedOptions[$sGroup])) {
            $aGroupedOptions[$sGroup] = array();
            $aGroupOrder[] = $sGroup;
        }
        $aGroupedOptions[$sGroup][] = $sOptionHtml;
    }
    foreach ($aUngroupedOptions as $sOptionHtml) {
        $sHtml .= $sOptionHtml;
    }
    foreach ($aGroupOrder as $sGroup) {
        $sHtml .= "<optgroup label=\"" . html($sGroup) . "\">";
        foreach ($aGroupedOptions[$sGroup] as $sOptionHtml) {
            $sHtml .= $sOptionHtml;
        }
        $sHtml .= "</optgroup>";
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

function interGetCommunicationInfo($sCommunicationServedAt) {
    $sCommunicationServedAt = trim($sCommunicationServedAt);
    $iTodayTimestamp = strtotime("today 12:00:00");
    if ($iTodayTimestamp === false) {
        return null;
    }
    if ($sCommunicationServedAt == "" || strpos($sCommunicationServedAt, "0000-00-00") === 0) {
        return array(
            "days_to_served" => 0,
            "served_date" => date("Y-m-d", $iTodayTimestamp)
        );
    }
    $iCommunicationServedTimestamp = strtotime($sCommunicationServedAt);
    if ($iCommunicationServedTimestamp === false) {
        return null;
    }
    $iCommunicationDueTimestamp = strtotime("+2 months", $iCommunicationServedTimestamp);
    if ($iCommunicationDueTimestamp === false) {
        return null;
    }
    $sCommunicationDueDate = date("Y-m-d", $iCommunicationDueTimestamp);
    $iCommunicationDueDateTimestamp = strtotime($sCommunicationDueDate . " 12:00:00");
    if ($iCommunicationDueDateTimestamp === false) {
        return null;
    }
    $iDaysToCommunication = (int)round(($iCommunicationDueDateTimestamp - $iTodayTimestamp) / 86400);
    if ($iDaysToCommunication < 0 || $iDaysToCommunication > 20) {
        return null;
    }
    return array(
        "days_to_served" => $iDaysToCommunication,
        "served_date" => $sCommunicationDueDate
    );
}

function interRenderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aInteractionSettings) {
    global $sCommunicationServedEmoji;

    return renderServedSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aInteractionSettings, "js-communication-served", "Mark communication served", $sCommunicationServedEmoji, array(
        "nickname_show_add_action" => true,
        "address_show_add_action" => true,
        "contact_show_add_action" => true,
        "group_show_add_action" => true,
        "note_show_add_action" => true
    ));
}

function interGetSubjectServedInfo($oPdo, $iSubjectId, $aRow) {
    $aCommunicationServedRows = fetchPersonServedRows($oPdo, "inter_served_at");
    $sCommunicationServedAt = isset($aCommunicationServedRows[$iSubjectId]["inter_served_at"]) ? $aCommunicationServedRows[$iSubjectId]["inter_served_at"] : "";
    return interGetCommunicationInfo($sCommunicationServedAt);
}

function interGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aInteractionSettings, $blShowActions) {
    return getUpdatedServedSubjectResponse($oPdo, $iSubjectId, $aInteractionSettings, $blShowActions, "interGetSubjectServedInfo", "interRenderSubjectRow");
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

function diffParseSqlIdentifierList($sSql) {
    $aIdentifiers = array();
    if (preg_match_all("/`((?:``|[^`])*)`/", $sSql, $aMatches)) {
        foreach ($aMatches[1] as $sIdentifier) {
            $aIdentifiers[] = str_replace("``", "`", $sIdentifier);
        }
    }
    return $aIdentifiers;
}

function diffNormalizeCreateSql($sSql) {
    $sSql = trim($sSql);
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
    $sToken = trim($sToken);
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
            $sTableName = str_replace("``", "`", $aMatches[1]);
            if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
                continue;
            }
            diffEnsureDumpTable($aDump, $sTableName);
            $sCreateSql = diffNormalizeCreateSql($sStatement);
            $aDump["tables"][$sTableName]["create"] = $sCreateSql;
            $aDump["tables"][$sTableName]["primary_keys"] = diffGetPrimaryKeyColumns($sCreateSql);
        } elseif (preg_match("/^INSERT\s+INTO\s+`((?:``|[^`])+)`\s*\((.*)\)\s+VALUES\s*\((.*)\)$/is", $sStatement, $aMatches)) {
            $sTableName = str_replace("``", "`", $aMatches[1]);
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
    while ($aRow = $oStatement->fetch()) {
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

function diffGetRowHash($aRow) {
    ksort($aRow, SORT_STRING);
    return sha1(json_encode($aRow));
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

function mailFormStripHeaderBreaks($sValue) {
    return trim(preg_replace("/[\r\n]+/", " ", $sValue));
}

function mailFormNormalizeEmailAddress($sValue) {
    $sEmail = trim($sValue);
    if ($sEmail == "") {
        return "";
    }
    return filter_var($sEmail, FILTER_VALIDATE_EMAIL) !== false ? $sEmail : false;
}

function mailFormIsAddressSeparator($sChar) {
    return $sChar == " " || $sChar == "\t" || $sChar == "," || $sChar == ";";
}

function mailFormReadEmailToken($sValue, $iOffset, &$iNextOffset) {
    $iLength = strlen($sValue);
    $iNextOffset = $iOffset;
    if ($iOffset >= $iLength) {
        return "";
    }
    if ($sValue[$iOffset] == "\"") {
        $iNextOffset++;
        $blEscaped = false;
        while ($iNextOffset < $iLength) {
            $sChar = $sValue[$iNextOffset];
            if ($blEscaped) {
                $blEscaped = false;
            } elseif ($sChar == "\\") {
                $blEscaped = true;
            } elseif ($sChar == "\"") {
                $iNextOffset++;
                break;
            }
            $iNextOffset++;
        }
        if ($iNextOffset >= $iLength || $sValue[$iNextOffset] != "@") {
            return "";
        }
        $iNextOffset++;
        while ($iNextOffset < $iLength && !mailFormIsAddressSeparator($sValue[$iNextOffset])) {
            $iNextOffset++;
        }
        return substr($sValue, $iOffset, $iNextOffset - $iOffset);
    }
    while ($iNextOffset < $iLength && !mailFormIsAddressSeparator($sValue[$iNextOffset])) {
        $iNextOffset++;
    }
    return substr($sValue, $iOffset, $iNextOffset - $iOffset);
}

function mailFormFindUnquotedChar($sValue, $sFind, $iOffset) {
    $iLength = strlen($sValue);
    $blQuoted = false;
    $blEscaped = false;
    while ($iOffset < $iLength) {
        $sChar = $sValue[$iOffset];
        if ($blEscaped) {
            $blEscaped = false;
        } elseif ($sChar == "\\") {
            $blEscaped = true;
        } elseif ($sChar == "\"") {
            $blQuoted = !$blQuoted;
        } elseif (!$blQuoted && $sChar == $sFind) {
            return $iOffset;
        }
        $iOffset++;
    }
    return false;
}

function mailFormFindUnquotedCharBeforeListSeparator($sValue, $sFind, $iOffset) {
    $iLength = strlen($sValue);
    $blQuoted = false;
    $blEscaped = false;
    while ($iOffset < $iLength) {
        $sChar = $sValue[$iOffset];
        if ($blEscaped) {
            $blEscaped = false;
        } elseif ($sChar == "\\") {
            $blEscaped = true;
        } elseif ($sChar == "\"") {
            $blQuoted = !$blQuoted;
        } elseif (!$blQuoted && ($sChar == "," || $sChar == ";")) {
            return false;
        } elseif (!$blQuoted && $sChar == $sFind) {
            return $iOffset;
        }
        $iOffset++;
    }
    return false;
}

function mailFormCleanDisplayName($sValue) {
    $sName = trim($sValue);
    $iLength = strlen($sName);
    if ($sName == "") {
        return "";
    }
    if (preg_match("/[\x00-\x1F\x7F]/", $sName) || strpos($sName, "<") !== false || strpos($sName, ">") !== false) {
        return false;
    }
    if ($iLength >= 2 && $sName[0] == "\"" && $sName[$iLength - 1] == "\"") {
        $sName = substr($sName, 1, -1);
        $sName = str_replace(array("\\\\", "\\\""), array("\\", "\""), $sName);
    }
    $sName = trim(preg_replace("/[ \t]+/", " ", $sName));
    return $sName;
}

function mailFormParseMailbox($sValue, $iOffset, &$iNextOffset) {
    $iLength = strlen($sValue);
    $iTokenEnd = $iOffset;
    $sToken = "";
    $sEmail = "";
    $sName = "";
    $iOpen = false;
    $iClose = false;
    $iProbe = $iOffset;

    $iNextOffset = $iOffset;
    if ($iOffset >= $iLength) {
        return false;
    }
    if ($sValue[$iOffset] == "<") {
        $iClose = mailFormFindUnquotedChar($sValue, ">", $iOffset + 1);
        if ($iClose === false) {
            return false;
        }
        $sToken = trim(substr($sValue, $iOffset + 1, $iClose - $iOffset - 1));
        $iProbe = $iClose + 1;
        while ($iProbe < $iLength && ($sValue[$iProbe] == " " || $sValue[$iProbe] == "\t")) {
            $iProbe++;
        }
        if ($iProbe >= $iLength || $sValue[$iProbe] == "," || $sValue[$iProbe] == ";") {
            $sEmail = mailFormNormalizeEmailAddress($sToken);
            if ($sEmail === false || $sEmail == "") {
                return false;
            }
            $iNextOffset = $iClose + 1;
            return array("name" => "", "email" => $sEmail);
        }
        $sName = mailFormCleanDisplayName($sToken);
        if ($sName === false) {
            return false;
        }
        $sEmail = mailFormNormalizeEmailAddress(mailFormReadEmailToken($sValue, $iProbe, $iTokenEnd));
        if ($sEmail === false || $sEmail == "") {
            return false;
        }
        $iNextOffset = $iTokenEnd;
        return array("name" => $sName, "email" => $sEmail);
    }

    $sToken = mailFormReadEmailToken($sValue, $iOffset, $iTokenEnd);
    $sEmail = mailFormNormalizeEmailAddress($sToken);
    if ($sEmail !== false && $sEmail != "") {
        $iNextOffset = $iTokenEnd;
        return array("name" => "", "email" => $sEmail);
    }

    $iOpen = mailFormFindUnquotedCharBeforeListSeparator($sValue, "<", $iOffset);
    if ($iOpen === false) {
        return false;
    }
    $iClose = mailFormFindUnquotedChar($sValue, ">", $iOpen + 1);
    if ($iClose === false) {
        return false;
    }
    $sName = mailFormCleanDisplayName(substr($sValue, $iOffset, $iOpen - $iOffset));
    $sEmail = mailFormNormalizeEmailAddress(trim(substr($sValue, $iOpen + 1, $iClose - $iOpen - 1)));
    if ($sName === false || $sEmail === false || $sEmail == "") {
        return false;
    }
    $iNextOffset = $iClose + 1;
    return array("name" => $sName, "email" => $sEmail);
}

function mailFormEncodeDisplayName($sValue) {
    $sName = trim(preg_replace("/[ \t]+/", " ", $sValue));
    if ($sName == "") {
        return "";
    }
    if (preg_match("/[^\x20-\x7E]/", $sName)) {
        return "=?UTF-8?B?" . base64_encode($sName) . "?=";
    }
    if (preg_match("/[()<>@,;:\\\\\".\[\]]/", $sName)) {
        return "\"" . str_replace(array("\\", "\""), array("\\\\", "\\\""), $sName) . "\"";
    }
    return $sName;
}

function mailFormFormatMailbox($aMailbox) {
    $sName = isset($aMailbox["name"]) ? mailFormEncodeDisplayName($aMailbox["name"]) : "";
    $sEmail = isset($aMailbox["email"]) ? (string)$aMailbox["email"] : "";
    return $sName != "" ? $sName . " <" . $sEmail . ">" : $sEmail;
}

function mailFormNormalizeEmailList($sValue) {
    $aMailboxes = array();
    $aHeaderMailboxes = array();
    $iLength = strlen($sValue);
    $iOffset = 0;
    $iNextOffset = 0;
    $aMailbox = array();

    if (preg_match("/[\x00-\x1F\x7F]/", $sValue)) {
        return false;
    }
    while ($iOffset < $iLength) {
        while ($iOffset < $iLength && mailFormIsAddressSeparator($sValue[$iOffset])) {
            $iOffset++;
        }
        if ($iOffset >= $iLength) {
            break;
        }
        $aMailbox = mailFormParseMailbox($sValue, $iOffset, $iNextOffset);
        if ($aMailbox === false || $iNextOffset <= $iOffset) {
            return false;
        }
        $aMailboxes[] = $aMailbox;
        $iOffset = $iNextOffset;
        if ($iOffset < $iLength && !mailFormIsAddressSeparator($sValue[$iOffset])) {
            return false;
        }
    }
    foreach ($aMailboxes as $aMailbox) {
        $aHeaderMailboxes[] = mailFormFormatMailbox($aMailbox);
    }
    return array(
        "count" => count($aMailboxes),
        "header" => implode(", ", $aHeaderMailboxes),
        "mailboxes" => $aMailboxes
    );
}

function mailFormNormalizeSingleEmail($sValue) {
    $aList = mailFormNormalizeEmailList($sValue);
    if ($aList === false || $aList["count"] > 1) {
        return false;
    }
    return $aList;
}

function mailFormEmailDomain($sEmail) {
    $iAt = strrpos($sEmail, "@");
    if ($iAt === false) {
        return "";
    }
    return strtolower(substr($sEmail, $iAt + 1));
}

function mailFormAllowedSenderDomainMap($aAllowedDomains) {
    $aDomainMap = array();
    foreach ($aAllowedDomains as $sDomain) {
        $sDomain = strtolower(trim($sDomain));
        if ($sDomain != "") {
            $aDomainMap[$sDomain] = true;
        }
    }
    return $aDomainMap;
}

function mailFormFetchSenderData($oPdo, $aAdditionalSenderDomains) {
    $aDomainMap = mailFormAllowedSenderDomainMap($aAdditionalSenderDomains);
    $aAddressMap = array();
    $oStatement = $oPdo->query("SELECT d.domain, u.local_part FROM fs_email_domains AS d LEFT JOIN fs_email_domain_users AS u ON u.email_domain_id = d.id ORDER BY d.domain ASC, u.local_part ASC");
    while ($aRow = $oStatement->fetch()) {
        $sDomain = strtolower(trim((string)$aRow["domain"]));
        if ($sDomain == "") {
            continue;
        }
        $aDomainMap[$sDomain] = true;
        if ($aRow["local_part"] === null) {
            continue;
        }
        $sEmail = mailFormNormalizeEmailAddress(trim((string)$aRow["local_part"]) . "@" . $sDomain);
        if ($sEmail !== false && $sEmail != "") {
            $aAddressMap[strtolower($sEmail)] = $sEmail;
        }
    }
    ksort($aDomainMap, SORT_STRING);
    ksort($aAddressMap, SORT_STRING);
    return array(
        "allowed_domains" => array_keys($aDomainMap),
        "account_addresses" => array_values($aAddressMap)
    );
}

function mailFormEmailListUsesAllowedSenderDomains($aEmailList, $aAllowedDomains) {
    $aDomainMap = mailFormAllowedSenderDomainMap($aAllowedDomains);
    $sDomain = "";
    if ($aEmailList === false) {
        return false;
    }
    foreach ($aEmailList["mailboxes"] as $aMailbox) {
        $sDomain = mailFormEmailDomain($aMailbox["email"]);
        if ($sDomain == "" || !isset($aDomainMap[$sDomain])) {
            return false;
        }
    }
    return true;
}

function mailFormFetchRecipientSuggestions($oPdo, $sTerm, $iLimit = 12, $aAllowedDomains = null, $aAdditionalAddresses = array()) {
    $aSuggestions = array();
    $aSeen = array();
    $aSeenEmails = array();
    $aParams = array();
    $aAllowedDomainMap = array();
    $aDomainPlaceholders = array();
    $sDomainSql = "";
    $sParam = "";
    $iDomain = 0;
    $sTerm = trim(preg_replace("/[<>]+/", " ", $sTerm));
    if (strlen($sTerm) < 3) {
        return $aSuggestions;
    }
    $iLimit = (int)$iLimit;
    if ($iLimit < 1) {
        $iLimit = 12;
    }
    if ($iLimit > 30) {
        $iLimit = 30;
    }
    if (is_array($aAllowedDomains)) {
        $aAllowedDomainMap = mailFormAllowedSenderDomainMap($aAllowedDomains);
        if (!$aAllowedDomainMap) {
            return $aSuggestions;
        }
        foreach ($aAllowedDomainMap as $sDomain => $blAllowed) {
            $sParam = "domain_" . $iDomain;
            $aDomainPlaceholders[] = ":" . $sParam;
            $aParams[$sParam] = $sDomain;
            $iDomain++;
        }
        $sDomainSql = " AND LOWER(SUBSTRING_INDEX(c.contact_value, '@', -1)) IN (" . implode(", ", $aDomainPlaceholders) . ")";
    }
    $sLike = "%" . strtr($sTerm, array("!" => "!!", "%" => "!%", "_" => "!_")) . "%";
    $sSql = "SELECT subject_rows.subject_id, subject_rows.subject_name, subject_rows.subject_sort_name, c.contact_value AS email FROM (" . getSubjectNameSelectSql() . ") AS subject_rows INNER JOIN ex_subjects AS s ON s.id = subject_rows.subject_id AND s.is_active = 1 INNER JOIN ex_subject_contacts AS sc ON sc.subject_id = subject_rows.subject_id INNER JOIN ex_contacts AS c ON c.id = sc.contact_id INNER JOIN ex_contact_types AS ct ON ct.id = c.contact_type_id WHERE ct.contact_type = 'email' AND sc.is_active = 1 AND c.contact_value <> ''" . $sDomainSql . " AND (LOWER(subject_rows.subject_name) LIKE LOWER(:subject_name_term) ESCAPE '!' OR LOWER(subject_rows.subject_sort_name) LIKE LOWER(:subject_sort_name_term) ESCAPE '!' OR LOWER(c.contact_value) LIKE LOWER(:email_term) ESCAPE '!') ORDER BY subject_rows.subject_sort_name COLLATE utf8mb4_czech_ci ASC, sc.is_primary DESC, c.contact_value ASC, sc.id ASC LIMIT " . $iLimit;
    $oStatement = $oPdo->prepare($sSql);
    $aParams["subject_name_term"] = $sLike;
    $aParams["subject_sort_name_term"] = $sLike;
    $aParams["email_term"] = $sLike;
    $oStatement->execute($aParams);
    while ($aRow = $oStatement->fetch()) {
        $sName = mailFormCleanDisplayName($aRow["subject_name"]);
        $sEmail = mailFormNormalizeEmailAddress($aRow["email"]);
        $sKey = strtolower($sEmail) . "\n" . strtolower($sName);
        if ($sName === false || $sEmail === false || $sEmail == "" || isset($aSeen[$sKey])) {
            continue;
        }
        if (is_array($aAllowedDomains) && !isset($aAllowedDomainMap[mailFormEmailDomain($sEmail)])) {
            continue;
        }
        $aSeen[$sKey] = true;
        $aSeenEmails[strtolower($sEmail)] = true;
        $aSuggestions[] = array(
            "subject_id" => (int)$aRow["subject_id"],
            "name" => $sName,
            "email" => $sEmail,
            "value" => $sName != "" ? "<" . $sName . "> " . $sEmail : $sEmail
        );
    }
    foreach ($aAdditionalAddresses as $sAdditionalAddress) {
        if (count($aSuggestions) >= $iLimit) {
            break;
        }
        $sEmail = mailFormNormalizeEmailAddress($sAdditionalAddress);
        $sEmailKey = strtolower($sEmail);
        if ($sEmail === false || $sEmail == "" || isset($aSeenEmails[$sEmailKey]) || stripos($sEmail, $sTerm) === false) {
            continue;
        }
        if (is_array($aAllowedDomains) && !isset($aAllowedDomainMap[mailFormEmailDomain($sEmail)])) {
            continue;
        }
        $aSeenEmails[$sEmailKey] = true;
        $aSuggestions[] = array(
            "subject_id" => 0,
            "name" => "",
            "email" => $sEmail,
            "value" => $sEmail
        );
    }
    return $aSuggestions;
}

function mailFormEncodeHeader($sValue) {
    $sValue = mailFormStripHeaderBreaks($sValue);
    if ($sValue == "") {
        return "";
    }
    if (preg_match("/[^\x20-\x7E]/", $sValue)) {
        return "=?UTF-8?B?" . base64_encode($sValue) . "?=";
    }
    return $sValue;
}

function mailFormEncodeMimeParameter($sValue) {
    $sValue = mailFormStripHeaderBreaks($sValue);
    if ($sValue == "") {
        $sValue = "attachment";
    }
    if (preg_match("/[^\x20-\x7E]/", $sValue)) {
        return "=?UTF-8?B?" . base64_encode($sValue) . "?=";
    }
    return str_replace(array("\\", "\""), array("\\\\", "\\\""), $sValue);
}

function mailFormHtmlBodyIsEmpty($sValue) {
    $sHtml = preg_replace("/<\s*(script|style)[^>]*>.*?<\s*\/\s*\\1\s*>/is", "", $sValue);
    if (preg_match("/<\s*img\b/i", $sHtml)) {
        return false;
    }
    $sText = html_entity_decode(strip_tags($sHtml), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    return trim($sText) == "";
}

function mailFormUploadedAttachments($sFieldName, &$aErrors) {
    $aAttachments = array();
    $aUpload = isset($_FILES[$sFieldName]) && is_array($_FILES[$sFieldName]) ? $_FILES[$sFieldName] : null;
    if (!$aUpload || !isset($aUpload["name"], $aUpload["type"], $aUpload["tmp_name"], $aUpload["error"], $aUpload["size"])) {
        return $aAttachments;
    }
    $aNames = is_array($aUpload["name"]) ? $aUpload["name"] : array($aUpload["name"]);
    $aTypes = is_array($aUpload["type"]) ? $aUpload["type"] : array($aUpload["type"]);
    $aTmpNames = is_array($aUpload["tmp_name"]) ? $aUpload["tmp_name"] : array($aUpload["tmp_name"]);
    $aUploadErrors = is_array($aUpload["error"]) ? $aUpload["error"] : array($aUpload["error"]);
    $aSizes = is_array($aUpload["size"]) ? $aUpload["size"] : array($aUpload["size"]);
    foreach ($aNames as $iIndex => $sName) {
        $iError = isset($aUploadErrors[$iIndex]) ? (int)$aUploadErrors[$iIndex] : UPLOAD_ERR_NO_FILE;
        $sTmpName = isset($aTmpNames[$iIndex]) ? (string)$aTmpNames[$iIndex] : "";
        if ($iError == UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($iError != UPLOAD_ERR_OK || $sTmpName == "" || !is_uploaded_file($sTmpName)) {
            $aErrors[] = $iError == UPLOAD_ERR_INI_SIZE || $iError == UPLOAD_ERR_FORM_SIZE ? "Attachment too large." : "Attachment upload failed.";
            continue;
        }
        $sContent = file_get_contents($sTmpName);
        if ($sContent === false) {
            $aErrors[] = "Attachment upload failed.";
            continue;
        }
        $sAttachmentName = trim(basename(str_replace("\\", "/", mailFormStripHeaderBreaks($sName))));
        $sAttachmentType = strtolower(trim(mailFormStripHeaderBreaks(isset($aTypes[$iIndex]) ? $aTypes[$iIndex] : "")));
        $aAttachments[] = array(
            "name" => $sAttachmentName != "" ? $sAttachmentName : "attachment",
            "type" => preg_match("~^[a-z0-9][a-z0-9!#$&^_.+\\-]*/[a-z0-9][a-z0-9!#$&^_.+\\-]*$~", $sAttachmentType) ? $sAttachmentType : "application/octet-stream",
            "size" => isset($aSizes[$iIndex]) ? (int)$aSizes[$iIndex] : strlen($sContent),
            "content" => $sContent
        );
    }
    return $aAttachments;
}

function mailFormNormalizeBodyLineEndings($sValue) {
    return preg_replace("/\r\n|\r|\n/", "\r\n", $sValue);
}

function mailFormBuildPlainTextMessage($sBody) {
    $sText = $sBody;
    $sText = preg_replace("/<\s*(script|style)[^>]*>.*?<\s*\/\s*\\1\s*>/is", "", $sText);
    $sText = preg_replace("/<\s*br\s*\/?\s*>/i", "\n", $sText);
    $sText = preg_replace("/<\s*\/\s*(p|div|h[1-6]|li|tr|blockquote|pre|table|ul|ol)\s*>/i", "\n", $sText);
    $sText = preg_replace("/<\s*\/\s*(td|th)\s*>/i", "\t", $sText);
    $sText = strip_tags($sText);
    $sText = html_entity_decode($sText, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sText = str_replace("\xc2\xa0", " ", $sText);
    $sText = preg_replace("/\r\n|\r/", "\n", $sText);
    $aLines = explode("\n", $sText);
    foreach ($aLines as $iLine => $sLine) {
        $aLines[$iLine] = trim(preg_replace("/[ \t]+/", " ", $sLine));
    }
    $sText = implode("\n", $aLines);
    $sText = preg_replace("/\n{3,}/", "\n\n", $sText);
    return mailFormNormalizeBodyLineEndings(trim($sText));
}

function mailFormBuildHtmlMessage($sSubject, $sBody) {
    return "<!DOCTYPE html>\r\n"
        . "<html lang=\"en-US\">\r\n"
        . "<head>\r\n"
        . "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\r\n"
        . "  <title>" . html($sSubject) . "</title>\r\n"
        . "</head>\r\n"
        . "<body>\r\n"
        . mailFormNormalizeBodyLineEndings($sBody) . "\r\n"
        . "</body>\r\n"
        . "</html>\r\n";
}

function mailFormBuildMultipartAlternativeMessage($sSubject, $sBody, $sBoundary) {
    return "--" . $sBoundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n"
        . "\r\n"
        . mailFormBuildPlainTextMessage($sBody) . "\r\n"
        . "\r\n"
        . "--" . $sBoundary . "\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n"
        . "\r\n"
        . mailFormBuildHtmlMessage($sSubject, $sBody)
        . "\r\n"
        . "--" . $sBoundary . "--\r\n";
}

function mailFormBuildTextMessagePart($sBody, $sBoundary) {
    return "--" . $sBoundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n"
        . "\r\n"
        . mailFormBuildPlainTextMessage($sBody) . "\r\n"
        . "\r\n";
}

function mailFormBuildAlternativeMessagePart($sSubject, $sBody, $sBoundary, $sAlternativeBoundary) {
    return "--" . $sBoundary . "\r\n"
        . "Content-Type: multipart/alternative; boundary=\"" . $sAlternativeBoundary . "\"\r\n"
        . "\r\n"
        . mailFormBuildMultipartAlternativeMessage($sSubject, $sBody, $sAlternativeBoundary)
        . "\r\n";
}

function mailFormBuildAttachmentPart($aAttachment, $sBoundary) {
    $sFileName = mailFormEncodeMimeParameter($aAttachment["name"]);
    return "--" . $sBoundary . "\r\n"
        . "Content-Type: " . $aAttachment["type"] . "; name=\"" . $sFileName . "\"\r\n"
        . "Content-Transfer-Encoding: base64\r\n"
        . "Content-Disposition: attachment; filename=\"" . $sFileName . "\"\r\n"
        . "\r\n"
        . chunk_split(base64_encode($aAttachment["content"]), 76, "\r\n")
        . "\r\n";
}

function mailFormBuildMixedMessage($sSubject, $sBody, $sBodyFormat, $aAttachments, $sBoundary) {
    $sMessage = "";
    if ($sBodyFormat == "plain") {
        $sMessage .= mailFormBuildTextMessagePart($sBody, $sBoundary);
    } else {
        $sAlternativeBoundary = "=_ex_mail_alt_" . md5(uniqid("", true));
        $sMessage .= mailFormBuildAlternativeMessagePart($sSubject, $sBody, $sBoundary, $sAlternativeBoundary);
    }
    foreach ($aAttachments as $aAttachment) {
        $sMessage .= mailFormBuildAttachmentPart($aAttachment, $sBoundary);
    }
    return $sMessage . "--" . $sBoundary . "--\r\n";
}

function mailFormSendMessage($sTo, $sCc, $sBcc, $sFrom, $sSender, $sReplyTo, $sSubject, $sBody, $sBodyFormat, $aAttachments) {
    $aHeaders = array();
    $sMessage = "";
    $sBoundary = "";
    if ($sFrom != "") {
        $aHeaders[] = "From: " . $sFrom;
    }
    if ($sSender != "") {
        $aHeaders[] = "Sender: " . $sSender;
    }
    if ($sReplyTo != "") {
        $aHeaders[] = "Reply-To: " . $sReplyTo;
    }
    if ($sCc != "") {
        $aHeaders[] = "Cc: " . $sCc;
    }
    if ($sBcc != "") {
        $aHeaders[] = "Bcc: " . $sBcc;
    }
    $aHeaders[] = "MIME-Version: 1.0";
    if ($aAttachments) {
        $sBoundary = "=_ex_mail_mixed_" . md5(uniqid("", true));
        $aHeaders[] = "Content-Type: multipart/mixed; boundary=\"" . $sBoundary . "\"";
        $sMessage = mailFormBuildMixedMessage($sSubject, $sBody, $sBodyFormat, $aAttachments, $sBoundary);
    } elseif ($sBodyFormat == "plain") {
        $aHeaders[] = "Content-Type: text/plain; charset=UTF-8";
        $aHeaders[] = "Content-Transfer-Encoding: 8bit";
        $sMessage = mailFormBuildPlainTextMessage($sBody);
    } else {
        $sBoundary = "=_ex_mail_" . md5(uniqid("", true));
        $aHeaders[] = "Content-Type: multipart/alternative; boundary=\"" . $sBoundary . "\"";
        $sMessage = mailFormBuildMultipartAlternativeMessage($sSubject, $sBody, $sBoundary);
    }
    $aHeaders[] = "X-Mailer: PHP/" . phpversion();
    return mail($sTo, mailFormEncodeHeader($sSubject), $sMessage, implode("\r\n", $aHeaders));
}
