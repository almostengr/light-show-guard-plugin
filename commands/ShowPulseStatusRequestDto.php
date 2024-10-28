<?php

namespace App;

final class ShowPulseStatusRequestDto
{
    private $warnings;
    private $playlist_name;
    private $fpp_status_id;
    private $request_id = null;
    private $weather_description = null;
    private $wind_chill = null;
    private $heat_index = null;
    private $wind_gust = null;
    private $wind_speed = null;
    private $humidity = null;
    private $temperature = null;

    public function __construct($fppStatus, $request_id = null)
    {
        $this->fpp_status_id = $fppStatus->status;
        $this->warnings = count($fppStatus->warnings) ?? 0;
        $this->playlist_name = $fppStatus->current_sequence === "" ? $fppStatus->current_song : $fppStatus->current_sequence;
        $this->request_id = $request_id;
    }

    public function setWeatherObservation($observation)
    {
        $this->temperature = $observation->temperature->value;
        $this->humidity = $observation->humidity->value;
        $this->wind_speed = $observation->windSpeed->value;
        $this->wind_gust = $observation->windGust->value;
        $this->heat_index = $observation->heatIndex->value;
        $this->wind_chill = $observation->windChill->value;
        $this->weather_description = $observation->textDescription;
    }
}
