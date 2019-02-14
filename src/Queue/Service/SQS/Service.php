<?php

declare(strict_types=1);

namespace LinioPay\Idle\Queue\Service\SQS;

use Aws\Result;
use Aws\Sqs\SqsClient;
use LinioPay\Idle\Queue\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Queue\Message;
use LinioPay\Idle\Queue\Service\DefaultService;
use Psr\Log\LoggerInterface;
use Throwable;

class Service extends DefaultService
{
    const IDENTIFIER = 'sqs';

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

    protected function getQueueUrl(string $queueIdentifier) : string
    {
        /** @var Result $result */
        $result = $this->client->getQueueUrl([
            'QueueName' => $queueIdentifier,
        ]);

        return (string) $result->get('QueueUrl');
    }

    public function queue(Message $message, array $parameters = []) : bool
    {
        $this->logger->info('Queueing message into queue', ['service' => Service::IDENTIFIER, 'message' => $message->toArray()]);

        try {
            $outParameters = array_replace_recursive($this->getQueueQueueingParameters($message->getQueueIdentifier()), $parameters, [
                'QueueUrl' => $this->getQueueUrl($message->getQueueIdentifier()),
                'MessageBody' => $message->getBody(),
                'MessageAttributes' => $message->getAttributes(),
            ]);

            /** @var Result $result */
            $result = $this->client->sendMessage($outParameters);

            $message->setMessageIdentifier((string) $result->get('MessageId'));

            return true;
        } catch (Throwable $throwable) {
            $this->logger->critical('Queueing encountered error', [
                'service' => Service::IDENTIFIER,
                'message' => $message->toArray(),
                'error' => $this->throwableToArray($throwable),
            ]);

            $queueIdentifier = $message->getQueueIdentifier();

            if (!$this->isQueueConfigured($queueIdentifier) || !$this->isQueueQueueingErrorSuppression($message->getQueueIdentifier())) {
                throw $throwable;
            }
        }

        return false;
    }

    public function dequeue(string $queueIdentifier, array $parameters = []) : array
    {
        $this->logger->info('Dequeueing message from queue', ['service' => Service::IDENTIFIER, 'queue' => $queueIdentifier]);

        try {
            $outParameters = array_replace_recursive($this->getQueueDequeueingParameters($queueIdentifier), $parameters, [
                'QueueUrl' => $this->getQueueUrl($queueIdentifier),
            ]);

            /** @var Result $result */
            $result = $this->client->receiveMessage($outParameters);

            return $this->buildMessagesFromResult($queueIdentifier, $result);
        } catch (Throwable $throwable) {
            $this->logger->critical('Dequeueing encountered error', [
                'service' => Service::IDENTIFIER,
                'queue' => $queueIdentifier,
                'error' => $this->throwableToArray($throwable),
            ]);

            if (!$this->isQueueConfigured($queueIdentifier) || !$this->isQueueDequeueingErrorSuppression($queueIdentifier)) {
                throw $throwable;
            }
        }

        return [];
    }

    public function delete(Message $message, array $parameters = []) : bool
    {
        $this->logger->info('Deleting message from queue', ['service' => Service::IDENTIFIER, 'message' => $message->toArray()]);

        try {
            $metadata = $message->getTemporaryMetadata();

            if (empty($metadata['ReceiptHandle'])) {
                throw new InvalidMessageParameterException('ReceiptHandle');
            }

            $this->client->deleteMessage([
                'QueueUrl' => $this->getQueueUrl($message->getQueueIdentifier()),
                'ReceiptHandle' => $metadata['ReceiptHandle'],
            ]);

            return true;
        } catch (Throwable $throwable) {
            $this->logger->critical('Deleting encountered error', [
                'service' => Service::IDENTIFIER,
                'message' => $message->toArray(),
                'error' => $this->throwableToArray($throwable),
            ]);

            $queueIdentifier = $message->getQueueIdentifier();

            if (!$this->isQueueConfigured($queueIdentifier) || !$this->isQueueDeletingErrorSuppression($queueIdentifier)) {
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

            $out[] = new Message($queueIdentifier, $body, $messageAttributes, $messageId, $metadata);
        }

        return $out;
    }
}
