<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use LinioPay\Idle\Job\TrackableWorker as TrackableWorkerInterface;

class FooWorker extends DefaultWorker implements TrackableWorkerInterface
{
    use TrackableWorker;

    const IDENTIFIER = 'foo';

    public static $skipFactory = true;

    public function work() : bool
    {
        return true;
    }
}
