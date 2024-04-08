<?php

define("FPP_API_BASE_URL", "http://localhost");
define("GUARD_API_BASE_URL", "https://guard.rthservices.net");

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