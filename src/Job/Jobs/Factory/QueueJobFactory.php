<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Jobs\QueueJob;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Queue\Service;
use Psr\Container\ContainerInterface;

class QueueJobFactory
{
    public function __invoke(ContainerInterface $container) : QueueJob
    {
        $jobConfig = $container->get('job-config');

        $service = $container->get(Service::class);

        $workerFactory = $container->get(WorkerFactory::class);

        return new QueueJob($jobConfig, $service, $workerFactory);
    }
}
