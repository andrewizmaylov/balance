<?php

namespace Src\Balance\InfrastructureLayer\Repository;

use App\Enums\ChainTypeEnum;
use App\Enums\CurrenciesEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Enums\Transaction\TransactionTypeEnum;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Src\Balance\DomainLayer\Entities\BalanceTransaction;
use Src\Balance\DomainLayer\Repository\BalanceTransactionRepositoryInterface;

class BalanceTransactionRepository implements BalanceTransactionRepositoryInterface
{
    protected string $tableName;

    public function __construct(
        private readonly ConnectionInterface $connection,
    )
    {
        $this->tableName = \App\Models\BalanceTransaction::getTableName();
    }

    public function findById(int $id): ?BalanceTransaction
    {
        $record = $this->connection
            ->table($this->tableName)
            ->where('id', $id)
            ->first();

        if (!$record) {
            return null;
        }

        return new BalanceTransaction(
            $record->id,
            $record->source_account_id,
            $record->destination_account_id,
            CurrenciesEnum::tryFrom($record->coin),
            $record->amount,
            $record->chain_name,
            $record->chain_type ? ChainTypeEnum::tryFrom($record->chain_type)  : null,
            $record->address,
            $record->transaction_id,
            $record->order_id,
            TransactionTypeEnum::tryFrom($record->transaction_type),
            TransactionStatusEnum::tryFrom($record->status),
        );
    }

    public function findByTransactionId(string $transaction_id): Collection
    {
        $records = $this->connection
            ->table($this->tableName)
            ->where('transaction_id', $transaction_id)
            ->get();

        if (!$records->count()) {
            return $records;
        }

        return $records->map(fn (object $record) => new BalanceTransaction(
            $record->id,
            $record->source_account_id,
            $record->destination_account_id,
            CurrenciesEnum::tryFrom($record->coin),
            $record->amount,
            $record->chain_name,
            $record->chain_type ? ChainTypeEnum::tryFrom($record->chain_type)  : null,
            $record->address,
            $record->transaction_id,
            $record->order_id,
            TransactionTypeEnum::tryFrom($record->transaction_type),
            TransactionStatusEnum::tryFrom($record->status),
        ));
    }
}
