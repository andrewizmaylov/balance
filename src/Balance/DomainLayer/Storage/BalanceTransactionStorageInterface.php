<?php

namespace Src\Balance\DomainLayer\Storage;

use App\Enums\Transaction\TransactionStatusEnum;

interface BalanceTransactionStorageInterface
{
    public function createTransaction(array $data): int;

    public function updateTransactionStatus(
        int $id,
        TransactionStatusEnum $status
    ): void;
}
