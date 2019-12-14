<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

trait TrackableWorker
{
    abstract public function getTrackerData() : array;
}
