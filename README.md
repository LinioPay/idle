
# Idle

Idle is a package for managing Jobs and Messaging systems.  The two aspects work in harmony to make background and queued job processing a breeze.

## Primary Components

### Job

A job as the name implies represents an objective which we wish to accomplish.  Idle currently ships with two main types of jobs:
- SimpleJob 
    - A `SimpleJob` is a generic job which will be made up of one or more workers.
- MessageJob
    - A `MessageJob` is a more specialized type of job which is responsible for accomplishing an objective while making use of data which is received from a message.

#### Job > Worker

A worker is the entity responsible for doing the work of one or more tasks which make up a job.  It is up to you to create your own custom workers depending on needed functionality. Idle ships with three types of workers:

- Worker
    - A `Worker` is a generic worker which performs some kind of task.
    - Idle currently ships with: `DeleteMessageWorker`, and `AcknowledgeMessageWorker`.
- TrackingWorker
    - A `TrackingWorker` is a type of worker which handles the task of persisting overall job details.
        - Idle currently ships with: `DynamoDBTrackerWorker`.
- TrackableWorker
    - A `TrackableWorker` as the name implies is useful whenever a worker has data which we wish to persist as part of the overall job tracking process.

### Message

Messaging is the other half of Idle, and is responsible for communication to and from messaging services.  Idle currently ships with two main strategies for dealing with messages:  

- Queue
    - A `Queue` based implementation entails creating a message which contains data you will need at a later date.  This message is then sent to a queueing service which will hold it in a `queue` until it is retrieved.
    - Idle currently ships with `SQS` as a queueing service. It is very simple to add adapters for other services by implementing the corresponding Service interface.
    - Idle utilizes `QueueMessage` to manage the data on these messages.  
- Publish/Subscribe
    - A `Publish/Subscribe` implementation is similar to a queue based implementation, but it allows more flexibility at the time of retrieval.  From an architectural point of view it consists of one `topic` and one or more `subscriptions`. When a message is sent to a given `topic`, it will forward the message to each of its `subscription(s)`.  The `subscription(s)` will then hold the message until its retrieved.
    - Idle currently ships with `Google PubSub` as a `Publish/Subscribe` service.
    - Idle utilizes two main types of message for dealing with Publish/Subscribe: 
        - `TopicMessage` which can be published to a `topic`.
        - `SubscriptionMessage` which has been obtained (pulled or pushed) from a `subscription`).  

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
Once the package is available to your application, it must be configured.  Idle ships with a sample configuration file to make it easy to get started.  This file is located at `config/sample_idle.php`.  Copy it to the appropriate config directory for your application:

```bash
cp vendor/liniopay/idle/config/sample_idle.php config/job.php
```

### Preparing the Container
Once the configuration file has been added to the target directory, it must be registered with the application's container. The built in factories assume these are added to the container under the `config` key as an array which contains an `idle` key.

For clarity, it should be compatible with the code below:
```php
$config = $container->get('config');
$jobConfig = $config['idle']['job'] ?? []; 
$messageConfig = $config['idle']['message'] ?? [];
```

Now that the container is aware of Idle's config, it must be made aware of its factories:
```php
// JOB 
\LinioPay\Idle\Job\JobFactory::class => \LinioPay\Idle\Job\Jobs\Factory\JobFactory::class,
\LinioPay\Idle\Job\WorkerFactory::class => \LinioPay\Idle\Job\Workers\Factory\WorkerFactory::class,
\LinioPay\Idle\Job\Jobs\MessageJob::class => \LinioPay\Idle\Job\Jobs\Factory\MessageJobFactory::class,
\LinioPay\Idle\Job\Jobs\SimpleJob::class => \LinioPay\Idle\Job\Jobs\Factory\SimpleJobFactory::class,

// MESSAGE
\LinioPay\Idle\Message\MessageFactory::class => \LinioPay\Idle\Message\Messages\Factory\MessageFactory::class,
\LinioPay\Idle\Message\ServiceFactory::class => \LinioPay\Idle\Message\Messages\Factory\ServiceFactory::class,

// CUSTOM
(optional - only if using AWS DynamoDB) \LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker::class => \LinioPay\Idle\Job\Workers\Factory\DynamoDBTrackerWorkerFactory::class,
(optional - only if using AWS SQS) \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory\ServiceFactory::class,
(optional - only if using Google PubSub) \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::class => \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory\ServiceFactory::class,
```

