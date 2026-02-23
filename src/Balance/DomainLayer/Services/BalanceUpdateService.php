<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\Services;

use App\Enums\Account\AccountStatusEnum;

use App\Enums\Transaction\TransactionStatusEnum;
use Exception;
use Src\Balance\DomainLayer\Entities\Account;
use Src\Balance\DomainLayer\Repository\AccountRepositoryInterface;
use Src\Balance\DomainLayer\Storage\AccountStorageInterface;

/*
 * При операции deposit списание происходит с $source и он сразу уменьшается
 * При операции withdrawal списание происходит с $destination
 */
readonly class BalanceUpdateService
{
    public const PLATFORM = 1;
    public const WITHDRAWAL_FEE = 7.24;
    public const DEPOSIT_FEE = 2.14;

    public function __construct(
        private AccountStorageInterface $accountStorage,
        private AccountRepositoryInterface $accountRepository,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function validateAccounts(array $data): array
    {
        $sourceAccount = $this->accountRepository->getById($data['source_account_id']);
        if ($sourceAccount === null) {
            throw new Exception('Account FROM not found');
        }
        $this->checkAccountBeforeTransaction($sourceAccount, $data);

        $destinationAccount = $this->accountRepository->getById($data['destination_account_id']);
        if ($destinationAccount === null) {
            throw new Exception('Account TO not found');
        }
        $this->checkAccountBeforeTransaction($destinationAccount, $data);

        $platformAccount = $this->accountRepository->getById(self::PLATFORM);
        if ($platformAccount === null) {
            throw new Exception('Platform Account not found');
        }
        $this->checkAccountBeforeTransaction($platformAccount, $data);

        return [
            $sourceAccount,
            $destinationAccount,
            $platformAccount,
        ];
    }

    /**
     * @throws Exception
     */
    public function checkAccountBeforeTransaction(Account $account, array $balanceTransaction): void
    {
        $transactionFee = match (true) {
            $account->id === $balanceTransaction['source_account_id'] => $this->calculateWithdrawalFee($balanceTransaction['amount']),
            $account->id === $balanceTransaction['destination_account_id'] => $this->calculateDepositFee($balanceTransaction['amount']),
            default => 0,
        };

        if ($balanceTransaction['status'] !== TransactionStatusEnum::Request->value) {
            throw new Exception('Transaction already proceeded supported');
        }

        if ($account->accountStatus === AccountStatusEnum::Inactive) {
            throw new Exception('Account is not active');
        }

        if ($account->coin->value !== $balanceTransaction['coin']) {
            throw new Exception('Transaction coin does not match');
        }

        // Check Account From before transaction
        if ($account->id === $balanceTransaction['source_account_id']) {
            $availableBalance = $account->balance - $account->lockedBalance;
            $transactionAmountWithFee = $balanceTransaction['amount'] + $transactionFee;

            if ($availableBalance < $transactionAmountWithFee) {
                throw new Exception('Insufficient funds for transaction');
            }
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

    public function updateAccounts(
        float $transactionAmount,
        float $withdrawalFee,
        float $depositFee,
        array $accounts,
    ): void
    {
        [$sourceAccount, $destinationAccount, $platformAccount] = $accounts;
        $this->accountStorage->updateAccount(
            id: $platformAccount->id,
            lockedBalance: $platformAccount->lockedBalance + $withdrawalFee + $depositFee,
        );

        $this->accountStorage->updateAccount(
            id: $sourceAccount->id,
            balance: $sourceAccount->balance - $transactionAmount - $withdrawalFee,
            lockedBalance: $sourceAccount->lockedBalance + $withdrawalFee + $transactionAmount,
        );

        $this->accountStorage->updateAccount(
            id: $destinationAccount->id,
            lockedBalance: $destinationAccount->lockedBalance - $depositFee + $transactionAmount,
        );
    }
}
