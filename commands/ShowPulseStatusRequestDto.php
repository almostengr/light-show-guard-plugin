<?php

namespace App\Commands;

final class ShowPulseStatusRequestDto
{
    private $warnings;
    private $show_id;
    private $sequence_filename;
    private $song_filename;
    private $display1;
    private $display2;
    private $fpp_status_id;

    public function __construct($fppStatus, $sequence_filename, $showId)
    {
        $this->show_id = $showId;
        $this->fpp_status_id = $fppStatus->status;
        $this->warnings = count($fppStatus->warnings) ?? 0;
        $this->song_filename = $fppStatus->current_song;
        $this->song_title = str_replace("_", " ", str_replace(".fseq", "", $sequence_filename));
        $this->song_artist = null;
    }

    public function assignMediaData($title, $artist)
    {
        $this->song_title = $title;
        $this->song_artist = $artist;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getShowId()
    {
        return $this->show_id;
    }

    public function getSequenceFilename()
    {
        return $this->sequence_filename;
    }

    public function getSongFilename()
    {
        return $this->song_filename;
    }

    public function getDisplay1()
    {
        return $this->display1;
    }

    public function getDisplay2()
    {
        return $this->display2;
    }

    public function getFppStatusId()
    {
        return $this->fpp_status_id;
    }
}
