<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
requireFullAccess("portal", "csrf_token", $blJsonResponse);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("csrf_token", $blJsonResponse);
}

$sAction = $_SERVER["REQUEST_METHOD"] == "POST" ? getPostedValue("action") : "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "unlock_lm_encryption") {
    try {
        $sLmEncryptionHash = trim((string)getPostedValue("lm_encryption_hash"));
        $sLmEncryptionKey = deriveLmEncryptionKey($sLmEncryptionHash);
        if (getLmEncryptionVerifier($oPdo) != "") {
            if (!verifyLmEncryptionKey($oPdo, $sLmEncryptionKey)) {
                throw new RuntimeException("Encryption hash is invalid.");
            }
        } else {
            if (!hash_equals($sLmEncryptionHash, trim((string)getPostedValue("lm_encryption_hash_confirm")))) {
                throw new RuntimeException("Hash confirmation does not match.");
            }
            saveLmEncryptionVerifier($oPdo, $sLmEncryptionKey);
        }
        setLmEncryptionSessionKey($sLmEncryptionKey);
        session_write_close();
        sendJsonAndExit(array("success" => true));
    } catch (PDOException $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    } catch (RuntimeException $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => $oException->getMessage()), 403);
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Encryption could not be unlocked."), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "check_snippet_board_revision") {
    try {
        requireLmEncryptionSessionKey($oPdo, $blJsonResponse);
        sendJsonAndExit(array("success" => true, "revision" => getSnippetBoardRevision($oPdo)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "load_snippet_board") {
    try {
        $aSnippetBoardData = loadSnippetBoardData($oPdo, requireLmEncryptionSessionKey($oPdo, $blJsonResponse));
        sendJsonAndExit(array("success" => true, "revision" => $aSnippetBoardData["revision"], "snippets" => $aSnippetBoardData["snippets"], "richTextPasteModes" => $aSnippetBoardData["richTextPasteModes"]));
    } catch (Exception $oException) {
        error_log((string)$oException);
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $sAction == "save_snippet_board") {
    try {
        $sLmEncryptionKey = requireLmEncryptionSessionKey($oPdo, $blJsonResponse);
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("UPDATE fs_snippet_board SET note_text = :note_text, rich_text_paste = :rich_text_paste WHERE id = :id");
        for ($iSnippetId = 1; $iSnippetId <= 6; $iSnippetId++) {
            $oStatement->execute(array(
                "id" => $iSnippetId,
                "note_text" => encryptLmSecretText(getPostedValue("snippet_" . $iSnippetId), $sLmEncryptionKey),
                "rich_text_paste" => getPostedValue("rich_text_paste_" . $iSnippetId) == "1" ? 1 : 0
            ));
        }
        $oPdo->commit();
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true, "revision" => getSnippetBoardRevision($oPdo)));
        }
        sendSecurityHeaders();
        header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
        exit;
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
        }
        send500AndExit("Database error: " . $oException->getMessage());
    }
}

$aSnippets = array();
$aRichTextPasteModes = array();
$sBoardRevision = "";
for ($iSnippetId = 1; $iSnippetId <= 6; $iSnippetId++) {
    $aSnippets[$iSnippetId] = "";
    $aRichTextPasteModes[$iSnippetId] = 0;
}
$blLmEncryptionConfigured = false;
try {
    $blLmEncryptionConfigured = getLmEncryptionVerifier($oPdo) != "";
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}
$sLmEncryptionKey = getLmEncryptionSessionKey();
$blLmEncryptionUnlocked = false;

if ($sLmEncryptionKey != "") {
    try {
        if ($blLmEncryptionConfigured && verifyLmEncryptionKey($oPdo, $sLmEncryptionKey)) {
            $blLmEncryptionUnlocked = true;
        } else {
            clearLmEncryptionSessionKey();
            $sLmEncryptionKey = "";
        }
    } catch (PDOException $oException) {
        error_log((string)$oException);
        send500AndExit("Database error: " . $oException->getMessage());
    } catch (RuntimeException $oException) {
        error_log((string)$oException);
        clearLmEncryptionSessionKey();
        $sLmEncryptionKey = "";
    }
}

if ($blLmEncryptionUnlocked) {
    try {
        $aSnippetBoardData = loadSnippetBoardData($oPdo, $sLmEncryptionKey);
        $aSnippets = $aSnippetBoardData["snippets"];
        $aRichTextPasteModes = $aSnippetBoardData["richTextPasteModes"];
        $sBoardRevision = $aSnippetBoardData["revision"];
    } catch (PDOException $oException) {
        error_log((string)$oException);
        send500AndExit("Database error: " . $oException->getMessage());
    } catch (RuntimeException $oException) {
        error_log((string)$oException);
        try {
            $sBoardRevision = getSnippetBoardRevision($oPdo);
        } catch (Exception $oRevisionException) {
            error_log((string)$oRevisionException);
            send500AndExit("Database error: " . $oRevisionException->getMessage());
        }
    } catch (Exception $oException) {
        error_log((string)$oException);
        send500AndExit("Database error: " . $oException->getMessage());
    }
} else {
    try {
        $sBoardRevision = getSnippetBoardRevision($oPdo);
    } catch (Exception $oException) {
        error_log((string)$oException);
        send500AndExit("Database error: " . $oException->getMessage());
    }
}

