<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use Exception;
use LinioPay\Idle\Job\Jobs\FailedJob;
use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class JobFactoryTest extends TestCase
{
    protected $config;

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
    }

    public function testCreatesJobSuccessfully()
    {
        $parameters = ['foo' => 'bar'];

        $mockJob = m::mock(SimpleJob::class);
        $mockJob->shouldReceive('setParameters')
            ->once()
            ->with($parameters);

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('job-config')
            ->andReturn($this->config);
        $container->shouldReceive('get')
            ->once()
            ->with(SimpleJob::class)
            ->andReturn($mockJob);

        $factory = new JobFactory();

        $factory($container);

        $job = $factory->createJob(SimpleJob::class, $parameters);
        $this->assertInstanceOf(SimpleJob::class, $job);
    }

    public function testFailsToCreateJob()
    {
        $mockJob = m::mock(SimpleJob::class);
        $mockJob->shouldReceive('setParameters')
            ->once()
            ->andThrow(new Exception('fooo'));

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('job-config')
            ->andReturn($this->config);
        $container->shouldReceive('get')
            ->once()
            ->with(SimpleJob::class)
            ->andReturn($mockJob);

        $factory = new JobFactory();

        $factory($container);

        $job = $factory->createJob(SimpleJob::class, []);
        $this->assertInstanceOf(FailedJob::class, $job);
    }
}
