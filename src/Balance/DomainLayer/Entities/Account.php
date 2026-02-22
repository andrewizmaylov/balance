<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\Entities;

use App\Enums\Account\AccountStatusEnum;
use App\Enums\Account\AccountTypeEnum;
use App\Enums\CurrenciesEnum;

readonly class Account
{
    public function __construct(
        public int $id,
        public int $userId,
        public float $balance,
        public float $lockedBalance,
        public CurrenciesEnum $coin,
        public AccountTypeEnum $accountType,
        public AccountStatusEnum $accountStatus,
    )
    {
    }
}
