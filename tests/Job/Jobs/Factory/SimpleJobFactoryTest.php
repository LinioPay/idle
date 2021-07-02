<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;
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
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());
        $container->shouldReceive('get')
            ->once()
            ->with(WorkerFactoryInterface::class)
            ->andReturn(m::mock(WorkerFactory::class));

        $factory = new SimpleJobFactory($container);

        $this->assertInstanceOf(SimpleJob::class, $factory->createJob(SimpleJob::IDENTIFIER, []));
    }
}
