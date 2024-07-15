<?php

namespace App;

use Exception;

$testing = false;
$commonFile = $testing ? __DIR__ . "/tests/OptFppWwwCommonMock.php" : "/opt/fpp/www/common.php";
require_once $commonFile;

abstract class ShowPulseBase
{
    private $token;
    private $showUuid;
    private $websiteApiUrl;

    protected function httpRequest($forFpp, $route, $method = "GET", $data = null)
    {
        if ($this->isNullOrEmpty($route)) {
            throw new Exception("Invalid URL");
        }

        $url = $this->websiteApiUrl;
        if ($forFpp) {
            $url = "https://127.0.0.1/api/";
        } else {
            array_push($headers, "Authorization: Bearer $this->token");
        }

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
            throw new Exception("cURL error: " . curl_error($ch));
        }

        curl_close($ch);

        if ($response !== null) {
            return json_decode($response, true);
        }

        return null;
    }

    protected function getShowId()
    {
        return $this->showUuid;
    }

    protected function executeFppCommand($command, $data = array())
    {
        $args = $command;
        foreach ($data as $value) {
            $args .= "/$value";
        }

        $this->httpRequest(true, $args, "GET", $args);
    }

    public function logError($message)
    {
        $currentDateTime = date('Y-m-d h:i:s A');
        error_log("$currentDateTime: $message");
    }

    public function isNullOrEmpty($value)
    {
        return is_null($value) || empty($value);
    }

    public function isNotNullOrEmpty($value)
    {
        return !$this->isNullOrEmpty($value);
    }

    public function loadConfiguration(): bool
    {
        $configFile = GetDirSetting("uploads") . "/showpulse.json";
        $contents = file_get_contents($configFile);

        if ($contents === false) {
            $this->logError(
                "Configuration file not found. Download configuration file contents from the Light Show Pulse website. Then restart FPPD."
            );
            return false;
        }

        $json = json_decode($contents, false);

        $this->showUuid = $json->show_id;
        $this->token = $json->token;
        $this->websiteApiUrl = $json->host;
        return true;
    }
}

final class ShowPulseResponseDto
{
    public $success;
    public $failed;
    public $message;
    public $data;
}

final class ShowPulseConstant
{
    const IDLE = 0;
}
