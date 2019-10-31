<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Exception;

use LinioPay\Idle\TestCase;

class ConfigurationExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new ConfigurationException('foo_identifier', 'bar_param');

        $this->assertSame(sprintf(ConfigurationException::MESSAGE, 'foo_identifier', 'bar_param'), $e->getMessage());
    }
}
