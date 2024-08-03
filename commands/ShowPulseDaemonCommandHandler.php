<?php

namespace App\Commands;

use Exception;

require_once 'BaseCommandHandler.php';

final class ShowPulseDaemonCommandHandler extends BaseCommandHandler implements ShowPulseCommandHandlerInterface
{
    public function execute()
    {
        $failureCount = 0;
        $lastSequence = "";
        $lastSong = "";

        file_put_contents(ShowPulseConstant::DAEMON_FILE, "");

        do {
            try {
                $configuration = $this->loadConfiguration();
                if (!$configuration) {
                    throw new Exception("Configuration file not loaded nor found");
                }

                $fppStatus = $this->getStatusFromFpp();
                $shouldPost = $this->shouldPostStatus($fppStatus, $lastSequence, $lastSong);

                if ($shouldPost) {
                    $statusDto = $this->createStatusDto($fppStatus, $configuration);
                    $updateSucceeded = $this->postStatusToWebsite($statusDto, $configuration);

                    if ($updateSucceeded) {
                        $lastSequence = $fppStatus->current_sequence;
                        $lastSong = $fppStatus->current_song;
                    }
                }

                $selectionResponse = $this->getNextSelectionFromWebsite($configuration);
                $this->postNextSelectionToFpp($selectionResponse, $fppStatus, $configuration);

                sleep(ShowPulseConstant::SLEEP_SHORT_VALUE);

                $failureCount = 0;
            } catch (Exception $e) {
                if ($failureCount < ShowPulseConstant::MAX_FAILURES_ALLOWED) {
                    $message = $e->getMessage() . " (Attempt  $failureCount)";
                    $this->logError($message);
                    $failureCount++;
                }

                $sleepTime = $failureCount * ShowPulseConstant::DELAY_SECONDS;
                sleep($sleepTime);
            }

            $daemonFileExists = file_exists(ShowPulseConstant::DAEMON_FILE);
        } while ($daemonFileExists);

        $this->logError("Daemon stopped.");
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
                $this->stopImmediately();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_STOP, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::IMMEDIATE_RESTART:
                $this->stopImmediately();
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_RESTART, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::IMMEDIATE_SHUTDOWN:
                $this->stopImmediately();
                $this->systemShutdown();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::IMMEDIATE_SHUTDOWN, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::GRACEFUL_STOP:
                $this->stopGracefully();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_STOP, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::GRACEFUL_RESTART:
                $this->stopGracefully();
                $this->systemRestart();
                $statusDto = new ShowPulseStatusRequestDto($fppStatus, ShowPulseConstant::GRACEFUL_RESTART, $configuration->getShowId());
                $this->postStatusToWebsite($statusDto, $configuration);
                break;

            case ShowPulseConstant::GRACEFUL_SHUTDOWN:
                $this->stopGracefully();
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

    private function stopImmediately()
    {
        return $this->httpRequest("playlists/stop");
    }

    private function stopGracefully()
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

    public function shouldPostStatus($fppStatus, $lastSequence, $lastSong)
    {
        if (
            is_null($fppStatus) ||
            ($lastSequence === $fppStatus->current_sequence && $lastSong === $fppStatus->current_song)
        ) {
            return false;
        }

        return true;
    }

    public function createStatusDto($fppStatus, $configuration)
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
}

