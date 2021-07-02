<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;

class WorkerFactory extends DefaultWorkerFactory
{
    public function createWorker(string $workerIdentifier, array $parameters = []) : WorkerInterface
    {
        $mergedConfig = $this->idleConfig->getMergedWorkerConfig($workerIdentifier, $parameters);

        $workerClass = $mergedConfig['class'];
        $mergedParameters = $mergedConfig['parameters'];

        if (property_exists($workerClass, 'skipFactory') && $workerClass::$skipFactory) {
            /** @var WorkerInterface $worker */
            $worker = new $workerClass();
        } else {
            /** @var WorkerFactoryInterface $factory */
            $factory = $this->container->get($workerClass);

            $worker = $factory->createWorker($workerClass, $mergedParameters);
        }

        $worker->setParameters($mergedParameters);
        $worker->validateParameters();

        return $worker;
    }
}
