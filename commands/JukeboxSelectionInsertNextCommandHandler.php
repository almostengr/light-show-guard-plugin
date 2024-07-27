<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class JukeboxSelectionInsertNextCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $loadSuccessful = $this->loadConfiguration();
        if (!$loadSuccessful) {
            return false;
        }

        $fppStatus = $this->getStatusFromFpp();
        $selectionResponse = $this->getNextSelectionFromWebsite();
        $this->postNextSelectionToFpp($selectionResponse, $fppStatus);
    }

    /**
     * Summary of getNextRequestFromWebsite
     * @var ShowPulseApiResponseDto $responseDto
     * @return ShowPulseJukeboxSelectionResponseDto|bool
     */
    private function getNextSelectionFromWebsite()
    {
        $responseDto = $this->httpRequest(
            false,
            "jukebox-selections/next/" . $this->getShowUuid(),
            "PUT",
            null
        );

        if (is_null($responseDto) || $responseDto->failed) {
            return $this->logError($responseDto->message);            
        }

        return new ShowPulseJukeboxSelectionResponseDto($responseDto);
    }

    /**
     * @param ShowPulseJukeboxSelectionResponseDto $selectionResponseDto
     * @param mixed $fppStatus
     */
    private function postNextSelectionToFpp($selectionResponseDto, $fppStatus)
    {
        if (is_null($selectionResponseDto)) {
            return false;
        }

        $secondsRemaining = intval($fppStatus->seconds_remaining);
        if ($secondsRemaining > 5 && !$selectionResponseDto->isHighPriority()) {
            return true;
        }

        switch ($selectionResponseDto->getSequenceFilename()) {
            case ShowPulseConstant::IMMEDIATE_STOP:
                $this->stopPlaylist();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_STOP, $this->getShowUuid());
                $this->postStatusToWebsite($statusDto);
                break;

            case ShowPulseConstant::IMMEDIATE_RESTART:
                $this->stopPlaylist();
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_RESTART, $this->getShowUuid());
                $this->postStatusToWebsite($statusDto);
                break;

            case ShowPulseConstant::IMMEDIATE_SHUTDOWN:
                $this->stopPlaylist();
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_SHUTDOWN, $this->getShowUuid());
                $this->postStatusToWebsite($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_STOP:
                $this->gracefulStopPlaylist();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_STOP, $this->getShowUuid());
                $this->postStatusToWebsite($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_RESTART:
                $this->gracefulStopPlaylist();
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_RESTART, $this->getShowUuid());
                $this->postStatusToWebsite($statusDto);
                break;

            case ShowPulseConstant::GRACEFUL_SHUTDOWN:
                $this->gracefulStopPlaylist();
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_SHUTDOWN, $this->getShowUuid());
                $this->postStatusToWebsite($statusDto);
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

        return true;
    }

    private function systemRestart()
    {
        return $this->httpRequest(true, "system/restart");
    }

    private function systemShutdown()
    {
        return $this->httpRequest(true, "system/shutdown");
    }

    private function stopPlaylist()
    {
        return $this->httpRequest(true, "playlists/stop");
    }

    private function gracefulStopPlaylist()
    {
        $this->httpRequest(true, "playlists/stopgracefully");

        $maxLoops = 180; // 180 = 5 seconds loops during 5 minutes
        for ($i = 0; $i < $maxLoops; $i++) {
            $latestStatus = $this->getStatusFromFpp();

            if ($latestStatus->status === ShowPulseConstant::FPP_STATUS_IDLE) {
                break;
            }

            sleep(ShowPulseConstant::SLEEP_SHORT_VALUE);
        }
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

final class ShowPulseJukeboxSelectionResponseDto
{
    private $sequence_filename;
    private $priority;

    /**
     * Summary of __construct
     * @param ShowPulseApiResponseDto $responseDto
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