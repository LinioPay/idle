<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory;

use LinioPay\Idle\Message\Messages\PublishSubscribe\Message\TopicMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service as PubSubService;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
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
                            TopicMessage::IDENTIFIER => [
                                'types' => [
                                    'my-topic' => [
                                        'publish' => [
                                            'parameters' => [
                                            ],
                                        ],
                                        'parameters' => [
                                            'service' => PubSubService::IDENTIFIER,
                                            'red' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'service' => [
                            'types' => [
                                PubSubService::IDENTIFIER => [
                                    'class' => PubSubService::class,
                                    'client' => [
                                        'projectId' => 'fooProject',
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
            ->andReturn(new Logger('api', [$this->apiTestHandler]));

        $factory = new ServiceFactory();
        $factory($container);

        $message = new TopicMessage('my-topic', 'foobody');
        $service = $factory->createFromMessage($message);

        $this->assertInstanceOf(PubSubService::class, $service);
    }
}
