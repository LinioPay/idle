<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Output\Transformer;

use League\Fractal\TransformerAbstract;

class ListJobs extends TransformerAbstract
{
    public function transform(array $data) : array
    {
        if (
            !isset($data['jobs']) || !is_array($data['jobs'])
        ) {
            throw new \InvalidArgumentException('ListJobs transformer requires jobs');
        }

        $out = [];

        foreach ($data['jobs'] as $job) {
            $out[] = (new JobDetails())->transform($job);
        }

        return $out;
    }
}
