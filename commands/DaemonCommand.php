<?php

namespace App;

use Exception;

require_once 'BaseCommand.php';
require_once 'ShowPulseStatusRequestDto.php';

final class DaemonCommand extends BaseCommand implements ShowPulseCommandInterface
{
    private const MAX_FAILURES_ALLOWED = 5;
    private const ONE_HOUR_IN_SECONDS = 3600;
    private const GRACEFUL_RESTART = "GRACEFUL RESTART";
    private const GRACEFUL_SHUTDOWN = "GRACEFUL SHUTDOWN";
    private const GRACEFUL_STOP = "GRACEFUL STOP";
    private const HIGH_PRIORITY = 10;
    private const IMMEDIATE_RESTART = "IMMEDIATE RESTART";
    private const IMMEDIATE_SHUTDOWN = "IMMEDIATE SHUTDOWN";
    private const IMMEDIATE_STOP = "IMMEDIATE STOP";
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

        if ($this->show === null || $this->show->weather_station_identifier === null || $timeDifference < self::ONE_HOUR_IN_SECONDS) {
            return;
        }

        $url = "stations/" . $this->show->weather_station_identifier . "/observations/latest";
        $this->latestWeather = $this->nwsHttpRequest($url, "GET");
        $this->lastWeatherUpdateTime = time();
    }

    /**
     * Summary of getNextRequestFromWebsite
     * @var ShowPulseResponseDto $responseDto
     * @return ShowPulseSelectionResponseDto|bool
     */
    private function getNextRequestedSelectionFromWebsite()
    {
        $responseDto = $this->webHttpRequest(
            "api/requested-selections/view-next/" . $this->configuration->getUserId()
        );

        // return new ShowPulseResponseDto($responseDto);
        return $responseDto;
    }

    private function getRandomSelection()
    {
        $responseDto = $this->webHttpRequest(
            "api/selection-options/view-random/" . $this->configuration->getUserId()
        );

        return $responseDto;
        // return new ShowPulseResponseDto($responseDto);
    }

    /**
     * @param ShowPulseSelectionResponseDto $selectionResponseDto
     * @param mixed $fppStatus
     */
    protected function postNextRequestedSelectionToFpp($data, $fppStatus)
    {
        if (is_null($data)) {
            return false;
        }

        switch ($data['playlist_name']) {
            case self::IMMEDIATE_STOP:
                $this->stopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::IMMEDIATE_STOP);
                break;

            case self::IMMEDIATE_RESTART:
                $this->stopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::IMMEDIATE_RESTART);
                $this->systemRestart();
                break;

            case self::IMMEDIATE_SHUTDOWN:
                $this->stopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::IMMEDIATE_SHUTDOWN);
                $this->systemShutdown();
                break;

            case self::GRACEFUL_STOP:
                $this->gracefulStopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::GRACEFUL_STOP);
                break;

            case self::GRACEFUL_RESTART:
                $this->gracefulStopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::GRACEFUL_RESTART);
                $this->systemRestart();
                break;

            case self::GRACEFUL_SHUTDOWN:
                $this->gracefulStopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::GRACEFUL_SHUTDOWN);
                $this->systemShutdown();
                break;

            default:
                $command = "Insert Playlist After Current";
                if ($data['priority'] === self::HIGH_PRIORITY) {
                    $command = "Insert Playlist Immediate";
                }

                $args = $command;
                $data = array($data['playlist_name'], "-1", "-1", "false");
                foreach ($data as $value) {
                    $args .= "/$value";
                }

                $this->fppHttpRequest($args, "GET", $args);
        }

        return true;
    }

    private function systemRestart()
    {
        return $this->fppHttpRequest("system/restart");
    }

    private function systemShutdown()
    {
        return $this->fppHttpRequest("system/shutdown");
    }

    private function stopPlaylist()
    {
        return $this->fppHttpRequest("playlists/stop");
    }

    private function gracefulStopPlaylist()
    {
        $this->fppHttpRequest("playlists/stopgracefully");

        $maxLoops = 180; // 180 = 5 seconds loops during 5 minutes
        for ($i = 0; $i < $maxLoops; $i++) {
            $latestStatus = $this->getStatusFromFpp();

            if ($latestStatus->status === self::FPP_STATUS_IDLE_ID) {
                break;
            }

            sleep(5);
        }
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
                    if ($this->latestWeather !== null) {
                        $statusDto->setWeatherObservation($this->latestWeather);
                    }

                    $this->postStatusToWebsite($statusDto);

                    $this->lastSequence = $fppStatus->current_sequence;
                    $this->lastSong = $fppStatus->current_song;
                    $this->lastSecondsPlayed = $fppStatus->seconds_played;

                    // $this->getNextRequestedSelection($fppStatus);

                    $selectionResponse = $this->getNextRequestedSelectionFromWebsite();
                    if ($selectionResponse->getData() === null) {
                        $selectionResponse = $this->getRandomSelection();
                    }

                    $this->postNextRequestedSelectionToFpp($selectionResponse->getData(), $fppStatus, $this->configuration);
                } else if ($isPlayerFrozen) {
                    throw new Exception("Player appears to be frozen.");
                }

                sleep(5);
                $failureCount = 0;
            } catch (Exception $exception) {
                if ($failureCount < self::MAX_FAILURES_ALLOWED + 3) {
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
}

$command = new DaemonCommand();
$command->execute();
