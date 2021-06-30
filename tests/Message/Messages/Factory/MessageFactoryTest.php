<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\TopicMessage;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\SendableMessage;
use LinioPay\Idle\Message\Service as ServiceInterface;
use LinioPay\Idle\Message\ServiceFactory as ServiceFactoryInterface;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Container\ContainerInterface;

class MessageFactoryTest extends TestCase
{
    public function createMessageDataProvider()
    {
        yield [['topic_identifier' => 'foo', 'body' => 'foobody'], TopicMessage::class];
        yield [['subscription_identifier' => 'foo', 'body' => 'foobody'], SubscriptionMessage::class];
        yield [['queue_identifier' => 'foo', 'body' => 'foobody'], QueueMessage::class];
    }

    /**
     * @dataProvider createMessageDataProvider
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
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());

        $factory = new MessageFactory($container);

        $out = $factory->createMessage($parameters);
        $this->assertInstanceOf(ServiceInterface::class, $out->getService());

        $this->assertInstanceOf($outcomeClass, $out);
    }

    public function testCreatesMessageThrowsInvalidMessageParameterExceptionWithInvalidType()
    {
        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());

        $factory = new MessageFactory($container);

        $this->expectException(InvalidMessageParameterException::class);
        $factory->createMessage([]);
    }

    public function receiveMessageDataProvider()
    {
        yield [['subscription_identifier' => 'foo'], SubscriptionMessage::class, 'pullOneOrFail'];
        yield [['queue_identifier' => 'foo'], QueueMessage::class, 'dequeueOneOrFail'];
    }

    /**
     * @dataProvider receiveMessageDataProvider
     */
    public function testReceivesMessage(array $parameters, string $outcomeClass, $serviceMethod)
    {
        $service = m::mock(ServiceInterface::class)->shouldAllowMockingMethod($serviceMethod);
        $service->shouldReceive($serviceMethod)
            ->once()
            ->andReturn(m::mock($outcomeClass));

        $serviceFactory = m::mock(ServiceFactoryInterface::class);
        $serviceFactory->shouldReceive('createFromMessage')
            ->once()
            ->andReturn($service);

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(ServiceFactoryInterface::class)
            ->andReturn($serviceFactory);
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());

        $factory = new MessageFactory($container);

        $this->assertInstanceOf($outcomeClass, $factory->receiveMessageOrFail($parameters));
    }

    public function receiveMessagesDataProvider()
    {
        yield [['subscription_identifier' => 'foo'], SubscriptionMessage::class, 'pull'];
        yield [['queue_identifier' => 'foo'], QueueMessage::class, 'dequeue'];
    }

    /**
     * @dataProvider receiveMessagesDataProvider
     */
    public function testReceivesMessages(array $parameters, string $outcomeClass, $serviceMethod)
    {
        $service = m::mock(ServiceInterface::class)->shouldAllowMockingMethod($serviceMethod);
        $service->shouldReceive($serviceMethod)
            ->once()
            ->andReturn([m::mock($outcomeClass)]);

        $serviceFactory = m::mock(ServiceFactoryInterface::class);
        $serviceFactory->shouldReceive('createFromMessage')
            ->once()
            ->andReturn($service);

        $container = m::mock(ContainerInterface::class);
        $container->shouldReceive('get')
            ->once()
            ->with(ServiceFactoryInterface::class)
            ->andReturn($serviceFactory);
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());

        $factory = new MessageFactory($container);

        $messages = $factory->receiveMessages($parameters);

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
        $this->assertInstanceOf($outcomeClass, $messages[0]);
    }

    public function testFailsToReceiveMessageWhenPropertiesDoNotResultInReceivableMessage()
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
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());

        $factory = new MessageFactory($container);

        $this->expectException(InvalidMessageParameterException::class);
        $factory->receiveMessageOrFail(['topic_identifier' => 'foo']);
    }

    public function testCreatesSendableMessage()
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
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());

        $factory = new MessageFactory($container);

        $this->assertInstanceOf(SendableMessage::class, $factory->createSendableMessage(['topic_identifier' => 'foo']));
    }

    public function testFailsToCreateSendableMessageWhenPropertiesDoNotResultInSendableMessage()
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
        $container->shouldReceive('get')
            ->once()
            ->with(IdleConfig::class)
            ->andReturn(new IdleConfig());

        $factory = new MessageFactory($container);

        $this->expectException(InvalidMessageParameterException::class);
        $factory->createSendableMessage(['subscription_identifier' => 'foo']);
    }
}
