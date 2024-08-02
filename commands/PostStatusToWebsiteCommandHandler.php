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
        $configuration = $this->loadConfiguration();
        if (!$configuration) {
            return false;
        }

        $fppStatus = $this->getStatusFromFpp();
        $shouldPost = $this->shouldPostStatus($fppStatus);

        if (!$shouldPost) {
            return true;
        }

        $statusDto = $this->createStatusDto($fppStatus, $configuration);
        $result = $this->postStatusToWebsite($statusDto, $configuration);

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

    private function createStatusDto($fppStatus, $configuration)
    {
        $statusDto = new ShowPulseStatusRequestDto($fppStatus, $fppStatus->current_sequence, $configuration->getShowId());

        if ($this->isNullOrEmpty($fppStatus->current_song)) {
            return $statusDto;
        }

        $url = "media/" . $fppStatus->current_song . "/meta";
        $metaData = $this->httpRequest($url);
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

