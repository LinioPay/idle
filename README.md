#Idle

Idle is a package for managing Jobs and Messaging systems.  The two aspects work in harmony to make background and queued job processing a breeze.  The package is designed with extreme flexibility in mind.  Any entity can be replaced by simply implementing the appropriate interface contract.  However, the biggest strength to Idle and its flexibility is its configuration hierarchy which allows definition of defaults and having each entity override the defaults with its own properties.

## Primary Components

### Job

A job as the name implies represents an objective that we wish to complete.  In order to accomplish this objective we will need to successfully complete one or more tasks.

Idle currently ships with two main types of jobs:
- SimpleJob 
    - A `SimpleJob` is a generic, straight forward job which will be made up of one or more workers.
- MessageJob
    - A `MessageJob` is a more specialized type of job which is responsible for handling messages which have come from a messaging platform such as SQS, RabbitMQ, PubSub, etc.

#### Job > Worker

A worker is the entity responsible for doing the work of each of the task(s) which make up a job.  Idle allows each job to define its own set of workers.  It is up to you to create your own workers depending on needed functionality, however Idle ships with two subtypes of workers:
- TrackingWorker
    - A `TrackingWorker` has the main objective of persisting overall job details.  This means that the worker will ask the parent job for any details it may want to persist.. such as if it was successful or not, execution time, etc.  The TrackingWorker will then connect to a database or service and store this information.
        - Idle currently ships with DynamoDBTrackingWorker
- TrackableWorker
    - A `TrackableWorker` is useful whenever a worker has important information which should be tracked as part of the overall job tracking process.

### Message

Messaging is the second aspect of Idle and essentially is responsible for communication to and from messaging services, as well as managing the message entities themselves.  Idle currently ships with two main strategies for dealing with messages:  
- Queue
    - A `Queue` based implementation essentially entails creating a message which contains whatever data you may need at a later date.  This message is then sent to a `queue` service which will hold it until some entity retrieves it from that same `queue`.
    - Idle currently ships with `SQS` as a queueing service.
    - Idle has one main type of message for dealing with queues: QueueMessage.  
- Publish/Subscribe
    - A `Publish/Subscribe` implementation is similar to a queue based implementation but it allows more flexibility at retrieval time.  From an architectural point of view, a `topic` must be configured with at least one `subscriptions` in order to be useful.  When a message is sent to a given `topic`, it will then add the message to each of the `subscription(s)`.  Each of the `subscriptions` will then hold the message until some external entity pulls the message out of it.  The main distinction with a `queue` based approach is that a `PublishSubscribe` model allows multiple things to occur to the message at different times.  Whereas a queue is more linear and typically once you retrieve a message that flow is complete.
    - Idle currently ships with `Google PubSub` as a `Publish/Subscribe` service.
    - Idle has two main types of message for dealing with Publish/Subscribe: TopicMessage (implying it can be published to a `topic`), and a SubscriptionMessage (implying it has been pulled from a `subscription`).  

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
### Configuration file
Once the package is available to your application, it must be configured.  Idle ships with a sample configuration file to make it easy to set up.  This file is located at `config/sample_idle.php`.  Copy it to the appropriate config directory for your application.

```bash
cp vendor/liniopay/idle/config/sample_idle.php config/job.php
```

### Prepare the container
Once the configuration file has been added to its target directory, it must be registered with the application's container. The built in factories assume these are added to the container under the `config` key as an array.

For example, when retrieved from the container, it should be compatible with the code below:
```php
$config = $container->get('config');
$jobConfig = $config['job'] ?? []; 
$messageConfig = $config['message'] ?? [];
```

Now that the container is aware of Idle's config, it must be made aware of its factories:
```php
// JOB 
\LinioPay\Idle\Job\JobFactory::class => \LinioPay\Idle\Job\Jobs\Factory\JobFactory::class, // Primary Job Factory
\LinioPay\Idle\Job\FactoryWorker::class => \LinioPay\Idle\Job\Workers\Factory\WorkerFactory::class, // Primary Worker Factory
\LinioPay\Idle\Job\Jobs\MessageJob::class => \LinioPay\Idle\Job\Jobs\Factory\MessageJobFactory::class, // MessageJob Factory
\LinioPay\Idle\Job\Jobs\SimpleJob::class => \LinioPay\Idle\Job\Jobs\Factory\SimpleJobFactory::class, // SimpleJob Factory

// MESSAGE
\LinioPay\Idle\Message\MessageFactory::class => \LinioPay\Idle\Message\Messages\Factory\MessageFactory::class, // Primary Message Factory
\LinioPay\Idle\Message\ServiceFactory::class => \LinioPay\Idle\Message\Messages\Factory\ServiceFactory::class, // Primary Message Service Factory

// CUSTOM
(optional - only if using DynamoDB) \LinioPay\Idle\Job\Workers\DynamoDBTrackerWorker::class => \LinioPay\Idle\Job\Workers\Factory\DynamoDBTrackerWorkerFactory::class,
(optional - only if using SQS) \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory\ServiceFactory::class,
(optional - only if using PubSub) \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::class => \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory\ServiceFactory::class,
```

