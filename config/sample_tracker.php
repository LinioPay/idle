<?php

use LinioPay\Idle\Job\Tracker\Service\DynamoDB\Service as DynamoDB;

return [
    'active_service' => DynamoDB::IDENTIFIER,
    'services' => [
        DynamoDB::IDENTIFIER  => [
            'type' => DynamoDB::class,
            'client' => [
                'version' => 'latest',
                'region' => getenv('AWS_REGION'),
            ],
        ],
    ]
];
