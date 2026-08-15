<?php

include "main.php";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET["fingerprint"]) && (string)$_GET["fingerprint"] == "1") {
    sendEvedUaFingerprintResponse($oPdo);
}

$sStyleNonce = base64_encode(random_bytes(16));

$blPortalIndexAllowed = isAllowedIp() || isProjectViewAllowed("portal");
if ($blPortalIndexAllowed) {
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
    $aLinks[] = array("href" => "https://securityheaders.com/", "icon" => "&#128274;", "name" => "Security Headers", "title" => "HTTP security header scanner");
    $aLinks[] = array("href" => "https://developer.mozilla.org/en-US/observatory", "icon" => "&#128301;", "name" => "MDN Observatory", "title" => "Mozilla site security scanner");
    $aLinks[] = array("href" => "https://github.com/fortsoft-eu", "icon" => "&#128008;", "name" => "GitHub", "title" => "Source code hosting");
    $aLinks[] = array("href" => "https://unicode.org/emoji/charts/full-emoji-list.html", "icon" => "&#9786;&#65039;", "name" => "Emoji List", "title" => "Unicode full emoji list");
    $aLinks[] = array("href" => "https://www.profisms.cz/", "icon" => "&#128241;", "name" => "ProfiSMS", "title" => "SMS administration");
    $aLinks[] = array("href" => "https://marketplace.apilayer.com/whois-api", "icon" => "&#128268;", "name" => "APILayer WHOIS", "title" => "APILayer WHOIS API dashboard");
    $aLinks[] = array("href" => "https://app.abstractapi.com/dashboard", "icon" => "&#129513;", "name" => "Abstract API", "title" => "Abstract API dashboard");

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
  <link href="<?php echo $sBaseUrl; ?>css/index-original.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-original.css")); ?>" rel="stylesheet" type="text/css" title="Original">
  <link href="<?php echo $sBaseUrl; ?>css/index-graphite.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-graphite.css")); ?>" rel="alternate stylesheet" type="text/css" title="Graphite">
  <link href="<?php echo $sBaseUrl; ?>css/index-midnight.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-midnight.css")); ?>" rel="alternate stylesheet" type="text/css" title="Midnight">
  <link href="<?php echo $sBaseUrl; ?>css/index-slate.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-slate.css")); ?>" rel="alternate stylesheet" type="text/css" title="Slate">
  <link href="<?php echo $sBaseUrl; ?>css/index-sepia.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-sepia.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sepia">
  <link href="<?php echo $sBaseUrl; ?>css/index-sand.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-sand.css")); ?>" rel="alternate stylesheet" type="text/css" title="Sand">
  <link href="<?php echo $sBaseUrl; ?>css/index-forest.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-forest.css")); ?>" rel="alternate stylesheet" type="text/css" title="Forest">
  <link href="<?php echo $sBaseUrl; ?>css/index-moss.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-moss.css")); ?>" rel="alternate stylesheet" type="text/css" title="Moss">
  <link href="<?php echo $sBaseUrl; ?>css/index-ocean.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-ocean.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ocean">
  <link href="<?php echo $sBaseUrl; ?>css/index-ice.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-ice.css")); ?>" rel="alternate stylesheet" type="text/css" title="Ice">
  <link href="<?php echo $sBaseUrl; ?>css/index-lavender.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-lavender.css")); ?>" rel="alternate stylesheet" type="text/css" title="Lavender">
  <link href="<?php echo $sBaseUrl; ?>css/index-rose.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-rose.css")); ?>" rel="alternate stylesheet" type="text/css" title="Rose">
  <link href="<?php echo $sBaseUrl; ?>css/index-copper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-copper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Copper">
  <link href="<?php echo $sBaseUrl; ?>css/index-burgundy.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-burgundy.css")); ?>" rel="alternate stylesheet" type="text/css" title="Burgundy">
  <link href="<?php echo $sBaseUrl; ?>css/index-monochrome.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-monochrome.css")); ?>" rel="alternate stylesheet" type="text/css" title="Monochrome">
  <link href="<?php echo $sBaseUrl; ?>css/index-high-contrast.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-high-contrast.css")); ?>" rel="alternate stylesheet" type="text/css" title="High Contrast">
  <link href="<?php echo $sBaseUrl; ?>css/index-soft.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-soft.css")); ?>" rel="alternate stylesheet" type="text/css" title="Soft">
  <link href="<?php echo $sBaseUrl; ?>css/index-paper.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-paper.css")); ?>" rel="alternate stylesheet" type="text/css" title="Paper">
  <link href="<?php echo $sBaseUrl; ?>css/index-terminal.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-terminal.css")); ?>" rel="alternate stylesheet" type="text/css" title="Terminal">
  <link href="<?php echo $sBaseUrl; ?>css/index-cobalt.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-cobalt.css")); ?>" rel="alternate stylesheet" type="text/css" title="Cobalt">
  <link href="<?php echo $sBaseUrl; ?>css/index-plum.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/index-plum.css")); ?>" rel="alternate stylesheet" type="text/css" title="Plum">
  <script type="text/javascript" src="/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/style.js")); ?>"></script>
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

    foreach ($aLinks as $aLink) {
        $sHref = (string)$aLink["href"];
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
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title>עבד יהוה</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="eved-ua-id" content="<?php echo (int)$iEvedUaId; ?>">
  <style type="text/css" nonce="<?php echo html($sStyleNonce); ?>">
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
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/vendors/bowser-2.14.1/es5.js"></script>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>film/js/ua.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/film/js/ua.js")); ?>"></script>
</body>
</html>