### Custom factories

It is possible your container or application may not be compatible with the provided factories, in this case custom factories may be created simply implement the appropriate factory interface.

## SimpleJob

Below is a simplified idle config which only shows the SimpleJob tree.  It is fairly straight forward.. 

```php
[
    'job' => [
        'types' => [
            SimpleJob::IDENTIFIER  => [
                'class' => SimpleJob::class,
                'parameters' => [
                    'supported' => [
                        'my_simple_job' => [ // Configure support for a simple job which we're calling 'my_simple_job'
                            'parameters' => [ // Define any parameters which are relevant to this type of job.. such as its workers
                                'workers' => [ // This job only has one worker, and it is of type FooWorker
                                    [
                                        'type' => FooWorker::IDENTIFIER,
                                        'parameters' => [ // Override any parameters for this specific instance of FooWorker
                                            'foo' => 'baz',
                                            'red' => true,
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
                FooWorker::IDENTIFIER => [ // Definition of FooWorker
                    'class' => FooWorker::class, // Set the appropriate class
                    'parameters' => [], // Set any default global parameters for this worker
                ],
            ]
        ]
    ]
]
```  

Creating a SimpleJob is very straight forward when utilizing the built in Job factory.

```php
$jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);

$job = $jobFactory->createJob(SimpleJob::IDENTIFIER, [
    'simple_identifier' => 'my_simple_job',
    'foo' => 'bar', // Any other parameters will override the configured values
]);
```

Processing and retrieving job details is also very simple.  At this point all that is left to do is to process the job.  When a `SimpleJob` is initiated through a call to `process`, it calls each of its worker's `work` method.  In the case of `my_simple_job` there was only one worker defined so only FooWorker will perform work.

```php
$job->process();
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```

### Outputting job results

Idle includes a `league/fractal` transformer to be able to quickly output basic job details. This is located at `src/Job/Output/Transformer/JobDetails.php`. 

## MessageJob

Below is a simplified Idle config for MessageJob.  It is slightly more complicated than SimpleJob's config but still fairly easy to follow.  In this case we have added support for Queues and PublishSubscribe.  However, since MessageJob is Message focused, we instead configure the job by the type of message which will initiate the job.  Therefore these are QueueMessage and SubscriptionMessage (keep in mind topics can not initiate a job).

```php
[
    'job' => [
        'types' => [
            MessageJob::IDENTIFIER => [
                'class' => MessageJob::class,
                'parameters' => [
                    QueueMessage::IDENTIFIER => [
                        'my_queue' => [ // Define the our queue.. in this case this is the name of the queue in the queueing service
                            'parameters' => [
                                'workers' => [ // Define any workers responsible for handling different aspects of the job
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Define the type of worker.. in this case again FooWorker
                                        'parameters' => [],
                                    ],
                                    [
                                        'type' => DynamoDBTrackerWorker::IDENTIFIER, // Define a second worker which persists job details
                                        'parameters' => [
                                            'table' => 'my_foo_queue_tracker_table', // This is a parameter which this worker accepts to indicate which table to utilize
                                        ]
                                    ]
                                ],
                            ],
                        ]
                    ],
                    SubscriptionMessage::IDENTIFIER => [ 
                        'my_subscription' => [
                            'parameters' => [
                                'workers' => [
                                    [
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
        'worker' => [ // Global worker configuration
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
                    'parameters' => [], // Set any global parameters for this worker (we could set the table for all jobs if we wanted)
                ],
            ]
        ]
    ]
]
```

Creating a MessageJob is very straight forward when utilizing the built in Job factory.

