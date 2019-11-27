<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\TopicMessage;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\Service as ServiceInterface;
use LinioPay\Idle\Message\ServiceFactory as ServiceFactoryInterface;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class MessageFactoryTest extends TestCase
{
    public function messageDataProvider()
    {
        yield [['topic_identifier' => 'foo', 'body' => ''], TopicMessage::class];
        yield [['subscription_identifier' => 'foo', 'body' => ''], SubscriptionMessage::class];
        yield [['queue_identifier' => 'foo', 'body' => ''], QueueMessage::class];
    }

    /**
     * @dataProvider messageDataProvider
     */
    public function testCreatesMessage(array $parameters, string $outcomeClass)
    {
        $serviceFactory = m::mock(ServiceFactoryInterface::class);
        $serviceFactory->shouldReceive('createFromMessage')
            ->once()
            ->andReturn(m::mock(ServiceInterface::class));

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(ServiceFactoryInterface::class)
            ->andReturn($serviceFactory);

        $factory = new MessageFactory();
        $factory($container);

        $out = $factory->createMessage($parameters);
        $this->assertInstanceOf(ServiceInterface::class, $out->getService());

        $this->assertInstanceOf($outcomeClass, $out);
    }

    public function testCreatesMessageThrowsInvalidMessageParameterExceptionWithInvalidType()
    {
        $container = m::mock(ContainerInterface::class);

        $factory = new MessageFactory();
        $factory($container);

        $this->expectException(InvalidMessageParameterException::class);
        $factory->createMessage([]);
    }
}
