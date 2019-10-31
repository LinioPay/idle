<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

interface TrackableWorker extends Worker
{
    public function getTrackerData() : array;
}
