<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue\Service\Factory;

use LinioPay\Idle\Queue\Service;
use Psr\Container\ContainerInterface;

class ServiceFactory
{
    public function __invoke(ContainerInterface $container) : Service
    {
        $idleConfig = $container->get('queue-config');

        $activeService = $idleConfig['active_service'] ?? '';

        return $container->get($idleConfig['services'][$activeService]['type'] ?? '');
    }
}
