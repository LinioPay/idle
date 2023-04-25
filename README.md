![](https://github.com/LinioPay/idle/workflows/Continuous%20Integration/badge.svg)

## Compatibility

| PHP  | Tag | Branch |
|------|-----|--------|
| ^8.1 | 5.X | master |
| ^7.2 | 4.X | 7.2    |


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

### Prepare your configurations
Idle requires four different configurations: service, message, job, and worker.  We provide some samples to get you started in both array and yaml (symfony parser) syntax.

#### Sample Pimple Services (config/pimple.php)

Below is a look at the sample container setup for pimple.  Keep in mind most of these services are optional.  You can mix and match the ones you need.

```php
$container = new PimpleContainer();

// Idle configuration
$container[IdleConfig::class] = function() {
    $serviceConfig = require('service_config.php');
    $messageConfig = require('message_config.php');
    $jobConfig = require('job_config.php');
    $workerConfig = require('worker_config.php');

    return new IdleConfig($serviceConfig, $messageConfig, $jobConfig, $workerConfig);
};

// Logs
$container[LoggerInterface::class] = function() {
    $log = new Logger('idle');
    $log->pushHandler(new MonologStreamHandler('php://stdout'));
    return $log;
};

// PSR11 Container Wrapper for Pimple
$container[PSRContainer::class] = function(PimpleContainer $container) {
    return new PSRContainer($container);
};

// Idle
$container[MessageFactoryInterface::class] = function(PimpleContainer $container) {
    return new MessageFactory($container[PSRContainer::class]);
};
$container[ServiceFactoryInterface::class] = function(PimpleContainer $container) {
    return new ServiceFactory($container[PSRContainer::class]);
};
$container[JobFactoryInterface::class] = function(PimpleContainer $container) {
    return new JobFactory($container[PSRContainer::class]);
};
$container[MessageJobInterface::class] = function(PimpleContainer $container) {
    return new MessageJobFactory($container[PSRContainer::class]);
};
$container[SimpleJobInterface::class] = function(PimpleContainer $container) {
    return new SimpleJobFactory($container[PSRContainer::class]);
};
$container[WorkerFactoryInterface::class] = function(PimpleContainer $container) {
    return new WorkerFactory($container[PSRContainer::class]);
};

// Services
$container[SQSService::class] = function(PimpleContainer $container) {
    return new SQSServiceFactory($container[PSRContainer::class]);
};

// Workers
$container[BazWorker::class] = function(PimpleContainer $container) {
    return new BazWorkerFactory($container[PSRContainer::class]);
};
```

### Good to go!  
Idle should be ready to run!

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

#### Service Config
This config defines support for messaging services such as SQS or PubSub.  If you only need one, feel free to remove the others.

```yaml
!php/const LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::IDENTIFIER:
  class: LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service
  client:
    # Provide client options, or leave empty to initialize directly from ENV
    version: latest

!php/const LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service::IDENTIFIER:
  class: LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service
  client: []

!php/const LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::IDENTIFIER:
  class: LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service
  client: []
```

#### Message Config
This config defines the behavior of our supported message types: QueueMessage, TopicMessage, SubscriptionMessage.  Depending on which service you utilize, you may use one or more of these.

```yaml
# Support for messages which will be used by Queue services (SQS, CloudTasks, etc)
!php/const LinioPay\Idle\Message\Messages\Queue\Message::IDENTIFIER:

  # Global defaults for all QueueMessages, across all services.
  # Configurable:
  # - queue (The action of queueing a message to the service).
  # - dequeue (The action of dequeueing a message from the service).
  # - delete (The action to delete a message from the service
  # - parameters (General parameters, such as the service being used).
  default:
    parameters:
      # Define a global service to be used for QueueMessages.
      service: !php/const LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::IDENTIFIER

  # Overrides for all QueueMessages belonging to a specific service.
  service_default:
    !php/const LinioPay\Idle\Message\Messages\Queue\Service\SQS\Service::IDENTIFIER:
      # https://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/API_SendMessage.html
      queue:
        parameters:
          # Override the `DelaySeconds` parameter to 5 for ALL SQS queues
          DelaySeconds: 5
      # https://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/API_ReceiveMessage.html
      dequeue:
        parameters:
          MaxNumberOfMessages: 3 # AWS dequeueing parameter for all queues
      # https://docs.aws.amazon.com/AWSSimpleQueueService/latest/APIReference/API_DeleteMessage.html
      delete:
        parameters: []

  # Define support for individual queues and their specific overrides.
  types:
    # Define support for a queue with name of `my-queue`.
    my-queue:
      queue:
        parameters:
          # Override the `DelaySeconds` parameter for `my-queue` when performing a `queue` action.
          DelaySeconds: 10
      # Inherit SQS as its service from global default
    # Define support for a queue with name of `my-task-queue`.
    my-task-queue:
      parameters:
        service: !php/const LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Service::IDENTIFIER

# Support for messages which will be used by Topic supporting services (PubSub, SNS, etc)
!php/const LinioPay\Idle\Message\Messages\PublishSubscribe\TopicMessage::IDENTIFIER:

  # Global defaults for all TopicMessages, across all services.
  # Configurable:
  # - publish (The action of publishing a message to the topic).
  # - parameters (General parameters, such as the service being used).
  default:
    parameters:
      service: !php/const LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::IDENTIFIER

  types:
    # Define support for a topic with name of `my-topic`.
    my-topic:
      # Inherit PubSub as its service from default.
      parameters: []

# Support for messages which will be used by Subscription supporting services (PubSub, SNS, etc)
!php/const LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage::IDENTIFIER:

  # Global defaults for all SubscriptionMessages, across all services.
  # Configurable:
  # - pull (The action of retrieving a message from the subscription).
  # - acknowledge (The action of acknowledging the message to the subscription).
  # - parameters (General parameters, such as the service being used).
  default:
    parameters:
      service: !php/const LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::IDENTIFIER

  service_default:
    !php/const LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub\Service::IDENTIFIER:
      pull:
        parameters:
          # PubSub specific pull parameter for all its subscriptions
          maxMessages: 3

  types:
    # Define support for a subscription with a name of `my-subscription`.
    my-subscription:
      parameters: []
```

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

In Idle, a job is responsible for coordinating workers to ensure they each carry out the actual work.  Idle currently ships with two main types of jobs: SimpleJob and MessageJob.

### Worker

A worker is the entity actually carrying out the work. Each job can be configured to have multiple workers under it.  Idle ships with three base workers:

- Worker
    - A `Worker` is a generic worker which performs some kind of task.  
    - Idle currently ships with: `DeleteMessageWorker`, and `AcknowledgeMessageWorker`.

#### Job Config

```yaml
# Configure support for MessageJob, a job which runs in order to process a message.
!php/const LinioPay\Idle\Job\Jobs\MessageJob::IDENTIFIER:
  class: LinioPay\Idle\Job\Jobs\MessageJob
  parameters:
    # Configure support for Queue messages (originating from SQS, CloudTasks, etc)
    !php/const LinioPay\Idle\Message\Messages\Queue\Message::IDENTIFIER:
      my-queue:
        parameters:
          workers:
            # Perform Foo work
            - type: !php/const LinioPay\Idle\Job\Workers\FooWorker::IDENTIFIER
              # Provide optional parameters
              parameters:
                foo: bar
            # Delete the QueueMessage from the queue
            - type: !php/const LinioPay\Idle\Job\Workers\Queue\DeleteMessageWorker::IDENTIFIER
      my-task-queue:
        parameters:
          workers:
            - type: !php/const LinioPay\Idle\Job\Workers\FooWorker::IDENTIFIER
    !php/const LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage::IDENTIFIER:
      my-subscription:
        parameters:
          workers:
            # Perform Foo work
            - type: !php/const LinioPay\Idle\Job\Workers\FooWorker::IDENTIFIER
            # Acknowledge subscription message
            - type: !php/const LinioPay\Idle\Job\Workers\PublishSubscribe\AcknowledgeMessageWorker::IDENTIFIER

# Configure support for SimpleJob, a generic job which can run some workers.
!php/const LinioPay\Idle\Job\Jobs\SimpleJob::IDENTIFIER:
  class: LinioPay\Idle\Job\Jobs\SimpleJob
  parameters:
    supported:
      my-simple-job:
        parameters:
          workers:
            - type: !php/const LinioPay\Idle\Job\Workers\FooWorker::IDENTIFIER
```

#### Worker Config

```yaml
!php/const LinioPay\Idle\Job\Workers\FooWorker::IDENTIFIER:
  class: LinioPay\Idle\Job\Workers\FooWorker

!php/const LinioPay\Idle\Job\Workers\BazWorker::IDENTIFIER:
  class: LinioPay\Idle\Job\Workers\BazWorker

!php/const LinioPay\Idle\Job\Workers\Queue\DeleteMessageWorker::IDENTIFIER:
  class: LinioPay\Idle\Job\Workers\Queue\DeleteMessageWorker

!php/const LinioPay\Idle\Job\Workers\PublishSubscribe\AcknowledgeMessageWorker::IDENTIFIER:
  class: LinioPay\Idle\Job\Workers\PublishSubscribe\AcknowledgeMessageWorker
```

### SimpleJob

A `SimpleJob` is a minimally configured job which runs one or more workers and reports the outcome.

```php
    use LinioPay\Idle\Job\JobFactory;
    use LinioPay\Idle\Job\Jobs\SimpleJob;
    
    /** @var JobFactory $jobFactory */
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

`MessageJob` is a job which processes data from Messages. Creating a `MessageJob` is very straight forward when utilizing the `JobFactory`.

```php
    use LinioPay\Idle\Job\JobFactory;
    use LinioPay\Idle\Job\Jobs\MessageJob;
    
    /** @var JobFactory $jobFactory */
    $jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);
    
    $job = $jobFactory->createJob(MessageJob::IDENTIFIER, [
        'message' => [ // MessageJob require a `message` parameter, either as an array or an object.
            'message_identifier' => '123',
            'queue_identifier' => 'my-queue', 
            'body'=> 'hello queue payload!',
            'attributes' => [
                'foo' => 'bar',
            ]
        ]
    ]);
    
    $job->process();
    $success = $job->isSuccessful();
    $duration = $job->getDuration();
    $errors = $job->getErrors();
