<?php

final class StatusRequestDto
{
    private $showId;
    private $hasErrors;
    private $cpuTemperature;
    private $sequence;
    private $isPlaying;
    private $title;
    private $artist;

    public function __construct($showId, $hasErrors, $cpuTemperature, $sequence, $isPlaying)
    {
        $this->showId = $showId;
        $this->hasErrors = $hasErrors;
        $this->cpuTemperature = $cpuTemperature;
        $this->sequence = $sequence;
        $this->isPlaying = $isPlaying;
    }

    public function assignMedia($title = null, $artist = null)
    {
        if (!is_null($title))
        {
            $this->title = $title;
        }

        if (!$is_null($artist))
        {
            $this->artist = $artist;
        }
    }
}