It also requires a registered `Psr\Log\LoggerInterface::class` entry for logging purposes. This should return a `LoggerInterface` compatible logger.

### Custom Factories

It is possible your container or application may not be compatible with the provided factories.  It is also possible you will want to add a new service.  Idle is extremely flexible in both configuration and its factory usage.  Any component can be replaced without affecting the other by simply implementing the corresponding interface.

## Job Usage

### SimpleJob

Lets start off with the simplest case.. `SimpleJob`. Below is a simplified Idle config which only shows the SimpleJob section and its workers. 

```php
[
    'job' => [
        'types' => [
            SimpleJob::IDENTIFIER  => [
                'class' => SimpleJob::class,
                'parameters' => [
                    'supported' => [ // The `supported` parameter allows us to define named simple jobs.
                        'my_simple_job' => [ // Configure support for a simple job which we're calling 'my_simple_job'
                            'parameters' => [ // Define any parameters which are relevant to this type of job.. such as its workers
                                'workers' => [
                                    [ // Define the usage of FooWorker
                                        'type' => FooWorker::IDENTIFIER,
                                        'parameters' => [ // Override any parameters for this specific instance of FooWorker
                                            'foo' => 'baz',
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ],
        'worker' => [ // Global worker configuration
            'types' => [
                FooWorker::IDENTIFIER => [ // Make idle aware of FooWorker
                    'class' => FooWorker::class, // Set its class
                    'parameters' => [], // Set any default global parameters for this worker
                ],
            ]
        ]
    ]
]
```  

Creating a SimpleJob is very straight forward when utilizing the `JobFactory`.

```php
$jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);

$job = $jobFactory->createJob(SimpleJob::IDENTIFIER, [ // Create a Job of the type SimpleJob::IDENTIFIER
    'simple_identifier' => 'my_simple_job', // Within SimpleJob, we're creating a "subjob" with the supported id "my_simple_job"
    'foo' => 'bar', // Set parameters to override the configured defaults
]);
```

Processing and retrieving job details is straight forward.  At this point all that is left to do is to process the job.

```php
$job->process(); // Calls each of its worker's `work` method.  In the case of `my_simple_job` only `FooWorker`.
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```

### Outputting job results

Idle includes an optional `league/fractal` transformer to quickly output basic job details. This is located at `src/Job/Output/Transformer/JobDetails.php`. 

### MessageJob

Below is a section of the Idle config for `MessageJob`.  We configure the `MessageJob` by the type of message which will initiate the job.  In this case we have added support for messages coming from Queues and PublishSubscribe, this is through QueueMessage and SubscriptionMessage (keep in mind topics can not initiate a job because they are never polled for messages).

