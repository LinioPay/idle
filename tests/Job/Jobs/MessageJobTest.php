<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Exception\InvalidJobParameterException;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\TrackingWorker;
use LinioPay\Idle\Job\Workers\DefaultWorker;
use LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessageInterface;
use LinioPay\Idle\Message\Messages\Queue\Message\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQS;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Mockery\Mock;

class MessageJobTest extends TestCase
{
    /** @var Mock|WorkerFactory */
    protected $workerFactory;

    /** @var Mock|MessageFactoryInterface */
    protected $messageFactory;

    /** @var IdleConfig */
    protected $idleConfig;

    public function setUp() : void
    {
        parent::setUp();

        $this->workerFactory = m::mock(WorkerFactory::class);
        $this->messageFactory = m::mock(MessageFactoryInterface::class);

        $this->idleConfig = new IdleConfig(
            [],
            [],
            [
                MessageJob::IDENTIFIER => [
                    'class' => MessageJob::class,
                    'parameters' => [
                        QueueMessageInterface::IDENTIFIER => [
                            'foo_queue' => [
                                'service' => SQS::IDENTIFIER,
                                'parameters' => [
                                    'workers' => [
                                        [
                                            'type' => FooWorker::class,
                                            'parameters' => [],
                                        ],
                                        [
                                            'type' => DynamoDBTrackerWorker::class,
                                            'parameters' => [
                                                'table' => 'my_foo_queue_tracker_table',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                DynamoDBTrackerWorker::IDENTIFIER => [
                    'class' => DynamoDBTrackerWorker::class,
                    'client' => [
                        'version' => 'latest',
                        'region' => 'us-east-1',
                    ],
                ],
                FooWorker::IDENTIFIER => [
                    'class' => FooWorker::class,
                ],
            ]
        );
    }

    public function tearDown() : void
    {
        parent::tearDown();

        $this->workerFactory = null;
        $this->messageFactory = null;
    }

    public function messageProvider()
    {
        return [
            [new QueueMessage('foo_queue', 'bar')],
            [[
                'queue_identifier' => 'foo_queue',
                'body' => 'bar',
            ]],
        ];
    }

    /**
     * @dataProvider messageProvider
     */
    public function testItCanProcessJobSuccessfully($message)
    {
        if (is_array($message)) {
            $this->messageFactory->shouldReceive('createMessage')
                ->once()
                ->with($message)
                ->andReturn(new QueueMessage('foo_queue', 'bar'));
        }

        $worker = m::mock(DefaultWorker::class);
        $worker->shouldReceive('work')
            ->once()
            ->andReturnTrue();
        $worker->shouldReceive('getErrors')
            ->andReturn([]);

        $trackingWorker = m::mock(TrackingWorker::class);
        $trackingWorker->shouldReceive('work')
            ->twice()
            ->andReturnTrue();
        $trackingWorker->shouldReceive('getErrors')
            ->andReturn([]);

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker, $trackingWorker);

        $job = new MessageJob($this->idleConfig, $this->messageFactory, $this->workerFactory);

        $job->setParameters(['message' => $message]);
        $job->validateParameters();
        $job->process();

        $this->assertTrue($job->isSuccessful());
        $this->assertGreaterThan(0, $job->getDuration());
        $this->assertEmpty($job->getErrors());
    }

    public function testThrowsInvalidParameterExceptionWhenNoMessageProvided()
    {
        /** @var Job $job */
        $job = $this->fake(MessageJob::class);
        $this->expectException(InvalidJobParameterException::class);
        $job->validateParameters();
    }
}
