<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue;

use LinioPay\Idle\Message\Exception\FailedReceivingMessageException;
use LinioPay\Idle\Message\Message as MessageInterface;

interface Message extends MessageInterface
{
    const IDENTIFIER = 'queue';

    public function getQueueIdentifier() : string;

    /**
     * Proxies a queue call to the service to queue the message.
     */
    public function queue(array $parameters = []) : bool;

    /**
     * Proxies a dequeue call to the service to retreive message(s).
     *
     * @return MessageInterface[]
     */
    public function dequeue(array $parameters = []) : array;

    /**
     * Proxies a dequeueOneOrFail call to the service to retrieve a message.
     *
     * @throws FailedReceivingMessageException
     */
    public function dequeueOneOrFail(array $parameters = []) : MessageInterface;

    /**
     * Proxies a delete call to the service.
     */
    public function delete(array $parameters = []) : bool;
}
