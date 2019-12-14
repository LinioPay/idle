<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Exception\InvalidJobParameterException;
use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory as WorkerFactoryInterface;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;

class MessageJob extends DefaultJob
{
    const IDENTIFIER = 'message';

    /** @var MessageInterface */
    protected $message;

    /** @var MessageFactoryInterface */
    protected $messageFactory;

    public function __construct(array $config, MessageFactoryInterface $messageFactory, WorkerFactoryInterface $workerFactory)
    {
        $this->config = $config;
        $this->messageFactory = $messageFactory;
        $this->workerFactory = $workerFactory;
    }

    public function setParameters(array $parameters = []) : void
    {
        $this->message = $parameters['message'] = is_a($parameters['message'] ?? [], MessageInterface::class)
            ? $parameters['message']
            : $this->messageFactory->createMessage($parameters['message'] ?? []);

        $config = $this->getMessageJobConfig();

        parent::setParameters(array_merge_recursive($config['parameters'] ?? [], $parameters));
    }

    public function validateParameters() : void
    {
        if (!is_a($this->message, MessageInterface::class)) {
            throw new InvalidJobParameterException($this, 'message');
        }

        $parameters = $this->getConfigParameters();

        if (!isset($parameters[$this->message->getIdleIdentifier()][$this->message->getSourceName()])) {
            throw new ConfigurationException(self::IDENTIFIER);
        }
    }

    protected function getMessageJobConfig() : array
    {
        $config = $this->getConfigParameters();

        return $config[$this->message->getIdleIdentifier()][$this->message->getSourceName()] ?? [];
    }

    protected function getMessageJobConfigWorkers() : array
    {
        $config = $this->getMessageJobConfig();

        return $config['parameters']['workers'] ?? [];
    }

    protected function getWorkersConfig() : array
    {
        return $this->getMessageJobConfigWorkers();
    }

    protected function buildWorker(string $workerIdentifier, array $workerParameters) : WorkerInterface
    {
        return $this->workerFactory->createWorker($workerIdentifier, array_merge(
            $workerParameters,
            ['job' => $this, 'message' => $this->message]
        ));
    }
}
