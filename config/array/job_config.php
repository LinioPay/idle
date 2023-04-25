<?php

use LinioPay\Idle\Job\Jobs\MessageJob;
use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\Job\Workers\PublishSubscribe\AcknowledgeMessageWorker;
use LinioPay\Idle\Job\Workers\Queue\DeleteMessageWorker;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;

return [
    MessageJob::IDENTIFIER => [
        'class' => MessageJob::class,
        'parameters' => [
            QueueMessage::IDENTIFIER => [
                'my-queue' => [
                    'parameters' => [
                        'workers' => [
                            [
                                'type' => FooWorker::IDENTIFIER,
                                'parameters' => [
                                    'foo' => 'bar',
                                ],
                            ],
                            [
                                'type' => DeleteMessageWorker::IDENTIFIER,
                            ],
                        ],
                    ],
                ],
                'my-task-queue' => [
                    'parameters' => [
                        'workers' => [
                            [
                                'type' => FooWorker::IDENTIFIER,
                                'parameters' => [],
                            ],
                        ],
                    ],
                ],
            ],
            SubscriptionMessage::IDENTIFIER => [
                'my-subscription' => [
                    'parameters' => [
                        'workers' => [
                            [
                                'type' => FooWorker::IDENTIFIER,
                            ],
                            [
                                'type' => AcknowledgeMessageWorker::IDENTIFIER,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    SimpleJob::IDENTIFIER => [
        'class' => SimpleJob::class,
        'parameters' => [
            'supported' => [
                'my-simple-job' => [
                    'parameters' => [
                        'workers' => [
                            [
                                'type' => FooWorker::IDENTIFIER,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
