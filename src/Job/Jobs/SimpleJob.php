<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Exception\InvalidJobParameterException;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;

class SimpleJob extends DefaultJob
{
    const IDENTIFIER = 'simple';

    /** @var string */
    protected $simpleIdentifier;

    public function __construct(array $config, WorkerFactoryInterface $workerFactory)
    {
        $this->config = $config;
        $this->workerFactory = $workerFactory;
    }

    public function setParameters(array $parameters = []) : void
    {
        $this->simpleIdentifier = $parameters['simple_identifier'] ?? '';

        $config = $this->getSimpleJobConfig();

        parent::setParameters(array_merge_recursive($config['parameters'] ?? [], $parameters));
    }

    public function validateParameters() : void
    {
        $config = $this->getConfigParameters();

        if (empty($this->simpleIdentifier) || !isset($config['supported'][$this->simpleIdentifier])) {
            throw new InvalidJobParameterException($this, 'simple_identifier');
        }
    }

    protected function getSimpleJobConfig() : array
    {
        $config = $this->getConfigParameters();

        return $config['supported'][$this->simpleIdentifier] ?? [];
    }

    protected function getSimpleJobConfigWorkers() : array
    {
        $config = $this->getSimpleJobConfig();

        return $config['parameters']['workers'] ?? [];
    }

    protected function getWorkersConfig() : array
    {
        return $this->getSimpleJobConfigWorkers();
    }
}
