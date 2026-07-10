<?php

function isAllowedIp($aAllowedIps) {
    return isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $aAllowedIps, true);
}

function isExTrustedClient($aAllowedIps) {
    $sTrustedUserAgent = isset($GLOBALS["sExTrustedUserAgent"]) ? (string)$GLOBALS["sExTrustedUserAgent"] : "";
    $sTrustedAcceptLanguage = isset($GLOBALS["sExTrustedAcceptLanguage"]) ? (string)$GLOBALS["sExTrustedAcceptLanguage"] : "";
    if (!isAllowedIp($aAllowedIps) || $sTrustedUserAgent === "" || $sTrustedAcceptLanguage === "") {
        return false;
    }
    if (!isset($_SERVER["HTTP_USER_AGENT"], $_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
        return false;
    }
    return hash_equals($sTrustedUserAgent, (string)$_SERVER["HTTP_USER_AGENT"])
        && hash_equals($sTrustedAcceptLanguage, (string)$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
}

function isExViewSessionAuthenticated() {
    return refreshExAuthSession();
}

function isExViewAllowed($aAllowedIps) {
    return isExTrustedClient($aAllowedIps) || isExViewSessionAuthenticated();
}

function isExFullAccessAllowed($aAllowedIps) {
    return isExTrustedClient($aAllowedIps) || isExPermissionAllowed("portal.full");
}

function getExLoginToken() {
    if (!isset($_SESSION["ex_login_token"]) || !is_string($_SESSION["ex_login_token"]) || $_SESSION["ex_login_token"] === "") {
        $_SESSION["ex_login_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["ex_login_token"];
}

function resetExLoginToken() {
    $_SESSION["ex_login_token"] = bin2hex(random_bytes(32));
    return $_SESSION["ex_login_token"];
}

function getExCsrfToken() {
    if (!isset($_SESSION["ex_csrf_token"]) || !is_string($_SESSION["ex_csrf_token"]) || $_SESSION["ex_csrf_token"] === "") {
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
    return $sToken !== "" && $sSessionToken !== "" && hash_equals($sSessionToken, $sToken);
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
    if (isset($aParts["query"]) && $aParts["query"] !== "") {
        parse_str($aParts["query"], $aQuery);
        unset($aQuery["logout"]);
        unset($aQuery["ex_csrf_token"]);
    }
    if (count($aQuery) > 0) {
        $sResult .= "?" . http_build_query($aQuery, "", "&");
    }
    if ($sResult === "") {
        $sResult = "/";
    }
    return $sResult;
}

function getExLogoutUrl() {
    $sUrl = getExCurrentUrlWithoutAuthAction();
    return $sUrl . (strpos($sUrl, "?") === false ? "?" : "&") . "logout=1&ex_csrf_token=" . rawurlencode(getExCsrfToken());
}

function isExAjaxRequest() {
    return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest";
}

function sendQuickTableFilterJsonAndExit($aData, $iStatusCode = 200) {
    nxSendJsonAndExit($aData, $iStatusCode);
}

function isQuickTableFilterAjaxRequest() {
    return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest";
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
    if (isset($_SESSION["quick_table_filters"][$sScriptName]) && is_array($_SESSION["quick_table_filters"][$sScriptName]) && count($_SESSION["quick_table_filters"][$sScriptName]) === 0) {
        unset($_SESSION["quick_table_filters"][$sScriptName]);
    }
}

function handleQuickTableFilterRequest() {
    if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["quick_table_filter_action"])) {
        return;
    }
    if (!isQuickTableFilterAjaxRequest()) {
        send403AndExit();
    }
    $sAction = (string)$_POST["quick_table_filter_action"];
    $sFilterId = isset($_POST["filter_id"]) ? (string)$_POST["filter_id"] : "table-filter";
    if ($sAction == "save") {
        $sValue = nxGetPostedValue("filter_value");
        setQuickTableFilterValue($sFilterId, $sValue);
        session_write_close();
        sendQuickTableFilterJsonAndExit(array("success" => true));
    } elseif ($sAction == "reset") {
        resetQuickTableFilterValue($sFilterId);
        session_write_close();
        sendQuickTableFilterJsonAndExit(array("success" => true));
    }
    sendQuickTableFilterJsonAndExit(array("success" => false, "message" => "Invalid quick filter action."), 400);
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
            if ($sRelativePath === "" || strpos($sRelativePath, "..") !== false || preg_match("#(^|/)\\.#", $sRelativePath) || preg_match("#[^A-Za-z0-9/_\\.\\-]#", $sRelativePath)) {
                continue;
            }
            $sName = trim((string)(isset($aRow["name"]) ? $aRow["name"] : ""));
            if ($sName === "") {
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

function nxRenderExMenu() {
    global $oPdo, $sBaseUrl;

    $aItems = nxGetExMenuItems($oPdo);
    $sCurrentPath = nxGetExMenuPathPrefix() . getQuickTableFilterScriptName();
    $sBase = isset($sBaseUrl) ? (string)$sBaseUrl : "";
    if (count($aItems) === 0) {
        return;
    }
    echo "    <span class=\"ex-menu\" data-ex-menu>\n";
    echo "      <button type=\"button\" class=\"ex-menu-button\" data-ex-menu-button aria-haspopup=\"true\" aria-expanded=\"false\" title=\"Menu\" aria-label=\"Menu\">&#9776;</button>\n";
    echo "      <span class=\"ex-menu-panel\" data-ex-menu-panel hidden>\n";
    foreach ($aItems as $aItem) {
        $sClass = "ex-menu-link";
        $sCurrent = "";
        $sIcon = trim((string)$aItem["icon"]);
        $sTitle = trim((string)$aItem["title"]);
        $sTarget = trim((string)$aItem["target"]);
        $sTitleAttribute = $sTitle !== "" ? " title=\"" . nxHtml($sTitle) . "\"" : "";
        $sTargetAttribute = $sTarget !== "" && preg_match("#^(_blank|_self|_parent|_top|[A-Za-z][A-Za-z0-9_\\-]*)$#", $sTarget) ? " target=\"" . nxHtml($sTarget) . "\"" : "";
        $sRelAttribute = $sTarget === "_blank" ? " rel=\"noopener noreferrer\"" : "";
        if ($aItem["path"] === $sCurrentPath) {
            $sClass .= " ex-menu-link-active";
            $sCurrent = " aria-current=\"page\"";
        }
        echo "        <a class=\"" . nxHtml($sClass) . "\" href=\"" . nxHtml($sBase . nxEncodeFsMenuPath($aItem["relative_path"])) . "\"" . $sTitleAttribute . $sTargetAttribute . $sRelAttribute . $sCurrent . "><span class=\"ex-menu-icon\" aria-hidden=\"true\">" . nxHtml($sIcon) . "</span><span class=\"ex-menu-text\">" . nxHtml($aItem["name"]) . "</span></a>\n";
    }
    echo "      </span>\n";
    echo "    </span>\n";
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
    } catch (Exception $oException) {}
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
            || (int)$aUser["is_active"] !== 1
            || (int)$aUser["subject_active"] !== 1
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
    return isExViewSessionAuthenticated()
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
    $sMessageHtml = $sMessage !== "" ? "    <p class=\"message-error ex-login-message\">" . htmlspecialchars($sMessage, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</p>\n" : "";
    echo "<!DOCTYPE html>\n"
        . "<html lang=\"en-US\" dir=\"ltr\">\n"
        . "<head>\n"
        . "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n"
        . "  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n"
        . "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n"
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
    if ($sToken === "" || $sSessionToken === "" || !hash_equals($sSessionToken, $sToken)) {
        resetExLoginToken();
        renderExLoginPageAndExit("Invalid sign-in request.");
    }
    $aUser = exFetchPortalLoginUser($oPdo, $sUserName);
    if ($aUser
        && (int)$aUser["is_active"] === 1
        && (int)$aUser["subject_active"] === 1
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
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "ex_logout") {
        requireExCsrfToken();
        clearExAuthSession();
        session_regenerate_id(true);
        resetExCsrfToken();
        sendSecurityHeaders();
        header("Location: " . getExCurrentUrlWithoutAuthAction(), true, 303);
        exit;
    }
    if (isset($_GET["logout"]) && isExViewSessionAuthenticated()) {
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
    if (isExViewSessionAuthenticated()) {
        return;
    }
    if (isset($_SESSION["ex_login_cancelled"]) && $_SESSION["ex_login_cancelled"] === true) {
        unset($_SESSION["ex_login_cancelled"]);
        send403AndExit();
    }
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "ex_cancel") {
        $sToken = isset($_POST["ex_login_token"]) ? (string)$_POST["ex_login_token"] : "";
        $sSessionToken = isset($_SESSION["ex_login_token"]) ? (string)$_SESSION["ex_login_token"] : "";
        if ($sToken === "" || $sSessionToken === "" || !hash_equals($sSessionToken, $sToken)) {
            resetExLoginToken();
            send403AndExit();
        }
        $_SESSION["ex_login_cancelled"] = true;
        sendSecurityHeaders();
        header("Location: " . getExCurrentUrlWithoutAuthAction(), true, 303);
        exit;
    }
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "ex_login") {
        handleExLoginPost();
    }
    if (isExAjaxRequest()) {
        nxSendJsonAndExit(array("success" => false, "message" => "Sign-in is required."), 403);
    }
    renderExLoginPageAndExit();
}

function getExPageTitleText($sTitle, $aAllowedIps) {
    $aStates = array();
    if (isExTrustedClient($aAllowedIps)) {
        $aStates[] = "Trusted";
    }
    if (isExViewSessionAuthenticated()) {
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
        if ($sPart === "") {
            continue;
        }
        if ($sPart[0] === "'") {
            $sHtml .= "<span class=\"sql-string\">" . htmlspecialchars($sPart, ENT_NOQUOTES | ENT_SUBSTITUTE, "UTF-8") . "</span>";
        } elseif ($sPart[0] === "`") {
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

function nxHtmlValue($mValue) {
    $sValue = trim((string)$mValue);
    return $sValue !== "" ? nxHtml($sValue) : "&#10134;";
}

function nxRenderCopyAction($mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue === "") {
        return "";
    }
    return "<a class=\"nx-copy-action\" href=\"#\" data-copy-value=\"" . nxHtml($sValue) . "\" title=\"Copy\" aria-label=\"Copy\"><span class=\"nx-copy-action-box\">&#128203;</span></a>";
}

function nxHtmlMultiline($mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue === "") {
        return "&#10134;";
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

    if (count($aRows) === 0) {
        $iDefaultContactTypeId = 1;
        foreach (nxGetDefaultContactTypeRows() as $aRow) {
            if (!$blActiveOnly || (int)$aRow["is_active"] === 1) {
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
        if ((int)$aType["id"] === $iContactTypeId) {
            return $aType;
        }
    }
    return null;
}

function nxContactTypeLabel($sType, $oPdo = null) {
    $sType = (string)$sType;
    foreach (nxFetchContactTypes($oPdo, false) as $aType) {
        if ((string)$aType["contact_type"] === $sType) {
            return (string)$aType["name"];
        }
    }
    if ($sType === "phone") {
        return "Landline";
    }
    if ($sType === "mobile") {
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
    return $sKey !== "" ? $sKey : "type";
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
    global $sDeleteEmoji, $sEditEmoji;

    $sMergeEmoji = "&#128260;";
    $sMoveUpEmoji = "&#9650;";
    $sMoveDownEmoji = "&#9660;";
    $blIsActive = (int)$aContactType["is_active"] === 1;

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
    if ($sDirection === "up") {
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
    if ($sText === "") {
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
        if ($sHost !== "youtube.com" && $sHost !== "www.youtube.com") {
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
        if ($sPath !== "") {
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
    if ($sHost === "t.me" || $sHost === "telegram.me" || $sHost === "telegram.dog") {
        return $sHost;
    }
    return false;
}

function nxTelegramInviteToken($sValue, $blRequireMarker = false) {
    $sText = rawurldecode((string)$sValue);
    $blMarked = false;
    if (substr($sText, 0, 1) === "+") {
        $sText = substr($sText, 1);
        $blMarked = true;
    } else if (substr($sText, 0, 1) === " ") {
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
    $aSegments = $sPath === "" ? array() : explode("/", $sPath);
    $sHandle = "";
    $sKind = "";
    $sToken = "";
    if ($sHost === false || count($aSegments) < 1 || count($aSegments) > 2) {
        return false;
    }
    if (count($aSegments) === 1) {
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
    if ($sKind === "joinchat") {
        $sToken = nxTelegramInviteToken($aSegments[1]);
        return $sToken !== false ? "https://" . $sHost . "/joinchat/" . rawurlencode($sToken) : false;
    }
    if ($sKind === "addstickers" || $sKind === "setlanguage") {
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
    if ($sText === "") {
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
    if (substr($sRawText, 0, 1) === " " || substr($sText, 0, 1) === "+" || preg_match("/^%20/i", $sText)) {
        $sToken = nxTelegramInviteToken(substr($sRawText, 0, 1) === " " ? $sRawText : $sText, true);
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
    if ($sText === "") {
        return "";
    }
    if (preg_match("/^[0-9]{5,9}$/", $sText)) {
        $sDigits = $sText;
    } else if (preg_match("/^[0-9]{1,3}(?:-[0-9]{3}){1,2}$/", $sText)) {
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
    return strpos((string)$sValue, "-") === false || trim((string)$sValue) === $sText ? $sText : false;
}

function nxNormalizeEmailContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText === "") {
        return "";
    }
    return filter_var($sText, FILTER_VALIDATE_EMAIL) !== false ? $sText : false;
}

function nxNormalizeSkypeContactValue($sValue) {
    $sText = trim((string)$sValue);
    if ($sText === "") {
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
    if ($sPattern === "") {
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
    $oXml = $sXml !== "" ? simplexml_load_string($sXml) : false;
    libxml_clear_errors();
    libxml_use_internal_errors($blPreviousLibxmlState);
    if (!$oXml || !isset($oXml->territories->territory)) {
        return $aMetadata;
    }

    foreach ($oXml->territories->territory as $oTerritory) {
        $sCountryCode = (string)$oTerritory["countryCode"];
        if ($sCountryCode === "") {
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
            "main" => (string)$oTerritory["mainCountryForCode"] === "true",
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
            if ((string)$aTerritory["national_prefix"] !== "" && strpos($sNationalNumber, (string)$aTerritory["national_prefix"]) === 0) {
                $aNationalNumbers[] = substr($sNationalNumber, strlen((string)$aTerritory["national_prefix"]));
            }
            foreach ($aNationalNumbers as $sCandidateNationalNumber) {
                if ((string)$aTerritory["leading_digits"] !== "" && !nxPhonePatternMatches((string)$aTerritory["leading_digits"], $sCandidateNationalNumber, false)) {
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
        if (count($aFallbackFormats) === 0 && count($aTerritory["formats"]) > 0) {
            $aFallbackFormats = $aTerritory["formats"];
        }
    }
    return $aFallbackFormats;
}

function nxApplyPhoneNumberFormat($sPattern, $sFormat, $sNationalNumber) {
    $aMatches = array();
    $sFormatted = (string)$sFormat;
    if ($sFormatted === "" || !nxPhonePatternMatches($sPattern, $sNationalNumber, true, $aMatches)) {
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
        if ($sFormatted !== "") {
            return "+" . (string)$sCountryCode . " " . $sFormatted;
        }
    }
    return "+" . (string)$sCountryCode . " " . (string)$sNationalNumber;
}

function nxAnalyzePhoneContactValue($sValue) {
    $sText = trim((string)$sValue);
    $sDigits = "";
    $aPhone = array();
    if ($sText === "") {
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
    return !empty($aPhone["valid"]) && (string)$aPhone["canonical"] !== "" ? "tel:" . str_replace(".", "", (string)$aPhone["canonical"]) : "";
}

function nxNormalizeContactInputForStorage($sContactType, $sContactValue) {
    $mKnownValue = null;

    if (nxIsPhoneContactType($sContactType)) {
        return nxNormalizePhoneContactValue($sContactValue);
    }
    if ((string)$sContactType === "youtube") {
        return nxNormalizeYouTubeContactValue($sContactValue, true);
    }
    if ((string)$sContactType === "telegram") {
        return nxNormalizeTelegramContactValue($sContactValue);
    }
    if ((string)$sContactType === "email") {
        return nxNormalizeEmailContactValue($sContactValue);
    }
    if ((string)$sContactType === "icq") {
        return nxNormalizeIcqContactValue($sContactValue);
    }
    if ((string)$sContactType === "skype") {
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

    if (nxIsPhoneContactType($sContactType)) {
        $mKnownValue = nxNormalizePhoneContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType === "youtube") {
        $mKnownValue = nxNormalizeYouTubeContactValue($sContactValue, true);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType === "telegram") {
        $mKnownValue = nxNormalizeTelegramContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType === "email") {
        $mKnownValue = nxNormalizeEmailContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType === "icq") {
        $mKnownValue = nxNormalizeIcqContactValue($sContactValue);
        return $mKnownValue !== false ? (string)$mKnownValue : (string)$sContactValue;
    }
    if ((string)$sContactType === "skype") {
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
    if (nxIsPhoneContactType($sContactType)) {
        return "Phone number must be a valid international number.";
    }
    if ((string)$sContactType === "youtube") {
        return "YouTube contact must be a YouTube link or handle.";
    }
    if ((string)$sContactType === "telegram") {
        return "Telegram contact must be a valid Telegram link, handle, invite link, sticker set or language link.";
    }
    if ((string)$sContactType === "email") {
        return "E-mail address is invalid.";
    }
    if ((string)$sContactType === "icq") {
        return "ICQ must have 5 to 9 digits, either without hyphens or grouped from the right.";
    }
    if ((string)$sContactType === "skype") {
        return "Skype name must start with a letter and have 6 to 32 valid characters, or use a valid live: name.";
    }
    if (nxNormalizeKnownContactValue($sContactType, "") !== null) {
        return "Contact value has invalid format for this contact type.";
    }
    return "Contact value is invalid.";
}

function nxContactValueIsInvalid($sType, $sValue) {
    $mKnownValue = null;

    if (trim((string)$sValue) === "") {
        return false;
    }
    if (nxIsPhoneContactType($sType)) {
        return nxNormalizePhoneContactValue($sValue) === false;
    }
    if ((string)$sType === "youtube") {
        return nxNormalizeYouTubeContactValue($sValue, true) === false;
    }
    if ((string)$sType === "telegram") {
        return nxNormalizeTelegramContactValue($sValue) === false;
    }
    if ((string)$sType === "email") {
        return nxNormalizeEmailContactValue($sValue) === false;
    }
    if ((string)$sType === "icq") {
        return nxNormalizeIcqContactValue($sValue) === false;
    }
    if ((string)$sType === "skype") {
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
    if ($sValue === "") {
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
    if ($sText === "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    } else if (!preg_match("#^[A-Za-z][A-Za-z0-9+.-]*://#", $sText)) {
        $sText = "https://" . $sText;
    }
    $aParts = parse_url($sText);
    if (!is_array($aParts) || empty($aParts["scheme"]) || empty($aParts["host"])) {
        return false;
    }
    $sScheme = strtolower((string)$aParts["scheme"]);
    $sHost = strtolower((string)$aParts["host"]);
    if ($sScheme !== "http" && $sScheme !== "https") {
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
    if ($sText === "") {
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
        if ($sText === $sAllowedHost || strpos($sText, $sAllowedHost . "/") === 0 || $sText === "www." . $sAllowedHost || strpos($sText, "www." . $sAllowedHost . "/") === 0) {
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
        if ($sPath === "") {
            return false;
        }
        $aSegments = explode("/", $sPath);
        $sPrefix = isset($aRule["prefix"]) ? (string)$aRule["prefix"] : "";
        if ($sPrefix === "~") {
            $sHandle = preg_replace("/^~/", "", rawurldecode($aSegments[0]));
        } else if ($sPrefix === "@") {
            $sHandle = preg_replace("/^@/", "", rawurldecode($aSegments[0]));
        } else if ($sPrefix !== "") {
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
    if ($sText === "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?linkedin\\.com(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        if ($sHost !== "linkedin.com") {
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
    if ($sText === "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?stackoverflow\\.com(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost !== "stackoverflow.com" || !preg_match("#^users/([0-9]+)(?:/.*)?$#i", $sPath, $aMatches)) {
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
    if ($sText === "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?steamcommunity\\.com(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost !== "steamcommunity.com" || !preg_match("#^(id|profiles)/([^/]+)$#i", $sPath, $aMatches)) {
            return false;
        }
        $sKind = strtolower($aMatches[1]);
        $sValuePart = rawurldecode($aMatches[2]);
    } else {
        $sKind = preg_match("/^[0-9]{17}$/", $sText) ? "profiles" : "id";
        $sValuePart = $sText;
    }
    if ($sKind === "profiles" && !preg_match("/^[0-9]{17}$/", $sValuePart)) {
        return false;
    }
    if ($sKind === "id" && !preg_match("/^[A-Za-z0-9_-]{2,64}$/", $sValuePart)) {
        return false;
    }
    return "https://steamcommunity.com/" . $sKind . "/" . rawurlencode($sValuePart);
}

function nxNormalizeGoodreadsContactValue($sValue) {
    $sText = trim((string)$sValue);
    $aParts = array();
    $sHost = "";
    $sPath = "";
    if ($sText === "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?goodreads\\.com(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost !== "goodreads.com" || !preg_match("#^user/show/([0-9]+)(?:[.-].*)?$#i", $sPath, $aMatches)) {
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
    if ($sText === "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText)) {
        $aParts = parse_url($sText);
        $sHost = isset($aParts["host"]) ? strtolower((string)$aParts["host"]) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sPathPrefix === "@" && preg_match("#^@([^/]+)$#", $sPath, $aMatches)) {
            $sUser = rawurldecode($aMatches[1]);
        } else if ($sPathPrefix !== "@" && preg_match("#^" . preg_quote($sPathPrefix, "#") . "/([^/]+)$#i", $sPath, $aMatches)) {
            $sUser = rawurldecode($aMatches[1]);
        } else {
            return false;
        }
        $sDomain = $sHost;
    } else if (preg_match("/^@?([A-Za-z0-9_][A-Za-z0-9_.-]{0,29})@([A-Za-z0-9.-]+\\.[A-Za-z]{2,})$/", $sText, $aMatches)) {
        $sUser = $aMatches[1];
        $sDomain = strtolower($aMatches[2]);
    } else {
        return false;
    }
    if (!preg_match("/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,29}$/", $sUser) || !preg_match("/^[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$/", $sDomain)) {
        return false;
    }
    return "https://" . $sDomain . "/" . ($sPathPrefix === "@" ? "@" : $sPathPrefix . "/") . rawurlencode($sUser);
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
    if ($sText === "") {
        return "";
    }
    if (preg_match("#^//#", $sText)) {
        $sText = "https:" . $sText;
    }
    if (preg_match("#^https?://#i", $sText) || preg_match("#^(?:www\\.)?bsky\\.app(?:[/:?\\#].*)?$#i", $sText)) {
        $aParts = parse_url(preg_match("#^https?://#i", $sText) ? $sText : "https://" . $sText);
        $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
        $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
        if ($sHost !== "bsky.app" || !preg_match("#^profile/([^/]+)$#i", $sPath, $aMatches)) {
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
    if ($sText === "") {
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
    if ($sText === "") {
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
    if ($sText === "") {
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
    if ($sText === "") {
        return "";
    }
    if ((string)$sContactType === "whatsapp") {
        if (preg_match("#^//#", $sText)) {
            $sText = "https:" . $sText;
        }
        if (preg_match("#^https?://#i", $sText)) {
            $aParts = parse_url($sText);
            $sHost = isset($aParts["host"]) ? strtolower(preg_replace("/^www\\./", "", (string)$aParts["host"])) : "";
            $sPath = isset($aParts["path"]) ? trim((string)$aParts["path"], "/") : "";
            if ($sHost === "wa.me" && preg_match("/^[0-9]+$/", $sPath)) {
                $sText = "+" . $sPath;
            } else if (($sHost === "api.whatsapp.com" || $sHost === "whatsapp.com") && isset($aParts["query"]) && preg_match("/(?:^|&)phone=([0-9]+)/", (string)$aParts["query"], $aMatches)) {
                $sText = "+" . $aMatches[1];
            }
        }
    }
    $sDigits = nxNormalizePhoneContactValue($sText);
    return $sDigits !== false ? $sDigits : false;
}

function nxNormalizeKnownContactValue($sContactType, $sContactValue) {
    $mProfileValue = nxNormalizeProfileContactValue($sContactType, $sContactValue);
    if (!nxIsOriginalContactType($sContactType)) {
        return null;
    }
    if ((string)$sContactType === "telegram") {
        return nxNormalizeTelegramContactValue($sContactValue);
    }
    if ($mProfileValue !== null) {
        return $mProfileValue;
    }
    if ((string)$sContactType === "web") {
        return nxNormalizeWebContactValue($sContactValue);
    }
    if ((string)$sContactType === "jabber") {
        return nxNormalizeJabberContactValue($sContactValue);
    }
    if ((string)$sContactType === "matrix") {
        return nxNormalizeMatrixContactValue($sContactValue);
    }
    if ((string)$sContactType === "mastodon") {
        return nxNormalizeFederatedContactValue($sContactValue, "@");
    }
    if ((string)$sContactType === "lemmy") {
        return nxNormalizeFederatedContactValue($sContactValue, "u");
    }
    if ((string)$sContactType === "bluesky") {
        return nxNormalizeBlueskyContactValue($sContactValue);
    }
    if ((string)$sContactType === "linkedin") {
        return nxNormalizeLinkedInContactValue($sContactValue);
    }
    if ((string)$sContactType === "stackoverflow") {
        return nxNormalizeStackOverflowContactValue($sContactValue);
    }
    if ((string)$sContactType === "steam") {
        return nxNormalizeSteamContactValue($sContactValue);
    }
    if ((string)$sContactType === "goodreads") {
        return nxNormalizeGoodreadsContactValue($sContactValue);
    }
    if ((string)$sContactType === "orcid") {
        return nxNormalizeOrcidContactValue($sContactValue);
    }
    if ((string)$sContactType === "whatsapp" || (string)$sContactType === "viber") {
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
    return isset($aTypes[(string)$sType]);
}

function nxContactDisplayValue($sType, $sValue) {
    $sCanonicalValue = nxContactCanonicalValue($sType, $sValue);

    if (nxIsPhoneContactType($sType) || (string)$sType === "whatsapp" || (string)$sType === "viber") {
        return nxPhoneContactDisplayValue($sCanonicalValue);
    }
    return $sCanonicalValue;
}

function nxContactHref($sType, $sValue, $blAllowExternalLinks = false) {
    $sText = trim((string)$sValue);
    $mKnownValue = nxNormalizeKnownContactValue($sType, $sValue);
    if (nxIsPhoneContactType($sType)) {
        return nxPhoneContactHref($sValue);
    }
    if ($sType === "email") {
        $sText = nxNormalizeEmailContactValue($sValue);
        return $sText !== false && $sText !== "" ? "mailto:" . $sText : "";
    }
    if ($sType === "jabber") {
        $sText = nxNormalizeJabberContactValue($sValue);
        return $sText !== false && $sText !== "" ? "xmpp:" . $sText : "";
    }
    if ($sType === "matrix") {
        $sText = nxNormalizeMatrixContactValue($sValue);
        return $sText !== false && $sText !== "" ? "https://matrix.to/#/" . rawurlencode($sText) : "";
    }
    if ($sType === "whatsapp") {
        $sText = nxNormalizeMessagingPhoneContactValue($sValue, $sType);
        return $sText !== false && $sText !== "" ? "https://wa.me/" . preg_replace("/\\D/", "", $sText) : "";
    }
    if ($sType === "viber") {
        $sText = nxNormalizeMessagingPhoneContactValue($sValue, $sType);
        return $sText !== false && $sText !== "" ? "viber://chat?number=%2B" . preg_replace("/\\D/", "", $sText) : "";
    }
    if ($blAllowExternalLinks && $mKnownValue !== null && $mKnownValue !== false && preg_match("#^https?://#i", (string)$mKnownValue)) {
        return (string)$mKnownValue;
    }
    if ($blAllowExternalLinks && $sType === "web") {
        $sText = nxNormalizeWebContactValue($sValue);
        if ($sText === false || $sText === "") {
            return "";
        }
        return $sText;
    }
    if ($blAllowExternalLinks && $sType === "telegram") {
        $sText = nxNormalizeTelegramContactValue($sValue);
        return $sText !== false ? $sText : "";
    }
    if ($blAllowExternalLinks && $sType === "youtube") {
        return nxYouTubeContactHref($sValue);
    }
    return "";
}

function nxContactLinkEmoji($sType) {
    if ($sType === "email") {
        return "&#128231;";
    }
    if ($sType === "landline") {
        return "&#128222;";
    }
    if ($sType === "cell") {
        return "&#128241;";
    }
    if ($sType === "fax") {
        return "&#128224;";
    }
    if ($sType === "pager") {
        return "&#128223;";
    }
    if ($sType === "web") {
        return "&#127760;";
    }
    if ($sType === "telegram") {
        return "&#9992;&#65039;";
    }
    if ($sType === "whatsapp") {
        return "&#128172;";
    }
    if ($sType === "viber") {
        return "&#128172;";
    }
    if ($sType === "jabber" || $sType === "matrix") {
        return "&#128172;";
    }
    if ($sType === "youtube") {
        return "&#9654;&#65039;";
    }
    if (nxContactTypeHasKnownLink($sType)) {
        return "&#128279;";
    }
    return "";
}

function nxContactLinkTitle($sType) {
    if ($sType === "email") {
        return "Send e-mail";
    }
    if ($sType === "landline") {
        return "Call landline";
    }
    if ($sType === "cell") {
        return "Call cell phone";
    }
    if ($sType === "fax") {
        return "Call fax";
    }
    if ($sType === "pager") {
        return "Call pager";
    }
    if ($sType === "web") {
        return "Open web";
    }
    if ($sType === "telegram") {
        return "Open Telegram";
    }
    if ($sType === "whatsapp") {
        return "Open WhatsApp";
    }
    if ($sType === "viber") {
        return "Open Viber";
    }
    if ($sType === "jabber") {
        return "Open Jabber";
    }
    if ($sType === "matrix") {
        return "Open Matrix";
    }
    if ($sType === "youtube") {
        return "Open YouTube";
    }
    if (nxContactTypeHasKnownLink($sType)) {
        return "Open link";
    }
    return "";
}

function nxRenderContactValue($sType, $sValue, $blShowCopy = false, $blAllowExternalLinks = false) {
    $sDisplayValue = nxContactDisplayValue($sType, $sValue);
    $sHref = nxContactHref($sType, $sValue, $blAllowExternalLinks);
    $sClass = "nx-contact-value" . (nxContactValueIsInvalid($sType, $sValue) ? " nx-invalid-contact-value" : "");
    $sHtml = "<span class=\"" . nxHtml($sClass) . "\">" . nxHtml($sDisplayValue) . "</span>";
    $blHasIcon = false;
    if ($blShowCopy && (string)$sDisplayValue !== "") {
        $sHtml .= "<a class=\"nx-contact-copy\" href=\"#\" title=\"Copy\" aria-label=\"Copy\"><span class=\"nx-copy-action-box\">&#128203;</span></a>";
        $blHasIcon = true;
    }
    if ($sHref !== "") {
        $sTarget = $blAllowExternalLinks && preg_match("#^https?://#i", $sHref) ? " target=\"_blank\" rel=\"noopener noreferrer\"" : "";
        return $sHtml . ($blHasIcon ? "" : " ") . "<a class=\"nx-contact-link\" href=\"" . nxHtml($sHref) . "\"" . $sTarget . " title=\"" . nxHtml(nxContactLinkTitle($sType)) . "\" aria-label=\"" . nxHtml(nxContactLinkTitle($sType)) . "\">" . nxContactLinkEmoji($sType) . "</a>";
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
    if ($sPattern === "") {
        return true;
    }
    return @preg_match("~^(?:" . str_replace("~", "\\~", $sPattern) . ")$~i", (string)$sPostalCode) === 1;
}

function nxPostalCodeAlnum($sPostalCode) {
    return preg_replace("/[^A-Z0-9]/", "", strtoupper((string)$sPostalCode));
}

function nxAddressCountryCode($sCountry) {
    $sCountry = strtoupper(trim((string)$sCountry));
    return $sCountry === "CS" ? "CZ" : $sCountry;
}

function nxPostalCodeFormatByExample($sPostalCode, $sExamples) {
    $sAlnum = nxPostalCodeAlnum($sPostalCode);
    $aExamples = explode(",", (string)$sExamples);
    $sExample = "";
    $sFormatted = "";
    $iIndex = 0;
    if ($sAlnum === "") {
        return "";
    }
    foreach ($aExamples as $sExampleCandidate) {
        if (strlen(nxPostalCodeAlnum($sExampleCandidate)) === strlen($sAlnum)) {
            $sExample = trim((string)$sExampleCandidate);
            break;
        }
    }
    if ($sExample === "") {
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

    if ($sText === "") {
        return array("valid" => true, "value" => "");
    }
    if ($sCountry === "CZ" || $sCountry === "SK") {
        $sDigits = preg_replace("/\\D/", "", $sText);
        if (strlen($sDigits) === 5) {
            $sText = substr($sDigits, 0, 3) . " " . substr($sDigits, 3, 2);
        }
    }
    if (!preg_match("/^[A-Z0-9\\s\\-]+$/", $sText)) {
        return array("valid" => false, "value" => $sText);
    }
    if ($sPattern === "") {
        return array("valid" => true, "value" => preg_replace("/\\s+/", " ", $sText));
    }

    $aCandidates[] = preg_replace("/\\s+/", " ", $sText);
    $aCandidates[] = nxPostalCodeAlnum($sText);
    $aCandidates[] = nxPostalCodeFormatByExample($sText, $sExamples);
    foreach ($aCandidates as $sCandidate) {
        $sCandidate = trim((string)$sCandidate);
        if ($sCandidate !== "" && nxPostalCodePatternMatches($sPattern, $sCandidate)) {
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
        if (count($aRawValues) > 0 && count($_POST[$sEncodedName]) !== count($aRawValues)) {
            return $aRawValues;
        }
        foreach ($_POST[$sEncodedName] as $mValue) {
            $aValues[] = nxDecodePostedBase64Value($mValue);
        }
        return $aValues;
    }
    return $aRawValues;
}

function nxRenderAddSubjectItemAction($sClass, $sTitle, $iSubjectId, $sPrefix = "") {
    global $sAddEmoji;

    if (!isset($sAddEmoji)) {
        $sAddEmoji = "&#10133;";
    }
    if ((int)$iSubjectId < 1) {
        return "&#10134;";
    }
    return "<div class=\"nx-add-item-row\">" . $sPrefix . "<a href=\"#\" class=\"nx-item-action nx-add-item-action " . nxHtml($sClass) . "\" data-subject-id=\"" . nxHtml($iSubjectId) . "\" title=\"" . nxHtml($sTitle) . "\" aria-label=\"" . nxHtml($sTitle) . "\">" . $sAddEmoji . "</a></div>";
}

function nxRenderHiddenInactiveIndicator() {
    global $sHiddenInactiveEmoji;

    if (!isset($sHiddenInactiveEmoji)) {
        $sHiddenInactiveEmoji = "🗃️";
    }
    return "<span class=\"nx-hidden-inactive-indicator\" title=\"Hidden inactive content\" aria-label=\"Hidden inactive content\">" . $sHiddenInactiveEmoji . "</span>";
}

function nxRenderEmptySubjectItemCell($blShowActions, $sClass, $sTitle, $iSubjectId, $blHasHiddenInactive, $blShowAddAction = true) {
    $sHiddenInactive = $blHasHiddenInactive ? nxRenderHiddenInactiveIndicator() : "";
    if ($blShowActions && $blShowAddAction) {
        return nxRenderAddSubjectItemAction($sClass, $sTitle, $iSubjectId, $sHiddenInactive);
    }
    return $sHiddenInactive !== "" ? $sHiddenInactive : "&#10134;";
}

function nxRenderContactList($aContacts, $blShowActions = true, $iSubjectId = 0, $blShowCopy = true, $blAllowExternalLinks = true, $blHasHiddenInactive = false, $blShowAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji;

    if (count($aContacts) === 0) {
        return nxRenderEmptySubjectItemCell($blShowActions, "js-add-subject-contact", "New contact", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"nx-contact-list\">";
    foreach ($aContacts as $aContact) {
        $sNote = trim((string)$aContact["note"]);
        $blIsPrimary = (int)$aContact["is_primary"] === 1;
        $blIsActive = (int)$aContact["is_active"] === 1;
        $sContactType = isset($aContact["contact_type"]) ? (string)$aContact["contact_type"] : "";
        $sContactTypeName = isset($aContact["contact_type_name"]) && trim((string)$aContact["contact_type_name"]) !== "" ? (string)$aContact["contact_type_name"] : nxContactTypeLabel($sContactType);
        $sContactValue = nxContactDisplayValue($sContactType, $aContact["contact_value"]);
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
            . " data-contact-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"nx-contact-type\">" . nxHtml($sContactTypeName) . "</span>: "
            . nxRenderContactValue($sContactType, $aContact["contact_value"], $blShowCopy, $blAllowExternalLinks)
            . "<span class=\"nx-contact-note\">" . ($sNote !== "" ? " (" . nxHtml($sNote) . ")" : "") . "</span>"
            . "<span class=\"nx-contact-flags\">"
            . "<span class=\"nx-contact-primary\" title=\"Primary\">" . ($blIsPrimary ? "&#11088;" : "") . "</span>"
            . "<span class=\"nx-contact-inactive-label\" title=\"Inactive\">" . ($blIsActive ? "" : "&#9940;") . "</span>"
            . "</span>"
            . $sActions
            . "</div>";
    }
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-contact", "New contact", $iSubjectId);
    }
    return $sHtml . "</div>";
}

function nxRenderNicknameList($aNicknames, $blShowActions = true, $iSubjectId = 0, $blHasHiddenInactive = false, $blShowAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji;

    if (count($aNicknames) === 0) {
        return nxRenderEmptySubjectItemCell($blShowActions, "js-add-subject-nickname", "New nickname", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"nx-subject-item-list\">";
    foreach ($aNicknames as $aNickname) {
        $sContext = trim((string)$aNickname["context"]);
        $sNote = trim((string)$aNickname["note"]);
        $sCopyText = $aNickname["nickname"] . ($sContext !== "" ? " [" . $sContext . "]" : "") . ($sNote !== "" ? " (" . $sNote . ")" : "");
        $blIsPrimary = (int)$aNickname["is_primary"] === 1;
        $blIsActive = (int)$aNickname["is_active"] === 1;
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
            . "<span class=\"nx-subject-item-value\">" . nxHtml($aNickname["nickname"]) . "</span>"
            . "<span class=\"nx-subject-item-context\">" . ($sContext !== "" ? " [" . nxHtml($sContext) . "]" : "") . "</span>"
            . "<span class=\"nx-subject-item-note\">" . ($sNote !== "" ? " (" . nxHtml($sNote) . ")" : "") . "</span>"
            . nxRenderCopyAction($sCopyText)
            . "<span class=\"nx-subject-item-flags\"><span title=\"Primary\">" . ($blIsPrimary ? "&#11088;" : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : "&#9940;") . "</span></span>"
            . $sActions
            . "</div>";
    }
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-nickname", "New nickname", $iSubjectId);
    }
    return $sHtml . "</div>";
}

function nxAppendAddressCopyLine(&$aLines, $mValue) {
    $sValue = trim((string)$mValue);
    if ($sValue !== "") {
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
    if (count($aLines) === 0) {
        $aLines[] = "";
    }
    foreach ($aValueLines as $sValueLine) {
        $sValueLine = trim((string)$sValueLine);
        if ($iIndex === 0) {
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
    $sHouse = trim($sHouseNumber . ($sHouseNumber !== "" && $sOrientation !== "" ? "/" : "") . $sOrientation);
    if ($sEvidenceNumber !== "") {
        $sHouse = trim($sHouse . ($sHouse !== "" ? ", " : "") . "ev. " . $sEvidenceNumber);
    }
    return $sCountryCode === "US"
        ? trim($sHouse . ($sHouse !== "" && $sStreetName !== "" ? " " : "") . $sStreetName)
        : trim($sStreetName . ($sStreetName !== "" && $sHouse !== "" ? " " : "") . $sHouse);
}

function nxAddressCityLine($aAddress) {
    $sCity = trim((string)$aAddress["city"]);
    $sCityPart = trim((string)$aAddress["city_part"]);
    return trim($sCity . ($sCity !== "" && $sCityPart !== "" ? "-" : "") . $sCityPart);
}

function nxAddressOrganizationLine($aAddress) {
    $aLines = array();
    nxAppendAddressCopyLine($aLines, $aAddress["organization_name"]);
    nxAppendAddressCopyLine($aLines, $aAddress["department_name"]);
    return implode("\n", $aLines);
}

function nxAddressAddressLine($aAddress, $sCountryCode) {
    $aLines = array();
    nxAppendAddressCopyLine($aLines, trim((string)$aAddress["care_of"]) !== "" ? "c/o " . trim((string)$aAddress["care_of"]) : "");
    nxAppendAddressCopyLine($aLines, nxAddressStreetLine($aAddress, $sCountryCode));
    nxAppendAddressCopyLine($aLines, $aAddress["address_line2"]);
    return implode("\n", $aLines);
}

function nxAddressFormatTemplate($sCountryCode) {
    $aMetadata = nxAddressMetadata($sCountryCode);
    $sFormat = isset($aMetadata["fmt"]) ? trim((string)$aMetadata["fmt"]) : "";
    return $sFormat !== "" ? $sFormat : "%N%n%O%n%A%n%Z %C";
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
        if ($sChar === "%" && $iIndex + 1 < strlen($sFormat)) {
            $iIndex++;
            $sToken = substr($sFormat, $iIndex, 1);
            if ($sToken === "n") {
                $aLines[] = "";
            } else if (isset($aFields[$sToken])) {
                nxAppendAddressTemplateValue($aLines, $aFields[$sToken]);
            }
        } else {
            $aLines[count($aLines) - 1] .= $sChar;
        }
    }
    $aCleanLines = array();
    foreach ($aLines as $sLine) {
        $sLine = nxCleanAddressLine($sLine);
        if ($sLine !== "") {
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

function nxRenderAddressCopyText($aAddress, $sSubjectName = "") {
    $aLines = nxBuildAddressLines($aAddress, $sSubjectName, null, true);
    nxAppendAddressCopyLine($aLines, $aAddress["note"]);
    return implode("\n", $aLines);
}

function nxRenderAddressList($aAddresses, $blShowActions = true, $iSubjectId = 0, $sSubjectName = "", $blHasHiddenInactive = false, $aAddressDisplaySettings = null, $blShowAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji;

    if (count($aAddresses) === 0) {
        return nxRenderEmptySubjectItemCell($blShowActions, "js-add-subject-address", "New address", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"nx-subject-item-list\">";
    foreach ($aAddresses as $aAddress) {
        $sText = nxRenderAddressText($aAddress, $aAddressDisplaySettings);
        $sNote = trim((string)$aAddress["note"]);
        $sCopyText = nxRenderAddressCopyText($aAddress, $sSubjectName);
        $blIsPrimary = (int)$aAddress["is_primary"] === 1;
        $blIsActive = (int)$aAddress["is_active"] === 1;
        $sValueClass = (string)$aAddress["address_type"] === "main" ? " nx-subject-address-main-value" : "";
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
            . "<span class=\"nx-subject-item-value" . $sValueClass . "\">" . ($sText !== "" ? nxHtml($sText) : "&#10134;") . "</span>"
            . "<span class=\"nx-subject-item-note\">" . ($sNote !== "" ? " (" . nxHtml($sNote) . ")" : "") . "</span>"
            . nxRenderCopyAction($sCopyText)
            . "<span class=\"nx-subject-item-flags\"><span title=\"Primary\">" . ($blIsPrimary ? "&#11088;" : "") . "</span><span title=\"Inactive\">" . ($blIsActive ? "" : "&#9940;") . "</span></span>"
            . $sActions
            . "</div>";
    }
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-address", "New address", $iSubjectId);
    }
    return $sHtml . "</div>";
}

function nxRenderGroupList($aGroups, $blShowActions = true, $iSubjectId = 0, $blShowAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji;

    if (count($aGroups) === 0) {
        return $blShowActions && $blShowAddAction ? nxRenderAddSubjectItemAction("js-add-subject-group", "Assign group", $iSubjectId) : "&#10134;";
    }
    $sHtml = "<div class=\"nx-subject-item-list\">";
    foreach ($aGroups as $aGroup) {
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
            . " data-group-name=\"" . nxHtml($aGroup["name"]) . "\">"
            . "<span class=\"nx-subject-item-value\">" . nxHtml($aGroup["name"]) . "</span>"
            . nxRenderCopyAction($aGroup["name"])
            . $sActions
            . "</div>";
    }
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-group", "Assign group", $iSubjectId);
    }
    return $sHtml . "</div>";
}

function nxRenderNoteList($aNotes, $blShowActions = true, $iSubjectId = 0, $blHasHiddenInactive = false, $blShowAddAction = true) {
    global $sEditEmoji, $sDeleteEmoji;

    if (count($aNotes) === 0) {
        return nxRenderEmptySubjectItemCell($blShowActions, "js-add-subject-note", "New note", $iSubjectId, $blHasHiddenInactive, $blShowAddAction);
    }
    $sHtml = "<div class=\"nx-subject-item-list\">";
    foreach ($aNotes as $aNote) {
        $blIsActive = (int)$aNote["is_active"] === 1;
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
            . " data-active=\"" . ($blIsActive ? "1" : "0") . "\">"
            . "<span class=\"nx-subject-item-value\">" . nxHtmlMultiline($aNote["note_text"]) . "</span>"
            . nxRenderCopyAction($aNote["note_text"])
            . "<span class=\"nx-subject-item-flags\"><span title=\"Inactive\">" . ($blIsActive ? "" : "&#9940;") . "</span></span>"
            . "<span class=\"nx-subject-note-source\">" . nxHtml($aNote["note_text"]) . "</span>"
            . $sActions
            . "</div>";
    }
    if ($blShowActions && $blShowAddAction) {
        $sHtml .= nxRenderAddSubjectItemAction("js-add-subject-note", "New note", $iSubjectId);
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

    if ($sCountry === "") {
        return "";
    }
    if (preg_match("/^[A-Z]{2}$/", $sCountryUpper) && in_array($sCountryUpper, $aCountryCodes, true)) {
        return $sCountryUpper;
    }
    if ($sCountryLower === "czech republic") {
        return "CZ";
    }
    foreach ($aCountryNames as $sCode => $sName) {
        $sNameLower = function_exists("mb_strtolower") ? mb_strtolower((string)$sName, "UTF-8") : strtolower((string)$sName);
        if ($sCountryLower === $sNameLower) {
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
    if ($sCountry === "") {
        return "";
    }
    if ($sCountry === "CS" && is_array($aSettings) && !empty($aSettings["show_czechia_country_in_czech"])) {
        return "Československo";
    }
    if ($sCountry === "CZ" && is_array($aSettings)) {
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
    return $sValue !== "" ? $sValue : null;
}

function nxPayloadValue($aPayload, $sName) {
    return isset($aPayload[$sName]) ? trim((string)$aPayload[$sName]) : "";
}

function nxPayloadFlag($aPayload, $sName) {
    return isset($aPayload[$sName]) && ((string)$aPayload[$sName] === "1" || $aPayload[$sName] === 1 || $aPayload[$sName] === true) ? 1 : 0;
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
            $aSettings[$sCountrySettingName] = (int)$_SESSION["ex_country_settings"][$sCountrySettingName] === 1 ? 1 : 0;
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
        $aCountrySettings[$sCountrySettingName] = isset($aPayload[$sCountrySettingName]) && (string)$aPayload[$sCountrySettingName] === "1" ? 1 : 0;
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
    if ($sValue === "") {
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
    if ($sNormalized === "") {
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

    if ($iLength === 9 && $sEnding === "000") {
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
    return $sValue !== "" && !nxIsValidBirthNumber($sValue);
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
    if ($sBirthDate !== "" && $sBirthNumberDate !== "" && $sBirthDate !== $sBirthNumberDate) {
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
    $sSql = "SELECT s.id AS subject_id, s.subject_type, COALESCE(IF(s.subject_type = 'person', " . $sPersonDisplayName . ", NULL), NULLIF(subn.name, ''), n.primary_nickname, c.primary_contact, 'Unnamed subject') AS subject_name, COALESCE(IF(s.subject_type = 'person', " . $sPersonSortName . ", NULL), NULLIF(subn.name, ''), n.primary_nickname, c.primary_contact, 'Unnamed subject') AS subject_sort_name, s.is_active, s.created_at, p.title_before, p.first_name, p.middle_name, p.last_name, p.title_after, p.birth_name, p.birth_number, p.birth_date, p.death_date, p.birthday_served_at, p.inter_served_at, c.contacts, a.addresses, n.nicknames, g.group_names, sn.notes FROM ex_subjects AS s
        LEFT JOIN ex_persons AS p ON p.subject_id = s.id
        LEFT JOIN ex_subject_names AS subn ON subn.subject_id = s.id
        LEFT JOIN (SELECT sc.subject_id, GROUP_CONCAT(CONCAT(" . $sContactTypeNameSql . ", ': ', c.contact_value, IF(sc.note IS NULL OR sc.note = '', '', CONCAT(' (', sc.note, ')'))) ORDER BY sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC SEPARATOR '\n') AS contacts, SUBSTRING_INDEX(GROUP_CONCAT(c.contact_value ORDER BY sc.is_active DESC, ct.`order` ASC, sc.is_primary DESC, sc.id ASC SEPARATOR '\n'), '\n', 1) AS primary_contact FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id" . $sContactTypeJoinSql . " GROUP BY sc.subject_id) AS c ON c.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(NULLIF(CONCAT_WS(', ', NULLIF(TRIM(CONCAT_WS(' ', NULLIF(street_name, ''), NULLIF(CONCAT_WS('/', NULLIF(house_number, ''), NULLIF(orientation_number, '')), ''))), ''), NULLIF(city, ''), NULLIF(postal_code, ''), NULLIF(country, '')), '') ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n') AS addresses FROM ex_subject_addresses GROUP BY subject_id) AS a ON a.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(CONCAT(nickname, IF(context IS NULL OR context = '', '', CONCAT(' [', context, ']')), IF(note IS NULL OR note = '', '', CONCAT(' (', note, ')'))) ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n') AS nicknames, SUBSTRING_INDEX(GROUP_CONCAT(nickname ORDER BY is_active DESC, is_primary DESC, id ASC SEPARATOR '\n'), '\n', 1) AS primary_nickname FROM ex_subject_nicknames GROUP BY subject_id) AS n ON n.subject_id = s.id
        LEFT JOIN (SELECT sg.subject_id, GROUP_CONCAT(g.name ORDER BY g.`order` ASC, g.id ASC SEPARATOR '\n') AS group_names FROM ex_subject_groups AS sg INNER JOIN ex_groups AS g ON g.id = sg.group_id GROUP BY sg.subject_id) AS g ON g.subject_id = s.id
        LEFT JOIN (SELECT subject_id, GROUP_CONCAT(note_text ORDER BY is_active DESC, id ASC SEPARATOR '\n') AS notes FROM ex_subject_notes GROUP BY subject_id) AS sn ON sn.subject_id = s.id";
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
    $sSql = "SELECT sc.id AS subject_contact_id, sc.subject_id, sc.contact_id, sc.is_primary, sc.is_active, sc.note, c.contact_type_id, COALESCE(ct.contact_type, '') AS contact_type, " . $sContactTypeNameSql . " AS contact_type_name, c.contact_value FROM ex_subject_contacts AS sc INNER JOIN ex_contacts AS c ON c.id = sc.contact_id" . $sContactTypeJoinSql;
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
    $sSql = "SELECT id, subject_id, nickname, context, is_primary, is_active, note FROM ex_subject_nicknames";
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
    $sSql = "SELECT id, subject_id, address_type, organization_name, department_name, care_of, street_name, house_number, evidence_number, orientation_number, orientation_suffix, address_line2, city, city_part, postal_code, region, country, is_primary, is_active, note FROM ex_subject_addresses";
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
    $sSql = "SELECT sg.subject_id, sg.group_id, g.name FROM ex_subject_groups AS sg INNER JOIN ex_groups AS g ON g.id = sg.group_id";
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

function nxFetchGroups($oPdo) {
    $oStatement = $oPdo->query("SELECT id, name, legacy_id, `order` FROM ex_groups ORDER BY `order` ASC, id ASC");
    return $oStatement->fetchAll(PDO::FETCH_ASSOC);
}

function nxFetchGroupAdminRows($oPdo, $iGroupId = 0) {
    $sSql = "SELECT g.id, g.name, g.`order`, COUNT(DISTINCT sg.subject_id) AS subject_count, GROUP_CONCAT(DISTINCT p.permission_key ORDER BY p.permission_key ASC SEPARATOR ',') AS permission_keys, GROUP_CONCAT(DISTINCT p.name ORDER BY p.permission_key ASC SEPARATOR ',') AS permission_names FROM ex_groups AS g LEFT JOIN ex_subject_groups AS sg ON sg.group_id = g.id LEFT JOIN ex_group_permissions AS gp ON gp.group_id = g.id AND gp.is_allowed = 1 LEFT JOIN ex_permissions AS p ON p.id = gp.permission_id AND p.is_active = 1";
    if ($iGroupId > 0) {
        $sSql .= " WHERE g.id = :id";
    }
    $sSql .= " GROUP BY g.id, g.name, g.`order`";
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
    $oStatement = $oPdo->prepare("SELECT id, user_name, is_active FROM ex_users WHERE subject_id = :subject_id");
    $oStatement->execute(array("subject_id" => $iSubjectId));
    $aUser = $oStatement->fetch(PDO::FETCH_ASSOC);
    if (!$aUser) {
        return $aPortalUser;
    }

    $aPortalUser["has_user"] = 1;
    $aPortalUser["user_name"] = (string)$aUser["user_name"];
    $aPortalUser["is_active"] = (int)$aUser["is_active"];
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
    if (!is_array($aPermissionKeys) || count($aPermissionKeys) === 0) {
        return $aNormalizedKeys;
    }
    foreach ($aPermissionKeys as $sPermissionKey) {
        $sPermissionKey = trim((string)$sPermissionKey);
        if ($sPermissionKey !== "" && !isset($aKeys[$sPermissionKey])) {
            $aKeys[$sPermissionKey] = true;
        }
    }
    if (count($aKeys) === 0) {
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
    if ($sDirection === "up") {
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
    if ($sUserName === "") {
        throw new Exception("Portal user name is required.");
    }
    if (!$aUser && $sPassword === "") {
        throw new Exception("Password is required for a new portal user.");
    }

    if ($aUser) {
        if ($sPassword !== "") {
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
    global $sDeleteEmoji, $sEditEmoji;

    $sMergeEmoji = "&#128260;";
    $sMoveUpEmoji = "&#9650;";
    $sMoveDownEmoji = "&#9660;";
    $sPermissionKeys = isset($aGroup["permission_keys"]) ? (string)$aGroup["permission_keys"] : "";
    $sPermissionNames = isset($aGroup["permission_names"]) ? (string)$aGroup["permission_names"] : "";

    return "      <tr data-group-id=\"" . nxHtml($aGroup["id"]) . "\" data-group-name=\"" . nxHtml($aGroup["name"]) . "\" data-group-order=\"" . nxHtml($aGroup["order"]) . "\" data-permission-keys=\"" . nxHtml($sPermissionKeys) . "\">\n"
        . "        <td>" . nxHtml($aGroup["name"]) . "</td>\n"
        . "        <td>" . nxHtml($aGroup["subject_count"]) . "</td>\n"
        . "        <td>" . ($sPermissionNames !== "" ? nl2br(nxHtml(str_replace(",", "\n", $sPermissionNames)), false) : "&#10134;") . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-move-group-up\" title=\"Move up\" aria-label=\"Move up\">" . $sMoveUpEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"nx-item-action js-move-group-down\" title=\"Move down\" aria-label=\"Move down\">" . $sMoveDownEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-merge-group\" title=\"Merge into this group\" aria-label=\"Merge into this group\">" . $sMergeEmoji . "</a>" : "") . "</td>\n"
        . "        <td class=\"nx-admin-action-column\">" . ($blShowActions ? "<a href=\"#\" class=\"nx-item-action js-edit-group\" title=\"Edit\" aria-label=\"Edit\">" . $sEditEmoji . "</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" class=\"nx-item-action js-delete-group\" title=\"Delete\" aria-label=\"Delete\">" . $sDeleteEmoji . "</a>" : "") . "</td>\n"
        . "      </tr>\n";
}

function nxFetchSubjectNotes($oPdo, $iSubjectId = 0) {
    $aNotes = array();
    $sSql = "SELECT id, subject_id, note_text, is_active FROM ex_subject_notes";
    if ($iSubjectId > 0) {
        $sSql .= " WHERE subject_id = :subject_id";
    }
    $sSql .= " ORDER BY subject_id ASC, is_active DESC, id ASC";
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

function nxCollectHiddenInactiveSubjectItems(&$aHiddenInactive, $aItems) {
    foreach ($aItems as $iSubjectId => $aSubjectItems) {
        foreach ($aSubjectItems as $aItem) {
            if (isset($aItem["is_active"]) && (int)$aItem["is_active"] !== 1) {
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
            if ((int)$aRow["is_active"] === 1) {
                $aActiveRows[] = $aRow;
            }
        }
        $aRows = $aActiveRows;
    }

    if (empty($aSettings["show_inactive_nicknames"])) {
        foreach ($aNicknames as $iSubjectId => $aSubjectNicknames) {
            $aActiveNicknames = array();
            foreach ($aSubjectNicknames as $aNickname) {
                if (!isset($aNickname["is_active"]) || (int)$aNickname["is_active"] === 1) {
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
                if (!isset($aAddress["is_active"]) || (int)$aAddress["is_active"] === 1) {
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
                if ((int)$aContact["is_active"] === 1) {
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
                if (!isset($aNote["is_active"]) || (int)$aNote["is_active"] === 1) {
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
    $blIsActive = (int)$aRow["is_active"] === 1;
    $blShowBirthNumber = !is_array($aDisplaySettings) || empty($aDisplaySettings["hide_personal_number"]);
    $sBirthNumberClass = nxBirthNumberClass($aRow["birth_number"]);
    $sBirthNumberClassAttribute = $sBirthNumberClass !== "" ? " class=\"" . nxHtml($sBirthNumberClass) . "\"" : "";
    $sBirthDateClass = nxBirthDateClass($aRow["birth_number"], $aRow["birth_date"]);
    $sBirthDateClassAttribute = $sBirthDateClass !== "" ? " class=\"" . nxHtml($sBirthDateClass) . "\"" : "";
    if (!isset($sPortalEmoji)) {
        $sPortalEmoji = "🔐";
    }
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
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["subject_name"])
        . nxRenderCopyAction($aRow["subject_name"])
        . $sActions . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["first_name"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["last_name"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["birth_name"]) . "</td>\n"
        . ($blShowBirthNumber ? "        <td" . $sBirthNumberClassAttribute . " style=\"vertical-align: top;\">" . nxRenderBirthNumberValue($aRow["birth_number"]) . "</td>\n" : "")
        . "        <td" . $sBirthDateClassAttribute . " style=\"vertical-align: top;\">" . nxHtmlValue($aRow["birth_date"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxHtmlValue($aRow["death_date"]) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderNicknameList(isset($aNicknames[$iSubjectId]) ? $aNicknames[$iSubjectId] : array(), $blShowActions, $iSubjectId, !empty($aHiddenInactive["nicknames"][$iSubjectId])) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderAddressList(isset($aAddresses[$iSubjectId]) ? $aAddresses[$iSubjectId] : array(), $blShowActions, $iSubjectId, $aRow["subject_name"], !empty($aHiddenInactive["addresses"][$iSubjectId]), $aDisplaySettings) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderContactList(isset($aContacts[$iSubjectId]) ? $aContacts[$iSubjectId] : array(), $blShowActions, $iSubjectId, true, true, !empty($aHiddenInactive["contacts"][$iSubjectId])) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderGroupList(isset($aGroups[$iSubjectId]) ? $aGroups[$iSubjectId] : array(), $blShowActions, $iSubjectId) . "</td>\n"
        . "        <td style=\"vertical-align: top;\">" . nxRenderNoteList(isset($aNotes[$iSubjectId]) ? $aNotes[$iSubjectId] : array(), $blShowActions, $iSubjectId, !empty($aHiddenInactive["notes"][$iSubjectId])) . "</td>\n"
        . "      </tr>\n";
}

function nxRenderUpdatedSubjectRow($oPdo, $iSubjectId, $aVisibilitySettings = null) {
    $aRows = nxFetchSubjectRows($oPdo, $iSubjectId);
    if (count($aRows) === 0) {
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
        if (count($aRows) === 0) {
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
    if ($sRowHtml === "") {
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
    if (count($aRows) === 0) {
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
    return preg_replace("#</head>#i", "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n</head>", $sHtml, 1);
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
        . "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n"
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
    if ($iSelected === 0) {
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
