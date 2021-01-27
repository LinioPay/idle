<?php

use LinioPay\Idle\Job\Jobs\MessageJob;
use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\Message\Messages\PublishSubscribe\TopicMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service as GoogleCloudTasks;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQS;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service as GooglePubSub;

return [
    'message' => [
        'service' => [
            'types' => [
                SQS::IDENTIFIER  => [
                    'class' => SQS::class,
                    'client' => [
                        'version' => 'latest',
                        'region' => getenv('AWS_REGION'),
                    ],
                ],
                GoogleCloudTasks::IDENTIFIER => [
                    'class' => GoogleCloudTasks::class,
                    'client' => [
                        'projectId' => 'my-project',
                        'location' => 'us-central1',
                    ],
                ],
                GooglePubSub::IDENTIFIER => [
                    'class' => GooglePubSub::class,
                    'client' => [
                        'projectId' => 'my-project',
                        'location' => 'us-central1',
                    ],
                ]
            ]
        ],
        'types' => [
            QueueMessage::IDENTIFIER => [
                'default' => [
                    'dequeue' => [
                        'parameters' => [],
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                    'queue' => [
                        'parameters' => [],
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                    'delete' => [
                        'parameters' => [],
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                    'parameters' => [
                        'service' => SQS::IDENTIFIER,
                    ],
                ],
                'service_default' => [
                    SQS::IDENTIFIER => [
                        'queue' => [
                            'parameters' => [
                                'DelaySeconds' => 5,
                            ],
                        ]
                    ],
                ],
                'types' => [
                    'my-queue' => [
                        'parameters' => [
                            // Inherit SQS as its service
                        ],
                    ],
                    'my-task-queue' => [
                        'parameters' => [
                            'service' => GoogleCloudTasks::IDENTIFIER,
                        ]
                    ]

                ]
            ],
            TopicMessage::IDENTIFIER => [
                'default' => [
                    'publish' => [
                        'parameters' => [],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my-topic' => [
                        'parameters' => [
                            // Inherit GooglePubSub as its service
                        ],
                    ]
                ]
            ],
            SubscriptionMessage::IDENTIFIER => [
                'default' => [
                    'pull' => [
                        'parameters' => [
                            //'maxMessages' => 1, // PubSub: Number of messages to retrieve
                        ],
                    ],
                    'acknowledge' => [
                        'parameters' => [],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my-subscription' => [
                        'parameters' => [
                            //'service' => GooglePubSub::IDENTIFIER,
                        ],
                    ]
                ]
            ],
        ],
    ],
    'job' => [
        'worker' => [
            'types' => [
                FooWorker::IDENTIFIER => [
                    'class' => FooWorker::class,
                ],
                DynamoDBTrackerWorker::IDENTIFIER  => [
                    'class' => DynamoDBTrackerWorker::class,
                    'client' => [
                        'version' => 'latest',
                        'region' => getenv('AWS_REGION'),
                    ],
                    'parameters' => [],
                ],
            ]
        ],
        'types' => [
            MessageJob::IDENTIFIER => [
                'class' => MessageJob::class,
                'parameters' => [
                    QueueMessage::IDENTIFIER => [
                        'my-queue' => [
                            'parameters' => [
                                'workers' => [
                                    [
                                        'type' => FooWorker::IDENTIFIER,
                                        'parameters' => [],
                                    ],
                                    [
                                        'type' => DynamoDBTrackerWorker::IDENTIFIER,
                                        'parameters' => [
                                            'table' => 'my_foo_queue_tracker_table',
                                        ]
                                    ]
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
                        ]
                    ],
                    SubscriptionMessage::IDENTIFIER => [
                        'my-subscription' => [
                            'parameters' => [
                                'workers' => [
                                    [
                                        'type' => FooWorker::IDENTIFIER,
                                        'parameters' => [],
                                    ],
                                ],
                            ],
                        ]
                    ],
                ],
            ],
            SimpleJob::IDENTIFIER  => [
                'class' => SimpleJob::class,
                'parameters' => [
                    'supported' => [
                        'my-simple-job' => [
                            'parameters' => [
                                'workers' => [
                                    [
                                        'type' => FooWorker::IDENTIFIER,
                                        'parameters' => [],
                                    ],
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ],
    ],
];
