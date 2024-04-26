<?php

include_once "/opt/fpp/www/common.php";

class ShowPulseCommon
{
    public function webUrl($route = null)
    {
        $url = "https://showpulse.rhtservices.net/";
        if (!is_null($route)) {
            $url .= $route;
        }

        return $url;
    }

    public function fppUrl($route = null)
    {
        $url = "http://127.0.0.1/";
        if (!is_null($route)) {
            $url .= $route;
        }

        return $url;
    }

    public function pluginName()
    {
        return "show_sync";
    }

    public function postShowStatus($data)
    {
        $apiKey = $this->readSetting($this->pluginName() . "api") ?? null;   
        $headers = array(
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey"
        );
        
        $webUrl = $this->webUrl("show_status/add");
        return $this->httpRequest($webUrl, "POST", $data, $headers);
    }

    public function httpRequest($url, $method = "GET", $data = null, $headers = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        if ($method === "POST" || $method === "PUT") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    
        $response = curl_exec($ch);
    
        if ($response === false) {
            throw new Exception("cURL error: " . curl_error($ch));
        }
    
        curl_close($ch);
        return json_decode($response, true);
    }
    
    public function saveSetting($key, $value)
    {
        if (empty($key) || empty($value)) {
            return;
        }
    
        WriteSettingToFile($key, $value, $this->pluginName());
    }
    
    public function readSetting($key)
    {
        if (empty($key)) {
            return false;
        }
    
        return ReadSettingFromFile($key, $this->pluginName()) ?? "";
    }
}
