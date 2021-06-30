<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use function json_decode;
use LinioPay\Idle\Config\Exception\ConfigurationException;
use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Exception\InvalidJobParameterException;
use LinioPay\Idle\Job\Job;
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

    /** @var IdleConfig */
    protected $idleConfig;

    /** @var Mock|WorkerFactory */
    protected $workerFactory;

    public function setUp() : void
    {
        parent::setUp();

        $this->workerFactory = m::mock(WorkerFactory::class);

        $this->idleConfig = new IdleConfig(
            [],
            [],
            [
                SimpleJob::IDENTIFIER => [
                    'class' => SimpleJob::class,
                    'parameters' => [
                        'supported' => [
                            'foo_job' => [
                                'parameters' => [
                                    'workers' => [
                                        [
                                            'type' => FooWorker::IDENTIFIER,
                                            'parameters' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
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
    }

    public function testProcessesWorker()
    {
        $worker = new FooWorker();

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $job = new SimpleJob($this->idleConfig, $this->workerFactory);
        $job->setParameters(['simple_identifier' => 'foo_job']);

        $job->process();
        $job->validateParameters();

        $this->assertTrue($job->isSuccessful());
        $this->assertTrue($job->isFinished());

        $parameters = $job->getParameters();
        $this->assertArrayHasKey('simple_identifier', $parameters);
    }

    public function testGetsTrackerData()
    {
        $worker = new FooWorker();
        $worker = m::mock($worker);
        $worker->shouldReceive('getTrackerData')
            ->once()
            ->andReturn(['foo_worker' => 'bar_worker']);

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $job = new SimpleJob($this->idleConfig, $this->workerFactory);
        $job->setParameters(['simple_identifier' => 'foo_job']);
        $job->process();

        $data = $job->getTrackerData();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('start', $data);
        $this->assertArrayHasKey('duration', $data);
        $this->assertArrayHasKey('successful', $data);
        $this->assertArrayHasKey('finished', $data);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('parameters', $data);
        $this->assertArrayHasKey('foo_worker', $data);

        $this->assertSame($data['id'], $job->getJobId()->toString());
        $this->assertSame($data['start'], $job->getStartDate()->format('Y-m-d H:i:s'));
        $this->assertSame($data['duration'], $job->getDuration());
        $this->assertSame($data['successful'], $job->isSuccessful());
        $this->assertSame($data['finished'], $job->isFinished());
        $this->assertSame(json_decode($data['parameters'], true), $job->getParameters());

        $this->assertSame('bar_worker', $data['foo_worker']);
    }

    public function testProcessAddsExceptionsToJobErrors()
    {
        $worker = m::mock(Worker::class);
        $worker->shouldReceive('work')
            ->andThrow(new ConfigurationException(ConfigurationException::ENTITY_WORKER, 'foo', 'class'));
        $worker->shouldReceive('getErrors')
            ->once()
            ->andReturn([]);

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $job = new SimpleJob($this->idleConfig, $this->workerFactory);
        $job->setParameters(['simple_identifier' => 'foo_job']);

        try {
            $job->process();
        } catch (ConfigurationException $e) {
            $errors = $job->getErrors();
            $this->assertArrayHasKey(0, $errors);
            $this->assertSame('Encountered an error: Worker foo is missing a proper class configuration.', $errors[0]);
        }
    }

    public function testJobCanStillBeSuccessfulIfWorkerHasErrors()
    {
        /** @var FooWorker $worker */
        $worker = $this->fake(FooWorker::class, [
            'errors' => ['error'],
        ]);

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $job = new SimpleJob($this->idleConfig, $this->workerFactory);
        $job->setParameters(['simple_identifier' => 'foo_job']);

        $job->process();
        $job->validateParameters();

        $this->assertTrue($job->isFinished());
        $this->assertTrue($job->isSuccessful());
        $this->assertNotEquals(0, count($job->getErrors()));
    }

    public function testJobUnsuccessfulOnlyIfWorkerReturnsFalse()
    {
        /** @var FooWorker|Mock $worker */
        $worker = m::mock(Worker::class);
        $worker->shouldReceive('work')
            ->andReturn(false);
        $worker->shouldReceive('getErrors')
            ->andReturn([]);

        $this->workerFactory->shouldReceive('createWorker')
            ->andReturn($worker);

        $job = new SimpleJob($this->idleConfig, $this->workerFactory);
        $job->setParameters(['simple_identifier' => 'foo_job']);

        $job->process();
        $job->validateParameters();

        $this->assertTrue($job->isFinished());
        $this->assertFalse($job->isSuccessful());
    }

    public function testCanGetIdentifier()
    {
        $job = new SimpleJob($this->idleConfig, $this->workerFactory);

        $this->assertSame('simple', $job->getTypeIdentifier());
    }

    public function testCanGetAndSetWorkerContext()
    {
        $job = new SimpleJob($this->idleConfig, $this->workerFactory);

        $job->setContext(['foo' => 'bar']);
        $this->assertSame('bar', $job->getContextEntry('foo'));

        $job->addContext('baz', 'foo');
        $this->assertSame('foo', $job->getContextEntry('baz'));
    }

    public function testCanGetAndSetOutput()
    {
        $job = new SimpleJob($this->idleConfig, $this->workerFactory);

        $job->setOutput(['foo' => 'bar']);
        $job->addOutput('baz', 'foo');

        $output = $job->getOutput();

        $this->assertIsArray($output);

        $this->assertArrayHasKey('foo', $output);
        $this->assertArrayHasKey('baz', $output);
    }

    public function testThrowsInvalidParameterExceptionWhenNoSimpleIdentifierProvided()
    {
        /** @var Job $job */
        $job = $this->fake(SimpleJob::class, ['idleConfig' => new IdleConfig(
            [],
            [],
            [
                SimpleJob::IDENTIFIER => [
                    'class' => SimpleJob::class,
                ],
            ]
        )]);
        $this->expectException(InvalidJobParameterException::class);
        $job->validateParameters();
    }
}
