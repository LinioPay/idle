<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks;

use Google\Cloud\Tasks\V2\CloudTasksClient;
use Google\Cloud\Tasks\V2\HttpMethod;
use Google\Cloud\Tasks\V2\HttpRequest;
use Google\Cloud\Tasks\V2\Task;
use LinioPay\Idle\Message\Exception\FailedReceivingMessageException;
use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Exception\InvalidServiceConfigurationException;
use LinioPay\Idle\Message\Exception\UnsupportedServiceOperationException;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessageInterface;
use LinioPay\Idle\Message\Messages\Queue\Service\DefaultService;
use LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Exception\InvalidMessageRequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Service extends DefaultService
{
    const IDENTIFIER = 'cloud-tasks';

    /** @var string[] Arguments which we want to extract from message attributes into HttpRequest */
    const HTTP_REQUEST_ATTRIBUTES = ['oauth_token', 'oidc_token'];

    /** @var CloudTasksClient */
    protected $client;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(CloudTasksClient $client, array $config, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->client->close();
    }

    public function queue(QueueMessageInterface $message, array $parameters = []) : bool
    {
        $this->logger->info('Idle queuing a message.', ['service' => Service::IDENTIFIER, 'message' => $message->toArray()]);

        try {
            $mergedParameters = array_replace_recursive($this->getQueueingParameters(), $parameters);

            $response = $this->client->createTask(
                $this->getGCPQueueName($message, $mergedParameters),
                $this->getTaskFromMessage($message),
                $mergedParameters
            );

            $message->setMessageId($response->getName());
            $message->setTemporaryMetadata([
                'task' => $response,
            ]);

            return true;
        } catch (Throwable $throwable) {
            $this->logger->critical('Idle queuing encountered an error.', [
                'service' => Service::IDENTIFIER,
                'message' => $message->toArray(),
                'error' => $this->throwableToArray($throwable),
            ]);

            if (!$this->isQueueingErrorSuppression()) {
                throw $throwable;
            }
        }

        return false;
    }

    /**
     * Pulling from CloudTasks is currently not supported.
     *
     * @throws UnsupportedServiceOperationException
     */
    public function dequeue(string $queueIdentifier, array $parameters = []) : array
    {
        $this->logger->critical('Idle attempted to dequeue from CloudTask queue but operation is not supported.', ['service' => Service::IDENTIFIER, 'queue' => $queueIdentifier]);

        if (!$this->isDequeueingErrorSuppression()) {
            throw new UnsupportedServiceOperationException(Service::IDENTIFIER, 'dequeue');
        }

        return [];
    }

    public function dequeueOneOrFail(string $queueIdentifier, array $parameters = []) : MessageInterface
    {
        throw new FailedReceivingMessageException(self::IDENTIFIER, QueueMessageInterface::IDENTIFIER, $queueIdentifier, new UnsupportedServiceOperationException(Service::IDENTIFIER, 'dequeue'));
    }

    public function delete(QueueMessageInterface $message, array $parameters = []) : bool
    {
        $this->logger->info('Deleting message from queue', ['service' => Service::IDENTIFIER, 'message' => $message->toArray()]);

        try {
            $messageId = $message->getMessageId();

            if (empty($messageId)) {
                throw new InvalidMessageParameterException('messageId');
            }

            $this->client->deleteTask($messageId, array_replace_recursive($this->getDeletingParameters(), $parameters));

            return true;
        } catch (Throwable $throwable) {
            $this->logger->critical('Deleting encountered error', [
                'service' => Service::IDENTIFIER,
                'message' => $message->toArray(),
                'error' => $this->throwableToArray($throwable),
            ]);

            if (!$this->isDeletingErrorSuppression()) {
                throw $throwable;
            }
        }

        return false;
    }

    protected function getGCPQueueName(QueueMessageInterface $message, array $parameters) : string
    {
        $serviceConfig = array_replace_recursive($this->getServiceConfig(), $parameters);

        $this->validateServiceConfig($serviceConfig);

        return $this->client->queueName($serviceConfig['client']['projectId'], $serviceConfig['client']['location'], $message->getQueueIdentifier());
    }

    protected function validateServiceConfig(array $serviceConfig) : void
    {
        if (empty($serviceConfig['client']['projectId']) || empty($serviceConfig['client']['location'])) {
            throw new InvalidServiceConfigurationException(static::IDENTIFIER, 'project|location');
        }
    }

    protected function getRequestFromMessage(QueueMessageInterface $message) : RequestInterface
    {
        $attributes = $message->getAttributes();
        $request = $attributes['request'] ?? false;

        if (!$request || !is_a($request, RequestInterface::class)) {
            throw new InvalidMessageRequestException();
        }

        return $request;
    }

    protected function getTaskFromMessage(QueueMessageInterface $message) : Task
    {
        $request = $this->getRequestFromMessage($message);

        $body = $request->getBody();
        $body->rewind();

        return new Task([
            'http_request' => new HttpRequest(
                array_merge(
                    [
                        'url' => (string) $request->getUri(),
                        'http_method' => HttpMethod::value(strtoupper($request->getMethod())),
                        'headers' => array_map(function ($headerValue) {
                            return $headerValue[0] ?? '';
                        }, $request->getHeaders()),
                        'body' => $body->getContents(),
                    ],
                    array_intersect_key($message->getAttributes(), array_flip(self::HTTP_REQUEST_ATTRIBUTES))
                )
            ),
        ]);
    }
}
