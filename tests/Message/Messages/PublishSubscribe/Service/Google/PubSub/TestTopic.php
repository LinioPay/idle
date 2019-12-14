<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub;

use Google\Cloud\PubSub\Topic as TopicBase;

class TestTopic extends TopicBase
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
