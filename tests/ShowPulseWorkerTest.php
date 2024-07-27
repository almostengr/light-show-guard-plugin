<?php

namespace App\Test;

use App\ShowPulseConstant;
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
        $worker->resetFailureCount();

        $this->assertEquals(0, $worker->getFailureCount());
    }

    public function testIncreaseAttemptCountTwoTimes()
    {
        $worker = new ShowPulseWorker();
        $worker->increaseFailureCount();
        $worker->increaseFailureCount();

        $this->assertEquals(2, $worker->getFailureCount());
    }

    public function testIncreaseAttemptCountSixTimes()
    {
        $worker = new ShowPulseWorker();
        $worker->increaseFailureCount();
        $worker->increaseFailureCount();
        $worker->increaseFailureCount();
        $worker->increaseFailureCount();
        $worker->increaseFailureCount();
        $worker->increaseFailureCount();

        $this->assertEquals(5, $worker->getFailureCount());
    }

    public function testSleepShortValue()
    {
        $worker = new ShowPulseWorker();

        $this->assertEquals(5, ShowPulseConstant::SLEEP_SHORT_VALUE);
    }

    public function testSleepLongValue()
    {
        $worker = new ShowPulseWorker();

        $this->assertEquals(30, ShowPulseConstant::SLEEP_LONG_VALUE);
    }

    public function testCalculateSleepTime()
    {
        $worker = new ShowPulseWorker();

        $sleepTime = $worker->calculateSleepTime();

        $this->assertEquals(5, $sleepTime);
    }

    public function testMaxFailuresAllowed()
    {
        $worker = new ShowPulseWorker();

        $failures = ShowPulseConstant::MAX_FAILURES_ALLOWED;

        $this->assertEquals(5, $failures);
    }
}
