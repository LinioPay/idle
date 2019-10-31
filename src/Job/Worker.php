<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

interface Worker
{
    public function work() : bool;

    public function setParameters(array $parameters) : void;

    public function getErrors() : array;

    public function getParameters() : array;

    public function validateParameters() : void;
}
