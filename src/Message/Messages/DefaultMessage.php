<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages;

use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Service;

abstract class DefaultMessage implements MessageInterface
{
    /** @var string */
    protected $messageId;

    /** @var string */
    protected $body;

    /** @var array */
    protected $attributes;

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

    public function getMessageId() : string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId) : void
    {
        $this->messageId = $messageId;
    }

    public function getBody() : string
    {
        return $this->body;
    }

    public function setBody(string $body) : void
    {
        $this->body = $body;
    }

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes) : void
    {
        $this->attributes = $attributes;
    }

    public function getTemporaryMetadata() : array
    {
        return $this->metadata;
    }

    public function setTemporaryMetadata(array $metadata) : void
    {
        $this->metadata = $metadata;
    }

    public function jsonSerialize()
    {
        $data = $this->toArray();

        unset($data['metadata']);

        return $data;
    }

    public function getService() : ?Service
    {
        return $this->service;
    }

    public function setService(Service $service) : void
    {
        $this->service = $service;
    }

    abstract public function toArray() : array;

    abstract public static function fromArray(array $parameters) : MessageInterface;
}
