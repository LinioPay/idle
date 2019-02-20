<?php

declare(strict_types=1);

namespace LinioPay\Idle\Job\Exception;

use LinioPay\Idle\TestCase;

class InvalidJobsExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new InvalidJobsException();

        $this->assertSame(InvalidJobsException::MESSAGE, $e->getMessage());
    }
}
