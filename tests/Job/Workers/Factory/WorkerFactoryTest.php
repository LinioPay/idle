<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Config\Exception\ConfigurationException;
use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Workers\BazWorker;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class WorkerFactoryTest extends TestCase
{
    public function testCreateWorkerDoesNotForwardCallToAppropriateFactoryWhenSkipFactory()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig([], [], [], [
                FooWorker::IDENTIFIER => [
                    'class' => FooWorker::class,
                    'parameters' => [
                        'red' => true,
                    ],
                ],
            ]));

        $factory = new WorkerFactory($container);

        $this->assertInstanceOf(WorkerFactory::class, $factory);

        $worker = $factory->createWorker(FooWorker::IDENTIFIER, ['blue' => true]);
        $this->assertInstanceOf(FooWorker::class, $worker);

        $parameters = $worker->getParameters();

        $this->assertArrayHasKey('red', $parameters);
        $this->assertArrayHasKey('blue', $parameters);
    }

    public function testCreateWorkerForwardsCallToAppropriateFactory()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig([], [], [], [
                BazWorker::IDENTIFIER => [
                    'class' => BazWorker::class,
                    'parameters' => [
                        'red' => true,
                    ],
                ],
            ]));
        $container->shouldReceive('get')
            ->once()
            ->with(BazWorker::class)
            ->andReturn(new BazWorkerFactory($container));

        $factory = new WorkerFactory($container);
        $this->assertInstanceOf(WorkerFactory::class, $factory);

        $worker = $factory->createWorker(BazWorker::IDENTIFIER, ['blue' => true]);
        $this->assertInstanceOf(BazWorker::class, $worker);

        $parameters = $worker->getParameters();

        $this->assertArrayHasKey('red', $parameters);
        $this->assertArrayHasKey('blue', $parameters);
    }

    public function testCreateWorkerThrowsConfigurationExceptionWhenMissingClassConfiguration()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig([], [], [], [
                BazWorker::IDENTIFIER => [],
            ]));

        $factory = new WorkerFactory($container);
        $this->assertInstanceOf(WorkerFactory::class, $factory);

        $this->expectException(ConfigurationException::class);
        $factory->createWorker(BazWorker::IDENTIFIER, ['blue' => true]);
    }
}
