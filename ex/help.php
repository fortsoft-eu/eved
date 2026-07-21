<?php

include "main.php";


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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link rel="icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo $sBaseUrl; ?>favicon.ico" type="image/x-icon">
  <title><?php echo html(getPageTitleText("Portal Help", $aAllowedIps)); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <h1>Portal Help</h1>
  <h2>US English</h2>
  <h3>Common Controls</h3>
  <dl class="portal-help-list">
    <dt>Menu and Page Titles</dt>
    <dd>
      <p>The portal menu is rendered from active rows in <code>fs_menu</code>. Page names used in the browser title and in the page heading are resolved through the same menu metadata, so a missing active menu row is treated as a configuration error instead of being silently ignored.</p>
      <p>Menu links and help links open the same portal pages in the current window. The help text describes the visible page behavior, not the database menu administration itself.</p>
      <ul>
        <li><strong>Source:</strong> Active menu rows from <code>fs_menu</code>.</li>
        <li><strong>Current page:</strong> The active page name must exist in the menu.</li>
        <li><strong>Links:</strong> Menu and help links open the portal pages in the current window.</li>
      </ul>
    </dd>
    <dt>Access and Sign-in</dt>
    <dd>
      <p>Normal data pages require portal view access. Editing controls are displayed only to users with full access, and several diagnostic pages require full access because they expose database structure, SQL exports, PHP configuration, or filesystem metadata.</p>
      <p>When a user is not signed in, the portal requires sign-in before the requested menu page is rendered. Failed sign-in attempts are delayed, the form uses a security token, and Ajax requests receive JSON errors instead of a full HTML response.</p>
      <p>The page help intentionally describes which pages are read-only, which pages can edit data, and which pages are diagnostic. A page may still hide individual actions if the current account does not have the required access.</p>
      <ul>
        <li><strong>View access:</strong> Required for normal menu pages.</li>
        <li><strong>Full access:</strong> Required for editing and for sensitive diagnostics.</li>
        <li><strong>Ajax requests:</strong> Receive JSON access errors instead of a full page.</li>
      </ul>
    </dd>
    <dt>Quick Filter</dt>
    <dd>
      <p>The quick filter searches the rendered text that is currently present in the table. It is meant for fast narrowing after the server has already built the page. It does not change the SQL query and does not create or modify database rows.</p>
      <p>The AND and OR buttons insert logical operators used by the client-side filter expression. Reset clears the stored filter for the current page. Because the filter works on visible text, hidden columns and hidden inactive items cannot match until the page settings make them visible.</p>
      <ul>
        <li><strong>Scope:</strong> Visible rendered table text only.</li>
        <li><strong>Operators:</strong> AND and OR are inserted into the client-side filter expression.</li>
        <li><strong>Reset:</strong> Clears the stored quick filter for the current page.</li>
      </ul>
    </dd>
    <dt>Complex Filter</dt>
    <dd>
      <p>The complex filter is available on the subject-style pages where the data model is wide enough to need it. It can match all conditions or any condition and supports a limited number of conditions so that the filter remains usable in the browser.</p>
      <p>Available fields cover subject type, computed subject name, active state, creation time, personal name fields, birth and death dates, service timestamps, nicknames, postal addresses, address fields, contact values, individual contact types, groups, and notes. Operators include equality, inequality, lower and greater comparisons, contains, starts with, ends with, empty, and not empty. Boolean fields intentionally use equality only.</p>
      <p>Each page keeps its own complex-filter state when the underlying listing is different. The compact Contacts page, the full Subjects page, and the Demo Subjects page therefore do not overwrite one another's complex filter settings.</p>
      <ul>
        <li><strong>Mode:</strong> Match all conditions or match any condition.</li>
        <li><strong>Fields:</strong> Subject, person, address, contact, group, note, date, datetime, boolean, and per-contact-type fields.</li>
        <li><strong>State:</strong> Stored separately for different subject-style listings.</li>
      </ul>
    </dd>
    <dt>Settings</dt>
    <dd>
      <p>Settings above the separator belong to the current listing. They usually control whether inactive subjects or inactive child records are displayed. The full Subjects page is intentionally more complete by default, while compact overview pages hide inactive rows more aggressively.</p>
      <p>Country display settings below the separator are shared across portal pages that render postal addresses. These settings affect whether country names are printed and which country may be omitted in local postal output.</p>
      <p>Most settings are stored in the session. They change how the page is rendered the next time the table is produced, and they do not modify the stored subject, contact, address, group, or note data.</p>
      <ul>
        <li><strong>Local settings:</strong> Control the current listing, usually inactive records.</li>
        <li><strong>Country settings:</strong> Shared by pages that render postal addresses.</li>
        <li><strong>Persistence:</strong> Stored in session and do not write subject data.</li>
      </ul>
    </dd>
    <dt>Actions and Dialogs</dt>
    <dd>
      <p>Action icons inside a table cell act on the concrete item represented by that cell. An edit icon in a contact cell edits that contact relation, an edit icon in an address cell edits that address relation, and an edit icon next to the subject name edits the subject itself.</p>
      <p>Some aggregate pages distinguish shared actions from subject-specific actions. A shared contact or shared address action can affect every matching row, while an action shown in the Subject cell affects only the relation for the listed subject. This distinction is important on Shared Contacts and Addresses.</p>
      <p>Dialogs validate values before saving. Contact values are normalized according to their contact type, dates use the database date format, postal codes are checked against country metadata when available, and destructive actions ask for confirmation before they are sent to the server.</p>
      <ul>
        <li><strong>Cell actions:</strong> Edit, delete, copy, or add the item shown in the cell.</li>
        <li><strong>Subject actions:</strong> Apply to the whole subject row.</li>
        <li><strong>Destructive actions:</strong> Require confirmation before they are sent.</li>
      </ul>
    </dd>
    <dt>Database Value Tooltips</dt>
    <dd>
      <p>Database-backed values that have creation and update timestamps show those timestamps directly on the value tooltip, not on the action icon. The displayed format is <code>Created: YYYY-MM-DD HH:MM:SS</code> and <code>Updated: YYYY-MM-DD HH:MM:SS</code>.</p>
      <p>Action icons keep their own action tooltips. This makes it possible to distinguish the age of a stored value from the command that would edit, delete, copy, or serve that value.</p>
      <ul>
        <li><strong>Value tooltip:</strong> Shows database creation and update timestamps.</li>
        <li><strong>Action tooltip:</strong> Describes the icon command.</li>
        <li><strong>Format:</strong> <code>YYYY-MM-DD HH:MM:SS</code>.</li>
      </ul>
    </dd>
  </dl>
  <h3>Menu Pages</h3>
  <dl class="portal-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Contacts</a></dt>
    <dd>
      <p>Contacts is the compact read-only overview of portal subjects. It is optimized for scanning people and services, copying table values, and opening contact links without exposing the full editing surface. It uses the same subject rendering helpers as the editing pages, but it intentionally keeps the page lighter and hides less important columns on narrower screens.</p>
      <p>The table contains the computed subject name, personal dates, nicknames, postal addresses, contacts, groups, and notes. Values are displayed in the form used by the rest of the portal, so the compact overview is suitable for checking how a subject will appear outside the full editor.</p>
      <p>The quick filter searches the visible table text, and the complex filter can narrow the subject set by subject, person, address, contact, group, note, date, datetime, boolean, empty, and per-contact-type fields. Its filter state is separate from the full Subjects page because the compact table has a different purpose.</p>
      <p>Inactive subjects and inactive child items are hidden by default. Country display settings are shared globally with the other portal address pages. Full edit actions are not shown here; when editing is needed, use Subjects or one of the focused aggregate editors.</p>
      <ul>
        <li><strong>Data:</strong> Subjects, computed names, birth data, nicknames, addresses, contacts, groups, and notes.</li>
        <li><strong>Filtering:</strong> Quick filter plus its own complex filter.</li>
        <li><strong>Settings:</strong> Inactive subjects and inactive items are hidden by default.</li>
        <li><strong>Actions:</strong> Copy and contact-link actions are available; editing is intentionally not shown.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>contacts.php">Shared Contacts</a></dt>
    <dd>
      <p>Shared Contacts groups exact shared contact values and shows every subject that uses each value. It is intended for finding reused phone numbers, e-mail addresses, web links, messaging identifiers, and profile URLs, and for correcting a shared value in one place when the stored contact itself is wrong.</p>
      <p>The Contact column represents the shared contact row. Editing that cell changes the contact type or contact value for every linked subject. Deleting that cell removes the shared contact and its subject links. These actions are broad by design, so they should be used only when the contact value itself is the problem.</p>
      <p>The Subject column represents the relation between one subject and the shared contact. Editing there changes only that subject-contact relation, including primary, active, and note fields. Deleting there removes only that one subject's use of the shared contact.</p>
      <p>Saved contact values are normalized and validated by contact type. The page uses the same normalization rules as the full subject editor, including phone numbers, e-mail addresses, web links, messaging services, and known profile services. Inactive contacts and inactive subjects are hidden by default.</p>
      <ul>
        <li><strong>Table:</strong> Contact and Subject columns.</li>
        <li><strong>Shared actions:</strong> Contact-cell actions affect the shared contact row and its linked subjects.</li>
        <li><strong>Subject actions:</strong> Subject-cell actions affect only one subject-contact relation.</li>
        <li><strong>Validation:</strong> Contact values are normalized and checked by contact type.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>list.php">Subjects</a></dt>
    <dd>
      <p>Subjects is the full portal editor and the main place for maintaining subject data. It shows person, organization, service, and other subject types in one table and exposes the editors for the subject itself and for the subject's dependent records.</p>
      <p>For person subjects, the displayed name is computed from personal name fields and related person data. For non-person subjects, the displayed name comes from the subject-name record. The table also includes birth name, birth number, birth and death dates, nicknames, postal addresses, contacts, groups, notes, and portal account information where available.</p>
      <p>Full access can create subjects, edit the subject record, manage the portal user attached to a subject, and create, edit, or delete nicknames, postal addresses, contacts, group assignments, and notes. Actions next to the subject name belong to the subject; actions inside a child-value cell belong to that child record or relation.</p>
      <p>The complex filter on this page is the broadest one in the portal. It can combine subject fields, computed names, person fields, address fields, contact values, individual contact types, group membership, notes, dates, datetimes, booleans, empty values, and service timestamps. The full table shows inactive subjects and inactive child items by default so that maintenance work starts from the complete data set.</p>
      <p>Validation is shared with the focused editors. Dates use <code>YYYY-MM-DD</code>, datetimes use the database datetime format, birth numbers contain 9 or 10 digits, contact values are normalized per contact type, and postal codes are validated according to the selected country metadata when metadata is available.</p>
      <ul>
        <li><strong>Data:</strong> Subject type, computed name, person fields, nicknames, addresses, contacts, groups, notes, and portal account data.</li>
        <li><strong>Editing:</strong> Full access can create subjects and manage all subject child records.</li>
        <li><strong>Subject types:</strong> Person subjects use person fields; other subject types use subject-name rows.</li>
        <li><strong>Defaults:</strong> The full list shows inactive subjects and inactive items by default.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>bd.php">Birthdays</a></dt>
    <dd>
      <p>Birthdays shows person subjects whose birthday falls into the current birthday service window: two days back, today, and seventeen days forward. It is a work queue, not a general subject list, so subjects outside that window are intentionally absent.</p>
      <p>The In column shows the day offset from today and contains the service action when the current account has full access. Marking a birthday as served stores the current timestamp in <code>birthday_served_at</code> and also updates <code>inter_served_at</code>, because birthday contact counts as a handled interaction.</p>
      <p>A birthday served between 17 days before and 3 days after the birthday is treated as served for that birthday. This keeps the page from showing the same birthday again while still allowing early or slightly late handling.</p>
      <p>Visible subject rows can be edited with the same subject and child-item dialogs as Subjects. Creating a brand new subject is blocked on this page, but adding or editing child records for a visible subject is allowed where the normal access rules permit it. Inactive subjects and inactive child items are hidden by default.</p>
      <ul>
        <li><strong>Window:</strong> Two days back, today, and seventeen days forward.</li>
        <li><strong>In column:</strong> Shows the day offset and the service action.</li>
        <li><strong>Serving:</strong> Updates <code>birthday_served_at</code> and <code>inter_served_at</code>.</li>
        <li><strong>Editing:</strong> New subjects are blocked, but visible subject data can be maintained.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>inter.php">Interactions</a></dt>
    <dd>
      <p>Interactions shows person subjects whose communication service date is due now or soon. A subject is due when the stored <code>inter_served_at</code> value is missing or old enough that the next interaction date falls within the current window, which reaches today and the next twenty days.</p>
      <p>The In column shows how many days remain until the interaction is due. A missing previous service time is treated as due today. Marking an interaction as served updates <code>inter_served_at</code> to the current timestamp and removes the subject from the current due list.</p>
      <p>This page is meant for operational follow-up, so it allows maintenance of the visible subject and its existing dependent records without turning into the general subject editor. Creating a new subject is intentionally blocked here. Inactive subjects and inactive child items are hidden by default, and country display settings are the same shared settings used by other address-rendering pages.</p>
      <ul>
        <li><strong>Window:</strong> Due today through the next twenty days.</li>
        <li><strong>Missing timestamp:</strong> Treated as due today.</li>
        <li><strong>Serving:</strong> Updates <code>inter_served_at</code> and removes the row from the due list.</li>
        <li><strong>Editing:</strong> Existing visible subject data can be maintained; new subjects are blocked.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>addresses.php">Addresses</a></dt>
    <dd>
      <p>Addresses groups exact shared postal address forms and lists every subject that uses each form. It is useful for finding households, shared service addresses, duplicated addresses, and address rows that differ only because of a small formatting or field difference.</p>
      <p>The Address column represents the postal fields that make up the shared address form. It uses the normal one-line display in the table, while copy actions use the multiline postal form. Editing the Address cell changes the address fields of all exact matching rows while preserving subject-specific flags such as address type, primary, active, and note.</p>
      <p>The Subject column represents one subject-address row. Editing that cell changes only that subject's address relation, including address type, primary flag, active flag, and note. If the postal fields are changed during subject-specific editing, the row can move into another shared-address group after saving.</p>
      <p>Deleting from the Address cell deletes all exact matching address rows. Deleting from the Subject cell deletes only the row for that subject. Subject highlighting and the primary marker belong to the subject-address relation, not to the shared address group. Inactive addresses and inactive subjects are hidden by default.</p>
      <ul>
        <li><strong>Table:</strong> Address and Subject columns.</li>
        <li><strong>Address actions:</strong> Affect all exact matching address rows.</li>
        <li><strong>Subject actions:</strong> Affect one subject-address row and its flags.</li>
        <li><strong>Display:</strong> Copy uses the multiline postal form; the table uses the one-line form.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>groups.php">Groups</a></dt>
    <dd>
      <p>Groups manages subject groups and group-based portal permissions. The table shows group names, the number of linked subjects, active portal permissions assigned to the group, order controls, merge controls, and edit or delete actions.</p>
      <p>Full access can create groups, rename them, change their active state and order, assign active portal permissions, merge groups, and delete groups when deletion is allowed. Group order affects how groups are displayed inside subject tables and other grouped output.</p>
      <p>Merging is target-based. First select the destination group, then choose the source groups that should be merged into it. Subject assignments and allowed permissions are copied to the destination. Optional source deletion removes the merged source groups after their data has been moved.</p>
      <p>The portal access group is protected and cannot be deleted. This keeps the access model from being broken through a normal group-maintenance action.</p>
      <ul>
        <li><strong>Table:</strong> Group name, subject count, permissions, order controls, merge action, and edit/delete actions.</li>
        <li><strong>Editing:</strong> Full access can create, rename, move, merge, and delete groups where allowed.</li>
        <li><strong>Permissions:</strong> Active portal permissions can be assigned to groups.</li>
        <li><strong>Restriction:</strong> The portal access group cannot be deleted.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ctypes.php">Contact Types</a></dt>
    <dd>
      <p>Contact Types manages the visible names, active state, display order, and merging of portal contact types. Contact types control how contact values are labeled, validated, normalized, offered in dialogs, and ordered in subject tables.</p>
      <p>The internal <code>contact_type</code> key is generated from the visible name and is not normally edited directly. This keeps stored contact type identifiers predictable while allowing the displayed name to be managed from the administration page.</p>
      <p>Contact type order affects the New Contact and Edit Contact selectors and the order in which contact values appear inside subject cells. Inactive contact types can remain in the database for existing data while being removed from normal active choices.</p>
      <p>A contact type that already has contact rows must be merged before it can be deleted. During merge, source contact rows are moved to the target type. If a matching target contact already exists, subject links are moved to that existing target contact where possible so duplicate values are not needlessly created.</p>
      <ul>
        <li><strong>Table:</strong> Name, contact count, active flag, order controls, merge action, and edit/delete actions.</li>
        <li><strong>Technical key:</strong> Generated from the visible name.</li>
        <li><strong>Order:</strong> Affects contact dialogs and contact display order.</li>
        <li><strong>Deletion:</strong> Types with existing contacts must be merged first.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
    <dd>
      <p>Database Structure is the full-access SQL structure and export page for portal tables. It inspects the current database, lists <code>ex_*</code> tables in dependency-aware order, and shows normalized <code>SHOW CREATE TABLE</code> output for review.</p>
      <p>The schema download exports structure SQL. The backup download exports structure plus data and is the expected input for Database Difference. Copy schema link and Copy backup link copy direct download URLs so the same export can be retrieved without navigating the form again.</p>
      <p>The page does not modify the database. It intentionally exposes metadata and export data, so it is restricted to full access.</p>
      <ul>
        <li><strong>Display:</strong> Dependency-aware list of <code>ex_*</code> tables and normalized create SQL.</li>
        <li><strong>Download schema:</strong> Exports structure only.</li>
        <li><strong>Download backup:</strong> Exports structure and data for Database Difference.</li>
        <li><strong>Safety:</strong> Reads metadata and export data without modifying the database.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>schema.php">Database Schema</a></dt>
    <dd>
      <p>Database Schema is the visual schema viewer for portal tables. It reads table, column, key, index, and relation metadata from <code>INFORMATION_SCHEMA</code> and renders a schema-oriented view of the current database.</p>
      <p>Table boxes show column key markers, column names, shortened data types, nullability, and extra attributes. Long enum definitions are shortened in the visible table but remain available through the title tooltip so the diagram stays readable.</p>
      <p>The relation section lists foreign-key constraints and renders relation lines between table columns where the device can display the diagram. The page is read-only and is intended for understanding table layout rather than checking data consistency.</p>
      <ul>
        <li><strong>Tables:</strong> Shows key markers, columns, shortened types, nullability, and extra attributes.</li>
        <li><strong>Relations:</strong> Lists foreign keys and renders relation lines when available.</li>
        <li><strong>Scope:</strong> Reads metadata for <code>ex_*</code> tables.</li>
        <li><strong>Safety:</strong> Read-only schema inspection.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>check.php">Database Consistency</a></dt>
    <dd>
      <p>Database Consistency is a full-access diagnostic page for broken or suspicious portal records. It does not repair records automatically; it reports what should be reviewed in the database or through the editors.</p>
      <p>Error checks include subject-contact links with missing subjects or contacts, person rows without subjects, person rows assigned to non-person subjects, subject-name rows without subjects, subject-name rows assigned to person subjects, addresses with missing subjects, nicknames with missing subjects, notes with missing subjects, group links with missing subjects, and group links with missing groups.</p>
      <p>Warning checks include unassigned contacts kept for review. The top status distinguishes a clean database, warnings only, and required-link errors. Each section shows the number of affected rows and a table of the relevant database columns so the problem can be traced without guessing.</p>
      <ul>
        <li><strong>Error checks:</strong> Broken required links and records attached to the wrong subject type.</li>
        <li><strong>Warning checks:</strong> Unassigned contacts kept for review.</li>
        <li><strong>Status:</strong> Clean database, warnings only, or required-link errors.</li>
        <li><strong>Output:</strong> Row counts and affected database columns for each check.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>diff.php">Database Difference</a></dt>
    <dd>
      <p>Database Difference compares an uploaded SQL backup generated by Database Structure with the current database. It is meant for checking what changed between a saved portal backup and the live portal tables, especially whether a person or subject disappeared, was added, or changed in important display fields.</p>
      <p>The upload parser accepts <code>CREATE TABLE</code> and <code>INSERT</code> statements from the portal backup export. Before comparison, the current database is exported internally with the same backup generator, so both sides are compared in the same normalized representation.</p>
      <p>Persons and subjects are compared first because those are the records most likely to matter during manual review. The page reports missing rows, added rows, and changed values such as subject type, active flag, legacy identifier, computed subject name, and person detail fields.</p>
      <p>The page also reports structure differences and a per-table row summary. Those sections are intentionally summarized so the result remains readable and does not turn into a full low-level diff of every relation. The uploaded backup is only read and compared; nothing is restored, deleted, or written to the database.</p>
      <ul>
        <li><strong>Input:</strong> SQL backup from Database Structure.</li>
        <li><strong>Main comparison:</strong> Persons and subjects are checked first.</li>
        <li><strong>Differences:</strong> Missing rows, added rows, and changed important values.</li>
        <li><strong>Safety:</strong> Upload is compared only; no restore or database write is performed.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>exlib.php">External Libraries</a></dt>
    <dd>
      <p>External Libraries is a full-access filesystem inventory of files stored in <code>ex/lib</code>. It is used to verify bundled library files and metadata that belong to the portal without fetching anything from the network.</p>
      <p>The table shows permissions, owner, downloaded timestamp derived from the file modification time, and file name. The quick filter can narrow the visible inventory. The page does not modify files and does not download updates.</p>
      <ul>
        <li><strong>Directory:</strong> Reads files from <code>ex/lib</code>.</li>
        <li><strong>Columns:</strong> Permissions, owner, downloaded timestamp, and file name.</li>
        <li><strong>Filtering:</strong> Quick filter narrows the visible inventory.</li>
        <li><strong>Safety:</strong> No network access and no file modification.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>info.php">PHP Info and PHP Credits</a></dt>
    <dd>
      <p>PHP Info and PHP Credits is a full-access environment diagnostic page. It exposes PHP configuration and credits output and is intended for verifying the runtime environment, loaded modules, configuration values, and related PHP metadata.</p>
      <p>The PHP Info selector can show sections such as general information, configuration, modules, environment, variables, license, or all info. The PHP Credits selector can show sections such as group, general, SAPI, modules, documentation, QA, or all credits.</p>
      <p>Output is loaded into an iframe by default and can also be opened in a new window. The page is diagnostic only and does not edit portal data.</p>
      <ul>
        <li><strong>PHP Info:</strong> Selects configuration, modules, environment, variables, license, or all info.</li>
        <li><strong>PHP Credits:</strong> Selects group, general, SAPI, modules, documentation, QA, or all credits.</li>
        <li><strong>Display:</strong> Iframe by default, with an option to open output separately.</li>
        <li><strong>Safety:</strong> Diagnostic only; portal data is not edited.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">Portal Help</a></dt>
    <dd>
      <p>Portal Help is this bilingual help page. It documents portal menu pages, shared controls, filters, settings, access expectations, edit scopes, diagnostic tools, exports, and the difference between shared aggregate actions and subject-specific actions.</p>
      <p>The help page itself is read-only. Its links follow the same base URL and menu behavior as the rest of the portal so that a user can move from documentation to the relevant page directly.</p>
      <ul>
        <li><strong>Scope:</strong> Menu pages and shared menu-page behavior.</li>
        <li><strong>Languages:</strong> US English and Czech.</li>
        <li><strong>Links:</strong> Point to the same portal pages shown in the menu.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>demo.php">Demo Subjects</a></dt>
    <dd>
      <p>Demo Subjects opens a standalone sample subject table. It uses hardcoded demonstration data instead of real database rows, so it can be used to exercise rendering and filtering behavior without changing production records.</p>
      <p>The page tests the subject table layout, responsive column hiding, quick filter, complex filter, settings, modals, action rendering, contact formatting, address formatting, copied text, and timestamp tooltip behavior. It deliberately mirrors enough of the real subject table to make UI regressions visible.</p>
      <p>Demo filtering uses its own session state and a reduced field model based on the sample data. Demo settings can affect inactive item visibility and shared country display choices, but the sample data itself is not stored in the database.</p>
      <ul>
        <li><strong>Data:</strong> Hardcoded sample subjects, not production rows.</li>
        <li><strong>Purpose:</strong> Tests table layout, responsive behavior, filters, settings, modals, actions, and formatting.</li>
        <li><strong>State:</strong> Demo filter and settings can use session state.</li>
        <li><strong>Safety:</strong> Demo subject data is not written to the database.</li>
      </ul>
    </dd>
  </dl>
  <h2>Česky</h2>
  <h3>Společné ovládání</h3>
  <dl class="portal-help-list">
    <dt>Menu a názvy stránek</dt>
    <dd>
      <p>Menu portálu se vykresluje z aktivních řádků v tabulce <code>fs_menu</code>. Ze stejných metadat se odvozuje název stránky v titulku prohlížeče a v hlavičce stránky, takže chybějící aktivní položka menu je konfigurační chyba a stránka ji neschovává.</p>
      <p>Odkazy v menu i odkazy v nápovědě otevírají stejné portálové stránky ve stejném okně. Nápověda popisuje viditelné chování stránek, nikoli administraci samotného menu.</p>
      <ul>
        <li><strong>Zdroj:</strong> Aktivní položky z tabulky <code>fs_menu</code>.</li>
        <li><strong>Aktuální stránka:</strong> Název aktivní stránky musí existovat v menu.</li>
        <li><strong>Odkazy:</strong> Odkazy v menu i nápovědě otevírají portálové stránky ve stejném okně.</li>
      </ul>
    </dd>
    <dt>Přístup a přihlášení</dt>
    <dd>
      <p>Běžné datové stránky vyžadují portálové právo pro zobrazení. Editační ovládací prvky se zobrazují pouze uživatelům s plným přístupem a několik diagnostických stránek vyžaduje plný přístup, protože ukazují strukturu databáze, SQL exporty, konfiguraci PHP nebo metadata souborového systému.</p>
      <p>Pokud uživatel není přihlášený, portál vyžaduje přihlášení před vykreslením požadované stránky menu. Neúspěšné pokusy o přihlášení jsou zpomalované, formulář používá bezpečnostní token a Ajaxové požadavky dostávají JSON chybu místo celé HTML odpovědi.</p>
      <p>Nápověda proto uvádí, které stránky jsou pouze pro čtení, které umí zapisovat a které jsou diagnostické. Jednotlivé akce se mohou dál skrýt, pokud aktuální účet nemá potřebné oprávnění.</p>
      <ul>
        <li><strong>Zobrazení:</strong> Běžné stránky menu vyžadují portálový přístup pro čtení.</li>
        <li><strong>Plný přístup:</strong> Je potřeba pro editace a citlivou diagnostiku.</li>
        <li><strong>Ajax:</strong> Vrací JSON chyby přístupu místo celé stránky.</li>
      </ul>
    </dd>
    <dt>Rychlý filtr</dt>
    <dd>
      <p>Rychlý filtr hledá ve vykresleném textu, který je právě v tabulce. Je určený k rychlému zúžení poté, co server stránku sestavil. Nemění SQL dotaz a nevytváří ani neupravuje databázové řádky.</p>
      <p>Tlačítka AND a OR vkládají logické operátory používané klientským filtrovacím výrazem. Reset smaže uložený filtr aktuální stránky. Protože filtr pracuje s viditelným textem, skryté sloupce a skryté neaktivní položky se nemohou najít, dokud je nastavení stránky nezobrazí.</p>
      <ul>
        <li><strong>Rozsah:</strong> Jen viditelný vykreslený text tabulky.</li>
        <li><strong>Operátory:</strong> AND a OR se vkládají do klientského filtrovacího výrazu.</li>
        <li><strong>Reset:</strong> Smaže uložený rychlý filtr aktuální stránky.</li>
      </ul>
    </dd>
    <dt>Komplexní filtr</dt>
    <dd>
      <p>Komplexní filtr je dostupný na stránkách se subjektovou tabulkou, kde je datový model dost široký na to, aby byl potřeba. Umí vyhodnocovat shodu všech podmínek nebo libovolné podmínky a počet podmínek je omezený, aby filtr zůstal použitelný v prohlížeči.</p>
      <p>Dostupná pole pokrývají typ subjektu, vypočtené jméno subjektu, aktivitu, čas vytvoření, osobní jmenná pole, data narození a úmrtí, časy obsloužení, přezdívky, poštovní adresy, adresní pole, kontaktní hodnoty, jednotlivé typy kontaktů, skupiny a poznámky. Operátory zahrnují rovnost, nerovnost, menší a větší porovnání, obsahuje, začíná, končí, prázdné a neprázdné. Logické hodnoty záměrně používají jen rovnost.</p>
      <p>Každá stránka si drží vlastní stav komplexního filtru, pokud je její výpis odlišný. Kompaktní Contacts, úplné Subjects a Demo Subjects si proto nepřepisují nastavení komplexního filtru navzájem.</p>
      <ul>
        <li><strong>Režim:</strong> Shoda všech podmínek nebo libovolné podmínky.</li>
        <li><strong>Pole:</strong> Subjekt, osoba, adresa, kontakt, skupina, poznámka, datum, čas, logická hodnota a konkrétní typy kontaktů.</li>
        <li><strong>Stav:</strong> Ukládá se odděleně pro různé subjektové výpisy.</li>
      </ul>
    </dd>
    <dt>Nastavení</dt>
    <dd>
      <p>Nastavení nad oddělovačem patří aktuálnímu výpisu. Obvykle řídí, jestli se zobrazují neaktivní subjekty nebo neaktivní podřízené záznamy. Úplná stránka Subjects je ve výchozím stavu záměrně kompletnější, zatímco kompaktní přehledy skrývají neaktivní řádky důrazněji.</p>
      <p>Nastavení zobrazení států pod oddělovačem je společné pro portálové stránky, které vykreslují poštovní adresy. Ovlivňuje, zda se tiskne název státu a který stát se může v lokálním poštovním výstupu vynechat.</p>
      <p>Většina nastavení se ukládá do session. Mění způsob, jak se stránka vykreslí při dalším sestavení tabulky, ale neupravuje uložené subjekty, kontakty, adresy, skupiny ani poznámky.</p>
      <ul>
        <li><strong>Lokální nastavení:</strong> Řídí aktuální výpis, obvykle neaktivní záznamy.</li>
        <li><strong>Státy:</strong> Sdílené nastavení pro stránky vykreslující poštovní adresy.</li>
        <li><strong>Uložení:</strong> Session nastavení nezapisuje data subjektů.</li>
      </ul>
    </dd>
    <dt>Akce a dialogy</dt>
    <dd>
      <p>Akční ikony uvnitř buňky tabulky platí pro konkrétní položku zobrazenou v této buňce. Editace v buňce kontaktu upravuje danou kontaktní vazbu, editace v buňce adresy upravuje danou adresní vazbu a editace vedle jména subjektu upravuje samotný subjekt.</p>
      <p>Některé agregační stránky rozlišují sdílené akce a akce konkrétního subjektu. Sdílený kontakt nebo sdílená adresa může zasáhnout všechny odpovídající řádky, zatímco akce v buňce Subject upravuje pouze vazbu vypsaného subjektu. Toto rozlišení je podstatné na stránkách Shared Contacts a Addresses.</p>
      <p>Dialogy hodnoty před uložením validují. Kontakty se normalizují podle typu kontaktu, data používají databázový formát data, poštovní směrovací čísla se kontrolují podle metadat státu, pokud jsou dostupná, a destruktivní akce před odesláním vyžadují potvrzení.</p>
      <ul>
        <li><strong>Akce v buňce:</strong> Upravují, mažou, kopírují nebo přidávají položku zobrazenou v buňce.</li>
        <li><strong>Akce subjektu:</strong> Platí pro celý řádek subjektu.</li>
        <li><strong>Destruktivní akce:</strong> Před odesláním vyžadují potvrzení.</li>
      </ul>
    </dd>
    <dt>Tooltipy databázových hodnot</dt>
    <dd>
      <p>Hodnoty z databáze, které mají čas vytvoření a aktualizace, zobrazují tyto časy přímo v tooltipu hodnoty, nikoli v tooltipu akční ikony. Formát je <code>Created: YYYY-MM-DD HH:MM:SS</code> a <code>Updated: YYYY-MM-DD HH:MM:SS</code>.</p>
      <p>Akční ikony si nechávají vlastní tooltipy pro akce. Je tak možné odlišit stáří uložené hodnoty od příkazu, který by hodnotu upravil, smazal, zkopíroval nebo označil jako obslouženou.</p>
      <ul>
        <li><strong>Tooltip hodnoty:</strong> Ukazuje databázový čas vytvoření a aktualizace.</li>
        <li><strong>Tooltip akce:</strong> Popisuje příkaz ikony.</li>
        <li><strong>Formát:</strong> <code>YYYY-MM-DD HH:MM:SS</code>.</li>
      </ul>
    </dd>
  </dl>
  <h3>Stránky v menu</h3>
  <dl class="portal-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Contacts</a></dt>
    <dd>
      <p>Contacts je kompaktní pouze čtecí přehled portálových subjektů. Je určený k rychlému procházení osob a služeb, kopírování hodnot z tabulky a otevírání kontaktních odkazů bez zobrazení celé editační vrstvy. Používá stejné pomocné vykreslování subjektů jako editační stránky, ale záměrně zůstává lehčí a na užších obrazovkách skrývá méně důležité sloupce.</p>
      <p>Tabulka obsahuje vypočtené jméno subjektu, osobní data, přezdívky, poštovní adresy, kontakty, skupiny a poznámky. Hodnoty se zobrazují ve stejném tvaru jako ve zbytku portálu, takže kompaktní přehled je vhodný i ke kontrole toho, jak bude subjekt vypadat mimo úplný editor.</p>
      <p>Rychlý filtr hledá ve viditelném textu tabulky a komplexní filtr umí zúžit sadu subjektů podle subjektu, osoby, adresy, kontaktu, skupiny, poznámky, data, času, logické hodnoty, prázdné hodnoty a konkrétních typů kontaktů. Stav tohoto filtru je oddělený od stránky Subjects, protože kompaktní tabulka má jiný účel.</p>
      <p>Neaktivní subjekty a neaktivní podřízené položky jsou ve výchozím stavu skryté. Nastavení zobrazení států je společné s ostatními adresními stránkami portálu. Plné editační akce se zde nezobrazují; pro úpravy slouží Subjects nebo některý zaměřený agregační editor.</p>
      <ul>
        <li><strong>Data:</strong> Subjekty, vypočtená jména, narození, přezdívky, adresy, kontakty, skupiny a poznámky.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr a vlastní komplexní filtr.</li>
        <li><strong>Nastavení:</strong> Neaktivní subjekty a neaktivní položky jsou ve výchozím stavu skryté.</li>
        <li><strong>Akce:</strong> Dostupné je kopírování a kontaktní odkazy; editace se záměrně nezobrazuje.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>contacts.php">Shared Contacts</a></dt>
    <dd>
      <p>Shared Contacts seskupuje exaktně shodné sdílené kontaktní hodnoty a ukazuje každý subjekt, který danou hodnotu používá. Slouží k hledání znovu použitých telefonů, e-mailů, webových odkazů, komunikačních identifikátorů a profilových URL a k opravě sdílené hodnoty na jednom místě, pokud je špatně samotný uložený kontakt.</p>
      <p>Sloupec Contact představuje sdílený řádek kontaktu. Editace této buňky změní typ kontaktu nebo hodnotu kontaktu pro všechny navázané subjekty. Smazání této buňky odstraní sdílený kontakt a jeho vazby na subjekty. Tyto akce jsou záměrně široké, takže patří jen k situaci, kdy je problém v samotné kontaktní hodnotě.</p>
      <p>Sloupec Subject představuje vazbu mezi jedním subjektem a sdíleným kontaktem. Editace v tomto místě mění pouze tuto vazbu subjekt-kontakt, včetně příznaků primary, active a poznámky. Smazání odstraní jen použití sdíleného kontaktu u daného subjektu.</p>
      <p>Ukládané kontaktní hodnoty se normalizují a validují podle typu kontaktu. Stránka používá stejná pravidla jako úplný editor subjektů, včetně telefonních čísel, e-mailových adres, webových odkazů, komunikačních služeb a známých profilových služeb. Neaktivní kontakty a neaktivní subjekty jsou ve výchozím stavu skryté.</p>
      <ul>
        <li><strong>Tabulka:</strong> Sloupce Contact a Subject.</li>
        <li><strong>Sdílené akce:</strong> Akce v buňce Contact působí na sdílený kontakt a jeho vazby.</li>
        <li><strong>Akce subjektu:</strong> Akce v buňce Subject působí jen na jednu vazbu subjekt-kontakt.</li>
        <li><strong>Validace:</strong> Kontaktní hodnoty se normalizují a kontrolují podle typu kontaktu.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>list.php">Subjects</a></dt>
    <dd>
      <p>Subjects je úplný editor portálu a hlavní místo pro údržbu dat subjektů. V jedné tabulce zobrazuje subjekty typu person, organization, service a other a zpřístupňuje editory samotného subjektu i závislých záznamů subjektu.</p>
      <p>U osob se zobrazované jméno počítá z osobních jmenných polí a souvisejících osobních údajů. U neosobních subjektů vychází zobrazované jméno z řádku jména subjektu. Tabulka dále obsahuje rodné jméno, rodné číslo, data narození a úmrtí, přezdívky, poštovní adresy, kontakty, skupiny, poznámky a dostupné informace o portálovém účtu.</p>
      <p>Plný přístup může vytvářet subjekty, upravovat záznam subjektu, spravovat portálového uživatele připojeného k subjektu a vytvářet, upravovat nebo mazat přezdívky, poštovní adresy, kontakty, přiřazení do skupin a poznámky. Akce vedle jména subjektu patří subjektu; akce uvnitř buňky podřízené hodnoty patří danému podřízenému záznamu nebo vazbě.</p>
      <p>Komplexní filtr na této stránce je nejširší v portálu. Umí kombinovat pole subjektu, vypočtená jména, osobní pole, adresní pole, kontaktní hodnoty, jednotlivé typy kontaktů, členství ve skupinách, poznámky, data, časy, logické hodnoty, prázdné hodnoty a časy obsloužení. Úplná tabulka ve výchozím stavu zobrazuje neaktivní subjekty i neaktivní podřízené položky, aby údržba začínala nad kompletní sadou dat.</p>
      <p>Validace je společná se zaměřenými editory. Data používají <code>YYYY-MM-DD</code>, datum a čas používá databázový datetime formát, rodná čísla obsahují 9 nebo 10 číslic, kontakty se normalizují podle typu kontaktu a poštovní směrovací čísla se kontrolují podle metadat vybraného státu, pokud jsou metadata dostupná.</p>
      <ul>
        <li><strong>Data:</strong> Typ subjektu, vypočtené jméno, osobní pole, přezdívky, adresy, kontakty, skupiny, poznámky a portálový účet.</li>
        <li><strong>Editace:</strong> Plný přístup může vytvářet subjekty a spravovat všechny podřízené záznamy subjektu.</li>
        <li><strong>Typy subjektů:</strong> Osoby používají osobní pole; ostatní typy používají řádky jména subjektu.</li>
        <li><strong>Výchozí stav:</strong> Úplný výpis zobrazuje neaktivní subjekty i neaktivní položky.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>bd.php">Birthdays</a></dt>
    <dd>
      <p>Birthdays zobrazuje osoby, jejichž narozeniny spadají do aktuálního okna obsloužení narozenin: dva dny zpět, dnešek a sedmnáct dnů dopředu. Je to pracovní fronta, ne obecný seznam subjektů, takže subjekty mimo toto okno záměrně chybí.</p>
      <p>Sloupec In ukazuje odchylku ve dnech od dneška a při plném přístupu obsahuje akci obsloužení. Označení narozenin jako obsloužených uloží aktuální čas do <code>birthday_served_at</code> a zároveň aktualizuje <code>inter_served_at</code>, protože narozeninový kontakt se počítá jako vyřízená interakce.</p>
      <p>Narozeniny obsloužené mezi 17 dny před narozeninami a 3 dny po narozeninách se pro dané narozeniny považují za obsloužené. Stránka tím zamezí opakovanému zobrazování stejného výročí a zároveň dovoluje dřívější nebo mírně opožděné vyřízení.</p>
      <p>Viditelné řádky subjektů lze upravovat stejnými dialogy subjektů a podřízených položek jako na stránce Subjects. Vytvoření úplně nového subjektu je zde blokované, ale přidání nebo úprava podřízených záznamů u viditelného subjektu je povolená tam, kde to dovolují běžná oprávnění. Neaktivní subjekty a neaktivní podřízené položky jsou ve výchozím stavu skryté.</p>
      <ul>
        <li><strong>Okno:</strong> Dva dny zpět, dnešek a sedmnáct dnů dopředu.</li>
        <li><strong>Sloupec In:</strong> Ukazuje denní odchylku a akci obsloužení.</li>
        <li><strong>Obsloužení:</strong> Aktualizuje <code>birthday_served_at</code> a <code>inter_served_at</code>.</li>
        <li><strong>Editace:</strong> Nové subjekty jsou blokované, ale viditelný subjekt lze udržovat.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>inter.php">Interactions</a></dt>
    <dd>
      <p>Interactions zobrazuje osoby, jejichž obsloužení komunikace je splatné nyní nebo brzy. Subjekt je splatný, pokud hodnota <code>inter_served_at</code> chybí nebo je dost stará na to, aby další termín interakce spadal do aktuálního okna, které zahrnuje dnešek a dalších dvacet dnů.</p>
      <p>Sloupec In ukazuje, kolik dnů zbývá do termínu interakce. Chybějící předchozí čas obsloužení se bere jako splatnost dnes. Označení interakce jako obsloužené aktualizuje <code>inter_served_at</code> na aktuální čas a odstraní subjekt z aktuálního seznamu splatných interakcí.</p>
      <p>Stránka je určená pro provozní následnou komunikaci, takže umožňuje údržbu viditelného subjektu a jeho existujících závislých záznamů, aniž by se měnila v obecný editor subjektů. Vytvoření nového subjektu je zde záměrně blokované. Neaktivní subjekty a neaktivní podřízené položky jsou ve výchozím stavu skryté a zobrazení států používá stejná sdílená nastavení jako ostatní stránky s adresami.</p>
      <ul>
        <li><strong>Okno:</strong> Splatnost dnes až dalších dvacet dnů.</li>
        <li><strong>Chybějící čas:</strong> Bere se jako splatnost dnes.</li>
        <li><strong>Obsloužení:</strong> Aktualizuje <code>inter_served_at</code> a odstraní řádek ze seznamu.</li>
        <li><strong>Editace:</strong> Existující viditelný subjekt lze udržovat; nové subjekty jsou blokované.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>addresses.php">Addresses</a></dt>
    <dd>
      <p>Addresses seskupuje exaktně shodné poštovní adresní tvary a vypisuje každý subjekt, který daný tvar používá. Hodí se pro hledání domácností, sdílených servisních adres, duplicitních adres a adresních řádků, které se liší jen drobným formátováním nebo jedním polem.</p>
      <p>Sloupec Address představuje poštovní pole tvořící sdílený adresní tvar. V tabulce používá běžný jednořádkový výpis, zatímco kopírovací akce používají víceřádkový poštovní tvar. Editace buňky Address změní adresní pole všech exaktně shodných řádků a zachová subjektové příznaky jako typ adresy, primary, active a poznámku.</p>
      <p>Sloupec Subject představuje jeden řádek adresy subjektu. Editace této buňky mění pouze adresní vazbu daného subjektu, včetně typu adresy, příznaku primary, příznaku active a poznámky. Pokud se při subjektové editaci změní poštovní pole, řádek se po uložení může přesunout do jiné skupiny sdílené adresy.</p>
      <p>Smazání z buňky Address smaže všechny exaktně shodné adresní řádky. Smazání z buňky Subject smaže pouze řádek daného subjektu. Podbarvení subjektu a značka primární adresy patří vazbě subjekt-adresa, ne sdílené skupině adres. Neaktivní adresy a neaktivní subjekty jsou ve výchozím stavu skryté.</p>
      <ul>
        <li><strong>Tabulka:</strong> Sloupce Address a Subject.</li>
        <li><strong>Akce adresy:</strong> Působí na všechny exaktně shodné adresní řádky.</li>
        <li><strong>Akce subjektu:</strong> Působí na jeden adresní řádek subjektu a jeho příznaky.</li>
        <li><strong>Zobrazení:</strong> Kopírování používá víceřádkový poštovní tvar; tabulka jednořádkový tvar.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>groups.php">Groups</a></dt>
    <dd>
      <p>Groups spravuje skupiny subjektů a skupinová portálová oprávnění. Tabulka ukazuje názvy skupin, počet navázaných subjektů, aktivní portálová oprávnění přiřazená skupině, ovládání pořadí, ovládání sloučení a akce editace nebo smazání.</p>
      <p>Plný přístup může skupiny vytvářet, přejmenovávat, měnit jejich aktivitu a pořadí, přiřazovat aktivní portálová oprávnění, slučovat skupiny a mazat skupiny tam, kde je smazání dovolené. Pořadí skupin ovlivňuje, jak se skupiny zobrazují v tabulkách subjektů a dalších seskupených výstupech.</p>
      <p>Slučování je cílové. Nejprve se vybere cílová skupina a potom zdrojové skupiny, které se mají do cíle sloučit. Přiřazení subjektů a povolená oprávnění se zkopírují do cílové skupiny. Volitelné smazání zdrojů odstraní sloučené zdrojové skupiny po přesunu jejich dat.</p>
      <p>Skupina pro portálový přístup je chráněná a nelze ji smazat. Tím se brání rozbití přístupového modelu běžnou údržbovou akcí nad skupinami.</p>
      <ul>
        <li><strong>Tabulka:</strong> Název skupiny, počet subjektů, oprávnění, pořadí, sloučení a akce editace/smazání.</li>
        <li><strong>Editace:</strong> Plný přístup může skupiny vytvářet, přejmenovat, přesouvat, slučovat a mazat tam, kde je to dovoleno.</li>
        <li><strong>Oprávnění:</strong> Aktivní portálová oprávnění lze přiřadit skupinám.</li>
        <li><strong>Omezení:</strong> Skupinu pro portálový přístup nelze smazat.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ctypes.php">Contact Types</a></dt>
    <dd>
      <p>Contact Types spravuje viditelné názvy, aktivitu, pořadí zobrazení a slučování portálových typů kontaktů. Typy kontaktů určují, jak se kontaktní hodnoty popisují, validují, normalizují, nabízejí v dialozích a řadí v tabulkách subjektů.</p>
      <p>Interní klíč <code>contact_type</code> se generuje z viditelného názvu a běžně se needituje přímo. Uložené identifikátory typů kontaktů tak zůstávají předvídatelné a přitom lze viditelný název spravovat z administrační stránky.</p>
      <p>Pořadí typů kontaktů ovlivňuje selecty New Contact a Edit Contact a pořadí, ve kterém se kontaktní hodnoty objevují v buňkách subjektů. Neaktivní typy kontaktů mohou zůstat v databázi pro existující data, ale zmizet z běžných aktivních voleb.</p>
      <p>Typ kontaktu, který už má kontaktní řádky, je nutné před smazáním sloučit. Při sloučení se zdrojové kontaktní řádky přesunou do cílového typu. Pokud odpovídající cílový kontakt už existuje, vazby subjektů se podle možnosti přesunou na existující cílový kontakt, aby zbytečně nevznikaly duplicitní hodnoty.</p>
      <ul>
        <li><strong>Tabulka:</strong> Název, počet kontaktů, aktivita, pořadí, sloučení a akce editace/smazání.</li>
        <li><strong>Technický klíč:</strong> Generuje se z viditelného názvu.</li>
        <li><strong>Pořadí:</strong> Ovlivňuje kontaktní dialogy a pořadí zobrazení kontaktů.</li>
        <li><strong>Smazání:</strong> Typy s existujícími kontakty je nejprve nutné sloučit.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
    <dd>
      <p>Database Structure je stránka s plným přístupem pro SQL strukturu a export portálových tabulek. Kontroluje aktuální databázi, vypisuje tabulky <code>ex_*</code> v pořadí podle závislostí a ukazuje normalizovaný výstup <code>SHOW CREATE TABLE</code> pro revizi.</p>
      <p>Download schema exportuje SQL struktury. Download backup exportuje strukturu i data a je očekávaným vstupem pro Database Difference. Copy schema link a Copy backup link kopírují přímé URL pro stažení, aby bylo možné stejný export získat znovu bez procházení formuláře.</p>
      <p>Stránka databázi neupravuje. Záměrně ale zpřístupňuje metadata a exportovaná data, proto vyžaduje plný přístup.</p>
      <ul>
        <li><strong>Zobrazení:</strong> Tabulky <code>ex_*</code> v pořadí podle závislostí a normalizované SQL struktury.</li>
        <li><strong>Download schema:</strong> Exportuje pouze strukturu.</li>
        <li><strong>Download backup:</strong> Exportuje strukturu i data pro Database Difference.</li>
        <li><strong>Bezpečnost:</strong> Čte metadata a exportuje data bez úprav databáze.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>schema.php">Database Schema</a></dt>
    <dd>
      <p>Database Schema je vizuální prohlížeč schématu portálových tabulek. Čte metadata tabulek, sloupců, klíčů, indexů a vazeb z <code>INFORMATION_SCHEMA</code> a vykresluje schématický pohled na aktuální databázi.</p>
      <p>Boxy tabulek ukazují značky klíčů, názvy sloupců, zkrácené datové typy, nullabilitu a extra atributy. Dlouhé enum definice se ve viditelné tabulce zkracují, ale zůstávají dostupné přes title tooltip, aby diagram zůstal čitelný.</p>
      <p>Sekce vazeb vypisuje cizí klíče a vykresluje spojnice mezi sloupci tabulek tam, kde zařízení zvládne diagram zobrazit. Stránka je pouze pro čtení a slouží k pochopení rozložení tabulek, ne ke kontrole datové konzistence.</p>
      <ul>
        <li><strong>Tabulky:</strong> Značky klíčů, sloupce, zkrácené typy, nullabilita a extra atributy.</li>
        <li><strong>Vazby:</strong> Vypisuje cizí klíče a vykresluje spojnice, pokud je diagram dostupný.</li>
        <li><strong>Rozsah:</strong> Čte metadata tabulek <code>ex_*</code>.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí kontrola schématu.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>check.php">Database Consistency</a></dt>
    <dd>
      <p>Database Consistency je diagnostická stránka s plným přístupem pro porušené nebo podezřelé portálové záznamy. Záznamy neopravuje automaticky; vypisuje, co je potřeba prověřit v databázi nebo přes editory.</p>
      <p>Chybové kontroly zahrnují vazby subjekt-kontakt s chybějícím subjektem nebo kontaktem, řádky osob bez subjektů, řádky osob přiřazené neosobním subjektům, řádky jmen subjektů bez subjektů, řádky jmen subjektů přiřazené osobám, adresy s chybějícími subjekty, přezdívky s chybějícími subjekty, poznámky s chybějícími subjekty, skupinové vazby s chybějícími subjekty a skupinové vazby s chybějícími skupinami.</p>
      <p>Varovné kontroly zahrnují nepřiřazené kontakty ponechané ke kontrole. Horní stav rozlišuje čistou databázi, pouze varování a chyby povinných vazeb. Každá sekce ukazuje počet dotčených řádků a tabulku relevantních databázových sloupců, aby šlo problém dohledat bez hádání.</p>
      <ul>
        <li><strong>Chybové kontroly:</strong> Porušené povinné vazby a záznamy připojené ke špatnému typu subjektu.</li>
        <li><strong>Varování:</strong> Nepřiřazené kontakty ponechané ke kontrole.</li>
        <li><strong>Stav:</strong> Čistá databáze, pouze varování nebo chyby povinných vazeb.</li>
        <li><strong>Výstup:</strong> Počty řádků a dotčené databázové sloupce pro každou kontrolu.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>diff.php">Database Difference</a></dt>
    <dd>
      <p>Database Difference porovnává nahranou SQL zálohu vygenerovanou stránkou Database Structure s aktuální databází. Slouží ke kontrole toho, co se změnilo mezi uloženou portálovou zálohou a živými portálovými tabulkami, zejména jestli osoba nebo subjekt nezmizel, nepřibyl nebo se nezměnil v důležitých zobrazovacích polích.</p>
      <p>Upload parser přijímá příkazy <code>CREATE TABLE</code> a <code>INSERT</code> z portálového zálohového exportu. Před porovnáním se aktuální databáze interně vyexportuje stejným generátorem záloh, takže obě strany se porovnávají ve stejné normalizované podobě.</p>
      <p>Osoby a subjekty se porovnávají jako první, protože při ruční kontrole obvykle záleží právě na nich. Stránka vypisuje chybějící řádky, přidané řádky a změněné hodnoty, například typ subjektu, aktivitu, legacy identifikátor, vypočtené jméno subjektu a osobní detailní pole.</p>
      <p>Stránka dále vypisuje rozdíly struktury a souhrn řádků po tabulkách. Tyto sekce jsou záměrně souhrnné, aby výsledek zůstal čitelný a nezměnil se v úplný nízkoúrovňový diff každé vazby. Nahraná záloha se pouze čte a porovnává; nic se neobnovuje, nemaže ani nezapisuje do databáze.</p>
      <ul>
        <li><strong>Vstup:</strong> SQL záloha ze stránky Database Structure.</li>
        <li><strong>Hlavní porovnání:</strong> Osoby a subjekty se kontrolují jako první.</li>
        <li><strong>Rozdíly:</strong> Chybějící řádky, přidané řádky a změněné důležité hodnoty.</li>
        <li><strong>Bezpečnost:</strong> Upload se pouze porovnává; neprobíhá obnova ani zápis do databáze.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>exlib.php">External Libraries</a></dt>
    <dd>
      <p>External Libraries je filesystemový inventář souborů uložených v <code>ex/lib</code> a vyžaduje plný přístup. Používá se ke kontrole přibalených knihovních souborů a metadat patřících k portálu bez toho, aby stránka cokoli stahovala ze sítě.</p>
      <p>Tabulka ukazuje práva, vlastníka, čas stažení odvozený z času modifikace souboru a název souboru. Rychlý filtr umí viditelný inventář zúžit. Stránka soubory neupravuje a nestahuje aktualizace.</p>
      <ul>
        <li><strong>Adresář:</strong> Čte soubory z <code>ex/lib</code>.</li>
        <li><strong>Sloupce:</strong> Práva, vlastník, čas stažení a název souboru.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr zužuje viditelný inventář.</li>
        <li><strong>Bezpečnost:</strong> Bez síťového přístupu a bez úprav souborů.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>info.php">PHP Info and PHP Credits</a></dt>
    <dd>
      <p>PHP Info and PHP Credits je diagnostická stránka prostředí s plným přístupem. Zpřístupňuje konfiguraci PHP a výstup PHP Credits a slouží ke kontrole běhového prostředí, načtených modulů, konfiguračních hodnot a souvisejících metadat PHP.</p>
      <p>Selector PHP Info umí zobrazit části jako general information, configuration, modules, environment, variables, license nebo all info. Selector PHP Credits umí zobrazit části jako group, general, SAPI, modules, documentation, QA nebo all credits.</p>
      <p>Výstup se standardně načítá do iframe a lze jej otevřít i v novém okně. Stránka je pouze diagnostická a neupravuje portálová data.</p>
      <ul>
        <li><strong>PHP Info:</strong> Vybírá konfiguraci, moduly, prostředí, proměnné, licenci nebo celý výstup.</li>
        <li><strong>PHP Credits:</strong> Vybírá group, general, SAPI, modules, documentation, QA nebo all credits.</li>
        <li><strong>Zobrazení:</strong> Standardně iframe, s možností otevřít výstup zvlášť.</li>
        <li><strong>Bezpečnost:</strong> Pouze diagnostika; portálová data se neupravují.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">Portal Help</a></dt>
    <dd>
      <p>Portal Help je tato dvojjazyčná nápověda. Dokumentuje stránky menu portálu, společné ovládání, filtry, nastavení, očekávaný přístup, rozsahy editací, diagnostické nástroje, exporty a rozdíl mezi sdílenými agregačními akcemi a akcemi konkrétního subjektu.</p>
      <p>Samotná nápověda je pouze pro čtení. Její odkazy používají stejnou základní URL a stejné chování menu jako zbytek portálu, aby bylo možné z dokumentace přejít přímo na příslušnou stránku.</p>
      <ul>
        <li><strong>Rozsah:</strong> Stránky menu a společné chování stránek menu.</li>
        <li><strong>Jazyky:</strong> US English a čeština.</li>
        <li><strong>Odkazy:</strong> Směřují na stejné portálové stránky, které jsou v menu.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>demo.php">Demo Subjects</a></dt>
    <dd>
      <p>Demo Subjects otevírá samostatnou ukázkovou tabulku subjektů. Používá pevně zadaná demonstrační data místo skutečných databázových řádků, takže se dá použít k ověření vykreslování a filtrování bez změn produkčních záznamů.</p>
      <p>Stránka testuje rozložení tabulky subjektů, responsivní skrývání sloupců, rychlý filtr, komplexní filtr, nastavení, modály, vykreslení akcí, formátování kontaktů, formátování adres, kopírovaný text a chování timestamp tooltipů. Záměrně zrcadlí dost z reálné tabulky subjektů, aby byly vidět regrese v UI.</p>
      <p>Demo filtrování používá vlastní session stav a zjednodušený model polí podle ukázkových dat. Demo nastavení může ovlivnit viditelnost neaktivních položek a sdílené volby zobrazení státu, ale samotná ukázková data se do databáze neukládají.</p>
      <ul>
        <li><strong>Data:</strong> Pevně zadané ukázkové subjekty, ne produkční řádky.</li>
        <li><strong>Účel:</strong> Testuje tabulku, responsivitu, filtry, nastavení, modály, akce a formátování.</li>
        <li><strong>Stav:</strong> Demo filtr a nastavení mohou používat session.</li>
        <li><strong>Bezpečnost:</strong> Ukázková data subjektů se nezapisují do databáze.</li>
      </ul>
    </dd>
  </dl>
  <div class="confirm-dialog" id="admin-reusable-dialog" data-reusable-dialog="1" hidden></div>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
