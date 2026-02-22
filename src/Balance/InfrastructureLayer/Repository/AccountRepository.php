<?php

declare(strict_types=1);

namespace Src\Balance\InfrastructureLayer\Repository;

use App\Enums\Account\AccountStatusEnum;
use App\Enums\Account\AccountTypeEnum;
use App\Enums\CurrenciesEnum;
use Illuminate\Database\ConnectionInterface;
use Src\Balance\DomainLayer\Entities\Account;
use Src\Balance\DomainLayer\Repository\AccountRepositoryInterface;

class AccountRepository implements AccountRepositoryInterface
{
    protected string $tableName;

    public function __construct(
        private readonly ConnectionInterface $connection,
    )
    {
        $this->tableName = \App\Models\Account::getTableName();
    }

    public function getById(int $accountId): ?Account
    {
        $record = $this->connection
            ->table('accounts')
            ->where('id', $accountId)
            ->first();

        if (!$record) {
            return null;
        }

        return new Account(
            $record->id,
            $record->user_id,
            $record->balance,
            $record->locked_balance,
            CurrenciesEnum::tryFrom($record->coin),
            AccountTypeEnum::tryFrom($record->account_type),
            AccountStatusEnum::tryFrom($record->account_status),
        );
    }
}
