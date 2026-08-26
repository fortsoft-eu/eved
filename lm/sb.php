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
        $sLmEncryptionHash = trim(getPostedValue("lm_encryption_hash"));
        $sLmEncryptionKey = deriveLmEncryptionKey($sLmEncryptionHash);
        if (getLmEncryptionVerifier($oPdo) != "") {
            if (!verifyLmEncryptionKey($oPdo, $sLmEncryptionKey)) {
                throw new RuntimeException("Encryption hash is invalid.");
            }
        } else {
            if (!hash_equals($sLmEncryptionHash, trim(getPostedValue("lm_encryption_hash_confirm")))) {
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

$iTime = sendPageHeaders("", "en-US", true);


?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr" class="snippet-board-html" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
  <link rel="icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style.css")); ?>" title="Original">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-graphite.css")); ?>" title="Graphite">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-midnight.css")); ?>" title="Midnight">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-slate.css")); ?>" title="Slate">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sepia.css")); ?>" title="Sepia">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-sand.css")); ?>" title="Sand">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-forest.css")); ?>" title="Forest">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-moss.css")); ?>" title="Moss">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ocean.css")); ?>" title="Ocean">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-ice.css")); ?>" title="Ice">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-lavender.css")); ?>" title="Lavender">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-rose.css")); ?>" title="Rose">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-copper.css")); ?>" title="Copper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-burgundy.css")); ?>" title="Burgundy">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-monochrome.css")); ?>" title="Monochrome">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-high-contrast.css")); ?>" title="High Contrast">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-soft.css")); ?>" title="Soft">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-paper.css")); ?>" title="Paper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-terminal.css")); ?>" title="Terminal">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-cobalt.css")); ?>" title="Cobalt">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/style-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/style-plum.css")); ?>" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>" integrity="sha384-Y1RNflBlpVdW8pnre87MlysCCOkrIrejOEni6hIeWzTbOmboT6BgObKOeMYSJ5C5"></script>
</head>
<body class="snippet-board-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>" data-snippet-board-revision="<?php echo html($sBoardRevision); ?>" data-snippet-board-locked="<?php echo $blLmEncryptionUnlocked ? "0" : "1"; ?>" data-lm-encryption-configured="<?php echo $blLmEncryptionConfigured ? "1" : "0"; ?>" data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
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
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/tinymce-8.8.1/tinymce.min.js" integrity="sha384-EVluCEb/WcelN32wQoUON0WS02blf7mK1zOWd0W6jvusWRW3l2JDcEtuVqa6O0Xs"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>" integrity="sha384-zi9FpsGST+GJEbRKC9fbVcqxHcE9U9rYiXnYRpsOJC6WiDPEFPeUOmPlmaMCUYcm"></script>
</body>
</html>
