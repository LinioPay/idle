<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Jobs\Factory;

use LinioPay\Idle\Job\Exception\ConfigurationException;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Job\JobFactory as JobFactoryInterface;
use Psr\Container\ContainerInterface;

abstract class DefaultJobFactory implements JobFactoryInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $jobConfig;

    public function __invoke(ContainerInterface $container) : JobFactory
    {
        $this->container = $container;

        $this->jobConfig = $container->get('config')['idle']['job'] ?? [];

        return $this;
    }

    abstract public function createJob(string $jobIdentifier, array $parameters) : Job;

    protected function getJobClass(string $jobIdentifier) : string
    {
        $jobClass = $this->jobConfig['types'][$jobIdentifier]['class'] ?? '';

        if (empty($jobClass)) {
            throw new ConfigurationException($jobIdentifier);
        }

        return $jobClass;
    }
}
