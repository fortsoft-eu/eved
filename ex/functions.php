<?php

function isAllowedIp($aAllowedIps) {
    return isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $aAllowedIps, true);
}

function isExTrustedClient($aAllowedIps) {
    $sTrustedUserAgent = isset($GLOBALS["sExTrustedUserAgent"]) ? (string)$GLOBALS["sExTrustedUserAgent"] : "";
    $sTrustedAcceptLanguage = isset($GLOBALS["sExTrustedAcceptLanguage"]) ? (string)$GLOBALS["sExTrustedAcceptLanguage"] : "";
    if (!isAllowedIp($aAllowedIps) || $sTrustedUserAgent == "" || $sTrustedAcceptLanguage == "") {
        return false;
    }
    if (!isset($_SERVER["HTTP_USER_AGENT"], $_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
        return false;
    }
    return hash_equals($sTrustedUserAgent, (string)$_SERVER["HTTP_USER_AGENT"])
        && hash_equals($sTrustedAcceptLanguage, (string)$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
}

function isExViewAllowed($aAllowedIps) {
    return isExTrustedClient($aAllowedIps) || refreshExAuthSession();
}

function isExFullAccessAllowed($aAllowedIps) {
    return isExTrustedClient($aAllowedIps) || isExPermissionAllowed("portal.full");
}

function getExLoginToken() {
    if (!isset($_SESSION["ex_login_token"]) || !is_string($_SESSION["ex_login_token"]) || $_SESSION["ex_login_token"] == "") {
        $_SESSION["ex_login_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["ex_login_token"];
}

function resetExLoginToken() {
    $_SESSION["ex_login_token"] = bin2hex(random_bytes(32));
    return $_SESSION["ex_login_token"];
}

function getExCsrfToken() {
    if (!isset($_SESSION["ex_csrf_token"]) || !is_string($_SESSION["ex_csrf_token"]) || $_SESSION["ex_csrf_token"] == "") {
        $_SESSION["ex_csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["ex_csrf_token"];
}

function resetExCsrfToken() {
    $_SESSION["ex_csrf_token"] = bin2hex(random_bytes(32));
    return $_SESSION["ex_csrf_token"];
}

function isExCsrfTokenValid($sToken) {
    $sSessionToken = isset($_SESSION["ex_csrf_token"]) ? (string)$_SESSION["ex_csrf_token"] : "";
    return $sToken != "" && $sSessionToken != "" && hash_equals($sSessionToken, $sToken);
}

function requireExCsrfToken() {
    $sToken = "";
    if (isset($_POST["ex_csrf_token"])) {
        $sToken = (string)$_POST["ex_csrf_token"];
    } elseif (isset($_SERVER["HTTP_X_CSRF_TOKEN"])) {
        $sToken = (string)$_SERVER["HTTP_X_CSRF_TOKEN"];
    }
    if (!isExCsrfTokenValid($sToken)) {
        if (isExAjaxRequest()) {
            nxSendJsonAndExit(array("success" => false, "message" => "Invalid security token."), 403);
        }
        send403AndExit();
    }
}

function getExCurrentUrlWithoutAuthAction() {
    $sPath = isset($_SERVER["REQUEST_URI"]) ? (string)$_SERVER["REQUEST_URI"] : "";
    $aParts = parse_url($sPath);
    $sResult = isset($aParts["path"]) ? $aParts["path"] : "";
    $aQuery = array();
    if (isset($aParts["query"]) && $aParts["query"] != "") {
        parse_str($aParts["query"], $aQuery);
        unset($aQuery["logout"]);
        unset($aQuery["ex_csrf_token"]);
    }
    if (count($aQuery) > 0) {
        $sResult .= "?" . http_build_query($aQuery, "", "&");
    }
    if ($sResult == "") {
        $sResult = "/";
    }
    return $sResult;
}

function getExLogoutUrl() {
    $sUrl = getExCurrentUrlWithoutAuthAction();
    return $sUrl . (strpos($sUrl, "?") === false ? "?" : "&") . "logout=1&ex_csrf_token=" . rawurlencode(getExCsrfToken());
}

function isExAjaxRequest() {
    return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
}

function getQuickTableFilterScriptName() {
    $sScriptName = isset($_SERVER["SCRIPT_NAME"]) ? (string)$_SERVER["SCRIPT_NAME"] : "";
    $sScriptName = str_replace("\\", "/", $sScriptName);
    $sScriptName = basename($sScriptName);
    if ($sScriptName == "") {
        $sScriptName = "index.php";
    }
    return $sScriptName;
}

function getQuickTableFilterId($sFilterId) {
    $sFilterId = trim((string)$sFilterId);
    $sFilterId = preg_replace("/[^A-Za-z0-9_\\-]/", "", $sFilterId);
    if ($sFilterId == "") {
        $sFilterId = "table-filter";
    }
    return $sFilterId;
}

function getQuickTableFilterValue($sFilterId = "table-filter") {
    $sScriptName = getQuickTableFilterScriptName();
    $sFilterId = getQuickTableFilterId($sFilterId);
    if (!isset($_SESSION["quick_table_filters"]) || !is_array($_SESSION["quick_table_filters"])) {
        return "";
    }
    if (!isset($_SESSION["quick_table_filters"][$sScriptName]) || !is_array($_SESSION["quick_table_filters"][$sScriptName])) {
        return "";
    }
    if (!isset($_SESSION["quick_table_filters"][$sScriptName][$sFilterId]) || !is_string($_SESSION["quick_table_filters"][$sScriptName][$sFilterId])) {
        return "";
    }
    return $_SESSION["quick_table_filters"][$sScriptName][$sFilterId];
}

function setQuickTableFilterValue($sFilterId, $sValue) {
    $sScriptName = getQuickTableFilterScriptName();
    $sFilterId = getQuickTableFilterId($sFilterId);
    if (!isset($_SESSION["quick_table_filters"]) || !is_array($_SESSION["quick_table_filters"])) {
        $_SESSION["quick_table_filters"] = array();
    }
    if (!isset($_SESSION["quick_table_filters"][$sScriptName]) || !is_array($_SESSION["quick_table_filters"][$sScriptName])) {
        $_SESSION["quick_table_filters"][$sScriptName] = array();
    }
    $_SESSION["quick_table_filters"][$sScriptName][$sFilterId] = (string)$sValue;
}

function resetQuickTableFilterValue($sFilterId) {
    $sScriptName = getQuickTableFilterScriptName();
    $sFilterId = getQuickTableFilterId($sFilterId);
    if (isset($_SESSION["quick_table_filters"][$sScriptName][$sFilterId])) {
        unset($_SESSION["quick_table_filters"][$sScriptName][$sFilterId]);
    }
    if (isset($_SESSION["quick_table_filters"][$sScriptName]) && is_array($_SESSION["quick_table_filters"][$sScriptName]) && !$_SESSION["quick_table_filters"][$sScriptName]) {
        unset($_SESSION["quick_table_filters"][$sScriptName]);
    }
}

function handleQuickTableFilterRequest() {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["quick_table_filter_action"])) {
        return;
    }
    if (!isExAjaxRequest()) {
        send403AndExit();
    }
    $sAction = (string)$_POST["quick_table_filter_action"];
    $sFilterId = isset($_POST["filter_id"]) ? (string)$_POST["filter_id"] : "table-filter";
    if ($sAction == "save") {
        $sValue = nxGetPostedValue("filter_value");
        setQuickTableFilterValue($sFilterId, $sValue);
        session_write_close();
        nxSendJsonAndExit(array("success" => true));
    } elseif ($sAction == "reset") {
        resetQuickTableFilterValue($sFilterId);
        session_write_close();
        nxSendJsonAndExit(array("success" => true));
    }
    nxSendJsonAndExit(array("success" => false, "message" => "Invalid quick filter action."), 400);
}

function nxNormalizeFsMenuPath($sPath) {
    $sPath = str_replace("\\", "/", trim((string)$sPath));
    $sPath = preg_replace("#/+#", "/", $sPath);
    $sPath = preg_replace("#^/+#", "", $sPath);
    return $sPath;
}

function nxEncodeFsMenuPath($sPath) {
    $aParts = explode("/", nxNormalizeFsMenuPath($sPath));
    $aEncodedParts = array();
    foreach ($aParts as $sPart) {
        $aEncodedParts[] = rawurlencode($sPart);
    }
    return implode("/", $aEncodedParts);
}

function nxGetExMenuPathPrefix() {
    $sScriptFile = isset($_SERVER["SCRIPT_FILENAME"]) ? (string)$_SERVER["SCRIPT_FILENAME"] : __FILE__;
    $sScriptFile = str_replace("\\", "/", $sScriptFile);
    $sScriptDirectory = dirname($sScriptFile);
    return nxNormalizeFsMenuPath(basename(dirname($sScriptDirectory)) . "/" . basename($sScriptDirectory)) . "/";
}

function nxGetExMenuItems($oPdo) {
    $aItems = array();
    $sPathPrefix = nxGetExMenuPathPrefix();
    if (!$oPdo) {
        return $aItems;
    }
    try {
        $oStatement = $oPdo->prepare("SELECT id, path, icon, name, title, target, `order` AS menu_order FROM ex_menu WHERE is_active = 1 AND path LIKE :path_prefix ORDER BY `order` ASC, id ASC");
        $oStatement->execute(array("path_prefix" => $sPathPrefix . "%"));
        while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
            $sPath = nxNormalizeFsMenuPath(isset($aRow["path"]) ? $aRow["path"] : "");
            if (strpos($sPath, $sPathPrefix) !== 0) {
                continue;
            }
            $sRelativePath = substr($sPath, strlen($sPathPrefix));
            if ($sRelativePath == "" || strpos($sRelativePath, "..") !== false || preg_match("#(^|/)\\.#", $sRelativePath) || preg_match("#[^A-Za-z0-9/_\\.\\-]#", $sRelativePath)) {
                continue;
            }
            $sName = trim((string)(isset($aRow["name"]) ? $aRow["name"] : ""));
            if ($sName == "") {
                $sName = $sRelativePath;
            }
            $aItems[] = array(
                "id" => (int)$aRow["id"],
                "path" => $sPath,
                "relative_path" => $sRelativePath,
                "icon" => (string)(isset($aRow["icon"]) ? $aRow["icon"] : ""),
                "name" => $sName,
                "title" => (string)(isset($aRow["title"]) ? $aRow["title"] : ""),
                "target" => (string)(isset($aRow["target"]) ? $aRow["target"] : ""),
                "order" => (int)$aRow["menu_order"]
            );
        }
    } catch (Exception $oException) {
        return array();
    }
    return $aItems;
}

function nxGetCurrentExMenuName($oPdo) {
    global $sError;

    $sPath = nxGetExMenuPathPrefix() . getQuickTableFilterScriptName();
    if (!$oPdo) {
        $sMessage = isset($sError) && $sError != "" ? (string)$sError : "Database connection is not available.";
        send500AndExit("Database error: " . $sMessage);
    }
    try {
        $oStatement = $oPdo->prepare("SELECT name FROM ex_menu WHERE is_active = 1 AND path = :path LIMIT 1");
        $oStatement->execute(array("path" => $sPath));
        $sName = trim((string)$oStatement->fetchColumn());
        if ($sName != "") {
            return $sName;
        }
    } catch (Exception $oException) {
        send500AndExit("Database error: " . $oException->getMessage());
    }
    send500AndExit("Menu error: Missing active menu name for " . $sPath . ".");
}

function nxRenderExMenu() {
    global $oPdo, $sBaseUrl, $sMenuEmoji;

    $aItems = nxGetExMenuItems($oPdo);
    $sCurrentPath = nxGetExMenuPathPrefix() . getQuickTableFilterScriptName();
    $sBase = isset($sBaseUrl) ? (string)$sBaseUrl : "";
    if (!$aItems) {
        return;
    }
    echo "    <span class=\"ex-menu\" data-ex-menu>\n"
        . "      <button type=\"button\" class=\"ex-menu-button\" data-ex-menu-button aria-haspopup=\"true\" aria-expanded=\"false\" title=\"Menu\" aria-label=\"Menu\">" . $sMenuEmoji . "</button>\n"
        . "      <span class=\"ex-menu-panel\" data-ex-menu-panel hidden>\n";
    foreach ($aItems as $aItem) {
        $sClass = "ex-menu-link";
        $sCurrent = "";
        $sIcon = trim((string)$aItem["icon"]);
        $sTitle = trim((string)$aItem["title"]);
        $sTarget = trim((string)$aItem["target"]);
        $sTitleAttribute = $sTitle != "" ? " title=\"" . nxHtml($sTitle) . "\"" : "";
        $sTargetAttribute = $sTarget != "" && preg_match("#^(_blank|_self|_parent|_top|[A-Za-z][A-Za-z0-9_\\-]*)$#", $sTarget) ? " target=\"" . nxHtml($sTarget) . "\"" : "";
        $sRelAttribute = $sTarget == "_blank" ? " rel=\"noopener noreferrer\"" : "";
        if ($aItem["path"] === $sCurrentPath) {
            $sClass .= " ex-menu-link-active";
            $sCurrent = " aria-current=\"page\"";
        }
        echo "        <a class=\"" . nxHtml($sClass) . "\" href=\"" . nxHtml($sBase . nxEncodeFsMenuPath($aItem["relative_path"])) . "\"" . $sTitleAttribute . $sTargetAttribute . $sRelAttribute . $sCurrent . "><span class=\"ex-menu-icon\" aria-hidden=\"true\">" . nxHtml($sIcon) . "</span><span class=\"ex-menu-text\">" . nxHtml($aItem["name"]) . "</span></a>\n";
    }
    echo "      </span>\n"
        . "    </span>\n";
}

function getExLoginDelaySeconds() {
    $iFailures = isset($_SESSION["ex_login_failures"]) ? (int)$_SESSION["ex_login_failures"] : 0;
    $iLastFailure = isset($_SESSION["ex_login_last_failure"]) ? (int)$_SESSION["ex_login_last_failure"] : 0;
    if ($iFailures < 5 || $iLastFailure < 1) {
        return 0;
    }
    $iDelay = 300 - (time() - $iLastFailure);
    return $iDelay > 0 ? $iDelay : 0;
}

function exFetchPortalLoginUser($oPdo, $sUserName) {
    $oStatement = $oPdo->prepare("SELECT u.id, u.subject_id, u.user_name, u.password_hash, u.is_active, s.subject_type, s.is_active AS subject_active FROM ex_users AS u INNER JOIN ex_subjects AS s ON s.id = u.subject_id WHERE u.user_name = :user_name LIMIT 1");
    $oStatement->execute(array("user_name" => $sUserName));
    $aUser = $oStatement->fetch(PDO::FETCH_ASSOC);
    return $aUser ? $aUser : null;
}

function exFetchPortalSessionUser($oPdo, $iUserId) {
    $oStatement = $oPdo->prepare("SELECT u.id, u.subject_id, u.user_name, u.is_active, s.subject_type, s.is_active AS subject_active FROM ex_users AS u INNER JOIN ex_subjects AS s ON s.id = u.subject_id WHERE u.id = :id LIMIT 1");
    $oStatement->execute(array("id" => $iUserId));
    $aUser = $oStatement->fetch(PDO::FETCH_ASSOC);
    return $aUser ? $aUser : null;
}

function exUserHasPermission($oPdo, $iUserId, $iSubjectId, $sPermissionKey) {
    $oStatement = $oPdo->prepare("SELECT COUNT(*) FROM ex_permissions AS p WHERE p.permission_key = :permission_key AND p.is_active = 1 AND (EXISTS (SELECT 1 FROM ex_user_permissions AS up WHERE up.permission_id = p.id AND up.user_id = :user_id AND up.is_allowed = 1) OR EXISTS (SELECT 1 FROM ex_group_permissions AS gp INNER JOIN ex_subject_groups AS sg ON sg.group_id = gp.group_id WHERE gp.permission_id = p.id AND gp.is_allowed = 1 AND sg.subject_id = :subject_id))");
    $oStatement->execute(array(
        "permission_key" => $sPermissionKey,
        "user_id" => $iUserId,
        "subject_id" => $iSubjectId
    ));
    return (int)$oStatement->fetchColumn() > 0;
}

function exFetchUserEffectivePermissions($oPdo, $iUserId, $iSubjectId) {
    $aPermissions = array();
    $oStatement = $oPdo->prepare("(SELECT p.permission_key FROM ex_user_permissions AS up INNER JOIN ex_permissions AS p ON p.id = up.permission_id WHERE up.user_id = :user_id AND up.is_allowed = 1 AND p.is_active = 1) UNION (SELECT p.permission_key FROM ex_group_permissions AS gp INNER JOIN ex_permissions AS p ON p.id = gp.permission_id INNER JOIN ex_subject_groups AS sg ON sg.group_id = gp.group_id WHERE sg.subject_id = :subject_id AND gp.is_allowed = 1 AND p.is_active = 1)");
    $oStatement->execute(array(
        "user_id" => $iUserId,
        "subject_id" => $iSubjectId
    ));
    while ($sPermissionKey = $oStatement->fetchColumn()) {
        $aPermissions[(string)$sPermissionKey] = true;
    }
    return $aPermissions;
}

function exUpdateLastLogin($oPdo, $iUserId) {
    try {
        $oStatement = $oPdo->prepare("UPDATE ex_users SET last_login_at = NOW() WHERE id = :id");
        $oStatement->execute(array("id" => $iUserId));
    } catch (Exception $oException) {
    }
}

function refreshExAuthSession() {
    static $blRefreshed = false;
    static $blAuthenticated = false;

    if ($blRefreshed) {
        return $blAuthenticated;
    }
    $blRefreshed = true;
    $blAuthenticated = false;

    if (!isset($_SESSION["ex_view_auth"], $_SESSION["ex_auth_user_id"])
        || $_SESSION["ex_view_auth"] !== true
        || (int)$_SESSION["ex_auth_user_id"] < 1) {
        return false;
    }

    $oPdo = isset($GLOBALS["oPdo"]) ? $GLOBALS["oPdo"] : null;
    if (!$oPdo) {
        clearExAuthSession();
        return false;
    }

    try {
        $aUser = exFetchPortalSessionUser($oPdo, (int)$_SESSION["ex_auth_user_id"]);
        if (!$aUser
            || (int)$aUser["is_active"] != 1
            || (int)$aUser["subject_active"] != 1
            || !in_array((string)$aUser["subject_type"], array("person", "service"), true)) {
            clearExAuthSession();
            return false;
        }

        $aPermissions = exFetchUserEffectivePermissions($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"]);
        if (empty($aPermissions["portal.view"]) && empty($aPermissions["portal.full"])) {
            clearExAuthSession();
            return false;
        }

        $_SESSION["ex_view_auth"] = true;
        $_SESSION["ex_auth_user_id"] = (int)$aUser["id"];
        $_SESSION["ex_auth_subject_id"] = (int)$aUser["subject_id"];
        $_SESSION["ex_view_auth_user"] = (string)$aUser["user_name"];
        $_SESSION["ex_view_permissions"] = $aPermissions;
        $blAuthenticated = true;
        return true;
    } catch (Exception $oException) {
        clearExAuthSession();
        return false;
    }
}

function isExPermissionAllowed($sPermissionKey) {
    return refreshExAuthSession()
        && isset($_SESSION["ex_view_permissions"])
        && is_array($_SESSION["ex_view_permissions"])
        && !empty($_SESSION["ex_view_permissions"][$sPermissionKey]);
}

function clearExAuthSession() {
    unset($_SESSION["ex_view_auth"], $_SESSION["ex_auth_user_id"], $_SESSION["ex_auth_subject_id"], $_SESSION["ex_view_auth_user"], $_SESSION["ex_view_auth_time"], $_SESSION["ex_view_permissions"]);
}

function renderExLoginPageAndExit($sMessage = "") {
    $iTime = sendPageHeaders();
    $sBaseUrl = isset($GLOBALS["sBaseUrl"]) ? $GLOBALS["sBaseUrl"] : "";
    $sAction = htmlspecialchars(getExCurrentUrlWithoutAuthAction(), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sToken = htmlspecialchars(getExLoginToken(), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $sMessageHtml = $sMessage != "" ? "    <p class=\"message-error ex-login-message\">" . htmlspecialchars($sMessage, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</p>\n" : "";
    echo "<!DOCTYPE html>\n"
        . "<html lang=\"en-US\" dir=\"ltr\">\n"
        . "<head>\n"
        . "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n"
        . "  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n"
        . "  <meta name=\"viewport\" content=\"" . nxHtml(nxGetLockedViewportContent()) . "\">\n"
        . "  <meta name=\"theme-color\" content=\"#FFD8BB\">\n"
        . "  <link rel=\"icon\" href=\"" . htmlspecialchars($sBaseUrl . "favicon.ico", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" type=\"image/x-icon\">\n"
        . "  <link rel=\"shortcut icon\" href=\"" . htmlspecialchars($sBaseUrl . "favicon.ico", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" type=\"image/x-icon\">\n"
        . "  <title>Sign In</title>\n"
        . "  <meta name=\"date\" content=\"" . gmdate("D, d M Y H:i:s", $iTime) . " GMT\">\n"
        . "  <link href=\"" . htmlspecialchars($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css")), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" rel=\"stylesheet\" type=\"text/css\">\n"
        . "</head>\n"
        . "<body class=\"ex-login-page\">\n"
        . "  <div class=\"confirm-dialog ex-login-dialog\">\n"
        . "    <form class=\"confirm-dialog-box ex-login-form\" method=\"post\" action=\"" . $sAction . "\" enctype=\"application/x-www-form-urlencoded\">\n"
        . "      <input type=\"hidden\" name=\"ex_login_token\" value=\"" . $sToken . "\">\n"
        . "      <div class=\"confirm-dialog-header\">\n"
        . "        <strong>Sign In</strong>\n"
        . "        <button type=\"submit\" name=\"action\" value=\"ex_cancel\" class=\"confirm-dialog-close\" aria-label=\"Close\" formnovalidate>&times;</button>\n"
        . "      </div>\n"
        . "      <div class=\"ex-login-fields\">\n"
        . $sMessageHtml
        . "      <label for=\"ex-login-user\">User Name</label>\n"
        . "      <input type=\"text\" id=\"ex-login-user\" name=\"user_name\" autocomplete=\"username\" required>\n"
        . "      <label for=\"ex-login-password\">Password</label>\n"
        . "      <input type=\"password\" id=\"ex-login-password\" name=\"password\" autocomplete=\"current-password\" required>\n"
        . "      </div>\n"
        . "      <div class=\"confirm-dialog-actions\">\n"
        . "        <button type=\"submit\" name=\"action\" value=\"ex_login\" class=\"confirm-dialog-button\">Login</button>\n"
        . "        <button type=\"submit\" name=\"action\" value=\"ex_cancel\" class=\"confirm-dialog-button\" formnovalidate>Cancel</button>\n"
        . "      </div>\n"
        . "    </form>\n"
        . "  </div>\n"
        . "</body>\n"
        . "</html>\n";
    exit;
}

function handleExLoginPost() {
    $oPdo = isset($GLOBALS["oPdo"]) ? $GLOBALS["oPdo"] : null;
    $sToken = isset($_POST["ex_login_token"]) ? (string)$_POST["ex_login_token"] : "";
    $sSessionToken = isset($_SESSION["ex_login_token"]) ? (string)$_SESSION["ex_login_token"] : "";
    $sUserName = isset($_POST["user_name"]) ? trim((string)$_POST["user_name"]) : "";
    $sPassword = isset($_POST["password"]) ? (string)$_POST["password"] : "";
    $iDelay = getExLoginDelaySeconds();

    if ($iDelay > 0) {
        renderExLoginPageAndExit("Too many failed attempts. Try again later.");
    }
    if ($sToken == "" || $sSessionToken == "" || !hash_equals($sSessionToken, $sToken)) {
        resetExLoginToken();
        renderExLoginPageAndExit("Invalid sign-in request.");
    }
    $aUser = exFetchPortalLoginUser($oPdo, $sUserName);
    if ($aUser
        && (int)$aUser["is_active"] == 1
        && (int)$aUser["subject_active"] == 1
        && in_array((string)$aUser["subject_type"], array("person", "service"), true)
        && password_verify($sPassword, (string)$aUser["password_hash"])
        && (exUserHasPermission($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"], "portal.view")
            || exUserHasPermission($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"], "portal.full"))) {
        session_regenerate_id(true);
        $_SESSION["ex_view_auth"] = true;
        $_SESSION["ex_auth_user_id"] = (int)$aUser["id"];
        $_SESSION["ex_auth_subject_id"] = (int)$aUser["subject_id"];
        $_SESSION["ex_view_auth_user"] = (string)$aUser["user_name"];
        $_SESSION["ex_view_permissions"] = exFetchUserEffectivePermissions($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"]);
        $_SESSION["ex_view_auth_time"] = time();
        resetExCsrfToken();
        unset($_SESSION["ex_login_failures"], $_SESSION["ex_login_last_failure"], $_SESSION["ex_login_token"]);
        exUpdateLastLogin($oPdo, (int)$aUser["id"]);
        sendSecurityHeaders();
        header("Location: " . getExCurrentUrlWithoutAuthAction(), true, 303);
        exit;
    }

    $_SESSION["ex_login_failures"] = isset($_SESSION["ex_login_failures"]) ? (int)$_SESSION["ex_login_failures"] + 1 : 1;
    $_SESSION["ex_login_last_failure"] = time();
    resetExLoginToken();
    renderExLoginPageAndExit("Invalid user name or password.");
}

function requireExViewAccess($aAllowedIps) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "ex_logout") {
        requireExCsrfToken();
        clearExAuthSession();
        session_regenerate_id(true);
        resetExCsrfToken();
        sendSecurityHeaders();
        header("Location: " . getExCurrentUrlWithoutAuthAction(), true, 303);
        exit;
    }
    if (isset($_GET["logout"]) && refreshExAuthSession()) {
        $sToken = isset($_GET["ex_csrf_token"]) ? (string)$_GET["ex_csrf_token"] : "";
        if (!isExCsrfTokenValid($sToken)) {
            send403AndExit();
        }
        clearExAuthSession();
        session_regenerate_id(true);
        resetExCsrfToken();
        sendSecurityHeaders();
        header("Location: " . getExCurrentUrlWithoutAuthAction(), true, 303);
        exit;
    }
    if (isExTrustedClient($aAllowedIps)) {
        return;
    }
    if (refreshExAuthSession()) {
        return;
    }
    if (isset($_SESSION["ex_login_cancelled"]) && $_SESSION["ex_login_cancelled"] === true) {
        unset($_SESSION["ex_login_cancelled"]);
        send403AndExit();
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "ex_cancel") {
        $sToken = isset($_POST["ex_login_token"]) ? (string)$_POST["ex_login_token"] : "";
        $sSessionToken = isset($_SESSION["ex_login_token"]) ? (string)$_SESSION["ex_login_token"] : "";
        if ($sToken == "" || $sSessionToken == "" || !hash_equals($sSessionToken, $sToken)) {
            resetExLoginToken();
            send403AndExit();
        }
        $_SESSION["ex_login_cancelled"] = true;
        sendSecurityHeaders();
        header("Location: " . getExCurrentUrlWithoutAuthAction(), true, 303);
        exit;
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "ex_login") {
        handleExLoginPost();
    }
    if (isExAjaxRequest()) {
        nxSendJsonAndExit(array("success" => false, "message" => "Sign-in is required."), 403);
    }
    renderExLoginPageAndExit();
}

function getExPageTitleText($sTitle, $aAllowedIps) {
    global $oPdo;

    $sTitle = nxGetCurrentExMenuName($oPdo);
    $aStates = array();
    if (isExTrustedClient($aAllowedIps)) {
        $aStates[] = "Trusted";
    }
    if (refreshExAuthSession()) {
        $aStates[] = "Authenticated";
    }
    if (count($aStates) > 0) {
        $sTitle .= " — " . implode(" + ", $aStates);
    }
    return $sTitle;
}

function requireExFullAccess($aAllowedIps) {
    requireExViewAccess($aAllowedIps);
    if (isExFullAccessAllowed($aAllowedIps)) {
        return;
    }
    if (isExAjaxRequest()) {
        nxSendJsonAndExit(array("success" => false, "message" => "Full access is required."), 403);
    }
    send403AndExit();
}

function getUrlWithoutScriptName() {
    $sPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if (substr($sPath, -1) == "/") {
        return $sPath;
    }
    return dirname($sPath) . "/";
}

function getContentSecurityPolicySource() {
    if (!isset($_SERVER["HTTP_HOST"]) || !isset($_SERVER["REQUEST_URI"])) {
        return "'self'";
    }
    $sRequestScheme = "http";
    if (isset($GLOBALS["sScheme"]) && ($GLOBALS["sScheme"] == "http" || $GLOBALS["sScheme"] == "https")) {
        $sRequestScheme = $GLOBALS["sScheme"];
    } elseif ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "" && $_SERVER["HTTPS"] != "off")
        || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https")) {
        $sRequestScheme = "https";
    }
    $sHost = preg_replace("/[^A-Za-z0-9\\.\\-\\:\\[\\]]/", "", $_SERVER["HTTP_HOST"]);
    $sPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if ($sPath === false || $sPath === null || $sPath == "") {
        $sPath = "/";
    }
    if (substr($sPath, -1) != "/") {
        $sPath = dirname($sPath) . "/";
    }
    $sPath = preg_replace("#[^A-Za-z0-9/_\\-.%~]#", "", $sPath);
    if ($sPath == "") {
        $sPath = "/";
    }
    return $sRequestScheme . "://" . $sHost . $sPath;
}

function sendSecurityHeaders($sStyleNonce = "") {
    $sContentSecurityPolicySource = getContentSecurityPolicySource();
    $sStyleSource = $sContentSecurityPolicySource;
    if ($sStyleNonce != "") {
        $sStyleSource .= " 'nonce-" . $sStyleNonce . "'";
    } else {
        $sStyleSource .= " 'unsafe-inline'";
    }
    $sContentSecurityPolicy = "default-src 'none'; "
        . "script-src " . $sContentSecurityPolicySource . "; "
        . "style-src " . $sStyleSource . "; "
        . "img-src " . $sContentSecurityPolicySource . " data: blob:; "
        . "font-src " . $sContentSecurityPolicySource . " data:; "
        . "connect-src " . $sContentSecurityPolicySource . "; "
        . "media-src " . $sContentSecurityPolicySource . "; "
        . "frame-src " . $sContentSecurityPolicySource . "; "
        . "object-src 'none'; "
        . "base-uri " . $sContentSecurityPolicySource . "; "
        . "form-action " . $sContentSecurityPolicySource . "; "
        . "frame-ancestors 'self'";
    header("Strict-Transport-Security: max-age=31536000", true);
    header("X-Content-Type-Options: nosniff", true);
    header("X-Frame-Options: SAMEORIGIN", true);
    header("Referrer-Policy: strict-origin-when-cross-origin", true);
    header("Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=(), serial=(), bluetooth=()", true);
    header("Content-Security-Policy: " . $sContentSecurityPolicy, true);
}

function sendPageHeaders() {
    $iTime = time();
    if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
        if (strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) >= $iTime) {
            sendSecurityHeaders();
            header("HTTP/1.1 304 Not Modified", true);
            exit;
        }
    }
    $sDate = gmdate("D, d M Y H:i:s", $iTime);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-UA-Compatible: IE=edge", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();
    return $iTime;
}

function redirectIndexPhpToRoot() {
    if (basename($_SERVER["SCRIPT_NAME"]) != "index.php") {
        return;
    }
    $sPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if (substr($sPath, -9) != "index.php") {
        return;
    }
    $sTarget = dirname($sPath);
    if ($sTarget == "\\" || $sTarget == ".") {
        $sTarget = "/";
    }
    $sQueryString = $_SERVER["QUERY_STRING"];
    $sTarget .= $sQueryString == "" ? "/" : "/?" . $sQueryString;
    sendSecurityHeaders();
    header("Location: " . $sTarget, true, 301);
    exit;
}

function getDatabaseDownloadFileName($sType) {
    $sPrefix = isset($GLOBALS["sDatabaseDownloadPrefix"]) ? $GLOBALS["sDatabaseDownloadPrefix"] : "eved";
    $sProject = isset($GLOBALS["sDatabaseDownloadProject"]) ? $GLOBALS["sDatabaseDownloadProject"] : basename(__DIR__);
    return $sPrefix . "_" . $sProject . "_" . $sType . "_" . date("Y-m-d_His", time()) . ".sql";
}

function quoteDatabaseIdentifier($sIdentifier) {
    return "`" . str_replace("`", "``", $sIdentifier) . "`";
}

function quoteDatabaseValue($oPdo, $mValue) {
    if ($mValue === null) {
        return "NULL";
    }
    $sQuoted = $oPdo->quote((string)$mValue);
    if ($sQuoted === false) {
        return "'" . str_replace("'", "''", (string)$mValue) . "'";
    }
    return $sQuoted;
}

function getDatabaseSchemaSql($aTables) {
    $sBody = "";
    foreach ($aTables as $aTable) {
        $sCreateTable = preg_replace("/\r\n|\r|\n/", "\r\n", $aTable[1]);
        $sBody .= $sCreateTable . ";\r\n\r\n";
    }
    return rtrim($sBody) . "\r\n";
}

function getDatabaseBackupSql($oPdo, $aTables) {
    $sBody = "SET NAMES utf8mb4;\r\n\r\n" . getDatabaseSchemaSql($aTables) . "\r\n";
    foreach ($aTables as $aTable) {
        $sTableName = $aTable[0];
        $sQuotedTableName = quoteDatabaseIdentifier($sTableName);
        $oStatement = $oPdo->query("SELECT * FROM " . $sQuotedTableName);
        $sColumns = "";
        $blHasRows = false;
        while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
            if ($sColumns == "") {
                $aColumns = array();
                foreach (array_keys($aRow) as $sColumnName) {
                    $aColumns[] = quoteDatabaseIdentifier($sColumnName);
                }
                $sColumns = implode(", ", $aColumns);
            }
            $aValues = array();
            foreach ($aRow as $mValue) {
                $aValues[] = quoteDatabaseValue($oPdo, $mValue);
            }
            $sBody .= "INSERT INTO " . $sQuotedTableName . " (" . $sColumns . ") VALUES (" . implode(", ", $aValues) . ");\r\n";
            $blHasRows = true;
        }
        if ($blHasRows) {
            $sBody .= "\r\n";
        }
    }
    return rtrim($sBody) . "\r\n";
}

function formatDatabaseStructureHtml($sSql) {
    $sSql = preg_replace_callback("/\\benum\\(([^)]*)\\)/i", function ($aMatches) {
        return "enum(" . preg_replace("/,\\s*/", ", ", $aMatches[1]) . ")";
    }, $sSql);
    $sSql .= ";";
    $aParts = preg_split("/('(?:\\\\.|''|[^'\\\\])*'|`(?:``|[^`])*`)/", $sSql, -1, PREG_SPLIT_DELIM_CAPTURE);
    $sHtml = "";
    foreach ($aParts as $sPart) {
        if ($sPart == "") {
            continue;
        }
        if ($sPart[0] == "'") {
            $sHtml .= "<span class=\"sql-string\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } elseif ($sPart[0] == "`") {
            $sHtml .= "<span class=\"sql-identifier\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } else {
            $sEscapedPart = htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8");
            $sHtml .= preg_replace("/\\b(ADD|ALTER|AUTO_INCREMENT|CASCADE|CHARACTER|CHARSET|CHECK|COLLATE|CONSTRAINT|CREATE|CURRENT_TIMESTAMP|DATABASE|DEFAULT|DELETE|ENGINE|ENUM|FOREIGN|KEY|NOT|NULL|ON|PRIMARY|REFERENCES|SET|TABLE|UNIQUE|UPDATE|USING|VALUES|INT|TINYINT|VARCHAR|TEXT|LONGTEXT|DATETIME|DATE|TIMESTAMP)\\b/i", "<span class=\"sql-keyword\">$1</span>", $sEscapedPart);
        }
    }
    return $sHtml;
}

function sendDatabaseSqlAndExit($sFileName, $sBody) {
    $sDate = gmdate("D, d M Y H:i:s", time());
    header("Content-Type: application/sql; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Content-Disposition: attachment; filename=\"" . $sFileName . "\"", true);
    header("Content-Transfer-Encoding: binary", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();
    echo $sBody;
    exit;
}
function send403AndExit() {
    $sDate = gmdate("D, d M Y H:i:s", time());
    $sHtml = "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n"
        . "<html><head>\n"
        . "<title>403 Forbidden</title>\n"
        . "</head><body>\n"
        . "<h1>Forbidden</h1>\n"
        . "<p>You don't have permission to access this resource.</p>\n"
        . "</body></html>\n";

    http_response_code(403);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Content-Length: " . strlen($sHtml), true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();
    echo $sHtml;
    exit;
}

function send500AndExit($sMessage) {
    $sDate = gmdate("D, d M Y H:i:s", time());
    $sHtml = "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n"
        . "<html><head>\n"
        . "<title>500 Internal Server Error</title>\n"
        . "</head><body>\n"
        . "<h1>Internal Server Error</h1>\n"
        . "<p>" . htmlspecialchars($sMessage, ENT_QUOTES, "UTF-8") . "</p>\n"
        . "</body></html>\n";

    http_response_code(500);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Content-Length: " . strlen($sHtml), true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();
    echo $sHtml;
    exit;
}

function nxHtml($mValue) {
    return htmlspecialchars((string)$mValue, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function nxFormatTimestampTooltipValue($mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return "";
    }
    if (preg_match("/^([0-9]{4}-[0-9]{2}-[0-9]{2})[ T]([0-9]{2}:[0-9]{2}:[0-9]{2})/", $sValue, $aMatches)) {
        return $aMatches[1] . " " . $aMatches[2];
    }
    return str_replace("T", " ", substr($sValue, 0, 19));
}

function nxTimestampTooltipText($aRow) {
    if (!is_array($aRow) || !array_key_exists("created_at", $aRow) || !array_key_exists("updated_at", $aRow)) {
        return "";
    }
    return "Created: " . nxFormatTimestampTooltipValue($aRow["created_at"]) . "\n"
        . "Updated: " . nxFormatTimestampTooltipValue($aRow["updated_at"]);
}

function nxRenderTimestampTooltipAttribute($aRow) {
    $sText = nxTimestampTooltipText($aRow);
    if ($sText == "") {
        return "";
    }
    return " title=\"" . str_replace("\n", "&#10;", nxHtml($sText)) . "\"";
}

function nxRenderTimestampTooltipDataAttribute($aRow) {
    $sText = nxTimestampTooltipText($aRow);
    if ($sText == "") {
        return "";
    }
    return " data-timestamp-tooltip=\"" . str_replace("\n", "&#10;", nxHtml($sText)) . "\"";
}

function nxEmojiValue($sName) {
    return (string)$GLOBALS[$sName];
}

function nxEmojiDataValue($sName) {
    return html_entity_decode(nxEmojiValue($sName), ENT_QUOTES | ENT_HTML5, "UTF-8");
}

function nxRenderEmojiData() {
    $aValues = array(
        "edit" => nxEmojiDataValue("sEditEmoji"),
        "delete" => nxEmojiDataValue("sDeleteEmoji"),
        "add" => nxEmojiDataValue("sAddEmoji"),
        "hidden-inactive" => nxEmojiDataValue("sHiddenInactiveEmoji"),
        "portal" => nxEmojiDataValue("sPortalEmoji"),
        "empty-value" => nxEmojiDataValue("sEmptyValueEmoji"),
        "throbber" => nxEmojiDataValue("sThrobberEmoji"),
        "filter-focus" => nxEmojiDataValue("sFilterFocusEmoji"),
        "copy" => nxEmojiDataValue("sCopyEmoji"),
        "copy-success" => nxEmojiDataValue("sCopySuccessEmoji"),
        "copy-failure" => nxEmojiDataValue("sCopyFailureEmoji"),
        "primary" => nxEmojiDataValue("sPrimaryEmoji"),
        "inactive" => nxEmojiDataValue("sInactiveEmoji"),
        "merge" => nxEmojiDataValue("sMergeEmoji"),
        "move-up" => nxEmojiDataValue("sMoveUpEmoji"),
        "move-down" => nxEmojiDataValue("sMoveDownEmoji"),
        "birthday-served" => nxEmojiDataValue("sBirthdayServedEmoji"),
        "communication-served" => nxEmojiDataValue("sCommunicationServedEmoji"),
        "contact-email" => nxEmojiDataValue("sContactEmailEmoji"),
        "contact-landline" => nxEmojiDataValue("sContactLandlineEmoji"),
        "contact-cell" => nxEmojiDataValue("sContactCellEmoji"),
        "contact-fax" => nxEmojiDataValue("sContactFaxEmoji"),
        "contact-pager" => nxEmojiDataValue("sContactPagerEmoji"),
        "contact-web" => nxEmojiDataValue("sContactWebEmoji"),
        "contact-telegram" => nxEmojiDataValue("sContactTelegramEmoji"),
        "contact-message" => nxEmojiDataValue("sContactMessageEmoji"),
        "contact-youtube" => nxEmojiDataValue("sContactYouTubeEmoji")
    );
    $sHtml = "  <span id=\"nx-emoji-data\" hidden";
    foreach ($aValues as $sKey => $sValue) {
        $sHtml .= " data-" . $sKey . "=\"" . nxHtml($sValue) . "\"";
    }
    return $sHtml . "></span>\n";
}

function nxRenderAdminScript($sBaseUrl) {
    return nxRenderEmojiData()
        . "  <script type=\"text/javascript\" src=\"" . nxHtml($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n";
}

function nxRenderFilterFocusButton($sFilterInput = "table-filter") {
    global $sFilterFocusEmoji;

    return "  <button type=\"button\" class=\"filter-focus-button js-filter-focus\" data-filter-input=\"" . nxHtml($sFilterInput) . "\" title=\"Focus filter\" aria-label=\"Focus filter\">" . $sFilterFocusEmoji . " Filter</button>\n";
}

function nxRenderPageThrobber() {
    global $sThrobberEmoji;

    return "  <div class=\"render-throbber js-render-throbber\" role=\"status\" aria-live=\"polite\">\n"
        . "    <div class=\"render-throbber-box\">\n"
        . "      <span class=\"render-throbber-icon\" aria-hidden=\"true\">" . $sThrobberEmoji . "</span>\n"
        . "    </div>\n"
        . "  </div>\n";
}

function nxGetLockedViewportContent() {
    return "width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no";
}

function nxGetRenderThrobberHtmlAttributes($blUseRenderThrobberLock) {
    $sAttributes = "";
    $sUserAgent = isset($_SERVER["HTTP_USER_AGENT"]) ? (string)$_SERVER["HTTP_USER_AGENT"] : "";
    if ($blUseRenderThrobberLock) {
        $blIsThrobberLockTarget = isThrobberLockTarget($sUserAgent);
        $sAttributes = " data-render-throbber-lock-target=\"" . nxHtml($blIsThrobberLockTarget ? "html" : "body") . "\" data-render-throbber-lock-active=\"1\"";
        if ($blIsThrobberLockTarget) {
            $sAttributes .= " data-render-throbber-zoom-lock=\"1\" data-render-throbber-viewport-content=\"" . nxHtml(nxGetLockedViewportContent()) . "\"";
        }
    }
    return $sAttributes;
}

function nxGetCondensedTableClass() {
    $sUserAgent = isset($_SERVER["HTTP_USER_AGENT"]) ? (string)$_SERVER["HTTP_USER_AGENT"] : "";
    return isPmdLikeUserAgent($sUserAgent) ? " nx-condensed-table" : "";
}

function nxHtmlValue($mValue) {
    global $sEmptyValueEmoji;

    $sValue = trim((string)$mValue);
    return $sValue != "" ? nxHtml($sValue) : $sEmptyValueEmoji;
}

function nxRenderCopyAction($mValue, $sTitle = "Copy") {
    global $sCopyEmoji;

    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return "";
    }
    return "<a class=\"nx-copy-action\" href=\"#\" data-copy-value=\"" . nxHtml($sValue) . "\" title=\"" . nxHtml($sTitle) . "\" aria-label=\"" . nxHtml($sTitle) . "\"><span class=\"nx-copy-action-box\">" . $sCopyEmoji . "</span></a>";
}

function nxRenderSubjectCellCopyAction($aValues, $blShowSingleItem = false) {
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
    return nxRenderCopyAction(implode("\n", $aCopyValues), "Copy items");
}

function nxHtmlMultiline($mValue) {
    global $sEmptyValueEmoji;

    $sValue = trim((string)$mValue);
    if ($sValue == "") {
        return $sEmptyValueEmoji;
    }
    return str_replace("\n", "<br>", nxHtml($sValue));
}

function nxGetDefaultContactTypeRows() {
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

function nxFetchContactTypes($oPdo = null, $blActiveOnly = true) {
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
        foreach (nxGetDefaultContactTypeRows() as $aRow) {
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

function nxGetContactTypeById($iContactTypeId, $oPdo = null, $blActiveOnly = true) {
    $iContactTypeId = (int)$iContactTypeId;
    foreach (nxFetchContactTypes($oPdo, $blActiveOnly) as $aType) {
        if ((int)$aType["id"] == $iContactTypeId) {
            return $aType;
        }
    }
    return null;
}

function nxContactTypeLabel($sType, $oPdo = null) {
    $sType = (string)$sType;
    foreach (nxFetchContactTypes($oPdo, false) as $aType) {
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

function nxGetContactTypes($oPdo = null) {
    $aTypes = array();
    foreach (nxFetchContactTypes($oPdo, true) as $aType) {
        $aTypes[] = (string)$aType["contact_type"];
    }
    return $aTypes;
}

function nxOriginalContactTypeMap() {
    static $aMap = null;

    if ($aMap !== null) {
        return $aMap;
    }

    $aMap = array();
    foreach (nxGetDefaultContactTypeRows() as $aType) {
        $aMap[(string)$aType["contact_type"]] = true;
    }
    return $aMap;
}

function nxIsOriginalContactType($sContactType) {
    $aMap = nxOriginalContactTypeMap();
    return isset($aMap[(string)$sContactType]);
}

function nxBuildContactTypeKeyBase($sName) {
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

function nxGenerateContactTypeKey($oPdo, $sName, $iExcludeId = 0) {
    $sBaseKey = nxBuildContactTypeKeyBase($sName);
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

function nxFetchContactTypeAdminRows($oPdo, $iContactTypeId = 0) {
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

function nxRenderContactTypeAdminRow($aContactType, $blShowActions = true) {
    global $sDeleteEmoji, $sEditEmoji, $sMergeEmoji, $sMoveUpEmoji, $sMoveDownEmoji;

    $blIsActive = (int)$aContactType["is_active"] == 1;

    return "      <tr data-contact-type-id=\"" . nxHtml($aContactType["id"]) . "\" data-contact-type-name=\"" . nxHtml($aContactType["name"]) . "\" data-contact-type-active=\"" . ($blIsActive ? "1" : "0") . "\" data-contact-type-order=\"" . nxHtml($aContactType["order"]) . "\">\n"
        . "        <td>" . nxHtml($aContactType["name"]) . "</td>\n"
        . "        <td>" . nxHtml($aContactType["contact_count"]) . "</td>\n"
        . "        <td>" . ($blIsActive ? "Yes" : "No") . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-move-contact-type-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"nx-item-action js-move-contact-type-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-merge-contact-type\" title=\"Merge into this contact type\" aria-label=\"Merge into this contact type\">" . $sMergeEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-edit-contact-type\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"nx-item-action js-delete-contact-type\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "") . "</td>\n"
        . "      </tr>\n";
}

function nxNormalizeContactTypeOrder($oPdo) {
    $oStatement = $oPdo->query("SELECT id FROM ex_contact_types ORDER BY `order` ASC, id ASC FOR UPDATE");
    $aIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
    $iOrder = 10;
    $oUpdateStatement = $oPdo->prepare("UPDATE ex_contact_types SET `order` = :order WHERE id = :id");
    foreach ($aIds as $iContactTypeId) {
        $oUpdateStatement->execute(array("order" => $iOrder, "id" => (int)$iContactTypeId));
        $iOrder += 10;
    }
}

function nxMoveContactTypeOrder($oPdo, $iContactTypeId, $sDirection) {
    nxNormalizeContactTypeOrder($oPdo);
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

function nxMergeContactTypeContacts($oPdo, $iTargetContactTypeId, $iSourceContactTypeId) {
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

function nxNormalizeYouTubeContactValue($sValue, $blRejectNonYouTubeLink = false) {
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
    $blLooksLikeUrl = preg_match("#^https?://#i", $sText) === 1
        || preg_match("#^www\\.#i", $sText) === 1
        || preg_match("#^(?:youtube\\.com|www\\.youtube\\.com)(?:[/:?\\#].*)?$#i", $sText) === 1
        || preg_match("#^[A-Za-z0-9.-]+\\.[A-Za-z]{2,}[/:?\\#].*$#", $sText) === 1;
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

function nxTelegramContactHost($sHost) {
    $sHost = strtolower(preg_replace("/^www\\./", "", (string)$sHost));
    if ($sHost == "t.me" || $sHost == "telegram.me" || $sHost == "telegram.dog") {
        return $sHost;
    }
    return false;
}

function nxTelegramInviteToken($sValue, $blRequireMarker = false) {
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

function nxTelegramSlug($sValue) {
    $sText = trim(rawurldecode((string)$sValue));
    if (!preg_match("/^[A-Za-z0-9_]{1,128}$/", $sText)) {
        return false;
    }
    return $sText;
}

function nxNormalizeTelegramContactPath($sHost, $sPath) {
    $sHost = nxTelegramContactHost($sHost);
    $sPath = trim((string)$sPath, "/");
    $aSegments = $sPath == "" ? array() : explode("/", $sPath);
    $sHandle = "";
    $sKind = "";
    $sToken = "";
    if ($sHost === false || count($aSegments) < 1 || count($aSegments) > 2) {
        return false;
    }
    if (count($aSegments) == 1) {
        $sToken = nxTelegramInviteToken($aSegments[0], true);
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
        $sToken = nxTelegramInviteToken($aSegments[1]);
        return $sToken !== false ? "https://" . $sHost . "/joinchat/" . rawurlencode($sToken) : false;
    }
    if ($sKind == "addstickers" || $sKind == "setlanguage") {
        $sToken = nxTelegramSlug($aSegments[1]);
        return $sToken !== false ? "https://" . $sHost . "/" . $sKind . "/" . rawurlencode($sToken) : false;
    }
    return false;
}

function nxNormalizeTelegramContactValue($sValue) {
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
        $sHost = isset($aParts["host"]) ? nxTelegramContactHost($aParts["host"]) : false;
        $sPath = isset($aParts["path"]) ? (string)$aParts["path"] : "";
        return $sHost !== false ? nxNormalizeTelegramContactPath($sHost, $sPath) : false;
    }
    if (preg_match("#^(joinchat|addstickers|setlanguage)/(.+)$#i", $sText, $aMatches)) {
        return nxNormalizeTelegramContactPath("t.me", $aMatches[1] . "/" . $aMatches[2]);
    }
    if (substr($sRawText, 0, 1) == " " || substr($sText, 0, 1) == "+" || preg_match("/^%20/i", $sText)) {
        $sToken = nxTelegramInviteToken(substr($sRawText, 0, 1) == " " ? $sRawText : $sText, true);
        return $sToken !== false ? "https://t.me/joinchat/" . rawurlencode($sToken) : false;
    }
    $sHandle = preg_replace("/^@+/", "", $sText);
    if (!preg_match("/^[A-Za-z0-9_]{5,32}$/", $sHandle)) {
        return false;
    }
    return "https://t.me/" . rawurlencode($sHandle);
}

function nxNormalizeIcqContactValue($sValue) {
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

function nxNormalizeEmailContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText == "") {
        return "";
    }
    return filter_var($sText, FILTER_VALIDATE_EMAIL) !== false ? $sText : false;
}

function nxNormalizeSkypeContactValue($sValue) {
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

function nxPhoneContactTypes() {
    return array(
        "landline" => true,
        "cell" => true,
        "fax" => true,
        "pager" => true
    );
}

function nxIsPhoneContactType($sContactType) {
    $aPhoneTypes = nxPhoneContactTypes();
    return isset($aPhoneTypes[(string)$sContactType]);
}

function nxPhoneMetadataRegex($sPattern) {
    return preg_replace("/\\s+/", "", trim((string)$sPattern));
}

function nxPhonePatternMatches($sPattern, $sValue, $blFullMatch = true, &$aMatches = null) {
    $sPattern = nxPhoneMetadataRegex($sPattern);
    $aMatches = array();
    if ($sPattern == "") {
        return false;
    }
    $sRegex = $blFullMatch ? "~^(?:" . str_replace("~", "\\~", $sPattern) . ")$~" : "~^(?:" . str_replace("~", "\\~", $sPattern) . ")~";
    return @preg_match($sRegex, (string)$sValue, $aMatches) === 1;
}

function nxPhoneMetadata() {
    static $aMetadata = null;

    if ($aMetadata !== null) {
        return $aMetadata;
    }

    $aMetadata = array("codes" => array());
    if (!function_exists("simplexml_load_file")) {
        return $aMetadata;
    }

    $sFile = __DIR__ . "/lib/phone_metadata.xml";
    $blPreviousLibxmlState = libxml_use_internal_errors(true);
    $sXml = is_file($sFile) ? file_get_contents($sFile) : "";
    $sXml = preg_replace("/^\\xEF\\xBB\\xBF/", "", (string)$sXml);
    $oXml = $sXml != "" ? simplexml_load_string($sXml) : false;
    libxml_clear_errors();
    libxml_use_internal_errors($blPreviousLibxmlState);
    if (!$oXml || !isset($oXml->territories->territory)) {
        return $aMetadata;
    }

    foreach ($oXml->territories->territory as $oTerritory) {
        $sCountryCode = (string)$oTerritory["countryCode"];
        if ($sCountryCode == "") {
            continue;
        }
        $aFormats = array();
        if (isset($oTerritory->availableFormats->numberFormat)) {
            foreach ($oTerritory->availableFormats->numberFormat as $oFormat) {
                $aLeadingDigits = array();
                foreach ($oFormat->leadingDigits as $oLeadingDigits) {
                    $aLeadingDigits[] = nxPhoneMetadataRegex((string)$oLeadingDigits);
                }
                $aFormats[] = array(
                    "pattern" => nxPhoneMetadataRegex((string)$oFormat["pattern"]),
                    "format" => (string)$oFormat->format,
                    "leading_digits" => $aLeadingDigits
                );
            }
        }
        if (!isset($aMetadata["codes"][$sCountryCode])) {
            $aMetadata["codes"][$sCountryCode] = array();
        }
        $aMetadata["codes"][$sCountryCode][] = array(
            "id" => (string)$oTerritory["id"],
            "main" => (string)$oTerritory["mainCountryForCode"] == "true",
            "leading_digits" => nxPhoneMetadataRegex((string)$oTerritory["leadingDigits"]),
            "national_prefix" => preg_replace("/\\D/", "", (string)$oTerritory["nationalPrefix"]),
            "pattern" => nxPhoneMetadataRegex((string)$oTerritory->generalDesc->nationalNumberPattern),
            "formats" => $aFormats
        );
    }
    return $aMetadata;
}

function nxFindPhoneTerritory($sDigits) {
    $aMetadata = nxPhoneMetadata();
    $iMaxCountryCodeLength = min(3, strlen((string)$sDigits) - 1);
    for ($iLength = $iMaxCountryCodeLength; $iLength >= 1; $iLength--) {
        $sCountryCode = substr((string)$sDigits, 0, $iLength);
        if (!isset($aMetadata["codes"][$sCountryCode])) {
            continue;
        }
        $sNationalNumber = substr((string)$sDigits, $iLength);
        foreach ($aMetadata["codes"][$sCountryCode] as $aTerritory) {
            $aNationalNumbers = array($sNationalNumber);
            if ($aTerritory["national_prefix"] != "" && strpos($sNationalNumber, (string)$aTerritory["national_prefix"]) === 0) {
                $aNationalNumbers[] = substr($sNationalNumber, strlen((string)$aTerritory["national_prefix"]));
            }
            foreach ($aNationalNumbers as $sCandidateNationalNumber) {
                if ($aTerritory["leading_digits"] != "" && !nxPhonePatternMatches((string)$aTerritory["leading_digits"], $sCandidateNationalNumber, false)) {
                    continue;
                }
                if (nxPhonePatternMatches((string)$aTerritory["pattern"], $sCandidateNationalNumber, true)) {
                    return array(
                        "country_code" => $sCountryCode,
                        "national_number" => $sCandidateNationalNumber,
                        "territory" => $aTerritory
                    );
                }
            }
        }
        return false;
    }
    return false;
}

function nxPhoneDefaultFormats($sCountryCode) {
    $aMetadata = nxPhoneMetadata();
    $aFallbackFormats = array();
    if (!isset($aMetadata["codes"][(string)$sCountryCode])) {
        return array();
    }
    foreach ($aMetadata["codes"][(string)$sCountryCode] as $aTerritory) {
        if (count($aTerritory["formats"]) > 0 && !empty($aTerritory["main"])) {
            return $aTerritory["formats"];
        }
        if (!$aFallbackFormats && count($aTerritory["formats"]) > 0) {
            $aFallbackFormats = $aTerritory["formats"];
        }
    }
    return $aFallbackFormats;
}

function nxApplyPhoneNumberFormat($sPattern, $sFormat, $sNationalNumber) {
    $aMatches = array();
    $sFormatted = (string)$sFormat;
    if ($sFormatted == "" || !nxPhonePatternMatches($sPattern, $sNationalNumber, true, $aMatches)) {
        return "";
    }
    for ($iIndex = 1; $iIndex < count($aMatches); $iIndex++) {
        $sFormatted = str_replace("$" . $iIndex, $aMatches[$iIndex], $sFormatted);
    }
    return $sFormatted;
}

function nxFormatPhoneContactDisplayValue($sCountryCode, $sNationalNumber, $aTerritory) {
    $aFormats = count($aTerritory["formats"]) > 0 ? $aTerritory["formats"] : nxPhoneDefaultFormats($sCountryCode);
    foreach ($aFormats as $aFormat) {
        $aLeadingDigits = $aFormat["leading_digits"];
        if (count($aLeadingDigits) > 0 && !nxPhonePatternMatches($aLeadingDigits[count($aLeadingDigits) - 1], $sNationalNumber, false)) {
            continue;
        }
        $sFormatted = nxApplyPhoneNumberFormat((string)$aFormat["pattern"], (string)$aFormat["format"], $sNationalNumber);
        if ($sFormatted != "") {
            return "+" . (string)$sCountryCode . " " . $sFormatted;
        }
    }
    return "+" . (string)$sCountryCode . " " . (string)$sNationalNumber;
}

function nxAnalyzePhoneContactValue($sValue) {
    $sText = trim((string)$sValue);
    $sDigits = "";
    $aPhone = array();
    if ($sText == "") {
        return array("valid" => true, "canonical" => "", "display" => "");
    }
    if (!preg_match("/^(?:\\+|00)/", $sText) && preg_match("/^[0-9().\\s\\-]+$/", $sText)) {
        $sDigits = preg_replace("/\\D/", "", $sText);
        $sText = "+420" . $sDigits;
    }
    if (!preg_match("/^(?:\\+|00)[0-9().\\s\\-]+$/", $sText)) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    if (strpos($sText, "+") === 0) {
        $sDigits = preg_replace("/\\D/", "", substr($sText, 1));
    } else {
        $sDigits = preg_replace("/\\D/", "", substr($sText, 2));
    }
    if (!preg_match("/^[1-9][0-9]{5,14}$/", $sDigits)) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    $aPhone = nxFindPhoneTerritory($sDigits);
    if ($aPhone === false) {
        return array("valid" => false, "canonical" => false, "display" => $sText);
    }
    return array(
        "valid" => true,
        "canonical" => "+" . (string)$aPhone["country_code"] . "." . (string)$aPhone["national_number"],
        "display" => nxFormatPhoneContactDisplayValue((string)$aPhone["country_code"], (string)$aPhone["national_number"], $aPhone["territory"])
    );
}

function nxNormalizePhoneContactValue($sValue) {
    $aPhone = nxAnalyzePhoneContactValue($sValue);
    if (empty($aPhone["valid"])) {
        return false;
    }
    if (strpos((string)$aPhone["canonical"], "00") === 0) {
        return "+" . substr((string)$aPhone["canonical"], 2);
    }
    return (string)$aPhone["canonical"];
}

function nxPhoneContactDisplayValue($sValue) {
    $aPhone = nxAnalyzePhoneContactValue($sValue);
    return !empty($aPhone["valid"]) ? (string)$aPhone["display"] : (string)$sValue;
}

function nxPhoneContactHref($sValue) {
    $aPhone = nxAnalyzePhoneContactValue($sValue);
    return !empty($aPhone["valid"]) && $aPhone["canonical"] != "" ? "tel:" . str_replace(".", "", (string)$aPhone["canonical"]) : "";
}

function nxContactTypeKey($sContactType) {
    return strtolower(trim((string)$sContactType));
}

function nxNormalizeContactInputForStorage($sContactType, $sContactValue) {
    $mKnownValue = null;
    $sContactType = nxContactTypeKey($sContactType);

    if (nxIsPhoneContactType($sContactType)) {
        return nxNormalizePhoneContactValue($sContactValue);
    }
    if ((string)$sContactType == "youtube") {
        return nxNormalizeYouTubeContactValue($sContactValue, true);
    }
    if ((string)$sContactType == "telegram") {
        return nxNormalizeTelegramContactValue($sContactValue);
    }
    if ((string)$sContactType == "email") {
        return nxNormalizeEmailContactValue($sContactValue);
    }
    if ((string)$sContactType == "icq") {
        return nxNormalizeIcqContactValue($sContactValue);
    }
    if ((string)$sContactType == "skype") {
        return nxNormalizeSkypeContactValue($sContactValue);
    }
    $mKnownValue = nxNormalizeKnownContactValue($sContactType, $sContactValue);
    if ($mKnownValue !== null) {
        return $mKnownValue;
    }
    return trim((string)$sContactValue);
}

function nxContactCanonicalValue($sContactType, $sContactValue) {
    $mKnownValue = null;
    $sContactType = nxContactTypeKey($sContactType);

    if (nxIsPhoneContactType($sContactType)) {
        $mKnownValue = nxNormalizePhoneContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "youtube") {
        $mKnownValue = nxNormalizeYouTubeContactValue($sContactValue, true);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "telegram") {
        $mKnownValue = nxNormalizeTelegramContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "email") {
        $mKnownValue = nxNormalizeEmailContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "icq") {
        $mKnownValue = nxNormalizeIcqContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType == "skype") {
        $mKnownValue = nxNormalizeSkypeContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    $mKnownValue = nxNormalizeKnownContactValue($sContactType, $sContactValue);
    if ($mKnownValue !== null) {
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    return (string)$sContactValue;
}

function nxContactInputErrorMessage($sContactType) {
    $sContactType = nxContactTypeKey($sContactType);

    if (nxIsPhoneContactType($sContactType)) {
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
    if (nxNormalizeKnownContactValue($sContactType, "") !== null) {
        return "Contact value has invalid format for this contact type.";
    }
    return "Contact value is invalid.";
}

function nxContactValueIsInvalid($sType, $sValue) {
    $mKnownValue = null;
    $sType = nxContactTypeKey($sType);

    if (trim((string)$sValue) == "") {
        return false;
    }
    if (nxIsPhoneContactType($sType)) {
        return nxNormalizePhoneContactValue($sValue) === false;
    }
    if ((string)$sType == "youtube") {
        return nxNormalizeYouTubeContactValue($sValue, true) === false;
    }
    if ((string)$sType == "telegram") {
        return nxNormalizeTelegramContactValue($sValue) === false;
    }
    if ((string)$sType == "email") {
        return nxNormalizeEmailContactValue($sValue) === false;
    }
    if ((string)$sType == "icq") {
        return nxNormalizeIcqContactValue($sValue) === false;
    }
    if ((string)$sType == "skype") {
        return nxNormalizeSkypeContactValue($sValue) === false;
    }
    $mKnownValue = nxNormalizeKnownContactValue($sType, $sValue);
    if ($mKnownValue !== null) {
        return $mKnownValue === false;
    }
    return false;
}

function nxYouTubeContactHref($sValue) {
    $sValue = trim((string)$sValue);
    if ($sValue == "") {
        return "";
    }
    $sValue = nxNormalizeYouTubeContactValue($sValue, true);
    return $sValue !== false ? $sValue : "";
}

function nxNormalizeWebContactValue($sValue) {
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

function nxContactProfileRules() {
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

function nxNormalizeProfileContactValue($sContactType, $sValue) {
    $aRules = nxContactProfileRules();
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
    $blLooksLikeUrl = preg_match("#^https?://#i", $sText) === 1
        || preg_match("#^www\\.#i", $sText) === 1
        || preg_match("#^[A-Za-z0-9.-]+\\.[A-Za-z]{2,}[/:?\\#].*$#", $sText) === 1;
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
        if ($sPath == "") {
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
    if (!preg_match($aRule["pattern"], $sHandle)) {
        return false;
    }
    return (string)$aRule["base"] . rawurlencode($sHandle);
}

function nxNormalizeLinkedInContactValue($sValue) {
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
    if (!preg_match("/^[A-Za-z0-9_-]{2,100}$/", $sHandle)) {
        return false;
    }
    return "https://www.linkedin.com/" . $sKind . "/" . rawurlencode($sHandle);
}

function nxNormalizeStackOverflowContactValue($sValue) {
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
    if (!preg_match("/^[0-9]+$/", $sUserId)) {
        return false;
    }
    return "https://stackoverflow.com/users/" . $sUserId;
}

function nxNormalizeSteamContactValue($sValue) {
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
    if ($sKind == "id" && !preg_match("/^[A-Za-z0-9_-]{2,64}$/", $sValuePart)) {
        return false;
    }
    return "https://steamcommunity.com/" . $sKind . "/" . rawurlencode($sValuePart);
}

function nxNormalizeGoodreadsContactValue($sValue) {
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
    if (!preg_match("/^[0-9]+$/", $sText)) {
        return false;
    }
    return "https://www.goodreads.com/user/show/" . $sText;
}

function nxNormalizeFederatedContactValue($sValue, $sPathPrefix) {
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

function nxIsAtprotoHandle($sHandle) {
    return preg_match("/^([A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\\.)+[A-Za-z](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/", (string)$sHandle) === 1;
}

function nxNormalizeBlueskyContactValue($sValue) {
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
    if (!nxIsAtprotoHandle($sHandle)) {
        return false;
    }
    return "https://bsky.app/profile/" . rawurlencode($sHandle);
}

function nxNormalizeMatrixContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText == "") {
        return "";
    }
    if (preg_match("#^https?://matrix\\.to/\\#/(@[^?\\#]+)#i", $sText, $aMatches)) {
        $sText = rawurldecode($aMatches[1]);
    }
    if (!preg_match("/^@[a-z0-9._=\\-\\/+]+:[A-Za-z0-9.-]+(?::[0-9]+)?$/", $sText)) {
        return false;
    }
    return $sText;
}

function nxNormalizeJabberContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText == "") {
        return "";
    }
    $sText = preg_replace("#^xmpp:#i", "", $sText);
    if (!preg_match("#^[^@\\s/]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}(?:/[^\\s]+)?$#", $sText)) {
        return false;
    }
    return $sText;
}

function nxOrcidCheckDigit($sDigits) {
    $iTotal = 0;
    for ($iI = 0; $iI < strlen($sDigits); $iI++) {
        $iTotal = ($iTotal + (int)$sDigits[$iI]) * 2;
    }
    $iResult = (12 - ($iTotal % 11)) % 11;
    return $iResult === 10 ? "X" : (string)$iResult;
}

function nxNormalizeOrcidContactValue($sValue) {
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
    if (nxOrcidCheckDigit(substr($sId, 0, 15)) !== substr($sId, 15, 1)) {
        return false;
    }
    return "https://orcid.org/" . substr($sId, 0, 4) . "-" . substr($sId, 4, 4) . "-" . substr($sId, 8, 4) . "-" . substr($sId, 12, 4);
}

function nxNormalizeMessagingPhoneContactValue($sValue, $sContactType) {
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
    $sDigits = nxNormalizePhoneContactValue($sText);
    return $sDigits !== false ? $sDigits : false;
}

function nxNormalizeKnownContactValue($sContactType, $sContactValue) {
    $sContactType = nxContactTypeKey($sContactType);
    $mProfileValue = nxNormalizeProfileContactValue($sContactType, $sContactValue);
    if (!nxIsOriginalContactType($sContactType)) {
        return null;
    }
    if ((string)$sContactType == "telegram") {
        return nxNormalizeTelegramContactValue($sContactValue);
    }
    if ($mProfileValue !== null) {
        return $mProfileValue;
    }
    if ((string)$sContactType == "web") {
        return nxNormalizeWebContactValue($sContactValue);
    }
    if ((string)$sContactType == "jabber") {
        return nxNormalizeJabberContactValue($sContactValue);
    }
    if ((string)$sContactType == "matrix") {
        return nxNormalizeMatrixContactValue($sContactValue);
    }
    if ((string)$sContactType == "mastodon") {
        return nxNormalizeFederatedContactValue($sContactValue, "@");
    }
    if ((string)$sContactType == "lemmy") {
        return nxNormalizeFederatedContactValue($sContactValue, "u");
    }
    if ((string)$sContactType == "bluesky") {
        return nxNormalizeBlueskyContactValue($sContactValue);
    }
    if ((string)$sContactType == "linkedin") {
        return nxNormalizeLinkedInContactValue($sContactValue);
    }
    if ((string)$sContactType == "stackoverflow") {
        return nxNormalizeStackOverflowContactValue($sContactValue);
    }
    if ((string)$sContactType == "steam") {
        return nxNormalizeSteamContactValue($sContactValue);
    }
    if ((string)$sContactType == "goodreads") {
        return nxNormalizeGoodreadsContactValue($sContactValue);
    }
    if ((string)$sContactType == "orcid") {
        return nxNormalizeOrcidContactValue($sContactValue);
    }
    if ((string)$sContactType == "whatsapp" || (string)$sContactType == "viber") {
        return nxNormalizeMessagingPhoneContactValue($sContactValue, $sContactType);
    }
    return null;
}

function nxKnownContactLinkTypes() {
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

function nxContactTypeHasKnownLink($sType) {
    $aTypes = nxKnownContactLinkTypes();
    return isset($aTypes[nxContactTypeKey($sType)]);
}

function nxContactDisplayValue($sType, $sValue) {
    $sType = nxContactTypeKey($sType);
    $sCanonicalValue = nxContactCanonicalValue($sType, $sValue);

    if (nxIsPhoneContactType($sType) || (string)$sType == "whatsapp" || (string)$sType == "viber") {
        return nxPhoneContactDisplayValue($sCanonicalValue);
    }
    return $sCanonicalValue;
}

function nxContactHref($sType, $sValue, $blAllowExternalLinks = false) {
    $sType = nxContactTypeKey($sType);
    $sText = trim((string)$sValue);
    $mKnownValue = nxNormalizeKnownContactValue($sType, $sValue);
    if (nxIsPhoneContactType($sType)) {
        return nxPhoneContactHref($sValue);
    }
    if ($sType == "email") {
        $sText = nxNormalizeEmailContactValue($sValue);
        return $sText !== false && $sText != "" ? "mailto:" . $sText : "";
    }
    if ($sType == "jabber") {
        $sText = nxNormalizeJabberContactValue($sValue);
        return $sText !== false && $sText != "" ? "xmpp:" . $sText : "";
    }
    if ($sType == "matrix") {
        $sText = nxNormalizeMatrixContactValue($sValue);
        return $sText !== false && $sText != "" ? "https://matrix.to/#/" . rawurlencode($sText) : "";
    }
    if ($sType == "whatsapp") {
        $sText = nxNormalizeMessagingPhoneContactValue($sValue, $sType);
        return $sText !== false && $sText != "" ? "https://wa.me/" . preg_replace("/\\D/", "", $sText) : "";
    }
    if ($sType == "viber") {
        $sText = nxNormalizeMessagingPhoneContactValue($sValue, $sType);
        return $sText !== false && $sText != "" ? "viber://chat?number=%2B" . preg_replace("/\\D/", "", $sText) : "";
    }
    if ($blAllowExternalLinks && $mKnownValue !== null && $mKnownValue !== false && preg_match("#^https?://#i", (string)$mKnownValue)) {
        return (string)$mKnownValue;
    }
    if ($blAllowExternalLinks && $sType == "web") {
        $sText = nxNormalizeWebContactValue($sValue);
        if ($sText === false || $sText == "") {
            return "";
        }
        return $sText;
    }
    if ($blAllowExternalLinks && $sType == "telegram") {
        $sText = nxNormalizeTelegramContactValue($sValue);
        return $sText !== false ? $sText : "";
    }
    if ($blAllowExternalLinks && $sType == "youtube") {
        return nxYouTubeContactHref($sValue);
    }
    return "";
}

function nxContactLinkEmoji($sType) {
    global $sContactEmailEmoji, $sContactLandlineEmoji, $sContactCellEmoji, $sContactFaxEmoji, $sContactPagerEmoji, $sContactWebEmoji, $sContactTelegramEmoji, $sContactMessageEmoji, $sContactYouTubeEmoji;

    $sType = nxContactTypeKey($sType);

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
    if (nxContactTypeHasKnownLink($sType)) {
        return $sContactWebEmoji;
    }
    return "";
}

function nxContactLinkTitle($sType) {
    $sType = nxContactTypeKey($sType);

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
    if (nxContactTypeHasKnownLink($sType)) {
        return "Open web";
    }
    return "";
}

function nxRenderContactValue($sType, $sValue, $blShowCopy = false, $blAllowExternalLinks = false, $sTooltipAttribute = "") {
    global $sCopyEmoji;

    $sDisplayValue = nxContactDisplayValue($sType, $sValue);
    $sHref = nxContactHref($sType, $sValue, $blAllowExternalLinks);
    $sClass = "nx-contact-value" . (nxContactValueIsInvalid($sType, $sValue) ? " nx-invalid-contact-value" : "");
    $sHtml = "<span class=\"" . nxHtml($sClass) . "\"" . $sTooltipAttribute . ">" . nxHtml($sDisplayValue) . "</span>";
    $sLinkTitle = "";
    $blHasIcon = false;
    if ($blShowCopy && $sDisplayValue != "") {
        $sHtml .= "<a class=\"nx-contact-copy\" href=\"#\" title=\"Copy\" aria-label=\"Copy\"><span class=\"nx-copy-action-box\">" . $sCopyEmoji . "</span></a>";
        $blHasIcon = true;
    }
    if ($sHref != "") {
        $sTarget = $blAllowExternalLinks && preg_match("#^https?://#i", $sHref) ? " target=\"_blank\" rel=\"noopener noreferrer\"" : "";
        $sLinkTitle = nxContactLinkTitle($sType);
        return $sHtml . ($blHasIcon ? "" : " ") . "<a class=\"nx-contact-link\" href=\"" . nxHtml($sHref) . "\"" . $sTarget . " title=\"" . nxHtml($sLinkTitle) . "\" aria-label=\"" . nxHtml($sLinkTitle) . "\">" . nxContactLinkEmoji($sType) . "</a>";
    }
    return $sHtml;
}

function nxSendJsonAndExit($aData, $iStatusCode = 200) {
    sendSecurityHeaders();
    http_response_code($iStatusCode);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");
    echo json_encode($aData);
    exit;
}

function nxDecodePostedBase64Value($sValue) {
    $sDecoded = base64_decode((string)$sValue, true);
    return $sDecoded !== false ? $sDecoded : (string)$sValue;
}

function nxGetPostedValue($sName, $sDefault = "") {
    $sEncodedName = $sName . "_b64";
    if (isset($_POST[$sEncodedName]) && !is_array($_POST[$sEncodedName])) {
        return nxDecodePostedBase64Value($_POST[$sEncodedName]);
    }
    if (isset($_POST[$sName]) && !is_array($_POST[$sName])) {
        return (string)$_POST[$sName];
    }
    return (string)$sDefault;
}

function nxGetPostedTrimmedValue($sName, $sDefault = "") {
    return trim(nxGetPostedValue($sName, $sDefault));
}

function nxPostalCodeMetadata() {
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

function nxPostalCodePatternMatches($sPattern, $sPostalCode) {
    $sPattern = trim((string)$sPattern);
    if ($sPattern == "") {
        return true;
    }
    return @preg_match("~^(?:" . str_replace("~", "\\~", $sPattern) . ")$~i", (string)$sPostalCode) === 1;
}

function nxPostalCodeAlnum($sPostalCode) {
    return preg_replace("/[^A-Z0-9]/", "", strtoupper((string)$sPostalCode));
}

function nxAddressCountryCode($sCountry) {
    $sCountry = strtoupper(trim((string)$sCountry));
    return $sCountry == "CS" ? "CZ" : $sCountry;
}

function nxPostalCodeFormatByExample($sPostalCode, $sExamples) {
    $sAlnum = nxPostalCodeAlnum($sPostalCode);
    $aExamples = explode(",", (string)$sExamples);
    $sExample = "";
    $sFormatted = "";
    $iIndex = 0;
    if ($sAlnum == "") {
        return "";
    }
    foreach ($aExamples as $sExampleCandidate) {
        if (strlen(nxPostalCodeAlnum($sExampleCandidate)) == strlen($sAlnum)) {
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

function nxAnalyzePostalCode($sCountry, $sPostalCode) {
    $sCountry = nxAddressCountryCode($sCountry);
    $sText = strtoupper(trim((string)$sPostalCode));
    $aMetadata = nxPostalCodeMetadata();
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
    $aCandidates[] = nxPostalCodeAlnum($sText);
    $aCandidates[] = nxPostalCodeFormatByExample($sText, $sExamples);
    foreach ($aCandidates as $sCandidate) {
        $sCandidate = trim((string)$sCandidate);
        if ($sCandidate != "" && nxPostalCodePatternMatches($sPattern, $sCandidate)) {
            return array("valid" => true, "value" => nxPostalCodeFormatByExample($sCandidate, $sExamples));
        }
    }
    return array("valid" => false, "value" => $sText);
}

function nxNormalizePostalCode($sCountry, $sPostalCode) {
    $aPostalCode = nxAnalyzePostalCode($sCountry, $sPostalCode);
    return !empty($aPostalCode["valid"]) ? (string)$aPostalCode["value"] : false;
}

function nxPostalCodeDisplayValue($sCountry, $sPostalCode) {
    $aPostalCode = nxAnalyzePostalCode($sCountry, $sPostalCode);
    return !empty($aPostalCode["valid"]) ? (string)$aPostalCode["value"] : (string)$sPostalCode;
}

function nxGetPostedValues($sName) {
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
            $aValues[] = nxDecodePostedBase64Value($mValue);
        }
        return $aValues;
    }
    return $aRawValues;
}

function nxRenderAddSubjectItemAction($sClass, $sTitle, $iSubjectId, $sPrefix = "", $sSuffix = "") {
    global $sAddEmoji, $sEmptyValueEmoji;

    if ((int)$iSubjectId < 1) {
        return $sEmptyValueEmoji;
    }
    return "<div class=\"nx-add-item-row\">" . $sPrefix . "<a href=\"#\" class=\"nx-item-action nx-add-item-action " . nxHtml($sClass) . "\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" title=\"" . nxHtml($sTitle) . "\" aria-label=\"" . nxHtml($sTitle) . "\">" . $sAddEmoji . "</a>" . $sSuffix . "</div>";
}

function nxRenderSubjectCellActionRow($sFirstAction, $sSecondAction = "") {
    if ($sFirstAction == "") {
        return $sSecondAction;
    }
    if ($sSecondAction == "") {
        return $sFirstAction;
    }
    return "<div class=\"nx-add-item-row\">" . $sFirstAction . $sSecondAction . "</div>";
}

function nxRenderHiddenInactiveIndicator() {
    global $sHiddenInactiveEmoji;

    return "<span class=\"nx-hidden-inactive-indicator\" title=\"Hidden inactive content\" aria-label=\"Hidden inactive content\">" . $sHiddenInactiveEmoji . "</span>";
}

function nxRenderEmptySubjectItemCell($blShowActions, $sClass, $sTitle, $iSubjectId, $blHasHiddenInactive, $blShowAddAction = true) {
    global $sEmptyValueEmoji;

    $sHiddenInactive = $blHasHiddenInactive ? nxRenderHiddenInactiveIndicator() : "";
    if ($blShowActions && $blShowAddAction) {
        return nxRenderAddSubjectItemAction($sClass, $sTitle, $iSubjectId, $sHiddenInactive);
    }
    return $sHiddenInactive != "" ? $sHiddenInactive : $sEmptyValueEmoji;
}

function nxRenderContactList($aContacts, $blShowActions = true, $iSubjectId = 0, $blShowCopy = true, $blAllowExternalLinks = true, $blHasHiddenInactive = false, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aContacts) {
        return nxRenderEmptySubjectItemCell($blShowActions, "js-add-subject-contact", "New contact", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"nx-contact-list\">";
    $aCellCopyValues = array();
    $sHiddenInactiveAction = $blHasHiddenInactive ? nxRenderHiddenInactiveIndicator() : "";
    foreach ($aContacts as $aContact) {
        $sNote = trim((string)$aContact["note"]);
        $blIsPrimary = (int)$aContact["is_primary"] == 1;
        $blIsActive = (int)$aContact["is_active"] == 1;
        $sContactType = isset($aContact["contact_type"]) ? (string)$aContact["contact_type"] : "";
        $sContactTypeName = isset($aContact["contact_type_name"]) && trim((string)$aContact["contact_type_name"]) != "" ? (string)$aContact["contact_type_name"] : nxContactTypeLabel($sContactType);
        $sContactValue = nxContactDisplayValue($sContactType, $aContact["contact_value"]);
        $aCellCopyValues[] = $sContactTypeName . ": " . $sContactValue . ($sNote != "" ? " (" . $sNote . ")" : "");
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"nx-list-item-actions\">"
                . "<a href=\"#\" class=\"nx-item-action js-edit-subject-contact\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"nx-item-action js-delete-subject-contact\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"nx-contact-item nx-list-item" . ($blIsActive ? "" : " nx-contact-item-inactive") . "\""
            . " data-subject-contact-id=\"" . nxHtml($aContact["subject_contact_id"]) . "\""
            . " data-contact-id=\"" . nxHtml($aContact["contact_id"]) . "\""
            . " data-contact-type-id=\"" . nxHtml(isset($aContact["contact_type_id"]) ? $aContact["contact_type_id"] : "") . "\""
            . " data-contact-type=\"" . nxHtml($sContactType) . "\""
            . " data-contact-type-name=\"" . nxHtml($sContactTypeName) . "\""
            . " data-contact-value=\"" . nxHtml($sContactValue) . "\""
            . " data-contact-note=\"" . nxHtml($sNote) . "\""
            . " data-contact-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-contact-active=\"" . ($blIsActive ? "1" : "0") . "\""
            . nxRenderTimestampTooltipDataAttribute($aContact) . ">"
            . "<span class=\"nx-contact-type\">" . nxHtml($sContactTypeName) . "</span>: "
            . nxRenderContactValue($sContactType, $aContact["contact_value"], $blShowCopy, $blAllowExternalLinks, nxRenderTimestampTooltipAttribute($aContact))
            . "<span class=\"nx-contact-note\">" . ($sNote != "" ? " (" . nxHtml($sNote) . ")" : "") . "</span>"
            . "<span class=\"nx-contact-flags\">"
            . "<span class=\"nx-contact-primary\" title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span>"
            . "<span class=\"nx-contact-inactive-label\" title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span>"
            . "</span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? nxRenderSubjectCellCopyAction($aCellCopyValues) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-contact", "New contact", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= nxRenderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function nxRenderNicknameList($aNicknames, $blShowActions = true, $iSubjectId = 0, $blHasHiddenInactive = false, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aNicknames) {
        return nxRenderEmptySubjectItemCell($blShowActions, "js-add-subject-nickname", "New nickname", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"nx-subject-item-list\">";
    $aCellCopyValues = array();
    $sHiddenInactiveAction = $blHasHiddenInactive ? nxRenderHiddenInactiveIndicator() : "";
    foreach ($aNicknames as $aNickname) {
        $sContext = trim((string)$aNickname["context"]);
        $sNote = trim((string)$aNickname["note"]);
        $sCopyText = $aNickname["nickname"] . ($sContext != "" ? " [" . $sContext . "]" : "") . ($sNote != "" ? " (" . $sNote . ")" : "");
        $aCellCopyValues[] = $sCopyText;
        $blIsPrimary = (int)$aNickname["is_primary"] == 1;
        $blIsActive = (int)$aNickname["is_active"] == 1;
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"nx-list-item-actions\">"
                . "<a href=\"#\" class=\"nx-item-action js-edit-subject-nickname\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"nx-item-action js-delete-subject-nickname\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"nx-subject-item nx-list-item nx-subject-nickname-item" . ($blIsActive ? "" : " nx-subject-item-inactive") . "\""
            . " data-nickname-id=\"" . nxHtml($aNickname["id"]) . "\""
            . " data-subject-id=\"" . nxHtml($aNickname["subject_id"]) . "\""
            . " data-nickname=\"" . nxHtml($aNickname["nickname"]) . "\""
            . " data-context=\"" . nxHtml($sContext) . "\""
            . " data-note=\"" . nxHtml($sNote) . "\""
            . " data-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"nx-subject-item-value\"" . nxRenderTimestampTooltipAttribute($aNickname) . ">" . nxHtml($aNickname["nickname"]) . "</span>"
            . "<span class=\"nx-subject-item-context\">" . ($sContext != "" ? " [" . nxHtml($sContext) . "]" : "") . "</span>"
            . "<span class=\"nx-subject-item-note\">" . ($sNote != "" ? " (" . nxHtml($sNote) . ")" : "") . "</span>"
            . nxRenderCopyAction($sCopyText)
            . "<span class=\"nx-subject-item-flags\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? nxRenderSubjectCellCopyAction($aCellCopyValues) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-nickname", "New nickname", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= nxRenderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function nxAppendAddressCopyLine(&$aLines, $mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue != "") {
        $aLines[] = $sValue;
    }
}

function nxCleanAddressLine($sLine) {
    $sLine = preg_replace("/[ \\t]+/", " ", trim((string)$sLine));
    $sLine = preg_replace("/\\s+,/", ",", $sLine);
    $sLine = preg_replace("/,\\s*,+/", ",", $sLine);
    return trim($sLine, " ,");
}

function nxAppendAddressTemplateValue(&$aLines, $sValue) {
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

function nxAddressMetadata($sCountry) {
    $sCountry = nxAddressCountryCode($sCountry);
    $aMetadata = nxPostalCodeMetadata();
    return isset($aMetadata[$sCountry]) && is_array($aMetadata[$sCountry]) ? $aMetadata[$sCountry] : array();
}

function nxAddressStreetLine($aAddress, $sCountryCode) {
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

function nxAddressCityLine($aAddress) {
    $sCity = trim((string)$aAddress["city"]);
    $sCityPart = trim((string)$aAddress["city_part"]);
    return trim($sCity . ($sCity != "" && $sCityPart != "" ? "-" : "") . $sCityPart);
}

function nxAddressOrganizationLine($aAddress) {
    $aLines = array();
    nxAppendAddressCopyLine($aLines, $aAddress["organization_name"]);
    nxAppendAddressCopyLine($aLines, $aAddress["department_name"]);
    return implode("\n", $aLines);
}

function nxAddressAddressLine($aAddress, $sCountryCode) {
    $aLines = array();
    nxAppendAddressCopyLine($aLines, trim((string)$aAddress["care_of"]) != "" ? "c/o " . trim((string)$aAddress["care_of"]) : "");
    nxAppendAddressCopyLine($aLines, nxAddressStreetLine($aAddress, $sCountryCode));
    nxAppendAddressCopyLine($aLines, $aAddress["address_line2"]);
    return implode("\n", $aLines);
}

function nxAddressFormatTemplate($sCountryCode) {
    $aMetadata = nxAddressMetadata($sCountryCode);
    $sFormat = isset($aMetadata["fmt"]) ? trim((string)$aMetadata["fmt"]) : "";
    return $sFormat != "" ? $sFormat : "%N%n%O%n%A%n%Z %C";
}

function nxBuildAddressLines($aAddress, $sSubjectName = "", $aSettings = null, $blDisplayCountry = true) {
    $sCountryCode = nxAddressCountryCode($aAddress["country"]);
    $sPostalCode = nxPostalCodeDisplayValue($sCountryCode, $aAddress["postal_code"]);
    $sFormat = nxAddressFormatTemplate($sCountryCode);
    $sCity = trim((string)$aAddress["city"]);
    $sCityPart = trim((string)$aAddress["city_part"]);
    $aFields = array(
        "N" => trim((string)$sSubjectName),
        "O" => nxAddressOrganizationLine($aAddress),
        "A" => nxAddressAddressLine($aAddress, $sCountryCode),
        "C" => strpos($sFormat, "%D") !== false ? $sCity : nxAddressCityLine($aAddress),
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
                nxAppendAddressTemplateValue($aLines, $aFields[$sToken]);
            }
        } else {
            $aLines[count($aLines) - 1] .= $sChar;
        }
    }
    $aCleanLines = array();
    foreach ($aLines as $sLine) {
        $sLine = nxCleanAddressLine($sLine);
        if ($sLine != "") {
            $aCleanLines[] = $sLine;
        }
    }
    if ($blDisplayCountry) {
        $sCountry = is_array($aSettings) ? nxCountryCodeToDisplayName($aAddress["country"], $aSettings) : nxCountryCodeToName($aAddress["country"]);
        nxAppendAddressCopyLine($aCleanLines, $sCountry);
    }
    return $aCleanLines;
}

function nxRenderAddressText($aAddress, $aSettings = null) {
    return implode(", ", nxBuildAddressLines($aAddress, "", $aSettings, true));
}

function nxRenderAddressCopyText($aAddress, $sSubjectName = "", $aSettings = null) {
    $aLines = nxBuildAddressLines($aAddress, $sSubjectName, $aSettings, true);
    nxAppendAddressCopyLine($aLines, $aAddress["note"]);
    return implode("\n", $aLines);
}

function nxRenderAddressList($aAddresses, $blShowActions = true, $iSubjectId = 0, $sSubjectName = "", $blHasHiddenInactive = false, $aAddressDisplaySettings = null, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aAddresses) {
        return nxRenderEmptySubjectItemCell($blShowActions, "js-add-subject-address", "New address", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"nx-subject-item-list\">";
    $aCellCopyValues = array();
    $sHiddenInactiveAction = $blHasHiddenInactive ? nxRenderHiddenInactiveIndicator() : "";
    foreach ($aAddresses as $aAddress) {
        $sText = nxRenderAddressText($aAddress, $aAddressDisplaySettings);
        $sNote = trim((string)$aAddress["note"]);
        $sCopyText = nxRenderAddressCopyText($aAddress, $sSubjectName, $aAddressDisplaySettings);
        $aCellCopyValues[] = $sText . ($sNote != "" ? " (" . $sNote . ")" : "");
        $blIsPrimary = (int)$aAddress["is_primary"] == 1;
        $blIsActive = (int)$aAddress["is_active"] == 1;
        $sValueClass = (string)$aAddress["address_type"] == "main" ? " nx-subject-address-main-value" : "";
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"nx-list-item-actions\">"
                . "<a href=\"#\" class=\"nx-item-action js-edit-subject-address\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"nx-item-action js-delete-subject-address\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"nx-subject-item nx-list-item nx-subject-address-item" . ($blIsActive ? "" : " nx-subject-item-inactive") . "\""
            . " data-address-id=\"" . nxHtml($aAddress["id"]) . "\""
            . " data-subject-id=\"" . nxHtml($aAddress["subject_id"]) . "\""
            . " data-address-type=\"" . nxHtml($aAddress["address_type"]) . "\""
            . " data-organization-name=\"" . nxHtml($aAddress["organization_name"]) . "\""
            . " data-department-name=\"" . nxHtml($aAddress["department_name"]) . "\""
            . " data-care-of=\"" . nxHtml($aAddress["care_of"]) . "\""
            . " data-street-name=\"" . nxHtml($aAddress["street_name"]) . "\""
            . " data-house-number=\"" . nxHtml($aAddress["house_number"]) . "\""
            . " data-evidence-number=\"" . nxHtml($aAddress["evidence_number"]) . "\""
            . " data-orientation-number=\"" . nxHtml($aAddress["orientation_number"]) . "\""
            . " data-orientation-suffix=\"" . nxHtml($aAddress["orientation_suffix"]) . "\""
            . " data-address-line2=\"" . nxHtml($aAddress["address_line2"]) . "\""
            . " data-city=\"" . nxHtml($aAddress["city"]) . "\""
            . " data-city-part=\"" . nxHtml($aAddress["city_part"]) . "\""
            . " data-postal-code=\"" . nxHtml(nxPostalCodeDisplayValue($aAddress["country"], $aAddress["postal_code"])) . "\""
            . " data-region=\"" . nxHtml($aAddress["region"]) . "\""
            . " data-country=\"" . nxHtml($aAddress["country"]) . "\""
            . " data-note=\"" . nxHtml($sNote) . "\""
            . " data-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"nx-subject-item-value" . $sValueClass . "\"" . nxRenderTimestampTooltipAttribute($aAddress) . ">" . ($sText != "" ? nxHtml($sText) : $sEmptyValueEmoji) . "</span>"
            . "<span class=\"nx-subject-item-note\">" . ($sNote != "" ? " (" . nxHtml($sNote) . ")" : "") . "</span>"
            . nxRenderCopyAction($sCopyText)
            . "<span class=\"nx-subject-item-flags\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? nxRenderSubjectCellCopyAction($aCellCopyValues, true) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-address", "New address", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= nxRenderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function nxRenderGroupList($aGroups, $blShowActions = true, $iSubjectId = 0, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sEmptyValueEmoji;

    if (!$aGroups) {
        return $blShowActions && $blShowAddAction ? nxRenderAddSubjectItemAction("js-add-subject-group", "Assign group", $iSubjectId) : $sEmptyValueEmoji;
    }
    $sHtml = "<div class=\"nx-subject-item-list\">";
    $aCellCopyValues = array();
    foreach ($aGroups as $aGroup) {
        $aCellCopyValues[] = $aGroup["name"];
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"nx-list-item-actions\">"
                . "<a href=\"#\" class=\"nx-item-action js-edit-subject-group\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"nx-item-action js-delete-subject-group\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"nx-subject-item nx-list-item nx-subject-group-item\""
            . " data-subject-id=\"" . nxHtml($aGroup["subject_id"]) . "\""
            . " data-group-id=\"" . nxHtml($aGroup["group_id"]) . "\""
            . " data-group-name=\"" . nxHtml($aGroup["name"]) . "\""
            . nxRenderTimestampTooltipDataAttribute($aGroup) . ">"
            . "<span class=\"nx-subject-item-value\"" . nxRenderTimestampTooltipAttribute($aGroup) . ">" . nxHtml($aGroup["name"]) . "</span>"
            . nxRenderCopyAction($aGroup["name"])
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? nxRenderSubjectCellCopyAction($aCellCopyValues) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-group", "Assign group", $iSubjectId, $blCellCopyBeforeAddAction ? $sCellCopyAction : "", $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= $sCellCopyAction;
    }
    return $sHtml . "</div>";
}

function nxRenderNoteList($aNotes, $blShowActions = true, $iSubjectId = 0, $blHasHiddenInactive = false, $blShowAddAction = true, $blShowCellCopyAction = false, $blCellCopyBeforeAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji, $sPrimaryEmoji, $sInactiveEmoji;

    if (!$aNotes) {
        return nxRenderEmptySubjectItemCell($blShowActions, "js-add-subject-note", "New note", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"nx-subject-item-list\">";
    $aCellCopyValues = array();
    $sHiddenInactiveAction = $blHasHiddenInactive ? nxRenderHiddenInactiveIndicator() : "";
    foreach ($aNotes as $aNote) {
        $aCellCopyValues[] = $aNote["note_text"];
        $blIsActive = (int)$aNote["is_active"] == 1;
        $blIsPrimary = (int)$aNote["is_primary"] == 1;
        $sActions = "";
        if ($blShowActions) {
            $sActions = "<span class=\"nx-list-item-actions\">"
                . "<a href=\"#\" class=\"nx-item-action js-edit-subject-note\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
                . "<a href=\"#\" class=\"nx-item-action js-delete-subject-note\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
                . "</span>";
        }
        $sHtml .= "<div class=\"nx-subject-item nx-list-item nx-subject-note-item" . ($blIsActive ? "" : " nx-subject-item-inactive") . "\""
            . " data-note-id=\"" . nxHtml($aNote["id"]) . "\""
            . " data-subject-id=\"" . nxHtml($aNote["subject_id"]) . "\""
            . " data-primary=\"" . ($blIsPrimary ? "1" : "0") . "\""
            . " data-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"nx-subject-item-value\"" . nxRenderTimestampTooltipAttribute($aNote) . ">" . nxHtmlMultiline($aNote["note_text"]) . "</span>"
            . nxRenderCopyAction($aNote["note_text"])
            . "<span class=\"nx-subject-item-flags\"><span title=\"Primary\">" . ($blIsPrimary ? $sPrimaryEmoji : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : $sInactiveEmoji) . "</span></span>"
            . "<span class=\"nx-subject-note-source\">" . nxHtml($aNote["note_text"]) . "</span>"
            . $sActions
            . "</div>";
    }
    $sCellCopyAction = $blShowCellCopyAction ? nxRenderSubjectCellCopyAction($aCellCopyValues) : "";
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-note", "New note", $iSubjectId, ($blCellCopyBeforeAddAction ? $sCellCopyAction : "") . $sHiddenInactiveAction, $blCellCopyBeforeAddAction ? "" : $sCellCopyAction);
    } else {
        $sHtml .= nxRenderSubjectCellActionRow($sCellCopyAction, $sHiddenInactiveAction);
    }
    return $sHtml . "</div>";
}

function nxGetSubjectTypes() {
    return array("person", "organization", "service", "other");
}

function nxGetAddressTypes() {
    return array("main", "home", "cottage", "work", "office", "registered", "delivery", "billing", "foreign", "temporary", "old", "other");
}

function nxAddressTypeLabel($sType) {
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

function nxGetCountryCodes() {
    return array("AD", "AE", "AF", "AG", "AI", "AL", "AM", "AO", "AQ", "AR", "AS", "AT", "AU", "AW", "AX", "AZ", "BA", "BB", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BL", "BM", "BN", "BO", "BQ", "BR", "BS", "BT", "BV", "BW", "BY", "BZ", "CA", "CC", "CD", "CF", "CG", "CH", "CI", "CK", "CL", "CM", "CN", "CO", "CR", "CS", "CU", "CV", "CW", "CX", "CY", "CZ", "DE", "DJ", "DK", "DM", "DO", "DZ", "EC", "EE", "EG", "EH", "ER", "ES", "ET", "FI", "FJ", "FK", "FM", "FO", "FR", "GA", "GB", "GD", "GE", "GF", "GG", "GH", "GI", "GL", "GM", "GN", "GP", "GQ", "GR", "GS", "GT", "GU", "GW", "GY", "HK", "HM", "HN", "HR", "HT", "HU", "ID", "IE", "IL", "IM", "IN", "IO", "IQ", "IR", "IS", "IT", "JE", "JM", "JO", "JP", "KE", "KG", "KH", "KI", "KM", "KN", "KP", "KR", "KW", "KY", "KZ", "LA", "LB", "LC", "LI", "LK", "LR", "LS", "LT", "LU", "LV", "LY", "MA", "MC", "MD", "ME", "MF", "MG", "MH", "MK", "ML", "MM", "MN", "MO", "MP", "MQ", "MR", "MS", "MT", "MU", "MV", "MW", "MX", "MY", "MZ", "NA", "NC", "NE", "NF", "NG", "NI", "NL", "NO", "NP", "NR", "NU", "NZ", "OM", "PA", "PE", "PF", "PG", "PH", "PK", "PL", "PM", "PN", "PR", "PS", "PT", "PW", "PY", "QA", "RE", "RO", "RS", "RU", "RW", "SA", "SB", "SC", "SD", "SE", "SG", "SH", "SI", "SJ", "SK", "SL", "SM", "SN", "SO", "SR", "SS", "ST", "SV", "SX", "SY", "SZ", "TC", "TD", "TF", "TG", "TH", "TJ", "TK", "TL", "TM", "TN", "TO", "TR", "TT", "TV", "TW", "TZ", "UA", "UG", "UM", "US", "UY", "UZ", "VA", "VC", "VE", "VG", "VI", "VN", "VU", "WF", "WS", "YE", "YT", "ZA", "ZM", "ZW");
}

function nxGetCountryNames() {
    return array("AD" => "Andorra", "AE" => "United Arab Emirates", "AF" => "Afghanistan", "AG" => "Antigua & Barbuda", "AI" => "Anguilla", "AL" => "Albania", "AM" => "Armenia", "AO" => "Angola", "AQ" => "Antarctica", "AR" => "Argentina", "AS" => "American Samoa", "AT" => "Austria", "AU" => "Australia", "AW" => "Aruba", "AX" => "Åland Islands", "AZ" => "Azerbaijan", "BA" => "Bosnia & Herzegovina", "BB" => "Barbados", "BD" => "Bangladesh", "BE" => "Belgium", "BF" => "Burkina Faso", "BG" => "Bulgaria", "BH" => "Bahrain", "BI" => "Burundi", "BJ" => "Benin", "BL" => "St. Barthélemy", "BM" => "Bermuda", "BN" => "Brunei", "BO" => "Bolivia", "BQ" => "Caribbean Netherlands", "BR" => "Brazil", "BS" => "Bahamas", "BT" => "Bhutan", "BV" => "Bouvet Island", "BW" => "Botswana", "BY" => "Belarus", "BZ" => "Belize", "CA" => "Canada", "CC" => "Cocos (Keeling) Islands", "CD" => "Congo - Kinshasa", "CF" => "Central African Republic", "CG" => "Congo - Brazzaville", "CH" => "Switzerland", "CI" => "Côte d’Ivoire", "CK" => "Cook Islands", "CL" => "Chile", "CM" => "Cameroon", "CN" => "China", "CO" => "Colombia", "CR" => "Costa Rica", "CS" => "Czechoslovakia", "CU" => "Cuba", "CV" => "Cape Verde", "CW" => "Curaçao", "CX" => "Christmas Island", "CY" => "Cyprus", "CZ" => "Czechia", "DE" => "Germany", "DJ" => "Djibouti", "DK" => "Denmark", "DM" => "Dominica", "DO" => "Dominican Republic", "DZ" => "Algeria", "EC" => "Ecuador", "EE" => "Estonia", "EG" => "Egypt", "EH" => "Western Sahara", "ER" => "Eritrea", "ES" => "Spain", "ET" => "Ethiopia", "FI" => "Finland", "FJ" => "Fiji", "FK" => "Falkland Islands", "FM" => "Micronesia", "FO" => "Faroe Islands", "FR" => "France", "GA" => "Gabon", "GB" => "United Kingdom", "GD" => "Grenada", "GE" => "Georgia", "GF" => "French Guiana", "GG" => "Guernsey", "GH" => "Ghana", "GI" => "Gibraltar", "GL" => "Greenland", "GM" => "Gambia", "GN" => "Guinea", "GP" => "Guadeloupe", "GQ" => "Equatorial Guinea", "GR" => "Greece", "GS" => "South Georgia & South Sandwich Islands", "GT" => "Guatemala", "GU" => "Guam", "GW" => "Guinea-Bissau", "GY" => "Guyana", "HK" => "Hong Kong SAR China", "HM" => "Heard & McDonald Islands", "HN" => "Honduras", "HR" => "Croatia", "HT" => "Haiti", "HU" => "Hungary", "ID" => "Indonesia", "IE" => "Ireland", "IL" => "Israel", "IM" => "Isle of Man", "IN" => "India", "IO" => "British Indian Ocean Territory", "IQ" => "Iraq", "IR" => "Iran", "IS" => "Iceland", "IT" => "Italy", "JE" => "Jersey", "JM" => "Jamaica", "JO" => "Jordan", "JP" => "Japan", "KE" => "Kenya", "KG" => "Kyrgyzstan", "KH" => "Cambodia", "KI" => "Kiribati", "KM" => "Comoros", "KN" => "St. Kitts & Nevis", "KP" => "North Korea", "KR" => "South Korea", "KW" => "Kuwait", "KY" => "Cayman Islands", "KZ" => "Kazakhstan", "LA" => "Laos", "LB" => "Lebanon", "LC" => "St. Lucia", "LI" => "Liechtenstein", "LK" => "Sri Lanka", "LR" => "Liberia", "LS" => "Lesotho", "LT" => "Lithuania", "LU" => "Luxembourg", "LV" => "Latvia", "LY" => "Libya", "MA" => "Morocco", "MC" => "Monaco", "MD" => "Moldova", "ME" => "Montenegro", "MF" => "St. Martin", "MG" => "Madagascar", "MH" => "Marshall Islands", "MK" => "North Macedonia", "ML" => "Mali", "MM" => "Myanmar (Burma)", "MN" => "Mongolia", "MO" => "Macao SAR China", "MP" => "Northern Mariana Islands", "MQ" => "Martinique", "MR" => "Mauritania", "MS" => "Montserrat", "MT" => "Malta", "MU" => "Mauritius", "MV" => "Maldives", "MW" => "Malawi", "MX" => "Mexico", "MY" => "Malaysia", "MZ" => "Mozambique", "NA" => "Namibia", "NC" => "New Caledonia", "NE" => "Niger", "NF" => "Norfolk Island", "NG" => "Nigeria", "NI" => "Nicaragua", "NL" => "Netherlands", "NO" => "Norway", "NP" => "Nepal", "NR" => "Nauru", "NU" => "Niue", "NZ" => "New Zealand", "OM" => "Oman", "PA" => "Panama", "PE" => "Peru", "PF" => "French Polynesia", "PG" => "Papua New Guinea", "PH" => "Philippines", "PK" => "Pakistan", "PL" => "Poland", "PM" => "St. Pierre & Miquelon", "PN" => "Pitcairn Islands", "PR" => "Puerto Rico", "PS" => "Palestinian Territories", "PT" => "Portugal", "PW" => "Palau", "PY" => "Paraguay", "QA" => "Qatar", "RE" => "Réunion", "RO" => "Romania", "RS" => "Serbia", "RU" => "Russia", "RW" => "Rwanda", "SA" => "Saudi Arabia", "SB" => "Solomon Islands", "SC" => "Seychelles", "SD" => "Sudan", "SE" => "Sweden", "SG" => "Singapore", "SH" => "St. Helena", "SI" => "Slovenia", "SJ" => "Svalbard & Jan Mayen", "SK" => "Slovakia", "SL" => "Sierra Leone", "SM" => "San Marino", "SN" => "Senegal", "SO" => "Somalia", "SR" => "Suriname", "SS" => "South Sudan", "ST" => "São Tomé & Príncipe", "SV" => "El Salvador", "SX" => "Sint Maarten", "SY" => "Syria", "SZ" => "Eswatini", "TC" => "Turks & Caicos Islands", "TD" => "Chad", "TF" => "French Southern Territories", "TG" => "Togo", "TH" => "Thailand", "TJ" => "Tajikistan", "TK" => "Tokelau", "TL" => "Timor-Leste", "TM" => "Turkmenistan", "TN" => "Tunisia", "TO" => "Tonga", "TR" => "Türkiye", "TT" => "Trinidad & Tobago", "TV" => "Tuvalu", "TW" => "Taiwan", "TZ" => "Tanzania", "UA" => "Ukraine", "UG" => "Uganda", "UM" => "U.S. Outlying Islands", "US" => "United States", "UY" => "Uruguay", "UZ" => "Uzbekistan", "VA" => "Vatican City", "VC" => "St. Vincent & Grenadines", "VE" => "Venezuela", "VG" => "British Virgin Islands", "VI" => "U.S. Virgin Islands", "VN" => "Vietnam", "VU" => "Vanuatu", "WF" => "Wallis & Futuna", "WS" => "Samoa", "YE" => "Yemen", "YT" => "Mayotte", "ZA" => "South Africa", "ZM" => "Zambia", "ZW" => "Zimbabwe");
}

function nxCountryCodeToName($sCountry) {
    $sCountry = strtoupper(trim((string)$sCountry));
    $aCountryNames = nxGetCountryNames();
    return isset($aCountryNames[$sCountry]) ? $aCountryNames[$sCountry] : $sCountry;
}

function nxCountryNameToCode($sCountry) {
    $sCountry = trim((string)$sCountry);
    $sCountryUpper = strtoupper($sCountry);
    $sCountryLower = function_exists("mb_strtolower") ? mb_strtolower($sCountry, "UTF-8") : strtolower($sCountry);
    $aCountryCodes = nxGetCountryCodes();
    $aCountryNames = nxGetCountryNames();

    if ($sCountry == "") {
        return "";
    }
    if (preg_match("/^[A-Z]{2}$/", $sCountryUpper) && in_array($sCountryUpper, $aCountryCodes, true)) {
        return $sCountryUpper;
    }
    if ($sCountryLower == "czech republic") {
        return "CZ";
    }
    foreach ($aCountryNames as $sCode => $sName) {
        $sNameLower = function_exists("mb_strtolower") ? mb_strtolower((string)$sName, "UTF-8") : strtolower((string)$sName);
        if ($sCountryLower == $sNameLower) {
            return $sCode;
        }
    }
    return $sCountry;
}

function nxRenderCountryDatalist($sId = "nx-country-list") {
    $sHtml = "<datalist id=\"" . nxHtml($sId) . "\">\n";

    foreach (nxGetCountryNames() as $sCode => $sName) {
        $sHtml .= "    <option value=\"" . nxHtml($sName) . "\" label=\"" . nxHtml($sCode) . "\"></option>\n";
    }
    return $sHtml . "  </datalist>\n";
}

function nxCountryCodeToDisplayName($sCountry, $aSettings = null) {
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
    return nxCountryCodeToName($sCountry);
}

function nxDbValue($mValue) {
    $sValue = trim((string)$mValue);
    return $sValue != "" ? $sValue : null;
}

function nxPayloadValue($aPayload, $sName) {
    return isset($aPayload[$sName]) ? trim((string)$aPayload[$sName]) : "";
}

function nxPayloadFlag($aPayload, $sName) {
    return isset($aPayload[$sName]) && ((string)$aPayload[$sName] == "1" || $aPayload[$sName] === 1 || $aPayload[$sName] === true) ? 1 : 0;
}

function nxGetExCountrySettingsDefaults() {
    return array(
        "show_czechia_country" => 1,
        "show_czechia_country_in_czech" => 1,
        "show_czechia_country_as_czech_republic" => 1
    );
}

function nxApplyExCountrySettings($aSettings) {
    $aCountrySettingsDefaults = nxGetExCountrySettingsDefaults();
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

function nxSaveExCountrySettings($aSettings, $aPayload) {
    $aCountrySettingsDefaults = nxGetExCountrySettingsDefaults();
    $aPreviousCountrySettings = nxApplyExCountrySettings(array());
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

function nxRemoveExCountrySettings($aSettings) {
    foreach (nxGetExCountrySettingsDefaults() as $sCountrySettingName => $iCountrySettingDefault) {
        unset($aSettings[$sCountrySettingName]);
    }
    return $aSettings;
}

function nxRenderExSettingsScopeNote() {
    return "<p class=\"index-settings-note\">Options above the line apply only to this listing. Country options below the line are shared across the EX subproject.</p>";
}

function nxNormalizeBirthNumber($mValue) {
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

function nxBirthNumberModulo($sDigits, $iDivisor) {
    $iModulo = 0;
    for ($iI = 0; $iI < strlen($sDigits); $iI++) {
        $iModulo = ($iModulo * 10 + (int)$sDigits[$iI]) % $iDivisor;
    }
    return $iModulo;
}

function nxAnalyzeBirthNumber($mValue) {
    $sNormalized = nxNormalizeBirthNumber($mValue);
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
    if ($iLength === 10 && nxBirthNumberModulo($sDigits, 11) !== 0) {
        $blValid = false;
    }

    return array("normalized" => $sNormalized, "valid" => $blValid, "birth_date" => $sBirthDate);
}

function nxIsValidBirthNumber($mValue) {
    $aAnalysis = nxAnalyzeBirthNumber($mValue);
    return !empty($aAnalysis["valid"]);
}

function nxBirthNumberBirthDate($mValue) {
    $aAnalysis = nxAnalyzeBirthNumber($mValue);
    return isset($aAnalysis["birth_date"]) ? $aAnalysis["birth_date"] : "";
}

function nxIsInvalidBirthNumber($mValue) {
    $sValue = trim((string)$mValue);
    return $sValue != "" && !nxIsValidBirthNumber($sValue);
}

function nxBirthNumberClass($mValue, $sClass = "") {
    $sClass = trim((string)$sClass);
    if (nxIsInvalidBirthNumber($mValue)) {
        $sClass = trim($sClass . " nx-invalid-birth-number");
    }
    return $sClass;
}

function nxBirthDateClass($mBirthNumber, $mBirthDate, $sClass = "") {
    $sClass = trim((string)$sClass);
    $sBirthDate = trim((string)$mBirthDate);
    $sBirthNumberDate = nxBirthNumberBirthDate($mBirthNumber);
    if ($sBirthDate != "" && $sBirthNumberDate != "" && $sBirthDate != $sBirthNumberDate) {
        $sClass = trim($sClass . " nx-invalid-birth-number");
    }
    return $sClass;
}

function nxRenderBirthNumberValue($mValue) {
    $sValue = trim((string)$mValue);
    $sNormalized = nxNormalizeBirthNumber($sValue);
    if ($sNormalized !== false) {
        $sValue = $sNormalized;
    }
    return nxHtmlValue($sValue);
}

function nxFetchSubjectRows($oPdo, $iSubjectId = 0, $aFilterSql = null) {
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

function nxFetchSubjectContacts($oPdo, $iSubjectId = 0) {
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

function nxFetchSubjectNicknames($oPdo, $iSubjectId = 0) {
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

function nxFetchSubjectAddresses($oPdo, $iSubjectId = 0) {
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

function nxFetchSubjectGroups($oPdo, $iSubjectId = 0) {
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

function nxFetchGroupAjaxData($oPdo, $iGroupId, $sName = "") {
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
        "timestamp_tooltip" => nxTimestampTooltipText($aGroup)
    );
}

function nxFetchGroups($oPdo) {
    $oStatement = $oPdo->query("SELECT id, name, legacy_id, `order` FROM ex_groups ORDER BY `order` ASC, id ASC");
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function nxFetchGroupAdminRows($oPdo, $iGroupId = 0) {
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

function nxFetchPortalPermissions($oPdo) {
    $oStatement = $oPdo->query("SELECT permission_key, name, note FROM ex_permissions WHERE is_active = 1 ORDER BY permission_key ASC");
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function nxFetchSubjectPortalUser($oPdo, $iSubjectId) {
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
    $aPortalUser["timestamp_tooltip"] = nxTimestampTooltipText($aUser);
    $oStatement = $oPdo->prepare("SELECT p.permission_key FROM ex_user_permissions AS up INNER JOIN ex_permissions AS p ON p.id = up.permission_id WHERE up.user_id = :user_id AND up.is_allowed = 1 AND p.is_active = 1 ORDER BY p.permission_key ASC");
    $oStatement->execute(array("user_id" => (int)$aUser["id"]));
    while ($sPermissionKey = $oStatement->fetchColumn()) {
        $aPortalUser["direct_permission_keys"][] = (string)$sPermissionKey;
    }
    $aEffectivePermissions = exFetchUserEffectivePermissions($oPdo, (int)$aUser["id"], $iSubjectId);
    foreach ($aEffectivePermissions as $sPermissionKey => $blAllowed) {
        if ($blAllowed) {
            $aPortalUser["effective_permission_keys"][] = (string)$sPermissionKey;
        }
    }
    sort($aPortalUser["effective_permission_keys"]);

    return $aPortalUser;
}

function nxNormalizePortalPermissionKeys($oPdo, $aPermissionKeys) {
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

function nxSavePortalUserPermissions($oPdo, $iUserId, $aPermissionKeys) {
    $aPermissions = nxNormalizePortalPermissionKeys($oPdo, $aPermissionKeys);
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

function nxSaveGroupPortalPermissions($oPdo, $iGroupId, $aPermissionKeys) {
    $aPermissions = nxNormalizePortalPermissionKeys($oPdo, $aPermissionKeys);
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

function nxNormalizeGroupOrder($oPdo) {
    $oStatement = $oPdo->query("SELECT id FROM ex_groups ORDER BY `order` ASC, id ASC FOR UPDATE");
    $aIds = $oStatement->fetchAll(PDO::FETCH_COLUMN, 0);
    $iOrder = 10;
    $oUpdateStatement = $oPdo->prepare("UPDATE ex_groups SET `order` = :order WHERE id = :id");
    foreach ($aIds as $iGroupId) {
        $oUpdateStatement->execute(array("order" => $iOrder, "id" => (int)$iGroupId));
        $iOrder += 10;
    }
}

function nxMoveGroupOrder($oPdo, $iGroupId, $sDirection) {
    nxNormalizeGroupOrder($oPdo);
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

function nxSaveSubjectPortalAccess($oPdo, $iSubjectId, $sSubjectType, $aPayload) {
    if (!isset($aPayload["portal_user_enabled"])
        && !isset($aPayload["portal_user_name"])
        && !isset($aPayload["portal_password"])
        && !isset($aPayload["portal_permission_keys"])) {
        return;
    }
    $iEnabled = nxPayloadFlag($aPayload, "portal_user_enabled");
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
    $sUserName = nxPayloadValue($aPayload, "portal_user_name");
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
                "is_active" => nxPayloadFlag($aPayload, "portal_user_active"),
                "id" => (int)$aUser["id"]
            ));
        } else {
            $oStatement = $oPdo->prepare("UPDATE ex_users SET user_name = :user_name, is_active = :is_active WHERE id = :id");
            $oStatement->execute(array(
                "user_name" => $sUserName,
                "is_active" => nxPayloadFlag($aPayload, "portal_user_active"),
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
            "is_active" => nxPayloadFlag($aPayload, "portal_user_active")
        ));
        $iUserId = (int)$oPdo->lastInsertId();
    }

    $aPermissionKeys = isset($aPayload["portal_permission_keys"]) && is_array($aPayload["portal_permission_keys"]) ? $aPayload["portal_permission_keys"] : array();
    nxSavePortalUserPermissions($oPdo, $iUserId, $aPermissionKeys);
}

function nxRenderGroupAdminRow($aGroup, $blShowActions = true) {
    global $sDeleteEmoji, $sEditEmoji, $sEmptyValueEmoji, $sMergeEmoji, $sMoveUpEmoji, $sMoveDownEmoji;

    $sPermissionKeys = isset($aGroup["permission_keys"]) ? (string)$aGroup["permission_keys"] : "";
    $sPermissionNames = isset($aGroup["permission_names"]) ? (string)$aGroup["permission_names"] : "";

    return "      <tr data-group-id=\"" . nxHtml($aGroup["id"]) . "\" data-group-name=\"" . nxHtml($aGroup["name"]) . "\" data-group-order=\"" . nxHtml($aGroup["order"]) . "\" data-permission-keys=\"" . nxHtml($sPermissionKeys) . "\">\n"
        . "        <td><span" . nxRenderTimestampTooltipAttribute($aGroup) . ">" . nxHtml($aGroup["name"]) . "</span></td>\n"
        . "        <td>" . nxHtml($aGroup["subject_count"]) . "</td>\n"
        . "        <td>" . ($sPermissionNames != "" ? nl2br(nxHtml(str_replace(",", "\n", $sPermissionNames)), false) : $sEmptyValueEmoji) . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-move-group-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"nx-item-action js-move-group-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-merge-group\" title=\"Merge into this group\" aria-label=\"Merge into this group\">" . $sMergeEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-edit-group\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"nx-item-action js-delete-group\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "") . "</td>\n"
        . "      </tr>\n";
}

function nxFetchSubjectNotes($oPdo, $iSubjectId = 0) {
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

function nxAddContactTimestampTooltip($oPdo, $aContact) {
    if (!is_array($aContact) || empty($aContact["contact_id"])) {
        return $aContact;
    }
    $oStatement = $oPdo->prepare("SELECT created_at, updated_at FROM ex_contacts WHERE id = :id");
    $oStatement->execute(array("id" => (int)$aContact["contact_id"]));
    $aContactRow = $oStatement->fetch(PDO::FETCH_ASSOC);
    if ($aContactRow) {
        $aContact["timestamp_tooltip"] = nxTimestampTooltipText($aContactRow);
    }
    return $aContact;
}

function nxCollectHiddenInactiveSubjectItems(&$aHiddenInactive, $aItems) {
    foreach ($aItems as $iSubjectId => $aSubjectItems) {
        foreach ($aSubjectItems as $aItem) {
            if (isset($aItem["is_active"]) && (int)$aItem["is_active"] != 1) {
                $aHiddenInactive[(int)$iSubjectId] = true;
                break;
            }
        }
    }
}

function nxGetHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aSettings) {
    $aHiddenInactive = array(
        "contacts" => array(),
        "nicknames" => array(),
        "addresses" => array(),
        "notes" => array()
    );

    if (empty($aSettings["show_inactive_contacts"])) {
        nxCollectHiddenInactiveSubjectItems($aHiddenInactive["contacts"], $aContacts);
    }
    if (empty($aSettings["show_inactive_nicknames"])) {
        nxCollectHiddenInactiveSubjectItems($aHiddenInactive["nicknames"], $aNicknames);
    }
    if (empty($aSettings["show_inactive_addresses"])) {
        nxCollectHiddenInactiveSubjectItems($aHiddenInactive["addresses"], $aAddresses);
    }
    if (empty($aSettings["show_inactive_notes"])) {
        nxCollectHiddenInactiveSubjectItems($aHiddenInactive["notes"], $aNotes);
    }
    return $aHiddenInactive;
}

function nxApplySubjectVisibilitySettings(&$aRows, &$aContacts, &$aNicknames, &$aAddresses, &$aNotes, $aSettings) {
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

function nxRenderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions = true, $aHiddenInactive = array(), $aDisplaySettings = null) {
    global $sEditEmoji, $sDeleteEmoji, $sPortalEmoji;

    $iSubjectId = (int)$aRow["subject_id"];
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aRow["subject_type"]));
    $blIsActive = (int)$aRow["is_active"] == 1;
    $blShowBirthNumber = !is_array($aDisplaySettings) || empty($aDisplaySettings["hide_personal_number"]);
    $sBirthNumberClass = nxBirthNumberClass($aRow["birth_number"]);
    $sBirthNumberClassAttribute = $sBirthNumberClass != "" ? " class=\"" . nxHtml($sBirthNumberClass) . "\"" : "";
    $sBirthDateClass = nxBirthDateClass($aRow["birth_number"], $aRow["birth_date"]);
    $sBirthDateClassAttribute = $sBirthDateClass != "" ? " class=\"" . nxHtml($sBirthDateClass) . "\"" : "";
    $sActions = "";
    if ($blShowActions) {
        $sActions = "<span class=\"nx-list-item-actions\">"
            . "<a href=\"#\" class=\"nx-item-action js-edit-subject\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
            . "<a href=\"#\" class=\"nx-item-action js-edit-subject-portal\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" title=\"Portal account\" aria-label=\"Portal account\">" . $sPortalEmoji . "</a>"
            . "<a href=\"#\" class=\"nx-item-action js-delete-subject\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" data-subject-name=\"" . nxHtml($aRow["subject_name"]) . "\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
            . "</span>";
    }
    return "      <tr class=\"nx-subject-row nx-subject-row-type-" . nxHtml($sSubjectType) . ($blIsActive ? " nx-subject-row-active" : " nx-subject-row-inactive") . "\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" data-subject-type=\"" . nxHtml($aRow["subject_type"]) . "\" data-subject-active=\"" . ($blIsActive ? "1" : "0") . "\">\n"
        . "        <td class=\"nx-subject-type-column\" style=\"vertical-align: top;\">" . nxHtml($aRow["subject_type"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\"><span class=\"nx-subject-item-value\"" . nxRenderTimestampTooltipAttribute($aRow) . ">" . nxHtmlValue($aRow["subject_name"]) . "</span>"
        . nxRenderCopyAction($aRow["subject_name"])
        . $sActions . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["first_name"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["last_name"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["birth_name"]) . "</td>\n"
        . ($blShowBirthNumber ? "        <td" . $sBirthNumberClassAttribute . " style=\"vertical-align: top;\">" . nxRenderBirthNumberValue($aRow["birth_number"]) . "</td>\n" : "")
        . "        <td" . $sBirthDateClassAttribute . " style=\"vertical-align: top;\">" . nxHtmlValue($aRow["birth_date"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["death_date"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderNicknameList(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(), $blShowActions, $iSubjectId, !empty($aHiddenInactive["nicknames"][$iSubjectId]), true, true, true) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderAddressList(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $blShowActions, $iSubjectId, $aRow["subject_name"], !empty($aHiddenInactive["addresses"][$iSubjectId]), $aDisplaySettings, true, true, true) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderContactList(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(), $blShowActions, $iSubjectId, true, true, !empty($aHiddenInactive["contacts"][$iSubjectId]), true, true, true) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderGroupList(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(), $blShowActions, $iSubjectId, true, true, true) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderNoteList(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array(), $blShowActions, $iSubjectId, !empty($aHiddenInactive["notes"][$iSubjectId]), true, true, true) . "</td>\n"
        . "      </tr>\n";
}

function nxSubjectRowOption($aOptions, $sName, $mDefault) {
    return is_array($aOptions) && array_key_exists($sName, $aOptions) ? $aOptions[$sName] : $mDefault;
}

function nxRenderSubjectTableCell($sHtml, $sClass = "", $sStyle = "") {
    $sAttributes = "";
    if ($sClass != "") {
        $sAttributes .= " class=\"" . nxHtml($sClass) . "\"";
    }
    if ($sStyle != "") {
        $sAttributes .= " style=\"" . nxHtml($sStyle) . "\"";
    }
    return "        <td" . $sAttributes . ">" . $sHtml . "</td>\n";
}

function nxRenderResponsiveSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aHiddenInactive = array(), $aDisplaySettings = null, $aOptions = array()) {
    $iSubjectId = (int)$aRow["subject_id"];
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aRow["subject_type"]));
    $blIsActive = (int)$aRow["is_active"] == 1;
    $blShowActions = nxSubjectRowOption($aOptions, "show_actions", false);
    $iItemSubjectId = (int)nxSubjectRowOption($aOptions, "item_subject_id", 0);
    $sNoWrapStyle = "overflow-wrap: normal; white-space: nowrap; word-break: normal;";
    $sBirthNumberClass = nxBirthNumberClass($aRow["birth_number"], nxSubjectRowOption($aOptions, "birth_number_class", "nx-column-hidden"));
    $sBirthDateClass = nxBirthDateClass($aRow["birth_number"], $aRow["birth_date"], nxSubjectRowOption($aOptions, "birth_date_class", "nx-column-step-two"));
    $aBeforeNameCells = nxSubjectRowOption($aOptions, "before_name_cells", array());
    $sHtml = "      <tr class=\"nx-subject-row nx-subject-row-type-" . nxHtml($sSubjectType) . ($blIsActive ? " nx-subject-row-active" : " nx-subject-row-inactive") . "\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" data-subject-type=\"" . nxHtml($aRow["subject_type"]) . "\" data-subject-active=\"" . ($blIsActive ? "1" : "0") . "\">\n"
        . nxRenderSubjectTableCell(nxHtml($aRow["subject_type"]), nxSubjectRowOption($aOptions, "type_class", "nx-column-hidden"), nxSubjectRowOption($aOptions, "type_style", ""));
    if (is_array($aBeforeNameCells)) {
        foreach ($aBeforeNameCells as $sCellHtml) {
            $sHtml .= $sCellHtml;
        }
    }
    $sHtml .= nxRenderSubjectTableCell(
            "<span class=\"nx-subject-item-value\"" . nxRenderTimestampTooltipAttribute($aRow) . ">" . nxHtmlValue($aRow["subject_name"]) . "</span>"
            . nxRenderCopyAction($aRow["subject_name"])
            . nxSubjectRowOption($aOptions, "name_actions", ""),
            nxSubjectRowOption($aOptions, "name_class", ""),
            nxSubjectRowOption($aOptions, "name_style", "")
        )
        . nxRenderSubjectTableCell(nxHtmlValue($aRow["first_name"]), nxSubjectRowOption($aOptions, "first_name_class", "nx-column-hidden"), nxSubjectRowOption($aOptions, "first_name_style", ""))
        . nxRenderSubjectTableCell(nxHtmlValue($aRow["last_name"]), nxSubjectRowOption($aOptions, "last_name_class", "nx-column-hidden"), nxSubjectRowOption($aOptions, "last_name_style", ""))
        . nxRenderSubjectTableCell(nxHtmlValue($aRow["birth_name"]), nxSubjectRowOption($aOptions, "birth_name_class", "nx-column-step-one"), nxSubjectRowOption($aOptions, "birth_name_style", ""))
        . nxRenderSubjectTableCell(nxRenderBirthNumberValue($aRow["birth_number"]), $sBirthNumberClass, nxSubjectRowOption($aOptions, "birth_number_style", ""))
        . nxRenderSubjectTableCell(nxHtmlValue($aRow["birth_date"]), $sBirthDateClass, nxSubjectRowOption($aOptions, "birth_date_style", $sNoWrapStyle))
        . nxRenderSubjectTableCell(nxHtmlValue($aRow["death_date"]), nxSubjectRowOption($aOptions, "death_date_class", "nx-column-hidden"), nxSubjectRowOption($aOptions, "death_date_style", ""))
        . nxRenderSubjectTableCell(nxRenderNicknameList(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, !empty($aHiddenInactive["nicknames"][$iSubjectId]), nxSubjectRowOption($aOptions, "nickname_show_add_action", false), nxSubjectRowOption($aOptions, "nickname_show_cell_copy_action", true), nxSubjectRowOption($aOptions, "nickname_cell_copy_before_add_action", true)), nxSubjectRowOption($aOptions, "nickname_class", "nx-column-step-one"), nxSubjectRowOption($aOptions, "nickname_style", ""))
        . nxRenderSubjectTableCell(nxRenderAddressList(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, $aRow["subject_name"], !empty($aHiddenInactive["addresses"][$iSubjectId]), $aDisplaySettings, nxSubjectRowOption($aOptions, "address_show_add_action", false), nxSubjectRowOption($aOptions, "address_show_cell_copy_action", true), nxSubjectRowOption($aOptions, "address_cell_copy_before_add_action", true)), nxSubjectRowOption($aOptions, "address_class", ""), nxSubjectRowOption($aOptions, "address_style", ""))
        . nxRenderSubjectTableCell(nxRenderContactList(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, true, true, !empty($aHiddenInactive["contacts"][$iSubjectId]), nxSubjectRowOption($aOptions, "contact_show_add_action", false), nxSubjectRowOption($aOptions, "contact_show_cell_copy_action", true), nxSubjectRowOption($aOptions, "contact_cell_copy_before_add_action", true)), nxSubjectRowOption($aOptions, "contact_class", ""), nxSubjectRowOption($aOptions, "contact_style", ""))
        . nxRenderSubjectTableCell(nxRenderGroupList(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, nxSubjectRowOption($aOptions, "group_show_add_action", false), nxSubjectRowOption($aOptions, "group_show_cell_copy_action", true), nxSubjectRowOption($aOptions, "group_cell_copy_before_add_action", true)), nxSubjectRowOption($aOptions, "group_class", "nx-column-step-three"), nxSubjectRowOption($aOptions, "group_style", ""))
        . nxRenderSubjectTableCell(nxRenderNoteList(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array(), $blShowActions, $iItemSubjectId, !empty($aHiddenInactive["notes"][$iSubjectId]), nxSubjectRowOption($aOptions, "note_show_add_action", false), nxSubjectRowOption($aOptions, "note_show_cell_copy_action", true), nxSubjectRowOption($aOptions, "note_cell_copy_before_add_action", true)), nxSubjectRowOption($aOptions, "note_class", "nx-column-step-three"), nxSubjectRowOption($aOptions, "note_style", ""))
        . "      </tr>\n";
    return $sHtml;
}

function nxRenderUpdatedSubjectRow($oPdo, $iSubjectId, $aVisibilitySettings = null) {
    $aRows = nxFetchSubjectRows($oPdo, $iSubjectId);
    if (!$aRows) {
        return "";
    }
    $aContacts = nxFetchSubjectContacts($oPdo, $iSubjectId);
    $aNicknames = nxFetchSubjectNicknames($oPdo, $iSubjectId);
    $aAddresses = nxFetchSubjectAddresses($oPdo, $iSubjectId);
    $aGroups = nxFetchSubjectGroups($oPdo, $iSubjectId);
    $aNotes = nxFetchSubjectNotes($oPdo, $iSubjectId);
    $aHiddenInactive = array();
    if (is_array($aVisibilitySettings)) {
        $aHiddenInactive = nxGetHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aVisibilitySettings);
        nxApplySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aVisibilitySettings);
        if (!$aRows) {
            return "";
        }
    }
    return nxRenderSubjectRow(
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

function nxGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aVisibilitySettings = null) {
    $sRowHtml = nxRenderUpdatedSubjectRow($oPdo, $iSubjectId, $aVisibilitySettings);
    if ($sRowHtml == "") {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    return array("success" => true, "subject_id" => $iSubjectId, "row_html" => $sRowHtml);
}

function nxFetchSubjectEditorData($oPdo, $iSubjectId) {
    $oStatement = $oPdo->prepare("SELECT s.id AS subject_id, s.subject_type, s.is_active, subn.name AS subject_name_value, p.title_before, p.first_name, p.middle_name, p.last_name, p.title_after, p.birth_name, p.birth_number, p.birth_date, p.death_date FROM ex_subjects AS s LEFT JOIN ex_persons AS p ON p.subject_id = s.id LEFT JOIN ex_subject_names AS subn ON subn.subject_id = s.id WHERE s.id = :subject_id");
    $oStatement->execute(array("subject_id" => $iSubjectId));
    $aSubject = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aSubject) {
        return null;
    }

    return $aSubject;
}

function nxFetchSubjectPortalEditorData($oPdo, $iSubjectId) {
    $aRows = nxFetchSubjectRows($oPdo, $iSubjectId);
    if (!$aRows) {
        return null;
    }

    return array(
        "subject_id" => (int)$aRows[0]["subject_id"],
        "subject_name" => (string)$aRows[0]["subject_name"],
        "subject_type" => (string)$aRows[0]["subject_type"],
        "portal_user" => nxFetchSubjectPortalUser($oPdo, $iSubjectId),
        "portal_permissions" => nxFetchPortalPermissions($oPdo)
    );
}

function getPhpGeneratedStyleTag($sStyleNonce) {
    $sBaseUrl = isset($GLOBALS["sBaseUrl"]) ? $GLOBALS["sBaseUrl"] : "";
    return "  <link href=\"" . htmlspecialchars($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css")), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\" rel=\"stylesheet\" type=\"text/css\">\n";
}

function addPhpGeneratedStyleAttributes($sHtml, $sStyleNonce) {
    if ($sStyleNonce == "") {
        return $sHtml;
    }
    return preg_replace_callback("#<style\\b([^>]*)>#i", function ($aMatches) use ($sStyleNonce) {
        if (stripos($aMatches[1], "nonce=") !== false) {
            return $aMatches[0];
        }
        return "<style" . $aMatches[1] . " nonce=\"" . htmlspecialchars($sStyleNonce, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "\">";
    }, $sHtml);
}

function addPhpGeneratedViewportMeta($sHtml) {
    if (preg_match("#<meta\\b[^>]*\\bname\\s*=\\s*([\"'])viewport\\1#i", $sHtml) || stripos($sHtml, "</head>") === false) {
        return $sHtml;
    }
    return preg_replace("#</head>#i", "  <meta name=\"viewport\" content=\"" . nxHtml(nxGetLockedViewportContent()) . "\">\n</head>", $sHtml, 1);
}

function formatPhpGeneratedOutput($sHtml, $sStyleNonce, $sTitle) {
    if (stripos($sHtml, "<html") !== false) {
        return addPhpGeneratedViewportMeta($sHtml);
    }

    $sHtml = addPhpGeneratedStyleAttributes($sHtml, $sStyleNonce);
    return "<!DOCTYPE html>\n"
        . "<html lang=\"en-US\" dir=\"ltr\">\n"
        . "<head>\n"
        . "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n"
        . "  <meta name=\"viewport\" content=\"" . nxHtml(nxGetLockedViewportContent()) . "\">\n"
        . "  <title>" . htmlspecialchars($sTitle, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</title>\n"
        . "</head>\n"
        . "<body><div class=\"center\">\n"
        . $sHtml
        . "\n</div></body>\n"
        . "</html>\n";
}

function sendPhpGeneratedHeaders($sStyleNonce) {
    $iTime = time();
    $sDate = gmdate("D, d M Y H:i:s", $iTime);
    header("Content-Type: text/html; charset=utf-8", true);
    header("Content-Language: en-US", true);
    header("Last-Modified: " . $sDate . " GMT", true);
    header("Expires: " . $sDate . " GMT", true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders($sStyleNonce);
}

function getPhpGeneratedSelectedFlags($sName, $aTypes, $iDefaultValue) {
    $iSelected = 0;
    $aValues = array();
    if (isset($_GET[$sName])) {
        $aValues = is_array($_GET[$sName]) ? $_GET[$sName] : array($_GET[$sName]);
    }
    foreach ($aValues as $sValue) {
        if (ctype_digit((string)$sValue)) {
            $iValue = (int)$sValue;
            if (in_array($iValue, $aTypes, true)) {
                $iSelected |= $iValue;
            }
        }
    }
    if ($iSelected == 0) {
        $iSelected = $iDefaultValue;
    }
    return $iSelected;
}

function sendPhpGeneratedOutputAndExit($sType, $iSelect) {
    ob_start();
    if ($sType == "credits") {
        phpcredits($iSelect | CREDITS_FULLPAGE);
    } else {
        phpinfo($iSelect);
    }
    $sTitle = $sType == "credits" ? "PHP Credits" : "PHP Info";
    if (isset($GLOBALS["aAllowedIps"]) && is_array($GLOBALS["aAllowedIps"])) {
        $sTitle = getExPageTitleText($sTitle, $GLOBALS["aAllowedIps"]);
    }
    $sHtml = ob_get_clean();
    $sStyleNonce = stripos($sHtml, "<html") !== false ? "" : base64_encode(random_bytes(16));
    $sHtml = formatPhpGeneratedOutput($sHtml, $sStyleNonce, $sTitle);
    sendPhpGeneratedHeaders($sStyleNonce);
    echo $sHtml;
    exit;
}

function nxAddressesNormalizeKey($sValue) {
    $sValue = str_replace("\r\n", "\n", (string)$sValue);
    $sValue = str_replace("\r", "\n", $sValue);
    if (function_exists("mb_strtolower")) {
        return mb_strtolower($sValue, "UTF-8");
    }
    return strtolower($sValue);
}

function nxAddressesCompareRows($aFirst, $aSecond) {
    $iResult = strcmp((string)$aFirst["address_sort"], (string)$aSecond["address_sort"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return strcmp((string)$aFirst["address_text"], (string)$aSecond["address_text"]);
}

function nxAddressesCompareSubjectNames($sFirst, $sSecond) {
    return strcmp((string)$sFirst, (string)$sSecond);
}

function nxAddressesCompareSubjects($aFirst, $aSecond) {
    $iResult = nxAddressesCompareSubjectNames($aFirst["subject_name"], $aSecond["subject_name"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["address_id"] - (int)$aSecond["address_id"];
}

function nxAddressesAddressFields() {
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

function nxAddressesRequiredAddressFields() {
    return array("country");
}

function nxAddressesSubjectAddressFields() {
    return array_merge(array("address_type"), nxAddressesAddressFields(), array("note"));
}

function nxAddressesBuildMatch($aAddress) {
    $aMatch = array();
    foreach (nxAddressesAddressFields() as $sField) {
        $aMatch[$sField] = array_key_exists($sField, $aAddress) && $aAddress[$sField] !== null ? (string)$aAddress[$sField] : null;
    }
    return $aMatch;
}

function nxAddressesEncodeMatch($aMatch) {
    return base64_encode(json_encode($aMatch));
}

function nxAddressesDecodeMatch($sMatch) {
    $sJson = base64_decode((string)$sMatch, true);
    $aMatch = $sJson !== false ? json_decode($sJson, true) : null;
    $aFields = nxAddressesAddressFields();

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

function nxAddressesNullValue($sField, $sValue) {
    return in_array($sField, nxAddressesRequiredAddressFields(), true) || $sValue != "" ? (string)$sValue : null;
}

function nxAddressesMatchSql($sPrefix) {
    $aSql = array();
    foreach (nxAddressesAddressFields() as $sField) {
        $aSql[] = "`" . $sField . "` <=> :" . $sPrefix . $sField;
    }
    return implode(" AND ", $aSql);
}

function nxAddressesMatchParams($aMatch, $sPrefix) {
    $aParams = array();
    foreach (nxAddressesAddressFields() as $sField) {
        $aParams[$sPrefix . $sField] = array_key_exists($sField, $aMatch) ? $aMatch[$sField] : null;
    }
    return $aParams;
}

function nxAddressesPostedAddressValues() {
    $sOrganizationName = nxGetPostedTrimmedValue("organization_name");
    $sDepartmentName = nxGetPostedTrimmedValue("department_name");
    $sCareOf = nxGetPostedTrimmedValue("care_of");
    $sStreetName = nxGetPostedTrimmedValue("street_name");
    $sHouseNumber = nxGetPostedTrimmedValue("house_number");
    $sEvidenceNumber = nxGetPostedTrimmedValue("evidence_number");
    $sOrientationNumber = nxGetPostedTrimmedValue("orientation_number");
    $sOrientationSuffix = nxGetPostedTrimmedValue("orientation_suffix");
    $sAddressLine2 = nxGetPostedTrimmedValue("address_line2");
    $sCity = nxGetPostedTrimmedValue("city");
    $sCityPart = nxGetPostedTrimmedValue("city_part");
    $sPostalCode = nxGetPostedTrimmedValue("postal_code");
    $sRegion = nxGetPostedTrimmedValue("region");
    $sCountry = nxCountryNameToCode(nxGetPostedTrimmedValue("country"));

    if ($sCountry != "") {
        $sCountry = strtoupper($sCountry);
    }
    if ($sCountry == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Country is required."), 400);
    }
    if ($sCountry != "" && !in_array($sCountry, nxGetCountryCodes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid country."), 400);
    }
    $sPostalCode = nxNormalizePostalCode($sCountry, $sPostalCode);
    if ($sPostalCode === false) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid postal code."), 400);
    }
    if ($sOrganizationName == "" && $sDepartmentName == "" && $sCareOf == "" && $sStreetName == "" && $sHouseNumber == "" && $sEvidenceNumber == "" && $sOrientationNumber == "" && $sOrientationSuffix == "" && $sAddressLine2 == "" && $sCity == "" && $sCityPart == "" && $sPostalCode == "" && $sRegion == "" && $sCountry == "") {
        nxSendJsonAndExit(array("success" => false, "message" => "Address is required."), 400);
    }
    return array(
        "organization_name" => nxAddressesNullValue("organization_name", $sOrganizationName),
        "department_name" => nxAddressesNullValue("department_name", $sDepartmentName),
        "care_of" => nxAddressesNullValue("care_of", $sCareOf),
        "street_name" => nxAddressesNullValue("street_name", $sStreetName),
        "house_number" => nxAddressesNullValue("house_number", $sHouseNumber),
        "evidence_number" => nxAddressesNullValue("evidence_number", $sEvidenceNumber),
        "orientation_number" => nxAddressesNullValue("orientation_number", $sOrientationNumber),
        "orientation_suffix" => nxAddressesNullValue("orientation_suffix", $sOrientationSuffix),
        "address_line2" => nxAddressesNullValue("address_line2", $sAddressLine2),
        "city" => nxAddressesNullValue("city", $sCity),
        "city_part" => nxAddressesNullValue("city_part", $sCityPart),
        "postal_code" => nxAddressesNullValue("postal_code", $sPostalCode),
        "region" => nxAddressesNullValue("region", $sRegion),
        "country" => $sCountry
    );
}

function nxAddressesPostedSubjectAddressValues() {
    $sAddressType = nxGetPostedTrimmedValue("address_type");
    $sNote = nxGetPostedTrimmedValue("note");
    $aAddress = nxAddressesPostedAddressValues();

    if ($sAddressType == "") {
        $sAddressType = "main";
    }
    if (!in_array($sAddressType, nxGetAddressTypes(), true)) {
        nxSendJsonAndExit(array("success" => false, "message" => "Invalid address type."), 400);
    }
    $aAddress["address_type"] = $sAddressType;
    $aAddress["note"] = nxAddressesNullValue("note", $sNote);
    return $aAddress;
}

function nxAddressesRenderDataAttributes($aAddressRow) {
    $sHtml = " data-address-match=\"" . nxHtml($aAddressRow["address_match"]) . "\""
        . nxRenderTimestampTooltipDataAttribute($aAddressRow);
    foreach (nxAddressesAddressFields() as $sField) {
        $sAttribute = str_replace("_", "-", $sField);
        $sValue = isset($aAddressRow["address_values"][$sField]) && $aAddressRow["address_values"][$sField] !== null ? (string)$aAddressRow["address_values"][$sField] : "";
        if ($sField == "postal_code") {
            $sValue = nxPostalCodeDisplayValue($aAddressRow["address_values"]["country"], $sValue);
        } elseif ($sField == "country") {
            $sHtml .= " data-country-name=\"" . nxHtml(nxCountryCodeToName($sValue)) . "\"";
        }
        $sHtml .= " data-" . $sAttribute . "=\"" . nxHtml($sValue) . "\"";
    }
    return $sHtml;
}

function nxAddressesRenderSubjectDataAttributes($aSubject) {
    $sHtml = " data-address-id=\"" . nxHtml($aSubject["address_id"]) . "\"";
    foreach (nxAddressesSubjectAddressFields() as $sField) {
        $sAttribute = str_replace("_", "-", $sField);
        $sValue = isset($aSubject["address_values"][$sField]) && $aSubject["address_values"][$sField] !== null ? (string)$aSubject["address_values"][$sField] : "";
        if ($sField == "postal_code") {
            $sValue = nxPostalCodeDisplayValue($aSubject["address_values"]["country"], $sValue);
        } elseif ($sField == "country") {
            $sHtml .= " data-country-name=\"" . nxHtml(nxCountryCodeToName($sValue)) . "\"";
        }
        $sHtml .= " data-" . $sAttribute . "=\"" . nxHtml($sValue) . "\"";
    }
    $sHtml .= " data-primary=\"" . ((int)$aSubject["is_primary"] == 1 ? "1" : "0") . "\"";
    $sHtml .= " data-active=\"" . ((int)$aSubject["address_is_active"] == 1 ? "1" : "0") . "\"";
    $sHtml .= " data-subject-active=\"" . (!empty($aSubject["is_active"]) ? "1" : "0") . "\"";
    return $sHtml;
}

function nxAddressesSubjectCellClass($aSubject) {
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aSubject["subject_type"]));
    return "nx-address-subject-cell nx-address-subject-type-" . $sSubjectType . (!empty($aSubject["is_active"]) && (int)$aSubject["address_is_active"] == 1 ? " nx-address-subject-active" : " nx-address-subject-inactive");
}

function nxAddressesTypeLabel($sAddressType) {
    return ucwords(str_replace("_", " ", (string)$sAddressType));
}

function nxAddressesFetchRows($oPdo, $aAddressSettings) {
    $aRows = array();
    $aSubjectNames = array();
    $aSubjectRows = nxFetchSubjectRows($oPdo);
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
    $aSubjectAddresses = nxFetchSubjectAddresses($oPdo);
    foreach ($aSubjectAddresses as $iSubjectId => $aAddresses) {
        $iSubjectId = (int)$iSubjectId;
        if (!isset($aSubjectNames[$iSubjectId])) {
            continue;
        }
        foreach ($aAddresses as $aAddress) {
            if (empty($aAddressSettings["show_inactive_addresses"]) && (int)$aAddress["is_active"] != 1) {
                continue;
            }
            $aAddressMatch = nxAddressesBuildMatch($aAddress);
            $sAddressKey = json_encode($aAddressMatch);
            $aCopyAddress = $aAddress;
            $aCopyAddress["note"] = "";
            $sAddressCopyText = nxRenderAddressCopyText($aCopyAddress, "", $aAddressSettings);
            $sAddressText = nxRenderAddressText($aAddress, $aAddressSettings);
            if (trim($sAddressText) == "") {
                continue;
            }
            if (!isset($aRows[$sAddressKey])) {
                $aRows[$sAddressKey] = array(
                    "address_text" => $sAddressText,
                    "address_copy_text" => $sAddressCopyText,
                    "address_sort" => nxAddressesNormalizeKey($sAddressText),
                    "address_match" => nxAddressesEncodeMatch($aAddressMatch),
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
        usort($aRows[$sKey]["subjects"], "nxAddressesCompareSubjects");
    }
    uasort($aRows, "nxAddressesCompareRows");
    return $aRows;
}


function nxBdGetBirthdayInfo($sBirthDate) {
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
        if ($iDaysToBirthday < -2 || $iDaysToBirthday > 17) {
            continue;
        }
        return array(
            "days_to_birthday" => $iDaysToBirthday,
            "birthday_date" => $oBirthday->format("Y-m-d")
        );
    }
    return null;
}

function nxBdFetchBirthdayServedRows($oPdo) {
    $aServedRows = array();
    $oStatement = $oPdo->query("SELECT subject_id, birthday_served_at FROM ex_persons");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $aServedRows[(int)$aRow["subject_id"]] = $aRow;
    }
    return $aServedRows;
}

function nxBdIsBirthdayServed($aServedRows, $iSubjectId, $sBirthdayDate) {
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
        return false;
    }
    return $oServedAt >= $oBirthday->modify("-17 days") && $oServedAt < $oBirthday->modify("+3 days");
}

function nxBdCompareRows($aFirst, $aSecond) {
    $iFirstCountdown = isset($aFirst["days_to_birthday"]) ? (int)$aFirst["days_to_birthday"] : 0;
    $iSecondCountdown = isset($aSecond["days_to_birthday"]) ? (int)$aSecond["days_to_birthday"] : 0;

    if ($iFirstCountdown === $iSecondCountdown) {
        return strcmp((string)$aFirst["subject_name"], (string)$aSecond["subject_name"]);
    }
    return $iFirstCountdown < $iSecondCountdown ? -1 : 1;
}

function nxBdRenderSubjectActions($aRow, $blShowActions) {
    global $sDeleteEmoji, $sEditEmoji, $sPortalEmoji;

    if (!$blShowActions) {
        return "";
    }
    return "<span class=\"nx-list-item-actions\">"
        . "<a href=\"#\" class=\"nx-item-action js-edit-subject\" data-subject-id=\"" . nxHtml($aRow["subject_id"]) . "\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>"
        . "<a href=\"#\" class=\"nx-item-action js-edit-subject-portal\" data-subject-id=\"" . nxHtml($aRow["subject_id"]) . "\" title=\"Portal account\" aria-label=\"Portal account\">" . $sPortalEmoji . "</a>"
        . "<a href=\"#\" class=\"nx-item-action js-delete-subject\" data-subject-id=\"" . nxHtml($aRow["subject_id"]) . "\" data-subject-name=\"" . nxHtml($aRow["subject_name"]) . "\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>"
        . "</span>";
}

function nxBdRenderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aBirthdaySettings) {
    global $sBirthdayServedEmoji;

    $iSubjectId = (int)$aRow["subject_id"];
    $sBirthdayServedAction = $blShowActions ? "<a class=\"nx-item-action nx-birthday-served-action js-birthday-served\" href=\"#\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" title=\"Mark birthday served\" aria-label=\"Mark birthday served\"><span class=\"nx-copy-action-box\">" . $sBirthdayServedEmoji . "</span></a>" : "";
    $sBirthdayInCell = nxHtmlValue($aRow["days_to_birthday"]) . ($sBirthdayServedAction != "" ? "&#8288;" . $sBirthdayServedAction : "");
    return nxRenderResponsiveSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aHiddenInactive, $aBirthdaySettings, array(
        "show_actions" => $blShowActions,
        "item_subject_id" => $iSubjectId,
        "before_name_cells" => array(nxRenderSubjectTableCell($sBirthdayInCell, "nx-birthday-in-column")),
        "name_actions" => nxBdRenderSubjectActions($aRow, $blShowActions),
        "birth_name_class" => "nx-column-step-two",
        "birth_date_class" => "",
        "death_date_class" => "nx-column-step-two",
        "death_date_style" => "overflow-wrap: normal; white-space: nowrap; word-break: normal;",
        "nickname_show_add_action" => true,
        "nickname_show_cell_copy_action" => true,
        "nickname_cell_copy_before_add_action" => false,
        "address_class" => "nx-column-step-one",
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

function nxBdGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blShowActions) {
    $aRows = nxFetchSubjectRows($oPdo, $iSubjectId);
    if (!$aRows) {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aContacts = nxFetchSubjectContacts($oPdo, $iSubjectId);
    $aNicknames = nxFetchSubjectNicknames($oPdo, $iSubjectId);
    $aAddresses = nxFetchSubjectAddresses($oPdo, $iSubjectId);
    $aGroups = nxFetchSubjectGroups($oPdo, $iSubjectId);
    $aNotes = nxFetchSubjectNotes($oPdo, $iSubjectId);
    $aHiddenInactive = nxGetHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aBirthdaySettings);
    nxApplySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aBirthdaySettings);
    if (!$aRows || (string)$aRows[0]["subject_type"] != "person") {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aBirthdayInfo = nxBdGetBirthdayInfo(isset($aRows[0]["birth_date"]) ? $aRows[0]["birth_date"] : "");
    if (!is_array($aBirthdayInfo)) {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    if (nxBdIsBirthdayServed(nxBdFetchBirthdayServedRows($oPdo), $iSubjectId, $aBirthdayInfo["birthday_date"])) {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aRows[0]["days_to_birthday"] = $aBirthdayInfo["days_to_birthday"];
    $aRows[0]["birthday_date"] = $aBirthdayInfo["birthday_date"];
    return array(
        "success" => true,
        "subject_id" => $iSubjectId,
        "row_html" => nxBdRenderSubjectRow($aRows[0], $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aBirthdaySettings)
    );
}

function nxCardDavRealm() {
    return "EVED CardDAV";
}

function nxCardDavSendCommonHeaders() {
    header("DAV: 1, 3, addressbook", true);
    header("MS-Author-Via: DAV", true);
    header("X-Robots-Tag: noindex, nofollow", true);
    sendSecurityHeaders();
}

function nxCardDavSendTextAndExit($iStatusCode, $sText) {
    $sBody = (string)$sText . "\r\n";
    http_response_code($iStatusCode);
    header("Content-Type: text/plain; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    nxCardDavSendCommonHeaders();
    echo $sBody;
    exit;
}

function nxCardDavSendAuthChallengeAndExit() {
    $sBody = "Authentication required.\r\n";
    http_response_code(401);
    header("WWW-Authenticate: Basic realm=\"" . str_replace("\"", "", nxCardDavRealm()) . "\", charset=\"UTF-8\"", true);
    header("Content-Type: text/plain; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    nxCardDavSendCommonHeaders();
    echo $sBody;
    exit;
}

function nxCardDavSendOptionsAndExit() {
    http_response_code(204);
    header("Allow: OPTIONS, PROPFIND, REPORT, GET, HEAD", true);
    header("Content-Length: 0", true);
    nxCardDavSendCommonHeaders();
    exit;
}

function nxCardDavHeaderValue($sName) {
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

function nxCardDavBasicCredentials() {
    $sUserName = isset($_SERVER["PHP_AUTH_USER"]) ? (string)$_SERVER["PHP_AUTH_USER"] : "";
    $sPassword = isset($_SERVER["PHP_AUTH_PW"]) ? (string)$_SERVER["PHP_AUTH_PW"] : "";
    $sAuthorization = "";
    $sDecoded = "";
    $iColon = 0;

    if ($sUserName != "" || $sPassword != "") {
        return array($sUserName, $sPassword);
    }

    $sAuthorization = trim(nxCardDavHeaderValue("Authorization"));
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

function nxCardDavRequireUser($oPdo) {
    list($sUserName, $sPassword) = nxCardDavBasicCredentials();
    $aUser = null;
    if (trim($sUserName) == "" || $sPassword == "") {
        nxCardDavSendAuthChallengeAndExit();
    }
    try {
        $aUser = exFetchPortalLoginUser($oPdo, trim($sUserName));
    } catch (Exception $oException) {
        nxCardDavSendTextAndExit(500, "Database error.");
    }
    if (!$aUser || (int)$aUser["is_active"] != 1 || (int)$aUser["subject_active"] != 1 || !in_array((string)$aUser["subject_type"], array("person", "service"), true) || !password_verify($sPassword, (string)$aUser["password_hash"]) || (!exUserHasPermission($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"], "portal.view") && !exUserHasPermission($oPdo, (int)$aUser["id"], (int)$aUser["subject_id"], "portal.full"))) {
        nxCardDavSendAuthChallengeAndExit();
    }
    return $aUser;
}

function nxCardDavPathInfo() {
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
    if ($sPath == "") {
        $sRequestPath = isset($_SERVER["REQUEST_URI"]) ? (string)parse_url((string)$_SERVER["REQUEST_URI"], PHP_URL_PATH) : "";
        $sScriptPath = nxCardDavScriptPath();
        if ($sRequestPath != "" && strpos($sRequestPath, $sScriptPath) === 0) {
            $sPath = substr($sRequestPath, strlen($sScriptPath));
        }
    }
    if ($sPath == "") {
        $sPath = "/";
    }
    $sPath = "/" . ltrim(str_replace("\\", "/", $sPath), "/");
    $sPath = preg_replace("#/+#", "/", $sPath);
    return $sPath;
}

function nxCardDavScriptPath() {
    $sPath = isset($_SERVER["SCRIPT_NAME"]) ? (string)$_SERVER["SCRIPT_NAME"] : "/carddav.php";
    $sRequestPath = "";
    $iPhpPos = false;
    $sPath = str_replace("\\", "/", $sPath);
    $iPhpPos = stripos($sPath, ".php");
    if ($iPhpPos !== false) {
        $sPath = substr($sPath, 0, $iPhpPos + 4);
    }
    if ($sPath == "") {
        $sRequestPath = isset($_SERVER["REQUEST_URI"]) ? (string)parse_url((string)$_SERVER["REQUEST_URI"], PHP_URL_PATH) : "";
        $sRequestPath = str_replace("\\", "/", $sRequestPath);
        $iPhpPos = stripos($sRequestPath, ".php");
        $sPath = $iPhpPos !== false ? substr($sRequestPath, 0, $iPhpPos + 4) : "/carddav.php";
    }
    return $sPath;
}

function nxCardDavHref($aQuery) {
    $sHref = nxCardDavScriptPath();
    if (is_array($aQuery) && count($aQuery) > 0) {
        $sHref .= "?" . http_build_query($aQuery, "", "&");
    }
    return $sHref;
}

function nxCardDavIsHomePath($sPath) {
    return (string)$sPath == "/";
}

function nxCardDavIsAddressBookPath($sPath) {
    return (string)$sPath == "/addressbook" || (string)$sPath == "/addressbook/";
}

function nxCardDavIsPrincipalCollectionPath($sPath) {
    return (string)$sPath == "/principals" || (string)$sPath == "/principals/";
}

function nxCardDavXml($mValue) {
    return htmlspecialchars((string)$mValue, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, "UTF-8");
}

function nxCardDavVCardEscape($mValue) {
    $sValue = (string)$mValue;
    $sValue = str_replace("\\", "\\\\", $sValue);
    $sValue = str_replace("\r\n", "\\n", $sValue);
    $sValue = str_replace("\r", "\\n", $sValue);
    $sValue = str_replace("\n", "\\n", $sValue);
    $sValue = str_replace(";", "\\;", $sValue);
    $sValue = str_replace(",", "\\,", $sValue);
    return $sValue;
}

function nxCardDavVCardList($aValues) {
    $aEscaped = array();
    foreach ($aValues as $sValue) {
        $sValue = trim((string)$sValue);
        if ($sValue != "") {
            $aEscaped[] = nxCardDavVCardEscape($sValue);
        }
    }
    return implode(",", $aEscaped);
}

function nxCardDavVCardLine($sName, $mValue, $sParams = "") {
    $sLine = strtoupper((string)$sName) . (trim((string)$sParams) != "" ? ";" . trim((string)$sParams) : "") . ":" . nxCardDavVCardEscape($mValue);
    return $sLine;
}

function nxCardDavVCardRawLine($sName, $mValue, $sParams = "") {
    return strtoupper((string)$sName) . (trim((string)$sParams) != "" ? ";" . trim((string)$sParams) : "") . ":" . (string)$mValue;
}

function nxCardDavCleanTypeToken($sValue) {
    $sValue = strtoupper(preg_replace("/[^A-Za-z0-9\\-]/", "-", (string)$sValue));
    $sValue = trim($sValue, "-");
    return $sValue != "" ? $sValue : "OTHER";
}

function nxCardDavAddressType($sAddressType) {
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

function nxCardDavPhoneType($sContactType) {
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

function nxCardDavAddressStreet($aAddress) {
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

function nxCardDavAddressExtended($aAddress) {
    $aParts = array();
    foreach (array("organization_name", "department_name", "care_of", "address_line2") as $sKey) {
        $sValue = trim((string)$aAddress[$sKey]);
        if ($sValue != "") {
            $aParts[] = $sValue;
        }
    }
    return implode(", ", $aParts);
}

function nxCardDavAddressLabel($aAddress) {
    $aLines = array();
    $sExtended = nxCardDavAddressExtended($aAddress);
    $sStreet = nxCardDavAddressStreet($aAddress);
    $sCity = trim((string)$aAddress["city"]);
    $sCityPart = trim((string)$aAddress["city_part"]);
    $sPostalCode = nxPostalCodeDisplayValue($aAddress["country"], $aAddress["postal_code"]);
    $sRegion = trim((string)$aAddress["region"]);
    $sCountry = nxCountryCodeToName($aAddress["country"]);
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

function nxCardDavAddVCardContactLines(&$aLines, $aContact) {
    $sType = (string)$aContact["contact_type"];
    $sTypeName = trim((string)$aContact["contact_type_name"]);
    $sValue = nxContactDisplayValue($sType, $aContact["contact_value"]);
    $sHref = nxContactHref($sType, $aContact["contact_value"], true);
    $sPref = (int)$aContact["is_primary"] == 1 ? ",PREF" : "";
    if ($sValue == "") {
        return;
    }
    if ($sType == "email") {
        $aLines[] = nxCardDavVCardLine("EMAIL", $sValue, "TYPE=INTERNET" . $sPref);
        return;
    }
    if (nxIsPhoneContactType($sType) || $sType == "whatsapp" || $sType == "viber") {
        $aLines[] = nxCardDavVCardLine("TEL", $sValue, "TYPE=" . nxCardDavPhoneType($sType) . $sPref);
        return;
    }
    if ($sType == "web" || preg_match("#^https?://#i", $sHref)) {
        $aLines[] = nxCardDavVCardLine("URL", $sHref != "" ? $sHref : $sValue, "TYPE=" . nxCardDavCleanTypeToken($sTypeName != "" ? $sTypeName : $sType));
        return;
    }
    if ($sType == "jabber") {
        $aLines[] = nxCardDavVCardLine("X-JABBER", $sValue);
        $aLines[] = nxCardDavVCardLine("IMPP", "xmpp:" . $sValue, "TYPE=" . nxCardDavCleanTypeToken($sTypeName != "" ? $sTypeName : $sType));
        return;
    }
    if ($sHref != "") {
        $aLines[] = nxCardDavVCardLine("IMPP", $sHref, "TYPE=" . nxCardDavCleanTypeToken($sTypeName != "" ? $sTypeName : $sType));
        return;
    }
    $aLines[] = nxCardDavVCardLine("X-EVED-CONTACT", ($sTypeName != "" ? $sTypeName : $sType) . ": " . $sValue);
}

function nxCardDavBuildCard($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes) {
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
    $aLines[] = nxCardDavVCardLine("PRODID", "-//EVED//Readonly CardDAV//EN");
    $aLines[] = nxCardDavVCardLine("UID", $sUid);
    $aLines[] = nxCardDavVCardLine("FN", $sFullName);
    if ($sSubjectType == "person") {
        $aLines[] = nxCardDavVCardRawLine(
            "N",
            nxCardDavVCardEscape($aRow["last_name"]) . ";"
            . nxCardDavVCardEscape($aRow["first_name"]) . ";"
            . nxCardDavVCardEscape($aRow["middle_name"]) . ";"
            . nxCardDavVCardEscape($aRow["title_before"]) . ";"
            . nxCardDavVCardEscape($aRow["title_after"])
        );
        if (trim((string)$aRow["birth_date"]) != "") {
            $aLines[] = nxCardDavVCardLine("BDAY", $aRow["birth_date"]);
        }
        if (trim((string)$aRow["death_date"]) != "") {
            $aLines[] = nxCardDavVCardLine("X-DEATHDATE", $aRow["death_date"]);
        }
    } else {
        $aLines[] = nxCardDavVCardRawLine("N", ";" . nxCardDavVCardEscape($sFullName) . ";;;");
        $aLines[] = nxCardDavVCardLine("ORG", $sFullName);
    }
    foreach ($aNicknames as $aNickname) {
        if ((int)$aNickname["is_active"] == 1 && trim((string)$aNickname["nickname"]) != "") {
            $aActiveNicknames[] = (string)$aNickname["nickname"];
        }
    }
    if (count($aActiveNicknames) > 0) {
        $aLines[] = nxCardDavVCardRawLine("NICKNAME", nxCardDavVCardList($aActiveNicknames));
    }
    foreach ($aContacts as $aContact) {
        if ((int)$aContact["is_active"] == 1) {
            nxCardDavAddVCardContactLines($aLines, $aContact);
        }
    }
    foreach ($aAddresses as $aAddress) {
        if ((int)$aAddress["is_active"] != 1) {
            continue;
        }
        $sAdrType = nxCardDavAddressType($aAddress["address_type"]) . ((int)$aAddress["is_primary"] == 1 ? ",PREF" : "");
        $sCountry = nxCountryCodeToName($aAddress["country"]);
        $sAdrValue = ";"
            . nxCardDavVCardEscape(nxCardDavAddressExtended($aAddress)) . ";"
            . nxCardDavVCardEscape(nxCardDavAddressStreet($aAddress)) . ";"
            . nxCardDavVCardEscape($aAddress["city"]) . ";"
            . nxCardDavVCardEscape($aAddress["region"]) . ";"
            . nxCardDavVCardEscape(nxPostalCodeDisplayValue($aAddress["country"], $aAddress["postal_code"])) . ";"
            . nxCardDavVCardEscape($sCountry);
        $aLines[] = nxCardDavVCardRawLine("ADR", $sAdrValue, "TYPE=" . $sAdrType);
        $aLines[] = nxCardDavVCardLine("LABEL", nxCardDavAddressLabel($aAddress), "TYPE=" . $sAdrType);
    }
    foreach ($aGroups as $aGroup) {
        if (trim((string)$aGroup["name"]) != "") {
            $aActiveGroups[] = (string)$aGroup["name"];
        }
    }
    if (count($aActiveGroups) > 0) {
        $aLines[] = nxCardDavVCardRawLine("CATEGORIES", nxCardDavVCardList($aActiveGroups));
    }
    foreach ($aNotes as $aNote) {
        if ((int)$aNote["is_active"] == 1 && trim((string)$aNote["note_text"]) != "") {
            $aActiveNotes[] = (string)$aNote["note_text"];
        }
    }
    if (count($aActiveNotes) > 0) {
        $aLines[] = nxCardDavVCardLine("NOTE", implode("\n\n", $aActiveNotes));
    }
    $aLines[] = nxCardDavVCardLine("X-EVED-SUBJECT-ID", $iSubjectId);
    $aLines[] = nxCardDavVCardLine("X-EVED-SUBJECT-TYPE", $sSubjectType);
    $aLines[] = "END:VCARD";
    return implode("\r\n", $aLines) . "\r\n";
}

function nxCardDavFetchCards($oPdo) {
    $aCards = array();
    $aRows = array();
    $aContacts = array();
    $aNicknames = array();
    $aAddresses = array();
    $aGroups = array();
    $aNotes = array();
    $oPdo->query("SET SESSION group_concat_max_len = 1048576");
    $aRows = nxFetchSubjectRows($oPdo);
    $aContacts = nxFetchSubjectContacts($oPdo);
    $aNicknames = nxFetchSubjectNicknames($oPdo);
    $aAddresses = nxFetchSubjectAddresses($oPdo);
    $aGroups = nxFetchSubjectGroups($oPdo);
    $aNotes = nxFetchSubjectNotes($oPdo);
    foreach ($aRows as $aRow) {
        $iSubjectId = (int)$aRow["subject_id"];
        $sBody = "";
        if ((int)$aRow["is_active"] != 1) {
            continue;
        }
        $sBody = nxCardDavBuildCard(
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
            "href" => nxCardDavHref(array("card" => (int)$iSubjectId)),
            "body" => $sBody,
            "etag" => "\"" . sha1($sBody) . "\"",
            "last_modified" => trim((string)$aRow["created_at"]) != "" ? strtotime((string)$aRow["created_at"]) : time()
        );
    }
    return $aCards;
}

function nxCardDavCollectionTag($aCards) {
    $aEtags = array();
    foreach ($aCards as $aCard) {
        $aEtags[] = (string)$aCard["etag"];
    }
    sort($aEtags);
    return sha1(implode("\n", $aEtags));
}

function nxCardDavResponseStart($sHref) {
    return "  <d:response>\r\n"
        . "    <d:href>" . nxCardDavXml($sHref) . "</d:href>\r\n"
        . "    <d:propstat>\r\n"
        . "      <d:prop>\r\n";
}

function nxCardDavResponseEnd() {
    return "      </d:prop>\r\n"
        . "      <d:status>HTTP/1.1 200 OK</d:status>\r\n"
        . "    </d:propstat>\r\n"
        . "  </d:response>\r\n";
}

function nxCardDavHomePropsXml($aCards, $aUser) {
    $sHomeHref = nxCardDavHref(array());
    $sCollectionHref = nxCardDavHref(array());
    $sPrincipalHref = nxCardDavHref(array("principal" => (string)$aUser["user_name"]));
    $sPrincipalCollectionHref = nxCardDavHref(array("principals" => "1"));
    return "        <d:resourcetype><d:collection/></d:resourcetype>\r\n"
        . "        <d:displayname>EVED CardDAV</d:displayname>\r\n"
        . "        <d:current-user-principal><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:current-user-principal>\r\n"
        . "        <d:principal-URL><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:principal-URL>\r\n"
        . "        <d:principal-collection-set><d:href>" . nxCardDavXml($sPrincipalCollectionHref) . "</d:href></d:principal-collection-set>\r\n"
        . "        <card:addressbook-home-set><d:href>" . nxCardDavXml($sHomeHref) . "</d:href></card:addressbook-home-set>\r\n"
        . "        <cs:getctag>" . nxCardDavXml(nxCardDavCollectionTag($aCards)) . "</cs:getctag>\r\n"
        . "        <d:supported-report-set>\r\n"
        . "          <d:supported-report><d:report><d:principal-property-search/></d:report></d:supported-report>\r\n"
        . "        </d:supported-report-set>\r\n"
        . "        <d:current-user-privilege-set><d:privilege><d:read/></d:privilege></d:current-user-privilege-set>\r\n"
        . "        <d:owner><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:owner>\r\n"
        . "        <d:sync-token>" . nxCardDavXml($sCollectionHref . nxCardDavCollectionTag($aCards)) . "</d:sync-token>\r\n";
}

function nxCardDavCollectionPropsXml($aCards, $aUser) {
    $sHomeHref = nxCardDavHref(array());
    $sPrincipalHref = nxCardDavHref(array("principal" => (string)$aUser["user_name"]));
    $sPrincipalCollectionHref = nxCardDavHref(array("principals" => "1"));
    $sCollectionHref = nxCardDavHref(array());
    return "        <d:resourcetype><d:collection/><card:addressbook/></d:resourcetype>\r\n"
        . "        <d:displayname>EVED Contacts</d:displayname>\r\n"
        . "        <d:current-user-principal><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:current-user-principal>\r\n"
        . "        <d:principal-URL><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:principal-URL>\r\n"
        . "        <d:principal-collection-set><d:href>" . nxCardDavXml($sPrincipalCollectionHref) . "</d:href></d:principal-collection-set>\r\n"
        . "        <d:owner><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:owner>\r\n"
        . "        <card:addressbook-home-set><d:href>" . nxCardDavXml($sHomeHref) . "</d:href></card:addressbook-home-set>\r\n"
        . "        <card:addressbook-description>EVED readonly contacts</card:addressbook-description>\r\n"
        . "        <card:supported-address-data><card:address-data content-type=\"text/vcard\" version=\"3.0\"/></card:supported-address-data>\r\n"
        . "        <cs:getctag>" . nxCardDavXml(nxCardDavCollectionTag($aCards)) . "</cs:getctag>\r\n"
        . "        <d:sync-token>" . nxCardDavXml($sCollectionHref . nxCardDavCollectionTag($aCards)) . "</d:sync-token>\r\n"
        . "        <d:current-user-privilege-set><d:privilege><d:read/></d:privilege></d:current-user-privilege-set>\r\n"
        . "        <d:supported-report-set>\r\n"
        . "          <d:supported-report><d:report><card:addressbook-query/></d:report></d:supported-report>\r\n"
        . "          <d:supported-report><d:report><card:addressbook-multiget/></d:report></d:supported-report>\r\n"
        . "        </d:supported-report-set>\r\n";
}

function nxCardDavPrincipalPropsXml($aUser) {
    $sHomeHref = nxCardDavHref(array());
    $sPrincipalHref = nxCardDavHref(array("principal" => (string)$aUser["user_name"]));
    $sPrincipalCollectionHref = nxCardDavHref(array("principals" => "1"));
    return "        <d:resourcetype><d:collection/><d:principal/></d:resourcetype>\r\n"
        . "        <d:displayname>" . nxCardDavXml($aUser["user_name"]) . "</d:displayname>\r\n"
        . "        <d:current-user-principal><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:current-user-principal>\r\n"
        . "        <d:principal-URL><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:principal-URL>\r\n"
        . "        <d:principal-collection-set><d:href>" . nxCardDavXml($sPrincipalCollectionHref) . "</d:href></d:principal-collection-set>\r\n"
        . "        <card:addressbook-home-set><d:href>" . nxCardDavXml($sHomeHref) . "</d:href></card:addressbook-home-set>\r\n";
}

function nxCardDavPrincipalCollectionPropsXml($aUser) {
    $sPrincipalHref = nxCardDavHref(array("principal" => (string)$aUser["user_name"]));
    $sPrincipalCollectionHref = nxCardDavHref(array("principals" => "1"));
    return "        <d:resourcetype><d:collection/></d:resourcetype>\r\n"
        . "        <d:displayname>EVED Principals</d:displayname>\r\n"
        . "        <d:current-user-principal><d:href>" . nxCardDavXml($sPrincipalHref) . "</d:href></d:current-user-principal>\r\n"
        . "        <d:principal-collection-set><d:href>" . nxCardDavXml($sPrincipalCollectionHref) . "</d:href></d:principal-collection-set>\r\n";
}

function nxCardDavCardPropsXml($aCard, $blIncludeAddressData) {
    $sXml = "        <d:resourcetype/>\r\n"
        . "        <d:getcontenttype>text/vcard; charset=utf-8</d:getcontenttype>\r\n"
        . "        <d:getcontentlength>" . strlen($aCard["body"]) . "</d:getcontentlength>\r\n"
        . "        <d:getetag>" . nxCardDavXml($aCard["etag"]) . "</d:getetag>\r\n"
        . "        <d:getlastmodified>" . gmdate("D, d M Y H:i:s", (int)$aCard["last_modified"]) . " GMT</d:getlastmodified>\r\n";
    if ($blIncludeAddressData) {
        $sXml .= "        <card:address-data>" . nxCardDavXml($aCard["body"]) . "</card:address-data>\r\n";
    }
    return $sXml;
}

function nxCardDavMultistatusAndExit($sInnerXml) {
    $sBody = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\r\n"
        . "<d:multistatus xmlns:d=\"DAV:\" xmlns:card=\"urn:ietf:params:xml:ns:carddav\" xmlns:cs=\"http://calendarserver.org/ns/\">\r\n"
        . $sInnerXml
        . "</d:multistatus>\r\n";
    http_response_code(207);
    header("Content-Type: application/xml; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
    header("Pragma: no-cache", true);
    nxCardDavSendCommonHeaders();
    echo $sBody;
    exit;
}

function nxCardDavIsPrincipalPath($sPath) {
    return preg_match("#^/principals/[^/]+/?$#", $sPath) === 1;
}

function nxCardDavSubjectIdFromPath($sPath) {
    if (preg_match("#^/(?:addressbook/)?ex-subject-([0-9]+)\\.vcf$#", $sPath, $aMatches)) {
        return (int)$aMatches[1];
    }
    return 0;
}

function nxCardDavSubjectIdFromHref($sHref) {
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
    if (strpos($sPath, nxCardDavScriptPath()) === 0) {
        $sPath = substr($sPath, strlen(nxCardDavScriptPath()));
    }
    if ($sPath == "") {
        $sPath = "/";
    }
    if ($sPath[0] != "/") {
        $sPath = "/" . $sPath;
    }
    return nxCardDavSubjectIdFromPath($sPath);
}

function nxCardDavRequestBody() {
    $sBody = file_get_contents("php://input");
    return $sBody !== false ? $sBody : "";
}

function nxCardDavRequestHrefs($sBody) {
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

function nxCardDavSendPropfindAndExit($aCards, $aUser, $sPath) {
    $sDepth = isset($_SERVER["HTTP_DEPTH"]) ? (string)$_SERVER["HTTP_DEPTH"] : "infinity";
    $sXml = "";
    if (nxCardDavIsHomePath($sPath)) {
        $sXml .= nxCardDavResponseStart(nxCardDavHref(array()))
            . nxCardDavCollectionPropsXml($aCards, $aUser)
            . nxCardDavResponseEnd();
        if ($sDepth != "0") {
            foreach ($aCards as $aCard) {
                $sXml .= nxCardDavResponseStart($aCard["href"])
                    . nxCardDavCardPropsXml($aCard, false)
                    . nxCardDavResponseEnd();
            }
        }
        nxCardDavMultistatusAndExit($sXml);
    }
    if (nxCardDavIsAddressBookPath($sPath)) {
        $sXml .= nxCardDavResponseStart(nxCardDavHref(array()))
            . nxCardDavCollectionPropsXml($aCards, $aUser)
            . nxCardDavResponseEnd();
        if ($sDepth != "0") {
            foreach ($aCards as $aCard) {
                $sXml .= nxCardDavResponseStart($aCard["href"])
                    . nxCardDavCardPropsXml($aCard, false)
                    . nxCardDavResponseEnd();
            }
        }
        nxCardDavMultistatusAndExit($sXml);
    }
    if (nxCardDavIsPrincipalCollectionPath($sPath)) {
        $sXml .= nxCardDavResponseStart(nxCardDavHref(array("principals" => "1")))
            . nxCardDavPrincipalCollectionPropsXml($aUser)
            . nxCardDavResponseEnd();
        if ($sDepth != "0") {
            $sXml .= nxCardDavResponseStart(nxCardDavHref(array("principal" => (string)$aUser["user_name"])))
                . nxCardDavPrincipalPropsXml($aUser)
                . nxCardDavResponseEnd();
        }
        nxCardDavMultistatusAndExit($sXml);
    }
    if (nxCardDavIsPrincipalPath($sPath)) {
        $sXml .= nxCardDavResponseStart(nxCardDavHref(array("principal" => (string)$aUser["user_name"])))
            . nxCardDavPrincipalPropsXml($aUser)
            . nxCardDavResponseEnd();
        nxCardDavMultistatusAndExit($sXml);
    }
    $iSubjectId = nxCardDavSubjectIdFromPath($sPath);
    if ($iSubjectId > 0 && isset($aCards[$iSubjectId])) {
        $sXml .= nxCardDavResponseStart($aCards[$iSubjectId]["href"])
            . nxCardDavCardPropsXml($aCards[$iSubjectId], false)
            . nxCardDavResponseEnd();
        nxCardDavMultistatusAndExit($sXml);
    }
    nxCardDavSendTextAndExit(404, "Not found.");
}

function nxCardDavSendReportAndExit($aCards, $sPath) {
    $sBody = nxCardDavRequestBody();
    $aHrefs = nxCardDavRequestHrefs($sBody);
    $aWantedIds = array();
    $sXml = "";
    $blIncludeAddressData = stripos($sBody, "address-data") !== false;
    if (!nxCardDavIsHomePath($sPath) && !nxCardDavIsAddressBookPath($sPath)) {
        nxCardDavSendTextAndExit(404, "Not found.");
    }
    foreach ($aHrefs as $sHref) {
        $iSubjectId = nxCardDavSubjectIdFromHref($sHref);
        if ($iSubjectId > 0) {
            $aWantedIds[$iSubjectId] = true;
        }
    }
    foreach ($aCards as $iSubjectId => $aCard) {
        if (count($aWantedIds) > 0 && empty($aWantedIds[$iSubjectId])) {
            continue;
        }
        $sXml .= nxCardDavResponseStart($aCard["href"])
            . nxCardDavCardPropsXml($aCard, $blIncludeAddressData)
            . nxCardDavResponseEnd();
    }
    nxCardDavMultistatusAndExit($sXml);
}

function nxCardDavSendGetAndExit($aCards, $sPath, $blHeadOnly) {
    $iSubjectId = nxCardDavSubjectIdFromPath($sPath);
    $aCard = null;
    if (nxCardDavIsHomePath($sPath) || nxCardDavIsAddressBookPath($sPath)) {
        nxCardDavSendCollectionGetAndExit($aCards, $sPath, $blHeadOnly);
    }
    if ($iSubjectId < 1 || !isset($aCards[$iSubjectId])) {
        nxCardDavSendTextAndExit(404, "Not found.");
    }
    $aCard = $aCards[$iSubjectId];
    http_response_code(200);
    header("Content-Type: text/vcard; charset=utf-8", true);
    header("Content-Length: " . strlen($aCard["body"]), true);
    header("ETag: " . $aCard["etag"], true);
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", (int)$aCard["last_modified"]) . " GMT", true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    nxCardDavSendCommonHeaders();
    if (!$blHeadOnly) {
        echo $aCard["body"];
    }
    exit;
}

function nxCardDavSendCollectionGetAndExit($aCards, $sPath, $blHeadOnly) {
    $sBody = "EVED CardDAV endpoint\r\n"
        . "\r\n"
        . "CardDAV home: " . nxCardDavHref(array()) . "\r\n"
        . "Address book: " . nxCardDavHref(array()) . "\r\n"
        . "Contacts: " . count($aCards) . "\r\n"
        . "\r\n"
        . "Use a CardDAV client such as Thunderbird. This endpoint is read-only.\r\n";
    http_response_code(200);
    header("Content-Type: text/plain; charset=utf-8", true);
    header("Content-Length: " . strlen($sBody), true);
    header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
    nxCardDavSendCommonHeaders();
    if (!$blHeadOnly) {
        echo $sBody;
    }
    exit;
}

function nxContactsNormalizeKey($sValue) {
    if (function_exists("mb_strtolower")) {
        return mb_strtolower((string)$sValue, "UTF-8");
    }
    return strtolower((string)$sValue);
}

function nxContactsCompareRows($aFirst, $aSecond) {
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

function nxContactsCompareSubjects($aFirst, $aSecond) {
    $iResult = strcmp((string)$aFirst["subject_name"], (string)$aSecond["subject_name"]);
    if ($iResult !== 0) {
        return $iResult;
    }
    return (int)$aFirst["subject_contact_id"] - (int)$aSecond["subject_contact_id"];
}

function nxContactsSubjectCellClass($aSubject) {
    $sSubjectType = preg_replace("/[^a-z0-9_-]/", "-", strtolower((string)$aSubject["subject_type"]));
    return "nx-contact-subject-cell nx-contact-subject-type-" . $sSubjectType . (!empty($aSubject["is_active"]) && (int)$aSubject["contact_is_active"] == 1 ? " nx-contact-subject-active" : " nx-contact-subject-inactive");
}

function nxContactsRenderContactDataAttributes($aContactRow) {
    return " data-contact-id=\"" . nxHtml($aContactRow["contact_id"]) . "\""
        . " data-contact-type-id=\"" . nxHtml($aContactRow["contact_type_id"]) . "\""
        . " data-contact-type=\"" . nxHtml($aContactRow["contact_type"]) . "\""
        . " data-contact-type-name=\"" . nxHtml($aContactRow["contact_type_name"]) . "\""
        . " data-contact-value=\"" . nxHtml($aContactRow["contact_display_value"]) . "\""
        . nxRenderTimestampTooltipDataAttribute($aContactRow);
}

function nxContactsRenderSubjectDataAttributes($aSubject) {
    return " data-subject-contact-id=\"" . nxHtml($aSubject["subject_contact_id"]) . "\""
        . " data-subject-id=\"" . nxHtml($aSubject["subject_id"]) . "\""
        . " data-contact-id=\"" . nxHtml($aSubject["contact_id"]) . "\""
        . " data-contact-type-id=\"" . nxHtml($aSubject["contact_type_id"]) . "\""
        . " data-contact-type=\"" . nxHtml($aSubject["contact_type"]) . "\""
        . " data-contact-type-name=\"" . nxHtml($aSubject["contact_type_name"]) . "\""
        . " data-contact-value=\"" . nxHtml($aSubject["contact_display_value"]) . "\""
        . " data-contact-note=\"" . nxHtml($aSubject["note"]) . "\""
        . " data-contact-primary=\"" . ((int)$aSubject["is_primary"] == 1 ? "1" : "0") . "\""
        . " data-contact-active=\"" . ((int)$aSubject["contact_is_active"] == 1 ? "1" : "0") . "\""
        . " data-subject-active=\"" . (!empty($aSubject["is_active"]) ? "1" : "0") . "\"";
}

function nxContactsFetchRows($oPdo, $aContactSettings) {
    $aRows = array();
    $aSubjectNames = array();
    $aSubjectRows = nxFetchSubjectRows($oPdo);
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
        $sContactDisplayValue = nxContactDisplayValue($sContactType, $aContact["contact_value"]);
        if (!isset($aRows[$iContactId])) {
            $aRows[$iContactId] = array(
                "contact_id" => $iContactId,
                "contact_type_id" => (int)$aContact["contact_type_id"],
                "contact_type" => $sContactType,
                "contact_type_name" => (string)$aContact["contact_type_name"],
                "contact_type_order" => (int)$aContact["contact_type_order"],
                "contact_type_sort" => nxContactsNormalizeKey((string)$aContact["contact_type_name"]),
                "contact_value" => (string)$aContact["contact_value"],
                "contact_display_value" => $sContactDisplayValue,
                "contact_sort" => nxContactsNormalizeKey($sContactDisplayValue),
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
        usort($aRows[$iContactId]["subjects"], "nxContactsCompareSubjects");
    }
    uasort($aRows, "nxContactsCompareRows");
    return $aRows;
}

function nxRenderContactTypeAdminRows($oPdo, $blCanEdit) {
    $sHtml = "";
    foreach (nxFetchContactTypeAdminRows($oPdo) as $aContactType) {
        $sHtml .= nxRenderContactTypeAdminRow($aContactType, $blCanEdit);
    }
    return $sHtml;
}

function nxGetDemoFullListComplexFilterFields() {
    return array(
        "subject_type" => array("label" => "Type", "value_type" => "text"),
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

function nxNormalizeDemoFullListComplexFilter($aPayload, $aFields, $aOperators) {
    $aFilter = nxGetDefaultFullListComplexFilter();
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
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
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
        if (empty($aOperators[$sOperator]["needs_value"])) {
            $sValue = "";
        }
        $aFilter["conditions"][] = array(
            "field" => $sField,
            "operator" => $sOperator,
            "value" => $sValue
        );
    }
    return $aFilter;
}

function nxNormalizeDemoFullListComplexFilterDraft($aPayload, $aFields, $aOperators) {
    $aFilter = nxGetDefaultFullListComplexFilterDraft();
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
                $sOperator = "contains";
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
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
                $sOperator = "contains";
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
        }
    }
    if (!$aFilter["conditions"]) {
        $aFilter = nxGetDefaultFullListComplexFilterDraft();
    }
    return $aFilter;
}

function nxRenderDemoFullListComplexFilterOperatorOptions($aOperators, $sSelected) {
    $sHtml = "<option value=\"\" data-needs-value=\"1\"" . ($sSelected == "" ? " selected" : "") . "></option>";
    foreach ($aOperators as $sOperator => $aOperator) {
        $sHtml .= "<option value=\"" . nxHtml($sOperator) . "\" data-needs-value=\"" . (!empty($aOperator["needs_value"]) ? "1" : "0") . "\"" . ($sSelected == $sOperator ? " selected" : "") . ">" . nxHtml($aOperator["label"]) . "</option>";
    }
    return $sHtml;
}

function nxDemoFullListLower($sValue) {
    return function_exists("mb_strtolower") ? mb_strtolower((string)$sValue, "UTF-8") : strtolower((string)$sValue);
}

function nxDemoFullListJoinContacts($aContacts) {
    $aValues = array();
    foreach ($aContacts as $aContact) {
        $sValue = nxContactTypeLabel($aContact["contact_type"]) . ": " . (string)$aContact["contact_value"];
        if (isset($aContact["note"]) && $aContact["note"] != "") {
            $sValue .= " (" . (string)$aContact["note"] . ")";
        }
        $aValues[] = $sValue;
    }
    return implode("\n", $aValues);
}

function nxDemoFullListJoinNicknames($aNicknames) {
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

function nxDemoFullListJoinAddresses($aAddresses, $aSettings) {
    $aValues = array();
    foreach ($aAddresses as $aAddress) {
        $aValues[] = nxRenderAddressText($aAddress, $aSettings);
    }
    return implode("\n", $aValues);
}

function nxDemoFullListJoinGroups($aGroups) {
    $aValues = array();
    foreach ($aGroups as $aGroup) {
        $aValues[] = (string)$aGroup["name"];
    }
    return implode("\n", $aValues);
}

function nxDemoFullListJoinNotes($aNotes) {
    $aValues = array();
    foreach ($aNotes as $aNote) {
        $aValues[] = (string)$aNote["note_text"];
    }
    return implode("\n", $aValues);
}

function nxDemoFullListComplexFilterValue($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aSettings, $sField) {
    $iSubjectId = (int)$aRow["subject_id"];
    if ($sField == "contacts") {
        return nxDemoFullListJoinContacts(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array());
    }
    if ($sField == "nicknames") {
        return nxDemoFullListJoinNicknames(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array());
    }
    if ($sField == "addresses") {
        return nxDemoFullListJoinAddresses(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $aSettings);
    }
    if ($sField == "group_names") {
        return nxDemoFullListJoinGroups(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array());
    }
    if ($sField == "notes") {
        return nxDemoFullListJoinNotes(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array());
    }
    return isset($aRow[$sField]) ? (string)$aRow[$sField] : "";
}

function nxDemoFullListComplexFilterAddressValues($aAddresses, $sColumn) {
    $aValues = array();
    foreach ($aAddresses as $aAddress) {
        if (array_key_exists($sColumn, $aAddress) && $aAddress[$sColumn] !== null && $aAddress[$sColumn] != "") {
            $aValues[] = (string)$aAddress[$sColumn];
        }
    }
    return $aValues;
}

function nxNormalizeDemoFullListComplexFilterValue($aField, $sValue) {
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "boolean") {
        $sNormalized = strtolower(trim((string)$sValue));
        if ($sNormalized == "0" || $sNormalized == "false" || $sNormalized == "no" || $sNormalized == "off") {
            return "0";
        }
        return "1";
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "birth_number") {
        $sNormalized = nxNormalizeBirthNumber($sValue);
        return $sNormalized === false ? (string)$sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "country") {
        return nxCountryNameToCode($sValue);
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "address_type") {
        $sNormalized = strtolower(trim((string)$sValue));
        foreach (nxGetAddressTypes() as $sAddressType) {
            if ($sNormalized == $sAddressType || $sNormalized == strtolower(nxAddressTypeLabel($sAddressType))) {
                return $sAddressType;
            }
        }
        return $sNormalized;
    }
    return (string)$sValue;
}

function nxDemoFullListComplexFilterAddressConditionMatches($aValues, $blHasAddressRows, $aCondition, $aField) {
    $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
    $sFilterValue = nxNormalizeDemoFullListComplexFilterValue($aField, isset($aCondition["value"]) ? (string)$aCondition["value"] : "");
    $sLowerFilterValue = nxDemoFullListLower($sFilterValue);
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
        $sValue = nxNormalizeDemoFullListComplexFilterValue($aField, $sValue);
        $sLowerValue = nxDemoFullListLower($sValue);
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

function nxDemoFullListComplexFilterConditionMatches($sValue, $aCondition, $aField) {
    $sOperator = isset($aCondition["operator"]) ? (string)$aCondition["operator"] : "";
    $sFilterValue = nxNormalizeDemoFullListComplexFilterValue($aField, isset($aCondition["value"]) ? (string)$aCondition["value"] : "");
    $sValue = nxNormalizeDemoFullListComplexFilterValue($aField, $sValue);
    $sLowerValue = nxDemoFullListLower($sValue);
    $sLowerFilterValue = nxDemoFullListLower($sFilterValue);
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

function nxApplyDemoFullListComplexFilter($aRows, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aSettings, $aFilter, $aFields) {
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
                $blConditionMatched = nxDemoFullListComplexFilterAddressConditionMatches(nxDemoFullListComplexFilterAddressValues($aSubjectAddresses, $aFields[$sField]["address_column"]), count($aSubjectAddresses) > 0, $aCondition, $aFields[$sField]);
            } elseif (isset($aFields[$sField]["scope_type"]) && (string)$aFields[$sField]["scope_type"] == "person" && (string)$aRow["subject_type"] != "person") {
                $blConditionMatched = false;
            } else {
                $sValue = nxDemoFullListComplexFilterValue($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aSettings, $sField);
                $blConditionMatched = nxDemoFullListComplexFilterConditionMatches($sValue, $aCondition, isset($aFields[$sField]) ? $aFields[$sField] : array());
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

function nxExternalLibraryPermissions($sPath) {
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

function nxExternalLibraryOwner($sPath) {
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

function nxRenderGroupAdminRows($oPdo, $blCanEdit) {
    $sHtml = "";
    foreach (nxFetchGroupAdminRows($oPdo) as $aGroup) {
        $sHtml .= nxRenderGroupAdminRow($aGroup, $blCanEdit);
    }
    return $sHtml;
}

function nxGetFullListComplexFilterFields($aContactTypes) {
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
        $sContactTypeName = trim((string)(isset($aContactType["name"]) ? $aContactType["name"] : ""));
        if ($sContactTypeName == "") {
            $sContactTypeName = trim((string)(isset($aContactType["contact_type"]) ? $aContactType["contact_type"] : ""));
        }
        if ($iContactTypeId > 0 && $sContactTypeName != "") {
            $aFields["contact_type_" . $iContactTypeId] = array("label" => "Contact: " . $sContactTypeName, "contact_type_id" => $iContactTypeId);
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


function nxGetFullListComplexFilterOperators() {
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


function nxGetDefaultFullListComplexFilter() {
    return array(
        "match" => "all",
        "conditions" => array()
    );
}


function nxGetDefaultFullListComplexFilterDraft() {
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

function nxIsFullListComplexFilterOperatorAllowed($aField, $sOperator) {
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

function nxGetFullListComplexFilterDefaultOperator($aField) {
    if (isset($aField["value_type"]) && ((string)$aField["value_type"] == "boolean" || (string)$aField["value_type"] == "country")) {
        return "equals";
    }
    return "contains";
}

function nxNormalizeFullListComplexFilter($aPayload, $aFields, $aOperators) {
    $aFilter = nxGetDefaultFullListComplexFilter();
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
            if (!nxIsFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                continue;
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
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
        if (!nxIsFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
            continue;
        }
        if (empty($aOperators[$sOperator]["needs_value"])) {
            $sValue = "";
        }
        $aFilter["conditions"][] = array(
            "field" => $sField,
            "operator" => $sOperator,
            "value" => $sValue
        );
    }
    return $aFilter;
}

function nxNormalizeFullListComplexFilterDraft($aPayload, $aFields, $aOperators) {
    $aFilter = nxGetDefaultFullListComplexFilterDraft();
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
                $sOperator = nxGetFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (!nxIsFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                $sOperator = nxGetFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
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
                $sOperator = nxGetFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (!nxIsFullListComplexFilterOperatorAllowed($aFields[$sField], $sOperator)) {
                $sOperator = nxGetFullListComplexFilterDefaultOperator($aFields[$sField]);
            }
            if (empty($aOperators[$sOperator]["needs_value"])) {
                $sValue = "";
            }
            $aFilter["conditions"][] = array(
                "field" => $sField,
                "operator" => $sOperator,
                "value" => $sValue
            );
        }
    }
    if (!$aFilter["conditions"]) {
        $aFilter = nxGetDefaultFullListComplexFilterDraft();
    }
    return $aFilter;
}

function nxEscapeFullListComplexFilterLike($sValue) {
    return str_replace(array("!", "%", "_"), array("!!", "!%", "!_"), $sValue);
}

function nxNormalizeFullListComplexFilterSqlValue($aField, $sValue) {
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "boolean") {
        $sNormalized = strtolower(trim((string)$sValue));
        if ($sNormalized == "0" || $sNormalized == "false" || $sNormalized == "no" || $sNormalized == "off") {
            return "0";
        }
        return "1";
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "birth_number") {
        $sNormalized = nxNormalizeBirthNumber($sValue);
        return $sNormalized === false ? (string)$sValue : $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "country") {
        return nxCountryNameToCode($sValue);
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "subject_type") {
        $sNormalized = strtolower(trim((string)$sValue));
        foreach (nxGetSubjectTypes() as $sSubjectType) {
            if ($sNormalized == $sSubjectType) {
                return $sSubjectType;
            }
        }
        return $sNormalized;
    }
    if (isset($aField["value_type"]) && (string)$aField["value_type"] == "address_type") {
        $sNormalized = strtolower(trim((string)$sValue));
        foreach (nxGetAddressTypes() as $sAddressType) {
            if ($sNormalized == $sAddressType || $sNormalized == strtolower(nxAddressTypeLabel($sAddressType))) {
                return $sAddressType;
            }
        }
        return $sNormalized;
    }
    return (string)$sValue;
}

function nxBuildFullListComplexAddressFilterSql($sColumn, $sOperator, $sParam, $sValue) {
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

function nxBuildFullListComplexContactTypeFilterSql($iContactTypeId, $sOperator, $sParam, $sValue) {
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

function nxApplyFullListComplexFilterScopeSql($sSql, $aField) {
    if ($sSql != "" && isset($aField["scope_sql"]) && $aField["scope_sql"] != "") {
        return "(" . (string)$aField["scope_sql"] . " AND " . $sSql . ")";
    }
    return $sSql;
}

function nxBuildFullListComplexFilterSql($aFilter, $aFields, $aOperators) {
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
        $sValue = nxNormalizeFullListComplexFilterSqlValue($aFields[$sField], $sValue);
        if (isset($aFields[$sField]["address_column"])) {
            $sParam = "complex_filter_" . $iIndex;
            $sAddressSql = nxBuildFullListComplexAddressFilterSql($aFields[$sField]["address_column"], $sOperator, $sParam, $sValue);
            if ($sAddressSql == "") {
                continue;
            }
            $aSql[] = $sAddressSql;
            if ($sOperator != "empty" && $sOperator != "not_empty") {
                if ($sOperator == "contains" || $sOperator == "not_contains") {
                    $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator == "starts" || $sOperator == "not_starts") {
                    $aParams[$sParam] = nxEscapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator == "ends" || $sOperator == "not_ends") {
                    $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue);
                } else {
                    $aParams[$sParam] = $sValue;
                }
                $iIndex += 1;
            }
            continue;
        }
        if (isset($aFields[$sField]["contact_type_id"])) {
            $sParam = "complex_filter_" . $iIndex;
            $sContactTypeSql = nxBuildFullListComplexContactTypeFilterSql($aFields[$sField]["contact_type_id"], $sOperator, $sParam, $sValue);
            if ($sContactTypeSql == "") {
                continue;
            }
            $aSql[] = $sContactTypeSql;
            if ($sOperator != "empty" && $sOperator != "not_empty") {
                if ($sOperator == "contains" || $sOperator == "not_contains") {
                    $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator == "starts" || $sOperator == "not_starts") {
                    $aParams[$sParam] = nxEscapeFullListComplexFilterLike($sValue) . "%";
                } elseif ($sOperator == "ends" || $sOperator == "not_ends") {
                    $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue);
                } else {
                    $aParams[$sParam] = $sValue;
                }
                $iIndex += 1;
            }
            continue;
        }
        if (isset($aFields[$sField]["value_type"]) && $aFields[$sField]["value_type"] == "datetime") {
            $sSqlValueBase = "DATE_FORMAT(" . $aFields[$sField]["sql"] . ", '%Y-%m-%dT%H:%i')";
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
                $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator == "not_contains") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator == "starts") {
                $sConditionSql = $sSqlValue . " LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = nxEscapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator == "not_starts") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = nxEscapeFullListComplexFilterLike($sValue) . "%";
            } elseif ($sOperator == "ends") {
                $sConditionSql = $sSqlValue . " LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue);
            } elseif ($sOperator == "not_ends") {
                $sConditionSql = $sSqlValue . " NOT LIKE LOWER(:" . $sParam . ") ESCAPE '!'";
                $aParams[$sParam] = "%" . nxEscapeFullListComplexFilterLike($sValue);
            }
            $iIndex += 1;
        }
        if ($sConditionSql != "") {
            $aSql[] = nxApplyFullListComplexFilterScopeSql($sConditionSql, $aFields[$sField]);
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


function nxRenderFullListComplexFilterFieldOptions($aFields, $sSelected) {
    $sHtml = "<option value=\"\" data-value-type=\"text\"" . ($sSelected == "" ? " selected" : "") . "></option>";
    foreach ($aFields as $sField => $aField) {
        $sValueType = isset($aField["value_type"]) ? (string)$aField["value_type"] : "text";
        $sHtml .= "<option value=\"" . nxHtml($sField) . "\" data-value-type=\"" . nxHtml($sValueType) . "\"" . ($sSelected == $sField ? " selected" : "") . ">" . nxHtml($aField["label"]) . "</option>";
    }
    return $sHtml;
}

function nxRenderFullListComplexFilterOperatorOptions($aOperators, $sSelected, $aField = null) {
    $sHtml = "<option value=\"\" data-needs-value=\"1\"" . ($sSelected == "" ? " selected" : "") . "></option>";
    foreach ($aOperators as $sOperator => $aOperator) {
        $sDisabled = is_array($aField) && !nxIsFullListComplexFilterOperatorAllowed($aField, $sOperator) ? " hidden disabled" : "";
        $sHtml .= "<option value=\"" . nxHtml($sOperator) . "\" data-needs-value=\"" . (!empty($aOperator["needs_value"]) ? "1" : "0") . "\"" . ($sSelected == $sOperator ? " selected" : "") . $sDisabled . ">" . nxHtml($aOperator["label"]) . "</option>";
    }
    return $sHtml;
}


function nxGetFullListComplexFilterPostPayload() {
    $aPayload = $_POST;
    if (isset($_POST["complex_filter_value_b64"]) && is_array($_POST["complex_filter_value_b64"])) {
        $aPayload["complex_filter_value"] = nxGetPostedValues("complex_filter_value");
    }
    return $aPayload;
}

function nxInterGetBirthdayInfo($sCommunicationServedAt) {
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

function nxInterFetchBirthdayServedRows($oPdo) {
    $aServedRows = array();
    $oStatement = $oPdo->query("SELECT subject_id, inter_served_at FROM ex_persons");
    while ($aRow = $oStatement->fetch(PDO::FETCH_ASSOC)) {
        $aServedRows[(int)$aRow["subject_id"]] = $aRow;
    }
    return $aServedRows;
}

function nxInterRenderSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aBirthdaySettings) {
    global $sCommunicationServedEmoji;

    $iSubjectId = (int)$aRow["subject_id"];
    $sBirthdayServedAction = $blShowActions ? "<a class=\"nx-item-action nx-birthday-served-action js-communication-served\" href=\"#\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" title=\"Mark communication served\" aria-label=\"Mark communication served\"><span class=\"nx-copy-action-box\">" . $sCommunicationServedEmoji . "</span></a>" : "";
    $sBirthdayInCell = nxHtmlValue($aRow["days_to_birthday"]) . ($sBirthdayServedAction != "" ? "&#8288;" . $sBirthdayServedAction : "");
    return nxRenderResponsiveSubjectRow($aRow, $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $aHiddenInactive, $aBirthdaySettings, array(
        "show_actions" => $blShowActions,
        "item_subject_id" => $iSubjectId,
        "before_name_cells" => array(nxRenderSubjectTableCell($sBirthdayInCell, "nx-birthday-in-column")),
        "name_actions" => nxBdRenderSubjectActions($aRow, $blShowActions),
        "birth_name_class" => "nx-column-step-two",
        "birth_date_class" => "",
        "death_date_class" => "nx-column-step-two",
        "death_date_style" => "overflow-wrap: normal; white-space: nowrap; word-break: normal;",
        "nickname_show_add_action" => true,
        "address_class" => "nx-column-step-one",
        "address_show_add_action" => true,
        "contact_show_add_action" => true,
        "group_show_add_action" => true,
        "note_show_add_action" => true
    ));
}

function nxInterGetUpdatedSubjectResponse($oPdo, $iSubjectId, $aBirthdaySettings, $blShowActions) {
    $aRows = nxFetchSubjectRows($oPdo, $iSubjectId);
    if (!$aRows) {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aContacts = nxFetchSubjectContacts($oPdo, $iSubjectId);
    $aNicknames = nxFetchSubjectNicknames($oPdo, $iSubjectId);
    $aAddresses = nxFetchSubjectAddresses($oPdo, $iSubjectId);
    $aGroups = nxFetchSubjectGroups($oPdo, $iSubjectId);
    $aNotes = nxFetchSubjectNotes($oPdo, $iSubjectId);
    $aHiddenInactive = nxGetHiddenInactiveSubjectItems($aContacts, $aNicknames, $aAddresses, $aNotes, $aBirthdaySettings);
    nxApplySubjectVisibilitySettings($aRows, $aContacts, $aNicknames, $aAddresses, $aNotes, $aBirthdaySettings);
    if (!$aRows || (string)$aRows[0]["subject_type"] != "person") {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aBirthdayServedRows = nxInterFetchBirthdayServedRows($oPdo);
    $sCommunicationServedAt = isset($aBirthdayServedRows[$iSubjectId]["inter_served_at"]) ? $aBirthdayServedRows[$iSubjectId]["inter_served_at"] : "";
    $aBirthdayInfo = nxInterGetBirthdayInfo($sCommunicationServedAt);
    if (!is_array($aBirthdayInfo)) {
        return array("success" => true, "subject_id" => $iSubjectId, "subject_deleted" => true);
    }
    $aRows[0]["days_to_birthday"] = $aBirthdayInfo["days_to_birthday"];
    $aRows[0]["birthday_date"] = $aBirthdayInfo["birthday_date"];
    return array(
        "success" => true,
        "subject_id" => $iSubjectId,
        "row_html" => nxInterRenderSubjectRow($aRows[0], $aContacts, $aNicknames, $aAddresses, $aGroups, $aNotes, $blShowActions, $aHiddenInactive, $aBirthdaySettings)
    );
}

function nxDiffEnsureDumpTable(&$aDump, $sTableName) {
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

function nxDiffDecodeSqlIdentifier($sIdentifier) {
    return str_replace("``", "`", $sIdentifier);
}

function nxDiffParseSqlIdentifierList($sSql) {
    $aIdentifiers = array();
    if (preg_match_all("/`((?:``|[^`])*)`/", $sSql, $aMatches)) {
        foreach ($aMatches[1] as $sIdentifier) {
            $aIdentifiers[] = nxDiffDecodeSqlIdentifier($sIdentifier);
        }
    }
    return $aIdentifiers;
}

function nxDiffNormalizeCreateSql($sSql) {
    $sSql = trim((string)$sSql);
    $sSql = preg_replace("/\s+AUTO_INCREMENT=\d+\b/i", "", $sSql);
    return preg_replace("/\r\n|\r|\n/", "\n", $sSql);
}

function nxDiffGetPrimaryKeyColumns($sCreateSql) {
    if (!preg_match("/PRIMARY\s+KEY\s+\(([^)]*)\)/is", $sCreateSql, $aMatches)) {
        return array();
    }
    return nxDiffParseSqlIdentifierList($aMatches[1]);
}

function nxDiffSplitSqlStatements($sSql) {
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

function nxDiffDecodeSqlString($sValue) {
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

function nxDiffDecodeSqlValue($sToken) {
    $sToken = trim((string)$sToken);
    if (strcasecmp($sToken, "NULL") === 0) {
        return null;
    }
    if (strlen($sToken) >= 2 && $sToken[0] == "'" && substr($sToken, -1) == "'") {
        return nxDiffDecodeSqlString(substr($sToken, 1, -1));
    }
    return $sToken;
}

function nxDiffParseSqlValues($sSql) {
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
            $aValues[] = nxDiffDecodeSqlValue($sToken);
            $sToken = "";
        } else {
            $sToken .= $sChar;
        }
    }
    if (trim($sToken) != "" || $sSql != "") {
        $aValues[] = nxDiffDecodeSqlValue($sToken);
    }
    return $aValues;
}

function nxDiffParseDatabaseSql($sSql) {
    $aDump = array(
        "tables" => array(),
        "table_order" => array()
    );
    foreach (nxDiffSplitSqlStatements($sSql) as $sStatement) {
        if (preg_match("/^CREATE\s+TABLE\s+`((?:``|[^`])+)`/is", $sStatement, $aMatches)) {
            $sTableName = nxDiffDecodeSqlIdentifier($aMatches[1]);
            if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
                continue;
            }
            nxDiffEnsureDumpTable($aDump, $sTableName);
            $sCreateSql = nxDiffNormalizeCreateSql($sStatement);
            $aDump["tables"][$sTableName]["create"] = $sCreateSql;
            $aDump["tables"][$sTableName]["primary_keys"] = nxDiffGetPrimaryKeyColumns($sCreateSql);
        } elseif (preg_match("/^INSERT\s+INTO\s+`((?:``|[^`])+)`\s*\((.*)\)\s+VALUES\s*\((.*)\)$/is", $sStatement, $aMatches)) {
            $sTableName = nxDiffDecodeSqlIdentifier($aMatches[1]);
            if (!preg_match("/^ex_[a-zA-Z0-9_]+$/", $sTableName)) {
                continue;
            }
            nxDiffEnsureDumpTable($aDump, $sTableName);
            $aColumns = nxDiffParseSqlIdentifierList($aMatches[2]);
            $aValues = nxDiffParseSqlValues($aMatches[3]);
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

function nxDiffFetchDatabaseTables($oPdo) {
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
            $bDependencyAdded = false;
            foreach ($aDependencies[$sTableName] as $sReferencedTableName => $bDependency) {
                if (!isset($aTableStates[$sReferencedTableName])) {
                    $aStack[] = $sReferencedTableName;
                    $bDependencyAdded = true;
                    break;
                }
            }
            if ($bDependencyAdded) {
                continue;
            }
            $aSortedTables[] = $aTableRows[$sTableName];
            $aTableStates[$sTableName] = "done";
            array_pop($aStack);
        }
    }
    return $aSortedTables;
}

function nxDiffGetCurrentDump($oPdo) {
    $aTables = nxDiffFetchDatabaseTables($oPdo);
    return nxDiffParseDatabaseSql(getDatabaseBackupSql($oPdo, $aTables));
}

function nxDiffGetTableRows($aDump, $sTableName) {
    return isset($aDump["tables"][$sTableName]) ? $aDump["tables"][$sTableName]["rows"] : array();
}

function nxDiffRowsByColumn($aDump, $sTableName, $sColumnName) {
    $aRows = array();
    foreach (nxDiffGetTableRows($aDump, $sTableName) as $aRow) {
        if (array_key_exists($sColumnName, $aRow) && $aRow[$sColumnName] !== null) {
            $aRows[(string)$aRow[$sColumnName]] = $aRow;
        }
    }
    return $aRows;
}

function nxDiffRowsGroupedByColumn($aDump, $sTableName, $sColumnName) {
    $aRows = array();
    foreach (nxDiffGetTableRows($aDump, $sTableName) as $aRow) {
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

function nxDiffRowValue($aRow, $sColumnName) {
    if (!is_array($aRow) || !array_key_exists($sColumnName, $aRow)) {
        return null;
    }
    return $aRow[$sColumnName];
}

function nxDiffTrimmedValue($aRow, $sColumnName) {
    $mValue = nxDiffRowValue($aRow, $sColumnName);
    return $mValue === null ? "" : trim((string)$mValue);
}

function nxDiffJoinNonEmptyValues($aValues, $sSeparator) {
    $aResult = array();
    foreach ($aValues as $mValue) {
        $sValue = trim((string)$mValue);
        if ($sValue != "") {
            $aResult[] = $sValue;
        }
    }
    return implode($sSeparator, $aResult);
}

function nxDiffCompareSubjectItems($aFirst, $aSecond) {
    $iFirstActive = (int)nxDiffRowValue($aFirst, "is_active");
    $iSecondActive = (int)nxDiffRowValue($aSecond, "is_active");
    if ($iFirstActive != $iSecondActive) {
        return $iSecondActive - $iFirstActive;
    }
    $iFirstPrimary = (int)nxDiffRowValue($aFirst, "is_primary");
    $iSecondPrimary = (int)nxDiffRowValue($aSecond, "is_primary");
    if ($iFirstPrimary != $iSecondPrimary) {
        return $iSecondPrimary - $iFirstPrimary;
    }
    return (int)nxDiffRowValue($aFirst, "id") - (int)nxDiffRowValue($aSecond, "id");
}

function nxDiffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts) {
    if (isset($aSubjectNames[$sSubjectId])) {
        $sName = nxDiffTrimmedValue($aSubjectNames[$sSubjectId], "name");
        if ($sName != "") {
            return $sName;
        }
    }
    if (isset($aNicknames[$sSubjectId])) {
        $aRows = $aNicknames[$sSubjectId];
        usort($aRows, "nxDiffCompareSubjectItems");
        foreach ($aRows as $aRow) {
            $sName = nxDiffTrimmedValue($aRow, "nickname");
            if ($sName != "") {
                return $sName;
            }
        }
    }
    if (isset($aSubjectContacts[$sSubjectId])) {
        $aRows = $aSubjectContacts[$sSubjectId];
        usort($aRows, "nxDiffCompareSubjectItems");
        foreach ($aRows as $aRow) {
            $sContactId = nxDiffTrimmedValue($aRow, "contact_id");
            if (isset($aContacts[$sContactId])) {
                $sName = nxDiffTrimmedValue($aContacts[$sContactId], "contact_value");
                if ($sName != "") {
                    return $sName;
                }
            }
        }
    }
    return "Unnamed subject";
}

function nxDiffBuildPersonDisplayName($aPerson, $sFallbackName) {
    $sBase = nxDiffJoinNonEmptyValues(array(
        nxDiffRowValue($aPerson, "title_before"),
        nxDiffRowValue($aPerson, "first_name"),
        nxDiffRowValue($aPerson, "middle_name"),
        nxDiffRowValue($aPerson, "last_name")
    ), " ");
    $sTitleAfter = nxDiffTrimmedValue($aPerson, "title_after");
    if ($sTitleAfter != "") {
        $sBase = $sBase != "" ? $sBase . ", " . $sTitleAfter : $sTitleAfter;
    }
    return $sBase != "" ? $sBase : $sFallbackName;
}

function nxDiffBuildPersonRows($aDump) {
    $aSubjects = nxDiffRowsByColumn($aDump, "ex_subjects", "id");
    $aPersons = nxDiffRowsByColumn($aDump, "ex_persons", "subject_id");
    $aSubjectNames = nxDiffRowsByColumn($aDump, "ex_subject_names", "subject_id");
    $aNicknames = nxDiffRowsGroupedByColumn($aDump, "ex_subject_nicknames", "subject_id");
    $aSubjectContacts = nxDiffRowsGroupedByColumn($aDump, "ex_subject_contacts", "subject_id");
    $aContacts = nxDiffRowsByColumn($aDump, "ex_contacts", "id");
    $aIds = array();
    foreach ($aSubjects as $sSubjectId => $aSubject) {
        if (nxDiffTrimmedValue($aSubject, "subject_type") == "person") {
            $aIds[$sSubjectId] = true;
        }
    }
    foreach ($aPersons as $sSubjectId => $aPerson) {
        $aIds[$sSubjectId] = true;
    }
    ksort($aIds, SORT_NUMERIC);
    $aRows = array();
    foreach ($aIds as $sSubjectId => $bUsed) {
        $aSubject = isset($aSubjects[$sSubjectId]) ? $aSubjects[$sSubjectId] : array();
        $aPerson = isset($aPersons[$sSubjectId]) ? $aPersons[$sSubjectId] : array();
        $sFallbackName = nxDiffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts);
        $aRows[$sSubjectId] = array(
            "subject_id" => $sSubjectId,
            "name" => nxDiffBuildPersonDisplayName($aPerson, $sFallbackName),
            "subject_type" => nxDiffRowValue($aSubject, "subject_type"),
            "is_active" => nxDiffRowValue($aSubject, "is_active"),
            "legacy_id" => nxDiffRowValue($aSubject, "legacy_id"),
            "person_row" => isset($aPersons[$sSubjectId]) ? "yes" : "no",
            "title_before" => nxDiffRowValue($aPerson, "title_before"),
            "first_name" => nxDiffRowValue($aPerson, "first_name"),
            "middle_name" => nxDiffRowValue($aPerson, "middle_name"),
            "last_name" => nxDiffRowValue($aPerson, "last_name"),
            "title_after" => nxDiffRowValue($aPerson, "title_after"),
            "birth_name" => nxDiffRowValue($aPerson, "birth_name"),
            "birth_number" => nxDiffRowValue($aPerson, "birth_number"),
            "birth_date" => nxDiffRowValue($aPerson, "birth_date"),
            "death_date" => nxDiffRowValue($aPerson, "death_date")
        );
    }
    return $aRows;
}

function nxDiffBuildSubjectRows($aDump) {
    $aSubjects = nxDiffRowsByColumn($aDump, "ex_subjects", "id");
    $aPersons = nxDiffRowsByColumn($aDump, "ex_persons", "subject_id");
    $aSubjectNames = nxDiffRowsByColumn($aDump, "ex_subject_names", "subject_id");
    $aNicknames = nxDiffRowsGroupedByColumn($aDump, "ex_subject_nicknames", "subject_id");
    $aSubjectContacts = nxDiffRowsGroupedByColumn($aDump, "ex_subject_contacts", "subject_id");
    $aContacts = nxDiffRowsByColumn($aDump, "ex_contacts", "id");
    ksort($aSubjects, SORT_NUMERIC);
    $aRows = array();
    foreach ($aSubjects as $sSubjectId => $aSubject) {
        $sFallbackName = nxDiffBuildSubjectFallbackName($sSubjectId, $aSubjectNames, $aNicknames, $aSubjectContacts, $aContacts);
        if (nxDiffTrimmedValue($aSubject, "subject_type") == "person" && isset($aPersons[$sSubjectId])) {
            $sName = nxDiffBuildPersonDisplayName($aPersons[$sSubjectId], $sFallbackName);
        } else {
            $sName = $sFallbackName;
        }
        $aRows[$sSubjectId] = array(
            "subject_id" => $sSubjectId,
            "name" => $sName,
            "subject_type" => nxDiffRowValue($aSubject, "subject_type"),
            "is_active" => nxDiffRowValue($aSubject, "is_active"),
            "legacy_id" => nxDiffRowValue($aSubject, "legacy_id")
        );
    }
    return $aRows;
}

function nxDiffGetFieldChanges($aBackupRow, $aCurrentRow, $aFields) {
    $aChanges = array();
    foreach ($aFields as $sField => $sLabel) {
        $mBackupValue = nxDiffRowValue($aBackupRow, $sField);
        $mCurrentValue = nxDiffRowValue($aCurrentRow, $sField);
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

function nxDiffCompareEntityRows($aBackupRows, $aCurrentRows, $aFields) {
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
        $aChanges = nxDiffGetFieldChanges($aBackupRow, $aCurrentRows[$sKey], $aFields);
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

function nxDiffNormalizeRowForHash($aRow) {
    ksort($aRow, SORT_STRING);
    return $aRow;
}

function nxDiffGetRowHash($aRow) {
    return sha1(json_encode(nxDiffNormalizeRowForHash($aRow)));
}

function nxDiffBuildRowKey($aRow, $aPrimaryKeys, $iIndex) {
    if (!$aPrimaryKeys) {
        return "row:" . $iIndex . ":" . nxDiffGetRowHash($aRow);
    }
    $aParts = array();
    foreach ($aPrimaryKeys as $sColumnName) {
        $aParts[$sColumnName] = nxDiffRowValue($aRow, $sColumnName);
    }
    return json_encode($aParts);
}

function nxDiffBuildTableRowMap($aDump, $sTableName) {
    $aRows = array();
    if (!isset($aDump["tables"][$sTableName])) {
        return $aRows;
    }
    $aPrimaryKeys = $aDump["tables"][$sTableName]["primary_keys"];
    foreach ($aDump["tables"][$sTableName]["rows"] as $iIndex => $aRow) {
        $sKey = nxDiffBuildRowKey($aRow, $aPrimaryKeys, $iIndex);
        $aRows[$sKey] = array(
            "row" => $aRow,
            "hash" => nxDiffGetRowHash($aRow)
        );
    }
    return $aRows;
}

function nxDiffCompareTableRows($aBackupDump, $aCurrentDump) {
    $aNames = array();
    foreach ($aBackupDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    foreach ($aCurrentDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    ksort($aNames, SORT_STRING);
    $aRows = array();
    foreach ($aNames as $sTableName => $bUsed) {
        $aBackupRows = isset($aBackupDump["tables"][$sTableName]) ? $aBackupDump["tables"][$sTableName]["rows"] : array();
        $aCurrentRows = isset($aCurrentDump["tables"][$sTableName]) ? $aCurrentDump["tables"][$sTableName]["rows"] : array();
        $aBackupMap = nxDiffBuildTableRowMap($aBackupDump, $sTableName);
        $aCurrentMap = nxDiffBuildTableRowMap($aCurrentDump, $sTableName);
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

function nxDiffCompareStructure($aBackupDump, $aCurrentDump) {
    $aNames = array();
    foreach ($aBackupDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    foreach ($aCurrentDump["tables"] as $sTableName => $aTable) {
        $aNames[$sTableName] = true;
    }
    ksort($aNames, SORT_STRING);
    $aRows = array();
    foreach ($aNames as $sTableName => $bUsed) {
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

function nxDiffUploadErrorMessage($iError) {
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

function nxDiffTextValue($mValue) {
    if ($mValue === null) {
        return "NULL";
    }
    $sValue = (string)$mValue;
    return $sValue != "" ? $sValue : "(empty)";
}

function nxDiffRenderChangeList($aChanges) {
    $aItems = array();
    foreach ($aChanges as $aChange) {
        $aItems[] = nxHtml($aChange["field"] . ": " . nxDiffTextValue($aChange["backup"]) . " -> " . nxDiffTextValue($aChange["current"]));
    }
    return implode("<br>", $aItems);
}

function nxDiffRenderEntityTable($aRows, $aColumns) {
    if (!$aRows) {
        echo "  <p><em>&mdash;</em></p>\n";
        return;
    }
    echo "  <table class=\"consistency-table\">\n"
        . "    <thead>\n"
        . "      <tr>\n";
    foreach ($aColumns as $sColumn => $sLabel) {
        echo "        <th>" . nxHtml($sLabel) . "</th>\n";
    }
    echo "      </tr>\n"
        . "    </thead>\n"
        . "    <tbody>\n";
    foreach ($aRows as $aRow) {
        echo "      <tr>\n";
        foreach ($aColumns as $sColumn => $sLabel) {
            echo "        <td>" . nxHtmlValue(nxDiffRowValue($aRow, $sColumn)) . "</td>\n";
        }
        echo "      </tr>\n";
    }
    echo "    </tbody>\n"
        . "  </table>\n";
}

function nxDiffRenderChangedEntityTable($aRows) {
    if (!$aRows) {
        echo "  <p><em>&mdash;</em></p>\n";
        return;
    }
    echo "  <table class=\"consistency-table\">\n"
        . "    <thead>\n"
        . "      <tr>\n"
        . "        <th>Subject ID</th>\n"
        . "        <th>Backup Name</th>\n"
        . "        <th>Current Name</th>\n"
        . "        <th>Changed Fields</th>\n"
        . "      </tr>\n"
        . "    </thead>\n"
        . "    <tbody>\n";
    foreach ($aRows as $aRow) {
        echo "      <tr>\n"
            . "        <td>" . nxHtmlValue(nxDiffRowValue($aRow["backup"], "subject_id")) . "</td>\n"
            . "        <td>" . nxHtmlValue(nxDiffRowValue($aRow["backup"], "name")) . "</td>\n"
            . "        <td>" . nxHtmlValue(nxDiffRowValue($aRow["current"], "name")) . "</td>\n"
            . "        <td>" . nxDiffRenderChangeList($aRow["changes"]) . "</td>\n"
            . "      </tr>\n";
    }
    echo "    </tbody>\n"
        . "  </table>\n";
}

function nxSchemaColumnTypeDisplay($sColumnType, $bShorten = true) {
    $sColumnType = (string)$sColumnType;
    if (preg_match("/^enum\\((.*)\\)$/i", $sColumnType, $aMatches)) {
        preg_match_all("/'((?:''|[^'])*)'/", $aMatches[1], $aEnumValues);
        $aDisplayValues = array();
        foreach ($aEnumValues[1] as $sEnumValue) {
            $aDisplayValues[] = "'" . $sEnumValue . "'";
        }
        if ($bShorten && count($aDisplayValues) > 24) {
            $aShortValues = array_slice($aDisplayValues, 0, 12);
            $aShortValues[] = "…";
            $aShortValues[] = $aDisplayValues[count($aDisplayValues) - 1];
            return "enum(" . implode(", ", $aShortValues) . ")";
        }
        return "enum(" . implode(", ", $aDisplayValues) . ")";
    }
    return $sColumnType;
}

function isPmdLikeUserAgent($sUserAgent) {
    return preg_match("/(?:Android|iPhone|iPad|iPod|Mobile|Tablet|Silk|Kindle|FxiOS)/i", $sUserAgent) == 1;
}

function isThrobberLockTarget($sUserAgent) {
    $blThrobberGeckoEngine = preg_match("/Gecko\/\d+/i", $sUserAgent) && preg_match("/Firefox\/\d+/i", $sUserAgent);
    $blThrobberPmdLike = preg_match("/(?:Android|iPhone|iPad|iPod|Mobile|Tablet|Silk|Kindle)/i", $sUserAgent);
    $blThrobberChromiumEngine = preg_match("/(?:Chrome|Chromium|CriOS|EdgA|SamsungBrowser|OPR|Opera)/i", $sUserAgent);
    return !$blThrobberGeckoEngine && $blThrobberPmdLike && $blThrobberChromiumEngine;
}
