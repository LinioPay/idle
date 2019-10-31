<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Message;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Message as IdleMessageInterface;
use LinioPay\Idle\Message\Messages\DefaultMessage;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessageInterface;

class Message extends DefaultMessage implements QueueMessageInterface
{
    /** @var string */
    protected $queueIdentifier;

    public function __construct(string $queueIdentifier, string $body, array $attributes = [], string $messageIdentifier = '', array $metadata = [])
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
            $parameters['queue_identifier'],
            $parameters['body']
        );

        if (!$required || !is_string($parameters['queue_identifier']) || !is_string($parameters['body'])) {
            throw new InvalidMessageParameterException('[queue_identifier, body]');
        }

        return new Message(
            $parameters['queue_identifier'],
            $parameters['body'],
            $parameters['attributes'] ?? [],
            $parameters['message_identifier'] ?? '',
            $parameters['metadata'] ?? []
        );
    }

    public function getSourceName() : string
    {
        return $this->getQueueIdentifier();
    }
}
