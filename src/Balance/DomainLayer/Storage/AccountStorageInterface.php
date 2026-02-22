<?php

namespace Src\Balance\DomainLayer\Storage;

use App\Enums\Account\AccountStatusEnum;
use App\Enums\Account\AccountTypeEnum;

interface AccountStorageInterface
{
    public function updateAccount(
        int $id,
        ?float $balance,
        ?float $lockedBalance,
        ?AccountTypeEnum $accountType,
        ?AccountStatusEnum $accountStatus,
    ): void;
}
