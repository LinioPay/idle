<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub;

use Google\Cloud\PubSub\Subscription as SubscriptionBase;

class TestSubscription extends SubscriptionBase
{
    /**
     * Override debugInfo since its wrecking havock on debuggers.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [];
    }
}
