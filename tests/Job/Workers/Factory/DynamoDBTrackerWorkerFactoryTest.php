<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DynamoDBTrackerWorkerFactoryTest extends TestCase
{
    public function testCreateWorker()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('config')
            ->andReturn([
                'idle' => [
                    'job' => [
                        'worker' => [
                            'types' => [
                                DynamoDBTrackerWorker::IDENTIFIER => [
                                    'client' => [
                                        'region' => 'us-east-1',
                                        'version' => 'latest',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        $container->shouldReceive('get')
            ->once()
            ->with(LoggerInterface::class)
            ->andReturn(m::mock(LoggerInterface::class));

        $factory = new DynamoDBTrackerWorkerFactory();
        $factory($container);

        $worker = $factory->createWorker(DynamoDBTrackerWorker::IDENTIFIER);
        $this->assertInstanceOf(DynamoDBTrackerWorker::class, $worker);
    }
}
