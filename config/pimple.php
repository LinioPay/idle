<?php

use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container as PSRContainer;
use LinioPay\Idle\Job\JobFactory as JobFactoryInterface;
use LinioPay\Idle\Job\Jobs\Factory\JobFactory;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;
use LinioPay\Idle\Job\Workers\Factory\WorkerFactory;
use LinioPay\Idle\Job\Jobs\MessageJob as MessageJobInterface;
use LinioPay\Idle\Job\Jobs\Factory\MessageJobFactory;
use LinioPay\Idle\Job\Jobs\SimpleJob as SimpleJobInterface;
use LinioPay\Idle\Job\Jobs\Factory\SimpleJobFactory;
use LinioPay\Idle\Message\MessageFactory as MessageFactoryInterface;
use LinioPay\Idle\Message\Messages\Factory\MessageFactory;
use LinioPay\Idle\Message\ServiceFactory as ServiceFactoryInterface;
use LinioPay\Idle\Message\Messages\Factory\ServiceFactory;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service as PubSubService;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory\ServiceFactory as PubSubServiceFactory;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service as SQSService;
use LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory\ServiceFactory as SQSServiceFactory;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service as CloudTasksService;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Factory\ServiceFactory as CloudTasksServiceFactory;
use LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker;
use LinioPay\Idle\Job\Workers\Factory\DynamoDBTrackerWorkerFactory;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler as MonologStreamHandler;


$container = new PimpleContainer();

// Config
$container['config'] = [
    'idle' => require __DIR__ . '/config.php'
];

// Logs
$container[LoggerInterface::class] = function() {
    $log = new Logger('idle');
    $log->pushHandler(new MonologStreamHandler('php://stdout', Logger::WARNING));
    return $log;
};

// PSR11 Container Wrapper for Pimple
$container[PSRContainer::class] = function(PimpleContainer $container) {
    return new PSRContainer($container);
};

// Idle
$container[MessageFactoryInterface::class] = function(PimpleContainer $container) {
    return (new MessageFactory())($container[PSRContainer::class]);
};
$container[ServiceFactoryInterface::class] = function(PimpleContainer $container) {
    return (new ServiceFactory())($container[PSRContainer::class]);
};
$container[JobFactoryInterface::class] = function(PSRContainer $container) {
    return (new JobFactory())($container[PSRContainer::class]);
};
$container[MessageJobInterface::class] = function(PimpleContainer $container) {
    return (new MessageJobFactory())($container[PSRContainer::class]);
};
$container[SimpleJobInterface::class] = function(PimpleContainer $container) {
    return (new SimpleJobFactory())($container[PSRContainer::class]);
};
$container[WorkerFactoryInterface::class] = function(PimpleContainer $container) {
    return (new WorkerFactory())($container[PSRContainer::class]);
};

// Services
$container[SQSService::class] = function(PimpleContainer $container) {
    return (new SQSServiceFactory())($container[PSRContainer::class]);
};
$container[CloudTasksService::class] = function(PimpleContainer $container) {
    return (new CloudTasksServiceFactory())($container[PSRContainer::class]);
};
$container[PubSubService::class] = function(PimpleContainer $container) {
    return (new PubSubServiceFactory())($container[PSRContainer::class]);
};

// Workers
$container[DynamoDBTrackerWorker::class] = function(PimpleContainer $container) {
    return (new DynamoDBTrackerWorkerFactory())($container[PSRContainer::class]);
};
