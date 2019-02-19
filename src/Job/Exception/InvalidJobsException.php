<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

use Exception;

class InvalidJobsException extends Exception
{
    const MESSAGE = 'Transformer requires a jobs array in order to transform.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
