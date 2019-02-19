<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Output\Transformer;

use LinioPay\Idle\Job\Jobs\FailedJob;
use LinioPay\Idle\TestCase;

class ListJobsTest extends TestCase
{
    public function testTransformsJobList()
    {
        $job = new FailedJob(['error']);

        $this->assertSame([[
            'finished' => true,
            'successful' => false,
            'duration' => 0.0,
            'errors' => ['error'],
        ]], (new ListJobs())->transform(['jobs' => [$job]]));
    }

    public function testFailsValidationWhenMissingJobs()
    {
        $job = new FailedJob(['error']);

        $this->expectException(\InvalidArgumentException::class);
        (new ListJobs())->transform(['jobs' => $job]);
    }
}
