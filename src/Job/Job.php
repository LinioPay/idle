<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

use Ramsey\Uuid\UuidInterface;

interface Job
{
    /**
     * Adds an entry to the context.  Useful to share data between workers.
     */
    public function addContext(string $key, $value) : void;

    /**
     * Adds an entry to the output data.  Useful to specify data which the job will output.
     */
    public function addOutput(string $key, $value) : void;

    /**
     * Retrieve the value of specific key in the context. Useful to share data between workers.
     */
    public function getContextEntry(string $key);

    /**
     * Overall duration of job execution.
     */
    public function getDuration() : float;

    /**
     * Retrieve an array of error messages which occurred while processing the job.
     */
    public function getErrors() : array;

    /**
     * Retrieve the id for the job.
     */
    public function getJobId() : UuidInterface;

    /**
     * Retrieves the output data.
     */
    public function getOutput() : array;

    /**
     * Retrieve all job parameters.
     */
    public function getParameters() : array;

    /**
     * Retrieve data which we may wish to persist.
     */
    public function getTrackerData() : array;

    /**
     * Retrieve the type identifier for the job.
     */
    public function getTypeIdentifier() : string;

    /**
     * Whether the job finished executing or not.
     */
    public function isFinished() : bool;

    /**
     * Whether the job completed successfully or not.
     */
    public function isSuccessful() : bool;

    /**
     * Begin executing the job.
     */
    public function process() : void;

    /**
     * Sets and replaces the entire context.  Useful to share data between workers.
     */
    public function setContext(array $data) : void;

    /**
     * Sets and replaces the entire output data.  Useful to specify data which the job will output.
     */
    public function setOutput(array $data) : void;

    /**
     * Set job parameters.
     */
    public function setParameters(array $parameters = []) : void;

    /**
     * Verifies the parameters for the job are valid.
     */
    public function validateParameters() : void;
}
