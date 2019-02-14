<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue\Exception;

class ConfigurationException extends \Exception
{
    const TYPE_WORKER = 'worker';
    const TYPE_QUEUE = 'queue';
    const TYPE_DEQUEUE = 'dequeue';
    const TYPE_DELETE = 'delete';

    const MESSAGE = 'Queue %s is missing a proper %s configuration.';

    public function __construct(string $queueName, string $parameterName)
    {
        parent::__construct(sprintf(self::MESSAGE, $queueName, $parameterName));
    }
}
