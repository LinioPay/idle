<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks;

use Google\Cloud\Tasks\V2\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\OidcToken;
use Google\Cloud\Tasks\V2\Task;
use GuzzleHttp\Psr7\Request;
use Laminas\Stdlib\ArrayUtils;
use LinioPay\Idle\Message\Exception\FailedReceivingMessageException;
use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Exception\InvalidServiceConfigurationException;
use LinioPay\Idle\Message\Exception\UnsupportedServiceOperationException;
use LinioPay\Idle\Message\Messages\Queue\Message\Message;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Exception\InvalidMessageRequestException;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service as CloudTasksService;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Mockery\Mock;
use Monolog\Handler\TestHandler;

class ServiceTest extends TestCase
{
    protected $apiTestHandler;

    protected $logger;

    protected $config;

    protected $queueIdentifier;

    /** @var Mock|CloudTasksClient */
    protected $tasksClient;

    protected function setUp() : void
    {
        $this->apiTestHandler = new TestHandler();

        $this->logger = new \Monolog\Logger('api', [$this->apiTestHandler]);

        $this->queueIdentifier = 'bar';

        $this->tasksClient = m::mock(CloudTasksClient::class);
        $this->tasksClient->shouldReceive('close');

        $this->config = [
            'queue' => [
                'parameters' => [
                ],
                'error' => [
                    'suppression' => false,
                ],
            ],
            'delete' => [
                'error' => [
                    'suppression' => false,
                ],
            ],
            'parameters' => [
                'service' => [
                    'client' => [
                        'projectId' => 'foo-project',
                        'location' => 'foo-location',
                    ],
                ],
            ],
        ];
    }

    public function testQueueingSuccessfully() : void
    {
        $request = new Request(
            'PUT',
            'http://foobar.example.com',
            [
                'Content-Type' => 'application/json',
            ],
            'payload'
        );

        $oidcToken = $this->fake(OidcToken::class);

        $message = new Message($this->queueIdentifier, '', [
            'request' => $request,
            'oidc_token' => $oidcToken,
        ]);

        $serviceConfig = $this->config['parameters']['service'];

        $queueName = 'projects/PROJECT_ID/locations/LOCATION_ID/queues/QUEUE_ID';

        $this->tasksClient->shouldReceive('queueName')
            ->once()
            ->with($serviceConfig['client']['projectId'], $serviceConfig['client']['location'], $this->queueIdentifier)
            ->andReturn($queueName);

        $this->tasksClient->shouldReceive('createTask')
            ->once()
            ->with($queueName, m::on(function (Task $task) use ($request, $oidcToken) {
                $this->assertInstanceOf(Task::class, $task);
                $taskRequest = $task->getHttpRequest();

                $body = $request->getBody();
                $body->rewind();

                $this->assertSame((string) $request->getUri(), $taskRequest->getUrl());
                $this->assertSame(HttpMethod::value(strtoupper($request->getMethod())), $taskRequest->getHttpMethod());
                $this->assertSame('application/json', $taskRequest->getHeaders()->offsetGet('Content-Type'));
                $this->assertSame($body->getContents(), $taskRequest->getBody());

                $this->assertSame($oidcToken, $taskRequest->getOidcToken());

                return true;
            }), [])
            ->andReturn($this->fake(Task::class, ['name' => 'footask']));

        $service = new CloudTasksService($this->tasksClient, $this->config, $this->logger);
        $this->assertTrue($service->queue($message));
        $this->assertSame($this->config, $service->getConfig());
        $this->assertSame($this->config['parameters']['service'], $service->getServiceConfig());
    }

    public function testQueueingFailureWhenNoRequestProvided()
    {
        $message = new Message($this->queueIdentifier, '', []);

        $this->tasksClient->shouldReceive('queueName')
            ->once()
            ->with('foo-project', 'foo-location', 'bar')
            ->andReturn('foo/foo/bar');

        $service = new CloudTasksService($this->tasksClient, $this->config, $this->logger);

        $this->expectException(InvalidMessageRequestException::class);
        $service->queue($message);
    }

    public function testQueueingFailureWhenInvalidServiceConfigProvided()
    {
        $request = new Request(
            'PUT',
            'http://foobar.example.com',
            [
                'Content-Type' => 'application/json',
            ],
            'payload'
        );

        $message = new Message($this->queueIdentifier, '', [
            'request' => $request,
        ]);

        unset($this->config['parameters']['service']['client']['location']);

        $service = new CloudTasksService($this->tasksClient, $this->config, $this->logger);
        $this->expectException(InvalidServiceConfigurationException::class);
        $service->queue($message);
    }

    public function testQueueingException()
    {
        $message = new Message($this->queueIdentifier);

        $config = ArrayUtils::merge($this->config, [
            'queue' => [
                'error' => [
                    'suppression' => true,
                ],
            ],
        ]);

        $service = new Service($this->tasksClient, $config, $this->logger);
        $this->assertFalse($service->queue($message));
    }

    public function testDequeueingWithoutErrorSuppression()
    {
        $service = new Service($this->tasksClient, $this->config, $this->logger);
        $this->expectException(UnsupportedServiceOperationException::class);
        $service->dequeue($this->queueIdentifier);
    }

    public function testDequeueingWithErrorSuppression()
    {
        $config = ArrayUtils::merge($this->config, [
            'dequeue' => [
                'error' => [
                    'suppression' => true,
                ],
            ],
        ]);
        $service = new Service($this->tasksClient, $config, $this->logger);
        $this->assertEmpty($service->dequeue($this->queueIdentifier));
    }

    public function testDequeueingOneFails()
    {
        $service = new Service($this->tasksClient, $this->config, $this->logger);
        $this->expectException(FailedReceivingMessageException::class);
        $service->dequeueOneOrFail($this->queueIdentifier, []);
    }

    public function testDeletingSuccessfully()
    {
        $request = new Request(
            'PUT',
            'http://foobar.example.com',
            [
                'Content-Type' => 'application/json',
            ],
            'payload'
        );

        $message = new Message($this->queueIdentifier, '', [
            'request' => $request,
        ], 'foo-id');

        $this->tasksClient->shouldReceive('deleteTask')
            ->once()
            ->with('foo-id', []);

        $service = new Service($this->tasksClient, $this->config, $this->logger);
        $this->assertTrue($service->delete($message));
    }

    public function testDeletingFailureDueToMissingMessageId()
    {
        $message = new Message($this->queueIdentifier);
        $service = new Service($this->tasksClient, $this->config, $this->logger);
        $this->expectException(InvalidMessageParameterException::class);
        $service->delete($message);
    }

    public function testDeletingExceptionWithSuppression()
    {
        $message = new Message($this->queueIdentifier);

        $config = ArrayUtils::merge($this->config, [
            'delete' => [
                'error' => [
                    'suppression' => true,
                ],
            ],
        ]);

        $service = new Service($this->tasksClient, $config, $this->logger);
        $this->assertFalse($service->delete($message));
    }
}
