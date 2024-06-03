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
    private $lastUpdated;
    private $nextJukeboxRequest;
    private $statusResponse;

    public function __construct()
    {
        $this->failureCount = 0;
        $this->fppStatus = null;
        $this->lastSequence = null;
        $this->nextJukeboxRequest = null;
        $this->statusResponse = null;
        $this->lastUpdated = time() - $this->fifteenMinutesAgo();
    }

    public function getFailureCount()
    {
        return $this->failureCount;
    }

    public function fifteenMinutesAgo()
    {
        return time() - 900;
    }

    public function logFailure($exceptionMessage)
    {
        if ($this->isBelowMaxFailureThreshold()) {
            $message = $exceptionMessage . " (Failure " . $this->failureCount . "/" > $this->maxFailuresAllowedValue() . ")";
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

    public function exponentialSleepTime()
    {
        $defaultDelay = 2;
        return pow(2, $this->failureCount) * $defaultDelay;
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
            ($this->lastSequence === $this->fppStatus->current_sequence && $this->lastUpdated < $this->fifteenMinutesAgo()) ||
            $this->isTestingOrOfflinePlaylist()
        ) {
            $this->statusResponse = null;
            return;
        }

        $errorCount = count($this->fppStatus->warnings);
        $statusDto = new StatusDto($errorCount, $this->fppStatus->current_sequence, $this->fppStatus->status_name);

        if (!empty($this->fppStatus->current_song)) {
            $metaData = $this->getMediaMetaData($this->fppStatus->current_song);

            if (!is_null($metaData) && !is_null($metaData->format->tags)) {
                $statusDto->assignMedia($metaData->format->tags->title, $metaData->format->tags->artist);
            }
        }

        $url = $this->websiteUrl("statuses/add");
        $this->statusResponse = $this->httpRequest($url, "POST", $statusDto, $this->getWebsiteAuthorizationHeaders());

        if ($this->statusResponse->failed) {
            $this->logError("Unable to update show status. " . $this->statusResponse->messsage);
            return;
        }

        $this->lastSequence = $this->fppStatus->current_sequence;
        $this->lastUpdated = time();
    }

    public function insertNextRequest()
    {
        if (is_null($this->statusResponse) || is_null($this->statusResponse->data)) {
            return;
        }

        switch ($this->statusResponse->data->sequence) {
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

    public function calculateSleepTime()
    {
        if (is_null($this->fppStatus)) {
            return $this->sleepShortValue();
        }

        return $this->fppStatus->status_name === "idle" ? $this->sleepLongValue() : $this->sleepShortValue();
    }
}
