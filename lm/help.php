<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireViewAccess($aAllowedIps, "portal", "lm_csrf_token");


$sTitle = getPageTitleText("LM Help", $aAllowedIps);
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
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <h1>LM Help</h1>
  <h2>US English</h2>
  <dl class="lm-help-list">
    <dt>Purpose</dt>
    <dd>
      <p>LM is the local maintenance dashboard. It collects runtime, request, PHP, database, menu, and author-list pages used during portal maintenance.</p>
      <p>The dashboard entry point shows the current request overview: navigation, IP sources, HTTP headers, <code>$_SERVER</code>, <code>$_SESSION</code>, and <code>$_COOKIE</code>.</p>
    </dd>
    <dt>Menu</dt>
    <dd>
      <p>All LM pages use the shared hamburger menu rendered from active <code>fs_menu</code> rows whose path starts with <code>/lm/</code>. The menu opens beside the button and marks the current page when its path matches the active row.</p>
    </dd>
    <dt>Access</dt>
    <dd>
      <p>Read-only help uses portal view access. Runtime diagnostics, database export, and menu administration require portal full access because they expose server configuration, request data, session values, cookies, database metadata, database content, or can edit global menu rows.</p>
    </dd>
    <dt>Quick Filter</dt>
    <dd>
      <p>Table pages use the local LM quick filter. It narrows already rendered rows in the browser, stores the value per page and filter id in the session, and supports AND and OR operators.</p>
    </dd>
    <dt>Pages</dt>
    <dd>
      <ul>
        <li><strong>Dashboard:</strong> Request and runtime overview from navigation through PHP cookie data.</li>
        <li><strong>Authors:</strong> Author names with linked page numbers from the source tables.</li>
        <li><strong>Defined PHP Constants:</strong> Constants returned by <code>get_defined_constants(true)</code>, with group, name, value, and type columns.</li>
        <li><strong>Database Structure:</strong> All database tables in dependency order, with full schema download and full backup download.</li>
        <li><strong>Database Information:</strong> Database server metadata and PDO connection attributes for the current connection.</li>
        <li><strong>PHP Info:</strong> Selectable <code>phpinfo()</code> and <code>phpcredits()</code> output.</li>
        <li><strong>Environment:</strong> Curated PHP runtime, configuration file, PDO, limit, and security settings.</li>
        <li><strong>Loaded Extensions:</strong> Extension names from <code>get_loaded_extensions()</code>.</li>
        <li><strong>Configuration Options:</strong> Values from <code>ini_get_all()</code>, including global value, local value, and access level.</li>
        <li><strong>OPcache:</strong> OPcache status and configuration when OPcache functions are available, with unavailable or disabled states shown explicitly.</li>
        <li><strong>Request:</strong> Filterable <code>$_GET</code>, <code>$_POST</code>, <code>$_FILES</code>, <code>$_SERVER</code>, <code>$_SESSION</code>, and <code>$_COOKIE</code>.</li>
        <li><strong>Streams:</strong> Stream wrappers, transports, and filters available in the PHP runtime.</li>
        <li><strong>Menu:</strong> Global <code>fs_menu</code> administration grouped by path prefix.</li>
        <li><strong>Help:</strong> This page, the only place where LM page behavior is described.</li>
      </ul>
    </dd>
  </dl>
  <h2>Cesky</h2>
  <dl class="lm-help-list">
    <dt>Ucel</dt>
    <dd>
      <p>LM je lokalni maintenance dashboard. Seskupuje runtime, request, PHP, databazove, menu a autorske stranky pouzivane pri udrzbe portalu.</p>
      <p>Vstupni dashboard zobrazuje aktualni request overview: navigaci, zdroje IP adres, HTTP hlavicky, <code>$_SERVER</code>, <code>$_SESSION</code> a <code>$_COOKIE</code>.</p>
    </dd>
    <dt>Menu</dt>
    <dd>
      <p>Vsechny LM stranky pouzivaji spolecne hamburger menu z aktivnich radku <code>fs_menu</code>, jejichz cesta zacina <code>/lm/</code>. Menu se otevre u tlacitka a oznaci aktualni stranku, pokud jeji cesta odpovida aktivnimu radku.</p>
    </dd>
    <dt>Pristup</dt>
    <dd>
      <p>Napoveda vyzaduje portal view pristup. Runtime diagnostiky, databazovy export a sprava menu vyzaduji portal full pristup, protoze zobrazuji serverovou konfiguraci, request data, session, cookies, databazova metadata, databazovy obsah nebo umi upravovat globalni menu.</p>
    </dd>
    <dt>Rychly filtr</dt>
    <dd>
      <p>Tabulkove stranky pouzivaji lokalni LM rychly filtr. V prohlizeci zuzuje uz vykreslene radky, hodnotu uklada do session podle stranky a id filtru a podporuje operatory AND a OR.</p>
    </dd>
    <dt>Stranky</dt>
    <dd>
      <ul>
        <li><strong>Dashboard:</strong> Request a runtime prehled od navigace po PHP cookie data.</li>
        <li><strong>Authors:</strong> Autori a prirazena cisla stran ze zdrojovych tabulek.</li>
        <li><strong>Defined PHP Constants:</strong> Konstanty z <code>get_defined_constants(true)</code> se sloupci skupina, nazev, hodnota a typ.</li>
        <li><strong>Database Structure:</strong> Vsechny databazove tabulky v poradi podle zavislosti, se stazenim celeho schematu a cele zalohy.</li>
        <li><strong>Database Information:</strong> Metadata databazoveho serveru a PDO atributy aktualniho pripojeni.</li>
        <li><strong>PHP Info:</strong> Volitelny vystup <code>phpinfo()</code> a <code>phpcredits()</code>.</li>
        <li><strong>Environment:</strong> Strucny prehled PHP runtime, konfiguracnich souboru, PDO, limitu a bezpecnostnich nastaveni.</li>
        <li><strong>Loaded Extensions:</strong> Nazvy rozsireni z <code>get_loaded_extensions()</code>.</li>
        <li><strong>Configuration Options:</strong> Hodnoty z <code>ini_get_all()</code>, vcetne globalni hodnoty, lokalni hodnoty a access levelu.</li>
        <li><strong>OPcache:</strong> Stav a konfigurace OPcache, pokud jsou OPcache funkce dostupne; nedostupny nebo vypnuty stav se vypise explicitne.</li>
        <li><strong>Request:</strong> Filtrovatelne <code>$_GET</code>, <code>$_POST</code>, <code>$_FILES</code>, <code>$_SERVER</code>, <code>$_SESSION</code> a <code>$_COOKIE</code>.</li>
        <li><strong>Streams:</strong> Stream wrappers, transports a filters dostupne v PHP runtime.</li>
        <li><strong>Menu:</strong> Globalni sprava <code>fs_menu</code> rozdelena podle prefixu cesty.</li>
        <li><strong>Help:</strong> Tato stranka, jedine misto, kde se popisuje chovani LM stranek.</li>
      </ul>
    </dd>
  </dl>
  <script type="text/javascript" src="<?php echo html($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))); ?>"></script>
</body>
</html>
