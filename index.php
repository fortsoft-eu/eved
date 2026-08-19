<?php

include "main.php";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["fingerprint"]) && (string)$_GET["fingerprint"] == "1") {
    sendEvedUaFingerprintResponse($oPdo);
}

$sStyleNonce = base64_encode(random_bytes(16));

$blPortalIndexAllowed = isAllowedIp() || isProjectViewAllowed("portal");
if ($blPortalIndexAllowed) {
    $sUserAgent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "";
    $blMsie = strpos($sUserAgent, "MSIE ") !== false || strpos($sUserAgent, "Trident/") !== false;
    $iTime = sendPageHeaders($blMsie ? "" : $sStyleNonce);
    $aProjects = array(
        array("href" => "lm/", "icon" => "&#128736;&#65039;", "name" => "Technical", "title" => "Monitoring and management"),
        array("href" => "ex/", "icon" => "&#128214;", "name" => "Portal", "title" => "Subjects and contacts directory"),
        array("href" => "kf/", "icon" => "&#128182;", "name" => "Kesef", "title" => "Income and expenses"),
        array("href" => "film/", "icon" => "&#127902;&#65039;", "name" => "Film", "title" => "Film scans gallery")
    );
    $aLinks = array();
    $aLinks[] = array("href" => "https://admin." . $sWebHostingDomain . "/", "icon" => "&#128745;&#65039;", "name" => "AeroHosting", "title" => "Hosting administration");
    $aLinks[] = array("href" => "https://myadmin." . $sWebHostingDomain . "/", "icon" => "&#128452;&#65039;", "name" => "MyAdmin", "title" => "Database administration");
    list($user, $pass) = arrayReadNext($aAWStatsAccounts);
    $aLinks[] = array("href" => "https://" . rawurlencode($user) . ":" . rawurlencode($pass) . "@stats." . $sWebHostingDomain . "/", "icon" => "&#128202;", "name" => "AWStats", "title" => html($user) . " site statistics");
    list($user, $pass) = arrayReadNext($aAWStatsAccounts);
    $aLinks[] = array("href" => "https://" . rawurlencode($user) . ":" . rawurlencode($pass) . "@stats." . $sWebHostingDomain . "/", "icon" => "&#128200;", "name" => "AWStats", "title" => html($user) . " site statistics");
    $aLinks[] = array("href" => "https://github.com/fortsoft-eu", "icon" => "&#128008;", "name" => "GitHub", "title" => "Source code hosting");
    $aLinks[] = array("href" => "https://www.profisms.cz/", "icon" => "&#128241;", "name" => "ProfiSMS", "title" => "SMS administration");
    $aLinks[] = array("href" => "https://marketplace.apilayer.com/whois-api", "icon" => "&#128268;", "name" => "APILayer WHOIS", "title" => "APILayer WHOIS API dashboard");
    $aLinks[] = array("href" => "https://app.abstractapi.com/dashboard", "icon" => "&#129513;", "name" => "Abstract API", "title" => "Abstract API dashboard");
    $aLinks[] = array("href" => "https://securityheaders.com/", "icon" => "&#128274;", "name" => "Security Headers", "title" => "HTTP security header scanner");
    $aLinks[] = array("href" => "https://developer.mozilla.org/en-US/observatory", "icon" => "&#128301;", "name" => "MDN Observatory", "title" => "Mozilla site security scanner");
    $aLinks[] = array("href" => "https://www.ssl.org/", "icon" => "&#128209;", "name" => "SSL Checker", "title" => "SSL Certificate Checker");
    $aLinks[] = array("href" => "https://www.ssllabs.com/ssltest/", "icon" => "&#128737;&#65039;", "name" => "SSL Server Test", "title" => "Qualys SSL Server Test");
    $aLinks[] = array("href" => "https://unicode.org/emoji/charts/full-emoji-list.html", "icon" => "&#9786;&#65039;", "name" => "Emoji List", "title" => "Unicode full emoji list");

    if ($blMsie) {

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="Content-Language" content="en-US">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>EVED</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <style type="text/css">
    body {
        margin: 0;
        padding: 24px;
        color: #000;
        background: #F8F8F8;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 14px;
        line-height: 1.45;
    }
    #page {
        width: 920px;
        margin: 0 auto;
    }
    h1 {
        margin: 0 0 18px;
        font-size: 28px;
        font-weight: bold;
    }
    .project-list {
        width: 932px;
        margin: 0 -6px;
        padding: 0;
        list-style: none;
        overflow: hidden;
    }
    .project-list li {
        float: left;
        width: 221px;
        margin: 0 6px 12px;
        padding: 0;
    }
    .project-link {
        position: relative;
        display: block;
        height: 62px;
        padding: 12px 12px 12px 70px;
        border: 1px solid #C0C0C0;
        color: #000;
        background: #FFF;
        text-decoration: none;
        overflow: hidden;
    }
    .project-link:hover, .project-link:focus {
        border-color: #4D90FE;
        color: #000;
        background: #DCDCE4;
        outline: 2px solid #4D90FE;
    }
    .project-icon {
        position: absolute;
        top: 26px;
        left: 12px;
        display: block;
        width: 48px;
        font-size: 36px;
        line-height: 1;
        text-align: center;
    }
    .project-text {
        display: block;
    }
    .project-name {
        display: block;
        font-weight: bold;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .project-title {
        display: block;
        max-height: 40px;
        color: #333;
        font-size: 14px;
        overflow: hidden;
    }
    hr {
        clear: both;
        height: 1px;
        margin: 6px 0 18px;
        padding: 0;
        border: 0;
        color: #C0C0C0;
        background: #C0C0C0;
        font-size: 0;
        line-height: 0;
        overflow: hidden;
    }
    @media screen and (max-width: 960px) {
        #page {
            width: auto;
        }
        .project-list {
            width: auto;
        }
        .project-list li {
            box-sizing: border-box;
            width: 50%;
            margin: 0;
            padding: 0 6px 12px;
        }
    }
    @media screen and (max-width: 520px) {
        body {
            padding: 16px;
        }
        .project-list li {
            width: 100%;
        }
        h1 {
            display: none;
        }
    }
  </style>
</head>
<body>
  <div id="page">
    <h1>EVED</h1>
    <ul class="project-list">
<?php

        foreach ($aProjects as $aProject) {
            $sHref = $aProject["href"];
            $sIcon = str_replace(array("&#65039;", "&#129513;"), array("", "&#10697;"), $aProject["icon"]);
            if (!preg_match("/^[a-z][a-z0-9+.-]*:/i", $sHref) && substr($sHref, 0, 1) != "/") {
                $sHref = $sBaseUrl . $sHref;
            }
            echo "      <li><a class=\"project-link\" href=\"" . html($sHref) . "\"><span class=\"project-icon\">" . $sIcon . "</span><span class=\"project-text\"><span class=\"project-name\">" . html($aProject["name"]) . "</span><span class=\"project-title\">" . html($aProject["title"]) . "</span></span></a></li>\n";
        }

?>
    </ul>
    <hr>
    <ul class="project-list">
<?php

        foreach ($aLinks as $aLink) {
            $sHref = $aLink["href"];
            $sIcon = str_replace(array("&#65039;", "&#129513;"), array("", "&#10697;"), $aLink["icon"]);
            if (!preg_match("/^[a-z][a-z0-9+.-]*:/i", $sHref) && substr($sHref, 0, 1) != "/") {
                $sHref = $sBaseUrl . $sHref;
            }
            echo "      <li><a class=\"project-link\" href=\"" . html($sHref) . "\"><span class=\"project-icon\">" . $sIcon . "</span><span class=\"project-text\"><span class=\"project-name\">" . html($aLink["name"]) . "</span><span class=\"project-title\">" . html($aLink["title"]) . "</span></span></a></li>\n";
        }

?>
    </ul>
  </div>
</body>
</html>
<?php

        exit;
    }

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>EVED</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/guidepost-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/style.js")); ?>"></script>
</head>
<body>
  <main>
    <h1>EVED</h1>
    <ul class="project-list">
