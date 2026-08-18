<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
requireFullAccess("ex", "csrf_token", $blJsonResponse);

try {
    $aMailSenderData = mailFormFetchSenderData($oPdo, $aAdditionalSenderDomains);
    $aAllowedSenderDomains = $aMailSenderData["allowed_domains"];
    $aEmailAccountAddresses = $aMailSenderData["account_addresses"];
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$sMailRichTextPasteSessionKey = "ex_mail_rich_text_paste";
$sMailStatusSessionKey = "ex_mail_status";
$sMailStatusClassSessionKey = "ex_mail_status_class";
$sMailValuesSessionKey = "ex_mail_values";
$iMailRichTextPaste = isset($_SESSION[$sMailRichTextPasteSessionKey]) && (string)$_SESSION[$sMailRichTextPasteSessionKey] == "1" ? 1 : 0;
$_SESSION[$sMailRichTextPasteSessionKey] = (string)$iMailRichTextPaste;
$aMailValues = array("to" => "", "cc" => "", "bcc" => "", "from" => "", "sender" => "", "reply_to" => "", "subject" => "", "message" => "");
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
    requireNamedCsrfToken("csrf_token", $blJsonResponse);
    if (getPostedValue("action") == "save_mail_rich_text_paste") {
        $iMailRichTextPaste = getPostedValue("rich_text_paste") == "1" ? 1 : 0;
        $_SESSION[$sMailRichTextPasteSessionKey] = (string)$iMailRichTextPaste;
        sendJsonAndExit(array("success" => true, "rich_text_paste" => $iMailRichTextPaste));
    }
    if (getPostedValue("action") == "suggest_mail_recipients") {
        try {
            sendJsonAndExit(array("success" => true, "recipients" => mailFormFetchRecipientSuggestions($oPdo, getPostedTrimmedValue("term"), 12, getPostedValue("allowed_sender_domains") == "1" ? $aAllowedSenderDomains : null, $aEmailAccountAddresses)));
        } catch (Exception $oException) {
            error_log((string)$oException);
            sendJsonAndExit(array("success" => false, "message" => "Recipients could not be loaded."), 500);
        }
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
        $aAttachments = mailFormUploadedAttachments("mail_attachments", $aErrors);
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
            $aErrors[] = "Invalid To.";
        }
        if ($aMailValues["cc"] != "" && $aCc === false) {
            $aErrors[] = "Invalid carbon copy.";
        }
        if ($aMailValues["bcc"] != "" && $aBcc === false) {
            $aErrors[] = "Invalid blind copy.";
        }
        if ($iRecipientCount < 1 && $aMailValues["to"] == "" && $aMailValues["cc"] == "" && $aMailValues["bcc"] == "") {
            $aErrors[] = "Recipient required.";
        }
        if ($aMailValues["from"] != "" && $aFrom === false) {
            $aErrors[] = "Invalid From.";
        }
        if ($aMailValues["from"] != "" && $aFrom !== false && !mailFormEmailListUsesAllowedSenderDomains($aFrom, $aAllowedSenderDomains)) {
            $aErrors[] = "Invalid From domain.";
        }
        if ($aMailValues["sender"] != "" && $aSender === false) {
            $aErrors[] = "Invalid Sender.";
        }
        if ($aMailValues["sender"] != "" && $aSender !== false && !mailFormEmailListUsesAllowedSenderDomains($aSender, $aAllowedSenderDomains)) {
            $aErrors[] = "Invalid Sender domain.";
        }
        if ($aFrom !== false && $aFrom["count"] > 1 && $blMailRestrictFromToOneAddress) {
            $aErrors[] = "Single From required.";
        }
        if ($aFrom !== false && $aFrom["count"] > 1 && !$blMailRestrictFromToOneAddress && $aMailValues["sender"] == "") {
            $aErrors[] = "Sender required.";
        }
        if ($aMailValues["reply_to"] != "" && $aReplyTo === false) {
            $aErrors[] = "Invalid Reply-To.";
        }
        $blMailBodyIsEmpty = $sMailBodyFormat == "plain" ? mailFormBuildPlainTextMessage($aMailValues["message"]) == "" : mailFormHtmlBodyIsEmpty($aMailValues["message"]) && !$aAttachments;
        if ($blMailBodyIsEmpty) {
            $aErrors[] = "Message required.";
        }
        if (!$aErrors && !function_exists("mail")) {
            $aErrors[] = "Mail unavailable.";
        }
        if ($aErrors) {
            $sMailStatus = isDesktop() ? implode(" ", $aErrors) : (string)$aErrors[0];
            $sMailStatusClass = "message-error";
        } elseif (mailFormSendMessage($sTo, $sCc, $sBcc, $sFrom, $sSender, $sReplyTo, $sSubject, $aMailValues["message"], $sMailBodyFormat, $aAttachments)) {
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
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>"></script>
</head>
<body class="mail-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>" data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <button type="submit" form="mail-form" name="mail_body_format" value="html" class="button-link mail-send-button">Send HTML</button>
    <button type="submit" form="mail-form" name="mail_body_format" value="plain" class="button-link mail-send-button">Send plain text</button>
    <span class="mail-form-status <?php echo html($sMailStatusClass); ?>" aria-live="polite"><?php echo html($sMailStatus); ?></span>
  </p>
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="post" id="mail-form" class="snippet-board-form mail-form" enctype="multipart/form-data" autocomplete="on" data-mail-allowed-sender-domains="<?php echo html(json_encode($aAllowedSenderDomains)); ?>" data-mail-restrict-from-to-single-address="<?php echo $blMailRestrictFromToOneAddress ? "1" : "0"; ?>">
    <input type="hidden" name="action" value="send_mail">
    <input type="hidden" name="csrf_token" value="<?php echo html(getCsrfToken("csrf_token")); ?>">
    <input type="hidden" name="mail_rich_text_paste" class="js-mail-rich-text-paste" value="<?php echo (int)$iMailRichTextPaste; ?>">
    <div class="mail-form-fields">
      <label for="mail-to">To:</label>
      <input type="text" id="mail-to" name="mail_to" value="<?php echo html($aMailValues["to"]); ?>" autocomplete="on" inputmode="email" spellcheck="false" data-mail-recipient-suggest="1">
      <label for="mail-cc">Carbon Copy:</label>
      <input type="text" id="mail-cc" name="mail_cc" value="<?php echo html($aMailValues["cc"]); ?>" autocomplete="on" inputmode="email" spellcheck="false" data-mail-recipient-suggest="1">
      <label for="mail-bcc">Blind Carbon Copy:</label>
      <input type="text" id="mail-bcc" name="mail_bcc" value="<?php echo html($aMailValues["bcc"]); ?>" autocomplete="on" inputmode="email" spellcheck="false" data-mail-recipient-suggest="1">
      <label for="mail-from">From:</label>
      <input type="text" id="mail-from" name="mail_from" value="<?php echo html($aMailValues["from"]); ?>" autocomplete="on" inputmode="email" spellcheck="false" data-mail-recipient-suggest="1" data-mail-recipient-suggest-allowed-domains="1" data-mail-recipient-suggest-single="<?php echo $blMailRestrictFromToOneAddress ? "1" : "0"; ?>">
<?php

if (!$blMailRestrictFromToOneAddress) {

?>
      <label for="mail-sender">Sender:</label>
      <input type="text" id="mail-sender" name="mail_sender" value="<?php echo html($aMailValues["sender"]); ?>" autocomplete="on" inputmode="email" spellcheck="false" data-mail-recipient-suggest="1" data-mail-recipient-suggest-allowed-domains="1" data-mail-recipient-suggest-single="1">
<?php

}

?>
      <label for="mail-reply-to">Reply-To:</label>
      <input type="text" id="mail-reply-to" name="mail_reply_to" value="<?php echo html($aMailValues["reply_to"]); ?>" autocomplete="on" inputmode="email" spellcheck="false" data-mail-recipient-suggest="1">
      <label for="mail-subject">Subject:</label>
      <input type="text" id="mail-subject" name="mail_subject" value="<?php echo html($aMailValues["subject"]); ?>" autocomplete="on">
      <label for="mail-attachments">Attachments:</label>
      <input type="file" id="mail-attachments" name="mail_attachments[]" multiple>
    </div>
    <label for="mail-message" class="mail-message-label">Message:</label>
    <textarea id="mail-message" name="mail_message" class="snippet-board-textarea js-snippet-board-textarea mail-message-textarea" rows="18" spellcheck="true"><?php echo html($aMailValues["message"]); ?></textarea>
  </form>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/tinymce-8.8.1/tinymce.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
