<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

interface Job
{
    public function process() : void;

    public function getErrors() : array;

    public function isSuccessful() : bool;

    public function getDuration() : float;

    public function getParameters() : array;

    public function setParameters(array $parameters = []) : void;

    public function isFinished() : bool;

    public function getTrackerData() : array;

    public function getTypeIdentifier() : string;
}
