<?php

declare(strict_types=1);

namespace Src\Balance\ApplicationLayer\UseCases;

use Src\Balance\DomainLayer\Entities\BalanceTransaction;
use Src\Balance\DomainLayer\Storage\BalanceStorageInterface;
use Src\Balance\DomainLayer\Repository\BalanceRepositoryInterface;

readonly class MakeDepositUseCase
{
    public function __construct(
        private BalanceStorageInterface $storage,
        private BalanceRepositoryInterface $repository,
    ) {}

    public function execute(): BalanceTransaction
    {
        //
    }
}
