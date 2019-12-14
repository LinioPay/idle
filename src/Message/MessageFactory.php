<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

interface MessageFactory
{
    public function createMessage(array $parameters) : Message;
}
