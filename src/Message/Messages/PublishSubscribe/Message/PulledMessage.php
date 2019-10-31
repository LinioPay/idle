<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Message;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Message as IdleMessageInterface;
use LinioPay\Idle\Message\Messages\DefaultMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\PulledMessage as PulledMessageInterface;

class PulledMessage extends DefaultMessage implements PulledMessageInterface
{
    /** @var string */
    protected $subscriptionIdentifier;

    public function __construct(string $subscriptionIdentifier, string $body, array $attributes = [], string $messageIdentifier = '', array $metadata = [])
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

        return new PulledMessage(
            $parameters['subscription_identifier'],
            $parameters['body'],
            $parameters['attributes'] ?? [],
            $parameters['message_identifier'] ?? '',
            $parameters['metadata'] ?? []
        );
    }

    public function getSourceName() : string
    {
        return $this->getSubscriptionIdentifier();
    }
}
