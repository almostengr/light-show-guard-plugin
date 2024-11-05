<?php

namespace App;

use Exception;


require_once 'ShowPulseResponseDto.php';
require_once 'ShowPulseConfigurationResponse.php';

abstract class BaseCommand
{
    protected $configuration;
    protected const DAEMON_FILE = "/home/fpp/media/plugins/show-pulse-fpp/daemon.run";
    protected const FPP_STATUS_IDLE_ID = 0;

    public function __construct()
    {
        $this->loadConfiguration();
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
        $response = $this->httpRequest($url, $method, $data, $this->configuration->getTokenAsHeader());
        return new ShowPulseResponseDto($response);
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
        // $statusDto = new ShowPulseStatusRequestDto($fppStatus, $this->configuration->getUserId(), $selectedSequence);
        return $this->webHttpRequest(
            "api/show-statuses/add/" . $this->configuration->getUserId(),
            "POST",
            $statusRequestDto, // $statusDto,
        );
    }

    protected function completed()
    {
        echo "Done";
    }

    protected function getShow()
    {
        $response = $this->webHttpRequest("api/shows/view/" . $this->configuration->getUserId());
        // return new ShowPulseResponseDto($response);
        return $response;
    }

    protected function updateShow($data)
    {
        $response = $this->webHttpRequest(
            "api/shows/edit/" . $this->configuration->getUserId(),
            'PUT',
            $data
        );
        // return new ShowPulseResponseDto($response);
        return $response;
    }

    protected function rejectSelectionRequests()
    {
        $response = $this->getShow();

        $show = $response->getData();
        $show['selection_request_status_id'] = 1;

        $response = $this->updateShow($show);
        return $response;
    }
}
