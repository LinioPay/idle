<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Message;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Exception\UndefinedServiceException;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service as PublishSubscribeServiceInterface;
use LinioPay\Idle\TestCase;
use Mockery as m;

class TopicMessageTest extends TestCase
{
    public function testFromArrayThrowsInvalidMessageParameterExceptionWhenMissingRequiredParameters()
    {
        $this->expectException(InvalidMessageParameterException::class);
        TopicMessage::fromArray([
           'body' => 'foobody',
           'attributes' => [
               'red' => true,
           ],
        ]);
    }

    public function testGettersAndSetters()
    {
        $message = new TopicMessage('foo_topic', 'body', ['red' => true], 'foo123', ['meta' => true]);

        $this->assertSame('foo_topic', $message->getTopicIdentifier());
        $this->assertSame('foo_topic', $message->getSourceName());

        $this->assertSame('foo123', $message->getMessageId());
        $message->setMessageId('123foo');
        $this->assertSame('123foo', $message->getMessageId());

        $this->assertSame('body', $message->getBody());
        $message->setBody('ydob');
        $this->assertSame('ydob', $message->getBody());

        $this->assertSame(['red' => true], $message->getAttributes());
        $message->setAttributes(['red' => false]);
        $this->assertSame(['red' => false], $message->getAttributes());

        $this->assertSame(['meta' => true], $message->getTemporaryMetadata());
        $message->setTemporaryMetadata(['meta' => false]);
        $this->assertSame(['meta' => false], $message->getTemporaryMetadata());

        $service = m::mock(PublishSubscribeServiceInterface::class);
        $message->setService($service);
        $this->assertSame($service, $message->getService());

        $this->assertSame(TopicMessage::IDENTIFIER, $message->getIdleIdentifier());
    }

    public function testProxiesCallToPublish()
    {
        $message = new TopicMessage('foo_topic', 'body', ['red' => true], 'foo123', ['redmeta' => true]);

        $service = m::mock(PublishSubscribeServiceInterface::class);
        $service->shouldReceive('publish')
            ->once()
            ->with($message, ['foo' => 'bar'])
            ->andReturn(true);

        $message->setService($service);
        $this->assertTrue($message->send(['foo' => 'bar']));
    }

    public function testPublishThrowsUndefinedServiceException()
    {
        $message = new TopicMessage('foo_topic', 'body', ['red' => true], 'foo123', ['redmeta' => true]);

        $this->expectException(UndefinedServiceException::class);
        $message->publish();
    }

    public function testToArrayAndFromArray()
    {
        $message = new TopicMessage('foo_topic', 'body', ['red' => true], 'foo123', ['redmeta' => true]);

        $asArray = $jsonOut = $message->toArray();
        $this->assertSame($asArray, TopicMessage::fromArray($message->toArray())->toArray());

        $this->assertArrayHasKey('message_identifier', $asArray);
        $this->assertArrayHasKey('topic_identifier', $asArray);
        $this->assertArrayHasKey('body', $asArray);
        $this->assertArrayHasKey('attributes', $asArray);
        $this->assertArrayHasKey('metadata', $asArray);

        unset($jsonOut['metadata']);
        $this->assertSame($jsonOut, json_decode(json_encode($message), true));
    }
}
