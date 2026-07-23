<?php

include "main.php";


if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


requireViewAccess($aAllowedIps, "portal", "lm_csrf_token");


$aAuthors = array();
$oStatement = $oPdo->query("SELECT a.id, a.name, p.page_number FROM fs_authors AS a LEFT JOIN fs_author_pages AS p ON p.authors_id = a.id ORDER BY a.name, p.page_number");

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

$sStyleToken = dechex(filemtime(__DIR__ . "/css/admin.css"));
$sScriptToken = dechex(filemtime(__DIR__ . "/js/admin.js"));

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
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <title>Authors</title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="css/admin.css?sToken=<?php echo $sStyleToken; ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php

renderMenu();

?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="authors-table" value="<?php echo html(getQuickTableFilterValue("table-filter")); ?>" autocomplete="off" spellcheck="false">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
  </p>
  <table id="authors-table" class="table-filter-target authors-table<?php echo getCondensedTableClass(); ?>">
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
  <script type="text/javascript" src="js/admin.js?sToken=<?php echo $sScriptToken; ?>"></script>
</body>
</html>
