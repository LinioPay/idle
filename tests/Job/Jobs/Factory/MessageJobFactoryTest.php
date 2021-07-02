<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Jobs\MessageJob;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class MessageJobFactoryTest extends TestCase
{
    public function testCreatesMessageJobSuccessfully()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());
        $container->shouldReceive('get')
            ->once()
            ->with(MessageFactoryInterface::class)
            ->andReturn(m::mock(MessageFactoryInterface::class));
        $container->shouldReceive('get')
            ->once()
            ->with(WorkerFactoryInterface::class)
            ->andReturn(m::mock(WorkerFactory::class));

        $factory = new MessageJobFactory($container);

        $this->assertInstanceOf(MessageJob::class, $factory->createJob(MessageJob::IDENTIFIER, []));
    }
}
