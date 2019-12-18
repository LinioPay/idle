<?php

use LinioPay\Idle\Job\Jobs\MessageJob;
use LinioPay\Idle\Job\Jobs\SimpleJob;
use LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\Message\Messages\PublishSubscribe\TopicMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQS;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service as GooglePubSub;

return [
    'job' => [
        'types' => [
            MessageJob::IDENTIFIER => [
                'class' => MessageJob::class,
                'parameters' => [
                    QueueMessage::IDENTIFIER => [
                        'foo_queue' => [
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
                        ]
                    ],
                    SubscriptionMessage::IDENTIFIER => [
                        'foo_subscription' => [
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
                        'my_simple_job' => [
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
        ]
    ],
    'message' => [
        'types' => [
            QueueMessage::IDENTIFIER => [
                'default' => [
                    'dequeue' => [
                        'parameters' => [ // Configure behavior for when retrieving messages
                            'MaxNumberOfMessages' => 1, // The maximum number of messages to return. Amazon SQS never returns more messages than this value but may return fewer. Values can be from 1 to 10.
                            //'VisibilityTimeout' => 30, // The duration (in seconds) that the received messages are hidden from subsequent retrieve requests after being retrieved by a ReceiveMessage request.
                            //'WaitTimeSeconds' => 2, // The duration (in seconds) for which the call will wait for a message to arrive in the queue before returning. If a message is available, the call will return sooner than WaitTimeSeconds.
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'queue' => [
                        'parameters' => [ // Configure behavior for when adding a new message
                            //'DelaySeconds' => 0, // The number of seconds (0 to 900 - 15 minutes) to delay a specific message. Messages with a positive DelaySeconds value become available for processing after the delay time is finished. If you don't specify a value, the default value for the queue applies.
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'delete' => [
                        'parameters' => [ // Configure behavior for when deleting a message
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'parameters' => [
                        'service' => SQS::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my-queue' => [
                        'parameters' => [
                            //'service' => SQS::IDENTIFIER,
                        ],
                    ]
                ]
            ],
            TopicMessage::IDENTIFIER => [
                'default' => [
                    'publish' => [
                        'parameters' => [],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my-topic' => [
                        'parameters' => [
                            //'service' => GooglePubSub::IDENTIFIER,
                        ],
                    ]
                ]
            ],
            SubscriptionMessage::IDENTIFIER => [
                'default' => [
                    'pull' => [
                        'parameters' => [
                            'maxMessages' => 1,
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my-subscription' => [
                        'parameters' => [],
                    ]
                ]
            ],
        ],
        'service' => [
            'types' => [
                SQS::IDENTIFIER  => [
                    'class' => SQS::class,
                    'client' => [
                        'version' => 'latest',
                        'region' => getenv('AWS_REGION'),
                    ],
                ],
                GooglePubSub::IDENTIFIER => [
                    'class' => GooglePubSub::class,
                    'client' => [
                        'projectId' => 'foo-project',
                        'keyFilePath' => '/application/foo-sandbox.json',
                    ],
                ]
            ]
        ],
    ],
];
