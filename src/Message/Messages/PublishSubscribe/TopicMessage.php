<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe;

use LinioPay\Idle\Message\Message as MessageInterface;

interface TopicMessage extends MessageInterface
{
    public const IDENTIFIER = 'topic';

    public function getTopicIdentifier() : string;

    /**
     * Proxies a publish call to the service.
     */
    public function publish(array $parameters = []) : bool;
}
