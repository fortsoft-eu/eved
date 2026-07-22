<?php

include "config.php";
include "functions.php";


ignore_user_abort(true);
ini_set("session.use_strict_mode", 1);
ini_set("session.use_only_cookies", 1);
ini_set("session.use_trans_sid", 0);
ini_set("session.gc_maxlifetime", 31536000);
session_set_cookie_params(array(
    "lifetime" => 31536000,
    "path" => "/",
    "domain" => "",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax"
));
session_start();


handleQuickTableFilterRequest();


$sError = "";
$oPdo = null;


try {
    $oPdo = new PDO("mysql:host=" . $sDbHost . ";dbname=" . $sDbName . ";charset=utf8mb4", $sDbUserName, $sDbUserPass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false)
    );
} catch (PDOException $oException) {
    error_log((string)$oException);
    $sError = $oException->getMessage();
}

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

$aAuthors = array();
$oStatement = $oPdo->query(
    "SELECT a.id, a.name, p.page_number
    FROM fs_authors AS a
    LEFT JOIN fs_author_pages AS p ON p.authors_id = a.id
    ORDER BY a.name, p.page_number"
);

foreach ($oStatement as $aRow) {
    $iAuthorId = (int)$aRow["id"];
    if (!isset($aAuthors[$iAuthorId])) {
        $aAuthors[$iAuthorId] = array(
            "name" => (string)$aRow["name"],
            "pages" => array()
        );
    }
    if ($aRow["page_number"] !== null) {
        $aAuthors[$iAuthorId]["pages"][] = (int)$aRow["page_number"];
    }
}

$sFilterFocusEmoji = "&#128269;";
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
  <link rel="icon" href="ex/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="ex/favicon.ico" type="image/x-icon">
  <title>Authors</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="ex/css/admin.css?sToken=<?php echo dechex(filemtime(__DIR__ . "/ex/css/admin.css")); ?>" rel="stylesheet" type="text/css">
  <style type="text/css">
    #authors-table th:first-child,
    #authors-table td:first-child {
        white-space: nowrap;
        width: 1px;
    }

    #authors-table th:nth-child(2),
    #authors-table td:nth-child(2) {
        white-space: normal;
        width: auto;
    }
  </style>
</head>
<body>
  <p class="admin-controls">
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="authors-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="authors-table" class="table-filter-target<?php echo getCondensedTableClass(); ?>">
    <thead>
      <tr>
        <th>Author</th>
        <th>Pages</th>
      </tr>
    </thead>
    <tbody>
<?php

if ($aAuthors) {
    foreach ($aAuthors as $aAuthor) {
        echo "      <tr>\n",
            "        <td>" . html($aAuthor["name"]) . "</td>\n",
            "        <td class=\"pages\">" . ($aAuthor["pages"] ? html(implode(", ", $aAuthor["pages"])) : "&mdash;") . "</td>\n",
            "      </tr>\n";
    }
} else {
    echo "      <tr>\n",
        "        <td colspan=\"2\">No authors found.</td>\n",
        "      </tr>\n";
}

?>
    </tbody>
  </table>
  <button type="button" class="filter-focus-button js-filter-focus" data-filter-input="table-filter" title="Focus filter" aria-label="Focus filter"><?php echo $sFilterFocusEmoji; ?> Filter</button>
  <script type="text/javascript" src="ex/js/admin.js?sToken=<?php echo dechex(filemtime(__DIR__ . "/ex/js/admin.js")); ?>"></script>
</body>
</html>
