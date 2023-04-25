<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

use Exception;
use Throwable;

class FailedReceivingMessageException extends Exception
{
    public const MESSAGE = 'Idle failed to receive a %s message from %s utilizing the %s service.';

    public function __construct(string $serviceIdentifier, string $resourceType, string $resourceIdentifier, Throwable $previous = null)
    {
        $message = sprintf(self::MESSAGE, $resourceType, $resourceIdentifier, $serviceIdentifier);

        parent::__construct($message, 0, $previous);
    }
}
