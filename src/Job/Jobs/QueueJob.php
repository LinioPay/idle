<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Queue\Message;
use LinioPay\Idle\Queue\Service;

class QueueJob extends DefaultJob
{
    /** @var Service */
    protected $service;

    /** @var Message */
    protected $message;

    public function __construct(Service $service, Message $message, WorkerFactory $workerFactory)
    {
        $this->service = $service;
        $this->message = $message;
        $this->workerFactory = $workerFactory;

        $this->buildQueueJobWorker();
    }

    protected function buildQueueJobWorker() : void
    {
        $workerConfig = $this->service->getQueueWorkerConfig($this->message->getQueueIdentifier());

        if (empty($workerConfig['type'])) {
            throw new \Exception(sprintf('Queue %s is missing a proper worker configuration.', $this->message->getQueueIdentifier()));
        }

        $this->buildWorker($workerConfig['type'], $workerConfig['parameters'] ?? []);
    }

    public function process() : void
    {
        $start = microtime(true);

        $this->successful = $this->worker->work();

        $this->duration = microtime(true) - $start;

        $this->removeFromQueue();
    }

    protected function removeFromQueue() : void
    {
        $queueConfig = $this->service->getQueueConfig($this->message->getQueueIdentifier());

        if ($this->successful && isset($queueConfig['delete']['enabled']) && $queueConfig['delete']['enabled']) {
            $this->service->delete($this->message);
        }
    }
}
