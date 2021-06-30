<?php

namespace LinioPay\Idle\Config;

use Laminas\Stdlib\ArrayUtils;
use LinioPay\Idle\Config\Exception\ConfigurationException;
use LinioPay\Idle\Message\Message;

class IdleConfig
{
    protected array $services = [];

    protected array $messages = [];

    protected array $jobs = [];

    protected array $workers = [];

    public function __construct(array $serviceConfig = [], array $messageConfig = [], array $jobConfig = [], array $workerConfig = [])
    {
        $this->services = $serviceConfig;
        $this->messages = $messageConfig;
        $this->jobs = $jobConfig;
        $this->workers = $workerConfig;
    }

    public function getServicesConfig() : array
    {
        return $this->services;
    }

    public function getServiceClass(string $identifier) : string
    {
        $this->validateServiceConfig($identifier);
        $config = $this->getServiceConfig($identifier);

        return $config['class'];
    }

    protected function validateServiceConfig(string $identifier) : void
    {
        $servicesConfig = $this->getServicesConfig();

        if (empty($servicesConfig[$identifier]['class'] ?? '')) {
            throw new ConfigurationException(ConfigurationException::ENTITY_SERVICE, $identifier, 'class');
        }
    }

    public function getServiceConfig(string $identifier) : array
    {
        $this->validateServiceConfig($identifier);

        $servicesConfig = $this->getServicesConfig();

        return $servicesConfig[$identifier] ?? [];
    }

    public function getMessagesConfig() : array
    {
        return $this->messages;
    }

    public function getMessageTypeConfig(string $identifier) : array
    {
        $this->validateMessageTypeExists($identifier);

        $messagesConfig = $this->getMessagesConfig();

        return $messagesConfig[$identifier] ?? [];
    }

    protected function validateMessageTypeExists(string $identifier) : void
    {
        $messagesConfig = $this->getMessagesConfig();

        if (!isset($messagesConfig[$identifier])) {
            throw new ConfigurationException(ConfigurationException::ENTITY_MESSAGE, $identifier, 'type');
        }
    }

    public function getMessageConfig(Message $message) : array
    {
        $messageTypeIdentifier = $message->getIdleIdentifier();
        $messageSource = $message->getSourceName();

        $this->validateMessageTypeAndSourceExist($messageTypeIdentifier, $messageSource);

        $messageTypeConfig = $this->getMessageTypeConfig($messageTypeIdentifier);

        $default = $messageTypeConfig['default'] ?? [];

        $override = $messageTypeConfig['types'][$messageSource] ?? [];

        $messageServiceIdentifier = isset($override['parameters']['service'])
            ? $override['parameters']['service']
            : $default['parameters']['service'] ?? '';

        $serviceDefault = $messageTypeConfig['service_default'][$messageServiceIdentifier] ?? [];

        $mergedDefaultConfig = ArrayUtils::merge($default, $serviceDefault);
        $mergedConfig = ArrayUtils::merge($mergedDefaultConfig, $override);

        // Inject service config based on the specified service identifier
        $mergedConfig['parameters']['service'] = $this->getServiceConfig($messageServiceIdentifier);

        return $mergedConfig;
    }

    protected function validateMessageTypeAndSourceExist(string $messageTypeIdentifier, string $messageSource) : void
    {
        $messagesConfig = $this->getMessagesConfig();

        if (!isset($messagesConfig[$messageTypeIdentifier]['types'][$messageSource])) {
            throw new ConfigurationException(ConfigurationException::ENTITY_MESSAGE, $messageTypeIdentifier, $messageSource);
        }
    }

    public function getJobsConfig() : array
    {
        return $this->jobs;
    }

    public function getJobClass(string $identifier) : string
    {
        $this->validateJobConfig($identifier);

        $config = $this->getJobConfig($identifier);

        return $config['class'];
    }

    protected function validateJobConfig(string $identifier)
    {
        $jobsConfig = $this->getJobsConfig();

        if (empty($jobsConfig[$identifier]['class'] ?? '')) {
            throw new ConfigurationException(ConfigurationException::ENTITY_JOB, $identifier, 'class');
        }
    }

    public function getJobConfig(string $identifier) : array
    {
        $this->validateJobConfig($identifier);

        $jobsConfig = $this->getJobsConfig();

        return $jobsConfig[$identifier] ?? [];
    }

    public function getJobParametersConfig(string $identifier) : array
    {
        $config = $this->getJobConfig($identifier);

        return $config['parameters'] ?? [];
    }

    public function getWorkersConfig() : array
    {
        return $this->workers;
    }

    public function getWorkerConfig(string $identifier) : array
    {
        $this->validateWorkerConfig($identifier);

        $workersConfig = $this->getWorkersConfig();

        return $workersConfig[$identifier] ?? [];
    }

    public function getMergedWorkerConfig(string $identifier, array $parameters = []) : array
    {
        return ArrayUtils::merge(
            $this->getWorkerConfig($identifier),
            ['parameters' => $parameters]
        );
    }

    public function getWorkerClass(string $identifier) : string
    {
        $workerConfig = $this->getWorkerConfig($identifier);

        return $workerConfig['class'];
    }

    protected function validateWorkerConfig(string $identifier) : void
    {
        $workersConfig = $this->getWorkersConfig();

        if (empty($workersConfig[$identifier]['class'] ?? '')) {
            throw new ConfigurationException(ConfigurationException::ENTITY_WORKER, $identifier, 'class');
        }
    }
}
