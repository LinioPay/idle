<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\TestCase;

class InvalidWorkerParameterExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $worker = new FooWorker();

        $e = new InvalidWorkerParameterException($worker, 'foo');

        $this->assertSame(sprintf(InvalidWorkerParameterException::MESSAGE, get_class($worker), 'foo'), $e->getMessage());
    }
}
