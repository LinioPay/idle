<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Message\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Message\TopicMessage;
use LinioPay\Idle\Message\Messages\Queue\Message\Message as QueueMessage;
use LinioPay\Idle\Message\ReceivableMessage as ReceivableMessageInterface;
use LinioPay\Idle\Message\ServiceFactory as ServiceFactoryInterface;
use Psr\Container\ContainerInterface;

class MessageFactory implements MessageFactoryInterface
{
    /** @var ContainerInterface */
    protected $container;

    protected const TYPE_IDENTIFIER_MAP = [
        'topic_identifier' => TopicMessage::class,
        'subscription_identifier' => SubscriptionMessage::class,
        'queue_identifier' => QueueMessage::class,
    ];

    public function __invoke(ContainerInterface $container) : self
    {
        $this->container = $container;

        return $this;
    }

    public function createMessage(array $messageParameters) : MessageInterface
    {
        /** @var MessageInterface $message */
        $message = call_user_func_array([$this->getMessageClassFromParameters($messageParameters), 'fromArray'], [$messageParameters]);

        /** @var ServiceFactoryInterface $serviceFactory */
        $serviceFactory = $this->container->get(ServiceFactoryInterface::class);

        $message->setService($serviceFactory->createFromMessage($message));

        return $message;
    }

    public function receiveMessageOrFail(array $messageParameters, array $receiveParameters = []) : MessageInterface
    {
        return $this->createReceivableMessage($messageParameters)->receiveOneOrFail($receiveParameters);
    }

    public function receiveMessages(array $messageParameters, array $receiveParameters = []) : array
    {
        return $this->createReceivableMessage($messageParameters)->receive($receiveParameters);
    }

    protected function createReceivableMessage(array $parameters) : ReceivableMessageInterface
    {
        $message = $this->createMessage($parameters);

        if (!is_a($message, ReceivableMessageInterface::class)) {
            throw new InvalidMessageParameterException('subscription_identifier|topic_identifier');
        }

        return $message;
    }

    protected function getMessageClassFromParameters(array $parameters) : string
    {
        $foundKeys = array_intersect_key(self::TYPE_IDENTIFIER_MAP, $parameters);

        if (empty($foundKeys)) {
            throw new InvalidMessageParameterException('type_identifier');
        }

        return self::TYPE_IDENTIFIER_MAP[current(array_keys($foundKeys))];
    }
}
