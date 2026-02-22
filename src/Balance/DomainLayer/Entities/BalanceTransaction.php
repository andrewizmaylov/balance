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
        public int $accountId,
        public CurrenciesEnum $coin,
        public float $amount,
        public ?string $chainName = null,
        public ?ChainTypeEnum $chainType = null,
        public ?string $address = null,
        public ?string $transactionId = null,
        public ?string $orderId = null,
        public TransactionTypeEnum $transactionType,
        public TransactionStatusEnum $status,
    )
    {
    }
}
