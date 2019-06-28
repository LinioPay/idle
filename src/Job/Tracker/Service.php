<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Tracker;

use LinioPay\Idle\Job\Job;

interface Service
{
    public function trackJob(Job $job) : void;
}
