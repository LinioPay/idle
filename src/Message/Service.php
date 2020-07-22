<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

interface Service
{
    /**
     * Retrieve the derived service configuration (including message overrides).
     */
    public function getConfig() : array;

    /**
     * Retrieve the global service configuration.
     */
    public function getServiceConfig() : array;
}
