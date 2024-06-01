<?php

namespace App;

use Exception;

require_once "ShowPulseWorker.php";

$worker = new ShowPulseWorker();
while (true) {
    try {
        $worker->getWebsiteApiKey();
        $worker->getFppStatus();
        $worker->postShowStatus();
        $worker->getNextRequest();
        $sleepTime = $worker->calculateSleepTime();
        sleep($sleepTime);
        $worker->resetAttemptCount();
    } catch (Exception $e) {
        $worker->logError($e->getMessage());
        $sleepTime = $worker->exponentialBackoffSleep();
        $worker->increaseAttemptCount();
        sleep($sleepTime);
    }
}