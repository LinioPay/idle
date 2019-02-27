<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Worker as WorkerInterface;

interface Worker
{
    public function createWorker(string $class) : WorkerInterface;
}
