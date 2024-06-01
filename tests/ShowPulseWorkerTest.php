<?php

namespace App\Test;

use App\ShowPulseWorker;
use PHPUnit\Framework\TestCase;

/**
 * @covers ShowPulseWorker
 */
final class ShowPulseWorkerTest extends TestCase
{
    public function testResetAttemptCount()
    {
        $worker = new ShowPulseWorker();
        $worker->resetAttemptCount();

        $this->assertEquals(0, $worker->getAttemptCount());
    }

    public function testIncreaseAttemptCountTwoTimes()
    {
        $worker = new ShowPulseWorker();
        $worker->increaseAttemptCount();
        $worker->increaseAttemptCount();

        $this->assertEquals(2, $worker->getAttemptCount());
    }

    public function testIncreaseAttemptCountSixTimes()
    {
        $worker = new ShowPulseWorker();
        $worker->increaseAttemptCount();
        $worker->increaseAttemptCount();
        $worker->increaseAttemptCount();
        $worker->increaseAttemptCount();
        $worker->increaseAttemptCount();
        $worker->increaseAttemptCount();

        $this->assertEquals(5, $worker->getAttemptCount());
    }

    public function testSleepShortValue()
    {
        $worker = new ShowPulseWorker();

        $this->assertEquals(5, $worker->sleepShortValue());
    }

    public function testSleepLongValue()
    {
        $worker = new ShowPulseWorker();

        $this->assertEquals(30, $worker->sleepLongValue());
    }

    public function testCalculateSleepTime()
    {
        $worker = new ShowPulseWorker();

        $sleepTime = $worker->calculateSleepTime();

        $this->assertEquals(5, $sleepTime);
    }
}
