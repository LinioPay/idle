<?php

use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\Job\Jobs\SimpleJob;

return [
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
