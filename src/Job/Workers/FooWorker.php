<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

class FooWorker extends DefaultWorker
{
    public function work() : bool
    {
        return true;
    }
}
