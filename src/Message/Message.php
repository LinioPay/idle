<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

use JsonSerializable;

interface Message extends JsonSerializable
{
    /**
     * Retrieve the id for the given message.
     */
    public function getMessageId() : string;

    /**
     * Set the id for the message.
     */
    public function setMessageId(string $messageIdentifier) : void;

    /**
     * Retrieve the message body.
     */
    public function getBody() : string;

    /**
     * Set the message body.
     */
    public function setBody(string $body) : void;

    /**
     * Get message attributes.
     */
    public function getAttributes() : array;

    /**
     * Set message attributes.
     */
    public function setAttributes(array $attributes) : void;

    /**
     * Retrieve any temporary metadata such as receipt id, retrieval time, etc.
     */
    public function getTemporaryMetadata() : array;

    /**
     * Set the temporary metadata such as receipt id, retrieval time, etc.
     */
    public function setTemporaryMetadata(array $metadata) : void;

    /**
     * Convert message instance into array format.
     */
    public function toArray() : array;

    /**
     * Create a message instance from array data.
     */
    public static function fromArray(array $parameters) : Message;

    /**
     * Retrieve the message type identifier.
     */
    public function getIdleIdentifier() : string;

    /**
     * Retrieve name of the source where the message resides.  Examples: topic name, queue name, etc.
     */
    public function getSourceName() : string;

    /**
     * Set the service for which the message belongs.
     */
    public function setService(Service $service) : void;

    /**
     * Retrieve the service for which the message belongs.
     */
    public function getService() : ?Service;
}
