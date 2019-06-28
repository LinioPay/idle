<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Tracker\Service\DynamoDB\Factory;

use Aws\DynamoDb\DynamoDbClient;
use LinioPay\Idle\Job\Tracker\Service\DynamoDB\Service;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ServiceFactory
{
    public function __invoke(ContainerInterface $container) : Service
    {
        $idleConfig = $container->get('config')['tracker'] ?? [];

        $serviceConfig = $idleConfig['services'][Service::IDENTIFIER] ?? [];

        $logger = $container->get(LoggerInterface::class);

        return new Service(new DynamoDbClient($serviceConfig['client'] ?? []), $serviceConfig, $logger);
    }
}
