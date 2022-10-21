<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

use JsonSerializable;

interface Message extends JsonSerializable
{
    /**
     * Get message attributes.
     */
    public function getAttributes() : array;

    /**
     * Retrieve the message body.
     */
    public function getBody() : string;

    /**
     * Retrieve the message type identifier.
     */
    public function getIdleIdentifier() : string;

    /**
     * Retrieve the id for the given message.
     */
    public function getMessageId() : string;

    /**
     * Retrieve the service for which the message belongs.
     */
    public function getService() : ?Service;

    /**
     * Retrieve name of the source where the message resides.  Examples: topic name, queue name, etc.
     */
    public function getSourceName() : string;

    /**
     * Retrieve any temporary metadata such as receipt id, retrieval time, etc.
     */
    public function getTemporaryMetadata() : array;

    /**
     * Set message attributes.
     */
    public function setAttributes(array $attributes) : void;

    /**
     * Set the message body.
     */
    public function setBody(string $body) : void;

    /**
     * Set the id for the message.
     */
    public function setMessageId(string $messageIdentifier) : void;

    /**
     * Set the service for which the message belongs.
     */
    public function setService(Service $service) : void;

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
}
