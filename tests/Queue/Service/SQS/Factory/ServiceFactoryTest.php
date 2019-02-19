<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue\Service\SQS\Factory;

use LinioPay\Idle\Queue\Service\SQS\Service as SQSService;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Monolog\Handler\TestHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ServiceFactoryTest extends TestCase
{
    protected $apiTestHandler;

    protected function setUp() : void
    {
        $this->apiTestHandler = new TestHandler();
    }

    public function testItCreatesService()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->with('config')
            ->andReturn([
                'queue' => [
                    'active_service' => SQSService::IDENTIFIER,
                    'services' => [
                        SQSService::IDENTIFIER => [
                            'type' => 'Bar',
                            'client' => [
                                'version' => 'latest',
                                'region' => 'us-east-1',
                            ],
                        ],
                    ],
                ],
            ]);

        $container->shouldReceive('get')
            ->once()
            ->with(LoggerInterface::class)
            ->andReturn(new \Monolog\Logger('api', [$this->apiTestHandler]));

        $factory = new ServiceFactory();
        $service = $factory($container);

        $this->assertInstanceOf(SQSService::class, $service);
    }
}
