<?php

namespace App\Commands;


abstract class BaseCommandHandler
{
    protected function httpRequest($route, $method = "GET", $data = null, $url = null, $headers = array())
    {
        $url = is_null($url) ? ShowPulseConstant::FPP_URL : $url;

        array_push($headers, "Content-Type: application/json");
        array_push($headers, "Accept: application/json");

        $url = $url . $route;

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
            return $this->logError("cURL error: " . curl_error($ch));
        }

        curl_close($ch);

        if ($response === null) {
            return $this->logError("Bad response returned.");
        }

        return json_decode($response, true);
    }

    public function logError($message)
    {
        $currentDateTime = date('Y-m-d h:i:s A');
        error_log("$currentDateTime: $message");
        return false;
    }

    protected function isNullOrEmpty($value)
    {
        return is_null($value) || empty($value);
    }

    protected function isNotNullOrEmpty($value)
    {
        return !$this->isNullOrEmpty($value);
    }

    protected function loadConfiguration()
    {
        $configFile = "/home/fpp/media/uploads/showpulse.json";
        $contents = file_get_contents($configFile);

        if ($contents === false) {
            return $this->logError("Configuration file not found or unable to be loaded. Download configuration file contents from the Light Show Pulse website. Then restart FPPD.");
        }

        return new ShowPulseConfiguration($contents);
    }

    protected function getStatusFromFpp()
    {
        $fppStatus = $this->httpRequest("fppd/status");

        if ($this->isNullOrEmpty($fppStatus)) {
            return $this->logError("Unable to get latest status from FPP.");
        }

        return $fppStatus;
    }

    protected function postStatusToWebsite($statusDto, $configuration)
    {
        $response = $this->httpRequest(
            "show-statuses/add/" . $configuration->getShowId,
            "POST",
            $statusDto,
            $configuration->getWebsiteUrl(),
            $configuration->getTokenAsHeader()
        );

        if ($response->failed) {
            return $this->logError("Unable to update show status. " . $response->message);
        }

        return $response;
    }
}
