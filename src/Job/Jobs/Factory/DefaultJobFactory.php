<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\JobFactory as JobFactoryInterface;
use Psr\Container\ContainerInterface;

abstract class DefaultJobFactory implements JobFactoryInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var IdleConfig */
    protected $idleConfig;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->loadIdleConfig();
    }

    protected function loadIdleConfig() : void
    {
        $this->idleConfig = $this->container->get(IdleConfig::class);
    }

    abstract public function createJob(string $jobIdentifier, array $parameters) : Job;
}
