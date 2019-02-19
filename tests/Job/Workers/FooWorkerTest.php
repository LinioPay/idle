<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use LinioPay\Idle\TestCase;

class FooWorkerTest extends TestCase
{
    public function testItWorks()
    {
        $worker = new FooWorker();
        $this->assertTrue($worker->work());
    }
}
