<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireViewAccess($aAllowedIps, "portal", "lm_csrf_token");


$sTitle = getPageTitleText("Dashboard Help", $aAllowedIps);
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
    <dt>Access</dt>
    <dd>
      <p>Read-only help uses portal view access. Runtime diagnostics, database export, and menu administration require portal full access because they expose server configuration, request data, session values, cookies, database metadata, database content, or can edit global menu rows.</p>
    </dd>
    <dt>Quick Filter</dt>
    <dd>
      <p>Dashboard table pages use the quick filter. It narrows already rendered rows in the browser, stores the value per page and filter id in the session, and supports AND and OR operators.</p>
    </dd>
  </dl>
  <h3>Menu Pages</h3>
  <dl class="lm-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Dashboard</a></dt>
    <dd>
      <p>Dashboard is the section entry point. It shows the maintenance request overview that used to be available from the trusted branch of the film entry point, from Navigation through the PHP <code>$_COOKIE</code> array.</p>
      <p>The page is read-only. It is meant for checking the current request, headers, session, cookies, and server values without opening several separate diagnostic pages.</p>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
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
    <dt><a href="<?php echo $sBaseUrl; ?>dbinfo.php">Database Information</a></dt>
    <dd>
      <p>Database Information is a full-access diagnostic page for the current database connection. It queries server version, database name, server comment, character set, collation, SQL mode, time zone values, and PDO client/server attributes.</p>
      <p>This page is useful when comparing local, staging, and production environments because it shows both SQL-level values and PDO connection metadata in one table.</p>
      <ul>
        <li><strong>Data:</strong> Database server metadata and PDO attributes.</li>
        <li><strong>Filtering:</strong> Quick filter over name and value.</li>
        <li><strong>Safety:</strong> Read-only diagnostic page.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>authors.php">Authors</a></dt>
    <dd>
      <p>Authors lists authors cited in books and their linked page numbers from the source tables.</p>
      <p>The page is a maintenance overview for cited-author data. It does not describe portal users or application authors.</p>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>menu.php">Menu Management</a></dt>
    <dd>
      <p>Menu Management edits global menu rows stored in <code>fs_menu</code>. Items are grouped by path section so menu entries from different project areas are not mixed while ordering is changed.</p>
      <p>Full access can create, edit, delete, activate, deactivate, and reorder menu entries within their own section.</p>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>info.php">PHP Info and PHP Credits</a></dt>
    <dd>
      <p>PHP Info and PHP Credits is the full PHP diagnostic page. It is restricted to full access because it can expose detailed server and PHP configuration data.</p>
      <p>The selector can show PHP info sections such as general information, configuration, modules, environment, variables, license, or all info. It can also show PHP credits sections such as group, general, SAPI, modules, documentation, QA, or all credits. Output is loaded into an iframe by default and can be opened in a new window.</p>
      <ul>
        <li><strong>PHP Info:</strong> Selectable phpinfo sections.</li>
        <li><strong>PHP Credits:</strong> Selectable phpcredits sections.</li>
        <li><strong>Display:</strong> Iframe by default, separate window on request.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>env.php">PHP Environment</a></dt>
    <dd>
      <p>PHP Environment is a full-access diagnostic page for high-level runtime information. It shows PHP version, SAPI, operating system, architecture, time zones, locale, loaded configuration files, PDO drivers, resource limits, and selected security-related configuration values.</p>
      <p>It is more curated than PHP Info and is easier to scan when the question is whether the runtime has the expected PHP version, limits, session settings, PDO drivers, or file-loading configuration.</p>
      <ul>
        <li><strong>Categories:</strong> PHP environment, configuration files, PDO, resource limits, and security configuration.</li>
        <li><strong>Filtering:</strong> Quick filter over category, name, and value.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ini.php">PHP Configuration Options</a></dt>
    <dd>
      <p>PHP Configuration Options lists values returned by <code>ini_get_all()</code>. It is a full-access diagnostic page for comparing global and local configuration values and checking each option's access level.</p>
      <p>Long string values are wrapped for table readability. The table is useful when a setting differs between the master configuration and the local runtime value used by this application.</p>
      <ul>
        <li><strong>Columns:</strong> Configuration option name, global value, local value, and access.</li>
        <li><strong>Filtering:</strong> Quick filter over all configuration rows.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>const.php">PHP Defined Constants</a></dt>
    <dd>
      <p>PHP Defined Constants lists constants returned by <code>get_defined_constants(true)</code>. It is a full-access diagnostic page for checking PHP core, extension, and application constants visible in the current runtime.</p>
      <p>Values are converted to readable strings, including booleans, nulls, arrays, special float values, and <code>PHP_EOL</code>. The table keeps the constant group, name, value, and PHP type separate for easier filtering.</p>
      <ul>
        <li><strong>Columns:</strong> Group, constant, value, and type.</li>
        <li><strong>Filtering:</strong> Quick filter over all visible constants.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ext.php">PHP Loaded Extensions</a></dt>
    <dd>
      <p>PHP Loaded Extensions lists the currently loaded PHP extensions from <code>get_loaded_extensions()</code>. It is a full-access diagnostic page for confirming whether required extensions are available to this runtime.</p>
      <ul>
        <li><strong>Columns:</strong> Numeric row number and extension name.</li>
        <li><strong>Filtering:</strong> Quick filter over extension names.</li>
        <li><strong>Safety:</strong> Read-only diagnostic page.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>streams.php">PHP Stream Support</a></dt>
    <dd>
      <p>PHP Stream Support lists stream wrappers, transports, and filters available in the current PHP runtime. It is restricted to full access and is useful when debugging file, URL, compression, or transport behavior.</p>
      <ul>
        <li><strong>Types:</strong> Wrapper, transport, and filter.</li>
        <li><strong>Filtering:</strong> Quick filter over stream support rows.</li>
        <li><strong>Safety:</strong> Read-only diagnostic page.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>opcache.php">PHP OPcache Status</a></dt>
    <dd>
      <p>PHP OPcache Status shows OPcache status and configuration when OPcache functions are available. If OPcache is unavailable or disabled, the page reports that state instead of failing.</p>
      <p>Nested OPcache status and configuration values are flattened into category, name, value, and type rows so they can be filtered and compared easily.</p>
      <ul>
        <li><strong>Data:</strong> OPcache status and configuration.</li>
        <li><strong>Fallback:</strong> Reports unavailable or disabled OPcache clearly.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>request.php">PHP Request Variables</a></dt>
    <dd>
      <p>PHP Request Variables is a full-access diagnostic page for the current request. It prints <code>$_GET</code>, <code>$_POST</code>, <code>$_FILES</code>, <code>$_SERVER</code>, <code>$_SESSION</code>, and <code>$_COOKIE</code> in a filterable table.</p>
      <p>Because it can reveal session values, cookies, server paths, headers, and request data, it should remain restricted. Empty arrays are shown explicitly so it is clear that the source was checked.</p>
      <ul>
        <li><strong>Sources:</strong> GET, POST, FILES, SERVER, SESSION, and COOKIE.</li>
        <li><strong>Columns:</strong> Array, key, value, and type.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">Help</a></dt>
    <dd>
      <p>Help is this read-only Dashboard help page. It documents Dashboard controls, access expectations, and Dashboard menu pages.</p>
      <p>The page itself requires only portal view access and does not modify data.</p>
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
    <dt>Přístup</dt>
    <dd>
      <p>Nápověda vyžaduje portálový view přístup. Runtime diagnostiky, databázový export a správa menu vyžadují portálový full přístup, protože zobrazují serverovou konfiguraci, request data, session, cookies, databázová metadata, databázový obsah nebo umí upravovat globální menu.</p>
    </dd>
    <dt>Rychlý filtr</dt>
    <dd>
      <p>Tabulkové stránky Dashboardu používají rychlý filtr. V prohlížeči zužuje už vykreslené řádky, hodnotu ukládá do session podle stránky a id filtru a podporuje operátory AND a OR.</p>
    </dd>
  </dl>
  <h3>Stránky v menu</h3>
  <dl class="lm-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Dashboard</a></dt>
    <dd>
      <p>Dashboard je vstupní stránka sekce. Zobrazuje údržbový přehled requestu, který byl dříve dostupný v trusted větvi filmového vstupu, od Navigation až po PHP pole <code>$_COOKIE</code>.</p>
      <p>Stránka je pouze pro čtení. Slouží ke kontrole aktuálního requestu, hlaviček, session, cookies a serverových hodnot bez otevírání několika samostatných diagnostik.</p>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
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
    <dt><a href="<?php echo $sBaseUrl; ?>dbinfo.php">Database Information</a></dt>
    <dd>
      <p>Database Information je diagnostická stránka aktuálního databázového připojení s full přístupem. Dotazuje se na verzi serveru, název databáze, komentář serveru, znakovou sadu, collation, SQL mode, časové zóny a atributy PDO klienta a serveru.</p>
      <p>Stránka je užitečná při porovnání lokálního, staging a produkčního prostředí, protože v jedné tabulce ukazuje SQL hodnoty i metadata PDO připojení.</p>
      <ul>
        <li><strong>Data:</strong> Metadata databázového serveru a atributy PDO.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes název a hodnotu.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí diagnostická stránka.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>authors.php">Authors</a></dt>
    <dd>
      <p>Authors vypisuje autory citované v knihách a jejich navázaná čísla stran ze zdrojových tabulek.</p>
      <p>Jde o údržbový přehled dat citovaných autorů. Nejde o portálové uživatele ani autory aplikace.</p>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>menu.php">Menu Management</a></dt>
    <dd>
      <p>Menu Management spravuje globální řádky menu uložené v <code>fs_menu</code>. Položky jsou seskupené podle části cesty, aby se při změně pořadí nemíchaly položky z různých částí projektu.</p>
      <p>Full přístup může položky menu vytvářet, upravovat, mazat, aktivovat, deaktivovat a přesouvat v pořadí v rámci jejich vlastní sekce.</p>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>info.php">PHP Info and PHP Credits</a></dt>
    <dd>
      <p>PHP Info and PHP Credits je plná diagnostická stránka PHP. Je omezená na full přístup, protože může zobrazit detailní serverovou a PHP konfiguraci.</p>
      <p>Selector umí zobrazit phpinfo sekce jako general information, configuration, modules, environment, variables, license nebo all info. Umí také zobrazit PHP credits sekce jako group, general, SAPI, modules, documentation, QA nebo all credits. Výstup se standardně načítá do iframe a lze ho otevřít i v novém okně.</p>
      <ul>
        <li><strong>PHP Info:</strong> Volitelné phpinfo sekce.</li>
        <li><strong>PHP Credits:</strong> Volitelné phpcredits sekce.</li>
        <li><strong>Zobrazení:</strong> Standardně iframe, na vyžádání samostatné okno.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>env.php">PHP Environment</a></dt>
    <dd>
      <p>PHP Environment je diagnostická stránka s přehledem runtime s full přístupem. Ukazuje verzi PHP, SAPI, operační systém, architekturu, časové zóny, locale, načtené konfigurační soubory, PDO drivery, limity prostředků a vybrané bezpečnostní konfigurační hodnoty.</p>
      <p>Je stručnější než PHP Info a lépe se čte, když je potřeba ověřit verzi PHP, limity, session nastavení, PDO drivery nebo konfiguraci načítání souborů.</p>
      <ul>
        <li><strong>Kategorie:</strong> PHP environment, konfigurační soubory, PDO, resource limits a security configuration.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes kategorii, název a hodnotu.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ini.php">PHP Configuration Options</a></dt>
    <dd>
      <p>PHP Configuration Options vypisuje hodnoty vrácené funkcí <code>ini_get_all()</code>. Je to diagnostická stránka s full přístupem, která slouží k porovnání globálních a lokálních konfiguračních hodnot a kontrole access levelu každé volby.</p>
      <p>Dlouhé textové hodnoty se zalamují kvůli čitelnosti tabulky. Stránka je užitečná, když se nastavení liší mezi master konfigurací a lokální runtime hodnotou použitou aplikací.</p>
      <ul>
        <li><strong>Sloupce:</strong> Název volby, globální hodnota, lokální hodnota a access.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes všechny konfigurační řádky.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>const.php">PHP Defined Constants</a></dt>
    <dd>
      <p>PHP Defined Constants vypisuje konstanty vrácené funkcí <code>get_defined_constants(true)</code>. Je to diagnostická stránka s full přístupem určená ke kontrole PHP core, extension a aplikačních konstant viditelných v aktuálním běhu.</p>
      <p>Hodnoty se převádějí na čitelné řetězce, včetně boolean, null, polí, speciálních float hodnot a <code>PHP_EOL</code>. Tabulka odděluje skupinu konstanty, název, hodnotu a PHP typ pro snazší filtrování.</p>
      <ul>
        <li><strong>Sloupce:</strong> Skupina, konstanta, hodnota a typ.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes všechny viditelné konstanty.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ext.php">PHP Loaded Extensions</a></dt>
    <dd>
      <p>PHP Loaded Extensions vypisuje aktuálně načtená PHP rozšíření z <code>get_loaded_extensions()</code>. Jde o diagnostickou stránku s full přístupem určenou ke kontrole, zda má runtime k dispozici potřebná rozšíření.</p>
      <ul>
        <li><strong>Sloupce:</strong> Číslo řádku a název rozšíření.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes názvy rozšíření.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí diagnostická stránka.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>streams.php">PHP Stream Support</a></dt>
    <dd>
      <p>PHP Stream Support vypisuje stream wrappers, transports a filters dostupné v aktuálním PHP runtime. Je omezený na full přístup a hodí se při ladění práce se soubory, URL, kompresí nebo transporty.</p>
      <ul>
        <li><strong>Typy:</strong> Wrapper, transport a filter.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes řádky stream support.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí diagnostická stránka.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>opcache.php">PHP OPcache Status</a></dt>
    <dd>
      <p>PHP OPcache Status ukazuje stav a konfiguraci OPcache, pokud jsou OPcache funkce dostupné. Pokud OPcache dostupná není nebo je vypnutá, stránka tento stav vypíše místo selhání.</p>
      <p>Vnořené hodnoty stavu a konfigurace OPcache se převádějí do řádků kategorie, název, hodnota a typ, aby je šlo snadno filtrovat a porovnávat.</p>
      <ul>
        <li><strong>Data:</strong> Stav a konfigurace OPcache.</li>
        <li><strong>Fallback:</strong> Nedostupná nebo vypnutá OPcache se vypíše srozumitelně.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>request.php">PHP Request Variables</a></dt>
    <dd>
      <p>PHP Request Variables je diagnostická stránka aktuálního požadavku s full přístupem. Vypisuje <code>$_GET</code>, <code>$_POST</code>, <code>$_FILES</code>, <code>$_SERVER</code>, <code>$_SESSION</code> a <code>$_COOKIE</code> ve filtrovatelné tabulce.</p>
      <p>Protože může odhalit session hodnoty, cookies, serverové cesty, hlavičky a data požadavku, má zůstat omezená. Prázdná pole se vypisují explicitně, aby bylo jasné, že zdroj byl zkontrolovaný.</p>
      <ul>
        <li><strong>Zdroje:</strong> GET, POST, FILES, SERVER, SESSION a COOKIE.</li>
        <li><strong>Sloupce:</strong> Pole, klíč, hodnota a typ.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">Help</a></dt>
    <dd>
      <p>Help je tato pouze čtecí nápověda Dashboardu. Dokumentuje ovládání Dashboardu, očekávaný přístup a stránky v menu Dashboardu.</p>
      <p>Samotná stránka vyžaduje jen portálový view přístup a neupravuje data.</p>
    </dd>
  </dl>
  <script type="text/javascript" src="<?php echo html($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))); ?>"></script>
</body>
</html>
