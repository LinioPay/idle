<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use LinioPay\Idle\Job\TrackableWorker as TrackableWorkerInterface;

class BazWorker extends DefaultWorker implements TrackableWorkerInterface
{
    use TrackableWorker;

    public const IDENTIFIER = 'baz';

    /** @var string */
    protected $bazDependency;

    public function __construct(string $bazDependency)
    {
        $this->bazDependency = $bazDependency;
    }

    public function work() : bool
    {
        return true;
    }
}
