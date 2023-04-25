<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

use Exception;

class InvalidMessageParameterException extends Exception
{
    public const MESSAGE = 'Invalid or missing message parameter, name: %s';

    public function __construct(string $parameterName)
    {
        parent::__construct(sprintf(self::MESSAGE, $parameterName));
    }
}
