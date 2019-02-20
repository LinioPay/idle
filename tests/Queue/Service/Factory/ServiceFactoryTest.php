<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue\Service\Factory;

use LinioPay\Idle\Queue\Service;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class ServiceFactoryTest extends TestCase
{
    public function testItCreatesActiveService()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->with('config')
            ->andReturn([
                'queue' => [
                    'active_service' => 'foo',
                    'services' => [
                        'foo' => [
                            'type' => 'Bar',
                        ],
                    ],
                ],
            ]);

        $container->shouldReceive('get')
            ->once()
            ->with('Bar')
            ->andReturn(m::mock(Service::class));

        $factory = new ServiceFactory();
        $factory($container);

        $this->assertTrue(true);
    }
}
