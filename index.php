<?php

include "main.php";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["fingerprint"]) && (string)$_GET["fingerprint"] == "1") {
    sendEvedUaFingerprintResponse($oPdo, $aAllowedIps);
}

runKfExchangeRateProcess($oPdo, $sError);

$sStyleNonce = base64_encode(random_bytes(16));

$blPortalIndexAllowed = isAllowedIp($aAllowedIps) || isProjectViewAllowed("portal");
if ($blPortalIndexAllowed) {
    $iTime = sendPageHeaders($sStyleNonce);
    $aProjects = array(
        array("href" => "lm/", "icon" => "&#128736;&#65039;", "name" => "Dashboard", "title" => "Monitoring and management"),
        array("href" => "ex/", "icon" => "&#128214;", "name" => "Portal", "title" => "Subjects and contacts directory"),
        array("href" => "kf/", "icon" => "&#128182;", "name" => "Kesef", "title" => "Income and expenses"),
        array("href" => "film/", "icon" => "&#127902;&#65039;", "name" => "Film", "title" => "Film scans gallery")
    );
    $aSecurityLinks = array(
        array("href" => "https://admin.aerohosting.cz/", "icon" => "&#128452;&#65039;", "name" => "AeroHosting", "title" => "Hosting administration"),
        array("href" => "https://securityheaders.com/", "icon" => "&#128737;&#65039;", "name" => "Security Headers", "title" => "HTTP security header scanner"),
        array("href" => "https://developer.mozilla.org/en-US/observatory", "icon" => "&#128301;", "name" => "MDN Observatory", "title" => "Mozilla site security scanner"),
        array("href" => "https://github.com/", "icon" => "&#128008;", "name" => "GitHub", "title" => "Source code hosting"),
        array("href" => "https://unicode.org/emoji/charts/full-emoji-list.html", "icon" => "&#9786;&#65039;", "name" => "Emoji List", "title" => "Unicode full emoji list"),
        array("href" => "https://www.profisms.cz/", "icon" => "&#128241;", "name" => "ProfiSMS", "title" => "SMS administration"),
        array("href" => "https://marketplace.apilayer.com/whois-api", "icon" => "&#128268;", "name" => "APILayer", "title" => "API dashboard")
    );

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>EVED</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <style type="text/css" nonce="<?php echo html($sStyleNonce); ?>">
    html, body {
        overscroll-behavior-y: none;
    }
    html {
        box-sizing: border-box;
    }
    *, *::before, *::after {
        box-sizing: inherit;
    }
    body {
        min-height: 100vh;
        margin: 0;
        padding: 24px;
        color: #1f2933;
        background: #f6f7f9;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 16px;
        line-height: 1.45;
    }
    main {
        width: min(920px, 100%);
        margin: 0 auto;
    }
    h1 {
        margin: 0 0 18px;
        font-size: 28px;
        font-weight: 700;
    }
    .project-list {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .project-link {
        display: grid;
        grid-template-columns: 48px 1fr;
        gap: 10px;
        align-items: start;
        height: 88px;
        padding: 12px;
        border: 1px solid #c8d0d8;
        border-radius: 0;
        color: inherit;
        background: #fff;
        text-decoration: none;
        overflow: hidden;
    }
    .project-link:hover, .project-link:focus {
        border-color: #1a73e8;
        outline: 2px solid #1a73e8;
        outline-offset: 0;
    }
    .project-icon {
        align-self: center;
        font-size: 36px;
        line-height: 1;
        text-align: center;
    }
    .project-name {
        display: block;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .project-title {
        display: block;
        color: #52606d;
        font-size: 14px;
        max-height: 40px;
        overflow: hidden;
    }
    hr {
        margin: 18px 0;
        border: 0;
        border-top: 1px solid #c8d0d8;
    }
    @media (max-width: 960px) {
        .project-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 520px) {
        body {
            padding: 16px;
        }
        .project-list {
            grid-template-columns: 1fr;
        }
        h1 {
            display: none;
        }
    }
  </style>
</head>
<body>
  <main>
    <h1>EVED</h1>
    <ul class="project-list">
<?php

    foreach ($aProjects as $aProject) {
        $sHref = (string)$aProject["href"];
        if (!preg_match("/^[a-z][a-z0-9+.-]*:/i", $sHref) && substr($sHref, 0, 1) != "/") {
            $sHref = $sBaseUrl . $sHref;
        }
        echo "      <li><a class=\"project-link\" href=\"" . html($sHref) . "\" target=\"_blank\" rel=\"noopener noreferrer\"><span class=\"project-icon\" aria-hidden=\"true\">" . $aProject["icon"] . "</span><span><span class=\"project-name\">" . html($aProject["name"]) . "</span><span class=\"project-title\">" . html($aProject["title"]) . "</span></span></a></li>\n";
    }

?>
    </ul>
    <hr>
    <ul class="project-list">
<?php

    foreach ($aSecurityLinks as $aSecurityLink) {
        $sHref = (string)$aSecurityLink["href"];
        if (!preg_match("/^[a-z][a-z0-9+.-]*:/i", $sHref) && substr($sHref, 0, 1) != "/") {
            $sHref = $sBaseUrl . $sHref;
        }
        echo "      <li><a class=\"project-link\" href=\"" . html($sHref) . "\" target=\"_blank\" rel=\"noopener noreferrer\"><span class=\"project-icon\" aria-hidden=\"true\">" . $aSecurityLink["icon"] . "</span><span><span class=\"project-name\">" . html($aSecurityLink["name"]) . "</span><span class=\"project-title\">" . html($aSecurityLink["title"]) . "</span></span></a></li>\n";
    }

?>
    </ul>
  </main>
</body>
</html>
<?php

    exit;
}

$iEvedUaId = insertEvedUaRequest($oPdo);
$iTime     = time();
$iExpires  = $iTime + 10;
$sDate     = gmdate("D, d M Y H:i:s", $iTime);
$sExpires  = gmdate("D, d M Y H:i:s", $iExpires);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", true);
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache", true);
header("Last-Modified: " . $sDate . " GMT", true);
header("Expires: " . $sExpires . " GMT", true);
header("Content-Type: text/html; charset=utf-8", true);
header("Content-Language: he", true);
header("X-Robots-Tag: noindex, nofollow", true);

sendSecurityHeaders($sStyleNonce);

?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>עבד יהוה</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="eved-ua-id" content="<?php echo (int)$iEvedUaId; ?>">
  <style type="text/css" nonce="<?php echo htmlspecialchars($sStyleNonce, ENT_QUOTES, "UTF-8"); ?>">
    html, body {
        overscroll-behavior-y: none;
    }
    body {
        background-color: #FFF;
        font-family: "Times New Roman", Times, serif;
        color: #000;
        font-size: 24px;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        text-align: center;
        flex-direction: column;
        transform: translateY(-8vh);
    }
    h1, h2 {
        margin: 10px 0;
    }
  </style>
</head>
<body>
  <h1>עֶבֶד יְהוָה</h1>
  <h2>וְאָנֹכִי וּבֵיתִי נַעֲבֹד אֶת־יְהוָה</h2>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>film/vendors/bowser-2.14.1/es5.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>film/js/ua.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/film/js/ua.js")); ?>"></script>
</body>
</html>
