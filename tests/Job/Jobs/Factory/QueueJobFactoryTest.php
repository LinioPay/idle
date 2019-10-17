<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Jobs\QueueJob;
use LinioPay\Idle\Job\Tracker\Service\Factory\Service as TrackerServiceFactoryInterface;
use LinioPay\Idle\Job\Tracker\Service\Factory\ServiceFactory as TrackerServiceFactory;
use LinioPay\Idle\Job\Workers\Factory\Worker as WorkerFactoryInterface;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Queue\Service;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class QueueJobFactoryTest extends TestCase
{
    public function testCreatesQueueJobSuccessfully()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('config')
            ->andReturn([]);
        $container->shouldReceive('get')
            ->once()
            ->with(Service::class)
            ->andReturn(m::mock(Service::class));
        $container->shouldReceive('get')
            ->once()
            ->with(WorkerFactoryInterface::class)
            ->andReturn(m::mock(WorkerFactory::class));
        $container->shouldReceive('get')
            ->once()
            ->with(TrackerServiceFactoryInterface::class)
            ->andReturn(m::mock(TrackerServiceFactory::class));

        $factory = new QueueJobFactory();

        $job = $factory($container);
        $this->assertInstanceOf(QueueJob::class, $job);
    }
}
