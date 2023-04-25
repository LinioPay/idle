<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\SQS;

use Aws\Result;
use Aws\Sqs\SqsClient;
use LinioPay\Idle\Message\Exception\FailedReceivingMessageException;
use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessageInterface;
use LinioPay\Idle\Message\Messages\Queue\Message\Message as QueueMessage;
use LinioPay\Idle\Message\Messages\Queue\Service\DefaultService;
use Psr\Log\LoggerInterface;
use Throwable;

class Service extends DefaultService
{
    public const IDENTIFIER = 'sqs';

    /** @var SqsClient */
    protected $client;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(SqsClient $client, array $config, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function delete(QueueMessageInterface $message, array $parameters = []) : bool
    {
        $this->logger->info('Idle deleting a message from queue', [
            'message' => $message->toArray(),
            'service' => Service::IDENTIFIER,
        ]);

        try {
            $metadata = $message->getTemporaryMetadata();

            if (empty($metadata['ReceiptHandle'])) {
                throw new InvalidMessageParameterException('ReceiptHandle');
            }

            $this->client->deleteMessage(array_replace_recursive(
                $this->getDeletingParameters(), $parameters, [
                    'QueueUrl' => $this->getQueueUrl($message->getQueueIdentifier()),
                    'ReceiptHandle' => $metadata['ReceiptHandle'],
                ]
            ));

            $this->logger->info('Idle successfully deleted a message from queue', [
                'message' => $message->toArray(),
                'service' => Service::IDENTIFIER,
            ]);

            return true;
        } catch (Throwable $throwable) {
            $this->logger->critical('Idle delete encountered an error', [
                'message' => $message->toArray(),
                'service' => Service::IDENTIFIER,
                'error' => $this->throwableToArray($throwable),
            ]);

            if (!$this->isDeletingErrorSuppression()) {
                throw $throwable;
            }
        }

        return false;
    }

    public function dequeue(string $queueIdentifier, array $parameters = []) : array
    {
        $this->logger->info('Idle dequeuing messages(s).', [
            'service' => Service::IDENTIFIER,
            'queue' => $queueIdentifier,
        ]);

        try {
            $outParameters = array_replace_recursive($this->getDequeueingParameters(), $parameters, [
                'QueueUrl' => $this->getQueueUrl($queueIdentifier),
            ]);

            /** @var Result $result */
            $result = $this->client->receiveMessage($outParameters);

            $this->logger->info(
                sprintf('Idle successfully dequeued %s messages(s).', $result->count()), [
                'service' => Service::IDENTIFIER,
                'queue' => $queueIdentifier,
            ]);

            return $this->buildMessagesFromResult($queueIdentifier, $result);
        } catch (Throwable $throwable) {
            $this->logger->critical('Idle dequeue encountered an error.', [
                'service' => Service::IDENTIFIER,
                'queue' => $queueIdentifier,
                'error' => $this->throwableToArray($throwable),
            ]);

            if (!$this->isDequeueingErrorSuppression()) {
                throw $throwable;
            }
        }

        return [];
    }

    public function dequeueOneOrFail(string $queueIdentifier, array $parameters = []) : MessageInterface
    {
        try {
            $messages = $this->dequeue($queueIdentifier, array_merge($parameters, ['MaxNumberOfMessages' => 1]));
        } catch (Throwable $throwable) {
        } finally {
            if (empty($messages) || !is_a($messages[0], MessageInterface::class)) {
                throw new FailedReceivingMessageException(self::IDENTIFIER, QueueMessageInterface::IDENTIFIER, $queueIdentifier, $throwable ?? null);
            }
        }

        return $messages[0];
    }

    public function queue(QueueMessageInterface $message, array $parameters = []) : bool
    {
        $this->logger->info('Idle queuing a message.', [
            'message' => $message->toArray(),
            'service' => Service::IDENTIFIER,
        ]);

        try {
            $outParameters = array_replace_recursive($this->getQueueingParameters(), $parameters, [
                'QueueUrl' => $this->getQueueUrl($message->getQueueIdentifier()),
                'MessageBody' => $message->getBody(),
                'MessageAttributes' => $message->getAttributes(),
            ]);

            /** @var Result $result */
            $result = $this->client->sendMessage($outParameters);

            $message->setMessageId((string) $result->get('MessageId'));

            $this->logger->info('Idle successfully queued a message.', [
                'message' => $message->toArray(),
                'service' => Service::IDENTIFIER,
            ]);

            return true;
        } catch (Throwable $throwable) {
            $this->logger->critical('Idle queue encountered an error.', [
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

    protected function buildMessagesFromResult(string $queueIdentifier, Result $result) : array
    {
        $out = [];

        $resultMessages = (array) $result->get('Messages');

        foreach ($resultMessages as $message) {
            $messageId = $message['MessageId'] ?? '';
            $body = $message['Body'] ?? '';
            $messageAttributes = $message['MessageAttributes'] ?? [];
            $metadata = [
                'ReceiptHandle' => $message['ReceiptHandle'] ?? '',
                'MD5OfBody' => $message['MD5OfBody'] ?? '',
                'MD5OfMessageAttributes' => $message['MD5OfMessageAttributes'] ?? '',
            ];

            $queueMessage = new QueueMessage($queueIdentifier, $body, $messageAttributes, $messageId, $metadata);

            $queueMessage->setService($this);

            $out[] = $queueMessage;
        }

        return $out;
    }

    protected function getQueueUrl(string $queueIdentifier) : string
    {
        /** @var Result $result */
        $result = $this->client->getQueueUrl([
            'QueueName' => $queueIdentifier,
        ]);

        return (string) $result->get('QueueUrl');
    }
}
