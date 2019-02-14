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
