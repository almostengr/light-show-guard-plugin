<?php

namespace App\Commands;

use App\Commands\JukeboxSelectionInsertNextCommandHandler;
use App\Commands\PostStatusToWebsiteCommandHandler;
use App\Commands\ShowPulseConstant;
use Exception;

require_once "InsertNextJukeboxSelectionCommandHandler.php";
require_once "PostStatusToWebsiteCommandHandler.php";

$postStatusCommand = new PostStatusToWebsiteCommandHandler();
$nextSelectionCommand = new JukeboxSelectionInsertNextCommandHandler();

$failureCount = 0;
$delaySeconds = 2;

do {
    try {
        $postStatusCommand->execute();
        $nextSelectionCommand->execute();

        sleep(ShowPulseConstant::SLEEP_SHORT_VALUE);

        $failureCount = 0;
    } catch (Exception $e) {
        if ($failureCount < ShowPulseConstant::MAX_FAILURES_ALLOWED) {
            $message = $e->getMessage() . " (Attempt  $failureCount)";
            $postStatusCommand->logError($message);
            $failureCount++;
        }

        $sleepTime = $failureCount * $delaySeconds;
        sleep($sleepTime);
    }

    $daemonFileExists = file_exists(ShowPulseConstant::DAEMON_FILE);
} while ($daemonFileExists);

$postStatusCommand->logError("Daemon stopped.");
