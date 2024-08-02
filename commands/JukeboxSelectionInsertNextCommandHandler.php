<?php

namespace App\Commands;

require_once 'BaseCommandHandler.php';

final class JukeboxSelectionInsertNextCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $configuration = $this->loadConfiguration();
        if (!$configuration) {
            return false;
        }

        $fppStatus = $this->getStatusFromFpp();
        $selectionResponse = $this->getNextSelectionFromWebsite($configuration);
        $this->postNextSelectionToFpp($selectionResponse, $fppStatus, $configuration);
    }

    /**
     * Summary of getNextRequestFromWebsite
     * @var ShowPulseApiResponseDto $responseDto
     * @return ShowPulseJukeboxSelectionResponseDto|bool
     */
    private function getNextSelectionFromWebsite($configuration)
    {
        $responseDto = $this->httpRequest(
            "jukebox-selections/next/" . $configuration->getShowId(),
            "PUT",
            null,
            $configuration->getWebsiteUrl(),
            $configuration->getTokenAsHeader()
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
    private function postNextSelectionToFpp($selectionResponseDto, $fppStatus, $configuration)
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
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_STOP, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::IMMEDIATE_RESTART:
                $this->stopPlaylist();
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_RESTART, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::IMMEDIATE_SHUTDOWN:
                $this->stopPlaylist();
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_SHUTDOWN, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::GRACEFUL_STOP:
                $this->gracefulStopPlaylist();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_STOP, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::GRACEFUL_RESTART:
                $this->gracefulStopPlaylist();
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_RESTART, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::GRACEFUL_SHUTDOWN:
                $this->gracefulStopPlaylist();
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_SHUTDOWN, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
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
        return $this->httpRequest("system/restart");
    }

    private function systemShutdown()
    {
        return $this->httpRequest("system/shutdown");
    }

    private function stopPlaylist()
    {
        return $this->httpRequest("playlists/stop");
    }

    private function gracefulStopPlaylist()
    {
        $this->httpRequest("playlists/stopgracefully");

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

        $this->httpRequest($args, "GET", $args);
    }
}
