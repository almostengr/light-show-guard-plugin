<?php

namespace App;

use Exception;

require_once "ShowPulseWorker.php";

$worker = new ShowPulseWorker();
$loadResult = $worker->loadConfiguration();

while ($loadResult) {
    try {
        $fppStatus = $worker->getFppStatus();
        $worker->postStatus($fppStatus);

        $request = $worker->getNextRequest($fppStatus);
        $requestInserted = $worker->insertNextRequest($request, $fppStatus);

        if (!$requestInserted) {
            $sleepTime = $worker->calculateSleepTime($fppStatus);
            sleep($sleepTime);
        }

        $worker->resetFailureCount();
    } catch (Exception $e) {
        if ($worker->isBelowMaxFailureThreshold()) {
            $message = $e->getMessage() . " (Attempt  $worker->failureCount)";
            $worker->logError($message);
        }

        $defaultDelay = 2;
        $sleepTime = $worker->getFailureCount() * $defaultDelay;

        $worker->increaseFailureCount();
        sleep($sleepTime);
    }
}