<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use Aws\DynamoDb\DynamoDbClient;
use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker;
use Psr\Log\LoggerInterface;

class DynamoDBTrackerWorkerFactory extends DefaultWorkerFactory
{
    public function createWorker(string $workerIdentifier, array $parameters = []) : WorkerInterface
    {
        $idleConfig = $this->container->get('config')['idle'] ?? [];

        $defaultConfig = $idleConfig['job']['worker']['types'][DynamoDBTrackerWorker::IDENTIFIER] ?? [];

        $logger = $this->container->get(LoggerInterface::class);

        $client = new DynamoDbClient($defaultConfig['client'] ?? []);

        return new DynamoDBTrackerWorker($client, $defaultConfig, $logger);
    }
}
