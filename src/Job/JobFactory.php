<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

use LinioPay\Idle\Job\Job as JobInterface;

interface JobFactory
{
    /**
     * Create a job instance of the specified job identifier.
     */
    public function createJob(string $jobIdentifier, array $parameters) : JobInterface;
}
