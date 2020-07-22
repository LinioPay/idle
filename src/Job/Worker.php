<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

interface Worker
{
    /**
     * Perform the worker's work.
     */
    public function work() : bool;

    /**
     * Define the parameters for the worker.
     */
    public function setParameters(array $parameters) : void;

    /**
     * Retrieve any errors caused by the execution of the worker.
     */
    public function getErrors() : array;

    /**
     * Retrieve the worker parameters.
     */
    public function getParameters() : array;

    /**
     * Verifies the worker's parameters are valid.
     */
    public function validateParameters() : void;
}
