<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue;

use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Service as ServiceInterface;

interface Service extends ServiceInterface
{
    public function queue(Message $message, array $parameters = []) : bool;

    /**
     * @return MessageInterface[]
     */
    public function dequeue(string $queueIdentifier, array $parameters = []) : array;

    public function delete(Message $message, array $parameters = []) : bool;

    public function getConfig() : array;
}
