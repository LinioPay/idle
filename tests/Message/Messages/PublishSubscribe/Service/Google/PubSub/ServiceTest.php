<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Service\Google\PubSub;

use Exception;
use Google\Cloud\PubSub\Message as GoogleCloudMessage;
use LinioPay\Idle\Message\Exception\FailedReceivingMessageException;
use LinioPay\Idle\Message\Exception\InvalidMessageParameterException;
use LinioPay\Idle\Message\Message as MessageInterface;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Message\SubscriptionMessage;
use LinioPay\Idle\Message\Messages\PublishSubscribe\Message\TopicMessage;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Monolog\Handler\TestHandler;

class ServiceTest extends TestCase
{
    protected $apiTestHandler;

    protected $client;

    protected $config;

    protected $logger;

    protected function setUp() : void
    {
        $this->apiTestHandler = new TestHandler();

        $this->logger = new \Monolog\Logger('api', [$this->apiTestHandler]);

        $this->config = [
            'publish' => [
                'parameters' => [
                    'red' => true,
                ],
                'error' => [
                    'suppression' => true,
                ],
            ],
            'pull' => [
                'parameters' => [
                    'red' => true,
                ],
                'error' => [
                    'suppression' => true,
                ],
            ],
            'acknowledge' => [
                'parameters' => [
                    'red' => true,
                ],
                'error' => [
                    'suppression' => true,
                ],
            ],
            'parameters' => [
                'service' => [
                    'foo' => 'bar',
                ],
            ],
        ];

        $this->client = m::mock(TestPubSubClient::class);
    }

    public function testAcknowledgeBubblesUpExceptions() : void
    {
        $subscriptionIdentifier = 'foo-subscription';
        $message = new SubscriptionMessage($subscriptionIdentifier, 'mbody', ['green' => true], 'fooId', [
            'gcMessage' => $this->fake(GoogleCloudMessage::class),
        ]);

        $this->client->shouldReceive('subscription')
            ->once()
            ->with($subscriptionIdentifier)
            ->andThrow(new Exception('kaboom!'));

        $this->config['acknowledge']['error']['suppression'] = false;
        $service = new Service($this->client, $this->config, $this->logger);
        $this->expectException(Exception::class);
        $service->acknowledge($message, ['blue' => true]);

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle acknowledge encountered an error.', $records[1]['message']);
    }

    public function testAcknowledgeDoesNotBubbleUpExceptions() : void
    {
        $subscriptionIdentifier = 'foo-subscription';
        $message = new SubscriptionMessage($subscriptionIdentifier, 'mbody', ['green' => true], 'fooId', [
            'gcMessage' => $this->fake(GoogleCloudMessage::class),
        ]);

        $this->client->shouldReceive('subscription')
            ->once()
            ->with($subscriptionIdentifier)
            ->andThrow(new Exception('kaboom!'));

        $this->config['acknowledge']['error']['suppression'] = true;
        $service = new Service($this->client, $this->config, $this->logger);
        $this->assertFalse($service->acknowledge($message, ['blue' => true]));

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle acknowledge encountered an error.', $records[1]['message']);
    }

