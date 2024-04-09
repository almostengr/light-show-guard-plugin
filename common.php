<?php

include_once "/opt/fpp/www/common.php";

define("FPP_API_BASE_URL", "http://localhost");
define("GUARD_API_BASE_URL", "https://guard.rthservices.net");
define("LSG_PLUGIN_NAME", "light_show_guard");
define("LSG_API_KEY", "lsg_api_key");

function httpRequest($url, $method = "GET", $data = null, $headers = array())
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
    return $response;
}

function lsgSaveSetting($key, $value)
{
    if (empty($key) || empty($value)) {
        return;
    }

    WriteSettingToFile($key, $value, LSG_PLUGIN_NAME);
}

function lsgReadSetting($key)
{
    if (empty($key)) {
        return "";
    }

    $value = ReadSettingFromFile($key, LSG_PLUGIN_NAME) ?? "";
    return $value;
}