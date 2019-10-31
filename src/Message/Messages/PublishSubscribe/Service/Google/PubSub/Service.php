<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub;

use Google\Cloud\PubSub\Message as GoogleCloudMessage;
use Google\Cloud\PubSub\PubSubClient;
use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Message\PulledMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\PublishableMessage as PublishableMessageInterface;
use LinioPay\Idle\Message\Messages\PublishSubscribe\PulledMessage as PulledMessageInterface;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Service\DefaultService;
use Psr\Log\LoggerInterface;
use Throwable;

class Service extends DefaultService
{
    const IDENTIFIER = 'google-pubsub';

    /** @var TestPubSubClient */
    protected $client;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(PubSubClient $client, array $config, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function publish(PublishableMessageInterface $message, array $parameters = []) : bool
    {
        $this->logger->info('Idle publishing a message.', ['service' => self::IDENTIFIER, 'message' => $message->toArray()]);

        try {
            $topicIdentifier = $message->getTopicIdentifier();
            $topic = $this->client->topic($topicIdentifier);

            // Publish a message to the topic.
            $result = $topic->publish([
                'data' => $message->getBody(),
                'attributes' => $message->getAttributes(),
            ], array_replace_recursive(
                $this->getPublishParameterConfig(),
                $parameters
            ));

            $message->setMessageId((string) $result['messageIds'][0] ?? '');

            return true;
        } catch (Throwable $throwable) {
            $this->logger->critical('Idle publish encountered an error.', [
                'service' => self::IDENTIFIER,
                'message' => $message->toArray(),
                'error' => $this->throwableToArray($throwable),
            ]);

            if (!$this->isPublishErrorSuppressed()) {
                throw $throwable;
            }
        }

        return false;
    }

    /**
     * @return PulledMessage[]
     *
     * @throws Throwable
     */
    public function pull(string $subscriptionIdentifier, array $parameters = []) : array
    {
        $this->logger->info('Idle pulling a message.', ['service' => self::IDENTIFIER, 'subscription' => $subscriptionIdentifier]);

        try {
            $subscription = $this->client->subscription($subscriptionIdentifier);

            $result = $subscription->pull(array_replace_recursive(
                $this->getPullParameterConfig(),
                $parameters
            ));

            return $this->buildMessagesFromResult($result);
        } catch (Throwable $throwable) {
            $this->logger->critical('Idle pull encountered an error.', [
                'service' => self::IDENTIFIER,
                'queue' => $subscriptionIdentifier,
                'error' => $this->throwableToArray($throwable),
            ]);

            if (!$this->isPullErrorSuppressed()) {
                throw $throwable;
            }
        }

        return [];
    }

    public function acknowledge(PulledMessageInterface $message, array $parameters = []) : bool
    {
        $this->logger->info('Idle acknowledging a message.', ['service' => self::IDENTIFIER, 'message' => $message->toArray()]);

        try {
            $metadata = $message->getTemporaryMetadata();

            if (empty($metadata['gcMessage']) || !is_a($metadata['gcMessage'], GoogleCloudMessage::class)) {
                throw new InvalidMessageParameterException('gcMessage');
            }

            $subscription = $this->client->subscription($message->getSubscriptionIdentifier());

            $subscription->acknowledge($metadata['gcMessage'], array_replace_recursive(
                $this->getAcknowledgeParameterConfig(),
                $parameters
            ));

            return true;
        } catch (Throwable $throwable) {
            $this->logger->critical('Idle acknowledge encountered an error.', [
                'service' => self::IDENTIFIER,
                'message' => $message->toArray(),
                'error' => $this->throwableToArray($throwable),
            ]);

            if (!$this->isAcknowledgeErrorSuppressed()) {
                throw $throwable;
            }
        }

        return false;
    }

    /**
     * @return PulledMessage[]
     */
    protected function buildMessagesFromResult(array $resultMessages) : array
    {
        $out = [];

        /** @var GoogleCloudMessage $message */
        foreach ($resultMessages as $message) {
            $out[] = new PulledMessage(
                $message->subscription()->name(),
                $message->data(),
                $message->attributes(),
                $message->id(),
                [
                    'gcMessage' => $message,
                ]
            );
        }

        return $out;
    }
}
