<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Tracker\Service as TrackerService;
use LinioPay\Idle\Job\Tracker\Service\Factory\ServiceFactory as TrackerServiceFactory;
use LinioPay\Idle\Job\Workers\DefaultWorker;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Queue\Exception\ConfigurationException;
use LinioPay\Idle\Queue\Message;
use LinioPay\Idle\Queue\Service as QueueService;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Mockery\Mock;

class QueueJobTest extends TestCase
{
    /** @var Mock|QueueService */
    protected $service;

    /** @var Mock|WorkerFactory */
    protected $workerFactory;

    /** @var Mock|TrackerServiceFactory */
    protected $trackerServiceFactory;

    /** @var array */
    protected $jobConfig;

    public function setUp() : void
    {
        parent::setUp();

        /* @var Mock|QueueService $service */
        $this->service = m::mock(QueueService::class);

        $this->workerFactory = m::mock(WorkerFactory::class);
        $this->trackerServiceFactory = m::mock(TrackerServiceFactory::class);

        $this->jobConfig = [
            QueueJob::IDENTIFIER => [
                'type' => QueueJob::class,
                'parameters' => [],
            ],
        ];
    }

    public function tearDown() : void
    {
        parent::tearDown();

        $this->service = null;
        $this->workerFactory = null;
        $this->trackerServiceFactory = null;
    }

    public function messageProvider()
    {
        return [
            [new Message('foo', 'bar')],
            [[
                'queue_identifier' => 'foo',
                'body' => 'bar',
            ]],
        ];
    }

    /**
     * @dataProvider messageProvider
     */
    public function testItCanProcessJobSuccessfully($message)
    {
        $this->service->shouldReceive('getQueueWorkerConfig')
            ->andReturn(['type' => DefaultWorker::class]);
        $this->service->shouldReceive('getQueueConfig')
            ->twice()
            ->andReturn([
                'delete' => ['enabled' => true],
                'parameters' => [
                    'tracker' => [
                        'service' => [
                            'type' => 'foo',
                        ],
                    ],
                ],
            ]);
        $this->service->shouldReceive('delete')
            ->once();

        $worker = m::mock(DefaultWorker::class);
        $worker->shouldReceive('setParameters')
            ->once();
        $worker->shouldReceive('work')
            ->once()
            ->andReturnTrue();
        $worker->shouldReceive('getErrors')
            ->andReturn([]);

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $trackerService = m::mock(TrackerService::class);
        $trackerService->shouldReceive('trackJob')
            ->twice();

        $this->trackerServiceFactory->shouldReceive('createTrackerService')
            ->once()
            ->andReturn($trackerService);

        $job = new QueueJob($this->jobConfig, $this->service, $this->workerFactory, $this->trackerServiceFactory);
        $job->setParameters(['message' => $message]);
        $job->process();

        $this->assertTrue($job->isSuccessful());
        $this->assertGreaterThan(0, $job->getDuration());
        $this->assertEmpty($job->getErrors());
    }

    public function testFailsToInstantiateIfInvalidConfiguration()
    {
        $this->service->shouldReceive('getQueueWorkerConfig')
            ->andReturn(['type' => '']);
        $this->service->shouldReceive('getQueueConfig')
            ->once()
            ->andReturn([]);

        $this->expectException(ConfigurationException::class);
        $job = new QueueJob($this->jobConfig, $this->service, $this->workerFactory, $this->trackerServiceFactory);
        $job->setParameters(['message' => new Message('foo', 'bar')]);
    }
}
