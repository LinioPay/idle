<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Worker;
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
            ->with(Worker::class)
            ->andReturn(m::mock(Worker::class));

        $factory = new WorkerFactory();

        $factory($container);
        $this->assertInstanceOf(WorkerFactory::class, $factory);

        $worker = $factory->createWorker(Worker::class);
        $this->assertInstanceOf(Worker::class, $worker);
    }
}
