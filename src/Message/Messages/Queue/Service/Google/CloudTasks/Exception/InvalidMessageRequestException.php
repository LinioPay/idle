<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Exception;

use Exception;

class InvalidMessageRequestException extends Exception
{
    public const MESSAGE = 'CloudTask queue messages require a valid request attribute';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
