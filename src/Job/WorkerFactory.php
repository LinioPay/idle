<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

use LinioPay\Idle\Job\Worker as WorkerInterface;

interface WorkerFactory
{
    /**
     * Create a worker of the specified identifier type.
     */
    public function createWorker(string $workerIdentifier, array $parameters = []) : WorkerInterface;
}
