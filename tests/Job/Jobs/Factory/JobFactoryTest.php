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
            'idle' => [
                'job' => [
                    'types' => [
                        SimpleJob::IDENTIFIER => [
                            'class' => SimpleJob::class,
                            'parameters' => [
                                'supported' => [
                                    'my_simple_job' => [
                                        'workers' => [
                                            [
                                                'class' => FooWorker::class,
                                                'parameters' => [
                                                    'size' => 'large',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
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
        $mockJob->shouldReceive('validateConfig')
            ->once();
        $mockJob->shouldReceive('validateParameters')
            ->once();

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('config')
            ->andReturn($this->config);
        $container->shouldReceive('get')
            ->once()
            ->with(SimpleJob::class)
            ->andReturn($mockJob);

        $factory = new JobFactory();

        $factory($container);

        $job = $factory->createJob(SimpleJob::IDENTIFIER, $parameters);
        $this->assertInstanceOf(SimpleJob::class, $job);
    }

    public function testFailsToCreateJob()
    {
        $mockJob = m::mock(SimpleJob::class);
        $mockJob->shouldReceive('validateConfig')
            ->once()
            ->andThrow(new Exception('fooo'));

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('config')
            ->andReturn($this->config);
        $container->shouldReceive('get')
            ->once()
            ->with(SimpleJob::class)
            ->andReturn($mockJob);

        $factory = new JobFactory();

        $factory($container);

        $job = $factory->createJob(SimpleJob::IDENTIFIER, []);
        $this->assertInstanceOf(FailedJob::class, $job);
    }

    public function testFailsToFindJob()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with('config')
            ->andReturn($this->config);

        $factory = new JobFactory();

        $factory($container);

        $job = $factory->createJob('fakeIdentifier', []);
        $this->assertInstanceOf(FailedJob::class, $job);
    }
}