```

```php
    use LinioPay\Idle\Job\JobFactory;
    use LinioPay\Idle\Job\Jobs\MessageJob;
    
    /** @var JobFactory $jobFactory */
    $jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);
    
    $job = $jobFactory->createJob(MessageJob::IDENTIFIER, [
        // With an array, the factory will automatically convert to the appropriate message entity (SubscriptionMessage) and inject the corresponding messaging service.
        'message' => [ 
            'message_identifier' => '123',
            'subscription_identifier' => 'my-subscription', 
            'body'=> 'hello pubsub payload!',
            'attributes' => [
                'foo' => 'bar',
            ]
        ]
    ]);
    
    $job->process();
```

```php
    use LinioPay\Idle\Job\JobFactory;
    use LinioPay\Idle\Job\Jobs\MessageJob;
    use LinioPay\Idle\Message\MessageFactory;
    
    /** @var JobFactory $jobFactory */
    $jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);
    
    /** @var MessageFactory $messageFactory */
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    $message = $messageFactory->receiveMessageOrFail(['queue_identifier' => 'my-queue']);
    
    $job = $jobFactory->createJob(MessageJob::IDENTIFIER, [
        'message' => $message
    ]);
    
    $job->process();
```

```php
    use LinioPay\Idle\Job\JobFactory;
    use LinioPay\Idle\Job\Jobs\MessageJob;
    use LinioPay\Idle\Message\MessageFactory;
    
    /** @var JobFactory $jobFactory */
    $jobFactory = $container->get(\LinioPay\Idle\Job\JobFactory::class);
    
    /** @var MessageFactory $messageFactory */
    $messageFactory = $container->get(\LinioPay\Idle\Message\MessageFactory::class);
    
    $messages = $messageFactory->receiveMessages(['queue_identifier' => 'my-queue']);
    
    foreach($messages as $message)
    {
        $job = $jobFactory->createJob(MessageJob::IDENTIFIER, [
            'message' => $message
        ]);
        
        $job->process();
    }
```

### Outputting job results

Idle includes an optional `league/fractal` transformer to quickly output basic job details. This is located at `src/Job/Output/Transformer/JobDetails.php`. 

