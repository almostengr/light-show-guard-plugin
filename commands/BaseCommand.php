<?php

namespace App\Commands;

use Exception;

abstract class BaseCommand
{
    protected $configuration;
    protected const DAEMON_FILE = "/home/fpp/media/plugins/show-pulse-fpp/daemon.run";
    protected const GRACEFUL_RESTART = "GRACEFUL RESTART";
    protected const GRACEFUL_SHUTDOWN = "GRACEFUL SHUTDOWN";
    protected const GRACEFUL_STOP = "GRACEFUL STOP";
    protected const HIGH_PRIORITY = 10;
    protected const IMMEDIATE_RESTART = "IMMEDIATE RESTART";
    protected const IMMEDIATE_SHUTDOWN = "IMMEDIATE SHUTDOWN";
    protected const IMMEDIATE_STOP = "IMMEDIATE STOP";

    public function __construct()
    {
        $this->configuration = $this->loadConfiguration();
    }

    /**
     * @param ShowPulseSelectionResponseDto $selectionResponseDto
     * @param mixed $fppStatus
     */
    protected function postNextRequestedSelectionToFpp($selectionResponseDto, $fppStatus)
    {
        if (is_null($selectionResponseDto)) {
            return false;
        }

        switch ($selectionResponseDto->getSequenceFilename()) {
            case self::IMMEDIATE_STOP:
                $this->stopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::IMMEDIATE_STOP);
                break;

            case self::IMMEDIATE_RESTART:
                $this->stopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::IMMEDIATE_RESTART);
                $this->systemRestart();
                break;

            case self::IMMEDIATE_SHUTDOWN:
                $this->stopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::IMMEDIATE_SHUTDOWN);
                $this->systemShutdown();
                break;

            case self::GRACEFUL_STOP:
                $this->gracefulStopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::GRACEFUL_STOP);
                break;

            case self::GRACEFUL_RESTART:
                $this->gracefulStopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::GRACEFUL_RESTART);
                $this->systemRestart();
                break;

            case self::GRACEFUL_SHUTDOWN:
                $this->gracefulStopPlaylist();
                $this->postStatusToWebsite($fppStatus, self::GRACEFUL_SHUTDOWN);
                $this->systemShutdown();
                break;

            default:
                $command = "Insert Playlist After Current";
                if ($selectionResponseDto->getPriority() === self::HIGH_PRIORITY) {
                    $command = "Insert Playlist Immediate";
                }

                $args = $command;
                $data = array($selectionResponseDto->getSequenceFilename(), "-1", "-1", "false");
                foreach ($data as $value) {
                    $args .= "/$value";
                }

                $this->fppHttpRequest($args, "GET", $args);
        }

        return true;
    }

    private function systemRestart()
    {
        return $this->fppHttpRequest("system/restart");
    }

    private function systemShutdown()
    {
        return $this->fppHttpRequest("system/shutdown");
    }

    private function stopPlaylist()
    {
        return $this->fppHttpRequest("playlists/stop");
    }

    private function gracefulStopPlaylist()
    {
        $this->fppHttpRequest("playlists/stopgracefully");

        $maxLoops = 180; // 180 = 5 seconds loops during 5 minutes
        for ($i = 0; $i < $maxLoops; $i++) {
            $latestStatus = $this->getStatusFromFpp();

            $fppStatusIdleId = 0;
            if ($latestStatus->status === $fppStatusIdleId) {
                break;
            }

            sleep(5);
        }
    }

    protected function fppHttpRequest($route, $method = "GET", $data = null)
    {
        $fppUrl = "https://127.0.0.1/api/";
        $url = $fppUrl . $route;
        return $this->httpRequest($url, $method, $data);
    }

    protected function webHttpRequest($route, $method = "GET", $data = null)
    {
        $url = $this->configuration->getWebsiteUrl()  . "/" . $route;
        return $this->httpRequest($url, $method, $data, $this->configuration->getTokenAsHeader());
    }

    private function httpRequest($url, $method, $data, $headers = array())
    {
        array_push($headers, "Content-Type: application/json");
        array_push($headers, "Accept: application/json");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $method = strtoupper($method);
        if ($method === "POST" || $method === "PUT") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($response === false) {
            $message = "cURL error: " . curl_error($ch);
            $this->logError($message, true);
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    protected function logError($message, $throwException = false)
    {
        $currentDateTime = date('Y-m-d h:i:s A');
        error_log("$currentDateTime: $message");
        echo $message;

        if ($throwException) {
            throw new Exception($message);
        }

        return false;
    }

    private function loadConfiguration()
    {
        $configFile = "/home/fpp/media/uploads/showpulse.json";
        $contents = file_get_contents($configFile);

        if ($contents === false) {
            $this->logError(
                "Configuration file not found or unable to be loaded. Download configuration file contents from the Light Show Pulse website.",
                true
            );
        }

        return new ShowPulseConfigurationResponse($contents);
    }

    protected function getStatusFromFpp()
    {
        return $this->fppHttpRequest("fppd/status");
    }

    protected function postStatusToWebsite($fppStatus, $selectedSequence = null)
    {
        $statusDto = new ShowPulseStatusRequest($fppStatus, $this->configuration->getShowId(), $selectedSequence);

        return $this->webHttpRequest(
            "api/show-statuses/add/" . $this->configuration->getShowId(),
            "POST",
            $statusDto,
        );
    }

    public function completed()
    {
        echo "Done";
    }

    /**
     * Summary of getNextRequestFromWebsite
     * @var ShowPulseApiResponseDto $responseDto
     * @return ShowPulseSelectionResponseDto|bool
     */
    private function getNextRequestedSelectionFromWebsite()
    {
        $responseDto = $this->webHttpRequest(
            "api/requested-selections/next/" . $this->configuration->getShowId()
        );

        return new ShowPulseSelectionResponseDto($responseDto);
    }

    protected function requestedSelectionGetNext($fppStatus)
    {
        $selectionResponse = $this->getNextRequestedSelectionFromWebsite($this->configuration);

        if ($selectionResponse === null) {
            $selectionResponse = $this->getRandomSelection();
        }

        $this->postNextRequestedSelectionToFpp($selectionResponse, $fppStatus, $this->configuration);

        return $selectionResponse;
    }

    private function getRandomSelection()
    {
        $responseDto =  $this->webHttpRequest(
            "api/selection-options/random/" . $this->configuration->getShowId(),
            'PUT'
        );

        return new ShowPulseSelectionResponseDto($responseDto);
    }

    protected function rejectSelectionRequests()
    {
        $this->webHttpRequest(
            "api/shows/reject-requests/" . $this->configuration->getShowId(),
            'PUT'
        );
    }
}
