<?php

include "main.php";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["fingerprint"]) && (string)$_GET["fingerprint"] == "1") {
    sendEvedUaFingerprintResponse();
}

$sStyleNonce = base64_encode(random_bytes(16));

$blPortalIndexAllowed = isAllowedIp() || isProjectViewAllowed("portal");
if ($blPortalIndexAllowed) {
    $sUserAgent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "";
    $blMsie = strpos($sUserAgent, "MSIE ") !== false || strpos($sUserAgent, "Trident/") !== false;
    $iTime = sendPageHeaders($sStyleNonce);
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
  <title>EVED</title>
  <meta http-equiv="Content-Language" content="en-US">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link rel="icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <style type="text/css" nonce="<?php echo html($sStyleNonce); ?>">
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
  <title>EVED</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link rel="icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="shortcut icon" type="image/x-icon" href="<?php echo $sBaseUrl; ?>gfx/favicon.ico">
  <link rel="stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost.css")); ?>" title="Original">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-graphite.css")); ?>" title="Graphite">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-midnight.css")); ?>" title="Midnight">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-slate.css")); ?>" title="Slate">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-sepia.css")); ?>" title="Sepia">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-sand.css")); ?>" title="Sand">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-forest.css")); ?>" title="Forest">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-moss.css")); ?>" title="Moss">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-ocean.css")); ?>" title="Ocean">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-ice.css")); ?>" title="Ice">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-lavender.css")); ?>" title="Lavender">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-rose.css")); ?>" title="Rose">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-copper.css")); ?>" title="Copper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-burgundy.css")); ?>" title="Burgundy">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-monochrome.css")); ?>" title="Monochrome">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-high-contrast.css")); ?>" title="High Contrast">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-soft.css")); ?>" title="Soft">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-paper.css")); ?>" title="Paper">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-terminal.css")); ?>" title="Terminal">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-cobalt.css")); ?>" title="Cobalt">
  <link rel="alternate stylesheet" type="text/css" href="<?php echo $sBaseUrl; ?>css/guidepost-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/guidepost-plum.css")); ?>" title="Plum">
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/style.js")); ?>" integrity="sha384-Y1RNflBlpVdW8pnre87MlysCCOkrIrejOEni6hIeWzTbOmboT6BgObKOeMYSJ5C5"></script>
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

$sEvedUaId = insertEvedUaRequest();
if ($sEvedUaId == "") {
    send500AndExit("Database error: UA request could not be logged.");
}
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
  <title>עבד יהוה</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
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
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/style.js")); ?>" integrity="sha384-Y1RNflBlpVdW8pnre87MlysCCOkrIrejOEni6hIeWzTbOmboT6BgObKOeMYSJ5C5"></script>
</head>
<body data-ua-id="<?php echo $sEvedUaId; ?>">
  <h1>עֶבֶד יְהוָה</h1>
  <h2>וְאָנֹכִי וּבֵיתִי נַעֲבֹד אֶת־יְהוָה</h2>
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/bowser-2.14.1/es5.js" integrity="sha384-cmTaw5RKX6Cm5u7iCsI3+4sR2uFMl5DL7luhvrg/DgphgH7NgvukeKWHbq8bOzrR"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>film/js/ua.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/film/js/ua.js")); ?>" integrity="sha384-gmuQ3c2lCXv90ThentvrWhINJM5FY1rFfp1b91mdXaF5+DEZQlwMbqKHJSM+BItF"></script>
</body>
</html>
