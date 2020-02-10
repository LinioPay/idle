<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

use LinioPay\Idle\TestCase;

class UnsupportedServiceOperationExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new UnsupportedServiceOperationException('foo_service', 'foo_operation');

        $this->assertSame(sprintf(UnsupportedServiceOperationException::MESSAGE, 'foo_service', 'foo_operation'), $e->getMessage());
    }
}
