<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

use Exception;
use LinioPay\Idle\Job\Job;

class InvalidJobParameterException extends Exception
{
    public const MESSAGE = 'Job %s did not receive a valid %s parameter.';

    public function __construct(Job $job, string $parameter)
    {
        parent::__construct(sprintf(self::MESSAGE, get_class($job), $parameter));
    }
}
