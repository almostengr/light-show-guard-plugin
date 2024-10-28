<?php

namespace App;;

use Exception;

require_once 'BaseCommand.php';

final class DaemonCommand extends BaseCommand implements ShowPulseCommandInterface
{
    private const MAX_FAILURES_ALLOWED = 5;
    private const ONE_HOUR_IN_SECONDS = 3600;
    private $lastWeatherUpdateTime;
    private $latestWeather;
    private $show;
    private $lastSequence;
    private $lastSecondsPlayed;
    private $lastSong;

    public function __construct()
    {
        parent::__construct();

        $this->lastWeatherUpdateTime = time() - self::ONE_HOUR_IN_SECONDS;
    }

    public function execute()
    {
        if (file_exists(self::DAEMON_FILE)) {
            $this->logError("Light Show Pulse Daemon is already running.");
            return;
        }

        file_put_contents(self::DAEMON_FILE, "Running");
        $daemonFileExists = true;

        $failureCount = 0;
        $this->show = $this->getShow();

        do {
            try {
                if ($failureCount >= self::MAX_FAILURES_ALLOWED) {
                    $this->rejectSelectionRequests();
                }

                $fppStatus = $this->getStatusFromFpp();
                $hasStatusUpdate = $this->shouldPostStatus($fppStatus);
                $isPlayerFrozen = $this->checkPlayerFrozen($fppStatus);

                if ($hasStatusUpdate) {
                    $this->getCurrentWeather();
                    // $this->postStatusToWebsite($fppStatus, null, $this->latestWeather);
                    
                    $statusDto = new ShowPulseStatusRequestDto($fppStatus);
                    $statusDto->setWeatherObservation($this->latestWeather);
                    $this->postStatusToWebsite($statusDto);

                    $this->lastSequence = $fppStatus->current_sequence;
                    $this->lastSong = $fppStatus->current_song;
                    $this->lastSecondsPlayed = $fppStatus->seconds_played;

                    $this->getNextRequestedSelection($fppStatus);
                } else if ($isPlayerFrozen) {
                    throw new Exception("Player appears to be frozen.");
                }

                sleep(5);
                $failureCount = 0;
            } catch (Exception $exception) {
                if ($failureCount < self::MAX_FAILURES_ALLOWED) {
                    $failureCount++;
                    $this->logError($exception->getMessage());
                }

                $delaySeconds = 2;
                $sleepTime = $failureCount * $delaySeconds;
                sleep($sleepTime);
            }

            $daemonFileExists = file_exists(self::DAEMON_FILE);
        } while ($daemonFileExists);

        $this->completed();
    }

    private function shouldPostStatus($fppStatus)
    {
        return
            !is_null($fppStatus) &&
            $this->lastSequence === $fppStatus->current_sequence &&
            $this->lastSong === $fppStatus->current_song;
    }

    private function checkPlayerFrozen($fppStatus)
    {
        return $this->lastSequence === $fppStatus->current_sequence && $this->lastSecondsPlayed === $fppStatus->seconds_played;
    }

    private function getCurrentWeather()
    {
        $timeDifference = time() - $this->lastWeatherUpdateTime;

        if ($timeDifference < self::ONE_HOUR_IN_SECONDS || $this->show === null || $this->show->station_identifier === null) {
            return;
        }

        $url = "/stations/" . $this->show->station_identifier . "/observations/latest";
        $this->latestWeather = $this->nwsHttpRequest($url, "GET");
        $this->lastWeatherUpdateTime = time();
    }
}

$command = new DaemonCommand();
$command->execute();
