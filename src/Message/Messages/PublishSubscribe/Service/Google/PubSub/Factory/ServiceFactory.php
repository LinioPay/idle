<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory;

use Google\Cloud\PubSub\PubSubClient;
use LinioPay\Idle\Message\Message;
use LinioPay\Idle\Message\Messages\Factory\DefaultServiceFactory;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service as PubSubService;
use LinioPay\Idle\Message\Service;
use Psr\Log\LoggerInterface;

class ServiceFactory extends DefaultServiceFactory
{
    public function createFromMessage(Message $message) : Service
    {
        $messageConfig = $this->getMessageConfig($message);

        $logger = $this->container->get(LoggerInterface::class);

        $client = new PubSubClient($messageConfig['parameters']['service']['client'] ?? []);

        return new PubSubService($client, $messageConfig, $logger);
    }
}