```php
[
    'job' => [
        'types' => [
            MessageJob::IDENTIFIER => [
                'class' => MessageJob::class,
                'parameters' => [
                    QueueMessage::IDENTIFIER => [
                        'my_queue' => [ // Define the name of the queue on the messaging service
                            'parameters' => [
                                'workers' => [
                                    [ // Define the usage of FooWorker
                                        'type' => FooWorker::IDENTIFIER, 
                                        'parameters' => [],
                                    ],
                                    [ // Define the usage of DynamoDBTrackerWorker to persist job details
                                        'type' => DynamoDBTrackerWorker::IDENTIFIER, 
                                        'parameters' => [
                                            'table' => 'job_details_my_queue', // Table to persist the job data to
                                        ]
                                    ]
                                ],
                            ],
                        ]
                    ],
                    SubscriptionMessage::IDENTIFIER => [ 
                        'my_subscription' => [ // Define the name of the subscription on the messaging service
                            'parameters' => [
                                'workers' => [
                                    [ // Define the usage of FooWorker
                                        'type' => FooWorker::IDENTIFIER,
                                        'parameters' => [],
                                    ],
                                ],
                            ],
                        ]
                    ],
                ],
            ],
        ],
        'worker' => [ // Global worker definition and configuration
            'types' => [
                FooWorker::IDENTIFIER => [ // Definition of FooWorker
                    'class' => FooWorker::class, // Set the appropriate class
                    'parameters' => [], // Set any default global parameters for this worker
                ],
                DynamoDBTrackerWorker::IDENTIFIER  => [ // Definition of DynamoDBTrackerWorker
                    'class' => DynamoDBTrackerWorker::class, // Set its class
                    'client' => [ // Set a custom property for this worker for instnatiating the DynamoDB client
                        'version' => 'latest',
                        'region' => getenv('AWS_REGION'),
                    ],
                    'parameters' => [], // Set any global parameters for this worker (we could set the same table for all jobs if we wanted)
                ],
            ]
        ]
    ]
]
```

Creating a `MessageJob` is very straight forward when utilizing the `JobFactory`.

```php
$jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);

$job1 = $jobFactory->createJob(MessageJob::IDENTIFIER, [
    'message' => [ // The factory will automatically convert to the appropriate message entity (QueueMessage) and inject the proper messaging service.
        'message_identifier' => '123',
        'queue_identifier' => 'my_queue', // Because this message provides a `queue_identifier`, Idle knows its a `QueueMessage`, along with its configuration details
        'body'=> 'hello queue payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]
]);


$job2 = $jobFactory->createJob(MessageJob::IDENTIFIER, [
    'message' => [ // The factory will automatically convert to the appropriate message entity (SubscriptionMessage) and inject the proper messaging service.
        'message_identifier' => '123',
        'subscription_identifier' => 'my_subscription', // Because this message provides a subscription_identifier, Idle knows its a `SubscriptionMessage`, along with its configuration details
        'body'=> 'hello pubsub payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]
]);
```

Processing and retrieving job details is the same for all jobs:

```php
$job->process();
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```

## Messaging

The second aspect to Idle is messaging, which allows us to interact with messaging services from within the Idle ecosystem.  The Idle configuration for messages also utilizes a cascading style of configuration.

```php
[
    'message' => [
        'types' => [
            QueueMessage::IDENTIFIER => [
                'default' => [ // Defaults shared for all queues, unless overriden
                    'queue' => [ // Configure behavior for when adding a new message
                        'parameters' => [], 
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'dequeue' => [ // Configure behavior for when retrieving messages
                        'parameters' => [], 
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'delete' => [ // Configure behavior for when deleting a message
                        'parameters' => [ 
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'parameters' => [ // Configure overall QueueMessage parameters
                        'service' => SQS::IDENTIFIER, // Default service to be used by all queues
                    ],
                ],
                'types' => [ // Define and override specific queues
                    'my_queue' => [ // Define the queue name as it appears on the messaging service
                        'queue' => [ // Override queueing behavior only for my_queue
                            'parameters' => [
                                'DelaySeconds' => 0, // SQS - Override the number of seconds (0 to 900 - 15 minutes) to delay a specific message
                            ],
                        ],
                        'parameters' => [
                            //'service' => FooMessagingService::IDENTIFIER, // Override service for `my_queue`
                        ],
                    ]
                ]
            ],
            TopicMessage::IDENTIFIER => [ // Configure support for TopicMessages
                'default' => [ // Defaults shared for all topics, unless overriden
                    'publish' => [ // Default publishing configuration
                        'parameters' => [],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my_topic' => [ // Overrides for my_topic
                        'parameters' => [
                            //'service' => GooglePubSub::IDENTIFIER,
                        ],
                    ]
                ]
            ],
            SubscriptionMessage::IDENTIFIER => [ // Configure support for SubscriptionMessages
                'default' => [ // Defaults shared for all topics, unless overriden
                    'pull' => [
                        'parameters' => [],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my_subscription' => [ // Override for `my_subscription`
                        'parameters' => [],
                    ]
                ]
            ],
        ],
        'service' => [ // Global service definition and configuration
            'types' => [
                SQS::IDENTIFIER  => [ // Define support for SQS and its configuration
                    'class' => SQS::class,
                    'client' => [
                        'version' => 'latest',
                        'region' => getenv('AWS_REGION'),
                    ],
                ],
                GooglePubSub::IDENTIFIER => [ // Define support for PubSub and its configuration
                    'class' => GooglePubSub::class,
                    'client' => [],
                ]
            ]
        ],
    ],
]
```

