<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

use Exception;
use LinioPay\Idle\Job\Worker;

class InvalidWorkerParameterException extends Exception
{
    const MESSAGE = 'Worker %s did not receive a valid %s parameter.';

    public function __construct(Worker $worker, string $parameter)
    {
        parent::__construct(sprintf(self::MESSAGE, get_class($worker), $parameter));
    }
}
