<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory;

use Aws\Sqs\SqsClient;
use LinioPay\Idle\Message\Message;
use LinioPay\Idle\Message\Messages\Factory\DefaultServiceFactory;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQSService;
use LinioPay\Idle\Message\Service;
use Psr\Log\LoggerInterface;

class ServiceFactory extends DefaultServiceFactory
{
    public function createFromMessage(Message $message) : Service
    {
        $messageConfig = $this->idleConfig->getMessageConfig($message);

        $logger = $this->container->get(LoggerInterface::class);

        $client = new SqsClient($messageConfig['parameters']['service']['client'] ?? []);

        return new SQSService($client, $messageConfig, $logger);
    }
}
