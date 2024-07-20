<?php

namespace App\Commands;

$commonFile = $testing ? __DIR__ . "/tests/OptFppWwwCommonMock.php" : "/opt/fpp/www/common.php";
require_once $commonFile;

interface ShowPulseCommandHandlerInterface
{
    protected function execute();
}

abstract class BaseCommandHandler
{
    private $token;
    private $showUuid;
    private $websiteApiUrl;

    protected function httpRequest($forFpp, $route, $method = "GET", $data = null)
    {
        if ($this->isNullOrEmpty($route)) {
            return $this->logError("Invalid URL");
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
            return $this->logError("cURL error: " . curl_error($ch));
        }

        curl_close($ch);

        if ($response === null) {
            return $this->logError("Bad response returned.");
        }

        return json_decode($response, true);
    }

    protected function getShowUuid()
    {
        return $this->showUuid;
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

    protected function loadConfiguration(): bool
    {
        $configFile = GetDirSetting("uploads") . "/showpulse.json";
        $contents = file_get_contents($configFile);

        if ($contents === false) {
            return $this->logError("Configuration file not found or unable to be loaded. Download configuration file contents from the Light Show Pulse website. Then restart FPPD.");
        }

        $json = json_decode($contents, false);

        $this->showUuid = $json->show_id;
        $this->token = $json->token;
        $this->websiteApiUrl = $json->host;
        return true;
    }

    protected function getStatusFromFpp()
    {
        $fppStatus = $this->httpRequest(true, "fppd/status");

        if ($this->isNullOrEmpty($fppStatus)) {
            return $this->logError("Unable to get latest status from FPP.");
        }

        return $fppStatus;
    }

    protected function postStatusToWebsite($statusDto)
    {
        $response = $this->httpRequest(
            false,
            "show-statuses/add/" . $this->getShowUuid(),
            "POST",
            $statusDto
        );

        if ($response->failed) {
            return $this->logError("Unable to update show status. " . $response->message);
        }

        return $response;
    }
}

final class ShowPulseConstant
{
    public const FPP_STATUS_IDLE = 0;
    public const GRACEFUL_RESTART = "GRACEFUL RESTART";
    public const GRACEFUL_SHUTDOWN = "GRACEFUL SHUTDOWN";
    public const GRACEFUL_STOP = "GRACEFUL STOP";
    public const HIGH_PRIORITY = 10;
    public const IMMEDIATE_RESTART = "IMMEDIATE RESTART";
    public const IMMEDIATE_SHUTDOWN = "IMMEDIATE SHUTDOWN";
    public const IMMEDIATE_STOP = "IMMEDIATE STOP";
    public const MAX_FAILURES_ALLOWED = 5;
    public const SLEEP_SHORT_VALUE = 5;
    public const DAEMON_FILE = "/home/fpp/media/plugins/show-pulse-fpp/daemon.run";
}

final class ShowPulseApiResponseDto
{
    public $success;
    public $failed;
    public $message;
    public $data;
}
