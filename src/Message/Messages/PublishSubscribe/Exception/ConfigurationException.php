<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Exception;

class ConfigurationException extends \Exception
{
    const TYPE_TOPIC = 'topic';
    const TYPE_SUBSCRIPTION = 'subscription';

    const MESSAGE = 'Encountered an invalid configuration for %s %s.';

    public function __construct(string $entityName, string $type)
    {
        parent::__construct(sprintf(self::MESSAGE, $type, $entityName));
    }
}
