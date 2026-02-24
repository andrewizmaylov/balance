<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\Exceptions;

use DomainException;

class DisputeTransactionException extends DomainException
{
    public static function onlyConfirmedAllowed(): self
    {
        return new self('Only confirmed transactions can be disputed');
    }
}
