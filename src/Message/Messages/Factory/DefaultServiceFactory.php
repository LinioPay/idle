<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Factory;

use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Message\Message;
use LinioPay\Idle\Message\Service;
use LinioPay\Idle\Message\ServiceFactory as ServiceFactoryInterface;
use Psr\Container\ContainerInterface;

abstract class DefaultServiceFactory implements ServiceFactoryInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var IdleConfig */
    protected $idleConfig;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->loadIdleConfig();
    }

    protected function loadIdleConfig() : void
    {
        $this->idleConfig = $this->container->get(IdleConfig::class);
    }

    abstract public function createFromMessage(Message $message) : Service;
}
