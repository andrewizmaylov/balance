<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\Entities;

use App\Enums\Account\AccountStatusEnum;
use App\Enums\Account\AccountTypeEnum;

readonly class Account
{
    public function __construct(
        public int $id,
        public int $userId,
        public AccountTypeEnum $accountType,
        public AccountStatusEnum $accountStatus,
    )
    {
    }
}
