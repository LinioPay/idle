<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

use LinioPay\Idle\TestCase;

class ConfigurationExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new ConfigurationException('foo_identifier');

        $this->assertSame(sprintf(ConfigurationException::MESSAGE, 'foo_identifier'), $e->getMessage());
    }
}
