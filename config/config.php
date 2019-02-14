<?php

use LinioPay\Idle\Queue\Service;
use LinioPay\Idle\Queue\Service\SQS\Service as SQS;
use LinioPay\Idle\Job\Workers\FooWorker;

return [
    'active_service' => SQS::IDENTIFIER,
    'services' => [
        SQS::IDENTIFIER  => [
            'type' => SQS::class,
            'client' => [
                'version' => 'latest',
                'region' => 'us-east-1',
            ],
            'queues' => [
                'default' => [
                    'dequeue' => [
                        'parameters' => [ // Configure behavior for when retrieving messages
                            'MaxNumberOfMessages' => 1, // The maximum number of messages to return. Amazon SQS never returns more messages than this value but may return fewer. Values can be from 1 to 10.
                            'VisibilityTimeout' => 30, // The duration (in seconds) that the received messages are hidden from subsequent retrieve requests after being retrieved by a ReceiveMessage request.
                            'WaitTimeSeconds' => 2, // The duration (in seconds) for which the call will wait for a message to arrive in the queue before returning. If a message is available, the call will return sooner than WaitTimeSeconds.
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'queue' => [
                        'parameters' => [ // Configure behavior for when adding a new message
                            'DelaySeconds' => 0, // The number of seconds (0 to 900 - 15 minutes) to delay a specific message. Messages with a positive DelaySeconds value become available for processing after the delay time is finished. If you don't specify a value, the default value for the queue applies.
                        ],
                        'attributes' => [ // Configure attributes for newly created queues
                            'DelaySeconds' => 0, // The time in seconds that the delivery of all messages in the queue will be delayed. An integer from 0 to 900 (15 minutes).
                            'MessageRetentionPeriod' => 1209600, // The number of seconds Amazon SQS retains a message. Integer representing seconds, from 60 (1 minute) to 1209600 (14 days).
                            'ReceiveMessageWaitTimeSeconds' => 0, // The time for which a ReceiveMessage call will wait for a message to arrive. An integer from 0 to 20 (seconds).
                            'VisibilityTimeout' => 30, // The visibility timeout for the queue. An integer from 0 to 43200 (12 hours). The default for this attribute is 30.
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'delete' => [
                        'enabled' => false,
                        'parameters' => [
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'worker' => [
                        //'type' => '', Must be overriden
                        'parameters' => [],
                    ]
                ],
                Service::FOO_QUEUE => [
                    'worker' => [
                        'type' => FooWorker::class,
                    ]
                ]
            ]
        ],
    ]
];
