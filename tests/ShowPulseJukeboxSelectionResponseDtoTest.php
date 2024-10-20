<?php

namespace App\Commands\Tests;

use App\Commands\ShowPulseApiResponseDto;
use App\Commands\self;
use App\Commands\ShowPulseSelectionResponseDto;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Summary of ShowPulseJukeboxSelectionResponseDtoTest
 * @covers ShowPulseJukeboxSelectionResponseDto
 */
final class ShowPulseJukeboxSelectionResponseDtoTest extends TestCase
{
    public function testIsHighPriority(): void
    {
        $responseDto = new ShowPulseApiResponseDto();
        $responseDto->data = new stdClass();
        $responseDto->data->priority = self::HIGH_PRIORITY;
        $responseDto->data->sequence_filename = "test.fseq";

        $dto = new ShowPulseSelectionResponseDto($responseDto);

        $this->assertTrue($dto->isHighPriority());
    }
    
    public function testGetSequenceFilename(): void
    {
        $responseDto = new ShowPulseApiResponseDto();
        $responseDto->data = new stdClass();
        $responseDto->data->priority = self::HIGH_PRIORITY;
        $responseDto->data->sequence_filename = "test.fseq";

        $dto = new ShowPulseSelectionResponseDto($responseDto);

        $this->assertEquals("test.fseq", $dto->getSequenceFilename());
    }
}