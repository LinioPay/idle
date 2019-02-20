<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Output\Transformer;

use LinioPay\Idle\Job\Jobs\FailedJob;
use LinioPay\Idle\TestCase;

class JobDetailsTest extends TestCase
{
    public function testTransformsDetails()
    {
        $job = new FailedJob(['error']);

        $this->assertSame([
            'finished' => true,
            'successful' => false,
            'duration' => 0.0,
            'errors' => ['error'],
        ], (new JobDetails())->transform($job));
    }
}
