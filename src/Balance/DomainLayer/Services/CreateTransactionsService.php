<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\Services;

use App\Enums\Transaction\TransactionTypeEnum;
use Src\Balance\DomainLayer\Storage\BalanceTransactionStorageInterface;

readonly class CreateTransactionsService
{
    public function __construct(
        private BalanceTransactionStorageInterface $storage,
    )
    {
    }

    public function execute(array $data, float $withdrawalFee, float $depositFee): int
    {
        $id = $this->storage->createTransaction($data);

        $transactionType = $data['transaction_type'];

        $reverseTransaction = $data;
        $reverseTransaction['source_account_id'] = $data['destination_account_id'];
        $reverseTransaction['destination_account_id'] = $data['source_account_id'];
        $reverseTransaction['transaction_type'] = $transactionType === TransactionTypeEnum::Withdrawal->value
            ? TransactionTypeEnum::Deposit->value
            : TransactionTypeEnum::Withdrawal->value;
        $this->storage->createTransaction($reverseTransaction);

        $feeDepositTransaction = $data;
        $feeDepositTransaction['source_account_id'] = $transactionType === TransactionTypeEnum::Withdrawal->value
            ? $data['destination_account_id']
            : $data['source_account_id'];
        $feeDepositTransaction['destination_account_id'] = BalanceUpdateService::PLATFORM;
        $feeDepositTransaction['amount'] = $depositFee;
        $feeDepositTransaction['transaction_type'] = TransactionTypeEnum::Deposit->value;
        $this->storage->createTransaction($feeDepositTransaction);

        $feeWithdrawalTransaction = $data;
        $feeWithdrawalTransaction['source_account_id'] = $transactionType === TransactionTypeEnum::Withdrawal->value
            ? $data['source_account_id']
            : $data['destination_account_id'];
        $feeWithdrawalTransaction['destination_account_id'] = BalanceUpdateService::PLATFORM;
        $feeWithdrawalTransaction['amount'] = $withdrawalFee;
        $feeWithdrawalTransaction['transaction_type'] = TransactionTypeEnum::Deposit->value;
        $this->storage->createTransaction($feeWithdrawalTransaction);

        return $id;
    }
}
