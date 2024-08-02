<?php

namespace App\Commands;

final class ShowPulseJukeboxSelectionResponseDto
{
    private $sequence_filename;
    private $priority;

    /**
     * Summary of __construct
     * @param ShowPulseApiResponseDto $responseDto
     */
    public function __construct($responseDto)
    {
        $this->sequence_filename = $responseDto->data->sequence_filename;
        $this->priority = $responseDto->data->priority;
    }

    public function isHighPriority()
    {
        return $this->priority == ShowPulseConstant::HIGH_PRIORITY;
    }

    public function getSequenceFilename()
    {
        return $this->sequence_filename;
    }
}