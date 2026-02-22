<?php

namespace Src\Balance\InfrastructureLayer\Storage;

use App\Enums\Account\AccountStatusEnum;
use App\Enums\Account\AccountTypeEnum;
use DateTime;
use Illuminate\Database\ConnectionInterface;
use Src\Balance\DomainLayer\Storage\AccountStorageInterface;

class AccountStorage implements AccountStorageInterface
{
    protected string $tableName;

    public function __construct(
        private readonly ConnectionInterface $connection,
    )
    {
        $this->tableName = \App\Models\Account::getTableName();
    }

    public function updateAccount(
        int $id,
        ?float $balance = null,
        ?float $lockedBalance = null,
        ?AccountTypeEnum $accountType = null,
        ?AccountStatusEnum $accountStatus  = null,
    ): void
    {
        $data = [
            'balance' => $balance,
            'locked_balance' => $lockedBalance,
            'account_type' => $accountType,
            'account_status' => $accountStatus,

            'updated_at' => new DateTime,
        ];

        $this->connection
            ->table($this->tableName)
            ->where('id', $id)
            ->update(array_filter($data));
    }
}
