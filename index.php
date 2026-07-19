<?php

include "config.php";
include "functions.php";

$sStyleNonce = base64_encode(random_bytes(16));

if (in_array($_SERVER["REMOTE_ADDR"], $aAllowedIps, true)) {
    sendPhpInfoAndExit($sStyleNonce);
}

$iTime    = time();
$iExpires = $iTime + 10;
$sDate    = gmdate("D, d M Y H:i:s", $iTime);
$sExpires = gmdate("D, d M Y H:i:s", $iExpires);

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
        <title>עבד יהוה</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css" nonce="<?php echo htmlspecialchars($sStyleNonce, ENT_QUOTES, "UTF-8"); ?>">
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
            }
            h1, h2 {
                margin: 10px 0;
            }
        </style>
    </head>
    <body>

        <h1>עֶבֶד יְהוָה</h1>
        <h2>וְאָנֹכִי וּבֵיתִי נַעֲבֹד אֶת־יְהוָה</h2>

    </body>
</html>
