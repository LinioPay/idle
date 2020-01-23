# Idle

Idle is a package for managing Jobs and Messaging systems.  The two aspects work in harmony to make background and queued job processing a breeze.

## Primary Components

### Message

The messaging component of Idle is responsible for communication to and from messaging services.  Idle currently ships with two main strategies for dealing with messages:  

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

A job as the name implies represents an objective which we wish to accomplish.  Idle currently ships with two main types of jobs:
- SimpleJob 
    - A `SimpleJob` is a generic job which will be made up of one or more workers.
- MessageJob
    - A `MessageJob` is a more specialized type of job which is responsible for accomplishing an objective while making use of data retrieved from a message.

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
Once the configuration has been created, it must be registered with the application's container. The built in factories assume these are added to the container under the `config` key as an array which contains an `idle` key.

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
(optional - only if using Google CloudTasks) \LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Factory\ServiceFactory::class,
(optional - only if using Google PubSub) \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::class => \LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Factory\ServiceFactory::class,
```

It also requires a registered `Psr\Log\LoggerInterface::class` entry for logging purposes. This should return a `LoggerInterface` logger.

### Custom Factories

It is possible your container or application is not compatible with the provided factories.  Idle is extremely flexible in both configuration and its factory usage.  Any component can be replaced as long as it implements the corresponding interface.

### Done

Idle should now be ready to use!

## Messaging

The messaging component allows us to interact with messaging services from within the Idle ecosystem.  In order to be as flexible as possible, Idle messaging utilizes a cascading style of configuration.  This means we can define some defaults which will can be overridden by the specific entities.

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
                    'client' => [],
                    'project' => 'my-project',
                    'location' => 'us-central1',
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
                'default' => [ // Default parameters shared amongst all QueueMessage
                    'dequeue' => [ // QueueMessage retrieval configuration
                        'parameters' => [
                            //'MaxNumberOfMessages' => 1, // SQS: The maximum number of messages to return.
                        ],
                        'error' => [
                            'suppression' => false,
                        ],
                    ],
                    'queue' => [ // QueueMessage addition configuration
                        'parameters' => [
                            //'DelaySeconds' => 0, // SQS: The number of seconds (0 to 900 - 15 minutes) to delay a specific message.
                        ],
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
                        'service' => SQS::IDENTIFIER, // Default service for all configured QueueMessages
                    ],
                ],
                'types' => [ // Define the queues where the QueueMessages are coming from
                    'my-queue' => [
                        'parameters' => [
                            // Inherit SQS as its service
                        ],
                    ],
                    'my-task' => [
                        'parameters' => [
                            'service' => GoogleCloudTasks::IDENTIFIER, // Override the service to use Google CloudTasks instead of AWS SQS
                        ]
                    ]

                ]
            ],
            TopicMessage::IDENTIFIER => [ // Define support for TopicMessage, utilized by Publish Subscribe services such as PubSub.
                'default' => [ // Default parameters shared amongst all TopicMessages
                    'publish' => [ // TopicMessage publishing configuration
                        'parameters' => [],
                    ],
                    'parameters' => [  // General TopicMessage parameters
                        'service' => GooglePubSub::IDENTIFIER,
                    ],
                ],
                'types' => [ // Define supported topics and their overrides
                    'my-topic' => [ // The name/identifier of our topic
                        'parameters' => [
                            // Will inherit GooglePubSub as the service
                        ],
                    ]
                ]
            ],
            SubscriptionMessage::IDENTIFIER => [ // Define support for SubscriptionMessage, utilized by Publish Subscribe services such as PubSub.
                'default' => [ // Default parameters shared amongst all SubscriptionMessages
                    'pull' => [ // SubscriptionMessage pulling configuration
                        'parameters' => [
                            //'maxMessages' => 1, // PubSub: Number of messages to retrieve
                        ],
                    ],
                    'acknowledge' => [ // SubscriptionMessage acknowledge configuration
                        'parameters' => [],
                    ],
                    'parameters' => [
                        'service' => GooglePubSub::IDENTIFIER, // Define which service from the configured list below will be used
                    ],
                ],
                'types' => [
                    'my-subscription' => [ // The identifier of our subscription (Should match the job configuration under MessageJob)
                        'parameters' => [
                            //'service' => GooglePubSub::IDENTIFIER,
                        ],
                    ]
                ]
            ],
        ],
    ],
]
```

With the configuration above we have configured support for the following:

- An AWS SQS queue named `my-queue` with a corresponding MessageJob entry which allows us to run jobs with its messages.
- A Google CloudTasks queue named `my-task` with a corresponding MessageJob entry which allows us to run jobs with its messages.
- A Google PubSub topic named `my-topic`
- A Google PubSub subscription named `my-subscription` with a corresponding MessageJob entry which allows us to run jobs with its messages.

### Creating and Interacting with Messages

#### QueueMessage

A QueueMessage can be created in one of two ways:

- Manually
    - This means we're creating a new message from data.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var QueueMessage $message */
    $message = $messageFactory->createMessage([ // Create a QueueMessage configured to work with SQS (Because it was defined in the config)
        'queue_identifier' => 'my-queue', // Must match the name of the configured queue in the config (note the use of 'queue_identifier')
        'body'=> 'hello queue payload!',
        'attributes' => [
            'foo' => 'bar',
        ]
    ]);
    $message->send(); // Send to SQS
  
    // Or for more control, retrieve the service
    $sqsService = $message->getService(); // Because `my-queue` is configured to work with SQS, the message factory injects the service into the message for us.
    $sqsService->queue($message); // Now we can send the message to the service
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
#### Topic Messages

