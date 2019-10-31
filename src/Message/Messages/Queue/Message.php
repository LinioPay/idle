<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue;

use LinioPay\Idle\Message\Message as MessageInterface;

interface Message extends MessageInterface
{
    const IDENTIFIER = 'queue';

    public function getQueueIdentifier() : string;
}
