<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

use LinioPay\Idle\TestCase;

class InvalidServiceConfigurationExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new InvalidServiceConfigurationException('foo_service', 'foo_param');

        $this->assertSame(sprintf(InvalidServiceConfigurationException::MESSAGE, 'foo_service', 'foo_param'), $e->getMessage());
    }
}
