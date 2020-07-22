<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job;

interface TrackableWorker extends Worker
{
    /**
     * Retrieves data from a worker which we may wish to persist as part of the overall job details.
     */
    public function getTrackerData() : array;
}
