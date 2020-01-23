<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Messages\Queue\Service\Google\CloudTasks\Exception;

use LinioPay\Idle\TestCase;

class InvalidMessageRequestExceptionTest extends TestCase
{
    public function testInstantiatesProperly()
    {
        $e = new InvalidMessageRequestException();

        $this->assertSame(InvalidMessageRequestException::MESSAGE, $e->getMessage());
    }
}
