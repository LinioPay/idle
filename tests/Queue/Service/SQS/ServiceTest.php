<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue\Service\SQS;

use Aws\Result;
use Aws\Sqs\SqsClient;
use LinioPay\Idle\Queue\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Queue\Message;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Monolog\Handler\TestHandler;
use Zend\Stdlib\ArrayUtils;

class ServiceTest extends TestCase
{
    protected $apiTestHandler;

    protected $logger;

    protected $config;

    protected $queueIdentifier;

    protected $sqsClient;

    protected function setUp() : void
    {
        $this->apiTestHandler = new TestHandler();

        $this->logger = new \Monolog\Logger('api', [$this->apiTestHandler]);

        $this->queueIdentifier = 'bar';

        $this->sqsClient = m::mock(SqsClient::class);
        $this->sqsClient->shouldAllowMockingMethod('getQueueUrl');
        $this->sqsClient->shouldAllowMockingMethod('sendMessage');
        $this->sqsClient->shouldAllowMockingMethod('receiveMessage');
        $this->sqsClient->shouldAllowMockingMethod('deleteMessage');

        $this->config = [
            'type' => Service::class,
            'client' => [
                'version' => 'latest',
                'region' => 'us-east-1',
            ],
            'queues' => [
                'default' => [
                    'dequeue' => [
                        'parameters' => [
                            'MaxNumberOfMessages' => 1,
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'queue' => [
                        'parameters' => [
                            'DelaySeconds' => 1,
                        ],
                        'attributes' => [
                            'DelaySeconds' => 0,
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'delete' => [
                        'enabled' => false,
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'worker' => [
                        //'type' => '', Must be overriden
                        'parameters' => [],
                    ],
                ],
                'bar' => [
                    'worker' => [
                        'type' => \stdClass::class,
                    ],
                ],
            ],
        ];
    }

    public function testQueueingSuccessfully() : void
    {
        $message = new Message($this->queueIdentifier, 'mbody');

        $this->sqsClient->shouldReceive('getQueueUrl')
            ->once()
            ->with(['QueueName' => $this->queueIdentifier])
            ->andReturn(new Result(['QueueUrl' => 'http://foo.bar']));

        $this->sqsClient->shouldReceive('sendMessage')
            ->once()
            ->with([
                'DelaySeconds' => 1,
                'QueueUrl' => 'http://foo.bar',
                'MessageBody' => $message->getBody(),
                'MessageAttributes' => $message->getAttributes(),
            ])
            ->andReturn(new Result(['MessageId' => 'foo123']));

        $service = new Service($this->sqsClient, $this->config, $this->logger);
        $this->assertTrue($service->queue($message));
    }

    public function testQueueingFailure()
    {
        $message = new Message($this->queueIdentifier, 'mbody');

        $this->sqsClient->shouldReceive('getQueueUrl')
            ->once()
            ->with(['QueueName' => $this->queueIdentifier])
            ->andThrow(new \Exception('boom'));

        $service = new Service($this->sqsClient, $this->config, $this->logger);
        $this->assertFalse($service->queue($message));
    }

    public function testQueueingException()
    {
        $message = new Message($this->queueIdentifier, 'mbody');

        $this->sqsClient->shouldReceive('getQueueUrl')
            ->once()
            ->with(['QueueName' => $this->queueIdentifier])
            ->andThrow(new \Exception('boom'));

        $config = ArrayUtils::merge($this->config, [
            'queues' => [
                'bar' => [
                    'queue' => [
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $this->expectException(\Exception::class);
        $service = new Service($this->sqsClient, $config, $this->logger);
        $service->queue($message);
    }

    public function testDequeueingSuccessfully()
    {
        $this->sqsClient->shouldReceive('getQueueUrl')
            ->once()
            ->with(['QueueName' => $this->queueIdentifier])
            ->andReturn(new Result(['QueueUrl' => 'http://foo.bar']));

        $this->sqsClient->shouldReceive('receiveMessage')
            ->once()
            ->with([
                'MaxNumberOfMessages' => 1,
                'VisibilityTimeout' => 5,
                'QueueUrl' => 'http://foo.bar',
            ])
            ->andReturn(new Result([
                'Messages' => [
                    [
                        'MessageId' => '123',
                        'Body' => 'mbody',
                        'MessageAttributes' => [
                            [
                                'Name' => 'myattr',
                                'Value' => [
                                    'StringValue' => 'myval',
                                    'DataType' => 'String',
                                ],
                            ],
                        ],
                        'ReceiptHandle' => '123handle',
                    ],
                ],
            ]));

        $service = new Service($this->sqsClient, $this->config, $this->logger);
        $messages = $service->dequeue($this->queueIdentifier, ['VisibilityTimeout' => 5]);

        $this->assertArrayHasKey(0, $messages);

        /** @var Message $message */
        $message = $messages[0];

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame('123', $message->getMessageIdentifier());
        $this->assertSame('bar', $message->getQueueIdentifier());
        $this->assertSame('mbody', $message->getBody());
        $this->assertCount(1, $message->getAttributes());
        $this->assertArrayHasKey('ReceiptHandle', $message->getTemporaryMetadata());
        $this->assertSame('123handle', $message->getTemporaryMetadata()['ReceiptHandle']);
    }

    public function testDequeueingFailure()
    {
        $this->sqsClient->shouldReceive('getQueueUrl')
            ->once()
            ->with(['QueueName' => $this->queueIdentifier])
            ->andThrow(new \Exception('boom'));

        $service = new Service($this->sqsClient, $this->config, $this->logger);

        $this->assertEmpty($service->dequeue($this->queueIdentifier));
    }

    public function testDequeueingException()
    {
        $this->sqsClient->shouldReceive('getQueueUrl')
            ->once()
            ->with(['QueueName' => $this->queueIdentifier])
            ->andThrow(new \Exception('boom'));

        $config = ArrayUtils::merge($this->config, [
            'queues' => [
                'bar' => [
                    'dequeue' => [
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $service = new Service($this->sqsClient, $config, $this->logger);

        $this->expectException(\Exception::class);
        $service->dequeue($this->queueIdentifier);
    }

    public function testDeletingSuccessfully()
    {
        $message = new Message($this->queueIdentifier, 'mbody', [], 'foo123', ['ReceiptHandle' => 'handle123']);

        $this->sqsClient->shouldReceive('getQueueUrl')
            ->once()
            ->with(['QueueName' => $this->queueIdentifier])
            ->andReturn(new Result(['QueueUrl' => 'http://foo.bar']));

        $this->sqsClient->shouldReceive('deleteMessage')
            ->once()
            ->with([
                'QueueUrl' => 'http://foo.bar',
                'ReceiptHandle' => 'handle123',
            ])
            ->andReturn(new Result(['MessageId' => 'foo123']));

        $service = new Service($this->sqsClient, $this->config, $this->logger);
        $this->assertTrue($service->delete($message));
    }

    public function testDeletingFailureDueToMissingReceiptHandle()
    {
        $message = new Message($this->queueIdentifier, 'mbody', [], 'foo123');
        $service = new Service($this->sqsClient, $this->config, $this->logger);
        $this->assertFalse($service->delete($message));
    }

    public function testDeletingException()
    {
        $message = new Message($this->queueIdentifier, 'mbody', [], 'foo123');

        $config = ArrayUtils::merge($this->config, [
            'queues' => [
                'bar' => [
                    'delete' => [
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $service = new Service($this->sqsClient, $config, $this->logger);

        $this->expectException(InvalidMessageParameterException::class);
        $service->delete($message);
    }

    public function testUnknownQueueThrowsException()
    {
        $message = new Message('fakequeue', 'mbody');

        $service = new Service($this->sqsClient, $this->config, $this->logger);

        $this->expectException(\Exception::class);

        $service->queue($message);
    }

    public function testCanGetWorkerConfig()
    {
        $service = new Service($this->sqsClient, $this->config, $this->logger);

        $workerConfig = $service->getQueueWorkerConfig('bar');

        $this->assertSame(['parameters' => [], 'type' => \stdClass::class], $workerConfig);
    }
}
