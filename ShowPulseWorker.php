<?php

namespace App;

use Exception;

require_once "ShowPulseBase.php";

final class StatusDto
{
    public $warnings;
    public $sequence;
    public $song;
    public $song_title;
    public $song_artist;
    public $fpp_status_id;

    public function __construct($warnings, $sequence, $song, $fpp_status_id)
    {
        $this->warnings = count($warnings) ?? 0;
        $this->sequence = $sequence;
        $this->song = $song;
        $this->fpp_status_id = $fpp_status_id;
        $this->song_title = str_replace("_", " ", str_replace(".fseq", "", $sequence));
        $this->song_title = null;
        $this->song_artist = null;
    }

    public function assignMediaData($title, $artist)
    {
        $this->song_title = $title;
        $this->song_artist = $artist;
    }
}

final class ShowPulseWorker extends ShowPulseBase
{
    private $fppStatus;
    private $failureCount;
    private $lastSequence;
    private $lastSong;
    private $lastStatusCheckTime;
    private $lastRequestCheckTime;
    private $skipRequestCheck;

    public function __construct()
    {
        $this->failureCount = 0;
        $this->fppStatus = null;
        $this->lastSequence = null;
        $this->lastStatusCheckTime = $this->fifteenMinutesAgo();
        $this->lastRequestCheckTime = $this->fifteenMinutesAgo();
    }

    public function getFailureCount()
    {
        return $this->failureCount;
    }

    public function fifteenMinutesAgo()
    {
        return time() - 900;
    }

    public function getFppStatus()
    {
        $this->fppStatus = $this->httpRequest(true, "fppd/status");

        if ($this->isNullOrEmpty($this->fppStatus)) {
            throw new Exception("Unable to get latest status from FPP.");
        }
    }

    public function resetFailureCount()
    {
        $this->failureCount = 0;
    }

    public function maxFailuresAllowedValue()
    {
        return 5;
    }

    public function isBelowMaxFailureThreshold()
    {
        return $this->failureCount < $this->maxFailuresAllowedValue();
    }

    public function increaseFailureCount()
    {
        if ($this->isBelowMaxFailureThreshold()) {
            $this->failureCount++;
        }
    }

    public function sleepShortValue()
    {
        return 5;
    }

    public function sleepLongValue()
    {
        return 30;
    }

    public function postStatus()
    {
        $this->getFppStatus();

        if (
            $this->lastSequence === $this->fppStatus->current_sequence &&
            $this->lastSong === $this->fppStatus->current_song
        ) {
            return;
        }

        $statusDto = new StatusDto(
            $this->fppStatus->warnings,
            $this->fppStatus->current_sequence,
            $this->fppStatus->current_song,
            $this->fppStatus->status
        );

        if ($this->isNotNullOrEmpty($this->fppStatus->current_song)) {
            $metaData = $this->httpRequest(
                true,
                "media/" . $this->fppStatus->current_song . "/meta"
            );

            if ($this->isNotNullOrEmpty($metaData) && $this->isNotNullOrEmpty($metaData->format->tags)) {
                $statusDto->assignMediaData($metaData->format->tags->title, $metaData->format->tags->artist);
            }
        }

        $response = $this->httpRequest(
            false,
            "shows/update-status",
            "POST",
            $statusDto,
            $this->getWebsiteAuthorizationHeaders()
        );

        if ($response->failed) {
            throw new Exception("Unable to update show status. " . $response->message);
        }

        $this->lastSequence = $this->fppStatus->current_sequence;
        $this->lastSong = $this->fppStatus->current_song;
        $this->lastStatusCheckTime = time();
        $this->skipRequestCheck = false;
    }

    /**
     * @var ShowPulseResponseDto @responseDto
     *
     */
    public function getAndInsertNextRequest()
    {
        if ($this->skipRequestCheck && $this->lastRequestCheckTime < $this->fifteenMinutesAgo()) {
            return;
        }

        $responseDto = $this->httpRequest(
            false,
            "jukebox-requests/next",
            "GET",
            null,
            $this->getWebsiteAuthorizationHeaders()
        );

        if (is_null($responseDto) || $responseDto->failed) {
            $this->logError($responseDto->message);
            return;
        }

        if (is_null($responseDto->data)) {
            $this->lastRequestCheckTime = time();
            return;
        }

        switch ($responseDto->data) {
            case "SP_STOP_IMMEDIATELY":
                $this->stopPlaylist(false);
                break;

            case "SP_RESTART_IMMEDIATELY":
                $this->stopPlaylist(false);
                $this->systemRestart();
                break;

            case "SP_SHUTDOWN_IMMEDIATELY":
                $this->stopPlaylist(false);
                $this->systemShutdown();
                break;

            case "SP_STOP_GRACEFULLY":
                $this->stopPlaylist(false);
                break;

            case "SP_RESTART_GRACEFULLY":
                $this->stopPlaylist(true);
                $this->systemRestart();
                break;

            case "SP_SHUTDOWN_GRACEFULLY":
                $this->stopPlaylist(true);
                $this->systemShutdown();
                break;

            default:
                $this->executeFppCommand(
                    "Insert Playlist After Current",
                    array($responseDto->data->sequence, "-1", "-1", "false")
                );
        }

        $this->lastRequestCheckTime = time();
    }

    private function systemRestart()
    {
        $this->httpRequest(true, "system/restart");
    }

    private function systemShutdown()
    {
        $this->httpRequest(true, "system/shutdown");
    }

    private function stopPlaylist($isGracefulStop)
    {
        $url = $isGracefulStop ? "playlists/stopgracefully" : "playlists/stop";
        $this->httpRequest(true, $url);

        while ($isGracefulStop) {
            $this->getFppStatus();

            if ($this->fppStatus->status === ShowPulseConstant::IDLE) {
                break;
            }

            sleep($this->sleepShortValue());
        }
    }

    public function calculateSleepTime()
    {
        if (is_null($this->fppStatus)) {
            return $this->sleepShortValue();
        }

        return $this->fppStatus->status === ShowPulseConstant::IDLE ? $this->sleepLongValue() : $this->sleepShortValue();
    }

    public function execute()
    {
        try {
            $this->postStatus();
            // $this->getAndInsertNextRequest();
            $sleepTime = $this->calculateSleepTime();
            sleep($sleepTime);
            $this->resetFailureCount();
        } catch (Exception $e) {
            if ($this->isBelowMaxFailureThreshold()) {
                $message = $e->getMessage() . " (Attempt  $this->failureCount)";
                $this->logError($message);
            }

            $defaultDelay = 2;
            $sleepTime = $this->getFailureCount() * $defaultDelay;

            $this->increaseFailureCount();
            sleep($sleepTime);
        }
    }
}
