<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use Laminas\Stdlib\ArrayUtils;
use LinioPay\Idle\Message\Exception\ConfigurationException;
use LinioPay\Idle\Message\Message;
use LinioPay\Idle\Message\Service;
use LinioPay\Idle\Message\ServiceFactory as ServiceFactoryInterface;
use Psr\Container\ContainerInterface;

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

        $messageServiceIdentifier = isset($override['parameters']['service'])
            ? $override['parameters']['service']
            : $default['parameters']['service'] ?? '';

        $serviceDefault = $messageTypeConfig['service_default'][$messageServiceIdentifier] ?? [];

        $mergedDefaultConfig = ArrayUtils::merge($default, $serviceDefault);
        $mergedConfig = ArrayUtils::merge($mergedDefaultConfig, $override);

        // Inject service config
        $mergedConfig['parameters']['service'] = $this->getServiceConfig($config, $message, $messageServiceIdentifier);

        return $mergedConfig;
    }

    protected function getServiceConfig(array $config, Message $message, string $serviceIdentifier)
    {
        if (!isset($config['idle']['message']['service']['types'][$serviceIdentifier]['class'])) {
            throw new ConfigurationException($message->getIdleIdentifier());
        }

        return $config['idle']['message']['service']['types'][$serviceIdentifier] ?? [];
    }
}
