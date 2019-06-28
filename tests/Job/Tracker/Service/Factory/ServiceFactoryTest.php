<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Tracker\Service\Factory;

use LinioPay\Idle\Job\Tracker\Service;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class ServiceFactoryTest extends TestCase
{
    public function testItCreatesTrackerService()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(Service::class)
            ->andReturn(m::mock(Service::class));

        $factory = new ServiceFactory();

        $factory($container);
        $this->assertInstanceOf(ServiceFactory::class, $factory);

        $worker = $factory->createTrackerService(Service::class);
        $this->assertInstanceOf(Service::class, $worker);
    }
}
