<?php

declare(strict_types=1);

namespace LinioPay\Idle\Config\Exception;

class ConfigurationException extends \Exception
{
    const ENTITY_JOB = 'Job';
    const ENTITY_WORKER = 'Worker';
    const ENTITY_MESSAGE = 'Message';
    const ENTITY_SERVICE = 'Service';

    const MESSAGE = '%s %s is missing a proper %s configuration.';

    public function __construct(string $entityType, string $identifier, string $parameter)
    {
        parent::__construct(sprintf(self::MESSAGE, $entityType, $identifier, $parameter));
    }
}
