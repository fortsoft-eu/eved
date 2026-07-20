<?php

include "main.php";


$blCanEdit = isFullAccessAllowed($aAllowedIps, "kf");
requireViewAccess($aAllowedIps, "kf", "kf_csrf_token");

if (!$oPdo) {
    send500AndExit("Database error: " . $sError);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireFullAccess($aAllowedIps, "kf", "kf_csrf_token");
    requireNamedCsrfToken("kf_csrf_token");
    $sAction = getPostedTrimmedValue("action");
    if ($sAction == "save_debt") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        $sFirstName = getPostedTrimmedValue("first_name");
        $sLastName = getPostedTrimmedValue("last_name");
        $fAmount = parseAmount(getPostedTrimmedValue("amount"));
        $sAccountNumber = getPostedTrimmedValue("account_number");
        $sEmail = getPostedTrimmedValue("email");
        if (($sFirstName == "" && $sLastName == "") || $fAmount === null) {
            setMessage("The debt could not be saved. Name and amount are required.", "error");
            redirect("debts.php");
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
            setMessage("Debt updated.");
        } else {
            $oStatement = $oPdo->prepare("INSERT INTO kf_debts (first_name, last_name, amount, account_number, email) VALUES (:first_name, :last_name, :amount, :account_number, :email)");
            $oStatement->execute(array(
                "first_name" => $sFirstName != "" ? $sFirstName : null,
                "last_name" => $sLastName != "" ? $sLastName : null,
                "amount" => $fAmount,
                "account_number" => $sAccountNumber != "" ? $sAccountNumber : null,
                "email" => $sEmail != "" ? $sEmail : null
            ));
            setMessage("Debt added.");
        }
        redirect("debts.php");
    } elseif ($sAction == "delete_debt") {
        $iId = (int)getPostedTrimmedValue("id", "0");
        if ($iId > 0) {
            $oStatement = $oPdo->prepare("DELETE FROM kf_debts WHERE id = :id");
            $oStatement->execute(array("id" => $iId));
            setMessage("Debt deleted.");
        }
        redirect("debts.php");
    }
}

$aRows = array();
$oStatement = $oPdo->query("SELECT id, first_name, last_name, amount, account_number, email FROM kf_debts ORDER BY last_name ASC, first_name ASC, id ASC");
while ($aRow = $oStatement->fetch()) {
    $aRows[] = $aRow;
}

$sToolbarHtml = "";
if ($blCanEdit) {
    $sToolbarHtml = "    <button type=\"button\" class=\"button-link js-add-debt\" data-modal-target=\"debt-modal\" data-modal-title=\"New Debt\" data-field-id=\"\" data-field-first_name=\"\" data-field-last_name=\"\" data-field-amount=\"\" data-field-account_number=\"\" data-field-email=\"\">New</button>\n";
}

$sTitle = getPageTitle("Debts");
$iTime = sendPageHeaders();

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo html(getCsrfToken("kf_csrf_token")); ?>">
  <title><?php echo html($sTitle); ?></title>
  <meta name="date" content="<?php echo gmdate("D, d M Y H:i:s", $iTime); ?> GMT">
  <link href="<?php echo html($sBaseUrl . "css/admin.css?sToken=" . dechex(filemtime(__DIR__ . "/css/admin.css"))); ?>" rel="stylesheet" type="text/css">
</head>
<body>
  <p class="admin-controls">
<?php renderMenu(); ?>
    <label for="table-filter">Filter:</label>
    <input type="text" id="table-filter" class="js-table-filter" data-table-filter="kf-debts-table" value="">
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="AND">AND</button>
    <button type="button" class="button-link js-filter-operator" data-filter-input="table-filter" data-filter-operator="OR">OR</button>
    <button type="button" class="button-link js-filter-reset" data-filter-input="table-filter">Reset</button>
<?php echo $sToolbarHtml; ?>
  </p>
<?php renderMessage(); ?>
  <table id="kf-debts-table" class="table-filter-target">
    <thead>
      <tr>
        <th>First Name</th>
        <th>Last Name</th>
        <th class="numeric">Amount</th>
        <th>Account Number</th>
        <th>Email</th>
<?php if ($blCanEdit) { ?>
        <th></th>
<?php } ?>
      </tr>
    </thead>
    <tbody>
<?php

$fTotal = 0;
foreach ($aRows as $aRow) {
    $fTotal += (float)$aRow["amount"];
    $sActionCell = "";
    if ($blCanEdit) {
        $sActionCell = "        <td class=\"nowrap\"><button type=\"button\" class=\"button-link\" data-modal-target=\"debt-modal\" data-modal-title=\"Edit Debt\" data-field-id=\"" . (int)$aRow["id"] . "\" data-field-first_name=\"" . html($aRow["first_name"]) . "\" data-field-last_name=\"" . html($aRow["last_name"]) . "\" data-field-amount=\"" . html(formatAmount($aRow["amount"])) . "\" data-field-account_number=\"" . html($aRow["account_number"]) . "\" data-field-email=\"" . html($aRow["email"]) . "\">Edit</button></td>\n";
    }
    echo "      <tr>\n"
        . "        <td>" . htmlValue($aRow["first_name"]) . "</td>\n"
        . "        <td>" . htmlValue($aRow["last_name"]) . "</td>\n"
        . "        <td class=\"numeric\">" . html(formatAmount($aRow["amount"])) . "</td>\n"
        . "        <td>" . htmlValue($aRow["account_number"]) . "</td>\n"
        . "        <td>" . ($aRow["email"] != "" ? "<a href=\"mailto:" . html($aRow["email"]) . "\">" . html($aRow["email"]) . "</a>" : htmlValue("")) . "</td>\n"
        . $sActionCell
        . "      </tr>\n";
}

if ($aRows) {
    echo "      <tr><td colspan=\"2\" class=\"kf-debt-total\">Total</td><td class=\"numeric kf-debt-total\">" . html(formatAmount($fTotal)) . "</td><td colspan=\"" . ($blCanEdit ? 3 : 2) . "\"></td></tr>\n";
} else {
    echo "      <tr><td colspan=\"" . ($blCanEdit ? 6 : 5) . "\">No debts found.</td></tr>\n";
}

?>
    </tbody>
  </table>

<?php if ($blCanEdit) { ?>
  <div id="debt-modal" class="confirm-dialog" hidden>
    <form method="post" class="confirm-dialog-box kf-edit-dialog">
      <div class="confirm-dialog-header"><strong data-modal-heading>Debt</strong><button type="button" class="confirm-dialog-close" data-modal-close aria-label="Close">&times;</button></div>
        <input type="hidden" name="kf_csrf_token" value="<?php echo html(getCsrfToken("kf_csrf_token")); ?>">
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
        <div class="confirm-dialog-actions">
          <button type="submit" class="confirm-dialog-button">Save</button>
          <button type="submit" name="action" value="delete_debt" class="confirm-dialog-button">Delete</button>
          <button type="button" class="confirm-dialog-button" data-modal-close>Cancel</button>
        </div>
    </form>
  </div>
<?php
}

echo "  <button type=\"button\" class=\"filter-focus-button js-filter-focus\" data-filter-input=\"table-filter\" title=\"Focus filter\" aria-label=\"Focus filter\">" . $sFilterFocusEmoji . " Filter</button>\n"
    . "  <script type=\"text/javascript\" src=\"" . html($sBaseUrl . "js/admin.js?sToken=" . dechex(filemtime(__DIR__ . "/js/admin.js"))) . "\"></script>\n"
    . "</body>\n"
    . "</html>\n";

