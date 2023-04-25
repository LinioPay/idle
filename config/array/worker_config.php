<?php

use LinioPay\Idle\Job\Workers\BazWorker;
use LinioPay\Idle\Job\Workers\FooWorker;
use LinioPay\Idle\Job\Workers\PublishSubscribe\AcknowledgeMessageWorker;
use LinioPay\Idle\Job\Workers\Queue\DeleteMessageWorker;

return [
    FooWorker::IDENTIFIER => [
        'class' => FooWorker::class,
    ],
    BazWorker::IDENTIFIER => [
        'class' => BazWorker::class,
    ],
    DeleteMessageWorker::IDENTIFIER => [
        'class' => DeleteMessageWorker::class,
    ],
    AcknowledgeMessageWorker::IDENTIFIER => [
        'class' => AcknowledgeMessageWorker::class,
    ],
];
