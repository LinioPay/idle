<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

interface SendableMessage extends Message
{
    /**
     * Send the message to its corresponding service.  Alias for queue, publish, etc
     */
    public function send(array $parameters = []) : bool;
}