With the configuration above we have enabled support for an SQS queue named `my_queue`, a Google PubSub topic named `my_topic`, and a Google PubSub subscription named `my_subscription`.  Once this is done, adding a queue message or a topic message is a breeze!

### Creating and Interacting with Messages

#### QueueMessage

A QueueMessage can be created in one of two ways:

- Manually
    - This means we're creating a new message from data.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var QueueMessage $message */
    $message = $messageFactory->createMessage([
        'queue_identifier' => 'my_queue', // Because we provide a queue_identifier, Idle knows its a QueueMessage along with its configuration details and which service to inject
        'body'=> 'hello queue payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]);
    
    $sqsService = $message->getService(); // Because `my_queue` is configured to work with SQS, the message factory injects the service to the message for us.
    $sqsService->queue($message); // Now we can send the message to the service
    
    // For convenience, we can send it directly from the message (alternative to the above two lines)
    $message->send();
    ```
- Dequeue
    - This means we're creating a QueueMessage by polling the service and obtaining one message from the queue.  The simplest way to achieve this is to create a message with only the queue identifier. This will instruct the message factory to pull a message from the service:
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var QueueMessage $message */
    $message = $messageFactory->receiveMessageOrFail(['queue_identifier' => 'my_queue']);
  
    // Or multiple messages
    /** @var QueueMessage[] $messages */
    $messages = $messageFactory->receiveMessages(['queue_identifier' => 'my_queue']);
    ```
#### Topic Messages

A `TopicMessage` is a message which we want to publish to a `topic`.
  
- Manually
    - Simply create it from data.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
  
    /** @var TopicMessage $message */
    $message = $messageFactory->createMessage([
       'topic_identifier' => 'my_topic', // Because we provide a topic_identifier, Idle knows its a TopicMessage..
       'body'=> 'hello pubsub payload!',
       'attributes' => [
           'foo' => 'bar',
       ]
    ]);
    
    $pubSubService = $message->getService(); // Because `my_topic` is configured to work with PubSub, the message factory injects the service to the message for us.
    $pubSubService->publish($message); // Now we can send the message to the service
    
    // For convenience, we can send it directly from the message (alternative to the above two lines)
    $message->send();
    ```

#### Subscription Messages

A `SubscriptionMessage` is a message which has been obtained from a `subscription`.  This can happen from one of two actions:

- Pull
    - We query the service and obtain one or more SubscriptionMessage(s).  By only providing a subscription identifier, the factory will automatically pull a message for us.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
  
    /** @var SubscriptionMessage $message */  
    $message = $messageFactory->receiveMessageOrFail(['subscription_identifier' => 'my_subscription']);

    // Or multiple messages
    
    /** @var SubscriptionMessage[] $message */  
    $messages = $messageFactory->receiveMessages(['subscription_identifier' => 'my_subscription']);  
    ```
- Push
    - The messaging service hits a webhook on our application and we instantiate a SubscriptionMessage from the webhook data.

    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var SubscriptionMessage $message */  
    $message = $messageFactory->createMessage([
       'subscription_identifier' => 'my_subscription', // Because we provide a subscription_identifier, Idle knows its a SubscriptionMessage..
       'body'=> 'hello pubsub payload!',
       'attributes' => [
           'foo' => 'bar',
       ]
    ]);
    
    $pubSubService = $message->getService(); // Because `my_subscription` is configured to work with PubSub, the message factory injects the service to the message for us.
    ```
