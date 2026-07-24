<?php

include "main.php";


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "lm_csrf_token", $blJsonResponse);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("lm_csrf_token", $blJsonResponse);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "save_snippet_board") {
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("INSERT INTO fs_snippet_board (id, note_text) VALUES (:id, :note_text) ON DUPLICATE KEY UPDATE note_text = VALUES(note_text)");
        for ($iSnippetId = 1; $iSnippetId <= 6; $iSnippetId++) {
            $oStatement->execute(array(
                "id" => $iSnippetId,
                "note_text" => getPostedValue("snippet_" . $iSnippetId)
            ));
        }
        $oPdo->commit();
        if ($blJsonResponse) {
            sendJsonAndExit(array("success" => true));
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
for ($iSnippetId = 1; $iSnippetId <= 6; $iSnippetId++) {
    $aSnippets[$iSnippetId] = "";
}

try {
    $oStatement = $oPdo->query("SELECT id, note_text FROM fs_snippet_board WHERE id BETWEEN 1 AND 6 ORDER BY id");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $iSnippetId = (int)$aRow["id"];
        if ($iSnippetId >= 1 && $iSnippetId <= 6) {
            $aSnippets[$iSnippetId] = (string)$aRow["note_text"];
        }
    }
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}

$sStyleToken = dechex(filemtime(__DIR__ . "/css/admin.css"));
$sScriptToken = dechex(filemtime(__DIR__ . "/js/admin.js"));
$sTinyMcePath = __DIR__ . "/vendors/tinymce-8.8.1/tinymce.min.js";
$blTinyMceAvailable = is_file($sTinyMcePath);
$sTinyMceToken = $blTinyMceAvailable ? dechex(filemtime($sTinyMcePath)) : "";
$sTitle = getPageTitleText("Snippet Board", $aAllowedIps);

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Cervinka">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo html($sBaseUrl . "favicon.ico"); ?>" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo html($sBaseUrl . "favicon.ico"); ?>" type="image/x-icon">
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("lm_csrf_token")); ?>">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . $sStyleToken); ?>" rel="stylesheet" type="text/css">
</head>
<body class="snippet-board-page" data-pmd-like="<?php echo isDesktop() ? "0" : "1"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <span class="snippet-board-tabs" role="tablist" aria-label="Snippet slots">
<?php for ($iSlot = 1; $iSlot <= 6; $iSlot++) { ?>
      <button type="button" class="button-link snippet-board-tab<?php echo $iSlot == 1 ? " snippet-board-tab-active" : ""; ?>" data-snippet-tab="<?php echo $iSlot; ?>" role="tab" aria-controls="snippet-panel-<?php echo $iSlot; ?>" aria-selected="<?php echo $iSlot == 1 ? "true" : "false"; ?>" aria-label="Snippet <?php echo $iSlot; ?>"><?php echo $iSlot; ?></button>
<?php } ?>
    </span>
    <span class="snippet-board-status js-snippet-board-status" aria-live="polite"></span>
  </p>
  <form action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" method="post" id="snippet-board-form" class="snippet-board-form">
    <input type="hidden" name="action" value="save_snippet_board">
    <input type="hidden" name="lm_csrf_token" value="<?php echo html(getCsrfToken("lm_csrf_token")); ?>">
    <div class="snippet-board-grid">
<?php for ($iSlot = 1; $iSlot <= 6; $iSlot++) { ?>
      <section id="snippet-panel-<?php echo $iSlot; ?>" class="snippet-board-panel<?php echo $iSlot == 1 ? " snippet-board-panel-active" : ""; ?>" data-snippet-panel="<?php echo $iSlot; ?>" role="tabpanel">
        <textarea id="snippet-<?php echo $iSlot; ?>" name="snippet_<?php echo $iSlot; ?>" class="snippet-board-textarea js-snippet-board-textarea" rows="18" autocomplete="off" spellcheck="true" aria-label="Snippet <?php echo $iSlot; ?>"><?php echo html($aSnippets[$iSlot]); ?></textarea>
      </section>
<?php } ?>
    </div>
  </form>
<?php if ($blTinyMceAvailable) { ?>
  <script type="text/javascript" src="<?php echo html($sBaseUrl . "vendors/tinymce-8.8.1/tinymce.min.js?sToken=" . $sTinyMceToken); ?>"></script>
<?php } ?>
  <script type="text/javascript" src="<?php echo html($sBaseUrl . "js/admin.js?sToken=" . $sScriptToken); ?>"></script>
</body>
</html>
