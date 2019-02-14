<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Worker;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;

abstract class DefaultJob implements Job
{
    /** @var WorkerFactory */
    protected $workerFactory;

    /** @var Worker */
    protected $worker;

    /** @var bool */
    protected $successful = false;

    /** @var float */
    protected $duration = 0.0;

    public function isSuccessful() : bool
    {
        return $this->successful;
    }

    public function getDuration() : float
    {
        return $this->duration;
    }

    public function getErrors() : array
    {
        return $this->worker->getErrors();
    }

    protected function buildWorker(string $workerClass, array $workerParameters) : void
    {
        $this->worker = $this->workerFactory->createWorker($workerClass);
        $this->worker->setParameters($workerParameters);
    }
}
