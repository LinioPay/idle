<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

interface Worker
{
    /**
     * Retrieve any errors caused by the execution of the worker.
     */
    public function getErrors() : array;

    /**
     * Retrieve the worker parameters.
     */
    public function getParameters() : array;

    /**
     * Define the parameters for the worker.
     */
    public function setParameters(array $parameters) : void;

    /**
     * Verifies the worker's parameters are valid.
     */
    public function validateParameters() : void;

    /**
     * Perform the worker's work.
     */
    public function work() : bool;
}
