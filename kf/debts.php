<?php

include "main.php";

requireExFullAccess($aAllowedIps);

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    kfRequireCsrfToken();
    $sAction = kfPostedValue("action");

    if ($sAction == "save_debt") {
        $iId = (int)kfPostedValue("id", "0");
        $sFirstName = kfPostedValue("first_name");
        $sLastName = kfPostedValue("last_name");
        $fAmount = kfParseAmount(kfPostedValue("amount"));
        $sAccountNumber = kfPostedValue("account_number");
        $sEmail = kfPostedValue("email");

        if (($sFirstName == "" && $sLastName == "") || $fAmount === null) {
            kfSetMessage("The debt could not be saved. Name and amount are required.", "error");
            kfRedirect("debts.php");
        }

        if ($iId > 0) {
            $oStatement = $oPdo->prepare("UPDATE kf_debts SET first_name = :first_name, last_name = :last_name, amount = :amount, account_number = :account_number, email = :email WHERE id = :id");
            $oStatement->execute(array(
                "first_name" => $sFirstName != "" ? $sFirstName : null,
                "last_name" => $sLastName != "" ? $sLastName : null,
                "amount" => $fAmount,
                "account_number" => $sAccountNumber != "" ? $sAccountNumber : null,
                "email" => $sEmail != "" ? $sEmail : null,
                "id" => $iId
            ));
            kfSetMessage("Debt updated.");
        } else {
            $oStatement = $oPdo->prepare("INSERT INTO kf_debts (first_name, last_name, amount, account_number, email) VALUES (:first_name, :last_name, :amount, :account_number, :email)");
            $oStatement->execute(array(
                "first_name" => $sFirstName != "" ? $sFirstName : null,
                "last_name" => $sLastName != "" ? $sLastName : null,
                "amount" => $fAmount,
                "account_number" => $sAccountNumber != "" ? $sAccountNumber : null,
                "email" => $sEmail != "" ? $sEmail : null
            ));
            kfSetMessage("Debt added.");
        }
        kfRedirect("debts.php");
    } elseif ($sAction == "delete_debt") {
        $iId = (int)kfPostedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("DELETE FROM kf_debts WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
            kfSetMessage("Debt deleted.");
        }
        kfRedirect("debts.php");
    }
}

$aRows = array();
$oStatement = $oPdo->query("SELECT id, first_name, last_name, amount, account_number, email FROM kf_debts ORDER BY last_name ASC, first_name ASC, id ASC");
while ($aRow = $oStatement->fetch()) {
    $aRows[] = $aRow;
}

$sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-debt\" data-modal-target=\"debt-modal\" data-modal-title=\"New Debt\" data-field-id=\"\" data-field-first_name=\"\" data-field-last_name=\"\" data-field-amount=\"\" data-field-account_number=\"\" data-field-email=\"\">New</button>\n";

$sTitle = kfGetPageTitle("Debts");
$iTime = kfSendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo kfHtml(kfGetCsrfToken()); ?>">
  <title><?php echo kfHtml($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo kfHtml($sBaseUrl . "../ex/css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/../ex/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
  <link href="<?php echo kfHtml($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php kfRenderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="kf-debts-table" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php echo $sToolbarHtml; ?>
  </p>
<?php kfRenderMessage(); ?>
  <table id="kf-debts-table" class="table-filter-target">
    <thead>
      <tr>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Amount</th>
        <th>Account Number</th>
        <th>Email</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
<?php

$fTotal = 0;
foreach ($aRows as $aRow) {
    $fTotal += (float)$aRow["amount"];
    echo "      <tr>\n"
        . "        <td>" . kfHtmlValue($aRow["first_name"]) . "</td>\n"
        . "        <td>" . kfHtmlValue($aRow["last_name"]) . "</td>\n"
        . "        <td class=\"numeric\">" . kfHtml(kfFormatAmount($aRow["amount"])) . "</td>\n"
        . "        <td>" . kfHtmlValue($aRow["account_number"]) . "</td>\n"
        . "        <td>" . ($aRow["email"] != "" ? "<a href=\"mailto:" . kfHtml($aRow["email"]) . "\">" . kfHtml($aRow["email"]) . "</a>" : kfHtmlValue("")) . "</td>\n"
        . "        <td class=\"nowrap\"><button type=\"button\" class=\"button-link\" data-modal-target=\"debt-modal\" data-modal-title=\"Edit Debt\" data-field-id=\"" . (int)$aRow["id"] . "\" data-field-first_name=\"" . kfHtml($aRow["first_name"]) . "\" data-field-last_name=\"" . kfHtml($aRow["last_name"]) . "\" data-field-amount=\"" . kfHtml(kfFormatAmount($aRow["amount"])) . "\" data-field-account_number=\"" . kfHtml($aRow["account_number"]) . "\" data-field-email=\"" . kfHtml($aRow["email"]) . "\">Edit</button></td>\n"
        . "      </tr>\n";
}

if ($aRows) {
    echo "      <tr><th colspan=\"2\" class=\"kf-debt-total\">Total</th><th class=\"numeric kf-debt-total\">" . kfHtml(kfFormatAmount($fTotal)) . "</th><th colspan=\"3\"></th></tr>\n";
} else {
    echo "      <tr><td colspan=\"6\">No debts found.</td></tr>\n";
}

?>
    </tbody>
  </table>

  <div id="debt-modal" class="modal-dialog" hidden>
    <div class="modal-box">
      <div class="modal-header"><strong data-modal-heading>Debt</strong><button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button></div>
      <form method="post" class="modal-form">
        <input type="hidden" name="kf_csrf_token" value="<?php echo kfHtml(kfGetCsrfToken()); ?>">
        <input type="hidden" name="action" value="save_debt">
        <input type="hidden" name="id" value="">
        <label for="first-name">First Name</label>
        <input type="text" id="first-name" name="first_name">
        <label for="last-name">Last Name</label>
        <input type="text" id="last-name" name="last_name">
        <label for="debt-amount">Amount</label>
        <input type="text" id="debt-amount" name="amount" required>
        <label for="account-number">Account Number</label>
        <input type="text" id="account-number" name="account_number">
        <label for="email">Email</label>
        <input type="email" id="email" name="email">
        <div class="modal-actions">
          <button type="submit">Save</button>
          <button type="submit" name="action" value="delete_debt">Delete</button>
          <button type="button" data-modal-close>Cancel</button>
        </div>
      </form>
    </div>
  </div>
<?php

echo "  <button type=\"button\" class=\"filter-focus-button js-filter-focus\" data-filter-input=\"table-filter\" title=\"Focus filter\" aria-label=\"Focus filter\">" . $sFilterFocusEmoji . " Filter</button>\n"
    . "  <script type=\"text/javascript\" src=\"" . kfHtml($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n"
    . "</body>\n"
    . "</html>\n";

