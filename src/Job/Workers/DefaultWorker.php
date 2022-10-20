<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use LinioPay\Idle\Job\Worker;

abstract class DefaultWorker implements Worker
{
    /** @var bool Whether this worker can be instantiated without a factory or not */
    public static $skipFactory = false;

    /** @var array */
    protected $errors = [];

    /** @var array */
    protected $parameters = [];

    public function getErrors() : array
    {
        return $this->errors;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function getTrackerData() : array
    {
        return [];
    }

    public function setParameters(array $parameters) : void
    {
        $this->parameters = $parameters;
    }

    public function validateParameters() : void
    {
        // Override with custom validation of worker parameters
    }

    protected function setErrors(array $errors) : void
    {
        $this->errors = $errors;
    }
}
