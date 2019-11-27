<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Message;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Message as IdleMessageInterface;
use LinioPay\Idle\Message\Messages\DefaultMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\TopicMessage as PublishSubscribeMessageInterface;

class TopicMessage extends DefaultMessage implements PublishSubscribeMessageInterface
{
    /** @var string */
    protected $topicIdentifier;

    public function __construct(string $topicIdentifier, string $body, array $attributes = [], string $messageIdentifier = '', array $metadata = [])
    {
        $this->topicIdentifier = $topicIdentifier;
        parent::__construct($body, $attributes, $messageIdentifier, $metadata);
    }

    public function getTopicIdentifier() : string
    {
        return $this->topicIdentifier;
    }

    public function toArray() : array
    {
        return [
            'message_identifier' => $this->getMessageId(),
            'topic_identifier' => $this->getTopicIdentifier(),
            'body' => $this->getBody(),
            'attributes' => $this->getAttributes(),
            'metadata' => $this->getTemporaryMetadata(),
        ];
    }

    public static function fromArray(array $parameters) : IdleMessageInterface
    {
        $required = isset(
            $parameters['topic_identifier']
        );

        if (!$required || !is_string($parameters['topic_identifier'])) {
            throw new InvalidMessageParameterException('[topic_identifier]');
        }

        return new TopicMessage(
            $parameters['topic_identifier'],
            $parameters['body'],
            $parameters['attributes'] ?? [],
            $parameters['message_identifier'] ?? '',
            $parameters['metadata'] ?? []
        );
    }

    public function getSourceName() : string
    {
        return $this->getTopicIdentifier();
    }
}
