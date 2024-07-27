<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class PostStatusToWebsiteCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    private $lastSequence;
    private $lastSong;

    public function __construct()
    {
        $this->lastSequence = "";
        $this->lastSong = "";
    }

    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();
        if (!$loadSuccessful) {
            return false;
        }

        $fppStatus = $this->getStatusFromFpp();
        $shouldPost = $this->shouldPostStatus($fppStatus);

        if (!$shouldPost) {
            return true;
        }

        $statusDto = $this->createStatusDto($fppStatus);
        $result = $this->postStatusToWebsite($statusDto);

        if ($result) {
            $this->setLatestValues($fppStatus);
        }
        return true;
    }

    private function shouldPostStatus($fppStatus)
    {
        if (
            is_null($fppStatus) ||
            ($this->lastSequence === $fppStatus->current_sequence && $this->lastSong === $fppStatus->current_song)
        ) {
            return false;
        }

        return true;
    }

    private function createStatusDto($fppStatus)
    {
        $statusDto = new ShowPulseStatusRequestDto($fppStatus, $fppStatus->current_sequence, $this->getShowUuid());

        if ($this->isNullOrEmpty($fppStatus->current_song)) {
            return $statusDto;
        }

        $metaData = $this->httpRequest(true, "media/" . $fppStatus->current_song . "/meta");

        if ($this->isNotNullOrEmpty($metaData) && $this->isNotNullOrEmpty($metaData->format->tags)) {
            $statusDto->assignMediaData($metaData->format->tags->title, $metaData->format->tags->artist);
        }

        return $statusDto;
    }

    private function setLatestValues($fppStatus)
    {
        $this->lastSequence = $fppStatus->current_sequence;
        $this->lastSong = $fppStatus->current_song;
    }
}

final class ShowPulseApiResponseDto
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
