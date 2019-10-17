<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Tracker\Service\Factory;

use LinioPay\Idle\Job\Tracker\Service as ServiceInterface;

interface Service
{
    public function createTrackerService(string $class) : ServiceInterface;
}
