<?php

declare(strict_types=1);

namespace LinioPay\Idle\Config;

use LinioPay\Idle\Config\Exception\ConfigurationException;
use LinioPay\Idle\Job\Jobs\MessageJob;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\Message\Message;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQS;
use LinioPay\Idle\TestCase;
use Mockery as m;

class IdleConfigTest extends TestCase
{
    /** @var IdleConfig */
    protected $config;

    protected $services;

    protected $messages;

    protected $jobs;

    protected $workers;

    public function setUp() : void
    {
        parent::setUp();
        $this->services = [
            SQS::IDENTIFIER => [
                'class' => SQS::class,
                'client' => [
                    'version' => 'latest',
                    'region' => getenv('AWS_REGION'),
                ],
            ],
        ];

        $this->messages = [
            QueueMessage::IDENTIFIER => [
                'default' => [
                    'parameters' => [
                        'service' => SQS::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my-queue' => [
                        'queue' => [
                            'parameters' => [
                                'DelaySeconds' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->jobs = [
            MessageJob::IDENTIFIER => [
                'class' => MessageJob::class,
                'parameters' => [
                    QueueMessage::IDENTIFIER => [
                        'my-queue' => [
                            'parameters' => [
                                'workers' => [
                                    [
                                        'type' => FooWorker::IDENTIFIER,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->workers = [
            FooWorker::IDENTIFIER => [
                'class' => FooWorker::class,
                'parameters' => [
                    'foo' => 'baz',
                    'a' => 'b',
                ],
            ],
        ];

        $this->config = new IdleConfig(
            $this->services,
            $this->messages,
            $this->jobs,
            $this->workers
        );
    }

    public function testCanGetMainConfigs()
    {
        $this->assertSame($this->services, $this->config->getServicesConfig());
        $this->assertSame($this->messages, $this->config->getMessagesConfig());
        $this->assertSame($this->jobs, $this->config->getJobsConfig());
        $this->assertSame($this->workers, $this->config->getWorkersConfig());
    }

    public function testCanGetConfigsByIdentifier()
    {
        $this->assertSame($this->services[SQS::IDENTIFIER], $this->config->getServiceConfig(SQS::IDENTIFIER));
        $this->assertSame($this->services[SQS::IDENTIFIER]['class'], $this->config->getServiceClass(SQS::IDENTIFIER));

        $this->assertSame($this->messages[QueueMessage::IDENTIFIER], $this->config->getMessageTypeConfig(QueueMessage::IDENTIFIER));

        $this->assertSame($this->jobs[MessageJob::IDENTIFIER], $this->config->getJobConfig(MessageJob::IDENTIFIER));
        $this->assertSame($this->jobs[MessageJob::IDENTIFIER]['class'], $this->config->getJobClass(MessageJob::IDENTIFIER));

        $this->assertSame($this->workers[FooWorker::IDENTIFIER], $this->config->getWorkerConfig(FooWorker::IDENTIFIER));
        $this->assertSame($this->workers[FooWorker::IDENTIFIER]['class'], $this->config->getWorkerClass(FooWorker::IDENTIFIER));
    }

    public function testCanGetMergedWorkerConfig()
    {
        $this->assertSame([
            'class' => FooWorker::class,
            'parameters' => [
                'foo' => 'bar',
                'a' => 'b',
            ],
        ], $this->config->getMergedWorkerConfig(FooWorker::IDENTIFIER, [
            'foo' => 'bar',
        ]));
    }

    public function testWillThrowExceptionWhenMessageTypeDoesNotExist()
    {
        $this->expectException(ConfigurationException::class);
        $this->config->getMessageTypeConfig('fail_type');
    }

    public function testWillThrowExceptionWhenMessageTypeOrSourceDoNotExist()
    {
        $message = m::mock(Message::class);
        $message->shouldReceive('getIdleIdentifier')
            ->andReturn('fail_type');
        $message->shouldReceive('getSourceName')
            ->andReturn('fail_source');

        $this->expectException(ConfigurationException::class);
        $this->config->getMessageConfig($message);
    }

    public function testWillThrowExceptionWhenJobTypeDoesNotExist()
    {
        $this->expectException(ConfigurationException::class);
        $this->config->getJobConfig('fail_type');
    }
}
