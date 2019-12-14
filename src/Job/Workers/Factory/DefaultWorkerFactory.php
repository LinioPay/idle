<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;
use Psr\Container\ContainerInterface;
use Zend\Stdlib\ArrayUtils;

abstract class DefaultWorkerFactory implements WorkerFactoryInterface
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

    abstract public function createWorker(string $workerIdentifier, array $parameters = []) : WorkerInterface;

    protected function getMergedWorkerConfig(string $workerIdentifier, array $parameters)
    {
        $config = $this->container->get('config') ?? [];

        $workerConfig = $config['idle']['job']['worker']['types'][$workerIdentifier] ?? [];

        $workerConfig['parameters'] = ArrayUtils::merge($workerConfig['parameters'] ?? [], $parameters);

        $workerConfig['class'] = $this->getWorkerClass($workerIdentifier);

        return $workerConfig;
    }

    protected function getWorkerClass(string $workerIdentifier) : string
    {
        $config = $this->container->get('config') ?? [];
        $class = $config['idle']['job']['worker']['types'][$workerIdentifier]['class'] ?? '';

        if (empty($class)) {
            throw new ConfigurationException($workerIdentifier);
        }

        return $class;
    }
}
