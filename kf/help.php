<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireViewAccess($aAllowedIps, "kf", "kf_csrf_token");


$sTitle = getPageTitleText("KF Help", $aAllowedIps);
$iTime = sendPageHeaders();

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
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("kf_csrf_token")); ?>">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <h1>KF Help</h1>
  <h2>US English</h2>
  <h3>Common Controls</h3>
  <dl class="kf-help-list">
    <dt>Access and Sign-in</dt>
    <dd>
      <p>KF pages are protected by the shared portal sign-in. Normal listings require view access for the <code>kf</code> project, while editing actions and sensitive database diagnostics require full access. A trusted client is treated as allowed by the shared access layer.</p>
      <p>Read-only pages can be opened with view access. Pages that show edit buttons still hide those buttons unless the current user has full access. Ajax write actions return JSON access errors instead of rendering a sign-in page inside a dialog.</p>
      <ul>
        <li><strong>View access:</strong> Monthly overview, transactions, debts, subscriptions, types, exchange rates, and this help page.</li>
        <li><strong>Full access:</strong> Creating, editing, deleting, and database diagnostics.</li>
        <li><strong>Session:</strong> Sign-in, logout, CSRF token, filters, and settings are session-backed.</li>
      </ul>
    </dd>
    <dt>Quick Filter</dt>
    <dd>
      <p>The quick filter searches the rendered text already present in the current table. It is a client-side narrowing tool and does not change the SQL query or stored finance data.</p>
      <p>The AND and OR buttons insert logical operators into the filter expression. Reset clears the stored quick filter for the current page. The floating Filter button focuses the filter input without changing the table contents.</p>
      <ul>
        <li><strong>Scope:</strong> Visible rendered table text only.</li>
        <li><strong>Operators:</strong> AND and OR are client-side filter operators.</li>
        <li><strong>Reset:</strong> Clears the saved quick filter value for the page.</li>
      </ul>
    </dd>
    <dt>Settings and Display Currency</dt>
    <dd>
      <p>Pages with amounts use the Settings dialog. Display currency is stored per listing above the separator. The European amount format setting is shared across the KF subproject below the separator.</p>
      <p>The display-currency selector offers <strong>As entered</strong> and available currencies. Currency choices are labeled as <code>CODE &mdash; currency name</code>, with the code visually separated from the name. If a display currency is selected, amounts are converted for display and totals before rendering.</p>
      <p><strong>As entered</strong> leaves individual values in their stored currency. The Subscriptions page defaults to As entered, while the main monetary listings default to CZK display unless the session setting changes it.</p>
      <ul>
        <li><strong>Per page:</strong> Display currency.</li>
        <li><strong>Shared:</strong> European amount formatting.</li>
        <li><strong>Labels:</strong> Currency options use the code, an em dash, and the currency name.</li>
      </ul>
    </dd>
    <dt>Currency Conversion</dt>
    <dd>
      <p>Transactions, debt movements, and subscriptions store their own currency. The stored amount is never relabeled as another currency without conversion. When conversion is requested, KF converts the numeric amount before totals and labels are rendered.</p>
      <p>Exchange rates come from rows in <code>kf_exchange_rates</code>. For dated records, the conversion uses the latest rate whose <code>valid_for</code> date is not later than the record date. If no older rate exists, it uses the earliest later rate. Records without a usable date use the latest available rate.</p>
      <p>If a required rate is missing, the affected value stays in its original currency and KF avoids showing a misleading converted currency suffix. Debt totals also track conversion failures so that a mixed or partially unconverted total is not labeled as a fully converted value.</p>
      <ul>
        <li><strong>Stored data:</strong> Amount plus currency per finance row.</li>
        <li><strong>Rate source:</strong> Stored CNB exchange-rate rows.</li>
        <li><strong>Failure behavior:</strong> No false currency label is printed after failed conversion.</li>
      </ul>
    </dd>
    <dt>Dialogs and Defaults</dt>
    <dd>
      <p>Editing dialogs are shown only with full access. Opening a dialog focuses the primary field or selected control without forcing native drop-downs to open. Save actions validate required values before writing to the database.</p>
      <p>Currency values are selected from the available currency list and amounts accept both dot and comma decimal input. Subscription creation remembers the type, currency, and period from the previous new subscription insertion, not from edits of existing subscriptions.</p>
      <p>Fields that should use application data use application selectors or autocomplete. For example, debt subjects are suggested from portal subjects, while a subscription name is treated as subscription text rather than a browser suggestion from unrelated portal data.</p>
      <ul>
        <li><strong>Focus:</strong> Dialogs focus the main control without auto-opening a drop-down.</li>
        <li><strong>Validation:</strong> Required IDs, dates, amounts, currencies, and periods are checked before save.</li>
        <li><strong>New subscription defaults:</strong> Type, currency, and period come from the previous new insertion.</li>
      </ul>
    </dd>
  </dl>
  <h3>Menu Pages</h3>
  <dl class="kf-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Income and Expenses</a></dt>
    <dd>
      <p>Income and Expenses is the monthly overview. It reads transactions, groups them by month, and shows columns for individual finance types, type groups, income total, expense total, and net total.</p>
      <p>Amounts are converted before monthly, type, group, and summary totals are calculated. This means totals are numerically consistent with the selected display currency when conversion data is available.</p>
      <p>The page is read-only with view access. Settings can change display currency and amount formatting, and the quick filter can narrow visible month rows after the overview has been rendered.</p>
      <ul>
        <li><strong>Data:</strong> Monthly totals from <code>kf_fin_transactions</code> and <code>kf_fin_types</code>.</li>
        <li><strong>Columns:</strong> Finance types, groups, income, expense, and net.</li>
        <li><strong>Currency:</strong> Totals are converted before aggregation when a display currency is selected.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>trans.php">Transactions</a></dt>
    <dd>
      <p>Transactions is the detailed list of finance transactions. It shows date, type, signed amount, counterparty, and note. Income rows are stored as positive amounts and expense rows as negative amounts.</p>
      <p>Full access can create, edit, and delete transactions. New or edited transactions require a valid date, income or expense type, positive input amount, and available currency. The selected type decides whether the stored amount is positive or negative.</p>
      <p>The transaction dialog can include additional same-kind transactions that subtract from the main entered amount. These additional rows must be valid, use distinct finance types, and be convertible to the main currency on the transaction date.</p>
      <ul>
        <li><strong>Rows:</strong> One stored finance transaction per row.</li>
        <li><strong>Editing:</strong> Full access can create, edit, and delete.</li>
        <li><strong>Additional rows:</strong> Optional same-kind split rows reduce the main saved amount.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>debts.php">Debts</a></dt>
    <dd>
      <p>Debts links a debt to a portal subject and shows its movements. The table displays subject, total amount, movement list, account number, e-mail, phone, and note.</p>
      <p>Subject contact details are read from the portal contact data. Bank account, e-mail, and the first available phone contact are displayed for quick reference and copying. The debt amount is the total of its movements.</p>
      <p>Full access can create debts, edit the linked subject and debt note, add or edit movements, and delete movements or whole debts. New debts require a subject and an initial movement with date, amount, and currency.</p>
      <ul>
        <li><strong>Subject:</strong> Selected from portal subjects.</li>
        <li><strong>Movements:</strong> Date, amount, currency, and note entries under the debt.</li>
        <li><strong>Total:</strong> Movement totals respect the selected display currency when conversion is possible.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>subscr.php">Subscriptions</a></dt>
    <dd>
      <p>Subscriptions tracks recurring income or expense rows. It shows days until due, name, finance type, amount, period, next due date and time, counterparty, note, and active state.</p>
      <p>The In column is calculated from <code>next_due_at</code>. Next Due is displayed with a clear visual separation between date and time. If a subscription has a supported automatic period and a due date, full access can mark it served.</p>
      <p>Marking a subscription served inserts a transaction for the current due date and advances the next due date according to the billing period. Monthly, quarterly, and yearly periods preserve the intended billing day where possible; weekly uses a one-week step.</p>
      <p>New subscriptions remember type, currency, and period from the previous new subscription insertion. Editing existing subscriptions does not overwrite those new-entry defaults.</p>
      <ul>
        <li><strong>Period:</strong> Weekly, monthly, quarterly, yearly, or other.</li>
        <li><strong>Serving:</strong> Creates a transaction and advances Next Due when the period can be advanced.</li>
        <li><strong>Defaults:</strong> New-entry type, currency, and period are session-backed.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>types.php">Income and Expense Types</a></dt>
    <dd>
      <p>Income and Expense Types manages finance type rows. A type can be income, expense, or group. Income and expense types are used directly by transactions and subscriptions; group types collect member types for overview columns.</p>
      <p>Full access can create and edit types, change their kind, and choose group members. Deleting a type is blocked when it is already used by transactions.</p>
      <ul>
        <li><strong>Kinds:</strong> Income, expense, and group.</li>
        <li><strong>Groups:</strong> Group rows can collect member finance types.</li>
        <li><strong>Deletion:</strong> Used transaction types cannot be deleted.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>exrates.php">Exchange Rates</a></dt>
    <dd>
      <p>Exchange Rates is a read-only view of the latest stored exchange-rate set. The table shows valid date, order, country, currency name, currency code, amount, rate, and fetch timestamp.</p>
      <p>Amount is displayed as amount plus currency code, for example <code>100 HUF</code>. Rate is displayed as the CZK value for that amount, for example <code>6.123 CZK</code>. Fetched At uses the same separated date-and-time style as subscription Next Due.</p>
      <p>The background endpoint <code>proc.php</code> fetches daily CNB exchange rates after the configured Prague publish time and records success or error state. The listing itself does not fetch or modify rates.</p>
      <ul>
        <li><strong>Rows:</strong> Latest stored <code>valid_for</code> date only.</li>
        <li><strong>Amount:</strong> Source amount and source currency code.</li>
        <li><strong>Rate:</strong> CZK value for the displayed source amount.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
    <dd>
      <p>Database Structure is a full-access diagnostic and export page. It lists <code>kf_*</code> tables in dependency-aware order and shows normalized <code>SHOW CREATE TABLE</code> output.</p>
      <p>The page can download a schema-only SQL file or a backup SQL file. Download links and copy-link buttons use the current script URL and project path.</p>
      <ul>
        <li><strong>Access:</strong> Full access only.</li>
        <li><strong>Scope:</strong> <code>kf_*</code> tables.</li>
        <li><strong>Downloads:</strong> Schema and backup SQL exports.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>schema.php">Database Schema</a></dt>
    <dd>
      <p>Database Schema is the full-access visual schema viewer. It reads KF table, column, key, index, and relation metadata from <code>INFORMATION_SCHEMA</code>.</p>
      <p>The diagram displays KF tables, primary keys, foreign keys, unique keys, indexes, column types, nullability, and relation lines. On devices where the diagram is not suitable, the page shows an unavailable message instead of a broken view.</p>
      <ul>
        <li><strong>Access:</strong> Full access only.</li>
        <li><strong>Metadata:</strong> Columns, keys, indexes, and relations.</li>
        <li><strong>Purpose:</strong> Visual review of the KF data model.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">KF Help</a></dt>
    <dd>
      <p>KF Help is this bilingual read-only help page. It documents common controls, access expectations, monetary conversion behavior, settings, dialogs, and KF menu pages.</p>
      <p>The page itself requires only KF view access and does not modify finance data.</p>
      <ul>
        <li><strong>Access:</strong> View access.</li>
        <li><strong>Languages:</strong> US English and Czech.</li>
        <li><strong>Scope:</strong> User-visible KF behavior.</li>
      </ul>
    </dd>
  </dl>
  <h2>Česky</h2>
  <h3>Společné ovládání</h3>
  <dl class="kf-help-list">
    <dt>Přístup a přihlášení</dt>
    <dd>
      <p>Stránky KF jsou chráněné společným portálovým přihlášením. Běžné výpisy vyžadují view přístup pro projekt <code>kf</code>, zatímco editační akce a citlivá databázová diagnostika vyžadují full přístup. Trusted klient se ve sdílené přístupové vrstvě bere jako povolený.</p>
      <p>Pouze čtecí stránky lze otevřít s view přístupem. Stránky, které umí editovat, dál skrývají editační tlačítka, pokud aktuální uživatel nemá full přístup. Ajaxové zápisové akce vracejí JSON chybu přístupu místo přihlašovací stránky uvnitř dialogu.</p>
      <ul>
        <li><strong>View přístup:</strong> Měsíční přehled, transakce, dluhy, předplatná, typy, kurzy a tato nápověda.</li>
        <li><strong>Full přístup:</strong> Vytváření, editace, mazání a databázová diagnostika.</li>
        <li><strong>Session:</strong> Přihlášení, odhlášení, CSRF token, filtry a nastavení používají session.</li>
      </ul>
    </dd>
    <dt>Rychlý filtr</dt>
    <dd>
      <p>Rychlý filtr hledá ve vykresleném textu, který už je v aktuální tabulce. Je to klientské zúžení výpisu a nemění SQL dotaz ani uložená finanční data.</p>
      <p>Tlačítka AND a OR vkládají logické operátory do filtrovacího výrazu. Reset smaže uložený rychlý filtr pro aktuální stránku. Plovoucí tlačítko Filter pouze zaostří vstup filtru bez změny obsahu tabulky.</p>
      <ul>
        <li><strong>Rozsah:</strong> Jen viditelný vykreslený text tabulky.</li>
        <li><strong>Operátory:</strong> AND a OR jsou klientské operátory filtru.</li>
        <li><strong>Reset:</strong> Smaže uloženou hodnotu rychlého filtru stránky.</li>
      </ul>
    </dd>
    <dt>Nastavení a zobrazovaná měna</dt>
    <dd>
      <p>Stránky s částkami používají dialog Settings. Zobrazovaná měna nad oddělovačem se ukládá zvlášť pro daný výpis. Evropský formát částek pod oddělovačem je společný pro celý KF podprojekt.</p>
      <p>Výběr zobrazované měny nabízí <strong>As entered</strong> a dostupné měny. Volby měn jsou popsané jako <code>CODE &mdash; název měny</code>, s kódem opticky odděleným od názvu. Pokud je vybraná zobrazovaná měna, částky se před vykreslením a součty přepočítají.</p>
      <p><strong>As entered</strong> nechává jednotlivé hodnoty v uložené měně. Stránka Subscriptions má ve výchozím stavu As entered, zatímco hlavní peněžní výpisy výchozí CZK, pokud to nezmění session nastavení.</p>
      <ul>
        <li><strong>Pro stránku:</strong> Zobrazovaná měna.</li>
        <li><strong>Sdílené:</strong> Evropské formátování částek.</li>
        <li><strong>Popisky:</strong> Volby měn používají kód, em dash a název měny.</li>
      </ul>
    </dd>
    <dt>Přepočty měn</dt>
    <dd>
      <p>Transakce, pohyby dluhů a předplatná ukládají vlastní měnu. Uložená částka se nikdy jen nepřelepí jinou měnou bez přepočtu. Pokud je přepočet vyžadovaný, KF přepočítá číselnou hodnotu před vykreslením součtů a popisků.</p>
      <p>Kurzy pocházejí z řádků v <code>kf_exchange_rates</code>. Pro datované záznamy se použije poslední kurz, jehož <code>valid_for</code> není pozdější než datum záznamu. Pokud starší kurz neexistuje, použije se nejbližší pozdější kurz. Záznamy bez použitelného data používají poslední dostupný kurz.</p>
      <p>Pokud potřebný kurz chybí, dotčená hodnota zůstane v původní měně a KF nevypíše zavádějící suffix přepočtené měny. Součty dluhů také sledují selhání přepočtu, aby smíšený nebo jen částečně přepočtený součet nebyl označený jako plně přepočtená hodnota.</p>
      <ul>
        <li><strong>Uložená data:</strong> Částka a měna u každého finančního řádku.</li>
        <li><strong>Zdroj kurzu:</strong> Uložené řádky kurzů CNB.</li>
        <li><strong>Selhání:</strong> Po neúspěšném přepočtu se netiskne falešný měnový popisek.</li>
      </ul>
    </dd>
    <dt>Dialogy a výchozí hodnoty</dt>
    <dd>
      <p>Editační dialogy se zobrazují jen s full přístupem. Dialog při otevření zaostří hlavní pole nebo vybraný prvek, ale nevynucuje rozbalení nativního drop-downu. Ukládání kontroluje povinné hodnoty před zápisem do databáze.</p>
      <p>Měna se vybírá ze seznamu dostupných měn a částky přijímají desetinnou tečku i čárku. Vytvoření nového předplatného si pamatuje typ, měnu a periodu z předchozího nového vložení, nikoli z editace existujícího předplatného.</p>
      <p>Pole, která mají používat aplikační data, používají aplikační výběry nebo autocomplete. Například subjekt dluhu se našeptává z portálových subjektů, zatímco název předplatného je text předplatného a ne browser suggestion z nesouvisejících portálových dat.</p>
      <ul>
        <li><strong>Focus:</strong> Dialogy zaostří hlavní prvek bez automatického rozbalení drop-downu.</li>
        <li><strong>Validace:</strong> Před uložením se kontrolují povinná ID, data, částky, měny a periody.</li>
        <li><strong>Výchozí hodnoty nového předplatného:</strong> Typ, měna a perioda jsou uložené v session.</li>
      </ul>
    </dd>
  </dl>
  <h3>Stránky v menu</h3>
  <dl class="kf-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Income and Expenses</a></dt>
    <dd>
      <p>Income and Expenses je měsíční přehled. Čte transakce, seskupuje je podle měsíce a zobrazuje sloupce pro jednotlivé finanční typy, skupiny typů, součet příjmů, součet výdajů a čistý součet.</p>
      <p>Částky se přepočítají před výpočtem měsíčních, typových, skupinových a souhrnných součtů. Díky tomu jsou součty číselně konzistentní s vybranou zobrazovanou měnou, pokud jsou dostupná kurzová data.</p>
      <p>Stránka je s view přístupem pouze pro čtení. Settings může změnit zobrazovanou měnu a formát částek a rychlý filtr může zúžit viditelné měsíce až po vykreslení přehledu.</p>
      <ul>
        <li><strong>Data:</strong> Měsíční součty z <code>kf_fin_transactions</code> a <code>kf_fin_types</code>.</li>
        <li><strong>Sloupce:</strong> Finanční typy, skupiny, příjmy, výdaje a čistý součet.</li>
        <li><strong>Měna:</strong> Součty se při vybrané zobrazované měně přepočítají před agregací.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>trans.php">Transactions</a></dt>
    <dd>
      <p>Transactions je podrobný seznam finančních transakcí. Zobrazuje datum, typ, podepsanou částku, protistranu a poznámku. Příjmy jsou uložené jako kladné částky a výdaje jako záporné částky.</p>
      <p>Full přístup může vytvářet, upravovat a mazat transakce. Nová nebo upravená transakce vyžaduje platné datum, příjmový nebo výdajový typ, kladnou vstupní částku a dostupnou měnu. Vybraný typ rozhoduje, jestli se uložená částka zapíše kladně nebo záporně.</p>
      <p>Dialog transakce může obsahovat další transakce stejného druhu, které se odečítají od hlavní zadané částky. Tyto další řádky musí být platné, používat odlišné finanční typy a být převoditelné do hlavní měny k datu transakce.</p>
      <ul>
        <li><strong>Řádky:</strong> Jeden uložený finanční pohyb na řádek.</li>
        <li><strong>Editace:</strong> Full přístup může vytvářet, upravovat a mazat.</li>
        <li><strong>Další řádky:</strong> Volitelné rozdělení stejného druhu snižuje hlavní uloženou částku.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>debts.php">Debts</a></dt>
    <dd>
      <p>Debts propojuje dluh s portálovým subjektem a zobrazuje jeho pohyby. Tabulka ukazuje subjekt, celkovou částku, seznam pohybů, číslo účtu, e-mail, telefon a poznámku.</p>
      <p>Kontaktní údaje subjektu se čtou z portálových kontaktů. Bankovní účet, e-mail a první dostupný telefon se zobrazují kvůli rychlé kontrole a kopírování. Částka dluhu je součet jeho pohybů.</p>
      <p>Full přístup může vytvářet dluhy, upravovat připojený subjekt a poznámku dluhu, přidávat nebo upravovat pohyby a mazat pohyby nebo celé dluhy. Nový dluh vyžaduje subjekt a počáteční pohyb s datem, částkou a měnou.</p>
      <ul>
        <li><strong>Subjekt:</strong> Vybírá se z portálových subjektů.</li>
        <li><strong>Pohyby:</strong> Záznamy data, částky, měny a poznámky pod dluhem.</li>
        <li><strong>Součet:</strong> Součty pohybů respektují vybranou zobrazovanou měnu, pokud lze přepočítat.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>subscr.php">Subscriptions</a></dt>
    <dd>
      <p>Subscriptions sleduje opakované příjmy nebo výdaje. Zobrazuje počet dnů do splatnosti, název, finanční typ, částku, periodu, další termín včetně času, protistranu, poznámku a aktivní stav.</p>
      <p>Sloupec In se počítá z <code>next_due_at</code>. Next Due se zobrazuje s jasným oddělením data od času. Pokud má předplatné podporovanou automatickou periodu a termín, full přístup ho může označit jako obsloužené.</p>
      <p>Označení předplatného jako obslouženého vloží transakci pro aktuální datum splatnosti a posune další termín podle billing periody. Měsíční, čtvrtletní a roční periody zachovávají zamýšlený billing day, kde to jde; týdenní perioda používá krok jeden týden.</p>
      <p>Nová předplatná si pamatují typ, měnu a periodu z předchozího nového vložení. Editace existujících předplatných tyto výchozí hodnoty pro nové záznamy nepřepisuje.</p>
      <ul>
        <li><strong>Perioda:</strong> Weekly, monthly, quarterly, yearly nebo other.</li>
        <li><strong>Obsloužení:</strong> Vytvoří transakci a posune Next Due, pokud lze periodu posunout.</li>
        <li><strong>Výchozí hodnoty:</strong> Typ, měna a perioda nového záznamu jsou uložené v session.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>types.php">Income and Expense Types</a></dt>
    <dd>
      <p>Income and Expense Types spravuje řádky finančních typů. Typ může být income, expense nebo group. Income a expense typy se používají přímo v transakcích a předplatných; group typy sdružují členské typy pro sloupce přehledu.</p>
      <p>Full přístup může vytvářet a upravovat typy, měnit jejich druh a vybírat členy skupiny. Smazání typu je blokované, pokud už je použitý transakcemi.</p>
      <ul>
        <li><strong>Druhy:</strong> Income, expense a group.</li>
        <li><strong>Skupiny:</strong> Skupinové řádky mohou sdružovat členské finanční typy.</li>
        <li><strong>Mazání:</strong> Typy použité v transakcích nelze smazat.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>exrates.php">Exchange Rates</a></dt>
    <dd>
      <p>Exchange Rates je pouze čtecí pohled na poslední uloženou sadu kurzů. Tabulka zobrazuje platnost, pořadí, zemi, název měny, kód měny, množství, kurz a čas stažení.</p>
      <p>Amount se zobrazuje jako množství a kód zdrojové měny, například <code>100 HUF</code>. Rate se zobrazuje jako hodnota v CZK pro dané množství, například <code>6.123 CZK</code>. Fetched At používá stejné oddělení data a času jako subscription Next Due.</p>
      <p>Background endpoint <code>proc.php</code> stahuje denní kurzy CNB po nastaveném pražském čase publikace a ukládá stav úspěchu nebo chyby. Samotný výpis kurzy nestahuje ani neupravuje.</p>
      <ul>
        <li><strong>Řádky:</strong> Jen poslední uložené datum <code>valid_for</code>.</li>
        <li><strong>Amount:</strong> Zdrojové množství a kód zdrojové měny.</li>
        <li><strong>Rate:</strong> Hodnota v CZK pro zobrazené zdrojové množství.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
    <dd>
      <p>Database Structure je diagnostická a exportní stránka pouze pro full přístup. Vypisuje tabulky <code>kf_*</code> v pořadí podle závislostí a zobrazuje normalizovaný výstup <code>SHOW CREATE TABLE</code>.</p>
      <p>Stránka umí stáhnout SQL soubor se schématem nebo SQL zálohu. Download odkazy a tlačítka pro kopírování odkazů používají aktuální URL skriptu a cestu projektu.</p>
      <ul>
        <li><strong>Přístup:</strong> Pouze full přístup.</li>
        <li><strong>Rozsah:</strong> Tabulky <code>kf_*</code>.</li>
        <li><strong>Stahování:</strong> SQL export schématu a zálohy.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>schema.php">Database Schema</a></dt>
    <dd>
      <p>Database Schema je vizuální schema viewer pouze pro full přístup. Čte metadata KF tabulek, sloupců, klíčů, indexů a relací z <code>INFORMATION_SCHEMA</code>.</p>
      <p>Diagram zobrazuje KF tabulky, primární klíče, cizí klíče, unikátní klíče, indexy, typy sloupců, nullability a spojnice relací. Na zařízeních, kde diagram není vhodný, stránka zobrazí zprávu o nedostupnosti místo rozbitého pohledu.</p>
      <ul>
        <li><strong>Přístup:</strong> Pouze full přístup.</li>
        <li><strong>Metadata:</strong> Sloupce, klíče, indexy a relace.</li>
        <li><strong>Účel:</strong> Vizuální kontrola datového modelu KF.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">KF Help</a></dt>
    <dd>
      <p>KF Help je tato dvojjazyčná pouze čtecí nápověda. Dokumentuje společné ovládání, očekávaný přístup, chování měnových přepočtů, nastavení, dialogy a stránky menu KF.</p>
      <p>Samotná stránka vyžaduje jen KF view přístup a neupravuje finanční data.</p>
      <ul>
        <li><strong>Přístup:</strong> View přístup.</li>
        <li><strong>Jazyky:</strong> US English a čeština.</li>
        <li><strong>Rozsah:</strong> Uživatelsky viditelné chování KF.</li>
      </ul>
    </dd>
  </dl>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
