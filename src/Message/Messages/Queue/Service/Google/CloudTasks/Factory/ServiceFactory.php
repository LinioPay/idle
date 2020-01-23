<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Factory;

use Google\Cloud\Tasks\V2\CloudTasksClient;
use LinioPay\Idle\Message\Message;
use LinioPay\Idle\Message\Messages\Factory\DefaultServiceFactory;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service as CloudTasksService;
use LinioPay\Idle\Message\Service;
use Psr\Log\LoggerInterface;

class ServiceFactory extends DefaultServiceFactory
{
    public function createFromMessage(Message $message) : Service
    {
        $messageConfig = $this->getMessageConfig($message);

        $logger = $this->container->get(LoggerInterface::class);

        $client = new CloudTasksClient($messageConfig['parameters']['service']['client'] ?? []);

        return new CloudTasksService($client, $messageConfig, $logger);
    }
}
