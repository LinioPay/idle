<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Tracker\Service\DynamoDB;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Tracker\Service as TrackerService;
use Psr\Log\LoggerInterface;
use Throwable;

class Service implements TrackerService
{
    const IDENTIFIER = 'dynamodb';

    /** @var DynamoDbClient */
    protected $client;

    /** @var array */
    protected $config;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(DynamoDbClient $client, array $config, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function trackJob(Job $job) : void
    {
        try {
            $this->logger->debug('Idle tracking a job.', $job->getTrackerData());

            $this->client->putItem([
                'TableName' => $this->getTableName($job),
                'Item' => (new Marshaler())->marshalItem($job->getTrackerData()),
            ]);
        } catch (Throwable $throwable) {
            $this->logger->error('Idle tracking encountered an error.', [
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]);
        }
    }

    protected function getTableName(Job $job) : string
    {
        $parameters = $job->getParameters();
        $table = $parameters['tracker']['service']['table'] ?? '';

        if (empty($table)) {
            $this->logger->error('Idle tracking encountered an invalid configuration.', [
                'service' => self::IDENTIFIER,
                'job' => $job->getTypeIdentifier(), ]
            );

            throw new ConfigurationException($job->getTypeIdentifier());
        }

        return $table;
    }
}
