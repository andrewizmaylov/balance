<?php

declare(strict_types=1);

namespace Src\Balance\ApplicationLayer\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Src\Balance\DomainLayer\Entities\BalanceTransaction;
use Src\Balance\DomainLayer\Services\BalanceUpdateService;
use Src\Balance\DomainLayer\Repository\BalanceTransactionRepositoryInterface;
use Src\Balance\DomainLayer\Services\CreateTransactionsService;
use Throwable;

readonly class UpdateBalanceUseCase
{
    private ?int $transactionId;

    public function __construct(
        private BalanceTransactionRepositoryInterface $repository,
        private CreateTransactionsService $transactionService,
        private BalanceUpdateService $balanceService,
    ) {}

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function execute(array $data): BalanceTransaction
    {
        // Perform DEPOSIT action from source to destination
        DB::transaction(function () use ($data, &$transactionId) {
            $accounts = $this->balanceService->validateAccounts($data);

            $transactionAmount = $data['amount'];
            $withdrawalFee = $this->balanceService->calculateWithdrawalFee($transactionAmount);
            $depositFee = $this->balanceService->calculateDepositFee($transactionAmount);
            // Create main, reverse and fees transactions
            $this->transactionId = $this->transactionService->execute($data, $withdrawalFee, $depositFee);
            // Update balances
            $this->balanceService
                ->updateAccounts(
                    $transactionAmount, $withdrawalFee, $depositFee, $accounts
                );
        });

        return $this->repository->findById($this->transactionId);
    }
}
