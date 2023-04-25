<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Exception\InvalidJobParameterException;
use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;

class MessageJob extends DefaultJob
{
    public const IDENTIFIER = 'message';

    /** @var MessageInterface */
    protected $message;

    /** @var MessageFactoryInterface */
    protected $messageFactory;

    public function __construct(IdleConfig $idleConfig, MessageFactoryInterface $messageFactory, WorkerFactoryInterface $workerFactory)
    {
        $this->idleConfig = $idleConfig;
        $this->messageFactory = $messageFactory;
        $this->workerFactory = $workerFactory;
    }

    public function setParameters(array $parameters = []) : void
    {
        $this->message = $parameters['message'] = is_a($parameters['message'] ?? [], MessageInterface::class)
            ? $parameters['message']
            : $this->messageFactory->createMessage($parameters['message'] ?? []);

        $config = $this->getMessageJobSourceConfig();

        parent::setParameters(array_merge_recursive($config['parameters'] ?? [], $parameters));
    }

    public function validateParameters() : void
    {
        if (!is_a($this->message, MessageInterface::class)) {
            throw new InvalidJobParameterException($this, 'message');
        }
    }

    protected function buildWorker(string $workerIdentifier, array $workerParameters) : WorkerInterface
    {
        return $this->workerFactory->createWorker($workerIdentifier, array_merge(
            $workerParameters,
            ['job' => $this, 'message' => $this->message]
        ));
    }

    /**
     * Obtain the list of workers which will be responsible for processing the given Message.
     */
    protected function getJobWorkersConfig() : array
    {
        $config = $this->getMessageJobSourceConfig();

        return $config['parameters']['workers'] ?? [];
    }

    protected function getMessageJobSourceConfig() : array
    {
        $messageJobParameters = $this->idleConfig->getJobParametersConfig(self::IDENTIFIER);
        $messageTypeIdentifier = $this->message->getIdleIdentifier();
        $messageSourceIdentifier = $this->message->getSourceName();

        return $messageJobParameters[$messageTypeIdentifier][$messageSourceIdentifier] ?? [];
    }
}
