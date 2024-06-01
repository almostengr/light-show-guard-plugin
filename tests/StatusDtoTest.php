<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

require_once "ShowPulseWorker.php";

/**
 * @covers \App\StatusDto
 */
final class StatusDtoTest extends TestCase
{
    public function testConstructor()
    {
        $dto = new \App\StatusDto(4, "test.fseq", "playing");
        $this->assertEquals("test.fseq", $dto->sequence);
    }

    public function testConstructorWithSpecialCharacterSequence()
    {
        $dto = new \App\StatusDto(0, 'test-flight_rainbow.fseq', 'playing');
        
        $this->assertEquals('test-flight rainbow', $dto->song_title);
        $this->assertEquals('test-flight_rainbow.fseq', $dto->sequence);
    }

    public function testConstructorWithErrors()
    {
        $dto = new \App\StatusDto(5, 'testing.fseq', 'idle');

        $this->assertEquals(5, $dto->warnings);
        $this->assertNotEquals(10, $dto->warnings);
    }

    public function testAssignMediaWithTitleAndArtist()
    {
        $dto = new \App\StatusDto(0, "test.fseq", "playing");
        $dto->assignMedia("Foo", "Bar");

        $this->assertEquals("Foo", $dto->song_title);
        $this->assertEquals("Bar", $dto->song_artist);
    }

    public function testAssignMediaWithTitleAndNoArtist()
    {
        $dto = new \App\StatusDto(0, "test.fseq", "playing");
        $dto->assignMedia("Foo");

        $this->assertEquals("Foo", $dto->song_title);
        $this->assertEquals(null, $dto->song_artist);
    }

    public function testAssignMediaWithNoTitleNorArtist()
    {
        $dto = new \App\StatusDto(0, "test.fseq", "playing");

        $this->assertEquals("test", $dto->song_title);
    }
}