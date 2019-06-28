<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Tracker\Service\Factory;

use LinioPay\Idle\Job\Tracker\Service;
use LinioPay\Idle\Job\Tracker\Service\Factory\Service as ServiceFactoryInterface;
use Psr\Container\ContainerInterface;

class ServiceFactory implements ServiceFactoryInterface
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

    public function createTrackerService(string $class) : Service
    {
        return $this->container->get($class);
    }
}
