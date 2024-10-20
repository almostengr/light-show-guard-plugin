<?php

namespace App\Commands;

use Exception;

require_once 'BaseCommand.php';

final class DaemonCommand extends BaseCommand implements ShowPulseCommandInterface
{
    private const MAX_FAILURES_ALLOWED = 3;

    public function execute()
    {
        $failureCount = 0;
        $lastSequence = "";
        $lastSong = "";
        $lastSecondsPlayed = null;

        if (file_exists(self::DAEMON_FILE)) {
            $this->logError("Daemon is already running.", false);
            return;
        }

        file_put_contents(self::DAEMON_FILE, "");

        do {
            try {
                if ($failureCount >= self::MAX_FAILURES_ALLOWED) {
                    $this->rejectSelectionRequests();
                }

                $fppStatus = $this->getStatusFromFpp();
                $hasStatusUpdate = $this->shouldPostStatus($fppStatus, $lastSequence, $lastSong);
                $isPlayerFrozen = $this->checkPlayerFrozen($fppStatus, $lastSequence, $lastSecondsPlayed);

                if ($hasStatusUpdate) {
                    $this->postStatusToWebsite($fppStatus);

                    $lastSequence = $fppStatus->current_sequence;
                    $lastSong = $fppStatus->current_song;
                    $lastSecondsPlayed = $fppStatus->seconds_played;

                    $this->requestedSelectionGetNext($fppStatus);
                } else if ($isPlayerFrozen) {
                    // $this->fppHttpRequest("apiv1/player-stuck", "POST");// todo future feature
                    $this->logError("Player appears to be frozen.", true);
                }

                sleep(5);
                $failureCount = 0;
            } catch (Exception) {
                if ($failureCount < self::MAX_FAILURES_ALLOWED) {
                    $failureCount++;
                }

                $delaySeconds = 2;
                $sleepTime = $failureCount * $delaySeconds;
                sleep($sleepTime);
            }

            $daemonFileExists = file_exists(self::DAEMON_FILE);
        } while ($daemonFileExists);

        $this->logError("Daemon stopped.", false);
    }

    private function shouldPostStatus($fppStatus, $lastSequence, $lastSong)
    {
        if (
            !is_null($fppStatus) &&
            $lastSequence === $fppStatus->current_sequence &&
            $lastSong === $fppStatus->current_song
        ) {
            return false;
        }

        return true;
    }

    private function checkPlayerFrozen($fppStatus, $lastSequence, $lastSecondsPlayed)
    {
        if (
            $lastSequence === $fppStatus->current_sequence &&
            $lastSecondsPlayed === $fppStatus->seconds_played
        ) {
            return true;
        }

        return false;
    }
}

$command = new DaemonCommand();
$command->execute();
