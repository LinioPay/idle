<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe;

use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Service as ServiceInterface;

interface Service extends ServiceInterface
{
    public function publish(PublishableMessage $message, array $parameters = []) : bool;

    /**
     * @return MessageInterface[]
     */
    public function pull(string $subscriptionIdentifier, array $parameters = []) : array;

    public function acknowledge(PulledMessage $message, array $parameters = []) : bool;

    public function getConfig() : array;
}