```php
$jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);

$job1 = $jobFactory->createJob(MessageJob::IDENTIFIER, [
    'message' => [ // The built in factories will automatically convert to the appropriate message entity (QueueMessage) and inject the proper messaging service client
        'message_identifier' => '123',
        'queue_identifier' => 'my_queue', // Because this message provides a queue_identifier, Idle knows its a QueueMessage along with its configuration details
        'body'=> 'hello queue payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]
]);


$job2 = $jobFactory->createJob(MessageJob::IDENTIFIER, [
    'message' => [ // The built in factories will automatically convert to the appropriate message entity (SubscriptionMessage) and inject the proper messaging service client
        'message_identifier' => '123',
        'subscription_identifier' => 'my_subscription', // Because this message provides a subscription_identifier, Idle knows its a SubscriptionMessage along with its configuration details
        'body'=> 'hello pubsub payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]
]);
```

Processing and retrieving job details is the same as for `SimpleJob` or any other Job type:

```php
$job->process();
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```

## Messaging

The second aspect to Idle is messaging.  This is simply a way to interact with messaging services from within the Idle ecosystem.  The Idle configuration for messages also utilizes a cascading style of configuration.  This means parameters are inherited from defaults and can be overriden at the entity level as well as on the fly at the request level.

```php
[
        'message' => [
            'types' => [
                QueueMessage::IDENTIFIER => [
                    'default' => [
                        'queue' => [
                            'parameters' => [], // Configure behavior for when adding a new message
                            'error' => [
                                'suppression' => true,
                            ],
                        ],
                        'dequeue' => [
                            'parameters' => [], // Configure behavior for when retrieving messages
                            'error' => [
                                'suppression' => true,
                            ],
                        ],
                        'delete' => [
                            'parameters' => [ // Configure behavior for when deleting a message
                            ],
                            'error' => [
                                'suppression' => true,
                            ],
                        ],
                        'parameters' => [
                            'service' => SQS::IDENTIFIER, // Default service to be used by all queues
                        ],
                    ],
                    'types' => [
                        'my_queue' => [
                            'queue' => [ // Override queueing behavior
                                'parameters' => [
                                    'DelaySeconds' => 0, // SQS Override - the number of seconds (0 to 900 - 15 minutes) to delay a specific message
                                ],
                            ],
                            'parameters' => [
                                //'service' => FooMessagingService::IDENTIFIER, // Override service for my_queue
                            ],
                        ]
                    ]
                ],
                TopicMessage::IDENTIFIER => [ // Configure support for TopicMessages
                    'default' => [
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
                    'default' => [
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
                        'my_subscription' => [
                            'parameters' => [],
                        ]
                    ]
                ],
            ],
            'service' => [
                'types' => [
                    SQS::IDENTIFIER  => [
                        'class' => SQS::class,
                        'client' => [
                            'version' => 'latest',
                            'region' => getenv('AWS_REGION'),
                        ],
                    ],
                    GooglePubSub::IDENTIFIER => [
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

#### Queue Messages
```php
$messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);

$message1 = $messageFactory->createMessage([
    'queue_identifier' => 'my_queue', // Because we provide a queue_identifier, Idle knows its a QueueMessage along with its configuration details and which service to inject
    'body'=> 'hello queue payload!',
    'attributes' => [
        'foo' => 'bar',
    ]
]);

$sqsService = $message1->getService(); // Because `my_queue` is configured to work with SQS, the message factory injects the service to the message for us.
$sqsService->queue($message1); // Now we can send the message to the service

// Or as a shortcut we can send it directly from the message (alternative to the above two lines)
$message1->queue($message1);
```

#### Topic Messages

A `TopicMessage` is a message which we want to publish to a `topic`.
  
```php
$messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
$message = $messageFactory->createMessage([
   'topic_identifier' => 'my_topic', // Because we provide a topic_identifier, Idle knows its a TopicMessage..
   'body'=> 'hello pubsub payload!',
   'attributes' => [
       'foo' => 'bar',
   ]
]);

$pubSubService = $message->getService(); // Because `my_topic` is configured to work with PubSub, the message factory injects the service to the message for us.
$pubSubService->publish($message); // Now we can send the message to the service

// Or as a shortcut we can send it directly from the message (alternative to the above two lines)
$message->publish();
```

#### Subscription Messages

A `SubscriptionMessage` is a message which has been obtained from a `subscription`.  Typically you would only create a SubscriptionMessage, if you're creating it from the data obtained from a subscription `push` webhook.  In most other scenarios you would simply receive a `SubscriptionMessage` after performing a `pull` on the `PublishSubscribe` service for the given `subscription`.  Below we instantiate it directly as an example.

```php
$messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
$message = $messageFactory->createMessage([
   'subscription_identifier' => 'my_subscription', // Because we provide a subscription_identifier, Idle knows its a SubscriptionMessage..
   'body'=> 'hello pubsub payload!',
   'attributes' => [
       'foo' => 'bar',
   ]
]);

