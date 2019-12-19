<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Message;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Exception\UndefinedServiceException;
use LinioPay\Idle\Message\Message as IdleMessageInterface;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Messages\DefaultMessage;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessageInterface;
use LinioPay\Idle\Message\Messages\Queue\Service as QueueServiceInterface;
use LinioPay\Idle\Message\ReceivableMessage as ReceivableMessageInterface;
use LinioPay\Idle\Message\SendableMessage as SendableMessageInterface;

class Message extends DefaultMessage implements QueueMessageInterface, SendableMessageInterface, ReceivableMessageInterface
{
    /** @var string */
    protected $queueIdentifier;

    /** @var QueueServiceInterface */
    protected $service;

    public function __construct(string $queueIdentifier, string $body = '', array $attributes = [], string $messageIdentifier = '', array $metadata = [])
    {
        $this->queueIdentifier = $queueIdentifier;
        parent::__construct($body, $attributes, $messageIdentifier, $metadata);
    }

    public function getQueueIdentifier() : string
    {
        return $this->queueIdentifier;
    }

    public function toArray() : array
    {
        return [
            'message_identifier' => $this->getMessageId(),
            'queue_identifier' => $this->getQueueIdentifier(),
            'body' => $this->getBody(),
            'attributes' => $this->getAttributes(),
            'metadata' => $this->getTemporaryMetadata(),
        ];
    }

    public static function fromArray(array $parameters) : IdleMessageInterface
    {
        $required = isset(
            $parameters['queue_identifier']
        );

        if (!$required || !is_string($parameters['queue_identifier']) || !is_string($parameters['body'] ?? '')) {
            throw new InvalidMessageParameterException('[queue_identifier, body]');
        }

        return new Message(
            $parameters['queue_identifier'],
            $parameters['body'] ?? '',
            $parameters['attributes'] ?? [],
            $parameters['message_identifier'] ?? '',
            $parameters['metadata'] ?? []
        );
    }

    public function getIdleIdentifier() : string
    {
        return QueueMessageInterface::IDENTIFIER;
    }

    public function getSourceName() : string
    {
        return $this->getQueueIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function queue(array $parameters = []) : bool
    {
        if (is_null($this->service)) {
            throw new UndefinedServiceException($this);
        }

        return $this->service->queue($this, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function send(array $parameters = []) : bool
    {
        return $this->queue($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function dequeue(array $parameters = []) : array
    {
        if (is_null($this->service)) {
            throw new UndefinedServiceException($this);
        }

        return $this->service->dequeue($this->getQueueIdentifier(), $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function receive(array $parameters = []) : array
    {
        return $this->dequeue($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function dequeueOneOrFail(array $parameters = []) : MessageInterface
    {
        if (is_null($this->service)) {
            throw new UndefinedServiceException($this);
        }

        return $this->service->dequeueOneOrFail($this->getQueueIdentifier(), $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function receiveOneOrFail(array $parameters = []) : MessageInterface
    {
        return $this->dequeueOneOrFail($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(array $parameters = []) : bool
    {
        if (is_null($this->service)) {
            throw new UndefinedServiceException($this);
        }

        return $this->service->delete($this, $parameters);
    }
}
