<?php

namespace App;

final class ShowPulseConfigurationResponse
{
    private $userId;
    private $token;
    private $host;

    public function __construct($contents)
    {
        $json = json_decode($contents, false);

        $this->userId = $json->show_id;
        $this->token = $json->token;
        $this->host = $json->host;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getWebsiteUrl()
    {
        return $this->host;
    }

    public function getTokenAsHeader()
    {
        $headers = array();
        array_push($headers, "Authorization: Bearer $this->token");
        return $headers;
    }
}
