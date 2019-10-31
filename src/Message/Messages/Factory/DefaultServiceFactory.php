<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use LinioPay\Idle\Message\Message;
use LinioPay\Idle\Message\Service;
use LinioPay\Idle\Message\ServiceFactory as ServiceFactoryInterface;
use Psr\Container\ContainerInterface;
use Zend\Stdlib\ArrayUtils;

abstract class DefaultServiceFactory implements ServiceFactoryInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __invoke(ContainerInterface $container) : self
    {
        $this->container = $container;

        return $this;
    }

    abstract public function createFromMessage(Message $message) : Service;

    protected function getMessageConfig(Message $message)
    {
        $config = $this->container->get('config') ?? [];

        $messageTypeConfig = $config['idle']['message']['types'][$message->getIdleIdentifier()] ?? [];

        $default = $messageTypeConfig['default'] ?? [];

        $override = $messageTypeConfig['types'][$message->getSourceName()] ?? [];

        return ArrayUtils::merge($default, $override);
    }

    protected function getServiceConfig(string $serviceIdentifier)
    {
        $config = $this->container->get('config') ?? [];

        return $config['idle']['message']['service']['types'][$serviceIdentifier] ?? [];
    }
}
