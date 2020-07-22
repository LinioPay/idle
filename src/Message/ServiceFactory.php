<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

interface ServiceFactory
{
    /**
     * Create a Service corresponding to the given Message.
     */
    public function createFromMessage(Message $message) : Service;
}
