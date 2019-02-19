<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use Psr\Container\ContainerInterface;

class SimpleJobFactory
{
    public function __invoke(ContainerInterface $container) : SimpleJob
    {
        $jobConfig = $container->get('job-config');

        $workerFactory = $container->get(WorkerFactory::class);

        return new SimpleJob($jobConfig, $workerFactory);
    }
}
