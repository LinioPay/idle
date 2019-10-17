<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Tracker\Service\Factory\ServiceFactory as TrackerServiceFactory;
use LinioPay\Idle\Job\Worker;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Mockery\Mock;

class SimpleJobTest extends TestCase
{
    /** @var array */
    protected $config;

    /** @var Mock|WorkerFactory workerFactory */
    protected $workerFactory;

    /** @var Mock|TrackerServiceFactory */
    protected $trackerServiceFactory;

    public function setUp() : void
    {
        parent::setUp();

        $this->config = [
            SimpleJob::IDENTIFIER => [
                'type' => SimpleJob::class,
                'parameters' => [
                    'supported' => [
                        FooWorker::IDENTIFIER => [
                            'type' => FooWorker::class,
                            'parameters' => [
                                'size' => 'large',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->workerFactory = m::mock(WorkerFactory::class);
        $this->trackerServiceFactory = m::mock(TrackerServiceFactory::class);
    }

    public function tearDown() : void
    {
        parent::tearDown();

        $this->workerFactory = null;
    }

    public function testProcessesWorker()
    {
        $worker = new FooWorker();

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $job = new SimpleJob($this->config, $this->workerFactory, $this->trackerServiceFactory);
        $job->setParameters(['worker_identifier' => FooWorker::IDENTIFIER, 'color' => 'red']);
        $job->process();

        $this->assertTrue($job->isSuccessful());
        $this->assertTrue($job->isFinished());
        $this->assertSame(['size' => 'large', 'worker_identifier' => 'foo', 'color' => 'red'], $worker->getParameters());
        $this->assertSame(array_merge($this->config[SimpleJob::IDENTIFIER]['parameters'], ['worker_identifier' => 'foo', 'color' => 'red']), $job->getParameters());
    }

    public function testRetrievesTrackerData()
    {
        $worker = new FooWorker();

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $job = new SimpleJob($this->config, $this->workerFactory, $this->trackerServiceFactory);
        $job->setParameters(['worker_identifier' => FooWorker::IDENTIFIER, 'color' => 'red']);
        $job->process();

        $trackerData = $job->getTrackerData();
        $this->assertArrayHasKey('id', $trackerData);
        $this->assertArrayHasKey('start', $trackerData);
        $this->assertArrayHasKey('duration', $trackerData);
        $this->assertArrayHasKey('successful', $trackerData);
        $this->assertArrayHasKey('finished', $trackerData);
        $this->assertArrayHasKey('errors', $trackerData);
        $this->assertArrayHasKey('parameters', $trackerData);

        $this->assertIsString($trackerData['id']);
        $this->assertIsString($trackerData['start']);
        $this->assertIsFloat($trackerData['duration']);
        $this->assertTrue($trackerData['successful']);
        $this->assertTrue($trackerData['finished']);
        $this->assertIsString($trackerData['errors']);
        $this->assertIsString($trackerData['parameters']);
    }

    public function testProcessAddsExceptionsToJobErrors()
    {
        $worker = m::mock(Worker::class);
        $worker->shouldReceive('work')
            ->andThrow(new ConfigurationException('foo'));
        $worker->shouldReceive('setParameters')
            ->once();
        $worker->shouldReceive('getErrors')
            ->once()
            ->andReturn([]);

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $job = new SimpleJob($this->config, $this->workerFactory, $this->trackerServiceFactory);
        $job->setParameters(['worker_identifier' => FooWorker::IDENTIFIER, 'color' => 'red']);

        try {
            $job->process();
        } catch (ConfigurationException $e) {
            $errors = $job->getErrors();
            $this->assertArrayHasKey(0, $errors);
            $this->assertSame('Encountered an error: Job foo is missing a proper configuration.', $errors[0]);
        }
    }

    public function testCanGetIdentifier()
    {
        $job = new SimpleJob($this->config, $this->workerFactory, $this->trackerServiceFactory);

        $this->assertSame('simple', $job->getTypeIdentifier());
    }

    public function testItThrowsConfigurationExceptionWhenWorkerConfigurationIsInvalid()
    {
        $failConfig = [
            SimpleJob::IDENTIFIER => [
                'type' => SimpleJob::class,
                'parameters' => [
                    'supported' => [
                        FooWorker::IDENTIFIER => [],
                    ],
                ],
            ],
        ];

        $this->expectException(ConfigurationException::class);
        $job = new SimpleJob($failConfig, $this->workerFactory, $this->trackerServiceFactory);
        $job->setParameters(['worker_identifier' => FooWorker::IDENTIFIER, 'color' => 'red']);
    }

    public function testItThrowsConfigurationExceptionWhenJobConfigurationIsInvalid()
    {
        $failConfig = [
            SimpleJob::IDENTIFIER => [],
        ];

        $this->expectException(ConfigurationException::class);

        $job = new SimpleJob($failConfig, $this->workerFactory, $this->trackerServiceFactory);
        $job->setParameters(['worker_identifier' => FooWorker::IDENTIFIER, 'color' => 'red']);
    }

    public function testItThrowsConfigurationExceptionWhenMissingWorkerIdentifier()
    {
        $failConfig = [
            SimpleJob::IDENTIFIER => [],
        ];

        $this->expectException(ConfigurationException::class);

        $job = new SimpleJob($failConfig, $this->workerFactory, $this->trackerServiceFactory);
        $job->setParameters(['color' => 'red']);
    }
}