$iTime = sendPageHeaders();


?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr" class="snippet-board-html" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
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
<body class="snippet-board-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>" data-snippet-board-revision="<?php echo html($sBoardRevision); ?>" data-snippet-board-locked="<?php echo $blLmEncryptionUnlocked ? "0" : "1"; ?>" data-lm-encryption-configured="<?php echo $blLmEncryptionConfigured ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();
echo "    <span class=\"snippet-board-tabs\" role=\"tablist\" aria-label=\"Snippet slots\">\n";
for ($iSlot = 1; $iSlot <= 6; $iSlot++) {

?>
      <button type="button" class="button-link snippet-board-tab<?php echo $iSlot == 1 ? " snippet-board-tab-active" : ""; ?>" data-snippet-tab="<?php echo $iSlot; ?>" role="tab" aria-controls="snippet-panel-<?php echo $iSlot; ?>" aria-selected="<?php echo $iSlot == 1 ? "true" : "false"; ?>" aria-label="Snippet <?php echo $iSlot; ?>"><?php echo $iSlot; ?></button>
<?php

}

?>
    </span>
    <span class="snippet-board-status js-snippet-board-status" aria-live="polite"></span>
  </p>
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="post" id="snippet-board-form" class="snippet-board-form">
    <input type="hidden" name="action" value="save_snippet_board">
    <input type="hidden" name="csrf_token" value="<?php echo html(getCsrfToken("csrf_token")); ?>">
    <div class="snippet-board-grid">
<?php

for ($iSlot = 1; $iSlot <= 6; $iSlot++) {

?>
      <section id="snippet-panel-<?php echo $iSlot; ?>" class="snippet-board-panel<?php echo $iSlot == 1 ? " snippet-board-panel-active" : ""; ?>" data-snippet-panel="<?php echo $iSlot; ?>" role="tabpanel">
        <input type="hidden" name="rich_text_paste_<?php echo $iSlot; ?>" class="js-snippet-board-rich-text-paste" data-snippet-rich-text-paste="<?php echo $iSlot; ?>" value="<?php echo (int)$aRichTextPasteModes[$iSlot]; ?>">
        <textarea id="snippet-<?php echo $iSlot; ?>" name="snippet_<?php echo $iSlot; ?>" class="snippet-board-textarea js-snippet-board-textarea" rows="18" autocomplete="off" spellcheck="true" aria-label="Snippet <?php echo $iSlot; ?>"<?php echo $blLmEncryptionUnlocked ? "" : " disabled"; ?>><?php echo html($aSnippets[$iSlot]); ?></textarea>
      </section>
<?php

}

?>
    </div>
  </form>
<?php

if (!$blLmEncryptionUnlocked) {

?>
  <div class="confirm-dialog lm-encryption-unlock-dialog js-lm-encryption-unlock-dialog" id="lm-encryption-unlock-dialog" role="dialog" aria-modal="true">
    <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="post" class="confirm-dialog-box login-form lm-encryption-unlock-form js-lm-encryption-unlock-form">
      <input type="hidden" name="action" value="unlock_lm_encryption">
      <input type="hidden" name="csrf_token" value="<?php echo html(getCsrfToken("csrf_token")); ?>">
      <div class="confirm-dialog-header">
        <strong><?php echo $blLmEncryptionConfigured ? "Unlock Encrypted Data" : "Set Encryption Hash"; ?></strong>
      </div>
      <div class="login-fields">
        <label for="lm-encryption-hash">Hash</label>
        <input type="password" id="lm-encryption-hash" name="lm_encryption_hash" autocomplete="current-password" required>
<?php

    if (!$blLmEncryptionConfigured) {

?>
        <label for="lm-encryption-hash-confirm">Confirm Hash</label>
        <input type="password" id="lm-encryption-hash-confirm" name="lm_encryption_hash_confirm" autocomplete="new-password" required>
<?php

    }

?>
      </div>
      <p class="login-message message-error js-lm-encryption-unlock-error" hidden></p>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button"><?php echo $blLmEncryptionConfigured ? "Unlock" : "Set"; ?></button>
      </div>
    </form>
  </div>
<?php

}

?>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/tinymce-8.8.1/tinymce.min.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
