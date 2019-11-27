<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Exception\UndefinedServiceException;
use LinioPay\Idle\Message\Messages\Queue\Message\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service as QueueServiceInterface;
use LinioPay\Idle\TestCase;
use Mockery as m;

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
        /** @var Message $message */
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

    public function testProxiesCallToQueue()
    {
        /** @var QueueMessage $message */
        $message = QueueMessage::fromArray(['body' => 'mbody', 'queue_identifier' => 'foo_queue']);

        $service = m::mock(QueueServiceInterface::class);
        $service->shouldReceive('queue')
            ->once()
            ->with($message, ['foo' => 'bar'])
            ->andReturn(true);

        $message->setService($service);
        $this->assertTrue($message->queue(['foo' => 'bar']));
    }

    public function testQueueThrowsUndefinedServiceException()
    {
        /** @var QueueMessage $message */
        $message = QueueMessage::fromArray(['body' => 'mbody', 'queue_identifier' => 'foo_queue']);

        $this->expectException(UndefinedServiceException::class);
        $message->queue();
    }

    public function testProxiesCallToDelete()
    {
        /** @var QueueMessage $message */
        $message = QueueMessage::fromArray(['body' => 'mbody', 'queue_identifier' => 'foo_queue']);

        $service = m::mock(QueueServiceInterface::class);
        $service->shouldReceive('delete')
            ->once()
            ->with($message, ['foo' => 'bar'])
            ->andReturn(true);

        $message->setService($service);
        $this->assertTrue($message->delete(['foo' => 'bar']));
    }

    public function testDeleteThrowsUndefinedServiceException()
    {
        /** @var QueueMessage $message */
        $message = QueueMessage::fromArray(['body' => 'mbody', 'queue_identifier' => 'foo_queue']);

        $this->expectException(UndefinedServiceException::class);
        $message->delete();
    }

    public function testProxiesCallToDequeue()
    {
        /** @var QueueMessage $message */
        $message = QueueMessage::fromArray(['queue_identifier' => 'foo_queue']);

        $service = m::mock(QueueServiceInterface::class);
        $service->shouldReceive('dequeue')
            ->once()
            ->with($message->getQueueIdentifier(), ['foo' => 'bar'])
            ->andReturn(['foo']);

        $message->setService($service);
        $this->assertSame(['foo'], $message->dequeue(['foo' => 'bar']));
    }

    public function testDequeueThrowsUndefinedServiceException()
    {
        /** @var QueueMessage $message */
        $message = QueueMessage::fromArray(['queue_identifier' => 'foo_queue']);

        $this->expectException(UndefinedServiceException::class);
        $message->dequeue();
    }
}
