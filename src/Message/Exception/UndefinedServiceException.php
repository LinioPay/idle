<?php

declare(strict_types=1);

namespace LinioPay\Idle\Message\Exception;

use LinioPay\Idle\Message\Message as MessageInterface;

class UndefinedServiceException extends \Exception
{
    const MESSAGE = 'Message %s is missing a service.';

    public function __construct(MessageInterface $message)
    {
        parent::__construct(sprintf(self::MESSAGE, get_class($message)));
    }
}
