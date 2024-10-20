<?php

namespace App\Commands;

final class ShowPulseConfigurationResponse
{
    private $showId;
    private $token;
    private $host;

    public function __construct($contents)
    {
        $json = json_decode($contents, false);

        $this->showId = $json->show_id;
        $this->token = $json->token;
        $this->host = $json->host;
    }

    public function getShowId()
    {
        return $this->showId;
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
