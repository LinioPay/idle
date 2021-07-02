<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Job;

class JobFactory extends DefaultJobFactory
{
    public function createJob(string $jobIdentifier, array $parameters) : Job
    {
        /** @var JobFactory $factory */
        $factory = $this->container->get($this->idleConfig->getJobClass($jobIdentifier));

        $job = $factory->createJob($jobIdentifier, $parameters);

        $job->setParameters($parameters);
        $job->validateParameters();

        return $job;
    }
}
