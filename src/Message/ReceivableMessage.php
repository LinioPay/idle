<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

interface ReceivableMessage extends Message
{
    /**
     * Receive message from its corresponding service.  Alias for dequeue, pull, etc.
     */
    public function receive(array $parameters = []) : array;
}
