![](https://github.com/LinioPay/idle/workflows/Continuous%20Integration/badge.svg)

# Idle

Idle is a package for managing Job and Messaging systems.  The two aspects work in harmony to make message queueing and job processing breeze.

## Primary Components

### Message

The messaging component of Idle is responsible for communication to and from messaging services.  It currently ships with two main strategies for dealing with messages:  

- Queue
    - A `Queue` based implementation entails creating a message which contains data you will need at a later date.  This message is then sent to a queueing service which will hold it in a `queue` until it is retrieved.
    - Idle currently ships with `AWS SQS` and `Google CloudTasks`, as queueing services. It is very simple to add adapters for other services by implementing the corresponding Service interface.
    - Idle utilizes `QueueMessage` to manage these type of messages.  
- Publish/Subscribe
    - A `Publish/Subscribe` implementation is similar to a queue based implementation, but allows for more flexibility when retrieving messages.  From an architectural point of view it consists of one `topic` and one or more `subscriptions`. When a message is sent to a given `topic`, it will forward the message to each of its `subscription(s)`.  Each of the `subscription(s)` will then process the message in their own way.
    - Idle currently ships with `Google PubSub` as a `Publish/Subscribe` service.
    - Idle utilizes two main types of message for dealing with Publish/Subscribe: 
        - `TopicMessage` which can be published to a `topic`.
        - `SubscriptionMessage` which has been obtained (pulled or pushed) from a `subscription`).  

### Job

A job represents one or more objectives which need to be accomplished.  In Idle, a job is responsible for coordinating workers to ensure they each carry out the actual work.  Idle currently ships with two main types of jobs:
- SimpleJob 
    - A `SimpleJob` is a minimally configured job which runs one or more workers and reports the outcome.
- MessageJob
    - A `MessageJob` is a more specialized type of job which is responsible for processing a job while leveraging data retrieved from a message.

#### Worker

A worker is the entity actually doing the work. Idle ships with three types of workers:

- Worker
    - A `Worker` is a generic worker which performs some kind of task.  
    - Idle currently ships with: `DeleteMessageWorker`, and `AcknowledgeMessageWorker`.
- TrackingWorker
    - A `TrackingWorker` is a type of worker which handles the task of persisting overall job details.
        - Idle currently ships with: `DynamoDBTrackerWorker`.
- TrackableWorker
    - A `TrackableWorker` as the name implies is useful whenever a worker has data which we wish to persist as part of the overall job tracking process.

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
### Configuration
Once the package is available to your application, it must be configured.  Idle ships with a sample configuration file to make it easy to get started.  This file is located at `config/sample_idle.php`.  Copy it to the appropriate config directory for your application and modify it to your needs:

```bash
cp vendor/liniopay/idle/config/sample_idle.php config/job.php
```

### Preparing the Container
Register the config with the application's container. In order to accomplish this, add a `config` array to the application container.  Within this `config` provide an `idle` key containing the full Idle config.

For clarity, it should be compatible with the code below:
```php
$config = $container->get('config');
$jobConfig = $config['idle']['job'] ?? [];
```

All Idle components can be extended and swapped out through the use of interfaces and factories.  This means we need to "glue" each interface to a corresponding concrete implementation.  This is done at the application container level: 
```php
[
    // Job 
    \LinioPay\Idle\Job\JobFactory::class => \LinioPay\Idle\Job\Jobs\Factory\JobFactory::class,
    \LinioPay\Idle\Job\WorkerFactory::class => \LinioPay\Idle\Job\Workers\Factory\WorkerFactory::class,
    \LinioPay\Idle\Job\Jobs\MessageJob::class => \LinioPay\Idle\Job\Jobs\Factory\MessageJobFactory::class,
    \LinioPay\Idle\Job\Jobs\SimpleJob::class => \LinioPay\Idle\Job\Jobs\Factory\SimpleJobFactory::class,
    
    // Message
    \LinioPay\Idle\Message\MessageFactory::class => \LinioPay\Idle\Message\Messages\Factory\MessageFactory::class,
    \LinioPay\Idle\Message\ServiceFactory::class => \LinioPay\Idle\Message\Messages\Factory\ServiceFactory::class,
    
    // All the following entries are optional, and should only be enabled if using the corresponding service.
    // \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory\ServiceFactory::class,
    // \LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Factory\ServiceFactory::class,
    // \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::class => \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory\ServiceFactory::class,
    
    // Workers may also have their own factories:
    \LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker::class => \LinioPay\Idle\Job\Workers\Factory\DynamoDBTrackerWorkerFactory::class,
];
```

Idle also assumes your container has a registered `Psr\Log\LoggerInterface::class`. This should return a concrete implementation of the `LoggerInterface` logger.
### Done

Idle should now be ready to use!

## Messaging

The messaging component allows us to interact with messaging services.  In order to be as flexible as possible, Idle utilizes a cascading style of configuration.  In general this means, there are default values and then each specific item inherits the defaults and overrides what it needs to.

