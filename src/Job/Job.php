<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

use Ramsey\Uuid\Uuid;

interface Job
{
    public function process() : void;

    public function validateConfig() : void;

    public function getErrors() : array;

    public function isSuccessful() : bool;

    public function getDuration() : float;

    public function getParameters() : array;

    public function setParameters(array $parameters = []) : void;

    public function validateParameters() : void;

    public function isFinished() : bool;

    public function getTrackerData() : array;

    public function getTypeIdentifier() : string;

    public function getJobId() : Uuid;

    public function setContext(array $data) : void;

    public function addContext(string $key, $value) : void;

    public function getContextEntry(string $key);

    public function setOutput(array $data) : void;

    public function addOutput(string $key, $value) : void;

    public function getOutput() : array;
}
