<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Queue\Exception\ConfigurationException;
use LinioPay\Idle\Queue\Message;
use LinioPay\Idle\Queue\Service;

class QueueJob extends DefaultJob
{
    const IDENTIFIER = 'queue';

    /** @var Service */
    protected $service;

    /** @var Message */
    protected $message;

    public function __construct(array $config, Service $service, WorkerFactory $workerFactory)
    {
        $this->config = $config;
        $this->service = $service;
        $this->workerFactory = $workerFactory;
    }

    public function process() : void
    {
        parent::process();

        $this->removeFromQueue();
    }

    protected function removeFromQueue() : void
    {
        $queueConfig = $this->service->getQueueConfig($this->message->getQueueIdentifier());

        if ($this->successful && $queueConfig['delete']['enabled'] ?? false) {
            $this->service->delete($this->message);
        }
    }

    public function setParameters(array $parameters = []) : void
    {
        $this->message = $parameters['message'] = is_a($parameters['message'], Message::class)
            ? $parameters['message']
            : Message::fromArray($parameters['message'] ?? []);

        parent::setParameters($parameters);

        $this->buildQueueJobWorker();
    }

    protected function buildQueueJobWorker() : void
    {
        $workerConfig = $this->service->getQueueWorkerConfig($this->message->getQueueIdentifier());

        if (empty($workerConfig['type'])) {
            throw new ConfigurationException($this->message->getQueueIdentifier(), ConfigurationException::TYPE_WORKER);
        }

        $this->buildWorker($workerConfig['type'], $workerConfig['parameters'] ?? []);
    }
}
