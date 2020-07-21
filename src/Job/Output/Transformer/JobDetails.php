<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Output\Transformer;

use League\Fractal\TransformerAbstract;
use LinioPay\Idle\Job\Job;

class JobDetails extends TransformerAbstract
{
    public function transform(Job $job) : array
    {
        return [
            'finished' => $job->isFinished(),
            'successful' => $job->isSuccessful(),
            'duration' => $job->getDuration(),
            'output' => $job->getOutput(),
            'errors' => $job->getErrors(),
        ];
    }
}
