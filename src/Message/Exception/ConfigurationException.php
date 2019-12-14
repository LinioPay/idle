<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

class ConfigurationException extends \Exception
{
    const MESSAGE = 'Message type %s is missing a proper configuration.';

    public function __construct(string $messageIdentifier)
    {
        parent::__construct(sprintf(self::MESSAGE, $messageIdentifier));
    }
}
