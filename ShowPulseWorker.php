<?php

namespace App;

use Exception;

require_once "ShowPulseBase.php";

final class ShowPulseStatusRequestDto
{
    public $warnings;
    public $show_id;
    public $sequence_filename;
    public $song_filename;
    public $song_title;
    public $song_artist;
    public $fpp_status_id;

    public function __construct($fppStatus, $sequence_filename, $showId)
    {
        $this->show_id = $showId;
        $this->fpp_status_id = $fppStatus->status;
        $this->warnings = count($fppStatus->warnings);
        $this->sequence_filename = $sequence_filename;
        $this->song_filename = $fppStatus->current_song;
        $this->song_title = str_replace("_", " ", str_replace(".fseq", "", $sequence_filename));
        $this->song_artist = null;
    }

    public function assignMediaData($title, $artist)
    {
        $this->song_title = $title;
        $this->song_artist = $artist;
    }
}

final class ShowPulseJukeboxSelectionResponseDto
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
        return $this->priority == ShowPulseConstant::LOW_PRIORITY;
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

    public function getFailureCount()
    {
        return $this->failureCount;
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

    public function isBelowMaxFailureThreshold()
    {
        return $this->failureCount < ShowPulseConstant::MAX_FAILURES_ALLOWED;
    }

    public function increaseFailureCount()
    {
        if ($this->isBelowMaxFailureThreshold()) {
            $this->failureCount++;
        }
    }

    private function postStatus($statusDto)
    {
        $response = $this->httpRequest(
            false,
            "show-statuses/add/" . $this->getShowId(),
            "POST",
            $statusDto
        );

        if ($response->failed) {
            throw new Exception("Unable to update show status. " . $response->message);
        }
    }

    public function createAndSendStatusToWebsite($fppStatus)
    {
        if (
            is_null($fppStatus) ||
            ($this->lastSequence === $fppStatus->current_sequence && $this->lastSong === $fppStatus->current_song)
        ) {
            return;
        }

        $statusDto = new ShowPulseStatusRequestDto($fppStatus, $fppStatus->current_sequence, $this->getShowId());
        
        if ($this->isNotNullOrEmpty($fppStatus->current_song)) {
            $metaData = $this->httpRequest(true, "media/" . $fppStatus->current_song . "/meta");

            if ($this->isNotNullOrEmpty($metaData) && $this->isNotNullOrEmpty($metaData->format->tags)) {
                $statusDto->assignMediaData($metaData->format->tags->title, $metaData->format->tags->artist);
            }
        }

        $this->postStatus($statusDto);

        $this->lastSequence = $fppStatus->current_sequence;
        $this->lastSong = $fppStatus->current_song;
    }

    /**
     * @var ShowPulseResponseDto @responseDto
     *
     */
    public function getNextRequestFromWebsite()
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

        return new ShowPulseJukeboxSelectionResponseDto($responseDto);
    }

    public function insertNextRequestToFpp($requestDto, $fppStatus)
    {
        if (is_null($requestDto)) {
            return false;
        }

        $secondsRemaining = intval($fppStatus->seconds_remaining);
        if ($secondsRemaining > 5 && $requestDto->isLowPriority()) {
            return;
        }

        switch ($requestDto->data->sequence_filename) {
            case ShowPulseConstant::IMMEDIATE_STOP:
                $this->stopPlaylist(false);
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_STOP, $this->getShowId());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::IMMEDIATE_RESTART:
                $this->stopPlaylist(false);
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_RESTART, $this->getShowId());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::IMMEDIATE_SHUTDOWN:
                $this->stopPlaylist(false);
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_SHUTDOWN, $this->getShowId());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_STOP:
                $this->stopPlaylist(false);
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_STOP, $this->getShowId());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_RESTART:
                $this->stopPlaylist(true);
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_RESTART, $this->getShowId());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_SHUTDOWN:
                $this->stopPlaylist(true);
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_SHUTDOWN, $this->getShowId());
                $this->postStatus($statusDto);
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

            if ($status === ShowPulseConstant::FPP_STATUS_IDLE) {
                break;
            }

            sleep(ShowPulseConstant::SLEEP_SHORT_VALUE);
        }
    }

    public function calculateSleepTime($fppStatus)
    {
        if (is_null($fppStatus)) {
            return ShowPulseConstant::SLEEP_SHORT_VALUE;
        }

        return $fppStatus->status === ShowPulseConstant::FPP_STATUS_IDLE ? ShowPulseConstant::SLEEP_LONG_VALUE : ShowPulseConstant::SLEEP_SHORT_VALUE;
    }
}
