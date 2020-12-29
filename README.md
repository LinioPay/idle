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

### Preparing the Container
Idle is highly flexible and allows any component to be swapped out for a custom version.  This does mean we need to do some work up front registering various factories with the application container.

### Register the Idle Config
Idle ships with a sample configuration file to make it easy to get started.  This file is located at `config/sample_idle.php`.  Copy it to the appropriate config directory for your application and modify it to your needs:

```bash
cp vendor/liniopay/idle/config/sample_idle.php config/job.php
```

Once the file is at its intended destination, add a `config` array to the application container.  Within this `config` array provide an `idle` key containing the full Idle config.

For clarity, it should be compatible with the code below:
```php
$config = $container->get('config');
$idleConfig = $config['idle'] ?? [];
```

#### Register Job and Worker Factories
Add the Job and Worker factories to your container by registering the interface namespace to its concrete implementation.
```php
[
    \LinioPay\Idle\Job\JobFactory::class => \LinioPay\Idle\Job\Jobs\Factory\JobFactory::class,
    \LinioPay\Idle\Job\WorkerFactory::class => \LinioPay\Idle\Job\Workers\Factory\WorkerFactory::class,
    \LinioPay\Idle\Job\Jobs\MessageJob::class => \LinioPay\Idle\Job\Jobs\Factory\MessageJobFactory::class,
    \LinioPay\Idle\Job\Jobs\SimpleJob::class => \LinioPay\Idle\Job\Jobs\Factory\SimpleJobFactory::class,
];
```

#### Register Message and Service Factories
Add the Message and Service factories to your container by registering the interface namespace to its concrete implementation.

```php
[
    \LinioPay\Idle\Message\MessageFactory::class => \LinioPay\Idle\Message\Messages\Factory\MessageFactory::class,
    \LinioPay\Idle\Message\ServiceFactory::class => \LinioPay\Idle\Message\Messages\Factory\ServiceFactory::class,
];
```

#### Register Optional Factories
Finally, add any optional factories for specific services or custom workers which your application uses.
```php
[
    \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory\ServiceFactory::class,
    \LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Factory\ServiceFactory::class,
    \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::class => \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory\ServiceFactory::class,
    \LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker::class => \LinioPay\Idle\Job\Workers\Factory\DynamoDBTrackerWorkerFactory::class,
];
```

#### Register Logger Factory
Idle also assumes your application container has a registered `Psr\Log\LoggerInterface::class` which returns a concrete implementation of the `LoggerInterface` logger.
```php
[
    Psr\Log\LoggerInterface::class => \Foo\App\Application\Logger\Factory\LoggerFactory::class,
];
```
### Done

Idle should now be ready to use!

## Messaging

The messaging component allows us to interact with messaging services.  In order to be as flexible as possible, Idle utilizes a cascading style of configuration.  It currently ships with two main strategies for dealing with messages:  

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
    $message->send(); // Send to whichever service 'my-queue' is configured under, in this case SQS
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

