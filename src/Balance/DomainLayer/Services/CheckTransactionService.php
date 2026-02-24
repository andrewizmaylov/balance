<?php

declare(strict_types=1);

namespace Src\Balance\DomainLayer\Services;

use App\Enums\Transaction\TransactionStatusEnum;
use Src\Balance\DomainLayer\Entities\BalanceTransaction;
use Src\Balance\DomainLayer\Exceptions\CancelTransactionException;
use Src\Balance\DomainLayer\Exceptions\CompleteTransactionException;
use Src\Balance\DomainLayer\Exceptions\DisputeTransactionException;
use Src\Balance\DomainLayer\Exceptions\UnconfirmedTransactionException;

class CheckTransactionService
{
    public function checkCoinsCanBeReleased(BalanceTransaction $transaction): void
    {
        if ($transaction->status !== TransactionStatusEnum::Confirmed) {
            throw UnconfirmedTransactionException::onlyConfirmedAllowed();
        }
    }

    public function checkOrderCanBeDisputed(BalanceTransaction $transaction): void
    {
        if ($transaction->status !== TransactionStatusEnum::Completed) {
            throw DisputeTransactionException::onlyConfirmedAllowed();
        }
    }

    public function checkOrderCanBeCompleted(BalanceTransaction $transaction): void
    {
        if ($transaction->status !== TransactionStatusEnum::Confirmed) {
            throw CompleteTransactionException::onlyConfirmedAllowed();
        }
    }

    public function checkOrderCanBeCanceled(BalanceTransaction $transaction): void
    {
        if ($transaction->status !== TransactionStatusEnum::Confirmed) {
            throw CancelTransactionException::onlyConfirmedAllowed();
        }
    }
}
