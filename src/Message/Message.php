<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message;

use JsonSerializable;

interface Message extends JsonSerializable
{
    public function getMessageId() : string;

    public function setMessageId(string $messageIdentifier) : void;

    public function getBody() : string;

    public function setBody(string $body) : void;

    public function getAttributes() : array;

    public function setAttributes(array $attributes) : void;

    public function getTemporaryMetadata() : array;

    public function setTemporaryMetadata(array $metadata) : void;

    public function toArray() : array;

    public static function fromArray(array $parameters) : Message;

    public function getIdleIdentifier() : string;

    public function getSourceName() : string;

    public function setService(Service $service) : void;

    public function getService() : ?Service;
}
