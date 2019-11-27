<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe;

use LinioPay\Idle\Message\Message as MessageInterface;

interface SubscriptionMessage extends MessageInterface
{
    const IDENTIFIER = 'subscription';

    public function getSubscriptionIdentifier() : string;
}
