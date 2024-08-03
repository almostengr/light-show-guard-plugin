<?php

namespace App\Test;

use App\Commands\ShowPulseDaemonCommandHandler;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Summary of ShowPulseDaemonCommandHandlerTest
 * 
 * @covers ShowPulseDaemonCommandHandler
 */
final class ShowPulseDaemonCommandHandlerTest extends TestCase
{
    public function testShouldPostStatus_WhenSongAndSequenceSame(): void
    {
        $lastSequence = "The Song That Doesn't End.fseq";
        $lastSong = "The Song That Doesn't End - Lamp Chop Play Along.mp3";

        $fppStatus = new stdClass();
        $fppStatus->current_sequence = "The Song That Doesn't End.fseq";
        $fppStatus->current_song = "The Song That Doesn't End - Lamp Chop Play Along.mp3";

        $command = new ShowPulseDaemonCommandHandler();
        $result = $command->shouldPostStatus($fppStatus, $lastSequence, $lastSong);

        $this->assertFalse($result);
    }

    public function testShouldPostStatus_WhenSongAndSequenceDifferent(): void
    {
        $lastSequence = "The Song That Doesn't End.fseq";
        $lastSong = "The Song That Doesn't End - Lamp Chop Play Along.mp3";

        $fppStatus = new stdClass();
        $fppStatus->current_sequence = "Disco Santa - Holiday Express.fseq";
        $fppStatus->current_song = "Disco Santa - Holiday Express.mp3";

        $command = new ShowPulseDaemonCommandHandler();
        $result = $command->shouldPostStatus($fppStatus, $lastSequence, $lastSong);

        $this->assertTrue($result);
    }
}