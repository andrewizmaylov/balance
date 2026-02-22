<?php

declare(strict_types=1);

namespace Src\Balance\ApplicationLayer\UseCases;

use App\Enums\Transaction\TransactionTypeEnum;
use Exception;
use Illuminate\Support\Facades\DB;
use Src\Balance\DomainLayer\Entities\BalanceTransaction;
use Src\Balance\DomainLayer\Repository\AccountRepositoryInterface;
use Src\Balance\DomainLayer\Services\BalanceValidationService;
use Src\Balance\DomainLayer\Storage\AccountStorageInterface;
use Src\Balance\DomainLayer\Storage\BalanceTransactionStorageInterface;
use Src\Balance\DomainLayer\Repository\BalanceTransactionRepositoryInterface;
use Throwable;

readonly class MakeDepositUseCase
{
    const PLATFORM = 1;

    public function __construct(
        private AccountStorageInterface $accountStorage,
        private AccountRepositoryInterface $accountRepository,
        private BalanceTransactionStorageInterface $storage,
        private BalanceTransactionRepositoryInterface $repository,
        private BalanceValidationService $balanceValidator,
    ) {}

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function execute(array $data): BalanceTransaction
    {
        // Perform DEPOSIT action from source to destination
        $transactionId = null;
        DB::transaction(function () use ($data, &$transactionId) {
            // Check balance and account
            // При операции deposit списание происходит с $source
            // При операции withdrawal списание происходит с $destination
            $source = $this->accountRepository->getById($data['source_account_id']);
            if ($source === null) {
                throw new Exception('Account FROM not found');
            }
            $destination = $this->accountRepository->getById($data['destination_account_id']);
            if ($destination === null) {
                throw new Exception('Account TO not found');
            }
            $this->balanceValidator->checkAccountBeforeTransaction($source, $data);

            $platformAccount = $this->accountRepository->getById(self::PLATFORM);
            if ($platformAccount === null) {
                throw new Exception('Platform Account not found');
            }

            $transactionAmount = $data['amount'];
            $withdrawalFee = $this->balanceValidator->calculateWithdrawalFee($transactionAmount);
            $depositFee = $this->balanceValidator->calculateDepositFee($transactionAmount);

            // Create main transaction
            $transactionId = $this->storage->createTransaction($data);

            // Create reverse transaction
            $reverseTransaction = $data;
            $reverseTransaction['source_account_id'] = $data ['destination_account_id'];
            $reverseTransaction['destination_account_id'] = $data ['source_account_id'];
            $reverseTransaction['transaction_type'] = TransactionTypeEnum::Withdrawal->value;
            $this->storage->createTransaction($reverseTransaction);

            // Create Fees transactions
            $feeDepositTransaction = $data;
            $feeDepositTransaction['source_account_id'] = $data ['source_account_id'];
            $feeDepositTransaction['destination_account_id'] = self::PLATFORM;
            $feeDepositTransaction['amount'] = $depositFee;
            $feeDepositTransaction['transaction_type'] = TransactionTypeEnum::Deposit->value;
            $this->storage->createTransaction($feeDepositTransaction);

            $feeWithdrawalTransaction = $data;
            $feeWithdrawalTransaction['source_account_id'] = $data ['destination_account_id'];
            $feeWithdrawalTransaction['destination_account_id'] = self::PLATFORM;
            $feeWithdrawalTransaction['amount'] = $withdrawalFee;
            $feeWithdrawalTransaction['transaction_type'] = TransactionTypeEnum::Deposit->value;
            $this->storage->createTransaction($feeWithdrawalTransaction);

            // Create Reverse Fees transactions
            $feeDepositTransaction['transaction_type'] = TransactionTypeEnum::Withdrawal->value;
            $this->storage->createTransaction($feeDepositTransaction);

            $feeWithdrawalTransaction['transaction_type'] = TransactionTypeEnum::Withdrawal->value;
            $this->storage->createTransaction($feeWithdrawalTransaction);

            $this->accountStorage->updateAccount(
                id: $platformAccount->id,
                lockedBalance: $platformAccount->lockedBalance + $withdrawalFee + $depositFee,
            );

            // При операции deposit списание происходит с $source и он сразу уменьшается
            // При операции withdrawal списание происходит с $destination
            $this->accountStorage->updateAccount(
                id: $source->id,
                balance: $source->balance - $transactionAmount - $withdrawalFee,
                lockedBalance: $source->lockedBalance + $withdrawalFee + $transactionAmount,
            );

            $this->accountStorage->updateAccount(
                id: $destination->id,
                lockedBalance: $destination->lockedBalance - $depositFee + $transactionAmount,
            );
        });

        // Return results
        return $this->repository->findById($transactionId);
    }
}
