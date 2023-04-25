<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\PublishSubscribe;

use LinioPay\Idle\Job\Workers\DefaultWorker;
use LinioPay\Idle\Job\Workers\Exception\InvalidWorkerParameterException;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage as SubscriptionMessageInterface;

class AcknowledgeMessageWorker extends DefaultWorker
{
    public const IDENTIFIER = 'publishsubscribe_acknowledge_message_worker';

    public static $skipFactory = true;

    /** @var SubscriptionMessageInterface */
    protected $message;

    public function setParameters(array $parameters) : void
    {
        $this->message = $parameters['message'] ?? null;

        unset($parameters['message'], $parameters['job']);

        parent::setParameters($parameters);
    }

    public function validateParameters() : void
    {
        if (is_null($this->message) || !is_a($this->message, SubscriptionMessageInterface::class)) {
            throw new InvalidWorkerParameterException($this, 'message');
        }
    }

    public function work() : bool
    {
        $this->message->acknowledge($this->getParameters());

        return true;
    }
}
