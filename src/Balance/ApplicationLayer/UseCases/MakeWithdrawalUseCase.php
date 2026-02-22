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

readonly class MakeWithdrawalUseCase
{
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
        // Check balance and account before Withdrawal
        $account = $this->accountRepository->getById($data['account_id']);
        if ($account === null) {
            throw new Exception('Account not found');
        }
        $this->balanceValidator->checkAccountBeforeTransaction($account, $data);

        // Perform action
        $transactionId = null;
        DB::transaction(function () use ($account, $data, &$transactionId) {
            // Create Main transaction
            $transactionId = $this->storage->createTransaction($data);

            // Create Fee Transaction
            $transactionFee = $this->balanceValidator->calculateWithdrawalFee($data['amount']);
            $data['amount'] = $transactionFee;
            $data['transaction_type'] = TransactionTypeEnum::Fee->value;
            $this->storage->createTransaction($data);

            $newAmount = $account->balance - $data['amount'] - $transactionFee;
            $this->accountStorage->updateAccount(
                id: $account->id,
                balance: $newAmount,
                lockedBalance: $data['amount'] + $transactionFee,
            );
        });

        // Return results
        return $this->repository->findById($transactionId);
    }
}
