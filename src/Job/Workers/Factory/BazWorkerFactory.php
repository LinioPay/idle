<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Factory;

use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\Workers\BazWorker;

class BazWorkerFactory extends DefaultWorkerFactory
{
    public function createWorker(string $workerIdentifier, array $parameters = []) : WorkerInterface
    {
        return new BazWorker('baz_dependency');
    }
}
