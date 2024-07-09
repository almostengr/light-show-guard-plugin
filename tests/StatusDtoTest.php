<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use \App\StatusDto;

require_once "ShowPulseWorker.php";

/**
 * @covers \App\StatusDto
 */
final class StatusDtoTest extends TestCase
{
    public function testConstructor()
    {
        $dto = new StatusDto(4, "test.fseq", "test.mp3", 1, 5);
        $this->assertEquals("test.fseq", $dto->sequence_filename);
    }

    public function testConstructorWithSpecialCharacterSequence()
    {
        $dto = new StatusDto(0, 'test-flight_rainbow.fseq', "test-flight_rainbow.mp3", 1, 5);

        $this->assertEquals('test-flight rainbow', $dto->song_title);
        $this->assertEquals('test-flight_rainbow.fseq', $dto->sequence_filename);
    }

    public function testConstructorWithErrors()
    {
        $dto = new StatusDto(5, 'testing.fseq', 'testing.mp3', 0, 5);

        $this->assertEquals(5, $dto->warnings);
        $this->assertNotEquals(10, $dto->warnings);
    }

    public function testAssignMediaWithTitleAndArtist()
    {
        $dto = new StatusDto(0, "test.fseq", 'test.mp3', 1, 5);
        $dto->assignMediaData("Foo", "Bar");

        $this->assertEquals("Foo", $dto->song_title);
        $this->assertEquals("Bar", $dto->song_artist);
    }

    public function testAssignMediaWithTitleAndNoArtist()
    {
        $dto = new StatusDto(0, "test.fseq", 'test.mp3', 1, 5);

        $dto->assignMediaData("Foo", null);

        $this->assertEquals("Foo", $dto->song_title);
        $this->assertEquals(null, $dto->song_artist);
    }

    public function testAssignMediaWithNoTitleNorArtist()
    {
        $dto = new StatusDto(0, "test.fseq", 'test.mp3', 1, 5);

        $this->assertEquals("test", $dto->song_title);
        $this->assertEquals("test", $dto->song_title);
    }
}