<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue;

use LinioPay\Idle\Message\Exception\FailedReceivingMessageException;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Service as ServiceInterface;

interface Service extends ServiceInterface
{
    public function queue(Message $message, array $parameters = []) : bool;

    /**
     * @return MessageInterface[]
     */
    public function dequeue(string $queueIdentifier, array $parameters = []) : array;

    /**
     * @throws FailedReceivingMessageException
     */
    public function dequeueOneOrFail(string $queueIdentifier, array $parameters = []) : MessageInterface;

    public function delete(Message $message, array $parameters = []) : bool;

    public function getConfig() : array;
}
