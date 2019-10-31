<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Messages\Queue\Message\Message as QueueMessage;
use LinioPay\Idle\TestCase;

class MessageTest extends TestCase
{
    public function testCanGetParameters()
    {
        $queueIdentifier = 'fooqueue';
        $body = 'foobody';
        $attributes = ['bar' => 'fuz'];
        $messageIdentifier = '123';
        $temporaryMetadata = ['foo' => 'bar'];
        $message = new QueueMessage($queueIdentifier, $body, $attributes, $messageIdentifier, $temporaryMetadata);

        $message->setAttributes($attributes);
        $message->setBody($body);
        $message->setTemporaryMetadata($temporaryMetadata);

        $this->assertSame($queueIdentifier, $message->getQueueIdentifier());
        $this->assertSame($body, $message->getBody());
        $this->assertSame($attributes, $message->getAttributes());
        $this->assertSame($messageIdentifier, $message->getMessageId());
        $this->assertSame($temporaryMetadata, $message->getTemporaryMetadata());
    }

    public function testCanGetFromArraySuccessfully()
    {
        $message = QueueMessage::fromArray(['body' => 'mbody', 'queue_identifier' => 'foo_queue']);

        $this->assertSame('mbody', $message->getBody());
        $this->assertSame('foo_queue', $message->getQueueIdentifier());
    }

    public function testCanJsonSerialize()
    {
        $message = QueueMessage::fromArray(['body' => 'mbody', 'queue_identifier' => 'foo_queue']);

        $this->assertSame([
            'message_identifier' => '',
            'queue_identifier' => 'foo_queue',
            'body' => 'mbody',
            'attributes' => [],
        ], $message->jsonSerialize());
    }

    public function testFromArrayThrowsExceptionWhenMessageInvalid()
    {
        $this->expectException(InvalidMessageParameterException::class);
        QueueMessage::fromArray(['body' => 'mbody']);
    }
}
