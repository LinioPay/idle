<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Queue;

use LinioPay\Idle\Job\Workers\DefaultWorker;
use LinioPay\Idle\Job\Workers\Exception\InvalidWorkerParameterException;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessageInterface;

class DeleteMessageWorker extends DefaultWorker
{
    const IDENTIFIER = 'queue_delete_message_worker';

    public static $skipFactory = true;

    /** @var QueueMessageInterface */
    protected $message;

    public function setParameters(array $parameters) : void
    {
        $this->message = $parameters['message'] ?? null;

        unset($parameters['message'], $parameters['job']);

        parent::setParameters($parameters);
    }

    public function validateParameters() : void
    {
        if (is_null($this->message) || !is_a($this->message, QueueMessageInterface::class)) {
            throw new InvalidWorkerParameterException($this, 'message');
        }
    }

    public function work() : bool
    {
        $this->message->delete($this->getParameters());

        return true;
    }
}
