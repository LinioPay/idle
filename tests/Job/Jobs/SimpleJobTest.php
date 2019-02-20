<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Exception\ConfigurationException;
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

        $job = new SimpleJob($this->config, $this->workerFactory);
        $job->setParameters(['worker_identifier' => FooWorker::IDENTIFIER, 'color' => 'red']);
        $job->process();

        $this->assertTrue($job->isSuccessful());
        $this->assertTrue($job->isFinished());
        $this->assertSame(['size' => 'large', 'worker_identifier' => 'foo', 'color' => 'red'], $worker->getParameters());
        $this->assertSame(array_merge($this->config[SimpleJob::IDENTIFIER]['parameters'], ['worker_identifier' => 'foo', 'color' => 'red']), $job->getParameters());
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
        $job = new SimpleJob($failConfig, $this->workerFactory);
        $job->setParameters(['worker_identifier' => FooWorker::IDENTIFIER, 'color' => 'red']);
    }

    public function testItThrowsConfigurationExceptionWhenJobConfigurationIsInvalid()
    {
        $failConfig = [
            SimpleJob::IDENTIFIER => [],
        ];

        $this->expectException(ConfigurationException::class);

        $job = new SimpleJob($failConfig, $this->workerFactory);
        $job->setParameters(['worker_identifier' => FooWorker::IDENTIFIER, 'color' => 'red']);
    }

    public function testItThrowsConfigurationExceptionWhenMissingWorkerIdentifier()
    {
        $failConfig = [
            SimpleJob::IDENTIFIER => [],
        ];

        $this->expectException(ConfigurationException::class);

        $job = new SimpleJob($failConfig, $this->workerFactory);
        $job->setParameters(['color' => 'red']);
    }
}
