<?php

namespace App;

use Exception;

require_once "ShowPulseBase.php";

final class StatusDto
{
    public $warnings;
    public $sequence;
    public $song_title;
    public $song_artist;
    public $status;

    public function __construct($hasErrors, $sequence, $status)
    {
        $this->warnings = $hasErrors;
        $this->sequence = $sequence;
        $this->status = $status;
        $this->song_title = str_replace("_", " ", str_replace(".fseq", "", $sequence));
    }

    public function assignMedia($title = null, $artist = null)
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
    private $lastStatusCheckTime;
    private $lastRequestCheckTime;

    public function __construct()
    {
        $this->failureCount = 0;
        $this->fppStatus = null;
        $this->lastSequence = null;
        $this->lastStatusCheckTime = time() - $this->fifteenMinutesAgo();
        $this->lastRequestCheckTime = time() - $this->fifteenMinutesAgo();
    }

    public function getFailureCount()
    {
        return $this->failureCount;
    }

    public function fifteenMinutesAgo()
    {
        return time() - 900;
    }

    public function logError($message)
    {
        if ($this->isBelowMaxFailureThreshold()) {
            $currentDateTime = date('Y-m-d h:i:s A');
            error_log("$currentDateTime: $message (Attempt  $this->failureCount)");
        }
    }

    public function getFppStatus()
    {
        $url = $this->fppUrl("fppd/status");
        $this->fppStatus = $this->httpRequest($url);

        if ($this->isNullOrEmpty($this->fppStatus)) {
            throw new Exception("Unable to get latest status from FPP.");
        }
    }

    public function isTestingOrOfflinePlaylist()
    {
        if ($this->isNullOrEmpty($this->fppStatus)) {
            return false;
        }

        $playlistName = strtolower($this->fppStatus->current_playlist->playlist);
        return strpos($playlistName, 'test') >= 0 || strpos($playlistName, 'offline') >= 0;
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
        if (
            ($this->lastSequence === $this->fppStatus->current_sequence && $this->lastStatusCheckTime < $this->fifteenMinutesAgo()) ||
            $this->isTestingOrOfflinePlaylist()
        ) {
            return;
        }

        $warningCount = count($this->fppStatus->warnings);
        $statusDto = new StatusDto($warningCount, $this->fppStatus->current_sequence, $this->fppStatus->status_name);

        if ($this->isNotNullOrEmpty($this->fppStatus->current_song)) {
            $url = $this->fppUrl("media/" . $this->fppStatus->current_song . "/meta");
            $metaData = $this->httpRequest($url);

            if ($this->isNotNullOrEmpty($metaData) && $this->isNotNullOrEmpty($metaData->format->tags)) {
                $statusDto->assignMedia($metaData->format->tags->title, $metaData->format->tags->artist);
            }
        }

        $url = $this->websiteUrl("statuses/add");
        $response = $this->httpRequest($url, "POST", $statusDto, $this->getWebsiteAuthorizationHeaders());

        if ($response->failed) {
            throw new Exception("Unable to update show status. " . $response->message);
        }

        $this->lastSequence = $this->fppStatus->current_sequence;
        $this->lastStatusCheckTime = time();
    }

    /**
     * @var ShowPulseResponseDto @responseDto
     * 
     */
    public function getAndInsertNextRequest()
    {
        if (
            ($this->lastSequence === $this->fppStatus->current_sequence && $this->lastRequestCheckTime < $this->fifteenMinutesAgo()) ||
            $this->isTestingOrOfflinePlaylist()
        ) {
            return;
        }

        $url = $this->websiteUrl("jukebox_requests/next");
        $responseDto = $this->httpRequest($url, "GET", null, $this->getWebsiteAuthorizationHeaders());

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
        $url = $this->fppUrl("system/restart");
        $this->httpRequest($url);
    }

    private function systemShutdown()
    {
        $url = $this->fppUrl("system/shutdown");
        $this->httpRequest($url);
    }

    private function stopPlaylist($isGracefulStop)
    {
        $url = $isGracefulStop ? $this->fppUrl("playlists/stopgracefully") : $this->fppUrl("playlists/stop");
        $this->httpRequest($url);

        while ($isGracefulStop) {
            $this->getFppStatus();

            if ($this->fppStatus->status_name === $this->idleStatusValue()) {
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

        return $this->fppStatus->status_name === $this->idleStatusValue() ? $this->sleepLongValue() : $this->sleepShortValue();
    }

    public function execute()
    {
        try {
            $this->getWebsiteApiKey();
            $this->getFppStatus();
            $this->postStatus();
            $this->getAndInsertNextRequest();
            $sleepTime = $this->calculateSleepTime();
            sleep($sleepTime);
            $this->resetFailureCount();
        } catch (Exception $e) {
            $this->logError($e->getMessage());

            $defaultDelay = 2;
            $sleepTime = $this->getFailureCount() * $defaultDelay;

            $this->increaseFailureCount();
            sleep($sleepTime);
        }
    }
}
