<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Worker;
use Psr\Container\ContainerInterface;

class WorkerFactory
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
