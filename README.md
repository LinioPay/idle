#Idle

Idle is a package for managing Jobs and Queues.  The two aspects work in harmony to make background and queued job processing breeze.

## Primary Components

#### Worker

A worker is the entity responsible for doing the actual work which needs to be done.  

#### Job

A job as far as Idle is concerned, is a task which needs to be performed, as well as the entity responsible for delegating the processing of a given task to a worker.  That is, a job, acts as a manager or task master which figures out what needs to be done, which worker can do it, and gets that worker to do the actual work.  Idle ships with two base jobs `SimpleJob` and `QueueJob`, it also supports the creation of custom jobs.

###### SimpleJob

A SimpleJob is essentially a transparent taskmaster which forwards all task details to the worker doing the actual work.  It is useful when the task being performed does not require any pre-processing or orchestration.  Since its a dummy taskmaster, it needs to be told exactly which worker will complete the task, and what the parameters the worker will need in order to complete the task.  SimpleJobs have only one required parameter: `simple_identifier`.

###### QueueJob

QueueJobs are another kind of job which is used when the task details are retrieved from a message queue.  The QueueJob has only one required parameter, and that is, a `message` which has within it details about which queue it belongs, as well as a body, and custom attributes.  Armed with the message details, a QueueJob figures out which worker is responsible for processing messages for the Message's queue.  It then instantiates this worker, and asks it to perform the actual work.  Once the worker is done, the QueueJob can delete the message from the queue (if configured to do so), or simply return the details of how the work went.

#### Queue Access

In order to allow QueueJob and other parts of an application to work with queues, it helps to be able to communicate with queue services and issue commands.  This part of the package handles this responsibility and makes it possible to perform operations such as add, read, or delete a message from/to a queue.

## Installing Idle

### Composer
The recommended way to install Idle is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of Idle:

```bash
php composer.phar require liniopay/idle
```
### Configuration files
Once the package is available to your application, it must be configured.  Idle ships with two sample configuration files.  The first is for jobs and its located at `config/sample_job.php`, the other is for queue management and its located at `config/sample_queue.php`.

```bash
cp vendor/liniopay/idle/config/sample_job.php config/job.php
cp vendor/liniopay/idle/config/sample_queue.php config/queue.php
```

### Prepare the container
Once these configuration files have been added to their target directory, they must be registered with the application's container. The built in factories assume these are added to the container under the `config` key as an array.  Within this array there should be a `job` and `queue` corresponding to each of the configs.   

When retrieved from the container, the it should be compatible with the code below:
```php
$config = $container->get('config');
$jobConfig = $config['job']; // should contain the contents of the job config
$queueConfig = $config['queue']; // should contain the contents of the queue config
```

Additionally, the container must be made aware of the following factories and invokables:
```php
\LinioPay\Idle\Job\Jobs\Factory\JobFactory::class => \LinioPay\Idle\Job\Jobs\Factory\JobFactory::class,
\LinioPay\Idle\Job\Jobs\QueueJob::class => \LinioPay\Idle\Job\Jobs\Factory\QueueJobFactory::class,
\LinioPay\Idle\Job\Jobs\SimpleJob::class => \LinioPay\Idle\Job\Jobs\Factory\SimpleJobFactory::class,
\LinioPay\Idle\Job\Workers\Factory\Worker::class => \LinioPay\Idle\Job\Workers\Factory\WorkerFactory::class,
\LinioPay\Idle\Message\Messages\Queue\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\Factory\ServiceFactory::class,
(optional - only if using SQS) \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::class => \LinioPay\Idle\Message\Messages\Queue\Service\SQS\Factory\ServiceFactory::class,
(optional - sample worker) \LinioPay\Idle\Job\Workers\FooWorker::class => \LinioPay\Idle\Job\Workers\FooWorker::class,
```

### Custom factories
It is possible your container or application may not be compatible with all or some of the provided factories.  In this case custom factories may be created.  When following this approach simply create the custom factories and ensure at some point you create a Worker factory which implements the Worker factory interface class `idle/src/Job/Workers/Factory/Worker.php`.

