<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

interface MessageFactory
{
    public function createMessage(array $messageParameters) : Message;

    public function receiveMessageOrFail(array $messageParameters, array $receiveParameters = []) : Message;

    /**
     * @return Message[]
     */
    public function receiveMessages(array $messageParameters, array $receiveParameters = []) : array;
}
