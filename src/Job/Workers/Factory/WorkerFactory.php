<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Worker;
use LinioPay\Idle\Job\Workers\Factory\Worker as WorkerFactoryInterface;
use Psr\Container\ContainerInterface;

class WorkerFactory implements WorkerFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    public function createWorker(string $class) : Worker
    {
        return $this->container->get($class);
    }
}
