<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

use Exception;

class UnsupportedServiceOperationException extends Exception
{
    public const MESSAGE = 'Service %s does not support the %s operation';

    public function __construct(string $serviceIdentifier, string $operation)
    {
        parent::__construct(sprintf(self::MESSAGE, $serviceIdentifier, $operation));
    }
}
