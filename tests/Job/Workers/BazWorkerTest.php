<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use LinioPay\Idle\TestCase;

class BazWorkerTest extends TestCase
{
    public function testItWorks()
    {
        $worker = new BazWorker('bar');
        $this->assertTrue($worker->work());
    }
}
