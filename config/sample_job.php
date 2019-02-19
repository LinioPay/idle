<?php

use LinioPay\Idle\Job\Jobs\QueueJob;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\Job\Jobs\SimpleJob;

return [
    QueueJob::IDENTIFIER => [
        'type' => QueueJob::class,
        'parameters' => []
    ],
    SimpleJob::IDENTIFIER => [
        'type' => SimpleJob::class,
        'parameters' => [
            'supported' => [
                FooWorker::IDENTIFIER => [
                    'type' => FooWorker::class,
                    'parameters' => [],
                ],
                // ...
            ]
        ]
    ]
];
