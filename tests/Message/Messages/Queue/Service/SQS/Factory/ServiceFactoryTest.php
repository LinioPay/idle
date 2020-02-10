<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory;

use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQSService;
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
                'idle' => [
                    'message' => [
                        'types' => [
                            QueueMessage::IDENTIFIER => [
                                'default' => [
                                    'dequeue' => [
                                        'parameters' => [
                                            'MaxNumberOfMessages' => 1,
                                            'Delay' => 30,
                                        ],
                                        'error' => [
                                            'suppression' => true,
                                        ],
                                    ],
                                    'queue' => [
                                        'parameters' => [],
                                        'error' => [
                                            'suppression' => true,
                                        ],
                                    ],
                                    'parameters' => [
                                        'service' => SQSService::IDENTIFIER,
                                    ],
                                ],
                                'service_default' => [
                                    SQSService::IDENTIFIER => [
                                        'dequeue' => [
                                            'parameters' => [
                                                'Delay' => 60,
                                            ],
                                        ],
                                    ],
                                ],
                                'types' => [
                                    'my-queue' => [
                                        'dequeue' => [
                                            'parameters' => [
                                                'MaxNumberOfMessages' => 2,
                                            ],
                                        ],
                                        'parameters' => [
                                            'red' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'service' => [
                            'types' => [
                                SQSService::IDENTIFIER => [
                                    'class' => SQSService::class,
                                    'client' => [
                                        'version' => 'latest',
                                        'region' => 'us-east-1',
                                    ],
                                ],
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
        $factory($container);

        $message = new QueueMessage\Message('my-queue', 'foobody');
        $service = $factory->createFromMessage($message);

        $this->assertInstanceOf(SQSService::class, $service);

        $config = $service->getConfig();
        $this->assertSame(2, $config['dequeue']['parameters']['MaxNumberOfMessages'] ?? 0);
        $this->assertSame(60, $config['dequeue']['parameters']['Delay'] ?? 0);
    }
}
