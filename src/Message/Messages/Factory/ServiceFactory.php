<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Service as ServiceInterface;
use LinioPay\Idle\Message\ServiceFactory as ServiceFactoryInterface;

class ServiceFactory extends DefaultServiceFactory
{
    public function createFromMessage(MessageInterface $message) : ServiceInterface
    {
        $messageConfig = $this->idleConfig->getMessageConfig($message);

        /** @var ServiceFactoryInterface $factory */
        $factory = $this->container->get($messageConfig['parameters']['service']['class']);

        return $factory->createFromMessage($message);
    }
}
