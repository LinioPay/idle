<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs;

use DateTime;
use LinioPay\Idle\Config\IdleConfig;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\TrackableWorker;
use LinioPay\Idle\Job\TrackingWorker;
use LinioPay\Idle\Job\Worker as WorkerInterface;
use LinioPay\Idle\Job\WorkerFactory as WorkerFactoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class DefaultJob implements Job
{
    const IDENTIFIER = '';

    /** @var UuidInterface */
    protected $jobId;

    /** @var DateTime */
    protected $startDate;

    /** @var bool */
    protected $successful = false;

    /** @var float */
    protected $duration = 0.0;

    /** @var IdleConfig */
    protected $idleConfig;

    /** @var array */
    protected $parameters;

    /** @var array */
    protected $errors = [];

    /** @var WorkerInterface[] */
    protected $workers;

    /** @var TrackingWorker */
    protected $trackingWorker;

    /** @var WorkerFactoryInterface */
    protected $workerFactory;

    /** @var array */
    protected $context = [];

    /** @var array */
    protected $output = [];

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
        return array_merge($this->errors, $this->getWorkersErrors());
    }

    public function getJobId() : UuidInterface
    {
        return $this->jobId;
    }

    public function getStartDate() : DateTime
    {
        return $this->startDate;
    }

    public function process() : void
    {
        $this->errors = [];
        $this->jobId = Uuid::uuid1();
        $this->startDate = new DateTime();

        $start = microtime(true);

        try {
            $this->buildWorkers();
            $this->track();

            $this->performWork();
        } catch (\Throwable $throwable) {
            $this->errors[] = sprintf('Encountered an error: %s', $throwable->getMessage());

            throw $throwable;
        } finally {
            $this->duration = microtime(true) - $start;

            $this->finished = true;

            $this->track();
        }
    }

    public function getTrackerData() : array
    {
        return array_merge([
            'id' => $this->getJobId()->toString(),
            'start' => $this->getStartDate()->format('Y-m-d H:i:s'),
            'duration' => $this->getDuration(),
            'successful' => $this->isSuccessful(),
            'finished' => $this->isFinished(),
            'errors' => json_encode($this->getErrors()),
            'parameters' => json_encode($this->getParameters()),
        ], $this->getWorkersTrackerData());
    }

    protected function getWorkersTrackerData() : array
    {
        $data = [];

        array_map(function (WorkerInterface $worker) use (&$data) {
            if (is_a($worker, TrackableWorker::class)) {
                /** @var TrackableWorker $worker */
                $data = array_merge($data, $worker->getTrackerData());
            }
        }, $this->workers);

        return $data;
    }

    protected function getWorkersErrors() : array
    {
        $errors = [];

        array_map(function (WorkerInterface $worker) use (&$errors) {
            $errors = array_merge($errors, $worker->getErrors());
        }, $this->workers);

        return $errors;
    }

    public function getParameters() : array
    {
        return array_merge_recursive(
            $this->idleConfig->getJobParametersConfig(static::IDENTIFIER),
            $this->parameters ?? []
        );
    }

    public function setParameters(array $parameters = []) : void
    {
        $this->parameters = $parameters;
    }

    public function getTypeIdentifier() : string
    {
        return static::IDENTIFIER;
    }

    protected function buildWorkers() : void
    {
        $this->workers = [];
        $workersConfig = $this->getJobWorkersConfig();

        array_map(function ($workerConfig) {
            $worker = $this->buildWorker($workerConfig['type'] ?? '', $workerConfig['parameters'] ?? []);

            if (is_a($worker, TrackingWorker::class)) {
                $this->trackingWorker = $worker;
            } else {
                $this->workers[] = $worker;
            }
        }, $workersConfig);
    }

    protected function getJobWorkersConfig() : array
    {
        return [];
    }

    protected function buildWorker(string $workerIdentifier, array $workerParameters) : WorkerInterface
    {
        return $this->workerFactory->createWorker($workerIdentifier, array_merge(
            $workerParameters,
            ['job' => $this]
        ));
    }

    protected function track() : void
    {
        if (!is_null($this->trackingWorker)) {
            $this->trackingWorker->work();
        }
    }

    protected function performWork() : void
    {
        $workers = $this->workers;
        $successful = true;

        while (!empty($workers) && ($worker = array_shift($workers))) {
            if (!$worker->work()) {
                $successful = false;
                break;
            }
        }

        $this->successful = $successful;
    }

    public function setContext(array $data) : void
    {
        $this->context = $data;
    }

    public function addContext(string $key, $value) : void
    {
        $this->context[$key] = $value;
    }

    public function getContextEntry(string $key)
    {
        return $this->context[$key] ?? null;
    }

    public function addOutput(string $key, $value) : void
    {
        $this->output[$key] = $value;
    }

    public function setOutput(array $data) : void
    {
        $this->output = $data;
    }

    public function getOutput() : array
    {
        return $this->output;
    }

    public function validateParameters() : void
    {
        // Optional parameter validation
    }

    public function isFinished() : bool
    {
        return $this->finished;
    }
}
