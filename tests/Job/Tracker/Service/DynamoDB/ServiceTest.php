<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Tracker\Service\DynamoDB;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;

class ServiceTest extends TestCase
{
    /** @var TestHandler */
    protected $apiTestHandler;

    /** @var Logger */
    protected $logger;

    /** @var array */
    protected $config;

    /** @var m\Mock|DynamoDbClient */
    protected $dynamoDBClient;

    protected function setUp() : void
    {
        $this->apiTestHandler = new TestHandler();

        $this->logger = new \Monolog\Logger('api', [$this->apiTestHandler]);

        $this->dynamoDBClient = m::mock(DynamoDbClient::class);

        $this->config = [
            'type' => Service::class,
            'client' => [
                'version' => 'latest',
                'region' => 'us-east-1',
            ],
        ];
    }

    public function testCanTrackJobSuccessfully()
    {
        $trackerData = [
            'id' => Uuid::uuid1()->toString(),
            'start' => '2019-01-01 00:00:00',
            'duration' => 5,
            'successful' => true,
            'finished' => true,
        ];

        $job = m::mock(Job::class);
        $job->shouldReceive('getTrackerData')
            ->andReturn($trackerData);
        $job->shouldReceive('getParameters')
            ->andReturn([
               'tracker' => [
                   'service' => [
                       'table' => 'mytable',
                   ],
               ],
            ]);

        $this->dynamoDBClient->shouldAllowMockingMethod('putItem');
        $this->dynamoDBClient->shouldReceive('putItem')
            ->once()
            ->with([
                'TableName' => 'mytable',
                'Item' => (new Marshaler())->marshalItem($trackerData),
            ]);

        $service = new Service($this->dynamoDBClient, $this->config, $this->logger);

        $service->trackJob($job);

        $records = $this->apiTestHandler->getRecords();

        $this->assertArrayHasKey(0, $records);
        $this->assertArrayHasKey('message', $records[0]);
        $this->assertSame('Idle tracking a job.', $records[0]['message']);
    }

    public function testCanLogAndSuppressErrorsWhenEncounteringException()
    {
        $trackerData = [
            'id' => Uuid::uuid1()->toString(),
        ];

        $job = m::mock(Job::class);
        $job->shouldReceive('getTrackerData')
            ->andReturn($trackerData);
        $job->shouldReceive('getTypeIdentifier')
            ->andReturn('foo');
        $job->shouldReceive('getParameters')
            ->andReturn([]);

        $service = new Service($this->dynamoDBClient, $this->config, $this->logger);

        $service->trackJob($job);

        $records = $this->apiTestHandler->getRecords();

        $this->assertArrayHasKey(0, $records);
        $this->assertArrayHasKey('message', $records[0]);
        $this->assertSame('Idle tracking a job.', $records[0]['message']);

        $this->assertArrayHasKey(1, $records);
        $this->assertArrayHasKey('message', $records[1]);
        $this->assertSame('Idle tracking encountered an invalid configuration.', $records[1]['message']);

        $this->assertArrayHasKey(2, $records);
        $this->assertArrayHasKey('message', $records[2]);
        $this->assertSame('Idle tracking encountered an error.', $records[2]['message']);
    }
}
