<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

interface ServiceFactory
{
    public function createFromMessage(Message $message) : Service;
}
