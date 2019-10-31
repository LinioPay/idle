<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

use LinioPay\Idle\Job\Job as JobInterface;

interface JobFactory
{
    public function createJob(string $jobIdentifier, array $parameters) : JobInterface;
}
