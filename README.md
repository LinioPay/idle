![](https://github.com/LinioPay/idle/workflows/Continuous%20Integration/badge.svg)

# Idle

Idle is a package for managing Job and Messaging systems.  The two aspects work in harmony to make message queueing and job processing breeze.

## Installing Idle

### Composer
The recommended way to install Idle is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, install the latest stable version of Idle:

```bash
php composer.phar require liniopay/idle
```

### Preparing the Config and Container
Idle is highly flexible and allows any component to be swapped out for a custom version.  This does 
mean we need to do some work up front to make our lives easier later. Luckily, this is relatively 
simple, and we provide an example using Pimple Container.  If you're using another container, the 
same concepts should apply.

#### Copy the Idle Config + Container 
Idle ships with a sample configuration file to demonstrate all configurable options.  The file is located at `vendor/liniopay/idle/config/config.php`.

Copy the files below to your application's config directory.

```bash
mkdir -p config/idle;
cp vendor/liniopay/idle/config/config.php config/idle/config.php
cp vendor/liniopay/idle/config/pimple.php config/idle/service.php
```

#### Sample Container (config/pimple.php)

Below is a look at the sample container setup for pimple.  Keep in mind many of these factories are optional, for example, if you're not using SimpleJob, or PubSub Service, etc, you don't need to add them to the container.

```php
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
```

#### Container Setup Details

##### Add Config
Add a `config` array to the application container.  Within this `config` array provide an `idle` key containing the full Idle configuration.

##### Register Logger Factory
Idle also assumes your application container has a registered `Psr\Log\LoggerInterface::class` which returns a concrete implementation of the `LoggerInterface` logger.
```php
[
    Psr\Log\LoggerInterface::class => \Foo\App\Application\Logger\Factory\LoggerFactory::class,
];
```

##### Register Job and Worker Factories
Add the Job and Worker factories to your container by registering each interface namespace to its corresponding implementation.
```php
[
    \LinioPay\Idle\Job\JobFactory::class => \LinioPay\Idle\Job\Jobs\Factory\JobFactory::class,
    \LinioPay\Idle\Job\WorkerFactory::class => \LinioPay\Idle\Job\Workers\Factory\WorkerFactory::class,
    \LinioPay\Idle\Job\Jobs\MessageJob::class => \LinioPay\Idle\Job\Jobs\Factory\MessageJobFactory::class,
    \LinioPay\Idle\Job\Jobs\SimpleJob::class => \LinioPay\Idle\Job\Jobs\Factory\SimpleJobFactory::class,
];
```

##### Register Message and Service Factories
Add the Message and Service factories to your container by registering each interface namespace to its corresponding implementation.

```php
[
    \LinioPay\Idle\Message\MessageFactory::class => \LinioPay\Idle\Message\Messages\Factory\MessageFactory::class,
    \LinioPay\Idle\Message\ServiceFactory::class => \LinioPay\Idle\Message\Messages\Factory\ServiceFactory::class,
];
```

##### Register Optional Factories
Add any optional factories for specific services or custom workers which your application utilizes.  For example, if you will only be utilizing PubSub, you do not need to define the SQS service.
```php
[
    \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory\ServiceFactory::class,
    \LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Factory\ServiceFactory::class,
    \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::class => \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory\ServiceFactory::class,
    \LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker::class => \LinioPay\Idle\Job\Workers\Factory\DynamoDBTrackerWorkerFactory::class,
];
```

Idle should now be ready to use!

## Messaging

The messaging component allows us to interact with messaging services.  

- Queue
    - A `Queue` based implementation entails creating a message which contains data you will need at a later date.  The message is then sent to a queueing service which will hold it in a `queue` until it is retrieved.
    - Idle currently ships with `AWS SQS` and `Google CloudTasks`, as queueing services. It is very simple to add adapters for other services by implementing the corresponding Service interface.
    - Idle utilizes `QueueMessage` to manage these type of messages and facilitate communication to the corresponding service. 
- Publish/Subscribe
    - A `Publish/Subscribe` implementation is similar to a queue based implementation, but allows for more flexibility when retrieving messages.  From an architectural point of view it consists of one `topic` and one or more `subscriptions`. When a message is sent to a given `topic`, it will forward the message to each of its `subscription(s)`.  Each of the `subscription(s)` will then process the message in their own way.
    - Idle currently ships with `Google PubSub` as a `Publish/Subscribe` service.
    - Idle utilizes two main types of message for dealing with Publish/Subscribe: 
        - `TopicMessage` which can be published to a `topic`.
        - `SubscriptionMessage` which can be obtained via pull or push from a `subscription`).  

