<?php

namespace Src\Balance\InfrastructureLayer\Repository;

use Illuminate\Database\ConnectionInterface;
use Src\Balance\DomainLayer\Repository\BalanceRepositoryInterface;

class BalanceRepository implements BalanceRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

}
