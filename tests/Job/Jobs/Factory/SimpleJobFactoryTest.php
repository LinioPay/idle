<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class SimpleJobFactoryTest extends TestCase
{
    public function testCreatesSimpleJobSuccessfully()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('config')
            ->andReturn([]);
        $container->shouldReceive('get')
            ->once()
            ->with(WorkerFactory::class)
            ->andReturn(m::mock(WorkerFactory::class));

        $factory = new SimpleJobFactory();

        $job = $factory($container);
        $this->assertInstanceOf(SimpleJob::class, $job);
    }
}
