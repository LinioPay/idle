<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Workers\BazWorker;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class WorkerFactoryTest extends TestCase
{
    public function testCreateWorkerForwardsCallToAppropriateFactory()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(BazWorker::class)
            ->andReturn((new BazWorkerFactory())($container));
        $container->shouldReceive('get')
            ->twice()
            ->with('config')
            ->andReturn([
               'idle' => [
                   'job' => [
                       'worker' => [
                           'types' => [
                               BazWorker::IDENTIFIER => [
                                   'class' => BazWorker::class,
                                   'parameters' => [
                                       'red' => true,
                                   ],
                               ],
                           ],
                       ],
                   ],
               ],
            ]);

        $factory = new WorkerFactory();

        $factory($container);
        $this->assertInstanceOf(WorkerFactory::class, $factory);

        $worker = $factory->createWorker(BazWorker::IDENTIFIER, ['blue' => true]);
        $this->assertInstanceOf(BazWorker::class, $worker);

        $parameters = $worker->getParameters();

        $this->assertArrayHasKey('red', $parameters);
        $this->assertArrayHasKey('blue', $parameters);
    }

    public function testCreateWorkerDoesNotForwardCallToAppropriateFactoryWhenSkipFactory()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->twice()
            ->with('config')
            ->andReturn([
                'idle' => [
                    'job' => [
                        'worker' => [
                            'types' => [
                                FooWorker::IDENTIFIER => [
                                    'class' => FooWorker::class,
                                    'parameters' => [
                                        'red' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $factory = new WorkerFactory();

        $factory($container);
        $this->assertInstanceOf(WorkerFactory::class, $factory);

        $worker = $factory->createWorker(FooWorker::IDENTIFIER, ['blue' => true]);
        $this->assertInstanceOf(FooWorker::class, $worker);

        $parameters = $worker->getParameters();

        $this->assertArrayHasKey('red', $parameters);
        $this->assertArrayHasKey('blue', $parameters);
    }

    public function testCreateWorkerThrowsConfigurationExceptionWhenMissingClassConfiguration()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->twice()
            ->with('config')
            ->andReturn([
                'idle' => [
                    'job' => [
                        'worker' => [
                            'types' => [
                                BazWorker::IDENTIFIER => [
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $factory = new WorkerFactory();

        $factory($container);
        $this->assertInstanceOf(WorkerFactory::class, $factory);

        $this->expectException(ConfigurationException::class);
        $factory->createWorker(BazWorker::IDENTIFIER, ['blue' => true]);
    }
}
