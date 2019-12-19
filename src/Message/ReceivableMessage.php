<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

use LinioPay\Idle\Message\Exception\FailedReceivingMessageException;
use LinioPay\Idle\Message\Message as MessageInterface;

interface ReceivableMessage extends Message
{
    /**
     * Receive message from its corresponding service.  Alias for dequeue, pull, etc.
     */
    public function receive(array $parameters = []) : array;

    /**
     * @throws FailedReceivingMessageException
     */
    public function receiveOneOrFail(array $parameters = []) : MessageInterface;
}
