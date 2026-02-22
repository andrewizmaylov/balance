<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\Entities;

use App\Enums\ChainTypeEnum;
use App\Enums\CurrenciesEnum;
use App\Enums\Transaction\TransactionTypeEnum;
use App\Enums\Transaction\TransactionStatusEnum;


readonly class BalanceTransaction
{
    public function __construct(
        public int $id,
        public Account $account,
        public CurrenciesEnum $coin,
        public int $amount,
        public int $fee,
        public string $chainName,
        public ChainTypeEnum $chainType,
        public string $address,
        public string $transactionId,
        public string $orderId,
        public TransactionTypeEnum $transactionType,
        public TransactionStatusEnum $status,
    )
    {
    }
}
