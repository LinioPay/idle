<?php

use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service as GooglePubSub;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service as GoogleCloudTasks;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQS;

return [
    SQS::IDENTIFIER => [
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
    ],
];
