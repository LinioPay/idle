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
                SQS::IDENTIFIER  => [ // Define support for SQS
                    'class' => SQS::class,
                    'client' => [
                        'version' => 'latest',
                        'region' => getenv('AWS_REGION'),
                    ],
                ],
                GoogleCloudTasks::IDENTIFIER => [ // Define support for GoogleCloudTasks
                    'class' => GoogleCloudTasks::class,
                    'client' => [],
                    'projectId' => 'my-project',
                    'location' => 'us-central1',
                ],
                GooglePubSub::IDENTIFIER => [ // Define support for Google PubSub
                    'class' => GooglePubSub::class,
                    'client' => [
                        'projectId' => 'my-project',
                        'keyFilePath' => '/application/my-sandbox.json',
                    ],
                ]
            ]
        ],
        'types' => [
            QueueMessage::IDENTIFIER => [ // Define support for QueueMessage, utilized by queue services such as SQS and CloudTasks.
                'default' => [ // Default parameters shared amongst all QueueMessage
                    'dequeue' => [ // QueueMessage retrieval configuration
                        'parameters' => [
                            //'MaxNumberOfMessages' => 1, // SQS: The maximum number of messages to return.
                        ],
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                    'queue' => [ // QueueMessage addition configuration
                        'parameters' => [
                            //'DelaySeconds' => 0, // SQS: The number of seconds (0 to 900 - 15 minutes) to delay a specific message.
                        ],
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                    'delete' => [ // QueueMessage deletion configuration
                        'parameters' => [],
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                    'parameters' => [ // General QueueMessage parameters
                        'service' => SQS::IDENTIFIER, // Default service for all configured QueueMessages
                    ],
                ],
                'types' => [ // Define the queues where the QueueMessages are coming from
                    'my-queue' => [
                        'parameters' => [
                            // Inherit SQS as its service
                        ],
                    ],
                    'my-task' => [
                        'parameters' => [
                            'service' => GoogleCloudTasks::IDENTIFIER, // Override the service to use Google CloudTasks instead of AWS SQS
                        ]
                    ]

                ]
            ],
            TopicMessage::IDENTIFIER => [ // Define support for TopicMessage, utilized by Publish Subscribe services such as PubSub.
                'default' => [ // Default parameters shared amongst all TopicMessages
                    'publish' => [ // TopicMessage publishing configuration
                        'parameters' => [],
                    ],
                    'parameters' => [  // General TopicMessage parameters
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [ // Define supported topics and their overrides
                    'my-topic' => [ // The name/identifier of our topic
                        'parameters' => [
                            // Will inherit GooglePubSub as the service
                        ],
                    ]
                ]
            ],
            SubscriptionMessage::IDENTIFIER => [ // Define support for SubscriptionMessage, utilized by Publish Subscribe services such as PubSub.
                'default' => [ // Default parameters shared amongst all SubscriptionMessages
                    'pull' => [ // SubscriptionMessage pulling configuration
                        'parameters' => [
                            //'maxMessages' => 1, // PubSub: Number of messages to retrieve
                        ],
                    ],
                    'acknowledge' => [ // SubscriptionMessage acknowledge configuration
                        'parameters' => [],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER, // Define which service from the configured list below will be used
                    ],
                ],
                'types' => [
                    'my-subscription' => [ // The identifier of our subscription (Should match the job configuration under MessageJob)
                        'parameters' => [
                            //'service' => GooglePubSub::IDENTIFIER,
                        ],
                    ]
                ]
            ],
        ],
    ],
    'job' => [
        'worker' => [ // Define workers
            'types' => [
                FooWorker::IDENTIFIER => [ // Define support for the FooWorker worker as well as any relevant parameters
                    'class' => FooWorker::class,
                ],
                DynamoDBTrackerWorker::IDENTIFIER  => [ // Define support for the DynamoDBTrackerWorker worker as well as any relevant parameters
                    'class' => DynamoDBTrackerWorker::class,
                    'client' => [
                        'version' => 'latest',
                        'region' => getenv('AWS_REGION'),
                    ],
                    'parameters' => [],
                ],
            ]
        ],
        'types' => [ // Define jobs and their workers
            MessageJob::IDENTIFIER => [ // Define the MessageJob job which is responsible for executing jobs based on received messages
                'class' => MessageJob::class,
                'parameters' => [
                    QueueMessage::IDENTIFIER => [ // Define support for running MessageJobs when receiving QueueMessages
                        'my-queue' => [ // The queue which will trigger this job, in this case my-queue on SQS which was defined previously in the message section
                            'parameters' => [
                                'workers' => [ // Define all the workers which will be processed when a QueueMessage is received from the queue 'my-queue'.
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Sample worker
                                        'parameters' => [],
                                    ],
                                    [
                                        'type' => DynamoDBTrackerWorker::IDENTIFIER, // Persist job details to DynamoDB under the table 'my_foo_queue_tracker_table'
                                        'parameters' => [
                                            'table' => 'my_foo_queue_tracker_table',
                                        ]
                                    ]
                                ],
                            ],
                        ],
                        'my-task' => [ // The queue which will trigger this job, in this case my-task on CloudTasks which was defined previously in the message section
                            'parameters' => [
                                'workers' => [ // Define all the workers which will be processed when a QueueMessage is received from the queue 'my-task'.
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Sample worker
                                        'parameters' => [],
                                    ],
                                ],
                            ],
                        ]
                    ],
                    SubscriptionMessage::IDENTIFIER => [ // Define support for running MessageJobs when receiving SubscriptionMessages
                        'my-subscription' => [  // The subscription which will trigger this job, in this case my-subscription on CloudTasks which was defined previously in the message section
                            'parameters' => [
                                'workers' => [ // Define all the workers which will be processed when a SubscriptionMessage is received from the subscription 'my-subscription'.
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Sample worker
                                        'parameters' => [],
                                    ],
                                ],
                            ],
                        ]
                    ],
                ],
            ],
            SimpleJob::IDENTIFIER  => [ // Define the SimpleJob job
                'class' => SimpleJob::class,
                'parameters' => [
                    'supported' => [
                        'my-simple-job' => [ // Define a SimpleJob with the name 'my-simple-job'.
                            'parameters' => [
                                'workers' => [ // Define the workers which will be processed when 'my-simple-job' runs
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Sample worker
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
