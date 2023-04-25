<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Exception\InvalidJobParameterException;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;

class SimpleJob extends DefaultJob
{
    public const IDENTIFIER = 'simple';

    /** @var string */
    protected $simpleIdentifier;

    public function __construct(IdleConfig $idleConfig, WorkerFactoryInterface $workerFactory)
    {
        $this->idleConfig = $idleConfig;
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
        $jobConfigParameters = $this->idleConfig->getJobParametersConfig(self::IDENTIFIER);

        if (empty($this->simpleIdentifier) || !isset($jobConfigParameters['supported'][$this->simpleIdentifier])) {
            throw new InvalidJobParameterException($this, 'simple_identifier');
        }
    }

    protected function getJobWorkersConfig() : array
    {
        $config = $this->getSimpleJobConfig();

        return $config['parameters']['workers'] ?? [];
    }

    protected function getSimpleJobConfig() : array
    {
        $jobConfigParameters = $this->idleConfig->getJobParametersConfig(self::IDENTIFIER);

        return $jobConfigParameters['supported'][$this->simpleIdentifier] ?? [];
    }
}
