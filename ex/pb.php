<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireViewAccess("ex", "csrf_token", true);
$blCanEdit = isFullAccessAllowed("ex");


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("csrf_token", true);
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "remove_phone_book_contact") {
    if (!$blCanEdit) {
        send403AndExit();
    }
    $iPhoneBookId = isset($_POST["phone_book_id"]) ? (int)$_POST["phone_book_id"] : 0;
    if ($iPhoneBookId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid Phone Book item."), 400);
    }
    try {
        removePhoneBookContact($oPdo, $iPhoneBookId);
        if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest") {
            sendJsonAndExit(array("success" => true));
        }
        sendSecurityHeaders();
        header("Location: " . $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]), true, 303);
        exit;
    } catch (Exception $oException) {
        error_log((string)$oException);
        send500AndExit("Database error: " . $oException->getMessage());
    }
}


try {
    $aPhoneBookRows = fetchPhoneBookRows($oPdo);
} catch (Exception $oException) {
    error_log((string)$oException);
    send500AndExit("Database error: " . $oException->getMessage());
}


$blRenderPageThrobber = count($aPhoneBookRows) > $iRenderThrobberRowLimit;
$sRenderThrobberHtmlAttributes = getRenderThrobberHtmlAttributes($blRenderPageThrobber);
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr"<?php echo $sRenderThrobberHtmlAttributes; ?>>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText()); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
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
<body data-chromium="<?php echo isChromiumBased() ? "1" : "0"; ?>">
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="phone-book-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <span class="table-record-count js-table-record-count" data-table-count="phone-book-table" aria-live="polite"><?php echo count($aPhoneBookRows); ?></span>
  </p>
<?php

