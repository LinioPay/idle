<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Mockery\Mock;

class FooJobTest extends TestCase
{
    /** @var IdleConfig */
    protected $config;

    /** @var Mock|WorkerFactory */
    protected $workerFactory;

    public function setUp() : void
    {
        parent::setUp();

        $this->workerFactory = m::mock(WorkerFactory::class);

        $this->config = new IdleConfig([], [], [
                FooJob::IDENTIFIER => [
                    'class' => FooJob::class,
                    'workers' => [
                        [
                            'type' => FooWorker::IDENTIFIER,
                            'parameters' => [],
                        ],
                    ],
                    'parameters' => [
                        'red' => true,
                    ],
                ],
        ]);
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

        $job = new FooJob($this->config, $this->workerFactory);

        $job->process();
        $job->validateParameters();

        $this->assertTrue($job->isSuccessful());
        $this->assertTrue($job->isFinished());

        $parameters = $job->getParameters();
        $this->assertArrayHasKey('red', $parameters);
    }
}
