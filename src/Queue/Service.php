<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue;

interface Service
{
    const FOO_QUEUE = 'foo_queue';

    public function queue(Message $message, array $parameters = []) : bool;

    public function dequeue(string $queueIdentifier, array $parameters = []) : array;

    public function delete(Message $message, array $parameters = []) : bool;

    public function getQueueConfig(string $queueIdentifier) : array;

    public function getQueueWorkerConfig(string $queueIdentifier) : array;
}
