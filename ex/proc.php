<?php

include "main.php";


$aProcResult = runExCalendarProcess($oPdo, $sError);
sendProcResultAndExit($aProcResult["result"], (int)$aProcResult["status_code"]);
