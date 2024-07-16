<?php

namespace App;

use Exception;

require_once "ShowPulseBase.php";

final class StatusRequestDto
{
    public $warnings;
    public $show_id;
    public $sequence_filename;
    public $song_filename;
    public $song_title;
    public $song_artist;
    public $fpp_status_id;

    public function __construct($warnings, $sequence, $song, $fpp_status_id, $showId)
    {
        $this->show_id = $showId;
        $this->fpp_status_id = $fpp_status_id;
        $this->warnings = count($warnings) ?? 0;
        $this->sequence_filename = $sequence;
        $this->song_filename = $song;
        $this->song_title = str_replace("_", " ", str_replace(".fseq", "", $sequence));
        $this->song_artist = null;
    }

    public function assignMediaData($title, $artist)
    {
        $this->song_title = $title;
        $this->song_artist = $artist;
    }
}

final class ShowPulseRequestResponseDto
{
    public $sequence_filename;
    public $priority;

    public function __construct($requestDto)
    {
        $this->sequence_filename = $requestDto->data->sequence_filename;
        $this->priority = $requestDto->data->priority;
    }

    public function isLowPriority()
    {
        return $this->priority == 99;
    }
}

final class ShowPulseWorker extends ShowPulseBase
{
    private $failureCount;
    private $lastSequence;
    private $lastSong;

    public function __construct()
    {
        $this->failureCount = 0;
        $this->lastSequence = null;
        $this->lastSong = null;
    }

    private function idleStatus()
    {
        return 0;
    }

    public function getFailureCount()
    {
        return $this->failureCount;
    }

    public function fifteenMinutesAgo()
    {
        return time() - 900;
    }

    public function getLastSequence()
    {
        return $this->lastSequence;
    }

    public function getFppStatus()
    {
        $fppStatus = $this->httpRequest(true, "fppd/status");

        if ($this->isNullOrEmpty($fppStatus)) {
            $this->logError("Unable to get latest status from FPP.");
        }

        return $fppStatus;
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

    public function postStatus($fppStatus)
    {
        if (
            is_null($fppStatus) ||
            ($this->lastSequence === $fppStatus->current_sequence && $this->lastSong === $fppStatus->current_song)
        ) {
            return;
        }

        $statusDto = new StatusRequestDto(
            $fppStatus->warnings,
            $fppStatus->current_sequence,
            $fppStatus->current_song,
            $fppStatus->status,
            $this->getShowId(),
        );

        if ($this->isNotNullOrEmpty($fppStatus->current_song)) {
            $metaData = $this->httpRequest(true, "media/" . $fppStatus->current_song . "/meta");

            if ($this->isNotNullOrEmpty($metaData) && $this->isNotNullOrEmpty($metaData->format->tags)) {
                $statusDto->assignMediaData($metaData->format->tags->title, $metaData->format->tags->artist);
            }
        }

        $response = $this->httpRequest(
            false,
            "show-statuses/add/" . $this->getShowId(),
            "POST",
            $statusDto
        );

        if ($response->failed) {
            throw new Exception("Unable to update show status. " . $response->message);
        }

        $this->lastSequence = $fppStatus->current_sequence;
        $this->lastSong = $fppStatus->current_song;
    }

    /**
     * @var ShowPulseResponseDto @responseDto
     *
     */
    public function getNextRequest()
    {
        $responseDto = $this->httpRequest(
            false,
            "jukebox-requests/next/" . $this->getShowId(),
            "PUT",
            null
        );

        if (is_null($responseDto) || $responseDto->failed) {
            $this->logError($responseDto->message);
        }

        return new ShowPulseRequestResponseDto($responseDto);
    }

    public function insertNextRequest($requestDto, $fppStatus)
    {
        if (is_null($requestDto)) {
            return false;
        }

        $secondsRemaining = intval($fppStatus->seconds_remaining);
        if ($secondsRemaining > 5 && $requestDto->isLowPriority()) {
            return;
        }

        switch ($requestDto->data->sequence_filename) {
            case "IMMEDIATE STOP":
                $this->stopPlaylist(false);
                break;

            case "IMMEDIATE RESTART":
                $this->stopPlaylist(false);
                $this->systemRestart();
                break;

            case "IMMEDIATE SHUTDOWN":
                $this->stopPlaylist(false);
                $this->systemShutdown();
                break;

            case "GRACEFUL STOP":
                $this->stopPlaylist(false);
                break;

            case "GRACEFUL RESTART":
                $this->stopPlaylist(true);
                $this->systemRestart();
                break;

            case "GRACEFUL SHUTDOWN":
                $this->stopPlaylist(true);
                $this->systemShutdown();
                break;

            default:
                $this->executeFppCommand(
                    "Insert Playlist After Current",
                    array($requestDto->sequence_filename, "-1", "-1", "false")
                );
        }
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
            $status = $this->getFppStatus();

            if ($status === $this->idleStatus()) {
                break;
            }

            sleep($this->sleepShortValue());
        }
    }

    public function calculateSleepTime($fppStatus)
    {
        if (is_null($fppStatus)) {
            return $this->sleepShortValue();
        }

        return $fppStatus->status === $this->idleStatus() ? $this->sleepLongValue() : $this->sleepShortValue();
    }
}
