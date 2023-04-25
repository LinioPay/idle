<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Service;

use LinioPay\Idle\Message\Messages\PublishSubscribe\Service;
use Throwable;

abstract class DefaultService implements Service
{
    public const IDENTIFIER = '';

    /** @var array */
    protected $config;

    public function getAcknowledgeErrorConfig() : array
    {
        return $this->config['acknowledge']['error'] ?? [];
    }

    public function getAcknowledgeParameterConfig() : array
    {
        return $this->config['acknowledge']['parameters'] ?? [];
    }

    public function getConfig() : array
    {
        return $this->config;
    }

    public function getPublishErrorConfig() : array
    {
        return $this->config['publish']['error'] ?? [];
    }

    public function getPublishParameterConfig() : array
    {
        return $this->config['publish']['parameters'] ?? [];
    }

    public function getPullErrorConfig() : array
    {
        return $this->config['pull']['error'] ?? [];
    }

    public function getPullParameterConfig() : array
    {
        return $this->config['pull']['parameters'] ?? [];
    }

    public function getServiceConfig() : array
    {
        return $this->config['parameters']['service'] ?? [];
    }

    protected function isAcknowledgeErrorSuppressed() : bool
    {
        $errorConfig = $this->getAcknowledgeErrorConfig();

        return $errorConfig['suppression'] ?? false;
    }

    protected function isPublishErrorSuppressed() : bool
    {
        $errorConfig = $this->getPublishErrorConfig();

        return $errorConfig['suppression'] ?? false;
    }

    protected function isPullErrorSuppressed() : bool
    {
        $errorConfig = $this->getPullErrorConfig();

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
