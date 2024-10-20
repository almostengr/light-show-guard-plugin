<?php

namespace App\Commands;

final class ShowPulseStatusRequest
{
    private $warnings;
    private $show_id;
    private $sequence_filename;
    private $fpp_status_id;
    private $request_id;

    public function __construct($fppStatus, $showId, $selected_sequence = null)
    {
        $this->show_id = $showId;
        $this->fpp_status_id = $fppStatus->status;
        $this->warnings = count($fppStatus->warnings) ?? 0;
        $this->sequence_filename = $selected_sequence === null ?  $fppStatus->current_sequence : $selected_sequence;
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

    public function getFppStatusId()
    {
        return $this->fpp_status_id;
    }
}
