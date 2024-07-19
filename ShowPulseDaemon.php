<?php

namespace App;

use App\Commands\InsertNextJukeboxSelectionCommandHandler;
use App\Commands\PostStatusToWebsiteCommandHandler;
use App\Commands\ShowPulseConstant;
use Exception;

require_once "commands/InsertNextJukeboxSelectionCommandHandler.php";
require_once "commands/PostStatusToWebsiteCommandHandler.php";

$postStatusCommand = new PostStatusToWebsiteCommandHandler();
$nextSelectionCommand = new InsertNextJukeboxSelectionCommandHandler();

$failureCount = 0;
$delaySeconds = 2;

while ($loadResult) {
    try {
        $postStatusCommand->execute();
        $nextSelectionCommand->execute();

        $sleepTime = $worker->calculateSleepTime($fppStatus);
        sleep($sleepTime);

        $failureCount = 0;
    } catch (Exception $e) {
        if ($failureCount < ShowPulseConstant::MAX_FAILURES_ALLOWED) {
            $message = $e->getMessage() . " (Attempt  $failureCount)";
            $worker->logError($message);
            $failureCount++;
        }

        $sleepTime = $failureCount * $delaySeconds;
        sleep($sleepTime);
    }
}