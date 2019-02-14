<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue\Exception;

class InvalidMessageParameterException extends \Exception
{
    const MESSAGE = 'Invalid or missing queue message parameter, name: %s';

    public function __construct(string $parameterName)
    {
        parent::__construct(sprintf(self::MESSAGE, $parameterName));
    }
}
