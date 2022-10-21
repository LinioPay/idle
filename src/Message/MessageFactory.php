<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

use LinioPay\Idle\Message\Exception\FailedReceivingMessageException;
use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;

interface MessageFactory
{
    /**
     * Create a Message from message data array.
     *
     * @throws InvalidMessageParameterException
     */
    public function createMessage(array $messageParameters) : Message;

    /**
     * Create a ReceivableMessage from message data array.  Typically this is used to define the
     * queue_identifier, or subscription_identifier parameters.
     *
     * @throws InvalidMessageParameterException
     */
    public function createReceivableMessage(array $messageParameters) : ReceivableMessage;

    /**
     * Create a SendableMessage from message data array.  Typically this is used to create a message
     * containing all the data which we want to add to the queue or topic.
     *
     * @throws InvalidMessageParameterException
     */
    public function createSendableMessage(array $messageParameters) : SendableMessage;

    /**
     * Create a ReceivableMessage and perform a receive on it which will cause it to retrieve a message from the queue.
     *
     * @throws FailedReceivingMessageException
     */
    public function receiveMessageOrFail(array $messageParameters, array $receiveParameters = []) : Message;

    /**
     * Receive messages according to the specified parameters.
     *
     * @return Message[]
     */
    public function receiveMessages(array $messageParameters, array $receiveParameters = []) : array;
}
