<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\TestCase;

class InvalidJobParameterExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        /** @var Job $job */
        $job = $this->fake(SimpleJob::class);

        $e = new InvalidJobParameterException($job, 'foo_parameter');

        $this->assertSame(sprintf(InvalidJobParameterException::MESSAGE, get_class($job), 'foo_parameter'), $e->getMessage());
    }
}
