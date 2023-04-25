<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

use Exception;

class InvalidJobsException extends Exception
{
    public const MESSAGE = 'An invalid jobs array was provided.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
