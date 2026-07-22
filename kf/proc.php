<?php

include "main.php";


if (!$oPdo) {
    error_log("kf/proc.php database error: " . $sError);
    sendProcResultAndExit("ERR", 500);
}

$sRequestedFor = "";
try {
    $sRequestedFor = getExchangeRateRequestedFor();
    if (!reserveExchangeRateFetchAttempt($oPdo, $sRequestedFor)) {
        sendProcResultAndExit("OK");
    }
    $aResponse = fetchExchangeRateApiResponse($sRequestedFor);
    if (!$aResponse["success"]) {
        $sErrorMessage = getExchangeRateApiErrorMessage($aResponse);
        recordExchangeRateFetchError($oPdo, $sRequestedFor, (int)$aResponse["status_code"], $sErrorMessage);
        error_log("kf/proc.php exchange rate fetch failed: " . $sErrorMessage);
        sendProcResultAndExit("ERR", 502);
    }
    $sParseError = "";
    $aRows = parseExchangeRateApiResponse($aResponse["body"], $sParseError);
    if ($aRows === false) {
        recordExchangeRateFetchError($oPdo, $sRequestedFor, (int)$aResponse["status_code"], $sParseError);
        error_log("kf/proc.php exchange rate parse failed: " . $sParseError);
        sendProcResultAndExit("ERR", 502);
    }
    saveExchangeRates($oPdo, $sRequestedFor, $aRows, (int)$aResponse["status_code"]);
    sendProcResultAndExit("OK");
} catch (Exception $oException) {
    error_log((string)$oException);
    if ($sRequestedFor != "") {
        try {
            recordExchangeRateFetchError($oPdo, $sRequestedFor, 0, $oException->getMessage());
        } catch (Exception $oIgnoredException) {
            error_log((string)$oIgnoredException);
        }
    }
    sendProcResultAndExit("ERR", 500);
}
