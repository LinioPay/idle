<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Message\TopicMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Message\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\Queue\Message\Message as QueueMessage;
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

    public function createMessage(array $parameters) : MessageInterface
    {
        $foundKeys = array_intersect_key(self::TYPE_IDENTIFIER_MAP, $parameters);

        if (empty($foundKeys)) {
            throw new InvalidMessageParameterException('type_identifier');
        }

        $class = self::TYPE_IDENTIFIER_MAP[current(array_keys($foundKeys))];

        /** @var MessageInterface $message */
        $message = call_user_func_array([$class, 'fromArray'], [$parameters]);

        /** @var ServiceFactoryInterface $serviceFactory */
        $serviceFactory = $this->container->get(ServiceFactoryInterface::class);

        $message->setService($serviceFactory->createFromMessage($message));

        return $message;
    }
}