    public function testCanAcknowledgeSuccessfully() : void
    {
        $subscriptionIdentifier = 'foo-subscription';

        $subscription = m::mock(TestSubscription::class);

        $gcMessage = $this->fake(GoogleCloudMessage::class, [
            'subscription' => $subscription,
            'message' => [
                'data' => 'mbody',
                'attributes' => ['green' => true],
                'messageId' => 'fooId',
            ],
        ]);
        $message = new SubscriptionMessage($subscriptionIdentifier, 'mbody', ['green' => true], 'fooId', [
            'gcMessage' => $gcMessage,
        ]);

        $subscription->shouldReceive('acknowledge')
            ->once()
            ->with($gcMessage, m::on(function ($options) {
                $this->assertArrayHasKey('red', $options);
                $this->assertArrayHasKey('blue', $options);

                return true;
            }));

        $this->client->shouldReceive('subscription')
            ->once()
            ->with($subscriptionIdentifier)
            ->andReturn($subscription);

        $service = new Service($this->client, $this->config, $this->logger);
        $this->assertTrue($service->acknowledge($message, ['blue' => true]));
        $this->assertSame('fooId', $message->getMessageId());
        $this->assertSame($this->config, $service->getConfig());

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[0]);
        $this->assertSame('Idle acknowledging a message.', $records[0]['message']);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle successfully acknowledged a message.', $records[1]['message']);
    }

    public function testCanPublishSuccessfully() : void
    {
        $topicIdentifier = 'foo-topic';
        $message = new TopicMessage($topicIdentifier, 'mbody', ['green' => true]);

        $topic = m::mock(TestTopic::class);
        $topic->shouldReceive('publish')
            ->once()
            ->with(m::on(function ($message) {
                $this->assertSame('mbody', $message['data']);
                $this->assertArrayHasKey('attributes', $message);
                $this->assertArrayHasKey('green', $message['attributes']);

                return true;
            }), m::on(function ($options) {
                $this->assertArrayHasKey('red', $options);
                $this->assertArrayHasKey('blue', $options);

                return true;
            }))
            ->andReturn([
                'messageIds' => [
                    'fooid',
                ],
            ]);

        $this->client->shouldReceive('topic')
            ->once()
            ->with($topicIdentifier)
            ->andReturn($topic);

        $service = new Service($this->client, $this->config, $this->logger);
        $this->assertTrue($service->publish($message, ['blue' => true]));
        $this->assertSame('fooid', $message->getMessageId());
        $this->assertSame($this->config, $service->getConfig());
        $this->assertSame($this->config['parameters']['service'], $service->getServiceConfig());

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[0]);
        $this->assertSame('Idle publishing a message.', $records[0]['message']);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle successfully published a message.', $records[1]['message']);
    }

    public function testCanPullSuccessfully() : void
    {
        $subscriptionIdentifier = 'foo-subscription';

        $subscription = m::mock(TestSubscription::class);

        $gcMessage = $this->fake(GoogleCloudMessage::class, [
            'subscription' => $subscription,
            'message' => [
                'data' => 'mbody',
                'attributes' => ['green' => true],
                'messageId' => 'fooId',
            ],
        ]);

        $subscription->shouldReceive('name')
            ->andReturn($subscriptionIdentifier);
        $subscription->shouldReceive('pull')
            ->once()
            ->with(m::on(function ($options) {
                $this->assertArrayHasKey('red', $options);
                $this->assertArrayHasKey('blue', $options);

                return true;
            }))
            ->andReturn([
                $gcMessage,
            ]);

        $this->client->shouldReceive('subscription')
            ->once()
            ->with($subscriptionIdentifier)
            ->andReturn($subscription);

        $service = new Service($this->client, $this->config, $this->logger);
        $messages = $service->pull($subscriptionIdentifier, ['blue' => true]);

        $this->assertCount(1, $messages);
        /** @var SubscriptionMessage $message */
        $message = $messages[0];

        $this->assertSame('mbody', $message->getBody());
        $this->assertSame(['green' => true], $message->getAttributes());
        $this->assertSame('fooId', $message->getMessageId());

        $metadata = $message->getTemporaryMetadata();
        $this->assertArrayHasKey('gcMessage', $metadata);
        $this->assertSame($gcMessage, $metadata['gcMessage']);

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[0]);
        $this->assertSame('Idle pulling from subscription.', $records[0]['message']);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle pulled 1 message(s) from subscription.', $records[1]['message']);
    }

    public function testPublishBubblesUpExceptions() : void
    {
        $topicIdentifier = 'foo-topic';
        $message = new TopicMessage($topicIdentifier, 'mbody', ['green' => true]);

        $this->client->shouldReceive('topic')
            ->once()
            ->with($topicIdentifier)
            ->andThrow(new Exception('kaboom!'));

        $this->config['publish']['error']['suppression'] = false;
        $service = new Service($this->client, $this->config, $this->logger);
        $this->expectException(Exception::class);
        $service->publish($message, ['blue' => true]);

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle publish encountered an error.', $records[1]['message']);
    }

    public function testPublishDoesNotBubbleUpExceptions() : void
    {
        $topicIdentifier = 'foo-topic';
        $message = new TopicMessage($topicIdentifier, 'mbody', ['green' => true]);

        $this->client->shouldReceive('topic')
            ->once()
            ->with($topicIdentifier)
            ->andThrow(new Exception('kaboom!'));

        $this->config['publish']['error']['suppression'] = true;
        $service = new Service($this->client, $this->config, $this->logger);
        $this->assertFalse($service->publish($message, ['blue' => true]));

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle publish encountered an error.', $records[1]['message']);
    }

    public function testPullBubblesUpExceptions() : void
    {
        $subscriptionIdentifier = 'foo-subscription';
        $this->client->shouldReceive('subscription')
            ->once()
            ->with($subscriptionIdentifier)
            ->andThrow(new Exception('kaboom!'));

        $this->config['pull']['error']['suppression'] = false;
        $service = new Service($this->client, $this->config, $this->logger);
        $this->expectException(Exception::class);
        $service->pull($subscriptionIdentifier, ['blue' => true]);

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle pull encountered an error.', $records[1]['message']);
    }

    public function testPullDoesNotBubbleUpExceptions() : void
    {
        $subscriptionIdentifier = 'foo-subscription';
        $this->client->shouldReceive('subscription')
            ->once()
            ->with($subscriptionIdentifier)
            ->andThrow(new Exception('kaboom!'));

        $this->config['pull']['error']['suppression'] = true;
        $service = new Service($this->client, $this->config, $this->logger);
        $pulled = $service->pull($subscriptionIdentifier, ['blue' => true]);
        $this->assertIsArray($pulled);
        $this->assertEmpty($pulled);

        $records = $this->apiTestHandler->getRecords();
        $this->assertCount(2, $records);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle pull encountered an error.', $records[1]['message']);
    }

    public function testPullOneFails()
    {
        $subscriptionIdentifier = 'foo-subscription';

        $subscription = m::mock(TestSubscription::class);

        $subscription->shouldReceive('name')
            ->andReturn($subscriptionIdentifier);
        $subscription->shouldReceive('pull')
            ->once()
            ->andThrow(new Exception('kaboom!'));

        $this->client->shouldReceive('subscription')
            ->once()
            ->with($subscriptionIdentifier)
            ->andReturn($subscription);

        $this->config['pull']['error']['suppression'] = false;
        $service = new Service($this->client, $this->config, $this->logger);
        $this->expectException(FailedReceivingMessageException::class);
        $service->pullOneOrFail($subscriptionIdentifier, ['blue' => true]);
    }

    public function testPullOneSuccessfully()
    {
        $subscriptionIdentifier = 'foo-subscription';

        $subscription = m::mock(TestSubscription::class);

        $gcMessage = $this->fake(GoogleCloudMessage::class, [
            'subscription' => $subscription,
            'message' => [
                'data' => 'mbody',
                'attributes' => ['green' => true],
                'messageId' => 'fooId',
            ],
        ]);

        $subscription->shouldReceive('name')
            ->andReturn($subscriptionIdentifier);
        $subscription->shouldReceive('pull')
            ->once()
            ->andReturn([
                $gcMessage,
            ]);

        $this->client->shouldReceive('subscription')
            ->once()
            ->with($subscriptionIdentifier)
            ->andReturn($subscription);

        $service = new Service($this->client, $this->config, $this->logger);
        $message = $service->pullOneOrFail($subscriptionIdentifier, ['blue' => true]);
        $this->assertInstanceOf(MessageInterface::class, $message);
    }

    public function testThrowsInvalidMessageParameterExceptionWhenMissingGCMessage() : void
    {
        $subscriptionIdentifier = 'foo-subscription';
        $message = new SubscriptionMessage($subscriptionIdentifier, 'mbody', ['green' => true], 'fooId', []);

        $this->config['acknowledge']['error']['suppression'] = false;
        $service = new Service($this->client, $this->config, $this->logger);
        $this->expectException(InvalidMessageParameterException::class);
        $service->acknowledge($message, ['blue' => true]);
    }
}
