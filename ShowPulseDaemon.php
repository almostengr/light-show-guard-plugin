<?php

namespace App;

use App\Commands\InsertNextJukeboxSelectionCommand;
use App\Commands\PostStatusToWebsiteCommand;
use Exception;

require_once "ShowPulseWorker.php";

$worker = new ShowPulseWorker();
$loadResult = $worker->loadConfiguration();

$postStatusCommand = new PostStatusToWebsiteCommand();
$nextSelectionCommand = new InsertNextJukeboxSelectionCommand();

while ($loadResult) {
    try {
        // todo convert worker to commands by functionality
        // ask about how to structure commands so that the code can be called. maybe have the command with all the code, and the CommandHandler that does the execution
        // $postStatusCommand->execute();
        // $nextSelectionCommand->execute();

        $fppStatus = $worker->getFppStatus();

        $worker->createAndSendStatusToWebsite($fppStatus);

        $request = $worker->getNextRequestFromWebsite();

        $worker->insertNextRequestToFpp($request, $fppStatus);

        $sleepTime = $worker->calculateSleepTime($fppStatus);
        sleep($sleepTime);

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