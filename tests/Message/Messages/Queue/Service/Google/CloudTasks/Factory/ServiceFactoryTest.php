<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Factory;

use LinioPay\Idle\Config\Exception\ConfigurationException;
use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service as CloudTasksService;
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
            ->with(IdleConfig::class)
            ->andReturn(
                new IdleConfig(
                    [
                        CloudTasksService::IDENTIFIER => [
                            'class' => CloudTasksService::class,
                            'client' => [
                                'credentialsConfig' => [
                                    'keyFile' => __DIR__ . '/../../../../../Helper/Google/credentials.json',
                                ],
                            ],
                            'projectId' => 'foo-project',
                            'location' => 'foo-location',
                        ],
                    ],
                    [
                        QueueMessage::IDENTIFIER => [
                            'default' => [
                                'parameters' => [
                                    'service' => CloudTasksService::IDENTIFIER,
                                ],
                            ],
                            'types' => [
                                'my-queue' => [
                                    'parameters' => [
                                        'red' => true,
                                    ],
                                ],
                            ],
                        ],
                    ]
                ));

        $container->shouldReceive('get')
            ->once()
            ->with(LoggerInterface::class)
            ->andReturn(new \Monolog\Logger('api', [$this->apiTestHandler]));

        $factory = new ServiceFactory($container);

        $message = new QueueMessage\Message('my-queue', 'foobody');
        $service = $factory->createFromMessage($message);

        $this->assertInstanceOf(CloudTasksService::class, $service);
    }

    public function testItFailsToCreateServiceWhenInvalidConfig()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->with(IdleConfig::class)
            ->andReturn(
                new IdleConfig(
                    [
                        CloudTasksService::IDENTIFIER => [],
                    ],
                    [
                        QueueMessage::IDENTIFIER => [
                            'default' => [
                                'parameters' => [
                                    'service' => CloudTasksService::IDENTIFIER,
                                ],
                            ],
                            'types' => [
                                'my-queue' => [],
                            ],
                        ],
                    ]
                )
            );

        $factory = new ServiceFactory($container);

        $message = new QueueMessage\Message('my-queue', 'foobody');
        $this->expectException(ConfigurationException::class);
        $factory->createFromMessage($message);
    }
}
