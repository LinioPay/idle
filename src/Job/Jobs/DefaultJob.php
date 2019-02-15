<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Worker;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;

abstract class DefaultJob implements Job
{
    const IDENTIFIER = '';

    /** @var bool */
    protected $successful = false;

    /** @var float */
    protected $duration = 0.0;

    /** @var array */
    protected $config = [];

    /** @var array */
    protected $parameters;

    /** @var Worker */
    protected $worker;

    /** @var WorkerFactory */
    protected $workerFactory;

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

    public function process() : void
    {
        $start = microtime(true);

        $this->successful = $this->worker->work();

        $this->duration = microtime(true) - $start;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters) : void
    {
        $this->parameters = $parameters;
    }

    protected function buildWorker(string $workerClass, array $workerParameters) : void
    {
        $this->worker = $this->workerFactory->createWorker($workerClass);
        $this->worker->setParameters($workerParameters);
    }

    public function getConfig() : array
    {
        $this->validate();

        return $this->config[static::IDENTIFIER];
    }

    public function getConfigParameters() : array
    {
        $config = $this->getConfig();

        return $config['parameters'] ?? [];
    }

    protected function validate() : void
    {
        if (empty(static::IDENTIFIER) || empty($this->config[static::IDENTIFIER]['type'])) {
            throw new ConfigurationException(static::IDENTIFIER);
        }
    }
}
