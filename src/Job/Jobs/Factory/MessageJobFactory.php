<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Jobs\MessageJob;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory as WorkerFactoryInterface;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;
use Psr\Container\ContainerInterface;

class MessageJobFactory
{
    public function __invoke(ContainerInterface $container) : MessageJob
    {
        $jobConfig = $container->get('config')['idle']['job'] ?? [];

        $messageFactory = $container->get(MessageFactoryInterface::class);

        $workerFactory = $container->get(WorkerFactoryInterface::class);

        return new MessageJob($jobConfig, $messageFactory, $workerFactory);
    }
}
