<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\ValueObjects;

use App\Enums\CurrenciesEnum;
use Exception;

readonly class Amount
{
    /**
     * @throws Exception
     */
    public function __construct(
        public int $amount,
        public string $currency,
    )
    {
        if ($amount <= 0) {
            throw new Exception('The amount must be greater than 0');
        }

        if (!CurrenciesEnum::tryFrom($currency)) {
            throw new Exception('This currency doesn\'t allowed');
        }
    }
}