<?php

    foreach ($aProjects as $aProject) {
        $sHref = $aProject["href"];
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

    foreach ($aLinks as $aLink) {
        $sHref = $aLink["href"];
        if (!preg_match("/^[a-z][a-z0-9+.-]*:/i", $sHref) && substr($sHref, 0, 1) != "/") {
            $sHref = $sBaseUrl . $sHref;
        }
        echo "      <li><a class=\"project-link\" href=\"" . html($sHref) . "\" target=\"_blank\" rel=\"noopener noreferrer\"><span class=\"project-icon\" aria-hidden=\"true\">" . $aLink["icon"] . "</span><span><span class=\"project-name\">" . html($aLink["name"]) . "</span><span class=\"project-title\">" . html($aLink["title"]) . "</span></span></a></li>\n";
    }

?>
    </ul>
  </main>
</body>
</html>
<?php

    exit;
}

include "kf/functions.php";
include "ex/functions.php";

runKfExchangeRateProcess($oPdo, $sError);
runExCalendarProcess($oPdo, $sError);

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
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>עבד יהוה</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="eved-ua-id" content="<?php echo (int)$iEvedUaId; ?>">
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
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/style.js")); ?>"></script>
</head>
<body>
  <h1>עֶבֶד יְהוָה</h1>
  <h2>וְאָנֹכִי וּבֵיתִי נַעֲבֹד אֶת־יְהוָה</h2>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/bowser-2.14.1/es5.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>film/js/ua.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/film/js/ua.js")); ?>"></script>
</body>
</html>
