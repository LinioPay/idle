<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;

class SimpleJobFactory extends DefaultJobFactory
{
    public function createJob(string $jobIdentifier, array $parameters) : Job
    {
        $workerFactory = $this->container->get(WorkerFactoryInterface::class);

        return new SimpleJob($this->idleConfig, $workerFactory);
    }
}
