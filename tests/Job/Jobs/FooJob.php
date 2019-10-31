<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Workers\Factory\WorkerFactory as WorkerFactoryInterface;

class FooJob extends DefaultJob
{
    const IDENTIFIER = 'foo';

    public function __construct(array $config, WorkerFactoryInterface $workerFactory)
    {
        $this->config = $config;
        $this->workerFactory = $workerFactory;
    }
}
