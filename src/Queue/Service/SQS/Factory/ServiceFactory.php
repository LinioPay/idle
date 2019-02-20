<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue\Service\SQS\Factory;

use Aws\Sqs\SqsClient;
use LinioPay\Idle\Queue\Service\SQS\Service;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ServiceFactory
{
    public function __invoke(ContainerInterface $container) : Service
    {
        $idleConfig = $container->get('config')['queue'] ?? [];

        $serviceConfig = $idleConfig['services'][Service::IDENTIFIER] ?? [];

        $logger = $container->get(LoggerInterface::class);

        return new Service(new SqsClient($serviceConfig['client'] ?? []), $serviceConfig, $logger);
    }
}
