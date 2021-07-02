<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;
use Psr\Container\ContainerInterface;

abstract class DefaultWorkerFactory implements WorkerFactoryInterface
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

    abstract public function createWorker(string $workerIdentifier, array $parameters = []) : WorkerInterface;
}
