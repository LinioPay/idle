<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\TestCase;

class FailedJobTest extends TestCase
{
    public function testGetters()
    {
        $job = new FailedJob(['Fail']);

        $job->process();

        $this->assertSame(['Fail'], $job->getErrors());
        $this->assertSame(0.0, $job->getDuration());
        $this->assertSame(false, $job->isSuccessful());
    }
}
