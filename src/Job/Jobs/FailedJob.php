<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

class FailedJob extends DefaultJob
{
    /** @var array */
    protected $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        $this->finished = true;
        $this->successful = false;
    }

    public function process() : void
    {
        // Nothing to do
    }

    public function getErrors() : array
    {
        return $this->errors;
    }
}