## Running and creating simple jobs

Idle is capable of running any type of job, not just QueueJob. The configuration file `config/sample_job.php` contains the default configuration for jobs.  Idle ships with two built in job types: `SimpleJob` and `QueueJob`.

```php
return [
    QueueJob::IDENTIFIER => [
        'type' => QueueJob::class,
        'parameters' => []
    ],
    SimpleJob::IDENTIFIER => [
        'type' => SimpleJob::class,
        'parameters' => [
            'supported' => [
                FooWorker::IDENTIFIER => [
                    'type' => FooWorker::class,
                    'parameters' => [],
                ],
                // ...
            ]
        ]
    ]
];
```

The Idle job configuration serves two purposes: define the job types Idle is capable of processing, and define the default parameters for each of these jobs.  

In some cases applications need to run random one off, straight forward jobs, in order to address this need, Idle ships with a Job type called `SimpleJob` which as the name suggests simply forwards the job details to the desired worker.   
```json
{
    "identifier": "simple", // This is the job type identifier for SimpleJob
    "parameters": {
      "simple_identifier": "foo", // Same as FooWorker::IDENTIFIER, which is configured as a `supported` worker under SimpleJob
      "color": "red" // A random parameter the worker may need to do its work
    }
}
```  

Creating a SimpleJob is very straight forward when utilizing the built in Job factory.

```php
$jobFactory = $container->get(JobFactory::class);
...
$job = $jobFactory->createJob($jobData['identifier'] ?? '', $jobData['parameters'] ?? []);
```

In this case the payload contains an `identifier` of 'simple', this will result in the factory creating a `SimpleJob`.  The `SimpleJob` validation expects a `simple_identifier` as its only required parameter.  If one is not provided -or an unsupported worker identifier- it will return a FailedJob containing the details.  Since our payload contains a valid `simple_identifier`, a `SimpleJob` will be created in this example.

Processing and retrieving job details is the same as for any other job type.  At this point all that is left is to process the job.  When a `SimpleJob` is initiated through a call to `process`, it calls the appropriate worker's `work` method.

```php
$job->process();
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```

### Running and creating custom jobs

Idle also allows for the creation of custom jobs which may involve more orchestration and complexity.  In these cases a custom job can be created which figures out what needs to be done, and which worker(s) will do it. 

This can be done by creating a custom job with an appropriate name, for example `FancyJob`.  

```php
class FancyJob extends DefaultJob
{
    const IDENTIFIER = 'fancy';

    public function __construct(array $config, WorkerFactoryInterface $workerFactory) // Add other parameters if needed, but these two are good to have
    {
        $this->config = $config;
        $this->workerFactory = $workerFactory;
    }

    public function setParameters(array $parameters = []) : void
    {
        // Define some validation rules for this job.
        if (!isset($parameters['fancy_parameter'])) {
            throw new FancyException('Not fancy enough'!);
        }
    }
    
    public function process() : void
    {
        // Figure out which worker is needed
        $workerType = $this->paramrters['fancy_parameter'] == 'ultra' 
            ? UltraWorker::class 
            : LameWorker::class;
        
        // Let the DefaultWorker handle creation of the chosen worker and providing it its parameters
        $this->buildWorker($workerType, ['action' => 'jump']); // Add whatever parameters are needed.. perhaps some of the Job's own parameters
        
        // Let the DefaultWorker handle the actual running of the worker
        parent::process();
    }
}
```

Once created, add the Job's IDENTIFIER of 'fancy' to the Idle job config.  Provide any default parameters if needed.

```php
return [
    QueueJob::IDENTIFIER => [
        'type' => QueueJob::class,
        'parameters' => []
    ],
    SimpleJob::IDENTIFIER => [
        'type' => SimpleJob::class,
        'parameters' => [
            'supported' => [
                FooWorker::IDENTIFIER => [
                    'type' => FooWorker::class,
                    'parameters' => [],
                ],
                // ...
            ]
        ]
    ],
    FancyJob::IDENTIFIER => [
        'type' => FancyJob::class,
        'parameters' => [],
    ],
];
```

