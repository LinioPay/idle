<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Jobs\FailedJob;
use Throwable;

class JobFactory extends DefaultJobFactory
{
    public function createJob(string $jobIdentifier, array $parameters) : Job
    {
        try {
            /** @var Job $job */
            $job = $this->container->get($this->getJobClass($jobIdentifier));
            $job->validateConfig();
            $job->setParameters($parameters);
            $job->validateParameters();

            return $job;
        } catch (Throwable $t) {
            return new FailedJob([
                'message' => $t->getMessage(),
                'file' => $t->getFile(),
                'line' => $t->getLine(),
            ]);
        }
    }
}
