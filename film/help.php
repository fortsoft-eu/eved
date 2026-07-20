<?php

include "main.php";


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
  <title><?php echo htmlspecialchars(getPageTitleText("Film Help", $aAllowedIps), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo $sBaseUrl; ?>css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/css/admin.css")); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
  </p>
  <h1>Film Help</h1>
  <h2>US English</h2>
  <h3>Common Controls</h3>
  <dl class="film-help-list">
    <dt>Menu</dt>
    <dd>
      <p>The film menu is built from PHP files in the <code>film</code> directory. <code>index.php</code>, <code>main.php</code>, and <code>functions.php</code> are intentionally excluded from the automatic file scan, because they are the public gallery entry point and shared implementation files rather than menu pages.</p>
      <p>The dropdown starts with a fixed <strong>Film Scans Gallery</strong> link to <code>index.php</code> and a separator. The first automatic menu group contains the photo and film work pages. The second automatic group contains database, PHP, request, schema, and environment diagnostics. This help page is an ordinary menu page and is included by the same file-based menu rule.</p>
      <ul>
        <li><strong>Fixed entry:</strong> Film Scans Gallery opens the public gallery entry point.</li>
        <li><strong>Photo group:</strong> Photographic Equipment, Assign Film to Lab Bag, Film Scans Overview, Photo Lab Orders, and Film Access Log.</li>
        <li><strong>Diagnostic group:</strong> PHP, database, request, stream, schema, and help pages.</li>
        <li><strong>Behavior:</strong> The menu opens next to its button and scrolls away with the page.</li>
      </ul>
    </dd>
    <dt>Access</dt>
    <dd>
      <p>The public gallery entry point and Photographic Equipment are public read-only pages. Other menu pages require either a trusted client or a signed-in user with the matching permission. Read-only administrative overviews use view access, while sensitive diagnostics and write-capable tools require full access.</p>
      <p>The most sensitive pages are the ones that expose request variables, PHP configuration, database server metadata, SQL exports, browser access logs, schema metadata, or assignment forms that write to the database.</p>
      <ul>
        <li><strong>Write page:</strong> Assign Film to Lab Bag can update film roll assignments.</li>
        <li><strong>Full access:</strong> PHP, request, database information, export, OPcache, constants, streams, schema, access-log pages, and the write page.</li>
        <li><strong>View access:</strong> Film scan overview, photo lab orders, and this help page do not modify data.</li>
      </ul>
    </dd>
    <dt>Quick Filter</dt>
    <dd>
      <p>Most table pages include a quick filter. It works on the rendered table text in the browser, so it narrows what is already visible instead of changing the SQL query. The filter value is stored per page and per filter id in the session.</p>
      <p>The AND and OR buttons insert operators into the client-side filter expression. Reset clears the stored value for that page. The floating filter button focuses the filter input on long tables where the control row has scrolled away.</p>
      <ul>
        <li><strong>Scope:</strong> Visible table text only.</li>
        <li><strong>Storage:</strong> Session state, separated by page.</li>
        <li><strong>Controls:</strong> Filter input, AND, OR, Reset, and a floating focus button.</li>
      </ul>
    </dd>
    <dt>Diagnostics and Safety</dt>
    <dd>
      <p>The diagnostic pages are intentionally direct. They show server, PHP, database, request, and browser-client details with minimal processing, because their purpose is to make the current runtime state visible during maintenance.</p>
      <p>Pages that only inspect metadata or runtime state do not write data. The database export page produces schema or backup SQL, and the schema page reads <code>INFORMATION_SCHEMA</code>. The assignment page is the notable exception: it writes <code>lab_order_id</code> on film scan rows and updates the row timestamp.</p>
      <ul>
        <li><strong>No automatic repair:</strong> Diagnostic pages show information but do not correct records.</li>
        <li><strong>Exports:</strong> Database Structure can download schema-only or backup SQL.</li>
        <li><strong>Mutation:</strong> Assign Film to Lab Bag is the database-writing menu page.</li>
      </ul>
    </dd>
  </dl>
  <h3>Public Gallery Entry Point</h3>
  <dl class="film-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Film Scans Public Gallery</a></dt>
    <dd>
      <p><code>index.php</code> is the public gallery entry point. It is intentionally not listed in the film menu, because it is the user-facing film scan viewer rather than an administrative menu page. Without an <code>id</code> parameter it shows the camera image; for an allowed IP address using Firefox with English language it also shows maintenance PHP file links and plain request information.</p>
      <p>With an <code>id</code> parameter, the page loads the selected row from <code>fs_film_scans</code>, derives the archive subdirectory from <code>folder_name</code>, verifies that the directory exists, and renders the selected film roll. The page title is built from the archive number, folder-name parts, and subdirectory. Previous and next buttons point to the nearest lower or higher <code>archive_no</code> whose directory exists.</p>
      <ul>
        <li><strong>Default view:</strong> Camera image when no film roll is selected.</li>
        <li><strong>Selected view:</strong> Metadata controls, previous/next navigation, optional metadata table, and image grid.</li>
        <li><strong>Missing directory:</strong> A selected roll whose archive directory is not present redirects back to the gallery root.</li>
      </ul>
    </dd>
    <dt>Left Panel and Navigation</dt>
    <dd>
      <p>The left panel is built from <code>fs_film_scans</code> ordered by <code>archive_no</code>. A roll is listed only when the folder name contains a usable subdirectory token and that subdirectory exists under the configured scan directory. Selecting a tree item loads the roll into the article area, updates browser history, updates the page title and fixed header, and synchronizes the selected tree node.</p>
      <p>On desktop the navigation panel is open by default and the splitter can resize it horizontally. On small screens the header button opens and hides the panel, and the dark mask closes it. The search and index containers are present in the template, but this page does not render visible tab buttons for them; normal navigation is through the film-roll tree.</p>
      <ul>
        <li><strong>Tree source:</strong> Existing scan directories from <code>fs_film_scans.folder_name</code>.</li>
        <li><strong>History:</strong> Tree clicks and gallery controls update the content area and browser history.</li>
        <li><strong>Responsive behavior:</strong> Desktop panel is persistent and resizable; the panel for portable devices is toggled by the header button.</li>
      </ul>
    </dd>
    <dt>Request Modes and Non-public Operations</dt>
    <dd>
      <p>The page has several request modes, not only the visible gallery view. <code>GET /film/</code> renders the default camera or maintenance view. <code>GET ?id=&lt;id&gt;</code> renders one film roll. <code>GET ?cover=</code>, <code>?metadata=</code>, and <code>?mode=</code> update the session-backed gallery controls. <code>POST</code> with <code>id</code> downloads the metadata text file. <code>POST ?fingerprint=1</code> stores browser fingerprint data for non-allowed visitors and is ignored for allowed IP addresses.</p>
      <p><code>GET ?img=&lt;base-name&gt;</code> is the image delivery endpoint. For allowed IP addresses, the same request branch also accepts <code>set</code>, <code>dir</code>, <code>img</code>, and <code>id</code> together and writes the requested photo status before rendering the current gallery view. The <code>set</code> values are <code>2</code> for public, <code>1</code> for private, and <code>0</code> for internal.</p>
      <ul>
        <li><strong>Public GET:</strong> Root, selected roll, session display switches, and public image delivery.</li>
        <li><strong>Allowed-IP GET:</strong> Status-mode selector and status writes through <code>set/dir/img/id</code>.</li>
        <li><strong>POST id:</strong> Metadata TXT download for the selected roll.</li>
        <li><strong>POST fingerprint:</strong> Access-log enrichment for non-allowed visitors.</li>
      </ul>
    </dd>
    <dt>Gallery Controls</dt>
    <dd>
      <p>The gallery controls are stored in <code>$_SESSION["film"]["gallery"]</code> and are changed with GET parameters. <code>cover</code> switches the thumbnail fitting mode, <code>metadata</code> toggles the metadata table, and <code>mode</code> controls the status filter for allowed IP addresses. The JavaScript handlers load the changed view into the article area, so most control changes do not require a full page reload.</p>
      <p><strong>Contain</strong> keeps the whole image visible inside a square thumbnail and may show background around it. <strong>Cover</strong> fills the square thumbnail and may crop the edges. The metadata button switches between <strong>Show Metadata</strong> and <strong>Hide Metadata</strong>; when visible, the table includes archive number, lab roll number, film stock, expiration date, exposure index, exposure correction, camera, lens, filter, development process, push/pull, lab, exposure dates, scan date, scan format, scan resolution, archive format, and corrections.</p>
      <ul>
        <li><strong>Session state:</strong> Thumbnail fitting, metadata visibility, and internal status mode persist in the current session.</li>
        <li><strong>Contain:</strong> Full image inside the square tile.</li>
        <li><strong>Cover:</strong> Filled square tile with possible cropping.</li>
        <li><strong>Metadata:</strong> Optional table above the image grid.</li>
      </ul>
    </dd>
    <dt>Status Modes</dt>
    <dd>
      <p>The status mode selector is visible only to allowed IP addresses. <strong>All</strong> shows every stored photo status. <strong>OK</strong> hides <code>internal</code> photos and shows public and private photos. <strong>Public</strong> shows only <code>ok_public</code>. <strong>Private</strong> shows only <code>ok_private</code>. <strong>Internal</strong> shows only <code>internal</code>. <strong>Colorized</strong> uses the same broad set as All, but in Contain view it also shows status backgrounds and per-photo status selectors.</p>
      <p>Public visitors are always limited to <code>ok_public</code> images. If a photo file exists in the scan directory but has no row in <code>fs_film_photos</code>, rendering the gallery creates that row as <code>internal</code>. The status selector on an image writes <code>fs_film_photos.status</code> through the current page URL and reloads the article area.</p>
      <ul>
        <li><strong>Public:</strong> Visible to public visitors and returned by direct image requests.</li>
        <li><strong>Private:</strong> Visible to allowed IP addresses, hidden from public visitors.</li>
        <li><strong>Internal:</strong> Default for newly detected files and hidden from public visitors.</li>
        <li><strong>Colorized editing:</strong> Available only for allowed IP addresses in Contain view.</li>
      </ul>
    </dd>
    <dt>Image Grid and Image Viewer</dt>
    <dd>
      <p>The grid is a responsive CSS grid with square thumbnails. Each tile shows the image and a numeric overlay derived from the last four digits of the file base name. In Contain and Colorized mode, allowed IP addresses see colored tile backgrounds: public is green, private is light yellow, and internal is striped orange. Cover mode hides those background cues because the image fills the square.</p>
      <p>Clicking a thumbnail opens the currently rendered image set in an overlay image viewer, starting at the clicked image. The viewer receives the visible images in DOM order, does not loop from the last image back to the first, uses a fade transition, opens the thumbnail strip automatically, and treats the mouse wheel as previous/next navigation. Keyboard handling comes from the viewer: Escape closes it, Delete or Backspace also close it, Page Up and Left/Up arrows go to the previous image, and Page Down and Right/Down arrows go to the next image. Image zooming is disabled for this gallery.</p>
      <ul>
        <li><strong>Order:</strong> Viewer order is the same as the rendered grid order.</li>
        <li><strong>Caption:</strong> File base name is used as title and caption.</li>
        <li><strong>Wheel:</strong> Mouse wheel changes images instead of zooming.</li>
        <li><strong>End behavior:</strong> The sequence is finite, not circular.</li>
      </ul>
    </dd>
    <dt>Downloads and Rendering</dt>
    <dd>
      <p>On desktop, <strong>Save TXT</strong> posts the current film id and downloads the same metadata fields as the visible metadata table. The file name is based on the lab roll code from the folder name and ends with <code>_RAW.txt</code>; if the code cannot be derived, it falls back to <code>film_&lt;archive_no&gt;_RAW.txt</code>.</p>
      <p><strong>Save PNG</strong> captures the gallery content area in the browser at scale 3. Before capture, images using Cover or Contain fitting are temporarily represented as CSS backgrounds so the saved PNG matches the visible thumbnail layout. The button is disabled during capture and re-enabled after the PNG is created or after an error. The generated file name includes the subdirectory, thumbnail fitting mode, allowed-IP status mode when applicable, <code>gallery</code>, and <code>with_metadata</code> when metadata is visible.</p>
      <ul>
        <li><strong>TXT:</strong> Server-side metadata download for the selected roll.</li>
        <li><strong>PNG:</strong> Browser-side screenshot of <code>main-content-gallery</code>.</li>
        <li><strong>Desktop only:</strong> TXT and PNG buttons are hidden on portable-device detection.</li>
      </ul>
    </dd>
    <dt>Image Delivery and Access Logging</dt>
    <dd>
      <p>Direct image requests use <code>?img=&lt;base-name&gt;</code>. The server derives the subdirectory from the first eight characters of the image base name, appends the configured image extension, checks the file, and sends the image with long immutable browser caching. Public image requests must have a matching <code>fs_film_photos</code> row with <code>ok_public</code>; otherwise they return forbidden or not found as appropriate. Allowed IP addresses can request all statuses.</p>
      <p>For visitors outside the allowed IP list, the page starts an access-log request and the browser sends a fingerprint containing GPU, detected fonts, screen size, physical screen size, color depth, time zone, language, platform, plugins, MIME types, and parsed browser and device information. Allowed IP requests clear and ignore that tracking state. Image loads retry up to five times with a cache-busting <code>reload</code> parameter, 500 ms between attempts, and the same retry logic is attached to images added later by dynamic content loading.</p>
      <ul>
        <li><strong>Image endpoint:</strong> <code>?img=</code> serves the configured scan file when access checks pass.</li>
        <li><strong>Public protection:</strong> Only <code>ok_public</code> images are available outside allowed IP addresses.</li>
        <li><strong>Access log:</strong> Non-allowed visitors are logged in <code>fs_film_ua</code>.</li>
        <li><strong>Retry:</strong> Failed image loads are retried up to five times.</li>
      </ul>
    </dd>
  </dl>
  <h3>Menu Pages</h3>
  <dl class="film-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>equip.php">Photographic Equipment</a></dt>
    <dd>
      <p>Photographic Equipment is a read-only inventory of photo equipment stored in <code>fs_photo_equip</code>. It lists equipment type, equipment name, acquisition date, retirement date, and disposition notes, ordered by acquisition date.</p>
      <p>The page is useful for checking historical camera, lens, scanner, and related equipment records without opening the broader database tools. It does not edit equipment and does not expose SQL export controls.</p>
      <ul>
        <li><strong>Data:</strong> Equipment type, name, acquired date, retired date, and disposition note.</li>
        <li><strong>Filtering:</strong> Quick filter over the rendered equipment table.</li>
        <li><strong>Safety:</strong> Read-only page.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>link.php">Assign Film to Lab Bag</a></dt>
    <dd>
      <p>Assign Film to Lab Bag is the maintenance page for linking scanned film rolls to photo lab orders. It is restricted to full access because it updates database rows.</p>
      <p>The form offers unassigned film rolls with archive numbers up to 990 and recent lab orders that have a bag number and were ordered within the last year. After a successful assignment, the selected bag is remembered in the session for the next assignment. The page redirects after POST so a refresh does not repeat the write.</p>
      <p>The detail panel shows the selected order's lab, bag number, order number, price, order date, return date, invoice date, film scan dates, and lab scan dates. The table below shows film rolls and their current assignment state. Unassigning is allowed only while the linked lab order is not older than one year.</p>
      <ul>
        <li><strong>Writes:</strong> Updates <code>fs_film_scans.lab_order_id</code> and <code>updated_at</code>.</li>
        <li><strong>Assign list:</strong> Unassigned film rolls with archive numbers up to 990.</li>
        <li><strong>Order list:</strong> Recent lab orders with bag numbers.</li>
        <li><strong>Unassign:</strong> Allowed only for lab orders from the last year.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>list.php">Film Scans Overview</a></dt>
    <dd>
      <p>Film Scans Overview is the main read-only table for scanned film rolls. It reads <code>fs_film_scans</code>, joins the linked lab order when present, and displays archive number, archive folder name, film stock, cartridge, exposure index, push/pull value, scan date and time, and order number.</p>
      <p>The page also performs lightweight consistency checks against the archive folder name. It compares the archive number, film stock, and cartridge stored in the database with the values parsed from the folder name. Mismatches are marked in the table so naming or metadata problems are visible during review.</p>
      <ul>
        <li><strong>Data:</strong> Film scan metadata and linked order number.</li>
        <li><strong>Checks:</strong> Folder-derived archive number, film stock, and cartridge are compared with stored values.</li>
        <li><strong>Display:</strong> Missing exposure index is shown as unknown; zero scan dates are marked.</li>
        <li><strong>Filtering:</strong> Quick filter over the film scan table.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>orders.php">Photo Lab Orders</a></dt>
    <dd>
      <p>Photo Lab Orders is a read-only overview of lab orders from <code>fs_photo_lab_orders</code>. It joins linked film scan rows and shows which film rolls belong to each lab order, along with scan dates and order-level financial and date fields.</p>
      <p>The page separates film scan dates by film roll and distinct lab scan dates by order. It highlights orders that have not yet been returned, which makes open lab work visible without editing the order records.</p>
      <ul>
        <li><strong>Data:</strong> Lab, order number, bag number, film rolls, prices, currency, order date, scan dates, invoice date, and return date.</li>
        <li><strong>Derived values:</strong> Film roll lists and scan-date lists are built with grouped database queries.</li>
        <li><strong>Warning state:</strong> Missing return date is shown as not yet returned.</li>
        <li><strong>Filtering:</strong> Quick filter over the order table.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ua.php">Film Access Log</a></dt>
    <dd>
      <p>Film Access Log is a full-access diagnostic page for recent film-access browser records. It reads the latest 100 rows from <code>fs_film_ua</code> and joins the requested film scan when available.</p>
      <p>The table combines server-side request data with browser fingerprint details collected by the film pages. It shows IP address, geo headers, parsed user agent, requested film roll and image, GPU, fonts, screen data, timezone, language, platform, plugins, MIME types, and timestamp. Long values are clipped in cells but kept in title tooltips.</p>
      <ul>
        <li><strong>Rows:</strong> Latest 100 access-log records.</li>
        <li><strong>Details:</strong> Browser, device, GPU, fonts, screen, language, plugins, and MIME types.</li>
        <li><strong>Refresh:</strong> Optional auto-refresh every 5 minutes.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>const.php">Defined PHP Constants</a></dt>
    <dd>
      <p>Defined PHP Constants lists constants returned by <code>get_defined_constants(true)</code>. It is a full-access diagnostic page for checking PHP core, extension, and application constants visible in the current runtime.</p>
      <p>Values are converted to readable strings, including booleans, nulls, arrays, special float values, and <code>PHP_EOL</code>. The table keeps the constant group, name, value, and PHP type separate for easier filtering.</p>
      <ul>
        <li><strong>Columns:</strong> Group, constant, value, and type.</li>
        <li><strong>Filtering:</strong> Quick filter over all visible constants.</li>
        <li><strong>Access:</strong> Restricted to full access.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
    <dd>
      <p>Database Structure is the full-access SQL structure and export page for film-related tables. It includes tables whose names match <code>fs_film_*</code>, <code>fs_photo_*</code>, or <code>fs_flickr_*</code>, sorts them by foreign-key dependencies, and displays normalized <code>SHOW CREATE TABLE</code> output.</p>
      <p>The page can download schema-only SQL or a backup containing structure and data. Copy buttons place direct schema and backup download URLs on the clipboard. The page reads metadata and table contents for export, but does not modify the database.</p>
      <ul>
        <li><strong>Scope:</strong> <code>fs_film_*</code>, <code>fs_photo_*</code>, and <code>fs_flickr_*</code> tables.</li>
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
    <dt><a href="<?php echo $sBaseUrl; ?>ext.php">PHP Loaded Extensions</a></dt>
    <dd>
      <p>PHP Loaded Extensions lists the currently loaded PHP extensions from <code>get_loaded_extensions()</code>. It is a full-access diagnostic page for confirming whether required extensions are available to this runtime.</p>
      <ul>
        <li><strong>Columns:</strong> Numeric row number and extension name.</li>
        <li><strong>Filtering:</strong> Quick filter over extension names.</li>
        <li><strong>Safety:</strong> Read-only diagnostic page.</li>
      </ul>
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
    <dt><a href="<?php echo $sBaseUrl; ?>schema.php">Database Schema</a></dt>
    <dd>
      <p>Database Schema is the visual schema viewer for film-related tables. It reads <code>INFORMATION_SCHEMA</code> metadata for <code>fs_film_*</code>, <code>fs_photo_*</code>, and <code>fs_flickr_*</code> tables and renders table boxes, keys, column types, nullability, extra attributes, and foreign-key relations.</p>
      <p>Relation lines are routed for the known film and photo relationships. On small screens, the schema diagram and relation table are hidden and a message is shown instead, because the diagram needs enough space to be readable.</p>
      <ul>
        <li><strong>Tables:</strong> Film, photo, and Flickr-prefixed tables.</li>
        <li><strong>Diagram:</strong> Shows columns, keys, and foreign-key relation lines.</li>
        <li><strong>Relations:</strong> Also listed in a table below the diagram.</li>
        <li><strong>Safety:</strong> Read-only schema inspection.</li>
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
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">Film Help</a></dt>
    <dd>
      <p>Film Help is this bilingual help page. It documents the film menu pages, shared controls, access expectations, diagnostic pages, database exports, and the difference between read-only listings and the assignment page that writes to the database.</p>
      <ul>
        <li><strong>Scope:</strong> Menu pages and the public gallery entry point.</li>
        <li><strong>Languages:</strong> US English and Czech.</li>
        <li><strong>Safety:</strong> Read-only page.</li>
      </ul>
    </dd>
  </dl>
  <h2>Česky</h2>
  <h3>Společné ovládání</h3>
  <dl class="film-help-list">
    <dt>Menu</dt>
    <dd>
      <p>Menu filmu se skládá z PHP souborů v adresáři <code>film</code>. Soubory <code>index.php</code>, <code>main.php</code> a <code>functions.php</code> jsou z automatického skenu záměrně vynechané, protože jde o veřejný vstup galerie a sdílené implementační soubory, ne o stránky menu.</p>
      <p>Dropdown začíná pevnou položkou <strong>Film Scans Gallery</strong> s odkazem na <code>index.php</code> a oddělovačem. První automatická skupina menu obsahuje pracovní stránky pro fotografii a film. Druhá automatická skupina obsahuje diagnostiku databáze, PHP, požadavku, schématu a prostředí. Tato nápověda je běžná stránka menu a do menu se dostává stejným souborovým pravidlem.</p>
      <ul>
        <li><strong>Pevná položka:</strong> Film Scans Gallery otevírá veřejný vstup galerie.</li>
        <li><strong>Foto skupina:</strong> Photographic Equipment, Assign Film to Lab Bag, Film Scans Overview, Photo Lab Orders a Film Access Log.</li>
        <li><strong>Diagnostická skupina:</strong> PHP, databáze, request, streamy, schéma a nápověda.</li>
        <li><strong>Chování:</strong> Menu se otevře u svého tlačítka a odjede se stránkou při rolování.</li>
      </ul>
    </dd>
    <dt>Přístup</dt>
    <dd>
      <p>Veřejný vstup galerie a Photographic Equipment jsou veřejné pouze čtecí stránky. Ostatní stránky menu vyžadují buď trusted klienta, nebo přihlášeného uživatele s odpovídajícím oprávněním. Pouze čtecí administrační přehledy používají view přístup, zatímco citlivá diagnostika a nástroje schopné zápisu vyžadují full přístup.</p>
      <p>Nejcitlivější jsou stránky, které ukazují proměnné požadavku, konfiguraci PHP, metadata databázového serveru, SQL exporty, browser access log, metadata schématu nebo přiřazovací formulář zapisující do databáze.</p>
      <ul>
        <li><strong>Zápisová stránka:</strong> Assign Film to Lab Bag může měnit přiřazení filmů.</li>
        <li><strong>Full přístup:</strong> PHP, request, databázové informace, export, OPcache, konstanty, streamy, schéma, access log a zápisová stránka.</li>
        <li><strong>View přístup:</strong> Filmový přehled, laboratorní objednávky a tato nápověda data nemění.</li>
      </ul>
    </dd>
    <dt>Rychlý filtr</dt>
    <dd>
      <p>Většina tabulkových stránek obsahuje rychlý filtr. Pracuje s vykresleným textem tabulky v prohlížeči, takže zužuje už viditelný výstup a nemění SQL dotaz. Hodnota filtru se ukládá do session odděleně podle stránky a id filtru.</p>
      <p>Tlačítka AND a OR vkládají operátory do klientského filtrovacího výrazu. Reset smaže uloženou hodnotu pro danou stránku. Plovoucí tlačítko filtru vrací fokus do vstupu na dlouhých tabulkách, kde ovládací řádek odroloval.</p>
      <ul>
        <li><strong>Rozsah:</strong> Pouze viditelný text tabulky.</li>
        <li><strong>Uložení:</strong> Session stav oddělený podle stránky.</li>
        <li><strong>Ovládání:</strong> Vstup filtru, AND, OR, Reset a plovoucí tlačítko fokusu.</li>
      </ul>
    </dd>
    <dt>Diagnostika a bezpečnost</dt>
    <dd>
      <p>Diagnostické stránky jsou záměrně přímočaré. Ukazují detaily serveru, PHP, databáze, požadavku a klientského prohlížeče s minimálním zpracováním, protože jejich účelem je zviditelnit aktuální běhový stav při údržbě.</p>
      <p>Stránky, které pouze kontrolují metadata nebo běhový stav, data nezapisují. Databázový export produkuje SQL schéma nebo zálohu a stránka schématu čte <code>INFORMATION_SCHEMA</code>. Výjimkou je stránka Assign Film to Lab Bag: zapisuje <code>lab_order_id</code> do řádků filmových scanů a aktualizuje timestamp řádku.</p>
      <ul>
        <li><strong>Bez automatických oprav:</strong> Diagnostické stránky informace ukazují, ale neopravují záznamy.</li>
        <li><strong>Exporty:</strong> Database Structure umí stáhnout schema-only nebo backup SQL.</li>
        <li><strong>Zápis:</strong> Assign Film to Lab Bag je zapisující stránka menu.</li>
      </ul>
    </dd>
  </dl>
  <h3>Veřejný vstup galerie</h3>
  <dl class="film-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>index.php">Film Scans Public Gallery</a></dt>
    <dd>
      <p><code>index.php</code> je veřejný vstup galerie. Záměrně není ve filmovém menu, protože jde o uživatelský prohlížeč filmových scanů, ne o administrační stránku menu. Bez parametru <code>id</code> zobrazuje obrázek fotoaparátu; pro povolenou IP adresu ve Firefoxu s anglickým jazykem navíc ukazuje údržbové odkazy na PHP soubory a prostý výpis informací o požadavku.</p>
      <p>S parametrem <code>id</code> stránka načte vybraný řádek z <code>fs_film_scans</code>, odvodí archivní podadresář z <code>folder_name</code>, ověří existenci adresáře a vykreslí vybraný film. Titulek stránky se skládá z archivního čísla, částí názvu složky a podadresáře. Tlačítka předchozí a další vedou na nejbližší nižší nebo vyšší <code>archive_no</code>, jehož adresář existuje.</p>
      <ul>
        <li><strong>Výchozí zobrazení:</strong> Obrázek fotoaparátu, pokud není vybraný film.</li>
        <li><strong>Vybraný film:</strong> Ovládání metadat, navigace předchozí/další, volitelná tabulka metadat a grid obrázků.</li>
        <li><strong>Chybějící adresář:</strong> Vybraný film bez archivního adresáře přesměruje zpět na kořen galerie.</li>
      </ul>
    </dd>
    <dt>Levý panel a navigace</dt>
    <dd>
      <p>Levý panel vzniká z <code>fs_film_scans</code> seřazených podle <code>archive_no</code>. Film je v seznamu jen tehdy, když název složky obsahuje použitelný token podadresáře a tento podadresář existuje v nakonfigurovaném adresáři scanů. Výběr položky ve stromu načte film do oblasti článku, upraví historii prohlížeče, titulek stránky i pevnou hlavičku a synchronizuje vybraný uzel stromu.</p>
      <p>Na desktopu je navigační panel standardně otevřený a splitterem ho lze vodorovně měnit. Na malých obrazovkách panel otevírá a skrývá tlačítko v hlavičce a tmavá maska ho zavírá. Kontejnery search a index jsou v šabloně přítomné, ale tato stránka pro ně nevykresluje viditelná tlačítka záložek; běžná navigace probíhá stromem filmů.</p>
      <ul>
        <li><strong>Zdroj stromu:</strong> Existující adresáře scanů podle <code>fs_film_scans.folder_name</code>.</li>
        <li><strong>Historie:</strong> Kliknutí ve stromu i ovládání galerie mění obsah a historii prohlížeče.</li>
        <li><strong>Responsivita:</strong> Desktopový panel je trvalý a měnitelný; panel pro přenosná zařízení se přepíná tlačítkem v hlavičce.</li>
      </ul>
    </dd>
    <dt>Režimy požadavků a neveřejné operace</dt>
    <dd>
      <p>Stránka má několik režimů požadavku, nejen viditelnou galerii. <code>GET /film/</code> vykreslí výchozí obrázek fotoaparátu nebo údržbový pohled. <code>GET ?id=&lt;id&gt;</code> vykreslí jeden film. <code>GET ?cover=</code>, <code>?metadata=</code> a <code>?mode=</code> mění ovládání galerie uložené v session. <code>POST</code> s <code>id</code> stáhne textový soubor metadat. <code>POST ?fingerprint=1</code> uloží browser fingerprint pro návštěvníky mimo povolené IP adresy a pro povolené IP adresy se ignoruje.</p>
      <p><code>GET ?img=&lt;base-name&gt;</code> je endpoint pro doručení obrázku. Pro povolené IP adresy stejná větev požadavku přijímá také společně <code>set</code>, <code>dir</code>, <code>img</code> a <code>id</code> a před vykreslením aktuální galerie zapíše požadovaný status fotky. Hodnoty <code>set</code> jsou <code>2</code> pro public, <code>1</code> pro private a <code>0</code> pro internal.</p>
      <ul>
        <li><strong>Veřejný GET:</strong> Kořen, vybraný film, session přepínače zobrazení a doručení veřejného obrázku.</li>
        <li><strong>GET pro povolenou IP:</strong> Přepínač status mode a zápisy statusu přes <code>set/dir/img/id</code>.</li>
        <li><strong>POST id:</strong> Stažení TXT metadat pro vybraný film.</li>
        <li><strong>POST fingerprint:</strong> Obohacení access logu pro návštěvníky mimo povolené IP adresy.</li>
      </ul>
    </dd>
    <dt>Ovládání galerie</dt>
    <dd>
      <p>Ovládání galerie se ukládá do <code>$_SESSION["film"]["gallery"]</code> a mění se GET parametry. <code>cover</code> přepíná způsob napasování miniatur, <code>metadata</code> přepíná tabulku metadat a <code>mode</code> řídí filtr statusů pro povolené IP adresy. JavaScriptové handlery načítají změněný pohled do oblasti článku, takže většina změn ovládání nevyžaduje plný reload stránky.</p>
      <p><strong>Contain</strong> nechává v čtvercové miniatuře viditelný celý obrázek a může kolem něj ukázat pozadí. <strong>Cover</strong> vyplní čtvercovou miniaturu a může oříznout okraje. Tlačítko metadat přepíná mezi <strong>Show Metadata</strong> a <strong>Hide Metadata</strong>; při zobrazení tabulka obsahuje archivní číslo, číslo laboratorního filmu, film, expiraci, exposure index, exposure correction, fotoaparát, objektiv, filtr, proces vyvolání, push/pull, laboratoř, data expozic, datum scanu, formát scanu, rozlišení scanu, archivní formát a korekce.</p>
      <ul>
        <li><strong>Session stav:</strong> Napasování miniatur, viditelnost metadat a interní status mode zůstávají v aktuální session.</li>
        <li><strong>Contain:</strong> Celý obrázek uvnitř čtvercové dlaždice.</li>
        <li><strong>Cover:</strong> Vyplněná čtvercová dlaždice s možným ořezem.</li>
        <li><strong>Metadata:</strong> Volitelná tabulka nad gridem obrázků.</li>
      </ul>
    </dd>
    <dt>Status modes</dt>
    <dd>
      <p>Selector status mode je viditelný jen pro povolené IP adresy. <strong>All</strong> zobrazí všechny uložené statusy fotek. <strong>OK</strong> skryje <code>internal</code> fotky a ukáže public a private. <strong>Public</strong> ukáže jen <code>ok_public</code>. <strong>Private</strong> ukáže jen <code>ok_private</code>. <strong>Internal</strong> ukáže jen <code>internal</code>. <strong>Colorized</strong> používá stejně širokou množinu jako All, ale v režimu Contain navíc ukazuje statusová pozadí a status selectory u jednotlivých fotek.</p>
      <p>Veřejní návštěvníci jsou vždy omezení na obrázky <code>ok_public</code>. Pokud soubor fotky existuje v adresáři scanu, ale nemá řádek v <code>fs_film_photos</code>, vykreslení galerie tento řádek založí jako <code>internal</code>. Status selector na obrázku zapisuje <code>fs_film_photos.status</code> přes aktuální URL stránky a znovu načte oblast článku.</p>
      <ul>
        <li><strong>Public:</strong> Viditelné veřejným návštěvníkům a vracené přímými požadavky na obrázek.</li>
        <li><strong>Private:</strong> Viditelné pro povolené IP adresy, skryté veřejným návštěvníkům.</li>
        <li><strong>Internal:</strong> Výchozí status nově nalezených souborů a skryté veřejným návštěvníkům.</li>
        <li><strong>Colorized editing:</strong> Dostupné pouze pro povolené IP adresy v režimu Contain.</li>
      </ul>
    </dd>
    <dt>Grid obrázků a prohlížeč obrázků</dt>
    <dd>
      <p>Grid je responsivní CSS mřížka se čtvercovými miniaturami. Každá dlaždice ukazuje obrázek a číselný overlay odvozený z posledních čtyř číslic základního názvu souboru. V režimu Contain a Colorized vidí povolené IP adresy barevná pozadí dlaždic: public je zelené, private světle žluté a internal oranžově pruhované. Režim Cover tyto podkladové signály schová, protože obrázek vyplní celý čtverec.</p>
      <p>Kliknutí na miniaturu otevře aktuálně vykreslenou sadu obrázků v překryvném prohlížeči obrázků od kliknutého snímku. Prohlížeč dostane viditelné obrázky v pořadí DOM, nepřetáčí se z posledního obrázku zpět na první, používá fade přecho d, automaticky otevírá pás miniatur a kolečko myši bere jako navigaci předchozí/další. Ovládání klávesnicí zajišťuje prohlížeč: Escape zavře, Delete nebo Backspace také zavřou, Page Up a šipky vlevo/nahoru jdou na předchozí obrázek a Page Down a šipky vpravo/dolů jdou na další obrázek. Zoom obrázků je pro tuto galerii vypnutý.</p>
      <ul>
        <li><strong>Pořadí:</strong> Pořadí v prohlížeči odpovídá pořadí vykresleného gridu.</li>
        <li><strong>Popisek:</strong> Základní název souboru se používá jako title i caption.</li>
        <li><strong>Kolečko:</strong> Kolečko myši mění obrázky místo zoomování.</li>
        <li><strong>Konec sady:</strong> Sekvence je konečná, ne cyklická.</li>
      </ul>
    </dd>
    <dt>Stahování a renderování</dt>
    <dd>
      <p>Na desktopu <strong>Save TXT</strong> odešle aktuální id filmu a stáhne stejná metadata jako viditelná tabulka metadat. Název souboru vychází z laboratorního kódu z názvu složky a končí <code>_RAW.txt</code>; pokud kód nejde odvodit, použije se fallback <code>film_&lt;archive_no&gt;_RAW.txt</code>.</p>
      <p><strong>Save PNG</strong> zachytí v prohlížeči obsahovou oblast galerie ve scale 3. Před zachycením se obrázky s režimem Cover nebo Contain dočasně reprezentují jako CSS background, aby uložené PNG odpovídalo viditelnému layoutu miniatur. Tlačítko je během renderování vypnuté a po vytvoření PNG nebo po chybě se znovu zapne. Název generovaného souboru obsahuje podadresář, režim napasování miniatur, status mode u povolené IP, <code>gallery</code> a <code>with_metadata</code>, pokud jsou metadata viditelná.</p>
      <ul>
        <li><strong>TXT:</strong> Serverové stažení metadat pro vybraný film.</li>
        <li><strong>PNG:</strong> Browser-side screenshot prvku <code>main-content-gallery</code>.</li>
        <li><strong>Pouze desktop:</strong> Tlačítka TXT a PNG jsou skrytá při detekci přenosného zařízení.</li>
      </ul>
    </dd>
    <dt>Doručení obrázků a access log</dt>
    <dd>
      <p>Přímé požadavky na obrázek používají <code>?img=&lt;base-name&gt;</code>. Server odvodí podadresář z prvních osmi znaků základního názvu obrázku, přidá nakonfigurovanou příponu, ověří soubor a odešle obrázek s dlouhou immutable cache prohlížeče. Veřejný požadavek na obrázek musí mít odpovídající řádek <code>fs_film_photos</code> se statusem <code>ok_public</code>; jinak vrátí zakázáno nebo nenalezeno podle situace. Povolené IP adresy mohou požadovat všechny statusy.</p>
      <p>Pro návštěvníky mimo seznam povolených IP stránka založí access-log požadavek a prohlížeč odešle fingerprint obsahující GPU, detekované fonty, rozměr obrazovky, fyzický rozměr obrazovky, barevnou hloubku, časové pásmo, jazyk, platformu, pluginy, MIME typy a parsované informace o prohlížeči a zařízení. Požadavky z povolených IP adres tento tracking stav mažou a ignorují. Načítání obrázků se při chybě zkouší znovu až pětkrát s cache-busting parametrem <code>reload</code>, po 500 ms, a stejná retry logika se připojuje i k obrázkům přidaným později dynamickým načtením obsahu.</p>
      <ul>
        <li><strong>Image endpoint:</strong> <code>?img=</code> odešle nakonfigurovaný soubor scanu, pokud projdou kontroly přístupu.</li>
        <li><strong>Veřejná ochrana:</strong> Mimo povolené IP adresy jsou dostupné pouze obrázky <code>ok_public</code>.</li>
        <li><strong>Access log:</strong> Návštěvníci mimo povolené IP se zapisují do <code>fs_film_ua</code>.</li>
        <li><strong>Retry:</strong> Neúspěšné načtení obrázku se zkusí až pětkrát.</li>
      </ul>
    </dd>
  </dl>
  <h3>Stránky v menu</h3>
  <dl class="film-help-list">
    <dt><a href="<?php echo $sBaseUrl; ?>equip.php">Photographic Equipment</a></dt>
    <dd>
      <p>Photographic Equipment je pouze čtecí inventář fotografického vybavení uloženého v <code>fs_photo_equip</code>. Vypisuje typ vybavení, název, datum pořízení, datum vyřazení a dispoziční poznámku v pořadí podle data pořízení.</p>
      <p>Stránka slouží ke kontrole historických záznamů fotoaparátů, objektivů, scannerů a souvisejícího vybavení bez otevírání širších databázových nástrojů. Vybavení neupravuje a nenabízí SQL export.</p>
      <ul>
        <li><strong>Data:</strong> Typ vybavení, název, datum pořízení, datum vyřazení a dispoziční poznámka.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr nad tabulkou vybavení.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí stránka.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>link.php">Assign Film to Lab Bag</a></dt>
    <dd>
      <p>Assign Film to Lab Bag je údržbová stránka pro napojení naskenovaných filmů na laboratorní objednávky. Je omezená na full přístup, protože aktualizuje databázové řádky.</p>
      <p>Formulář nabízí nepřiřazené filmy s archivním číslem do 990 a nedávné laboratorní objednávky, které mají číslo sáčku a byly objednané během posledního roku. Po úspěšném přiřazení se vybraný sáček zapamatuje v session pro další přiřazování. Stránka po POSTu přesměruje, aby refresh neopakoval zápis.</p>
      <p>Detailní panel ukazuje laboratoř, číslo sáčku, číslo objednávky, cenu, datum objednání, datum vrácení, datum faktury, data scanů filmů a data laboratorních scanů vybrané objednávky. Tabulka pod formulářem ukazuje filmy a jejich aktuální přiřazení. Odebrání přiřazení je dovoleno jen u objednávky ne starší než jeden rok.</p>
      <ul>
        <li><strong>Zápis:</strong> Aktualizuje <code>fs_film_scans.lab_order_id</code> a <code>updated_at</code>.</li>
        <li><strong>Seznam filmů:</strong> Nepřiřazené filmy s archivním číslem do 990.</li>
        <li><strong>Seznam objednávek:</strong> Nedávné laboratorní objednávky s číslem sáčku.</li>
        <li><strong>Odebrání:</strong> Dovoleno jen pro laboratorní objednávky z posledního roku.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>list.php">Film Scans Overview</a></dt>
    <dd>
      <p>Film Scans Overview je hlavní pouze čtecí tabulka naskenovaných filmů. Čte <code>fs_film_scans</code>, připojuje laboratorní objednávku, pokud existuje, a zobrazuje archivní číslo, název archivní složky, film, cartridge, exposure index, push/pull, datum a čas scanu a číslo objednávky.</p>
      <p>Stránka provádí i lehké konzistenční kontroly vůči názvu archivní složky. Porovnává archivní číslo, film a cartridge uložené v databázi s hodnotami vyčtenými z názvu složky. Neshody jsou označené v tabulce, aby byly při kontrole vidět problémy v pojmenování nebo metadatech.</p>
      <ul>
        <li><strong>Data:</strong> Metadata filmového scanu a číslo navázané objednávky.</li>
        <li><strong>Kontroly:</strong> Archivní číslo, film a cartridge z názvu složky se porovnávají s uloženými hodnotami.</li>
        <li><strong>Zobrazení:</strong> Chybějící exposure index se ukáže jako unknown; nulová data scanu jsou označená.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr nad tabulkou filmových scanů.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>orders.php">Photo Lab Orders</a></dt>
    <dd>
      <p>Photo Lab Orders je pouze čtecí přehled laboratorních objednávek z <code>fs_photo_lab_orders</code>. Připojuje navázané filmové scany a ukazuje, které filmy patří ke které objednávce, společně s daty scanů, finančními poli a daty objednávky.</p>
      <p>Stránka odděluje data filmových scanů podle filmů a odlišná data laboratorních scanů podle objednávky. Objednávky, které ještě nemají datum vrácení, jsou zvýrazněné, takže otevřená laboratorní práce je viditelná bez editace záznamů.</p>
      <ul>
        <li><strong>Data:</strong> Laboratoř, objednávka, sáček, filmy, ceny, měna, objednání, scany, faktura a vrácení.</li>
        <li><strong>Odvozené hodnoty:</strong> Seznamy filmů a scan dates vznikají seskupenými databázovými dotazy.</li>
        <li><strong>Varování:</strong> Chybějící datum vrácení se zobrazuje jako nevráceno.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr nad tabulkou objednávek.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>ua.php">Film Access Log</a></dt>
    <dd>
      <p>Film Access Log je diagnostická stránka s full přístupem pro poslední záznamy přístupů k filmům. Čte posledních 100 řádků z <code>fs_film_ua</code> a podle možnosti připojuje požadovaný filmový scan.</p>
      <p>Tabulka kombinuje serverová request data s browser fingerprint detaily sbíranými filmovými stránkami. Ukazuje IP adresu, geo hlavičky, parsovaný user agent, požadovaný film a obrázek, GPU, fonty, obrazovku, časové pásmo, jazyk, platformu, pluginy, MIME typy a timestamp. Dlouhé hodnoty jsou v buňkách zkrácené, ale zůstávají v title tooltipech.</p>
      <ul>
        <li><strong>Řádky:</strong> Posledních 100 záznamů access logu.</li>
        <li><strong>Detaily:</strong> Prohlížeč, zařízení, GPU, fonty, obrazovka, jazyk, pluginy a MIME typy.</li>
        <li><strong>Refresh:</strong> Volitelný auto-refresh každých 5 minut.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>const.php">Defined PHP Constants</a></dt>
    <dd>
      <p>Defined PHP Constants vypisuje konstanty vrácené funkcí <code>get_defined_constants(true)</code>. Je to diagnostická stránka s full přístupem určená ke kontrole PHP core, extension a aplikačních konstant viditelných v aktuálním běhu.</p>
      <p>Hodnoty se převádějí na čitelné řetězce, včetně boolean, null, polí, speciálních float hodnot a <code>PHP_EOL</code>. Tabulka odděluje skupinu konstanty, název, hodnotu a PHP typ pro snazší filtrování.</p>
      <ul>
        <li><strong>Sloupce:</strong> Skupina, konstanta, hodnota a typ.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes všechny viditelné konstanty.</li>
        <li><strong>Přístup:</strong> Omezeno na full přístup.</li>
      </ul>
    </dd>
    <dt><a href="<?php echo $sBaseUrl; ?>db.php">Database Structure</a></dt>
    <dd>
      <p>Database Structure je stránka s SQL strukturou a exportem filmových tabulek s full přístupem. Zahrnuje tabulky odpovídající <code>fs_film_*</code>, <code>fs_photo_*</code> nebo <code>fs_flickr_*</code>, řadí je podle závislostí cizích klíčů a zobrazuje normalizovaný výstup <code>SHOW CREATE TABLE</code>.</p>
      <p>Stránka umí stáhnout SQL pouze se schématem nebo zálohu obsahující strukturu i data. Kopírovací tlačítka ukládají do schránky přímé odkazy pro stažení schématu a zálohy. Stránka čte metadata a pro export i obsah tabulek, ale databázi neupravuje.</p>
      <ul>
        <li><strong>Rozsah:</strong> Tabulky <code>fs_film_*</code>, <code>fs_photo_*</code> a <code>fs_flickr_*</code>.</li>
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
    <dt><a href="<?php echo $sBaseUrl; ?>ext.php">PHP Loaded Extensions</a></dt>
    <dd>
      <p>PHP Loaded Extensions vypisuje aktuálně načtená PHP rozšíření z <code>get_loaded_extensions()</code>. Jde o diagnostickou stránku s full přístupem určenou ke kontrole, zda má runtime k dispozici potřebná rozšíření.</p>
      <ul>
        <li><strong>Sloupce:</strong> Číslo řádku a název rozšíření.</li>
        <li><strong>Filtrování:</strong> Rychlý filtr přes názvy rozšíření.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí diagnostická stránka.</li>
      </ul>
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
    <dt><a href="<?php echo $sBaseUrl; ?>schema.php">Database Schema</a></dt>
    <dd>
      <p>Database Schema je vizuální prohlížeč schématu filmových tabulek. Čte metadata <code>INFORMATION_SCHEMA</code> pro tabulky <code>fs_film_*</code>, <code>fs_photo_*</code> a <code>fs_flickr_*</code> a vykresluje tabulkové boxy, klíče, typy sloupců, nullabilitu, extra atributy a vazby cizích klíčů.</p>
      <p>Spojovací linie jsou ručně trasované pro známé filmové a fotografické vztahy. Na malých obrazovkách se diagram i tabulka vazeb skryjí a zobrazí se hláška, protože diagram potřebuje dost prostoru, aby byl čitelný.</p>
      <ul>
        <li><strong>Tabulky:</strong> Tabulky s prefixem film, photo a Flickr.</li>
        <li><strong>Diagram:</strong> Ukazuje sloupce, klíče a vazby cizích klíčů.</li>
        <li><strong>Vazby:</strong> Jsou vypsané také v tabulce pod diagramem.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí kontrola schématu.</li>
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
    <dt><a href="<?php echo $sBaseUrl; ?>help.php">Film Help</a></dt>
    <dd>
      <p>Film Help je tato dvojjazyčná nápověda. Dokumentuje stránky filmového menu, společné ovládání, očekávaný přístup, diagnostické stránky, databázové exporty a rozdíl mezi pouze čtecími výpisy a přiřazovací stránkou, která zapisuje do databáze.</p>
      <ul>
        <li><strong>Rozsah:</strong> Stránky menu a veřejný vstup galerie.</li>
        <li><strong>Jazyky:</strong> US English a čeština.</li>
        <li><strong>Bezpečnost:</strong> Pouze čtecí stránka.</li>
      </ul>
    </dd>
  </dl>
  <script type="text/javascript" src="<?php echo $sBaseUrl; ?>js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/js/admin.js")); ?>"></script>
</body>
</html>
