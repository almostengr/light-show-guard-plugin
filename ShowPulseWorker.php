<?php

namespace App;

use Exception;

require_once "ShowPulseBase.php";

final class ShowPulseResponseDto
{
    public $success;
    public $failed;
    public $message;
    public $data;
}

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
    private $sequence_filename;
    private $priority;

    /**
     * Summary of __construct
     * @param ShowPulseResponseDto $responseDto
     */
    public function __construct($responseDto)
    {
        $this->sequence_filename = $responseDto->data->sequence_filename;
        $this->priority = $responseDto->data->priority;
    }

    public function isHighPriority()
    {
        return $this->priority == ShowPulseConstant::HIGH_PRIORITY;
    }

    public function getSequenceFilename()
    {
        return $this->sequence_filename;
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
            "show-statuses/add/" . $this->getShowUuid(),
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

        $statusDto = new ShowPulseStatusRequestDto($fppStatus, $fppStatus->current_sequence, $this->getShowUuid());

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
     * Summary of getNextRequestFromWebsite
     * @var ShowPulseResponseDto @responseDto
     * @return ShowPulseJukeboxSelectionResponseDto|null
     */
    public function getNextRequestFromWebsite()
    {
        $responseDto = $this->httpRequest(
            false,
            "jukebox-selections/next/" . $this->getShowUuid(),
            "PUT",
            null
        );

        if (is_null($responseDto) || $responseDto->failed) {
            $this->logError($responseDto->message);
            return null;
        }

        return new ShowPulseJukeboxSelectionResponseDto($responseDto);
    }

    /**
     * @param ShowPulseJukeboxSelectionResponseDto $selectionResponseDto
     * @param mixed $fppStatus
     */
    public function insertNextRequestToFpp($selectionResponseDto, $fppStatus)
    {
        if (is_null($selectionResponseDto)) {
            return false;
        }

        $secondsRemaining = intval($fppStatus->seconds_remaining);
        if ($secondsRemaining > 5 && !$selectionResponseDto->isHighPriority()) {
            return;
        }

        switch ($selectionResponseDto->getSequenceFilename()) {
            case ShowPulseConstant::IMMEDIATE_STOP:
                $this->stopPlaylist(false);
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_STOP, $this->getShowUuid());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::IMMEDIATE_RESTART:
                $this->stopPlaylist(false);
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_RESTART, $this->getShowUuid());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::IMMEDIATE_SHUTDOWN:
                $this->stopPlaylist(false);
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_SHUTDOWN, $this->getShowUuid());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_STOP:
                $this->stopPlaylist(false);
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_STOP, $this->getShowUuid());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_RESTART:
                $this->stopPlaylist(true);
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_RESTART, $this->getShowUuid());
                $this->postStatus($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_SHUTDOWN:
                $this->stopPlaylist(true);
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_SHUTDOWN, $this->getShowUuid());
                $this->postStatus($statusDto);
                break;

            default:
                $command = "Insert Playlist After Current";
                if ($selectionResponseDto->isHighPriority()) {
                    $command = "Insert Playlist Immediate";
                }

                $this->executeFppCommand(
                    $command,
                    array($selectionResponseDto->getSequenceFilename(), "-1", "-1", "false")
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

    private function executeFppCommand($command, $data = array())
    {
        $args = $command;
        foreach ($data as $value) {
            $args .= "/$value";
        }

        $this->httpRequest(true, $args, "GET", $args);
    }
}
