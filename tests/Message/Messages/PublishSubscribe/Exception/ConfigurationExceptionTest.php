<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\PublishSubscribe\Exception;

use LinioPay\Idle\TestCase;

class ConfigurationExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new ConfigurationException(ConfigurationException::TYPE_TOPIC, 'foo_identifier');

        $this->assertSame(
            sprintf(
                ConfigurationException::MESSAGE,
                'foo_identifier',
                ConfigurationException::TYPE_TOPIC
            ), $e->getMessage()
        );
    }
}
