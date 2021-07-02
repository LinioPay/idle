<?php

use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service as GooglePubSub;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\TopicMessage;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service as GoogleCloudTasks;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQS;

return [
    QueueMessage::IDENTIFIER        => [
        'default'         => [
            'parameters' => [
                'service' => SQS::IDENTIFIER,
            ],
        ],
        'service_default' => [
            SQS::IDENTIFIER => [
                'queue'      => [
                    'parameters' => [
                        'DelaySeconds' => 5,
                    ],
                ],
                'dequeue'    => [
                    'parameters' => [
                        'MaxNumberOfMessages' => 3
                    ],
                ],
                'delete'     => [
                    'parameters' => [],
                ],
            ],
        ],
        'types'           => [
            'my-queue'      => [
                'queue'      => [
                    'parameters' => [
                        'DelaySeconds' => 10,
                    ],
                ],
            ],
            'my-task-queue' => [
                'parameters' => [
                    'service' => GoogleCloudTasks::IDENTIFIER,
                ],
            ],
        ],
    ],
    TopicMessage::IDENTIFIER        => [
        'default' => [
            'parameters' => [
                'service' => GooglePubSub::IDENTIFIER,
            ],
        ],
        'types'   => [
            'my-topic' => [
                'parameters' => [
                    // Inherit GooglePubSub as its service
                ],
            ],
        ],
    ],
    SubscriptionMessage::IDENTIFIER => [
        'default' => [
            'parameters'  => [
                'service' => GooglePubSub::IDENTIFIER,
            ],
        ],
        'service_default' => [
            GooglePubSub::IDENTIFIER => [
                'pull'        => [
                    'parameters' => [
                        'maxMessages' => 3,
                    ],
                ],
            ]
        ],
        'types'   => [
            'my-subscription' => [
                'parameters' => [],
            ],
        ],
    ],
];
