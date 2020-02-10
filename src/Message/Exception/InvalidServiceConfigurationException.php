<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

class InvalidServiceConfigurationException extends \Exception
{
    const MESSAGE = 'Invalid service configuration for %s parameter %s';

    public function __construct(string $serviceIdentifier, string $parameterName)
    {
        parent::__construct(sprintf(self::MESSAGE, $serviceIdentifier, $parameterName));
    }
}
