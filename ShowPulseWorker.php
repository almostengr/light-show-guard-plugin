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
        if (!is_null($title)) {
            $this->song_title = $title;
        }

        if (!is_null($artist)) {
            $this->song_artist = $artist;
        }
    }
}

final class ShowPulseWorker extends ShowPulseBase
{
    private $fppStatus;
    private $failureCount;
    private $lastSequence;
    private $nextJukeboxRequest;

    public function __construct()
    {
        $this->failureCount = 0;
        $this->fppStatus = null;
        $this->lastSequence = null;
        $this->nextJukeboxRequest = null;
    }

    public function getFailureCount()
    {
        return $this->failureCount;
    }

    public function logFailure($exceptionMessage)
    {
        if ($this->isBelowMaxFailureThreshold()) {
            $message = $exceptionMessage . " (Failure " . $this->failureCount . "/" > $this->maxFailuresAllowed() . ")";
            $this->logError($message);
        }
    }

    public function getFppStatus()
    {
        $url = $this->fppUrl("fppd/status");
        $this->fppStatus = $this->httpRequest($url);

        if (is_null($this->fppStatus)) {
            throw new Exception("Unable to get latest status from FPP.");
        }
    }

    public function getMediaMetaData($filename = null)
    {
        if (is_null($filename) || empty($filename)) {
            return null;
        }

        $url = $this->fppUrl("media/$filename/meta");
        return $this->httpRequest($url);
    }

    public function isTestingOrOfflinePlaylist()
    {
        if (is_null($this->fppStatus)) {
            return false;
        }

        $playlistName = strtolower($this->fppStatus->current_playlist->playlist);
        return strpos($playlistName, 'test') >= 0 || strpos($playlistName, 'offline') >= 0;
    }

    public function exponentialBackoffSleep()
    {
        $defaultDelay = 2;
        $maxDelay = 15;
        return min(pow(2, $this->failureCount) * $defaultDelay, $maxDelay);
    }

    public function resetFailureCount()
    {
        $this->failureCount = 0;
    }

    public function maxFailuresAllowed()
    {
        return 5;
    }

    public function isBelowMaxFailureThreshold()
    {
        return $this->failureCount < $this->maxFailuresAllowed();
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

    public function getNextRequest()
    {
        $secondsRemaining = intval($this->fppStatus->seconds_remaining);
        if ($secondsRemaining > $this->sleepShortValue()) {
            return;
        }

        $url = $this->websiteUrl("requests/next");
        $this->nextJukeboxRequest = $this->httpRequest($url, "PUT", null, $this->getWebsiteAuthorizationHeaders());

        if ($this->nextJukeboxRequest->failed) {
            $this->logError("Unable to get latest jukebox request from server. " . $this->nextJukeboxRequest->message);
            return;
        }

        if (is_null($this->nextJukeboxRequest->data)) {
            return;
        }

        switch ($this->nextJukeboxRequest->data->sequence) {
            case "SPSTOPSHOW":
                $url = $this->fppUrl("playlists/stopgracefully");
                $this->httpRequest($url);
                break;

            case "SPRESTART":
                $url = $this->fppUrl("system/restart");
                $this->httpRequest($url);
                break;

            case "SPSHUTDOWN":
                $url = $this->fppUrl("system/shutdown");
                $this->httpRequest($url);
                break;

            default:
                $this->executeFppCommand(
                    "Insert Playlist After Current",
                    array($this->nextJukeboxRequest->data->sequence, "-1", "-1", "false")
                );
        }
    }

    public function postShowStatus()
    {
        if (
            $this->lastSequence === $this->fppStatus->current_sequence ||
            $this->isTestingOrOfflinePlaylist()
        ) {
            return;
        }

        $hasErrors = count($this->fppStatus->warnings);
        $statusDto = new StatusDto($hasErrors, $this->fppStatus->current_sequence, $this->fppStatus->status_name);

        if (!empty($this->fppStatus->current_song)) {
            $metaData = $this->getMediaMetaData($this->fppStatus->current_song);

            if (!is_null($metaData) && !is_null($metaData->format->tags)) {
                $statusDto->assignMedia($metaData->format->tags->title, $metaData->format->tags->artist);
            }
        }

        $url = $this->websiteUrl("statuses/add");
        $result = $this->httpRequest($url, "POST", $statusDto, $this->getWebsiteAuthorizationHeaders());

        if ($result->failed) {
            $this->logError("Unable to update show status. " . $result->messsage);
            return;
        }

        $this->lastSequence = $this->fppStatus->current_sequence;
    }

    public function calculateSleepTime()
    {
        if (is_null($this->fppStatus)) {
            return $this->sleepShortValue();
        }

        return $this->fppStatus->status_name === "idle" ? $this->sleepLongValue() : $this->sleepShortValue();
    }
}
