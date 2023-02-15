<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages;

use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Service;

abstract class DefaultMessage implements MessageInterface
{
    /** @var array */
    protected $attributes;

    /** @var string */
    protected $body;
    /** @var string */
    protected $messageId;

    /** @var array */
    protected $metadata;

    /** @var Service */
    protected $service;

    public function __construct(string $body = '', array $attributes = [], string $messageId = '', array $metadata = [])
    {
        $this->body = $body;
        $this->attributes = $attributes;
        $this->messageId = $messageId;
        $this->metadata = $metadata;
    }

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    public function getBody() : string
    {
        return $this->body;
    }

    public function getMessageId() : string
    {
        return $this->messageId;
    }

    public function getService() : ?Service
    {
        return $this->service;
    }

    public function getTemporaryMetadata() : array
    {
        return $this->metadata;
    }

    public function jsonSerialize() : array
    {
        $data = $this->toArray();

        unset($data['metadata']);

        return $data;
    }

    public function setAttributes(array $attributes) : void
    {
        $this->attributes = $attributes;
    }

    public function setBody(string $body) : void
    {
        $this->body = $body;
    }

    public function setMessageId(string $messageId) : void
    {
        $this->messageId = $messageId;
    }

    public function setService(Service $service) : void
    {
        $this->service = $service;
    }

    public function setTemporaryMetadata(array $metadata) : void
    {
        $this->metadata = $metadata;
    }

    abstract public function toArray() : array;

    abstract public static function fromArray(array $parameters) : MessageInterface;
}
