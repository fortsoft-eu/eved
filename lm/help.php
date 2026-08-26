<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


$aMenuItemUrls = getMenuItemUrls($oPdo);
$sScriptUrl = $sBaseUrl . basename($_SERVER["SCRIPT_NAME"]);
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo html(getPageTitleText()); ?></title>
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
  <script type="text/javascript" src="<?php echo $sOrigin; ?>/js/style.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/../js/style.js")); ?>" integrity="sha384-Y1RNflBlpVdW8pnre87MlysCCOkrIrejOEni6hIeWzTbOmboT6BgObKOeMYSJ5C5"></script>
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <h1>Dashboard Help</h1>
  <h2>US English</h2>
  <h3>Common Controls</h3>
  <dl class="lm-help-list">
    <dt>Purpose</dt>
    <dd>
      <p>Dashboard is the local maintenance area for portal diagnostics and administration. It collects request, PHP runtime, database, menu, and cited-author pages used during maintenance.</p>
      <p>The Dashboard entry point shows the current request overview from navigation through IP sources, HTTP headers, <code>$_SERVER</code>, <code>$_SESSION</code>, and <code>$_COOKIE</code>.</p>
    </dd>
    <dt>Menu</dt>
    <dd>
      <p>Dashboard pages use the shared hamburger menu rendered from active <code>fs_menu</code> rows whose path belongs to the Dashboard section. The menu opens beside the button and marks the current page when its path matches the active row.</p>
    </dd>
    <dt>Alternate Styles</dt>
    <dd>
      <p>Dashboard pages provide the Original style and twenty named alternate styles. Firefox exposes them through <strong>View &gt; Page Style</strong>; the selected title is stored in browser <code>localStorage</code> and restored on later pages with the same style title.</p>
      <p>Changing the style affects presentation only. The Snippet Board editor follows the selected colors without requiring a page reload.</p>
    </dd>
    <dt>Access</dt>
    <dd>
      <p>Read-only help uses portal view access. Runtime diagnostics, database export, and menu administration require portal full access because they expose server configuration, request data, session values, cookies, database metadata, database content, or can edit global menu rows.</p>
    </dd>
    <dt>Quick Filter</dt>
    <dd>
      <p>Dashboard table pages use the quick filter. It narrows already rendered rows in the browser, stores the value per page and filter id in the session, supports AND and OR operators, and uses F8 as the shortcut for focusing the filter input.</p>
    </dd>
  </dl>
  <h3>Menu Pages</h3>
  <dl class="lm-help-list">
    <dt><a href="<?php echo html($sBaseUrl); ?>">Dashboard</a></dt>
    <dd>
      <p>Dashboard is the section entry point. It shows the maintenance request overview that used to be available from the trusted branch of the film entry point, from Navigation through the PHP <code>$_COOKIE</code> array.</p>
      <p>The page is read-only. It is meant for checking the current request, headers, session, cookies, and server values without opening several separate diagnostic pages.</p>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["req.php"]); ?>">Request Overview</a></dt>
    <dd>
      <p>Request Overview is the compact plain-text request diagnostic page. It prints the current request summary in a preformatted block so the request can be copied or compared without opening the larger table view.</p>
      <p>The page is read-only and is intended for quick inspection of request, server, session, and cookie context.</p>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["bt.php"]); ?>">Issue Tracker</a></dt>
    <dd>
      <p>Issue Tracker lists maintenance issues with type, status, priority, title, due date, updated date, and edit actions. Full access can create, edit, and delete issues from the page dialogs.</p>
      <p>The quick filter narrows the rendered issue table. Description fields are edited as text areas and use the browser's normal spellcheck settings.</p>
      <ul>
        <li><strong>Rows:</strong> One issue per row.</li>
        <li><strong>Editing:</strong> Full access can create, update, and delete issues.</li>
        <li><strong>Filtering:</strong> Quick filter over visible issue text.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["bh.php"]); ?>">Business Hours</a></dt>
    <dd>
      <p>Business Hours manages displayed opening-hour rows. The page uses tabbed business-hour groups, cards for individual records, and a New dialog for adding another entry.</p>
      <p>Full access can add and maintain business-hour records. The page keeps the editing surface focused on the visible business-hour cards instead of exposing raw database tables.</p>
      <ul>
        <li><strong>Display:</strong> Tabs and cards for business-hour records.</li>
        <li><strong>Editing:</strong> Full access can add and update entries.</li>
        <li><strong>Dialogs:</strong> Uses the shared project modal dialog.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["email.php"]); ?>">E-mail Domains</a></dt>
    <dd>
      <p>E-mail Domains shows local-part users across managed domains. Domains are columns, local parts are rows, and a missing address cell is shown as an em dash so gaps are visible.</p>
      <p>The New dialog adds a domain and a list of local users. It stores whether each added address is a mailbox, alias, or forwarder, ignores duplicates already present in the database, and colors table cells by account type.</p>
      <ul>
        <li><strong>Table:</strong> Domains by local-part user.</li>
        <li><strong>Types:</strong> Mailbox, alias, and forwarder.</li>
        <li><strong>Input:</strong> User tokens can be separated by commas, semicolons, whitespace, or lines.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["phone.php"]); ?>">Phone Accounts</a></dt>
    <dd>
      <p>Phone Accounts stores standalone phone-number records with SIM identifiers, PIN/PUK values, IMEI, note, paid date/time, paid amount, currency, and Telegram account. It has no subject, domain, or other page relation.</p>
      <p>Phone numbers and Telegram accounts are normalized to the same canonical values used by the EX contact pages before they are stored.</p>
      <ul>
        <li><strong>Rows:</strong> One phone account per row.</li>
        <li><strong>Order:</strong> New rows are appended and can be moved with arrows.</li>
        <li><strong>Payment:</strong> Amounts accept dot or comma decimal input, currency options follow KF currencies, and new-entry amount and currency defaults are session-backed.</li>
        <li><strong>Editing:</strong> Full access can create, update, and delete rows.</li>
        <li><strong>Filtering:</strong> Quick filter over visible phone-account text.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["sb.php"]); ?>">Snippet Board</a></dt>
    <dd>
      <p>Snippet Board stores six rich-text snippets for quick reuse. Each slot is edited with the shared rich-text editor and saved through the page without leaving the board.</p>
      <p>The board tracks changed, saving, and saved state in the toolbar. On compact devices the visible slot is selected through numbered tabs.</p>
      <ul>
        <li><strong>Slots:</strong> Six editable snippet panels.</li>
        <li><strong>Editor:</strong> Shared TinyMCE editor setup.</li>
        <li><strong>State:</strong> Changed and saved status is shown in the toolbar.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["char.php"]); ?>">Character Converter</a></dt>
    <dd>
      <p>Character Converter converts a Unicode character between the character itself, decimal code point, hexadecimal code point, decimal HTML entity, hexadecimal HTML entity, and named HTML entity where available.</p>
      <p>The palette offers commonly used characters and variants. Text and Emoji buttons choose the preferred presentation for characters that support variation selectors.</p>
      <ul>
        <li><strong>Conversion:</strong> Character, code points, and HTML entities.</li>
        <li><strong>Palette:</strong> Common Unicode characters with title details.</li>
        <li><strong>Safety:</strong> Browser-side conversion without database writes.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["fancy.php"]); ?>">Fancy Text</a></dt>
    <dd>
      <p>Fancy Text converts plain or already styled Unicode text into selected Unicode mathematical alphanumeric styles. The left text area contains the source text and the right text area shows the regenerated styled output.</p>
      <p>The style selector is in the top control row. Changing the selected style regenerates the output, and unsupported characters are left unchanged.</p>
      <ul>
        <li><strong>Input:</strong> Plain or styled Unicode text.</li>
        <li><strong>Output:</strong> Unicode styled text.</li>
        <li><strong>Layout:</strong> Text areas switch between vertical and side-by-side layout according to available space.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["ares.php"]); ?>">ARES Lookup</a></dt>
    <dd>
      <p>ARES Lookup searches Czech business-register data by company ID, business name, registered office, legal form, tax office, and CZ-NACE fields. A direct company-ID match shows a detail table; broader searches show result rows.</p>
      <p>The form is read-only from the local database point of view. It displays returned ARES values in tables that can use the compact table style on small devices.</p>
      <ul>
        <li><strong>Search fields:</strong> Company ID, business name, office, legal form, tax office, and CZ-NACE.</li>
        <li><strong>Output:</strong> Detail or search-result table.</li>
        <li><strong>Safety:</strong> Lookup page without local data writes.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["whois.php"]); ?>">Domain/IP Lookup</a></dt>
    <dd>
      <p>Domain/IP Lookup checks a domain name or IP address and displays registration, reverse DNS, and DNS record information when available.</p>
      <p>The top row follows the same compact control layout as other lookup pages: menu, label, input, and Lookup button. Result tables keep detail values separate from DNS rows so they can be copied or reviewed independently.</p>
      <ul>
        <li><strong>Input:</strong> Domain name or IP address.</li>
        <li><strong>Output:</strong> Domain details, reverse DNS, and DNS records.</li>
        <li><strong>Safety:</strong> Lookup page without local data writes.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["db.php"]); ?>">Database Structure</a></dt>
    <dd>
      <p>Database Structure is the full-access SQL structure and export page for all database tables. It sorts them by foreign-key dependencies and displays normalized <code>SHOW CREATE TABLE</code> output.</p>
      <p>The page can download schema-only SQL or a backup containing structure and data. Copy buttons place direct schema and backup download URLs on the clipboard. The page reads metadata and table contents for export, but does not modify the database.</p>
      <ul>
        <li><strong>Scope:</strong> All database tables.</li>
        <li><strong>Export:</strong> Schema download and backup download.</li>
        <li><strong>Filtering:</strong> Quick filter over the structure table.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["dbinfo.php"]); ?>">Database Information</a></dt>
    <dd>
      <p>Database Information is a full-access diagnostic page for the current database connection. It queries server version, database name, server comment, character set, collation, SQL mode, time zone values, and PDO client/server attributes.</p>
      <p>This page is useful when comparing local, staging, and production environments because it shows both SQL-level values and PDO connection metadata in one table.</p>
      <ul>
        <li><strong>Data:</strong> Database server metadata and PDO attributes.</li>
        <li><strong>Filtering:</strong> Quick filter over name and value.</li>
        <li><strong>Safety:</strong> Read-only diagnostic page.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["authors.php"]); ?>">Authors</a></dt>
    <dd>
      <p>Authors lists authors cited in books and their linked page numbers from the source tables.</p>
      <p>The page is a maintenance overview for cited-author data. It does not describe portal users or application authors.</p>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["menu.php"]); ?>">Menu Management</a></dt>
    <dd>
      <p>Menu Management edits global menu rows stored in <code>fs_menu</code>. Items are grouped by path section so menu entries from different project areas are not mixed while ordering is changed.</p>
      <p>Full access can create, edit, delete, activate, deactivate, and reorder menu entries within their own section.</p>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["info.php"]); ?>">PHP Info and PHP Credits</a></dt>
    <dd>
      <p>PHP Info and PHP Credits is the full PHP diagnostic page. It is restricted to full access because it can expose detailed server and PHP configuration data.</p>
      <p>The selector can show PHP info sections such as general information, configuration, modules, environment, variables, license, or all info. It can also show PHP credits sections such as group, general, SAPI, modules, documentation, QA, or all credits. Output is loaded into an iframe by default and can be opened in a new window.</p>
      <ul>
        <li><strong>PHP Info:</strong> Selectable phpinfo sections.</li>
        <li><strong>PHP Credits:</strong> Selectable phpcredits sections.</li>
        <li><strong>Display:</strong> Iframe by default, separate window on request.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["env.php"]); ?>">PHP Environment</a></dt>
    <dd>
      <p>PHP Environment is a full-access diagnostic page for high-level runtime information. It shows PHP version, SAPI, operating system, architecture, time zones, locale, loaded configuration files, PDO drivers, resource limits, and selected security-related configuration values.</p>
      <p>It is more curated than PHP Info and is easier to scan when the question is whether the runtime has the expected PHP version, limits, session settings, PDO drivers, or file-loading configuration.</p>
      <ul>
        <li><strong>Categories:</strong> PHP environment, configuration files, PDO, resource limits, and security configuration.</li>
        <li><strong>Filtering:</strong> Quick filter over category, name, and value.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["ini.php"]); ?>">PHP Configuration Options</a></dt>
    <dd>
      <p>PHP Configuration Options lists values returned by <code>ini_get_all()</code>. It is a full-access diagnostic page for comparing global and local configuration values and checking each option's access level.</p>
      <p>Long string values are wrapped for table readability. The table is useful when a setting differs between the master configuration and the local runtime value used by this application.</p>
      <ul>
        <li><strong>Columns:</strong> Configuration option name, global value, local value, and access.</li>
        <li><strong>Filtering:</strong> Quick filter over all configuration rows.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["const.php"]); ?>">PHP Defined Constants</a></dt>
    <dd>
      <p>PHP Defined Constants lists constants returned by <code>get_defined_constants(true)</code>. It is a full-access diagnostic page for checking PHP core, extension, and application constants visible in the current runtime.</p>
      <p>Values are converted to readable strings, including booleans, nulls, arrays, special float values, and <code>PHP_EOL</code>. The table keeps the constant group, name, value, and PHP type separate for easier filtering.</p>
      <ul>
        <li><strong>Columns:</strong> Group, constant, value, and type.</li>
        <li><strong>Filtering:</strong> Quick filter over all visible constants.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["ext.php"]); ?>">PHP Loaded Extensions</a></dt>
    <dd>
      <p>PHP Loaded Extensions lists the currently loaded PHP extensions from <code>get_loaded_extensions()</code>. It is a full-access diagnostic page for confirming whether required extensions are available to this runtime.</p>
      <ul>
        <li><strong>Columns:</strong> Numeric row number and extension name.</li>
        <li><strong>Filtering:</strong> Quick filter over extension names.</li>
        <li><strong>Safety:</strong> Read-only diagnostic page.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["streams.php"]); ?>">PHP Stream Support</a></dt>
    <dd>
      <p>PHP Stream Support lists stream wrappers, transports, and filters available in the current PHP runtime. It is restricted to full access and is useful when debugging file, URL, compression, or transport behavior.</p>
      <ul>
        <li><strong>Types:</strong> Wrapper, transport, and filter.</li>
        <li><strong>Filtering:</strong> Quick filter over stream support rows.</li>
        <li><strong>Safety:</strong> Read-only diagnostic page.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["opcache.php"]); ?>">PHP OPcache Status</a></dt>
    <dd>
      <p>PHP OPcache Status shows OPcache status and configuration when OPcache functions are available. If OPcache is unavailable or disabled, the page reports that state instead of failing.</p>
      <p>Nested OPcache status and configuration values are flattened into category, name, value, and type rows so they can be filtered and compared easily.</p>
      <ul>
        <li><strong>Data:</strong> OPcache status and configuration.</li>
        <li><strong>Fallback:</strong> Reports unavailable or disabled OPcache clearly.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["ua.php"]); ?>">Eved Access Log</a></dt>
    <dd>
      <p>Eved Access Log is a full-access diagnostic page for recent browser fingerprint records. It shows request time, IP address, geo headers, parsed user agent, GPU, fonts, screen data, timezone, language, platform, plugins, and MIME types.</p>
      <p>The page can auto-refresh every five minutes and uses the quick filter for narrowing already rendered rows.</p>
      <ul>
        <li><strong>Rows:</strong> Recent access-log records.</li>
        <li><strong>Details:</strong> Browser, device, screen, language, and plugin data.</li>
        <li><strong>Refresh:</strong> Optional five-minute auto-refresh.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["request.php"]); ?>">PHP Request Variables</a></dt>
    <dd>
      <p>PHP Request Variables is a full-access diagnostic page for the current request. It prints <code>$_GET</code>, <code>$_POST</code>, <code>$_FILES</code>, <code>$_SERVER</code>, <code>$_SESSION</code>, and <code>$_COOKIE</code> in a filterable table.</p>
      <p>Because it can reveal session values, cookies, server paths, headers, and request data, it should remain restricted. Empty arrays are shown explicitly so it is clear that the source was checked.</p>
      <ul>
        <li><strong>Sources:</strong> GET, POST, FILES, SERVER, SESSION, and COOKIE.</li>
        <li><strong>Columns:</strong> Array, key, value, and type.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sScriptUrl; ?>">Help</a></dt>
    <dd>
      <p>Help is this read-only Dashboard help page. It documents Dashboard controls, access expectations, and Dashboard menu pages.</p>
      <p>The page itself is public, reads menu metadata for navigation, and does not modify data.</p>
    </dd>
  </dl>
  <h2>Česky</h2>
  <h3>Společné ovládání</h3>
  <dl class="lm-help-list">
    <dt>Účel</dt>
    <dd>
      <p>Dashboard je lokální údržbová část pro portálovou diagnostiku a administraci. Seskupuje stránky pro request, PHP runtime, databázi, menu a autory citované v knihách.</p>
      <p>Vstupní Dashboard zobrazuje aktuální přehled requestu od navigace přes zdroje IP adres, HTTP hlavičky, <code>$_SERVER</code>, <code>$_SESSION</code> až po <code>$_COOKIE</code>.</p>
    </dd>
    <dt>Menu</dt>
    <dd>
      <p>Stránky Dashboardu používají společné hamburger menu z aktivních řádků <code>fs_menu</code>, které patří do sekce Dashboard. Menu se otevře u tlačítka a označí aktuální stránku, pokud její cesta odpovídá aktivnímu řádku.</p>
    </dd>
    <dt>Alternativní styly</dt>
    <dd>
      <p>Stránky Dashboardu nabízejí styl Original a dvacet pojmenovaných alternativních stylů. Firefox je zpřístupňuje přes <strong>Zobrazit &gt; Styl stránky</strong>; vybraný název se ukládá do <code>localStorage</code> prohlížeče a obnovuje se na dalších stránkách se stejným názvem stylu.</p>
      <p>Změna stylu ovlivňuje pouze vzhled. Editor Snippet Board přebírá vybrané barvy bez nutnosti znovu načíst stránku.</p>
    </dd>
    <dt>Přístup</dt>
    <dd>
      <p>Nápověda vyžaduje portálový view přístup. Runtime diagnostiky, databázový export a správa menu vyžadují portálový full přístup, protože zobrazují serverovou konfiguraci, request data, session, cookies, databázová metadata, databázový obsah nebo umí upravovat globální menu.</p>
    </dd>
    <dt>Rychlý filtr</dt>
    <dd>
      <p>Tabulkové stránky Dashboardu používají rychlý filtr. V prohlížeči zužuje už vykreslené řádky, hodnotu ukládá do session podle stránky a id filtru, podporuje operátory AND a OR a používá F8 jako zkratku pro zaostření vstupu filtru.</p>
    </dd>
  </dl>
  <h3>Stránky v menu</h3>
  <dl class="lm-help-list">
    <dt><a href="<?php echo html($sBaseUrl); ?>">Dashboard</a></dt>
    <dd>
      <p>Dashboard je vstupní stránka sekce. Zobrazuje údržbový přehled requestu, který byl dříve dostupný v trusted větvi filmového vstupu, od Navigation až po PHP pole <code>$_COOKIE</code>.</p>
      <p>Stránka je pouze pro čtení. Slouží ke kontrole aktuálního requestu, hlaviček, session, cookies a serverových hodnot bez otevírání několika samostatných diagnostik.</p>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["req.php"]); ?>">Request Overview</a></dt>
    <dd>
      <p>Request Overview je kompaktní plain-text diagnostika aktuálního requestu. Vypisuje souhrn požadavku do preformátovaného bloku, aby ho šlo snadno kopírovat nebo porovnat bez otevření větší tabulkové stránky.</p>
      <p>Stránka je pouze pro čtení a slouží k rychlé kontrole requestu, serveru, session a cookies.</p>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["bt.php"]); ?>">Issue Tracker</a></dt>
    <dd>
      <p>Issue Tracker vypisuje údržbové issues s typem, stavem, prioritou, názvem, termínem, časem aktualizace a editačními akcemi. Full přístup může issues vytvářet, upravovat a mazat z dialogů stránky.</p>
      <p>Rychlý filtr zužuje vykreslenou tabulku issues. Pole Description se upravují jako textareas a používají běžné nastavení kontroly pravopisu v prohlížeči.</p>
      <ul>
        <li><strong>Řádky:</strong> Jedno issue na řádek.</li>
        <li><strong>Editace:</strong> Full přístup může issues vytvářet, upravovat a mazat.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr nad viditelným textem issues.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["bh.php"]); ?>">Business Hours</a></dt>
    <dd>
      <p>Business Hours spravuje zobrazované řádky otevírací doby. Stránka používá taby skupin business hours, karty jednotlivých záznamů a dialog New pro přidání další položky.</p>
      <p>Full přístup může přidávat a udržovat záznamy business hours. Stránka nechává editační vrstvu zaměřenou na viditelné karty místo zobrazení surových databázových tabulek.</p>
      <ul>
        <li><strong>Zobrazení:</strong> Taby a karty pro záznamy business hours.</li>
        <li><strong>Editace:</strong> Full přístup může přidávat a upravovat položky.</li>
        <li><strong>Dialogy:</strong> Používá sdílený projektový modální dialog.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["email.php"]); ?>">E-mail Domains</a></dt>
    <dd>
      <p>E-mail Domains ukazuje uživatele před zavináčem napříč spravovanými doménami. Domény jsou sloupce, lokální části adres jsou řádky a chybějící adresa se zobrazuje jako em dash, aby byly mezery viditelné.</p>
      <p>Dialog New přidává doménu a seznam lokálních uživatelů. Ukládá, jestli jde o mailbox, alias nebo forwarder, ignoruje duplicity, které už v databázi jsou, a buňky tabulky barevně odlišuje podle typu účtu.</p>
      <ul>
        <li><strong>Tabulka:</strong> Domény podle lokální části adresy.</li>
        <li><strong>Typy:</strong> Mailbox, alias a forwarder.</li>
        <li><strong>Vstup:</strong> Uživatelské tokeny lze oddělovat čárkami, středníky, bílými znaky nebo řádky.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["phone.php"]); ?>">Phone Accounts</a></dt>
    <dd>
      <p>Phone Accounts ukládá samostatné záznamy telefonních čísel s identifikátory SIM, PIN/PUK hodnotami, IMEI, poznámkou, datem a časem zaplacení, částkou, měnou a Telegram účtem. Nemá vazbu na subject, doménu ani jinou stránku.</p>
      <p>Telefonní čísla a Telegram účty se před uložením normalizují do stejných kanonických hodnot, jaké používají EX kontaktní stránky.</p>
      <ul>
        <li><strong>Řádky:</strong> Jeden phone account na řádek.</li>
        <li><strong>Pořadí:</strong> Nové řádky se přidávají nakonec a pořadí lze měnit šipkami.</li>
        <li><strong>Platba:</strong> Částky přijímají desetinnou tečku i čárku, měny vycházejí z KF měn a výchozí částka i měna pro nový záznam jsou uložené v session.</li>
        <li><strong>Editace:</strong> Full přístup může řádky vytvářet, upravovat a mazat.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr nad viditelným textem phone accounts.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["sb.php"]); ?>">Snippet Board</a></dt>
    <dd>
      <p>Snippet Board ukládá šest rich-text snippetů pro rychlé opakované použití. Každý slot se upravuje sdíleným rich-text editorem a ukládá se přímo ze stránky bez opuštění boardu.</p>
      <p>Board ukazuje stav changed, saving a saved v horní liště. Na kompaktních zařízeních se viditelný slot vybírá očíslovanými taby.</p>
      <ul>
        <li><strong>Sloty:</strong> Šest editovatelných snippet panelů.</li>
        <li><strong>Editor:</strong> Sdílené nastavení TinyMCE.</li>
        <li><strong>Stav:</strong> Změna a uložení jsou vidět v horní liště.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["char.php"]); ?>">Character Converter</a></dt>
    <dd>
      <p>Character Converter převádí Unicode znak mezi samotným znakem, decimálním code pointem, hexadecimálním code pointem, decimální HTML entitou, hexadecimální HTML entitou a pojmenovanou HTML entitou, pokud existuje.</p>
      <p>Paleta nabízí často používané znaky a varianty. Tlačítka Text a Emoji volí preferovanou prezentaci u znaků, které podporují variation selectors.</p>
      <ul>
        <li><strong>Převod:</strong> Znak, code points a HTML entity.</li>
        <li><strong>Paleta:</strong> Běžné Unicode znaky s detaily v titulku.</li>
        <li><strong>Bezpečnost:</strong> Převod v prohlížeči bez databázových zápisů.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["fancy.php"]); ?>">Fancy Text</a></dt>
    <dd>
      <p>Fancy Text převádí plain nebo už stylovaný Unicode text do vybraných Unicode mathematical alphanumeric stylů. Levá textarea obsahuje zdrojový text a pravá textarea ukazuje znovu vygenerovaný stylovaný výstup.</p>
      <p>Select stylu je v horním řádku ovládacích prvků. Změna stylu přegeneruje výstup a nepodporované znaky zůstanou beze změny.</p>
      <ul>
        <li><strong>Vstup:</strong> Plain nebo stylovaný Unicode text.</li>
        <li><strong>Výstup:</strong> Unicode stylovaný text.</li>
        <li><strong>Rozložení:</strong> Textareas se přepínají mezi svislým a vedle sebe rozložením podle dostupného prostoru.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["ares.php"]); ?>">ARES Lookup</a></dt>
    <dd>
      <p>ARES Lookup hledá česká data ekonomických subjektů podle IČO, obchodního jména, sídla, právní formy, finančního úřadu a CZ-NACE. Přímá shoda podle IČO ukazuje detailní tabulku; širší hledání ukazuje výsledkové řádky.</p>
      <p>Z pohledu lokální databáze je formulář pouze čtecí. Vrácené hodnoty ARES vypisuje v tabulkách, které mohou na malých zařízeních používat kompaktní styl tabulky.</p>
      <ul>
        <li><strong>Vyhledávací pole:</strong> IČO, obchodní jméno, sídlo, právní forma, finanční úřad a CZ-NACE.</li>
        <li><strong>Výstup:</strong> Detail nebo tabulka výsledků hledání.</li>
        <li><strong>Bezpečnost:</strong> Vyhledávací stránka bez lokálních datových zápisů.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["whois.php"]); ?>">Domain/IP Lookup</a></dt>
    <dd>
      <p>Domain/IP Lookup kontroluje doménové jméno nebo IP adresu a zobrazuje registrační, reverse DNS a DNS informace, pokud jsou dostupné.</p>
      <p>Horní řádek používá stejné kompaktní rozložení ovládacích prvků jako ostatní lookup stránky: menu, label, vstup a tlačítko Lookup. Výsledkové tabulky oddělují detailní hodnoty od DNS řádků, aby šly samostatně kopírovat nebo kontrolovat.</p>
      <ul>
        <li><strong>Vstup:</strong> Doménové jméno nebo IP adresa.</li>
        <li><strong>Výstup:</strong> Detail domény, reverse DNS a DNS záznamy.</li>
        <li><strong>Bezpečnost:</strong> Vyhledávací stránka bez lokálních datových zápisů.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["db.php"]); ?>">Database Structure</a></dt>
    <dd>
      <p>Database Structure je stránka s SQL strukturou a exportem všech databázových tabulek s full přístupem. Řadí je podle závislostí cizích klíčů a zobrazuje normalizovaný výstup <code>SHOW CREATE TABLE</code>.</p>
      <p>Stránka umí stáhnout SQL pouze se schématem nebo zálohu obsahující strukturu i data. Kopírovací tlačítka ukládají do schránky přímé odkazy pro stažení schématu a zálohy. Stránka čte metadata a pro export i obsah tabulek, ale databázi neupravuje.</p>
      <ul>
        <li><strong>Rozsah:</strong> Všechny databázové tabulky.</li>
        <li><strong>Export:</strong> Stažení schématu a stažení zálohy.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr nad tabulkou struktury.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["dbinfo.php"]); ?>">Database Information</a></dt>
    <dd>
      <p>Database Information je diagnostická stránka aktuálního databázového připojení s full přístupem. Dotazuje se na verzi serveru, název databáze, komentář serveru, znakovou sadu, collation, SQL mode, časové zóny a atributy PDO klienta a serveru.</p>
      <p>Stránka je užitečná při porovnání lokálního, staging a produkčního prostředí, protože v jedné tabulce ukazuje SQL hodnoty i metadata PDO připojení.</p>
      <ul>
        <li><strong>Data:</strong> Metadata databázového serveru a atributy PDO.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes název a hodnotu.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí diagnostická stránka.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["authors.php"]); ?>">Authors</a></dt>
    <dd>
      <p>Authors vypisuje autory citované v knihách a jejich navázaná čísla stran ze zdrojových tabulek.</p>
      <p>Jde o údržbový přehled dat citovaných autorů. Nejde o portálové uživatele ani autory aplikace.</p>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["menu.php"]); ?>">Menu Management</a></dt>
    <dd>
      <p>Menu Management spravuje globální řádky menu uložené v <code>fs_menu</code>. Položky jsou seskupené podle části cesty, aby se při změně pořadí nemíchaly položky z různých částí projektu.</p>
      <p>Full přístup může položky menu vytvářet, upravovat, mazat, aktivovat, deaktivovat a přesouvat v pořadí v rámci jejich vlastní sekce.</p>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["info.php"]); ?>">PHP Info and PHP Credits</a></dt>
    <dd>
      <p>PHP Info and PHP Credits je plná diagnostická stránka PHP. Je omezená na full přístup, protože může zobrazit detailní serverovou a PHP konfiguraci.</p>
      <p>Selector umí zobrazit phpinfo sekce jako general information, configuration, modules, environment, variables, license nebo all info. Umí také zobrazit PHP credits sekce jako group, general, SAPI, modules, documentation, QA nebo all credits. Výstup se standardně načítá do iframe a lze ho otevřít i v novém okně.</p>
      <ul>
        <li><strong>PHP Info:</strong> Volitelné phpinfo sekce.</li>
        <li><strong>PHP Credits:</strong> Volitelné phpcredits sekce.</li>
        <li><strong>Zobrazení:</strong> Standardně iframe, na vyžádání samostatné okno.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["env.php"]); ?>">PHP Environment</a></dt>
    <dd>
      <p>PHP Environment je diagnostická stránka s přehledem runtime s full přístupem. Ukazuje verzi PHP, SAPI, operační systém, architekturu, časové zóny, locale, načtené konfigurační soubory, PDO drivery, limity prostředků a vybrané bezpečnostní konfigurační hodnoty.</p>
      <p>Je stručnější než PHP Info a lépe se čte, když je potřeba ověřit verzi PHP, limity, session nastavení, PDO drivery nebo konfiguraci načítání souborů.</p>
      <ul>
        <li><strong>Kategorie:</strong> PHP environment, konfigurační soubory, PDO, resource limits a security configuration.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes kategorii, název a hodnotu.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["ini.php"]); ?>">PHP Configuration Options</a></dt>
    <dd>
      <p>PHP Configuration Options vypisuje hodnoty vrácené funkcí <code>ini_get_all()</code>. Je to diagnostická stránka s full přístupem, která slouží k porovnání globálních a lokálních konfiguračních hodnot a kontrole access levelu každé volby.</p>
      <p>Dlouhé textové hodnoty se zalamují kvůli čitelnosti tabulky. Stránka je užitečná, když se nastavení liší mezi master konfigurací a lokální runtime hodnotou použitou aplikací.</p>
      <ul>
        <li><strong>Sloupce:</strong> Název volby, globální hodnota, lokální hodnota a access.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes všechny konfigurační řádky.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["const.php"]); ?>">PHP Defined Constants</a></dt>
    <dd>
      <p>PHP Defined Constants vypisuje konstanty vrácené funkcí <code>get_defined_constants(true)</code>. Je to diagnostická stránka s full přístupem určená ke kontrole PHP core, extension a aplikačních konstant viditelných v aktuálním běhu.</p>
      <p>Hodnoty se převádějí na čitelné řetězce, včetně boolean, null, polí, speciálních float hodnot a <code>PHP_EOL</code>. Tabulka odděluje skupinu konstanty, název, hodnotu a PHP typ pro snazší filtrování.</p>
      <ul>
        <li><strong>Sloupce:</strong> Skupina, konstanta, hodnota a typ.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes všechny viditelné konstanty.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["ext.php"]); ?>">PHP Loaded Extensions</a></dt>
    <dd>
      <p>PHP Loaded Extensions vypisuje aktuálně načtená PHP rozšíření z <code>get_loaded_extensions()</code>. Jde o diagnostickou stránku s full přístupem určenou ke kontrole, zda má runtime k dispozici potřebná rozšíření.</p>
      <ul>
        <li><strong>Sloupce:</strong> Číslo řádku a název rozšíření.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes názvy rozšíření.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí diagnostická stránka.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["streams.php"]); ?>">PHP Stream Support</a></dt>
    <dd>
      <p>PHP Stream Support vypisuje stream wrappers, transports a filters dostupné v aktuálním PHP runtime. Je omezený na full přístup a hodí se při ladění práce se soubory, URL, kompresí nebo transporty.</p>
      <ul>
        <li><strong>Typy:</strong> Wrapper, transport a filter.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes řádky stream support.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí diagnostická stránka.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["opcache.php"]); ?>">PHP OPcache Status</a></dt>
    <dd>
      <p>PHP OPcache Status ukazuje stav a konfiguraci OPcache, pokud jsou OPcache funkce dostupné. Pokud OPcache dostupná není nebo je vypnutá, stránka tento stav vypíše místo selhání.</p>
      <p>Vnořené hodnoty stavu a konfigurace OPcache se převádějí do řádků kategorie, název, hodnota a typ, aby je šlo snadno filtrovat a porovnávat.</p>
      <ul>
        <li><strong>Data:</strong> Stav a konfigurace OPcache.</li>
        <li><strong>Fallback:</strong> Nedostupná nebo vypnutá OPcache se vypíše srozumitelně.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["ua.php"]); ?>">Eved Access Log</a></dt>
    <dd>
      <p>Eved Access Log je diagnostická stránka s full přístupem pro poslední browser fingerprint záznamy. Ukazuje čas requestu, IP adresu, geo hlavičky, parsovaný user agent, GPU, fonty, display, časové pásmo, jazyk, platformu, pluginy a MIME typy.</p>
      <p>Stránka může provádět auto-refresh každých pět minut a používá rychlý filtr pro zúžení už vykreslených řádků.</p>
      <ul>
        <li><strong>Řádky:</strong> Poslední záznamy access logu.</li>
        <li><strong>Detaily:</strong> Prohlížeč, zařízení, display, jazyk a data pluginů.</li>
        <li><strong>Refresh:</strong> Volitelný pětiminutový auto-refresh.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo html($aMenuItemUrls["request.php"]); ?>">PHP Request Variables</a></dt>
    <dd>
      <p>PHP Request Variables je diagnostická stránka aktuálního požadavku s full přístupem. Vypisuje <code>$_GET</code>, <code>$_POST</code>, <code>$_FILES</code>, <code>$_SERVER</code>, <code>$_SESSION</code> a <code>$_COOKIE</code> ve filtrovatelné tabulce.</p>
      <p>Protože může odhalit session hodnoty, cookies, serverové cesty, hlavičky a data požadavku, má zůstat omezená. Prázdná pole se vypisují explicitně, aby bylo jasné, že zdroj byl zkontrolovaný.</p>
      <ul>
        <li><strong>Zdroje:</strong> GET, POST, FILES, SERVER, SESSION a COOKIE.</li>
        <li><strong>Sloupce:</strong> Pole, klíč, hodnota a typ.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sScriptUrl; ?>">Help</a></dt>
    <dd>
      <p>Help je tato pouze čtecí nápověda Dashboardu. Dokumentuje ovládání Dashboardu, očekávaný přístup a stránky v menu Dashboardu.</p>
      <p>Samotná stránka je veřejná, čte metadata menu kvůli navigaci a neupravuje data.</p>
    </dd>
  </dl>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>" integrity="sha384-zi9FpsGST+GJEbRKC9fbVcqxHcE9U9rYiXnYRpsOJC6WiDPEFPeUOmPlmaMCUYcm"></script>
</body>
</html>
