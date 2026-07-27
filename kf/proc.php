<?php

include "main.php";


$aProcResult = runKfExchangeRateProcess($oPdo, $sError);
sendProcResultAndExit($aProcResult["result"], (int)$aProcResult["status_code"]);
