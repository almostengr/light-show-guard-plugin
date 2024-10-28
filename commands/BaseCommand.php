<?php

namespace App;;

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
    protected const FPP_STATUS_IDLE_ID = 0;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * @param ShowPulseSelectionResponseDto $selectionResponseDto
     * @param mixed $fppStatus
     */
    protected function postNextRequestedSelectionToFpp($data, $fppStatus)
    {
        if (is_null($data)) {
            return false;
        }

        switch ($data['playlist_name']) {
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

            if ($latestStatus->status === self::FPP_STATUS_IDLE_ID) {
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

    protected function nwsHttpRequest($route)
    {
        $url = "https://api.weather.gov/" . $route;
        return $this->httpRequest($url);
    }

    private function httpRequest($url, $method = "GET", $data = null, $headers = array())
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
            throw new Exception($message);
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    protected function logError($message)
    {
        $currentDateTime = date('Y-m-d h:i:s A');
        error_log("$currentDateTime: $message");
        echo $message;

        return false;
    }

    private function loadConfiguration()
    {
        $configFile = "/home/fpp/media/uploads/showpulse.json";
        $contents = file_get_contents($configFile);

        if ($contents === false) {
            throw new Exception("Configuration file not found or unable to be loaded. Download configuration file contents from the Light Show Pulse website.");
        }

        $this->configuration = new ShowPulseConfigurationResponse($contents);
    }

    protected function getStatusFromFpp()
    {
        return $this->fppHttpRequest("fppd/status");
    }

    // protected function postStatusToWebsite($fppStatus, $selectedSequence = null, $latestWeather = null)
    protected function postStatusToWebsite($statusRequestDto)
    {
        // $statusDto = new ShowPulseStatusRequestDto($fppStatus, $this->configuration->getShowId(), $selectedSequence);
        return $this->webHttpRequest(
            "api/show-statuses/add/" . $this->configuration->getShowId(),
            "POST",
            $statusRequestDto, // $statusDto,
        );
    }

    protected function completed()
    {
        echo "Done";
    }

    /**
     * Summary of getNextRequestFromWebsite
     * @var ShowPulseResponseDto $responseDto
     * @return ShowPulseSelectionResponseDto|bool
     */
    private function getNextRequestedSelectionFromWebsite()
    {
        $responseDto = $this->webHttpRequest(
            "api/requested-selections/view-next/" . $this->configuration->getShowId()
        );

        return new ShowPulseResponseDto($responseDto);
    }

    protected function getNextRequestedSelection($fppStatus)
    {
        $selectionResponse = $this->getNextRequestedSelectionFromWebsite();

        if ($selectionResponse->getData() === null) {
            $selectionResponse = $this->getRandomSelection();
        }

        $this->postNextRequestedSelectionToFpp($selectionResponse->getData(), $fppStatus, $this->configuration);

        return $selectionResponse;
    }

    private function getRandomSelection()
    {
        $responseDto =  $this->webHttpRequest(
            "api/selection-options/view-random/" . $this->configuration->getShowId()
        );

        return new ShowPulseSelectionResponseDto($responseDto);
    }

    protected function getShow()
    {
        $response = $this->webHttpRequest("api/shows/view/" . $this->configuration->getShowId());
        return new ShowPulseResponseDto($response);
    }

    protected function updateShow($data)
    {
        $response = $this->webHttpRequest(
            "api/shows/edit/" . $this->configuration->getShowId(),
            'PUT',
            $data
        );
        return new ShowPulseResponseDto($response);
    }

    protected function rejectSelectionRequests()
    {
        $response = $this->getShow();

        $show = $response->getData();
        $show['accepting_requests_id'] = 1;

        $response = $this->updateShow($show);
        return $response;
    }
}
