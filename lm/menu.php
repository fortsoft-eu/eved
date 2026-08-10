<?php

include "main.php";


$blJsonResponse = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireFullAccess($aAllowedIps, "portal", "csrf_token", $blJsonResponse);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireNamedCsrfToken("csrf_token", true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "create_menu_item") {
    menuAdminCreateOrUpdate($oPdo, 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "update_menu_item") {
    $iMenuId = isset($_POST["menu_id"]) ? (int)$_POST["menu_id"] : 0;
    if ($iMenuId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid menu item."), 400);
    }
    menuAdminCreateOrUpdate($oPdo, $iMenuId);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "delete_menu_item") {
    $iMenuId = isset($_POST["menu_id"]) ? (int)$_POST["menu_id"] : 0;
    if ($iMenuId < 1) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid menu item."), 400);
    }
    try {
        $oPdo->beginTransaction();
        $oStatement = $oPdo->prepare("SELECT path FROM fs_menu WHERE id = :id FOR UPDATE");
        $oStatement->execute(array("id" => $iMenuId));
        $aCurrent = $oStatement->fetch();
        if (!$aCurrent) {
            $oPdo->rollBack();
            sendJsonAndExit(array("success" => false, "message" => "Menu item was not found."), 404);
        }
        $sGroupKey = menuAdminGroupKey($aCurrent["path"]);
        $oStatement = $oPdo->prepare("DELETE FROM fs_menu WHERE id = :id");
        $oStatement->execute(array("id" => $iMenuId));
        menuAdminNormalizeGroupOrder($oPdo, $sGroupKey);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "menu_id" => $iMenuId, "menu_deleted" => true, "tables_html" => menuAdminRenderTables($oPdo)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "move_menu_item") {
    $iMenuId = isset($_POST["menu_id"]) ? (int)$_POST["menu_id"] : 0;
    $sDirection = isset($_POST["direction"]) ? (string)$_POST["direction"] : "";
    if ($iMenuId < 1 || ($sDirection != "up" && $sDirection != "down")) {
        sendJsonAndExit(array("success" => false, "message" => "Invalid order change."), 400);
    }
    try {
        $oPdo->beginTransaction();
        menuAdminMoveItem($oPdo, $iMenuId, $sDirection);
        $oPdo->commit();
        sendJsonAndExit(array("success" => true, "menu_id" => $iMenuId, "tables_html" => menuAdminRenderTables($oPdo)));
    } catch (Exception $oException) {
        error_log((string)$oException);
        if ($oPdo->inTransaction()) {
            $oPdo->rollBack();
        }
        sendJsonAndExit(array("success" => false, "message" => "Database error: " . $oException->getMessage()), 500);
    }
}

$sFilterValue = getQuickTableFilterValue();

$iTime = sendPageHeaders();


?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("csrf_token")); ?>">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText($aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="menu-admin-tables" value="<?php echo html($sFilterValue); ?>" autocomplete="off" spellcheck="false">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
    <button type="button" class="button-link js-add-menu-item">New</button>
  </p>
  <div id="menu-admin-tables" class="admin-table-groups">
<?php echo menuAdminRenderTables($oPdo); ?>
  </div>
  <div id="admin-reusable-dialog" class="confirm-dialog" role="dialog" aria-modal="true" hidden></div>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
