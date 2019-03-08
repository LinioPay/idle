<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use LinioPay\Idle\Job\Worker;

abstract class DefaultWorker implements Worker
{
    /** @var array */
    protected $parameters = [];

    /** @var array */
    protected $errors = [];

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters) : void
    {
        $this->validateParameters($parameters);

        $this->parameters = $parameters;
    }

    public function getErrors() : array
    {
        return $this->errors;
    }

    protected function setErrors(array $errors) : void
    {
        $this->errors = $errors;
    }

    public function validateParameters(array $parameters): void
    {
        // Override with custom validation of worker parameters
    }
}