A `TopicMessage` is a message which we want to publish to a `topic`.
  
- Manually
    - Simply create it from data.
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
  
    /** @var TopicMessage $message */
    $message = $messageFactory->createMessage([
       'topic_identifier' => 'my-topic', // Must match the name of the configured topic in the config (note the use of 'topic_identifier')
       'body'=> 'hello pubsub payload!',
       'attributes' => [
           'foo' => 'bar',
       ]
    ]);
    $message->send(); // Send to PubSub with the configured 'publish' parameters
    
    // Or for more control:
    $pubSubService = $message->getService(); // Because `my-topic` is configured to work with PubSub, the message factory injects the service to the message for us.
    $pubSubService->publish($message); // Now we can send the message to the service
    ```

#### Subscription Messages

A `SubscriptionMessage` is a message which has been obtained from a `subscription`.  This can happen from one of two actions:

- Pull
    - We query the service and obtain one or more SubscriptionMessage(s):
    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
  
    /** @var SubscriptionMessage $message */  
    $message = $messageFactory->receiveMessageOrFail(['subscription_identifier' => 'my-subscription']);

    // Or multiple messages
    
    /** @var SubscriptionMessage[] $message */  
    $messages = $messageFactory->receiveMessages(['subscription_identifier' => 'my-subscription']);  
    ```
- Push
    - The messaging service hits an endpoint on our application and we instantiate a SubscriptionMessage from the payload data.

    ```php
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    /** @var SubscriptionMessage $message */  
    $message = $messageFactory->createMessage([
       'subscription_identifier' => 'my-subscription', // Must match the name of the configured subscription in the config (note the use of 'subscription_identifier')
       'body'=> 'hello pubsub payload!',
       'attributes' => [
           'foo' => 'bar',
       ]
    ]);
    ```

## Utilizing Jobs

### SimpleJob

Lets start off with the simplest case.. `SimpleJob`. Below is a simplified Idle config which only shows the SimpleJob section and its workers. 

```php
[
    'job' => [
        'worker' => [ // Define workers
            'types' => [
                FooWorker::IDENTIFIER => [ // Define support for the FooWorker worker as well as any relevant parameters
                    'class' => FooWorker::class,
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
        'types' => [ // Define jobs and their workers
            SimpleJob::IDENTIFIER  => [ // Define the SimpleJob job
                'class' => SimpleJob::class,
                'parameters' => [
                    'supported' => [
                        'my-simple-job' => [ // Define a SimpleJob with the name 'my-simple-job'.
                            'parameters' => [
                                'workers' => [ // Define the workers which will be processed when 'my-simple-job' runs
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Sample worker
                                        'parameters' => [],
                                    ],
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ],
    ],
]
```  

Creating a SimpleJob is very straight forward when utilizing the `JobFactory`:

```php
$jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);

$job = $jobFactory->createJob(SimpleJob::IDENTIFIER, [ // Create a Job of the type SimpleJob::IDENTIFIER
    'simple_identifier' => 'my-simple-job', // The name of our SimpleJob
    'foo' => 'bar', // Set parameters to override the configured defaults for `my-simple-job`
]);
```

At this point all that is left to do is to process the job.

```php
$job->process(); // Processes all the defined workers for `my-simple-job`.  In this case only `FooWorker`.
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
        'worker' => [ // Define workers
            'types' => [
                FooWorker::IDENTIFIER => [ // Define support for the FooWorker worker as well as any relevant parameters
                    'class' => FooWorker::class,
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
        'types' => [ // Define jobs and their workers
            MessageJob::IDENTIFIER => [ // Define the MessageJob job which is responsible for executing jobs based on received messages
                'class' => MessageJob::class,
                'parameters' => [
                    QueueMessage::IDENTIFIER => [ // Define support for running MessageJobs when receiving QueueMessages
                        'my-queue' => [ // The queue which will trigger this job, in this case my-queue on SQS which was defined previously in the message section
                            'parameters' => [
                                'workers' => [ // Define all the workers which will be processed when a QueueMessage is received from the queue 'my-queue'.
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
                        'my-task' => [ // The queue which will trigger this job, in this case my-task on CloudTasks which was defined previously in the message section
                            'parameters' => [
                                'workers' => [ // Define all the workers which will be processed when a QueueMessage is received from the queue 'my-task'.
                                    [
                                        'type' => FooWorker::IDENTIFIER, // Sample worker
                                        'parameters' => [],
                                    ],
                                ],
                            ],
                        ]
                    ],
                    SubscriptionMessage::IDENTIFIER => [ // Define support for running MessageJobs when receiving SubscriptionMessages
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
]
```

Creating a `MessageJob` is very straight forward when utilizing the `JobFactory`.

```php
$jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);

$job1 = $jobFactory->createJob(MessageJob::IDENTIFIER, [
    'message' => [ // The factory will automatically convert to the appropriate message entity (QueueMessage) and inject the corresponding messaging service.
        'message_identifier' => '123',
        'queue_identifier' => 'my-queue', // Must match the configured queue name (note the use of 'queue_identifier')
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
```

Processing and retrieving job details is the same as for SimpleJob:

```php
$job->process();
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```
