<?php

declare(strict_types=1);

namespace Src\Balance\ApplicationLayer\UseCases;

use Illuminate\Support\Facades\DB;
use Src\Balance\DomainLayer\Entities\BalanceTransaction;
use Src\Balance\DomainLayer\Exceptions\CancelTransactionException;
use Src\Balance\DomainLayer\Repository\BalanceTransactionRepositoryInterface;
use Src\Balance\DomainLayer\Services\CheckTransactionService;
use Throwable;

readonly class CancelOrderUseCase
{
    public function __construct(
        private BalanceTransactionRepositoryInterface $repository,
        private CheckTransactionService $checkTransactionService,
    ) {}

    /**
     * @throws CancelTransactionException|Throwable
     */
    public function execute(string $transactionId): BalanceTransaction
    {
        $transactions = $this->repository->findByTransactionId($transactionId);
        // Find working transaction by active user account (using first() for simplicity)
        $activeTransaction = $transactions->first();
        $this->checkTransactionService->checkOrderCanBeCanceled($activeTransaction);

        DB::transaction(function () use ($transactions) {
            // TODO:
            // change status,
            // save transactions etc.
        });

        return $this->repository->findById($activeTransaction->id);
    }
}
