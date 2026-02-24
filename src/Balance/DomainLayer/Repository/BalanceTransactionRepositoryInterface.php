<?php

namespace Src\Balance\DomainLayer\Repository;

use Illuminate\Support\Collection;
use Src\Balance\DomainLayer\Entities\BalanceTransaction;

interface BalanceTransactionRepositoryInterface
{
    public function findById(int $id): ?BalanceTransaction;
    public function findByTransactionId(string $transaction_id): Collection;
}
