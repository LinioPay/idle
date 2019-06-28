<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use LinioPay\Idle\TestCase;
use Mockery as m;
use Mockery\Mock;

class DefaultWorkerTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $parameters = ['foo' => 'bar'];
        /** @var DefaultWorker|Mock $worker */
        $worker = m::mock(DefaultWorker::class)->makePartial();

        $worker->setParameters($parameters);
        $this->assertSame($parameters, $worker->getParameters());

        $errors = ['error'];
        $method = self::getMethod(DefaultWorker::class, 'setErrors');
        $method->invokeArgs($worker, [$errors]);

        $this->assertSame($errors, $worker->getErrors());
        $this->assertSame([], $worker->getTrackerData());
    }
}
