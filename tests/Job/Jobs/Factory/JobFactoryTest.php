<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Config\IdleConfig;
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

        $this->config = new IdleConfig([], [],
            [
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
            ]);
    }

    public function testCreatesJobSuccessfully()
    {
        $parameters = ['foo' => 'bar'];

        $mockJob = m::mock(SimpleJob::class);
        $mockJob->shouldReceive('setParameters')
            ->once()
            ->with($parameters);
        $mockJob->shouldReceive('validateParameters')
            ->once();

        $mockJobFactory = m::mock(SimpleJobFactory::class);
        $mockJobFactory->shouldReceive('createJob')
            ->once()
            ->withArgs([SimpleJob::IDENTIFIER, $parameters])
            ->andReturn($mockJob);

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn($this->config);
        $container->shouldReceive('get')
            ->once()
            ->with(SimpleJob::class)
            ->andReturn($mockJobFactory);

        $factory = new JobFactory($container);

        $job = $factory->createJob(SimpleJob::IDENTIFIER, $parameters);
        $this->assertInstanceOf(SimpleJob::class, $job);
    }
}