$pubSubService = $message->getService(); // Because `my_subscription` is configured to work with PubSub, the message factory injects the service to the message for us.
$pubSubService->acknowledge($message); // Now we can acknowledge the message on the service

// Or as a shortcut we can acknowledge it directly from the message (alternative to the above two lines)
$message->acknowledge();
```

### Retrieving Messages from Services

Idle services are defined within the 'message' section of the Idle config.  Below is an excerpt of the messaging section of the Idle config.  Within this sample config you can see that a message must belong to one of the three types currently supported by Idle: 

- QueueMessage
    - This type is utilized when following a normal queueing pattern.
- TopicMessage
    - This type is utilized when following a PublishSubscribe pattern and represents a message which will be published to a topic.
- SubscriptionMessage
    - This type is utilized when following PublishSubscribe pattern and represents a message which has been retrieved from a `subscription`.
    
Within each of these message types you can see a bit of the cascading configuration at work.  Each type has a default configuration, with general parameters which will apply to all queues (or topic/subscription in the case of PublishSubscribe), as well as the ability to define specific options for a particular queue (or topic/subscription).  This makes it possible for us to override all aspects of each entity's parameters, including the actual service which is used.  If we wanted we could have my-queue utilizing RabbitMQ (or any other service as long as we add an adapter for it) and have all other queues utilize SQS.

Below the type configurations, there is a service configuration section.  In this area we can define the parameters for instantiating a given service.  In the example below, there is configuration for SQS which allows us to set any client parameters such as the region, or version.  We have also defined the same for Google PubSub. 
```php
[
    'message' => [
        'types' => [
            QueueMessage::IDENTIFIER => [
                'default' => [
                    'dequeue' => [
                        'parameters' => [ // Configure behavior for when retrieving messages
                            //'MaxNumberOfMessages' => 1, // The maximum number of messages to return. Amazon SQS never returns more messages than this value but may return fewer. Values can be from 1 to 10.
                            //'VisibilityTimeout' => 30, // The duration (in seconds) that the received messages are hidden from subsequent retrieve requests after being retrieved by a ReceiveMessage request.
                            //'WaitTimeSeconds' => 2, // The duration (in seconds) for which the call will wait for a message to arrive in the queue before returning. If a message is available, the call will return sooner than WaitTimeSeconds.
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'queue' => [
                        'parameters' => [ // Configure behavior for when adding a new message
                            //'DelaySeconds' => 0, // The number of seconds (0 to 900 - 15 minutes) to delay a specific message. Messages with a positive DelaySeconds value become available for processing after the delay time is finished. If you don't specify a value, the default value for the queue applies.
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'delete' => [
                        'parameters' => [ // Configure behavior for when deleting a message
                        ],
                        'error' => [
                            'suppression' => true,
                        ],
                    ],
                    'parameters' => [
                        'service' => SQS::IDENTIFIER,
                    ],
                ],
                'types' => [
                    'my-queue' => [
                        'parameters' => [
                            //'service' => SQS::IDENTIFIER,
                        ],
                    ]
                ]
            ],
            TopicMessage::IDENTIFIER => [
                'default' => [
                    'publish' => [
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
                    'my-topic' => [
                        'parameters' => [
                            //'service' => GooglePubSub::IDENTIFIER,
                        ],
                    ]
                ]
            ],
            SubscriptionMessage::IDENTIFIER => [
                'default' => [
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
                    'my-subscription' => [
                        'parameters' => [],
                    ]
                ]
            ],
        ],
        'service' => [
            'types' => [
                SQS::IDENTIFIER  => [
                    'class' => SQS::class,
                    'client' => [
                        'version' => 'latest',
                        'region' => getenv('AWS_REGION'),
                    ],
                ],
                GooglePubSub::IDENTIFIER => [
                    'class' => GooglePubSub::class,
                    'client' => [],
                ]
            ]
        ],
    ],
]
```

Since Idle is very flexible, the type of service and its configuration is completely dependent upon which entity we're interacting with.  This means we could have one queue for SQS and another for RabbitMQ, and we could have completely different parameters for retrieving and adding messages. Essentially, this means Idle is message centric and therefore the simplest way of obtaining a service is with a message:

```php
$messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
$message = $messageFactory->createMessage([
   'subscription_identifier' => 'my_subscription'
]);

// To retrieve a configured service
$service = $message->getService();

// Perform the operation
$message->pull();

// Or one line it:
/** @var array $messages */
$messages = $messageFactory->createMessage(['subscription_identifier' => 'my_subscription'])->pull()
```
