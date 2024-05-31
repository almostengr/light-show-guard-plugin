<?php

require_once "/opt/fpp/www/common.php";

abstract class ShowPulseBase
{
    protected function websiteUrl($route = null)
    {
        $url = "https://showpulse.rhtservices.net/api/";
        if (!is_null($route)) {
            $url .= $route;
        }

        return $url;
    }

    protected function fppUrl($route = null)
    {
        $url = "http://127.0.0.1/api/";
        if (!is_null($route)) {
            $url .= $route;
        }

        return $url;
    }

    protected function pluginName()
    {
        return "show_pulse";
    }

    protected function httpRequest($url, $method = "GET", $data = null, $headers = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $method = strtoupper($method);
        if ($method === "POST" || $method === "PUT") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        array_push($headers, "Content-Type: application/json");
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

    protected function saveSetting($key, $value)
    {
        if (empty($key) || is_null($key)) {
            throw new Exception("Setting key was not specified");
        }

        $value = trim($value);
        $key = trim($key);

        WriteSettingToFile($key, $value, $this->pluginName());
    }

    protected function readSetting($key)
    {
        if (empty($key) || is_null($key)) {
            return false;
        }

        return ReadSettingFromFile($key, $this->pluginName()) ?? false;
    }

    protected function getWebsiteApiKey()
    {
        $value = $this->readSetting("API_KEY");
        if ($value) {
            return $value;
        }

        throw new Exception("API Key has not been entered.");
    }

    protected function getWebsiteAuthorizationHeaders()
    {
        $apiKey = $this->getWebsiteApiKey();
        return array("Authorization: Bearer $apiKey");
    }

    protected function executeFppCommand($command, $data = array())
    {
        $args = $command;
        foreach ($data as $value) {
            $args .= "/$value";
        }

        $url = $this->fppUrl($args);
        $result = $this->httpRequest($url, "GET", $args);

        if ($result === false) {
            throw new Exception("Unable to execute FPP command");
        }
    }

    protected function logError($data)
    {
        $currentDateTime = date('Y-m-d h:i:s A', time());
        error_log("$currentDateTime: $data");
    }
}
