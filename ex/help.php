<?php

include "main.php";


requireExViewAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="author" content="Petr Červinka &lt;cervinka@fortsoft.cz&gt;">
  <meta name="contact" content="cervinka@fortsoft.cz">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#FFD8BB">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo nxHtml(getExPageTitleText("Portal Help", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php nxRenderExMenu(); ?>
  </p>
  <h1>Portal Help</h1>
  <h2>US English</h2>
  <dl class="portal-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Contacts</a></dt>
    <dd>Shows the compact contacts overview. It is read-only and intended for quick scanning, copying, and opening contact links. The quick filter works directly on the visible table text. Settings control whether inactive subjects and inactive subject items are shown, and the country display options are shared across EX pages that use the same global country settings.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>list.php">Subjects</a></dt>
    <dd>Shows the full subject list and is the main editor for subject data. It manages subjects, person names, nicknames, postal addresses, contacts, groups, notes, and portal access. The complex filter can combine conditions over subject fields, groups, contacts, notes, address fields, booleans, dates, and empty values. Actions shown inside table cells apply to the concrete item in that cell; subject-level actions apply to the whole subject row.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>bd.php">Birthdays</a></dt>
    <dd>Shows birthdays in the configured birthday window: two days back, today, and seventeen days forward. The In column shows the day offset and the service action. Marking a birthday as served stores the service timestamp, removes the row from the current list, and also marks the subject interaction as served. Existing subject data can be edited or deleted here, but new subject items are not added from this page.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>inter.php">Interactions</a></dt>
    <dd>Shows subjects whose interaction has not been served for more than two months and are due within the interaction window. The list is ordered with the longest unserved contact first. The In column contains the day offset and the service action; serving an interaction removes the row. This page can edit or delete existing subject data, but it does not create new subject items.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>addresses.php">Addresses</a></dt>
    <dd>Shows exact shared postal addresses and the subjects using them. The address cell uses the same one-line display as other pages, while clipboard copying uses the multiline postal form. Settings for this page control inactive addresses and inactive subjects above the separator; the country checkboxes below the separator are global. Actions in the address cell edit or delete the exact shared postal address for all matching subjects while preserving subject-specific flags such as address type, primary, active, and note. Actions in the Subject cell edit or delete only that subject's address; if the postal form changes, the record can move to another shared-address row. Subject highlighting and the primary star belong to the subject-address row, not to the shared address cell.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>groups.php">Groups</a></dt>
    <dd>Manages subject groups. Groups can be created, renamed, deleted, reordered, and merged. Merge is target-based: select the destination group first, then choose source groups whose users will be moved into the selected target group. Optional source deletion removes the merged groups after the move. Group order is used when groups are displayed in subject tables.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ctypes.php">Contact Types</a></dt>
    <dd>Manages contact types, their visible names, active state, and order. The internal technical string is generated from the visible name and is not shown during normal editing. Ordering affects the New Contact and Edit Contact selectors and also the order of contact values displayed in subject table cells. Contact types can be merged in the same target-based manner as groups.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
    <dd>Shows database tables and columns in a compact tabular structure view. It is for inspecting the current database structure and does not edit data. This page is one of the places where database metadata queries are expected.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>schema.php">Database Schema</a></dt>
    <dd>Shows the visual database schema diagram with tables, columns, keys, and relationships. It is intended for checking table layout, foreign-key relationships, column types, nullability, and generated schema presentation.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>check.php">Database Consistency</a></dt>
    <dd>Runs consistency checks for broken or suspicious records, such as rows linked to missing subjects or unassigned contact records kept for review. The page is diagnostic and reports what should be reviewed in the database.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>exlib.php">External Libraries</a></dt>
    <dd>Lists files in the EX external library directory in an ls-like order. The table shows permissions, owner, downloaded timestamp from the file timestamp on disk, and file name. It is used to verify bundled metadata and external library files without reading them from the network.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>info.php">PHP Info and PHP Credits</a></dt>
    <dd>Shows PHP configuration and credits information for administrative diagnostics. It is intended for environment verification, not for editing portal data.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">Portal Help</a></dt>
    <dd>Shows this bilingual help page. It describes the purpose of each menu page and the important differences between shared actions, subject-specific actions, settings, filters, and diagnostics.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>demo.php" target="_blank" rel="noopener noreferrer">Demo Subjects</a></dt>
    <dd>Opens the demo subject page with sample data in a new window, matching the menu target. It is meant for testing table layout, filters, settings, modals, action rendering, contact formatting, and address display without changing real records.</dd>
  </dl>
  <h2>Česky</h2>
  <dl class="portal-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Contacts</a></dt>
    <dd>Zobrazuje kompaktní přehled kontaktů. Stránka je jen pro čtení a slouží k rychlému procházení, kopírování a otevírání kontaktních odkazů. Rychlý filtr pracuje přímo s textem viditelným v tabulce. Nastavení určuje zobrazení neaktivních subjektů a jejich neaktivních položek; volby zobrazení státu jsou sdílené globálně napříč stránkami EX, které je používají.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>list.php">Subjects</a></dt>
    <dd>Zobrazuje úplný výpis subjektů a je hlavním editorem dat. Spravuje subjekty, osobní jména, přezdívky, poštovní adresy, kontakty, skupiny, poznámky a portálový přístup. Komplexní filtr umí kombinovat podmínky nad poli subjektu, skupinami, kontakty, poznámkami, adresními poli, logickými hodnotami, daty i prázdnými hodnotami. Akce zobrazené uvnitř buněk tabulky platí pro konkrétní položku v dané buňce; akce subjektu platí pro celý řádek subjektu.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>bd.php">Birthdays</a></dt>
    <dd>Zobrazuje narozeniny v nastaveném okně: dva dny zpět, aktuální den a sedmnáct dní dopředu. Sloupec In obsahuje odchylku ve dnech a akci obsloužení. Označení narozenin jako obsloužených uloží čas obsloužení, odstraní řádek z aktuálního výpisu a současně obslouží i interakci daného subjektu. Existující údaje subjektu lze upravit nebo smazat, nové položky se z této stránky nepřidávají.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>inter.php">Interactions</a></dt>
    <dd>Zobrazuje subjekty, u kterých nebyla obsloužena interakce déle než dva měsíce a spadají do interakčního okna. Nejdříve je subjekt, se kterým nebyla obsloužena komunikace nejdéle. Sloupec In obsahuje odchylku ve dnech a akci obsloužení; po obsloužení interakce řádek zmizí. Stránka umožňuje upravit nebo smazat existující data subjektu, ale nevytváří nové položky.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>addresses.php">Addresses</a></dt>
    <dd>Zobrazuje exaktně shodné sdílené poštovní adresy a subjekty, které je používají. Adresní buňka používá stejný jednořádkový tvar jako ostatní stránky, zatímco kopírování do schránky používá víceřádkový poštovní tvar. Nastavení této stránky nad oddělovačem řídí neaktivní adresy a neaktivní subjekty; volby států pod oddělovačem jsou globální. Akce v buňce adresy upravují nebo mažou exaktně shodnou sdílenou poštovní adresu u všech odpovídajících subjektů a zachovávají subjektové příznaky jako typ adresy, primární, aktivní a poznámka. Akce v buňce Subject upravují nebo mažou jen adresu konkrétního subjektu; pokud se změní poštovní tvar, záznam se může přesunout do jiného řádku sdílené adresy. Podbarvení subjektu a hvězdička primární adresy patří řádku adresy subjektu, nikoli sdílené buňce adresy.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>groups.php">Groups</a></dt>
    <dd>Spravuje skupiny subjektů. Skupiny lze vytvářet, přejmenovat, smazat, seřadit a sloučit. Slučování je cílové: nejprve se vybere cílová skupina a potom zdrojové skupiny, jejichž uživatelé se přesunou do vybrané cílové skupiny. Volitelné smazání zdrojů odstraní sloučené skupiny po přesunu. Nastavené pořadí skupin se používá při zobrazení skupin v tabulkách subjektů.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ctypes.php">Contact Types</a></dt>
    <dd>Spravuje typy kontaktů, jejich viditelné názvy, aktivitu a pořadí. Interní technický řetězec se generuje z viditelného názvu a při běžné editaci není zobrazen. Pořadí ovlivňuje selecty New Contact a Edit Contact i pořadí kontaktních hodnot v buňkách tabulek subjektů. Typy kontaktů lze slučovat stejným cílovým způsobem jako skupiny.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
    <dd>Zobrazuje databázové tabulky a sloupce v kompaktním tabulkovém přehledu. Slouží ke kontrole aktuální struktury databáze a neupravuje data. Toto je jedno z míst, kde jsou metadata dotazy do databáze očekávané.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>schema.php">Database Schema</a></dt>
    <dd>Zobrazuje vizuální diagram databázového schématu s tabulkami, sloupci, klíči a vazbami. Slouží ke kontrole rozložení tabulek, cizích klíčů, typů sloupců, hodnot NULL a vykreslení schématu.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>check.php">Database Consistency</a></dt>
    <dd>Spouští kontroly porušených nebo podezřelých záznamů, například řádků navázaných na chybějící subjekt nebo nepřiřazených kontaktů ponechaných ke kontrole. Stránka je diagnostická a vypisuje, co je třeba v databázi prověřit.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>exlib.php">External Libraries</a></dt>
    <dd>Vypisuje soubory v adresáři externích knihoven EX v pořadí podobném ls. Tabulka ukazuje práva, vlastníka, čas stažení odvozený z časové značky souboru na disku a název souboru. Slouží ke kontrole přibalených metadat a externích knihovních souborů bez síťového načítání.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>info.php">PHP Info and PHP Credits</a></dt>
    <dd>Zobrazuje konfiguraci PHP a informace PHP Credits pro administrativní diagnostiku. Slouží ke kontrole prostředí, nikoli k úpravě dat portálu.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">Portal Help</a></dt>
    <dd>Zobrazuje tuto dvojjazyčnou nápovědu. Popisuje účel jednotlivých stránek menu a důležité rozdíly mezi sdílenými akcemi, akcemi konkrétního subjektu, nastavením, filtry a diagnostikou.</dd>
    <dt><a href="<?php echo $sBaseUrl; ?>demo.php" target="_blank" rel="noopener noreferrer">Demo Subjects</a></dt>
    <dd>Otevírá demonstrační stránku se vzorovými subjekty v novém okně stejně jako položka menu. Slouží k testování rozložení tabulek, filtrů, nastavení, modálů, vykreslení akcí, formátování kontaktů a zobrazení adres bez úprav skutečných záznamů.</dd>
  </dl>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
