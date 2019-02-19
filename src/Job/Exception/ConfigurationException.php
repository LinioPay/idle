<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

class ConfigurationException extends \Exception
{
    const MESSAGE = 'Job %s is missing a proper configuration.';

    public function __construct(string $jobIdentifier)
    {
        parent::__construct(sprintf(self::MESSAGE, $jobIdentifier));
    }
}
