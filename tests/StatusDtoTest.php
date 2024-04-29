<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

include_once "src/ShowPulseWorker.php";

class StatusDtoTest extends TestCase
{
    public function testAssignMediaMetaWithNull()
    {
        $dto = new \App\StatusDto("", false, 24, "Test sequence.fseq", false);
        $dto->assignMedia("Testing Title", "Demo Artist");


    }
}