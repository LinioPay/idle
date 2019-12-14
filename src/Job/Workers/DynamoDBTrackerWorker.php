<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use LinioPay\Idle\Job\Exception\InvalidWorkerParameterException;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\TrackingWorker as TrackingWorkerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class DynamoDBTrackerWorker extends DefaultWorker implements TrackingWorkerInterface
{
    use TrackingWorker;

    const IDENTIFIER = 'dynamodb_tracker_worker';

    /** @var DynamoDbClient */
    protected $client;

    /** @var array */
    protected $config;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Job */
    protected $job;

    /** @var string */
    protected $table;

    public function __construct(DynamoDbClient $client, array $config, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function work() : bool
    {
        try {
            $this->logger->debug('Idle tracking a job.', $this->job->getTrackerData());

            $this->client->putItem([
                'TableName' => $this->table,
                'Item' => (new Marshaler())->marshalItem($this->job->getTrackerData()),
            ]);
        } catch (Throwable $throwable) {
            $this->logger->error('Idle tracking encountered an error.', [
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]);
        }

        // Tracking should not have impact on the job itself
        return true;
    }

    public function setParameters(array $parameters) : void
    {
        parent::setParameters($parameters);

        $this->job = $parameters['job'] ?? null;
        $this->table = $parameters['table'] ?? '';
    }

    public function validateParameters() : void
    {
        if (!isset($this->parameters['job']) || !is_a($this->parameters['job'], Job::class)) {
            throw new InvalidWorkerParameterException($this, 'job');
        }

        if (empty($this->parameters['table'])) {
            throw new InvalidWorkerParameterException($this, 'table');
        }
    }
}
