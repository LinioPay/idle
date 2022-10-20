<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Message;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Exception\UndefinedServiceException;
use LinioPay\Idle\Message\Message as IdleMessageInterface;
use LinioPay\Idle\Message\Messages\DefaultMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service as PublishSubscribeServiceInterface;
use LinioPay\Idle\Message\Messages\PublishSubscribe\TopicMessage as TopicMessageInterface;
use LinioPay\Idle\Message\SendableMessage as SendableMessageInterface;

class TopicMessage extends DefaultMessage implements TopicMessageInterface, SendableMessageInterface
{
    /** @var PublishSubscribeServiceInterface */
    protected $service;
    /** @var string */
    protected $topicIdentifier;

    public function __construct(string $topicIdentifier, string $body = '', array $attributes = [], string $messageIdentifier = '', array $metadata = [])
    {
        $this->topicIdentifier = $topicIdentifier;
        parent::__construct($body, $attributes, $messageIdentifier, $metadata);
    }

    public function getIdleIdentifier() : string
    {
        return TopicMessageInterface::IDENTIFIER;
    }

    public function getSourceName() : string
    {
        return $this->getTopicIdentifier();
    }

    public function getTopicIdentifier() : string
    {
        return $this->topicIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(array $parameters = []) : bool
    {
        if (is_null($this->service)) {
            throw new UndefinedServiceException($this);
        }

        return $this->service->publish($this, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function send(array $parameters = []) : bool
    {
        return $this->publish($parameters);
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
            $parameters['body'] ?? '',
            $parameters['attributes'] ?? [],
            $parameters['message_identifier'] ?? '',
            $parameters['metadata'] ?? []
        );
    }
}
