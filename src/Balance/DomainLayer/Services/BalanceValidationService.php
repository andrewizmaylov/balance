<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\Services;

use App\Enums\Account\AccountStatusEnum;

use App\Enums\Transaction\TransactionStatusEnum;
use App\Enums\Transaction\TransactionTypeEnum;
use Exception;
use Src\Balance\DomainLayer\Entities\Account;

readonly class BalanceValidationService
{
    public const WITHDRAWAL_FEE = 7.24;
    public const DEPOSIT_FEE = 2.14;
    /**
     * @throws Exception
     */
    public function checkAccountBeforeTransaction(Account $account, array $balanceTransaction): void
    {
        $transactionFee = $balanceTransaction['transaction_type'] === TransactionTypeEnum::Withdrawal->value
            ? $balanceTransaction['amount'] * self::WITHDRAWAL_FEE / 100
            : 0;

        if ($balanceTransaction['status'] !== TransactionStatusEnum::Request->value) {
            throw new Exception('Transaction already proceeded supported');
        }

        if ($account->accountStatus === AccountStatusEnum::Inactive) {
            throw new Exception('Account is not active');
        }

        if ($account->coin->value !== $balanceTransaction['coin']) {
            throw new Exception('Transaction coin does not match');
        }

        $availableBalance = $account->balance - $account->lockedBalance;
        $transactionAmountWithFee = $balanceTransaction['amount'] + $transactionFee;

        if ($availableBalance < $transactionAmountWithFee) {
            throw new Exception('Insufficient funds for transaction');
        }
    }

    public function calculateWithdrawalFee(float $amount): float
    {
        return round(($amount * self::WITHDRAWAL_FEE / 100), 8);
    }

    public function calculateDepositFee(float $amount): float
    {
        return round(($amount * self::DEPOSIT_FEE / 100), 8);
    }
}
