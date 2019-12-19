<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

interface MessageFactory
{
    public function createMessage(array $parameters) : Message;

    public function receiveMessageOrFail(array $parameters) : Message;

    /**
     * @return Message[]
     */
    public function receiveMessages(array $parameters) : array;
}
