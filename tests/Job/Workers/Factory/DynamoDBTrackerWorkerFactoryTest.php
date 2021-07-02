<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DynamoDBTrackerWorkerFactoryTest extends TestCase
{
    public function testCreateWorker()
    {
        $idleConfig = new IdleConfig([], [], [], [
            DynamoDBTrackerWorker::IDENTIFIER => [
                'class' => DynamoDBTrackerWorker::class,
                'client' => [
                    'region' => 'us-east-1',
                    'version' => 'latest',
                ],
            ],
        ]);
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn($idleConfig);
        $container->shouldReceive('get')
            ->once()
            ->with(LoggerInterface::class)
            ->andReturn(m::mock(LoggerInterface::class));

        $factory = new DynamoDBTrackerWorkerFactory($container);

        $worker = $factory->createWorker(DynamoDBTrackerWorker::IDENTIFIER);

        $this->assertInstanceOf(DynamoDBTrackerWorker::class, $worker);
    }
}
