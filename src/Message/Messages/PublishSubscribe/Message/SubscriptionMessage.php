<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Message;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Exception\UndefinedServiceException;
use LinioPay\Idle\Message\Message as IdleMessageInterface;
use LinioPay\Idle\Message\Messages\DefaultMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service as PublishSubscribeServiceInterface;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage as SubscriptionMessageInterface;
use LinioPay\Idle\Message\ReceivableMessage as ReceivableMessageInterface;

class SubscriptionMessage extends DefaultMessage implements SubscriptionMessageInterface, ReceivableMessageInterface
{
    /** @var string */
    protected $subscriptionIdentifier;

    /** @var PublishSubscribeServiceInterface */
    protected $service;

    public function __construct(string $subscriptionIdentifier, string $body = '', array $attributes = [], string $messageIdentifier = '', array $metadata = [])
    {
        $this->subscriptionIdentifier = $subscriptionIdentifier;
        parent::__construct($body, $attributes, $messageIdentifier, $metadata);
    }

    public function getSubscriptionIdentifier() : string
    {
        return $this->subscriptionIdentifier;
    }

    public function toArray() : array
    {
        return [
            'message_identifier' => $this->getMessageId(),
            'subscription_identifier' => $this->getSubscriptionIdentifier(),
            'body' => $this->getBody(),
            'attributes' => $this->getAttributes(),
            'metadata' => $this->getTemporaryMetadata(),
        ];
    }

    public static function fromArray(array $parameters) : IdleMessageInterface
    {
        $required = isset(
            $parameters['subscription_identifier']
        );

        if (!$required || !is_string($parameters['subscription_identifier'])) {
            throw new InvalidMessageParameterException('[subscription_identifier]');
        }

        return new SubscriptionMessage(
            $parameters['subscription_identifier'],
            $parameters['body'],
            $parameters['attributes'] ?? [],
            $parameters['message_identifier'] ?? '',
            $parameters['metadata'] ?? []
        );
    }

    public function getIdleIdentifier() : string
    {
        return SubscriptionMessageInterface::IDENTIFIER;
    }

    public function getSourceName() : string
    {
        return $this->getSubscriptionIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(array $parameters = []) : bool
    {
        if (is_null($this->service)) {
            throw new UndefinedServiceException($this);
        }

        return $this->service->acknowledge($this, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function pull(array $parameters = []) : array
    {
        if (is_null($this->service)) {
            throw new UndefinedServiceException($this);
        }

        return $this->service->pull($this->getSubscriptionIdentifier(), $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function receive(array $parameters = []) : array
    {
        return $this->pull($parameters);
    }
}
