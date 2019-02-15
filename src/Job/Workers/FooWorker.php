<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

class FooWorker extends DefaultWorker
{
    const IDENTIFIER = 'foo';

    public function work() : bool
    {
        return true;
    }

    public function validateParameters(array $parameters) : void
    {
        return;
    }
}
