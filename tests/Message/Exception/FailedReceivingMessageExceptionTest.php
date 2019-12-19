<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

use LinioPay\Idle\TestCase;

class FailedReceivingMessageExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new FailedReceivingMessageException('foo_service', 'foo_resource', 'foo_id');

        $this->assertSame(sprintf(FailedReceivingMessageException::MESSAGE, 'foo_resource', 'foo_id', 'foo_service'), $e->getMessage());
    }
}
