<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use DateTime;
use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\Tracker\Service as TrackerService;
use LinioPay\Idle\Job\Tracker\Service\Factory\Service as TrackerServiceFactoryInterface;
use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\Workers\Factory\Worker as WorkerFactoryInterface;
use Ramsey\Uuid\Uuid;

abstract class DefaultJob implements Job
{
    const IDENTIFIER = '';

    /** @var Uuid */
    protected $trackerId;

    /** @var DateTime */
    protected $startDate;

    /** @var bool */
    protected $successful = false;

    /** @var float */
    protected $duration = 0.0;

    /** @var array */
    protected $config = [];

    /** @var array */
    protected $parameters;

    /** @var array */
    protected $errors = [];

    /** @var WorkerInterface */
    protected $worker;

    /** @var WorkerFactoryInterface */
    protected $workerFactory;

    /** @var TrackerService */
    protected $trackerService;

    /** @var TrackerServiceFactoryInterface */
    protected $trackerServiceFactory;

    /** @var bool */
    protected $finished = false;

    public function isSuccessful() : bool
    {
        return $this->successful;
    }

    public function getDuration() : float
    {
        return $this->duration;
    }

    public function getErrors() : array
    {
        return array_merge($this->errors, $this->worker->getErrors());
    }

    public function getTrackerId() : Uuid
    {
        return $this->trackerId;
    }

    public function getStartDate() : DateTime
    {
        return $this->startDate;
    }

    public function process() : void
    {
        $this->trackerId = Uuid::uuid1();
        $this->startDate = new DateTime();

        $start = microtime(true);

        try {
            $this->buildTrackerService();
            $this->persistTracker();

            $this->validate();

            $this->successful = $this->worker->work();
        } catch (\Throwable $throwable) {
            $this->errors[] = sprintf('Encountered an error: %s', $throwable->getMessage());

            throw $throwable;
        } finally {
            $this->duration = microtime(true) - $start;

            $this->finished = true;

            $this->persistTracker();
        }
    }

    protected function persistTracker() : void
    {
        if (is_null($this->trackerService)) {
            return;
        }

        $this->trackerService->trackJob($this);
    }

    public function getTrackerData() : array
    {
        return array_merge([
            'id' => $this->getTrackerId()->toString(),
            'start' => $this->getStartDate()->format('Y-m-d H:i:s'),
            'duration' => $this->getDuration(),
            'successful' => $this->isSuccessful(),
            'finished' => $this->isFinished(),
            'errors' => json_encode($this->getErrors()),
            'parameters' => json_encode($this->getParameters()),
        ], $this->worker->getTrackerData());
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters = []) : void
    {
        $this->parameters = $parameters;
    }

    public function getTypeIdentifier() : string
    {
        return static::IDENTIFIER;
    }

    protected function buildWorker(string $workerClass, array $workerParameters) : void
    {
        $this->worker = $this->workerFactory->createWorker($workerClass);
        $this->worker->setParameters($workerParameters);
    }

    protected function buildTrackerService() : void
    {
        if (!isset($this->parameters['tracker']['service']['type'])) {
            return;
        }

        $this->trackerService = $this->trackerServiceFactory->createTrackerService(
            $this->parameters['tracker']['service']['type']
        );
    }

    public function getConfig() : array
    {
        $this->validate();

        return $this->config[static::IDENTIFIER];
    }

    public function getConfigParameters() : array
    {
        $config = $this->getConfig();

        return $config['parameters'] ?? [];
    }

    protected function validate() : void
    {
        if (empty(static::IDENTIFIER) || empty($this->config[static::IDENTIFIER]['type'])) {
            throw new ConfigurationException(static::IDENTIFIER);
        }
    }

    public function isFinished() : bool
    {
        return $this->finished;
    }
}