Below is an example of a minimally configured queue with the name of "my-queue", and which utilizes SQS.

```php
[
    'message' => [
        'service' => [
            'types' => [
                SQS::IDENTIFIER  => [ // Define support for SQS
                    'class' => SQS::class,
                    'client' => [], // Grab any client initialization options from environment variables
                ],
            ]
        ],
        'types' => [
            QueueMessage::IDENTIFIER => [
                'types' => [ // Define the actual queues we will be working with
                    'my-queue' => [ // The name of the queue in SQS
                        'parameters' => [
                            'service' => SQS::IDENTIFIER,
                        ],
                    ],
                ]
            ],
        ],
    ],
];
```
The previous config was the absolute minimum configuration needed for you to be able to queue or dequeue a message from "my-queue" in SQS.  However, this is too easy, lets make it a bit more complex.  

Let's say we're migrating to GCP and temporarily we wish to configure our application to have one queue in SQS (AWS) and another queue in CloudTasks (GCP).  Without Idle, this would be a pain.. with idle, its a few more lines:

```php
[
    'message' => [
        'service' => [
            'types' => [
                SQS::IDENTIFIER  => [ // Define support for SQS
                    'class' => SQS::class,
                    'client' => [ // Specify client options
                        'version' => 'latest',
                        'region' => 'us-east-1',
                    ],
                ],
                GoogleCloudTasks::IDENTIFIER => [ // Define support for GoogleCloudTasks
                    'class' => GoogleCloudTasks::class,
                    'client' => [ // Or you can leave it empty to grab from environment variables
                        'project' => 'my-project',
                        'location' => 'us-central1',       
                    ],
                ],
            ]
        ],
        'types' => [
            QueueMessage::IDENTIFIER => [ // Define support for QueueMessages
                'default' => [ // Base configuration for ALL QueueMessages
                    'parameters' => [ // General QueueMessage parameters
                        'service' => SQS::IDENTIFIER, // Define a default service for ALL QueueMessages
                    ],
                ],
                'service_default' => [ // Service Specific Defaults
                    SQS::IDENTIFIER => [ // SQS specific overrides
                        'queue' => [ // Configuration for when we are performing a 'queue' action (adding a message)
                            'parameters' => [
                                'DelaySeconds' => 5, // All SQS QueueMessages will have a 5 second delay
                            ],
                        ],
                    ],
                ],
                'types' => [ // Define the actual queues we will be working with
                    'my-queue' => [ // The name of the queue
                        'queue' => [ // Configuration for when we are performing a 'queue' to 'my-queue'
                            'parameters' => [
                                // Inherits DelaySeconds of 5 seconds from SQS service default without us having to specify it
                            ],
                        ],
                        'parameters' => [
                            // Inherit SQS as its service from 'default' without us having to specify it
                        ],
                    ],
                    'my-other-queue' => [ // The name of the queue
                        'queue' => [ // Configuration for when we are performing a 'queue' to 'my-queue'
                            'parameters' => [
                                // Inherit DelaySeconds of 5
                                'DelaySeconds' => 8, // This specific queue ('my-queue') will have an 8 second delay
                            ],
                        ],
                        'parameters' => [
                            // Inherit SQS as its service from 'default'
                        ],
                    ],
                    'my-task-queue' => [ // The name of the Task in GCP CloudTasks
                        'parameters' => [
                            'service' => GoogleCloudTasks::IDENTIFIER, // Override only 'my-task-queue' to use Google CloudTasks instead of SQS
                        ]
                    ]
                ]
            ],
        ],
    ],
];
```

With the configuration above we have configured support for the following:

- AWS SQS Service support
- GCP CloudTasks Service support
- A queue named `my-queue` with a `DelaySeconds` of 5 and a service of AWS SQS.
- A second queue named `my-other-queue` with a `DelaySeconds` of 8 and a service of AWS SQS.
- A third queue named `my-task-queue` and a service of Google CloudTasks.

This means that later on when we build a QueueMessage with a 'queue_identifier' of 'my-queue', Idle 
will pull up these details and it will know that for a queue named 'my-queue', it needs to instantiate 
the AWS SQS service, and it needs to perform the `queue` action with a `DelaySeconds` of 5.  Similarly, 
all details will be the same for `my-other-queue`, except `DelaySeconds` will be 8 when performing a 
`queue` action.  If we wanted to we could specify other paremeters for other actions, such as `delete`,
or `dequeue` following the same convention.

