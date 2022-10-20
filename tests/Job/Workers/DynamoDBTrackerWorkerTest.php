<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use Aws\DynamoDb\DynamoDbClient;
use Exception;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Workers\Exception\InvalidWorkerParameterException;
use LinioPay\Idle\TestCase;
use Mockery as m;
use Psr\Log\LoggerInterface;

class DynamoDBTrackerWorkerTest extends TestCase
{
    public function testItLogsDetailsAfterThrowable()
    {
        $trackerData = [
            'id' => 'foo_id',
            'success' => true,
        ];

        $job = m::mock(Job::class);
        $job->shouldReceive('getTrackerData')
            ->twice()
            ->andReturn($trackerData);

        $client = m::mock(DynamoDbClient::class)->shouldAllowMockingMethod('putItem');
        $client->shouldReceive('putItem')
            ->once()
            ->andThrow(new Exception('kaboom!'));

        $config = [];

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('debug')
            ->once()
            ->with('Idle tracking a job.', $trackerData);
        $logger->shouldReceive('error')
            ->once()
            ->with('Idle tracking encountered an error.', m::on(function ($context) {
                $this->assertArrayHasKey('message', $context);
                $this->assertArrayHasKey('code', $context);
                $this->assertArrayHasKey('file', $context);
                $this->assertArrayHasKey('line', $context);

                return true;
            }));

        $worker = new DynamoDBTrackerWorker($client, $config, $logger);
        $worker->setParameters([
            'job' => $job,
            'table' => 'foo_table',
        ]);

        $worker->work();
    }

    public function testItTracksJobData()
    {
        $trackerData = [
            'id' => 'foo_id',
            'success' => true,
        ];

        $job = m::mock(Job::class);
        $job->shouldReceive('getTrackerData')
            ->twice()
            ->andReturn($trackerData);

        $client = m::mock(DynamoDbClient::class)->shouldAllowMockingMethod('putItem');
        $client->shouldReceive('putItem')
            ->once()
            ->with([
                'TableName' => 'foo_table',
                'Item' => [
                    'id' => [
                        'S' => 'foo_id',
                    ],
                    'success' => [
                        'BOOL' => true,
                    ],
                ],
            ]);

        $config = [];

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('debug')
            ->once()
            ->with('Idle tracking a job.', $trackerData);

        $worker = new DynamoDBTrackerWorker($client, $config, $logger);
        $worker->setParameters([
            'job' => $job,
            'table' => 'foo_table',
        ]);
        $worker->validateParameters();

        $this->assertTrue($worker->work());
    }

    public function testValidateParametersThrowsInvalidWorkerParameterExceptionWhenMissingJob()
    {
        $client = m::mock(DynamoDbClient::class);

        $config = [];

        $logger = m::mock(LoggerInterface::class);

        $worker = new DynamoDBTrackerWorker($client, $config, $logger);
        $worker->setParameters([
        ]);

        $this->expectException(InvalidWorkerParameterException::class);
        $worker->validateParameters();
    }

    public function testValidateParametersThrowsInvalidWorkerParameterExceptionWhenMissingTable()
    {
        $client = m::mock(DynamoDbClient::class);

        $config = [];

        $logger = m::mock(LoggerInterface::class);

        $worker = new DynamoDBTrackerWorker($client, $config, $logger);
        $worker->setParameters([
            'job' => m::mock(Job::class),
        ]);

        $this->expectException(InvalidWorkerParameterException::class);
        $worker->validateParameters();
    }
}
