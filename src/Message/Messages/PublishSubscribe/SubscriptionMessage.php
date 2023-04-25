<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe;

use LinioPay\Idle\Message\Message as MessageInterface;

interface SubscriptionMessage extends MessageInterface
{
    public const IDENTIFIER = 'subscription';

    /**
     * Proxies call to acknowledge on the service.
     */
    public function acknowledge(array $parameters = []) : bool;

    public function getSubscriptionIdentifier() : string;

    /**
     * Proxies call to pull an array of messages from the service.
     *
     * @return MessageInterface[]
     */
    public function pull(array $parameters = []) : array;
}
