<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Workers\DefaultWorker;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Queue\Message;
use LinioPay\Idle\Queue\Service;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Mockery\Mock;

class QueueJobTest extends TestCase
{
    /** @var Mock|Service */
    protected $service;

    /** @var Mock|WorkerFactory */
    protected $workerFactory;

    public function setUp()
    {
        parent::setUp();

        /* @var Mock|Service $service */
        $this->service = m::mock(Service::class);

        $this->workerFactory = m::mock(WorkerFactory::class);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->service = null;
        $this->workerFactory = null;
    }

    public function testItCanProcessJobSuccessfully()
    {
        $this->service->shouldReceive('getQueueWorkerConfig')
            ->andReturn(['type' => DefaultWorker::class]);
        $this->service->shouldReceive('getQueueConfig')
            ->once()
            ->andReturn(['delete' => ['enabled' => true]]);
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

        $job = new QueueJob($this->service, new Message('foo', 'bar'), $this->workerFactory);
        $job->process();

        $this->assertTrue($job->isSuccessful());
        $this->assertGreaterThan(0, $job->getDuration());
        $this->assertEmpty($job->getErrors());
    }

    public function testFailsToInstantiateIfInvalidConfiguration()
    {
        $this->service->shouldReceive('getQueueWorkerConfig')
            ->andReturn(['type' => '']);

        $this->expectException(\Exception::class);
        (new QueueJob($this->service, new Message('foo', 'bar'), $this->workerFactory));
    }
}
