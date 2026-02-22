<?php

namespace Src\Balance\InfrastructureLayer\Storage;

use App\Enums\Transaction\TransactionStatusEnum;
use Exception;
use Illuminate\Database\ConnectionInterface;
use Src\Balance\DomainLayer\Storage\BalanceTransactionStorageInterface;

class BalanceTransactionStorage implements BalanceTransactionStorageInterface
{
    protected string $tableName;

    public function __construct(
        private readonly ConnectionInterface $connection,
    )
    {
        $this->tableName = \App\Models\BalanceTransaction::getTableName();
    }

    /**
     * @throws Exception
     */
    public function createTransaction(array $data): int
    {
        if ($data['status'] !== TransactionStatusEnum::Request->value) {
            throw new Exception('Transaction already in process');
        }

        $data['status'] = TransactionStatusEnum::Pending->value;

        return $this->connection
            ->table($this->tableName)
            ->insertGetId($data);
    }

    public function updateTransactionStatus(int $id, TransactionStatusEnum $status): void
    {
        // TODO: Implement updateTransactionStatus() method.
    }
}