Now that Idle is aware of this new Job, it is time to register `FancyJob` with the application container.  This can be done by creating a factory for it, and registering it with the container.  Once this is done, its the same flow as for the built in jobs:

```json
{
    "identifier": "fancy", // This is the job type identifier for FancyJob (FancyJob::IDENTIFIER)
    "parameters": {
      "fancy_paramerter": "ultra"
    }
}
```  

Creating a FancyJob is very straight forward when utilizing the built in Job factory.

```php
$jobFactory = $container->get(JobFactory::class);
...
$job = $jobFactory->createJob($jobData['identifier'] ?? '', $jobData['parameters'] ?? []);
```

All other functionality available to the built in jobs is still available to the FancyJob.

```php
$job->process();
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```

### Outputting job results

Idle includes a `league/fractal` transformer to be able to quickly output basic job details. This is located at `src/Job/Output/Transformer/JobDetails.php`. 

## Working with queues

At this time Idle ships only with Amazon's SQS support.  Over time the plan is to expand this and support all the major providers.  In the mean time it is possible to add support for any desired queue service.

### Service configuration

Before being able to utilize a queue service, its necessary to configure its client, queues, actions, etc.  These are all configured in the `config/sample_queue.php` configuration file.  Configuration is fairly straight forward, its broken down by service type.  Within each service there are three main areas of configuration: `type` (service class), `client` (client initialization parameters), and `queues` (queues this service supports, as well as their parameters). 

```php
[
    'active_service' => SQS::IDENTIFIER, // Indicates which service is actively being used
    'services' => [
        SQS::IDENTIFIER  => [ // Defines an available queue service by its name
            'type' => SQS::class, // Indicates which class actually handles this type of service
            'client' => [ // Client initialization parameters
                'version' => 'latest',
                'region' => 'us-east-1',
            ],
            'queues' => [ // Supported queues within the service
                // ...
            ]
        ]
    ]
]
```

### Queue configuration

It is important to understand how queues are configured in order to utilize Idle effectively.  As previously mentioned, within each service there is a `queues` property which contains configuration for each of its supported queues.  In order to simply the process of configuration, there is also a `default` queue in there which contains default values for queues to extend and override. 

Each queue entry in the `queues` section has four main action areas: dequeue, queue, delete, and worker.  As their names imply they simply allow extending default configuration of parameters for each of these actions.  

- `queue` is used to configure parameters when adding a new message.  
- `dequeue` is used to configure parameters when retrieving (but not deleting) a message from the queue.
- `delete` is used to configure parameters when permanently deleting a message from the queue.
- `worker` is used to configure the worker which will actually perform the task processing the message.

```php
'queues' => [
    'default' => [ // Essentially a `dummy` queue which all others extend and override
        'dequeue' => [
            'parameters' => [
                'MaxNumberOfMessages' => 1, // The maximum number of messages to return. Amazon SQS never returns more messages than this value but may return fewer. Values can be from 1 to 10.
                'VisibilityTimeout' => 30, // The duration (in seconds) that the received messages are hidden from subsequent retrieve requests after being retrieved by a ReceiveMessage request.
                'WaitTimeSeconds' => 2, // The duration (in seconds) for which the call will wait for a message to arrive in the queue before returning. If a message is available, the call will return sooner than WaitTimeSeconds.
            ],
        ],
    ],
    Service::FOO_QUEUE => [ // The name of the queue we're defining (should match the name in the service).
        'worker' => [
            'type' => FooWorker::class, // (required), defines which worker will process messages retrieved from 'foo_queue'
        ],
        'dequeue' => [ // (optional) Override one of the sections
            'parameters' => [
                'VisibilityTimeout' => 300, // Perhaps processing this message takes longer so it needs a higher visibility timeout than the default
            ],
        ],
    ]
]
```

#### Adding a message to the queue

The process for adding a message to the queue simply involves creating the message and passing it to the `queue` (`public function queue(Message $message, array $parameters = []) : bool`) method in the queue service. It will return a boolean indicating success.

