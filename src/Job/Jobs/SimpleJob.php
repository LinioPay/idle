<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use Zend\Stdlib\ArrayUtils;

class SimpleJob extends DefaultJob
{
    const IDENTIFIER = 'simple';

    /** @var string */
    protected $workerIdentifier;

    public function __construct(array $config, string $workerIdentifier, WorkerFactory $workerFactory, array $parameters = [])
    {
        $this->config = $config;
        $this->workerFactory = $workerFactory;
        $this->workerIdentifier = $workerIdentifier;

        $this->prepareJob($parameters);
        $this->prepareWorker($parameters);
    }

    protected function prepareJob(array $parameters) : void
    {
        $this->validate();
        $this->setParameters(ArrayUtils::merge(
            $this->getConfigParameters(),
            $parameters
        ));
    }

    protected function prepareWorker(array $parameters) : void
    {
        $this->validateWorkerSupport();
        $workerConfig = $this->getParameters()['supported'][$this->workerIdentifier];

        $this->buildWorker($workerConfig['type'], ArrayUtils::merge(
            $workerConfig['parameters'] ?? [],
            $parameters
        ));
    }

    protected function validateWorkerSupport() : void
    {
        $parameters = $this->getParameters();

        if (empty($parameters['supported'][$this->workerIdentifier]['type'])) {
            throw new ConfigurationException(self::IDENTIFIER);
        }
    }
}
