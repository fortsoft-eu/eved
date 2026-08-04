<?php

include "main.php";


function mailFormStripHeaderBreaks($sValue) {
    return trim(preg_replace("/[\r\n]+/", " ", (string)$sValue));
}

function mailFormHasHeaderControlChars($sValue) {
    return preg_match("/[\x00-\x1F\x7F]/", (string)$sValue) ? true : false;
}

function mailFormAddError(&$aErrors, $sMessage) {
    $aErrors[] = $sMessage;
}

function mailFormNormalizeEmailAddress($sValue) {
    $sEmail = trim((string)$sValue);
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
    $sName = trim((string)$sValue);
    $iLength = strlen($sName);
    if ($sName == "") {
        return "";
    }
    if (mailFormHasHeaderControlChars($sName) || strpos($sName, "<") !== false || strpos($sName, ">") !== false) {
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
    $sName = trim(preg_replace("/[ \t]+/", " ", (string)$sValue));
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
    $sValue = (string)$sValue;
    $iLength = strlen($sValue);
    $iOffset = 0;
    $iNextOffset = 0;
    $aMailbox = array();

    if (mailFormHasHeaderControlChars($sValue)) {
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
    $iAt = strrpos((string)$sEmail, "@");
    if ($iAt === false) {
        return "";
    }
    return strtolower(substr((string)$sEmail, $iAt + 1));
}

function mailFormAllowedSenderDomainMap($aAllowedDomains) {
    $aDomainMap = array();
    foreach ($aAllowedDomains as $sDomain) {
        $sDomain = strtolower(trim((string)$sDomain));
        if ($sDomain != "") {
            $aDomainMap[$sDomain] = true;
        }
    }
    return $aDomainMap;
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

function mailFormHtmlBodyIsEmpty($sValue) {
    $sHtml = preg_replace("/<\s*(script|style)[^>]*>.*?<\s*\/\s*\\1\s*>/is", "", (string)$sValue);
    if (preg_match("/<\s*img\b/i", $sHtml)) {
        return false;
    }
    $sText = html_entity_decode(strip_tags($sHtml), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    return trim($sText) == "";
}

function mailFormNormalizeBodyLineEndings($sValue) {
    return preg_replace("/\r\n|\r|\n/", "\r\n", (string)$sValue);
}

function mailFormBuildPlainTextMessage($sBody) {
    $sText = (string)$sBody;
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

function mailFormSendMessage($sTo, $sCc, $sBcc, $sFrom, $sSender, $sReplyTo, $sSubject, $sBody, $sBodyFormat) {
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
    if ($sBodyFormat == "plain") {
        $aHeaders[] = "Content-Type: text/plain; charset=UTF-8";
        $aHeaders[] = "Content-Transfer-Encoding: 8bit";
        $sMessage = mailFormBuildPlainTextMessage($sBody);
    } else {
        $sBoundary = "=_lm_mail_" . md5(uniqid("", true));
        $aHeaders[] = "Content-Type: multipart/alternative; boundary=\"" . $sBoundary . "\"";
        $sMessage = mailFormBuildMultipartAlternativeMessage($sSubject, $sBody, $sBoundary);
    }
    $aHeaders[] = "X-Mailer: PHP/" . phpversion();
    return mail($sTo, mailFormEncodeHeader($sSubject), $sMessage, implode("\r\n", $aHeaders));
}


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
$sMailRichTextPasteSessionKey = "lm_mail_rich_text_paste";
$sMailStatusSessionKey = "lm_mail_status";
$sMailStatusClassSessionKey = "lm_mail_status_class";
$sMailValuesSessionKey = "lm_mail_values";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "lm_csrf_token", $blJsonResponse);


$iMailRichTextPaste = isset($_SESSION[$sMailRichTextPasteSessionKey]) && (string)$_SESSION[$sMailRichTextPasteSessionKey] == "1" ? 1 : 0;
$_SESSION[$sMailRichTextPasteSessionKey] = (string)$iMailRichTextPaste;
$aMailValues = array(
    "to" => "",
    "cc" => "",
    "bcc" => "",
    "from" => "",
    "sender" => "",
    "reply_to" => "",
    "subject" => "",
    "message" => ""
);
$sMailStatus = "";
$sMailStatusClass = "";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    if (isset($_SESSION[$sMailValuesSessionKey]) && is_array($_SESSION[$sMailValuesSessionKey])) {
        foreach ($aMailValues as $sKey => $sValue) {
            if (isset($_SESSION[$sMailValuesSessionKey][$sKey])) {
                $aMailValues[$sKey] = (string)$_SESSION[$sMailValuesSessionKey][$sKey];
            }
        }
    }
    if (isset($_SESSION[$sMailStatusSessionKey])) {
        $sMailStatus = (string)$_SESSION[$sMailStatusSessionKey];
    }
    if (isset($_SESSION[$sMailStatusClassSessionKey])) {
        $sMailStatusClass = (string)$_SESSION[$sMailStatusClassSessionKey];
    }
    unset($_SESSION[$sMailValuesSessionKey]);
    unset($_SESSION[$sMailStatusSessionKey]);
    unset($_SESSION[$sMailStatusClassSessionKey]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("lm_csrf_token", $blJsonResponse);
    if (getPostedValue("action") == "save_mail_rich_text_paste") {
        $iMailRichTextPaste = getPostedValue("rich_text_paste") == "1" ? 1 : 0;
        $_SESSION[$sMailRichTextPasteSessionKey] = (string)$iMailRichTextPaste;
        sendJsonAndExit(array("success" => true, "rich_text_paste" => $iMailRichTextPaste));
    }
    if (getPostedValue("action") == "send_mail") {
        $iMailRichTextPaste = getPostedValue("mail_rich_text_paste") == "1" ? 1 : 0;
        $sMailBodyFormat = getPostedValue("mail_body_format") == "plain" ? "plain" : "html";
        $_SESSION[$sMailRichTextPasteSessionKey] = (string)$iMailRichTextPaste;
        $aMailValues["to"] = getPostedTrimmedValue("mail_to");
        $aMailValues["cc"] = getPostedTrimmedValue("mail_cc");
        $aMailValues["bcc"] = getPostedTrimmedValue("mail_bcc");
        $aMailValues["from"] = getPostedTrimmedValue("mail_from");
        $aMailValues["sender"] = getPostedTrimmedValue("mail_sender");
        $aMailValues["reply_to"] = getPostedTrimmedValue("mail_reply_to");
        $aMailValues["subject"] = getPostedTrimmedValue("mail_subject");
        $aMailValues["message"] = getPostedValue("mail_message");

        $aErrors = array();
        $aTo = mailFormNormalizeEmailList($aMailValues["to"]);
        $aCc = mailFormNormalizeEmailList($aMailValues["cc"]);
        $aBcc = mailFormNormalizeEmailList($aMailValues["bcc"]);
        $aFrom = mailFormNormalizeEmailList($aMailValues["from"]);
        $aSender = mailFormNormalizeSingleEmail($aMailValues["sender"]);
        $aReplyTo = mailFormNormalizeEmailList($aMailValues["reply_to"]);
        $iRecipientCount = ($aTo !== false ? $aTo["count"] : 0) + ($aCc !== false ? $aCc["count"] : 0) + ($aBcc !== false ? $aBcc["count"] : 0);
        $sTo = $aTo !== false ? $aTo["header"] : "";
        $sCc = $aCc !== false ? $aCc["header"] : "";
        $sBcc = $aBcc !== false ? $aBcc["header"] : "";
        $sFrom = $aFrom !== false ? $aFrom["header"] : "";
        $sSender = $aSender !== false ? $aSender["header"] : "";
        $sReplyTo = $aReplyTo !== false ? $aReplyTo["header"] : "";
        $sSubject = mailFormStripHeaderBreaks($aMailValues["subject"]);
        if ($sReplyTo == "" && $sFrom != "") {
            $sReplyTo = $sFrom;
        }
        if ($aMailValues["to"] != "" && $aTo === false) {
            mailFormAddError($aErrors, "Invalid To.");
        }
        if ($aMailValues["cc"] != "" && $aCc === false) {
            mailFormAddError($aErrors, "Invalid carbon copy.");
        }
        if ($aMailValues["bcc"] != "" && $aBcc === false) {
            mailFormAddError($aErrors, "Invalid blind copy.");
        }
        if ($iRecipientCount < 1 && $aMailValues["to"] == "" && $aMailValues["cc"] == "" && $aMailValues["bcc"] == "") {
            mailFormAddError($aErrors, "Recipient required.");
        }
        if ($aMailValues["from"] != "" && $aFrom === false) {
            mailFormAddError($aErrors, "Invalid From.");
        }
        if ($aMailValues["from"] != "" && $aFrom !== false && !mailFormEmailListUsesAllowedSenderDomains($aFrom, $aMailAllowedSenderDomains)) {
            mailFormAddError($aErrors, "Invalid From domain.");
        }
        if ($aMailValues["sender"] != "" && $aSender === false) {
            mailFormAddError($aErrors, "Invalid Sender.");
        }
        if ($aMailValues["sender"] != "" && $aSender !== false && !mailFormEmailListUsesAllowedSenderDomains($aSender, $aMailAllowedSenderDomains)) {
            mailFormAddError($aErrors, "Invalid Sender domain.");
        }
        if ($aFrom !== false && $aFrom["count"] > 1 && $blMailRestrictFromToSingleAddress) {
            mailFormAddError($aErrors, "Single From required.");
        }
        if ($aFrom !== false && $aFrom["count"] > 1 && !$blMailRestrictFromToSingleAddress && $aMailValues["sender"] == "") {
            mailFormAddError($aErrors, "Sender required.");
        }
        if ($aMailValues["reply_to"] != "" && $aReplyTo === false) {
            mailFormAddError($aErrors, "Invalid Reply-To.");
        }
        $blMailBodyIsEmpty = $sMailBodyFormat == "plain" ? mailFormBuildPlainTextMessage($aMailValues["message"]) == "" : mailFormHtmlBodyIsEmpty($aMailValues["message"]);
        if ($blMailBodyIsEmpty) {
            mailFormAddError($aErrors, "Message required.");
        }
        if (!$aErrors && !function_exists("mail")) {
            mailFormAddError($aErrors, "Mail unavailable.");
        }
        if ($aErrors) {
            $sMailStatus = isDesktop() ? implode(" ", $aErrors) : (string)$aErrors[0];
            $sMailStatusClass = "message-error";
        } elseif (mailFormSendMessage($sTo, $sCc, $sBcc, $sFrom, $sSender, $sReplyTo, $sSubject, $aMailValues["message"], $sMailBodyFormat)) {
            $sMailStatus = "E-mail sent.";
            $sMailStatusClass = "message-success";
            $aMailValues["to"] = "";
            $aMailValues["cc"] = "";
            $aMailValues["bcc"] = "";
            $aMailValues["from"] = "";
            $aMailValues["sender"] = "";
            $aMailValues["reply_to"] = "";
            $aMailValues["subject"] = "";
            $aMailValues["message"] = "";
        } else {
            $sMailStatus = "Not sent.";
            $sMailStatusClass = "message-error";
        }
        $_SESSION[$sMailStatusSessionKey] = $sMailStatus;
        $_SESSION[$sMailStatusClassSessionKey] = $sMailStatusClass;
        $_SESSION[$sMailValuesSessionKey] = $aMailValues;
        sendSecurityHeaders();
        header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
        exit;
    }
}

$iTime = sendPageHeaders();


?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr" class="mail-html" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Cervinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("lm_csrf_token")); ?>">
  <link rel="icon" href="<?php echo html($sBaseUrl . "favicon.ico"); ?>" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo html($sBaseUrl . "favicon.ico"); ?>" type="image/x-icon">
  <title><?php echo html(getPageTitleText("Mail", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body class="mail-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <button type="submit" form="mail-form" name="mail_body_format" value="html" class="button-link mail-send-button">Send HTML</button>
    <button type="submit" form="mail-form" name="mail_body_format" value="plain" class="button-link mail-send-button">Send plain text</button>
    <span class="mail-form-status <?php echo html($sMailStatusClass); ?>" aria-live="polite"><?php echo html($sMailStatus); ?></span>
  </p>
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="post" id="mail-form" class="snippet-board-form mail-form" autocomplete="on" data-mail-allowed-sender-domains="<?php echo html(json_encode($aMailAllowedSenderDomains)); ?>" data-mail-restrict-from-to-single-address="<?php echo $blMailRestrictFromToSingleAddress ? "1" : "0"; ?>">
    <input type="hidden" name="action" value="send_mail">
    <input type="hidden" name="lm_csrf_token" value="<?php echo html(getCsrfToken("lm_csrf_token")); ?>">
    <input type="hidden" name="mail_rich_text_paste" class="js-mail-rich-text-paste" value="<?php echo (int)$iMailRichTextPaste; ?>">
    <div class="mail-form-fields">
      <label for="mail-to">To:</label>
      <input type="text" id="mail-to" name="mail_to" value="<?php echo html($aMailValues["to"]); ?>" autocomplete="on" inputmode="email" spellcheck="false">
      <label for="mail-cc">Carbon Copy:</label>
      <input type="text" id="mail-cc" name="mail_cc" value="<?php echo html($aMailValues["cc"]); ?>" autocomplete="on" inputmode="email" spellcheck="false">
      <label for="mail-bcc">Blind Carbon Copy:</label>
      <input type="text" id="mail-bcc" name="mail_bcc" value="<?php echo html($aMailValues["bcc"]); ?>" autocomplete="on" inputmode="email" spellcheck="false">
      <label for="mail-from">From:</label>
      <input type="text" id="mail-from" name="mail_from" value="<?php echo html($aMailValues["from"]); ?>" autocomplete="on" inputmode="email" spellcheck="false">
<?php

if (!$blMailRestrictFromToSingleAddress) {

?>
      <label for="mail-sender">Sender:</label>
      <input type="text" id="mail-sender" name="mail_sender" value="<?php echo html($aMailValues["sender"]); ?>" autocomplete="on" inputmode="email" spellcheck="false">
<?php

}

?>
      <label for="mail-reply-to">Reply-To:</label>
      <input type="text" id="mail-reply-to" name="mail_reply_to" value="<?php echo html($aMailValues["reply_to"]); ?>" autocomplete="on" inputmode="email" spellcheck="false">
      <label for="mail-subject">Subject:</label>
      <input type="text" id="mail-subject" name="mail_subject" value="<?php echo html($aMailValues["subject"]); ?>" autocomplete="on">
    </div>
    <label for="mail-message" class="mail-message-label">Message:</label>
    <textarea id="mail-message" name="mail_message" class="snippet-board-textarea js-snippet-board-textarea mail-message-textarea" rows="18" spellcheck="true"><?php echo html($aMailValues["message"]); ?></textarea>
  </form>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>vendors/tinymce-8.8.1/tinymce.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