It also means that when we build a QueueMessage with a 'queue_identifier' of 'my-task-queue', Idle will
pull up the configuration details and figure out that this particular queue utilizes the GCP CloudTasks
Service.  It does not have a `DelaySeconds` when performing a `queue` action because that action was only
defined for AWS SQS QueueMessages.

The same principles apply to PubSub, except rather than there only being `QueueMessage`, we have `TopicMessage`
and `SubscriptionMessage`.  Instead of `queue` and `dequeue`, we have `publish` and `pull`.

### Utilizing Messages

#### QueueMessage

A QueueMessage can be used to queue to a service or dequeue from a service.

- Queue
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
  
    /** @var @var SendableMessage|QueueMessage $message */
    $message = $messageFactory->createSendableMessage([
        'queue_identifier' => 'my-queue',
        'body'=> 'hello queue payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]);
  
    // You could then queue this message:
    $message->send(); // Send is an alias for `queue` which queues the message to its service (SQS)
    ```
- Dequeue
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var QueueMessage $message */
    $message = $messageFactory->receiveMessageOrFail(['queue_identifier' => 'my-queue']);
  
    // Or multiple messages
    /** @var QueueMessage[] $messages */
    $messages = $messageFactory->receiveMessages(['queue_identifier' => 'my-queue']);
    ```
  
### Cloud Tasks

Google CloudTasks is a queue service which performs a request when the message reaches the top of the queue.  An example of this can be seen below:

```php
    use GuzzleHttp\Psr7\Request; // Any Request class may be used as long as it implements PSR7
    // ... 
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    $messageFactory->createSendableMessage([
            'queue_identifier' => 'my-task-queue',
            'attributes' => [
                'request' => new Request(
                    'PUT',
                    'http://foobar.example.com',
                    [
                        'Content-Type' => 'application/json',
                    ],
                    'payload'
                ),
            ]
        ])->send();
``` 

Note: Google CloudTasks does not support dequeueing.

#### Topic Messages

A `TopicMessage` is designed to allow us to publish messages into Publish Subscribe systems.
  
- Manually
    - Simply create it from data.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
  
    /** @var SendableMessage|TopicMessage $message */
    $message = $messageFactory->createSendableMessage([
       'topic_identifier' => 'my-topic', // Key 'topic_identifier' lets idle know to expect a TopicMessage.  Its value must match the name of the configured topic in the config.
       'body'=> 'hello pubsub payload!',
       'attributes' => [
           'foo' => 'bar',
       ]
    ]);
    // You can now send this message up to the topic
    $message->send(); // Send to PubSub with the configured 'publish' parameters
    ```

#### Subscription Messages

A `SubscriptionMessage` is a message which contains data which has been retrieved from a `subscription`.  This can happen from one of two actions:

- Pull
    - Query the service and obtain one or more SubscriptionMessage(s):
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
  
    /** @var SubscriptionMessage $message */  
    $message = $messageFactory->receiveMessageOrFail(['subscription_identifier' => 'my-subscription']);

    // Or multiple messages
    
    /** @var SubscriptionMessage[] $message */  
    $messages = $messageFactory->receiveMessages(['subscription_identifier' => 'my-subscription']);  
    ```
- Push
    - A subscription service makes a request to the application and provides it with message data.  We then instantiate a SubscriptionMessage directly from its data.

    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var SubscriptionMessage $message */  
    $message = $messageFactory->createMessage([
       'subscription_identifier' => 'my-subscription', // Key 'subscription_identifier' lets idle know to expect a SubscriptionMessage.  Its value must match the name of the configured subscription in the config.
       'body'=> 'hello pubsub payload!',
       'attributes' => [
           'foo' => 'bar',
       ]
    ]);
    ```

## Utilizing Jobs

### Job

In Idle, a job is responsible for coordinating workers to ensure they each carry out the actual work.  Idle currently ships with two main types of jobs:
- SimpleJob 
    - A `SimpleJob` is a minimally configured job which runs one or more workers and reports the outcome.
- MessageJob
    - A `MessageJob` is a more specialized type of job which is responsible for processing a job from message data.

### Worker

A worker is the entity actually carrying out the work. Each job can be configured to have multiple workers under it.  Idle ships with three base workers:

- Worker
    - A `Worker` is a generic worker which performs some kind of task.  
    - Idle currently ships with: `DeleteMessageWorker`, and `AcknowledgeMessageWorker`.
- TrackingWorker
    - A `TrackingWorker` is a type of worker which handles the task of persisting overall job details.
        - Idle currently ships with: `DynamoDBTrackerWorker`.
- TrackableWorker
    - A `TrackableWorker` as the name implies is useful whenever a worker has data which we wish to persist as part of the overall job tracking process.


### SimpleJob

Below is an extract of an Idle config showing the SimpleJob section and its workers.

```php
[
    'job' => [
        'worker' => [ // Global worker definition
            'types' => [
                FooWorker::IDENTIFIER => [ // Define support for the FooWorker worker and its parameters
                    'class' => FooWorker::class,
                    'parameters' => [
                        'foo_count' => 10 // Default value for all executions of this worker
                    ],                   
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
        'types' => [
            SimpleJob::IDENTIFIER  => [ // Define the SimpleJob type
                'class' => SimpleJob::class,
                'parameters' => [
                    'supported' => [
                        'my-simple-job' => [ // Define a SimpleJob with the name 'my-simple-job'.
                            'parameters' => [
                                'workers' => [ // Reference one of the global workers and override any parameters
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Sample worker
                                        'parameters' => [
                                            'foo_count' => 20, // Override to 20 only for 'my-simple-job'
                                        ],
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
```  

Creating a SimpleJob is very straight forward when utilizing the `JobFactory`:

```php
$jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);

$job = $jobFactory->createJob(SimpleJob::IDENTIFIER, [ // Create a Job of the type SimpleJob::IDENTIFIER
    'simple_identifier' => 'my-simple-job', // The name of our SimpleJob
    'foo' => 'bar', // Set parameters to override the configured defaults for `my-simple-job`
]);

$job->process(); // Processes each the defined workers for `my-simple-job`.  In this case only `FooWorker`.
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```

### MessageJob

`MessageJob` is a type of Job which utilizes data from Messages.  Below is an extract of an Idle config for `MessageJob`.  We configure the `MessageJob` based on the type of message which will initiate the job.  In this case we have added support for messages coming from Queues and PublishSubscribe, this is through QueueMessage and SubscriptionMessage (keep in mind topics cannot initiate a job).

```php
[
    'job' => [
        'worker' => [ // Define workers for the application
            'types' => [
                FooWorker::IDENTIFIER => [ // Define support for the FooWorker worker as well as any relevant parameters
                    'class' => FooWorker::class,
                ],
                DynamoDBTrackerWorker::IDENTIFIER  => [ // Define support for the DynamoDBTrackerWorker worker (along with its default configuration)
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
            MessageJob::IDENTIFIER => [ // Define support for the MessageJob job type
                'class' => MessageJob::class, // The corresponding class 
                'parameters' => [
                    QueueMessage::IDENTIFIER => [ // Define support for running MessageJobs when receiving QueueMessages
                        'my-queue' => [ // Define a job for any QueueMessage coming from 'my-queue'.  Keep in mind this value must match the name of the queue in SQS.
                            'parameters' => [
                                'workers' => [ // Define all the workers which will be processed when a 'my-queue' QueueMessage is received.
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
                        'my-task-queue' => [ // The queue which will trigger this job, in this case my-task-queue on CloudTasks which was defined previously in the message section
                            'parameters' => [
                                'workers' => [ // Define all the workers which will be processed when a QueueMessage is received from the queue 'my-task-queue'.
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Sample worker
                                        'parameters' => [],
                                    ],
                                ],
                            ],
                        ]
                    ],
                    SubscriptionMessage::IDENTIFIER => [ // Define support for running a job when receiving SubscriptionMessages
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
        ],
    ],
];
```

Creating a `MessageJob` is very straight forward when utilizing the `JobFactory`.

```php
$jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);

$job1 = $jobFactory->createJob(MessageJob::IDENTIFIER, [
    'message' => [ // MessageJob require a `message` parameter, either as an array or an object.
        'message_identifier' => '123',
        'queue_identifier' => 'my-queue', 
        'body'=> 'hello queue payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]
]);


$job2 = $jobFactory->createJob(MessageJob::IDENTIFIER, [
    'message' => [ // The factory will automatically convert to the appropriate message entity (SubscriptionMessage) and inject the corresponding messaging service.
        'message_identifier' => '123',
        'subscription_identifier' => 'my-subscription', // Must match the configured subscription name (note the use of 'subscription_identifier')
        'body'=> 'hello pubsub payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]
]);

$job1->process();
$success = $job1->isSuccessful();
$duration = $job1->getDuration();
$errors = $job1->getErrors();
```

### Outputting job results

Idle includes an optional `league/fractal` transformer to quickly output basic job details. This is located at `src/Job/Output/Transformer/JobDetails.php`. 

