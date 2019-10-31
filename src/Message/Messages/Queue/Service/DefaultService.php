<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service;

use LinioPay\Idle\Message\Messages\Queue\Service as QueueServiceInterface;
use Throwable;

abstract class DefaultService implements QueueServiceInterface
{
    const IDENTIFIER = '';

    /** @var array */
    protected $config;

    public function getConfig() : array
    {
        return $this->config;
    }

    protected function getQueueingParameters() : array
    {
        return $this->config['queue']['parameters'] ?? [];
    }

    protected function getQueueingErrorConfig() : array
    {
        return $this->config['queue']['error'] ?? [];
    }

    protected function isQueueingErrorSuppression() : bool
    {
        $errorConfig = $this->getQueueingErrorConfig();

        return $errorConfig['suppression'] ?? false;
    }

    protected function getDequeueingParameters() : array
    {
        return $this->config['dequeue']['parameters'] ?? [];
    }

    protected function getDequeueingErrorConfig() : array
    {
        return $this->config['dequeue']['error'] ?? [];
    }

    protected function isDequeueingErrorSuppression() : bool
    {
        $errorConfig = $this->getDequeueingErrorConfig();

        return $errorConfig['suppression'] ?? false;
    }

    protected function getDeletingParameters() : array
    {
        return $this->config['delete']['parameters'] ?? [];
    }

    protected function getDeletingErrorConfig() : array
    {
        return $this->config['delete']['error'] ?? [];
    }

    protected function isDeletingErrorSuppression() : bool
    {
        $errorConfig = $this->getDeletingErrorConfig();

        return $errorConfig['suppression'] ?? false;
    }

    protected function throwableToArray(Throwable $throwable)
    {
        return [
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ];
    }
}
