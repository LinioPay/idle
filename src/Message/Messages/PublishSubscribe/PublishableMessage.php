<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe;

use LinioPay\Idle\Message\Message as MessageInterface;

interface PublishableMessage extends MessageInterface
{
    const IDENTIFIER = 'topic';

    public function getTopicIdentifier() : string;
}
