<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

use LinioPay\Idle\TestCase;

class InvalidMessageParameterExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new InvalidMessageParameterException('foo_parameter');

        $this->assertSame(sprintf(InvalidMessageParameterException::MESSAGE, 'foo_parameter'), $e->getMessage());
    }
}
