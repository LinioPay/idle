<?php

declare(strict_types=1);

namespace LinioPay\Idle\Config\Exception;

use Exception;

class ConfigurationException extends Exception
{
    public const ENTITY_JOB = 'Job';
    public const ENTITY_MESSAGE = 'Message';
    public const ENTITY_SERVICE = 'Service';
    public const ENTITY_WORKER = 'Worker';

    public const MESSAGE = '%s %s is missing a proper %s configuration.';

    public function __construct(string $entityType, string $identifier, string $parameter)
    {
        parent::__construct(sprintf(self::MESSAGE, $entityType, $identifier, $parameter));
    }
}