if (!$aPhoneBookRows) {
    echo "  <p>No records found.</p>\n";
} else {
    if ($blRenderPageThrobber) {
        echo renderPageThrobber();
    }

?>
  <table id="phone-book-table" class="contacts-table table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Subject</th>
        <th>Contact</th>
      </tr>
    </thead>
    <tbody>
<?php

    $iPhoneBookRowCount = count($aPhoneBookRows);
    $aPhoneBookSubjectRowspans = array();
    $aPhoneBookFilterTexts = array();
    for ($iPhoneBookIndex = 0; $iPhoneBookIndex < $iPhoneBookRowCount; $iPhoneBookIndex++) {
        if (isset($aPhoneBookFilterTexts[$iPhoneBookIndex])) {
            continue;
        }
        $iSubjectId = (int)$aPhoneBookRows[$iPhoneBookIndex]["subject_id"];
        $aFilterTexts = array(phoneBookSubjectDisplayName($aPhoneBookRows[$iPhoneBookIndex]));
        $iSubjectRowspan = 0;
        for ($iSubjectIndex = $iPhoneBookIndex; $iSubjectIndex < $iPhoneBookRowCount && (int)$aPhoneBookRows[$iSubjectIndex]["subject_id"] === $iSubjectId; $iSubjectIndex++) {
            $iSubjectRowspan++;
            $aFilterTexts[] = (string)$aPhoneBookRows[$iSubjectIndex]["contact_type_name"];
            $aFilterTexts[] = contactDisplayValue($aPhoneBookRows[$iSubjectIndex]["contact_type"], $aPhoneBookRows[$iSubjectIndex]["contact_value"]);
            $aFilterTexts[] = (string)$aPhoneBookRows[$iSubjectIndex]["note"];
        }
        $aPhoneBookSubjectRowspans[$iPhoneBookIndex] = $iSubjectRowspan;
        $sPhoneBookFilterText = implode(" ", $aFilterTexts);
        for ($iSubjectIndex = $iPhoneBookIndex; $iSubjectIndex < $iPhoneBookIndex + $iSubjectRowspan; $iSubjectIndex++) {
            $aPhoneBookFilterTexts[$iSubjectIndex] = $sPhoneBookFilterText;
        }
    }
    foreach ($aPhoneBookRows as $iPhoneBookIndex => $aPhoneBookRow) {
        $sSubjectName = phoneBookSubjectDisplayName($aPhoneBookRow);
        $iSubjectRowspan = isset($aPhoneBookSubjectRowspans[$iPhoneBookIndex]) ? (int)$aPhoneBookSubjectRowspans[$iPhoneBookIndex] : 0;
        $sContactDisplayValue = contactDisplayValue($aPhoneBookRow["contact_type"], $aPhoneBookRow["contact_value"]);
        $sNote = trim((string)$aPhoneBookRow["note"]);
        $blIsPrimary = (int)$aPhoneBookRow["is_primary"] == 1;
        $blIsContactActive = (int)$aPhoneBookRow["contact_is_active"] == 1;
        $sRowClass = (int)$aPhoneBookRow["subject_is_active"] == 1 && (int)$aPhoneBookRow["contact_is_active"] == 1 ? "" : " class=\"phone-book-row-inactive\"";
        $sContactActions = $blCanEdit ? "<span class=\"la\"><a href=\"#\" class=\"ia js-remove-phone-book-contact\" data-phone-book-id=\"" . html($aPhoneBookRow["phone_book_id"]) . "\" title=\"Remove from Phone Book\" aria-label=\"Remove from Phone Book\">" . $sDeleteEmoji . "</a></span>" : "";
        echo "      <tr data-phone-book-id=\"" . html($aPhoneBookRow["phone_book_id"]) . "\"" . $sRowClass . ">\n";
        if ($iSubjectRowspan > 0) {
            echo "        <td class=\"phone-book-subject\" rowspan=\"" . html($iSubjectRowspan) . "\">" . htmlValue($sSubjectName) . renderCopyAction($sSubjectName) . "</td>\n";
        }
        echo "        <td class=\"phone-book-contact contact-cell ci li" . ($blIsContactActive ? "" : " cx") . "\" data-phone-book-id=\"" . html($aPhoneBookRow["phone_book_id"]) . "\" data-contact-id=\"" . html($aPhoneBookRow["contact_id"]) . "\" data-contact-type-id=\"" . html($aPhoneBookRow["contact_type_id"]) . "\" data-contact-type=\"" . html($aPhoneBookRow["contact_type"]) . "\" data-contact-type-name=\"" . html($aPhoneBookRow["contact_type_name"]) . "\" data-contact-value=\"" . html($sContactDisplayValue) . "\"><span class=\"ch\">" . htmlValue($aPhoneBookFilterTexts[$iPhoneBookIndex]) . "</span><span class=\"dv\"><span class=\"ct\">" . html($aPhoneBookRow["contact_type_name"]) . "</span>: " . renderContactValueText($aPhoneBookRow["contact_type"], $aPhoneBookRow["contact_value"]) . "</span>" . renderContactValueActions($aPhoneBookRow["contact_type"], $aPhoneBookRow["contact_value"], true, true) . "<span class=\"cn\">" . ($sNote != "" ? "(" . html($sNote) . ")" : "") . "</span><span class=\"cf\"><span class=\"cp\" title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span class=\"cz\" title=\"Inactive\">" . ($blIsContactActive ? "" : $sInactiveEmoji) . "</span></span>" . $sContactActions . "</td>\n",
            "      </tr>\n";
    }

?>
    </tbody>
  </table>
<?php

}
if ($blCanEdit) {

?>
  <div class="confirm-dialog" id="phone-book-remove-dialog" hidden>
    <form class="confirm-dialog-box subject-edit-dialog" method="post" action="<?php echo html($sBaseUrl . basename($_SERVER["SCRIPT_NAME"])); ?>" enctype="application/x-www-form-urlencoded">
      <input type="hidden" name="action" value="remove_phone_book_contact">
      <input type="hidden" name="phone_book_id" value="">
      <input type="hidden" name="csrf_token" value="<?php echo html(getCsrfToken("csrf_token")); ?>">
      <div class="confirm-dialog-header">
        <strong>Confirm Deletion</strong>
        <button type="button" class="confirm-dialog-close js-phone-book-remove-close" aria-label="Close">&times;</button>
      </div>
      <p class="confirm-dialog-message">Remove this contact from Phone Book?<br><strong class="js-phone-book-remove-value"></strong></p>
      <div class="contact-edit-error" style="display: none;"></div>
      <div class="confirm-dialog-actions">
        <button type="submit" class="confirm-dialog-button">Yes</button>
        <button type="button" class="confirm-dialog-button js-phone-book-remove-cancel">No</button>
      </div>
    </form>
  </div>
<?php

}
echo renderEmojiData();

?>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