```php
$queueService = $container->get(LinioPay\Idle\Message\Messages\Queue\Service::class);
...
$queueService->queue(new LinioPay\Idle\Message\Messages\Queue\Message(Service::FOO_QUEUE, 'foo body'));
```

#### Reading a message from the queue

The process for reading a message from the queue simply involves figuring out which queue to read from and passing it to the `dequeue` (`public function dequeue(string $queueIdentifier, array $parameters = []) : array`) method in the queue service. It will return an array of `LinioPay\Idle\Message\Messages\Queue\Message` since its possible to read more than one Message at a time for some services.

```php
$queueService = $container->get(LinioPay\Idle\Message\Messages\Queue\Service::class);
...
$messages = $queueService->dequeue(Service::FOO_QUEUE);
```

#### Deleting a message from the queue

The process for deleting a message from the queue requires passing the Message which is being deleted to the `delete` (`public function delete(Message $message, array $parameters = []) : bool`) method in the queue service. It will return a boolean indicating success.

```php
$queueService = $container->get(LinioPay\Idle\Message\Messages\Queue\Service::class);
...
$messages = $queueService->dequeue(Service::FOO_QUEUE);

// TODO: validate there is at least one message

/** @var Message $message */
$message = $messages[0];

// TODO: Do somemthing with the message

/** @var bool $success */
$success = $queueService->delete($message);
```

## Processing queue messages with JobQueue

Define a constant somewhere with the name of the queue for easier referencing.

```php
class Queue
{
    const MY_QUEUE = 'my_queue_name';
}
```

Create a new worker to work on messages retrieved from MY_QUEUE.  This can be done by extending the `DefaultWorker` class.

```php
class MyWorker extends DefaultWorker
{
    public function work(): bool
    {
        // TODO: Do some work..
        return true;
    }

    public function validateParameters(array $parameters): void
    {
        // TODO: Add some validation of parameters
    }
}
```

Make Idle aware of the new queue and its corresponding worker. The only required parameter is the worker type.  The rest will be inherited from the 'default' configuration.

```php
[
    'active_service' => SQS::IDENTIFIER,
    'services' => [
        SQS::IDENTIFIER  => [ 
            'type' => SQS::class, 
            'client' => [
                'version' => 'latest',
                'region' => 'us-east-1',
            ],
            'queues' => [
                ...
                Queue::MY_QUEUE => [
                    'worker' => [
                        'type' => MyWorker::class,
                    ]
                ]
            ]
        ]
    ]
]
```

### Running queue jobs

Idle includes a `QueueJob` class which simplifies the process of creating and running workers for message processing. As an example, assume a separate part of the application (perhaps a lambda trigger) has already read a message from the queue and now calls the API with the following job payload: 

```json
{
    "identifier": "queue", // This is the job type identifier, should remain as queue for QueueJob
    "parameters": {
        "message": { // QueueJobs require an Idle message as its only parameter 
            "message_identifier": "123-456-7890",
            "queue_identifier": "my_queue_name",
            "body": "mybody",
            "attributes": {
                "foo": "bar"
            },
            "metadata": {
                "baz": "foo"
            }
        }	
    }
}
```  

Creating a job becomes quite simple when utilizing the built in job factory.

```php
$jobFactory = $container->get(JobFactory::class);
...
$job = $jobFactory->createJob($jobData['identifier'] ?? '', $jobData['parameters'] ?? []);
```

In this case the payload contains an `identifier` of 'queue', this will result in the factory creating a `QueueJob`.  The QueueJob validation expects a message as its only parameter (corresponding to a `LinioPay\Idle\Message\Messages\Queue\Message`).  If one is not provided -or an invalid Message is provided- it will return a FailedJob containing the details.  Since our payload contains a valid message, a `QueueJob` will be created in this example.

Processing and retrieving job details is quite simple.  At this point all that is left is to initiate the job.  When a QueueJob is initiated through a call to `process`, it calls the appropriate worker's `work` method.

```php
$job->process();
$success = $job->isSuccessful();
$duration = $job->getDuration();
$errors = $job->getErrors();
```
