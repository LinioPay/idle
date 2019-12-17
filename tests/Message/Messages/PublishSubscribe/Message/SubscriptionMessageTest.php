<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Message;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Exception\UndefinedServiceException;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service as PublishSubscribeServiceInterface;
use LinioPay\Idle\TestCase;
use Mockery as m;

class SubscriptionMessageTest extends TestCase
{
    public function testGettersAndSetters()
    {
        $message = new SubscriptionMessage('foo_subscription', 'body', ['red' => true], 'foo123', ['meta' => true]);

        $this->assertSame('foo_subscription', $message->getSubscriptionIdentifier());
        $this->assertSame('foo_subscription', $message->getSourceName());

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

        $this->assertSame(SubscriptionMessage::IDENTIFIER, $message->getIdleIdentifier());
    }

    public function testToArrayAndFromArray()
    {
        $message = new SubscriptionMessage('foo_subscription', 'body', ['red' => true], 'foo123', ['redmeta' => true]);

        $asArray = $jsonOut = $message->toArray();
        $this->assertSame($asArray, SubscriptionMessage::fromArray($message->toArray())->toArray());

        $this->assertArrayHasKey('message_identifier', $asArray);
        $this->assertArrayHasKey('subscription_identifier', $asArray);
        $this->assertArrayHasKey('body', $asArray);
        $this->assertArrayHasKey('attributes', $asArray);
        $this->assertArrayHasKey('metadata', $asArray);

        unset($jsonOut['metadata']);
        $this->assertSame($jsonOut, json_decode(json_encode($message), true));
    }

    public function testFromArrayThrowsInvalidMessageParameterExceptionWhenMissingRequiredParameters()
    {
        $this->expectException(InvalidMessageParameterException::class);
        SubscriptionMessage::fromArray([
           'body' => 'foobody',
           'attributes' => [
               'red' => true,
           ],
        ]);
    }

    public function testProxiesCallToAcknowledge()
    {
        $message = new SubscriptionMessage('foo_subscription', 'body', ['red' => true], 'foo123', ['redmeta' => true]);

        $service = m::mock(PublishSubscribeServiceInterface::class);
        $service->shouldReceive('acknowledge')
            ->once()
            ->with($message, ['foo' => 'bar'])
            ->andReturn(true);

        $message->setService($service);
        $this->assertTrue($message->acknowledge(['foo' => 'bar']));
    }

    public function testAcknowledgeThrowsUndefinedServiceException()
    {
        $message = new SubscriptionMessage('foo_subscription', 'body', ['red' => true], 'foo123', ['redmeta' => true]);

        $this->expectException(UndefinedServiceException::class);
        $message->acknowledge();
    }

    public function testProxiesCallToPull()
    {
        $message = new SubscriptionMessage('foo_subscription');

        $service = m::mock(PublishSubscribeServiceInterface::class);
        $service->shouldReceive('pull')
            ->once()
            ->with($message->getSubscriptionIdentifier(), ['foo' => 'bar'])
            ->andReturn(['foo']);

        $message->setService($service);
        $this->assertSame(['foo'], $message->receive(['foo' => 'bar']));
    }

    public function testPullThrowsUndefinedServiceException()
    {
        $message = new SubscriptionMessage('foo_subscription', 'body', ['red' => true], 'foo123', ['redmeta' => true]);

        $this->expectException(UndefinedServiceException::class);
        $message->pull();
    }
}
