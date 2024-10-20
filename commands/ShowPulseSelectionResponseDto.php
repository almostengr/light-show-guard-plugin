<?php

namespace App\Commands;

final class ShowPulseSelectionResponseDto
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

    public function getPriority()
    {
        return $this->priority;
    }

    public function getSequenceFilename()
    {
        return $this->sequence_filename;
    }
}
