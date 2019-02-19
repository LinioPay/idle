<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Jobs\QueueJob;
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
            ->with('job-config')
            ->andReturn([]);
        $container->shouldReceive('get')
            ->once()
            ->with(Service::class)
            ->andReturn(m::mock(Service::class));
        $container->shouldReceive('get')
            ->once()
            ->with(WorkerFactory::class)
            ->andReturn(m::mock(WorkerFactory::class));

        $factory = new QueueJobFactory();

        $job = $factory($container);
        $this->assertInstanceOf(QueueJob::class, $job);
    }
}
