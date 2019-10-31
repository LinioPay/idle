<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;

class WorkerFactory extends DefaultWorkerFactory
{
    public function createWorker(string $workerIdentifier, array $parameters = []) : WorkerInterface
    {
        $mergedConfig = $this->getMergedWorkerConfig($workerIdentifier, $parameters);

        $class = $mergedConfig['class'];
        $mergedParameters = $mergedConfig['parameters'];

        if (property_exists($class, 'skipFactory') && $class::$skipFactory) {
            /** @var WorkerInterface $worker */
            $worker = new $class();
        } else {
            /** @var WorkerFactoryInterface $factory */
            $factory = $this->container->get($class);

            $worker = $factory->createWorker($class, $mergedParameters);
        }

        $worker->setParameters($mergedParameters);
        $worker->validateParameters();

        return $worker;
    }
}
