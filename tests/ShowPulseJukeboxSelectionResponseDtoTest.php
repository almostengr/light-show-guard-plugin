<?php

namespace Tests;

require_once '../commands/ShowPulseJukeboxSelectionResponseDto.php';

final class ShowPulseJukeboxSelectionResponseDtoTest extends TestCase
{
    public function testIsHighPriority(): void
    {
        $responseDto = stdClass();
        $responseDto->$data->priority = ShowPulseConstant::HIGH_PRIORITY;
        $responseDto->$data->sequence_filename = "test.fseq";

        $dto = new ShowPulseJukeboxSelectionResponseDto($responseDto);

        $this->assertTrue($dto->isHighPriority());
    }
    
    public function testGetSequenceFilename(): void
    {
        $responseDto = stdClass();
        $responseDto->$data->priority = ShowPulseConstant::HIGH_PRIORITY;
        $responseDto->$data->sequence_filename = "test.fseq";

        $dto = new ShowPulseJukeboxSelectionResponseDto($responseDto);

        $this->assertEquals("test.fseq", $dto->getSequenceFilename());
    }
}