```php
[
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
                    'client' => [
                        'project' => 'my-project',
                        'location' => 'us-central1',       
                    ],
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
                'default' => [ // Global 'default' configuration across all services supporting QueueMessages
                    'dequeue' => [ // QueueMessage retrieval configuration
                        'parameters' => [],
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                    'queue' => [ // QueueMessage addition configuration
                        'parameters' => [],
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
                        'service' => SQS::IDENTIFIER, // Define a global default service for all QueueMessages
                    ],
                ],
                'service_default' => [ // Optional
                    SQS::IDENTIFIER => [ // SQS specific overrides to the global 'default'
                        'queue' => [ // QueueMessage addition configuration
                            'parameters' => [
                                'DelaySeconds' => 5, // All SQS QueueMessages will have a 5 second delay
                            ],
                        ]
                    ],
                ],
                'types' => [ // Define the actual queues we will be working with
                    'my-queue' => [ // The name of the queue
                        'parameters' => [
                            // Inherit SQS as its service
                        ],
                    ],
                    'my-task-queue' => [ // The name of the second queue
                        'parameters' => [
                            'service' => GoogleCloudTasks::IDENTIFIER, // Override only 'my-task-queue' to use Google CloudTasks instead of SQS
                        ]
                    ]
                ]
            ],
            TopicMessage::IDENTIFIER => [ // Define support for TopicMessage, utilized by Publish Subscribe services such as PubSub.
                'default' => [ // Global 'default' configuration across all services supporting TopicMessage
                    'publish' => [ // TopicMessage publishing configuration
                        'parameters' => [],
                    ],
                    'parameters' => [  // General TopicMessage parameter overrides across all services
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [ // Define the actual topics we will be working with
                    'my-topic' => [ // The name of our topic
                        'parameters' => [
                            // Will inherit GooglePubSub as the service
                        ],
                    ]
                ]
            ],
            SubscriptionMessage::IDENTIFIER => [ // Define support for SubscriptionMessage, utilized by Publish Subscribe services such as PubSub.
                'default' => [ // Global 'default' configuration across all services supporting SubscriptionMessages
                    'pull' => [ // SubscriptionMessage pulling configuration across all services
                        'parameters' => [
                            //'maxMessages' => 1, // PubSub: Number of messages to retrieve
                        ],
                    ],
                    'acknowledge' => [ // SubscriptionMessage acknowledge configuration across all services
                        'parameters' => [],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER, // Define a global default service for all SubscriptionMessage
                    ],
                ],
                'types' => [ // Define the actual subscriptions we will be working with
                    'my-subscription' => [ // The name of our subscription
                        'parameters' => [
                            //'service' => GooglePubSub::IDENTIFIER,
                        ],
                    ]
                ]
            ],
        ],
    ],
];
```

With the configuration above we have configured support for the following:

- An AWS SQS queue named `my-queue`.
- A Google CloudTasks queue named `my-task-queue`.
- A Google PubSub topic named `my-topic`.
- A Google PubSub subscription named `my-subscription`.

### Getting started with Messages

#### QueueMessage

A QueueMessage can be created in one of two ways:

- Manually
    - This means we're creating a new message from its data.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var SendableMessage $message */
    $message = $messageFactory->createSendableMessage([
        'queue_identifier' => 'my-queue', // Key 'queue_identifier' lets idle know to expect a QueueMessage.  Its value must match the name of the configured queue in the config.
        'body'=> 'hello queue payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ])->send(); // Send to SQS
    ```
- Dequeue
    - This means we're creating a QueueMessage by polling the service and obtaining one or more messages from the queue.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var QueueMessage $message */
    $message = $messageFactory->receiveMessageOrFail(['queue_identifier' => 'my-queue']);
  
    // Or multiple messages
    /** @var QueueMessage[] $messages */
    $messages = $messageFactory->receiveMessages(['queue_identifier' => 'my-queue']);
    ```
  
### Cloud Tasks

Google CloudTasks is a queue service which allows the user to to define an action which will be performed when the message reaches the top of the queue.  Idle currently supports HTTP Request based Tasks.  An example of this can be seen below:

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

A `TopicMessage` is a message type which is designed to allow us to publish messages into Publish Subscribe systems.
  
- Manually
    - Simply create it from data.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
  
    $messageFactory->createSendableMessage([
       'topic_identifier' => 'my-topic', // Key 'topic_identifier' lets idle know to expect a TopicMessage.  Its value must match the name of the configured topic in the config.
       'body'=> 'hello pubsub payload!',
       'attributes' => [
           'foo' => 'bar',
       ]
    ])->send(); // Send to PubSub with the configured 'publish' parameters
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
    - A subscription makes a request to the application and provides it with the data.  In this case we simply instantiate a SubscriptionMessage directly since we already have the data.

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

### SimpleJob

Below is an extract of an Idle config showing the SimpleJob section and its workers.

```php
[
    'job' => [
        'worker' => [ // Define all supported workers for the application
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
                                'workers' => [ // Define the workers which will be processed when 'my-simple-job' runs
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

// At this point all that is left to do is to process the job.

$job->process(); // Processes each the defined workers for `my-simple-job`.  In this case only `FooWorker`.
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```

### Outputting job results

Idle includes an optional `league/fractal` transformer to quickly output basic job details. This is located at `src/Job/Output/Transformer/JobDetails.php`. 

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

// Then we can process the job

$job1->process();
$success = $job1->isSuccessful();
$duration = $job1->getDuration();
$errors = $job1->getErrors();
```
