<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory as WorkerFactoryInterface;

class FooJob extends DefaultJob
{
    public const IDENTIFIER = 'foo_job';

    public function __construct(IdleConfig $config, WorkerFactoryInterface $workerFactory)
    {
        $this->idleConfig = $config;
        $this->workerFactory = $workerFactory;
    }

    protected function getJobWorkersConfig() : array
    {
        parent::getJobWorkersConfig();

        $config = $this->idleConfig->getJobConfig(static::IDENTIFIER);

        return $config['workers'] ?? [];
    }
}
