<?php

namespace Src\Balance\DomainLayer\Repository;

use Src\Balance\DomainLayer\Entities\BalanceTransaction;

interface BalanceTransactionRepositoryInterface
{
    public function findById(int $transactionId): ?BalanceTransaction;
}
