<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Jobs\MessageJob;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;

class MessageJobFactory extends DefaultJobFactory
{
    public function createJob(string $jobIdentifier, array $parameters) : Job
    {
        $messageFactory = $this->container->get(MessageFactoryInterface::class);

        $workerFactory = $this->container->get(WorkerFactoryInterface::class);

        return new MessageJob($this->idleConfig, $messageFactory, $workerFactory);
    }
}
