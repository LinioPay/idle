<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use LinioPay\Idle\Config\Exception\ConfigurationException;
use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQSService;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Monolog\Handler\TestHandler;
use Psr\Container\ContainerInterface;

class ServiceFactoryTest extends TestCase
{
    protected $apiTestHandler;

    /** @var IdleConfig */
    protected $idleConfig;

    protected function setUp() : void
    {
        $this->apiTestHandler = new TestHandler();
        $this->idleConfig = new IdleConfig([
            SQSService::IDENTIFIER => [
                'class' => 'fooclass',
                'client' => [
                    'version' => 'latest',
                    'region' => 'us-east-1',
                ],
            ],
        ],
            [
                QueueMessage::IDENTIFIER => [
                    'default' => [
                        'dequeue' => [
                            'parameters' => [ // Configure behavior for when retrieving messages
                                'MaxNumberOfMessages' => 1,
                            ],
                            'error' => [
                                'suppression' => true,
                            ],
                        ],
                        'queue' => [
                            'parameters' => [ // Configure behavior for when adding a new message
                                // 'DelaySeconds' => 0, // The number of seconds (0 to 900 - 15 minutes) to delay a specific message. Messages with a positive DelaySeconds value become available for processing after the delay time is finished. If you don't specify a value, the default value for the queue applies.
                            ],
                            'error' => [
                                'suppression' => true,
                            ],
                        ],
                        'parameters' => [
                            'service' => SQSService::IDENTIFIER,
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
            ]
        );
    }

    public function testItCreatesService()
    {
        $sqsFactory = m::mock(ServiceFactory::class);
        $sqsFactory->shouldReceive('createFromMessage')
            ->once()
            ->andReturn(m::mock(SQSService::class));

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('fooclass')
            ->andReturn($sqsFactory);
        $container->shouldReceive('get')
            ->with(IdleConfig::class)
            ->andReturn($this->idleConfig);

        $factory = new ServiceFactory($container);

        $message = new QueueMessage\Message('my-queue', 'foobody');
        $service = $factory->createFromMessage($message);

        $this->assertInstanceOf(SQSService::class, $service);
    }

    public function testItThrowsConfigurationExceptionForUnknownClass()
    {
        $config = new IdleConfig([
            SQSService::IDENTIFIER => [
                'client' => [
                    'version' => 'latest',
                    'region' => 'us-east-1',
                ],
            ],
        ], $this->idleConfig->getMessagesConfig());

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->with(IdleConfig::class)
            ->andReturn($config);

        $factory = new ServiceFactory($container);

        $message = new QueueMessage\Message('unknown-queue', 'foobody');

        $this->expectException(ConfigurationException::class);
        $factory->createFromMessage($message);
    }
}
