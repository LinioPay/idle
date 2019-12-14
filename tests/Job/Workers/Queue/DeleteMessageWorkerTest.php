<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Workers\Queue;

use LinioPay\Idle\Job\Exception\InvalidWorkerParameterException;
use LinioPay\Idle\Job\Job;
use LinioPay\Idle\Message\Messages\PublishSubscribe\SubscriptionMessage as SubscriptionMessageInterface;
use LinioPay\Idle\Message\Messages\Queue\Message as QueueMessageInterface;
use LinioPay\Idle\TestCase;
use Mockery as m;

class DeleteMessageWorkerTest extends TestCase
{
    public function testItWorks()
    {
        $message = m::mock(QueueMessageInterface::class);
        $message->shouldReceive('delete')
            ->once()
            ->with(['foo' => 'bar']);

        $worker = new DeleteMessageWorker();
        $worker->setParameters(['job' => m::mock(Job::class), 'message' => $message, 'foo' => 'bar']);
        $worker->validateParameters();

        $this->assertTrue($worker->work());
    }

    public function testItFailsValidation()
    {
        $message = m::mock(SubscriptionMessageInterface::class);

        $worker = new DeleteMessageWorker();
        $worker->setParameters(['job' => m::mock(Job::class), 'message' => $message, 'foo' => 'bar']);

        $this->expectException(InvalidWorkerParameterException::class);
        $worker->validateParameters();
    }
}
