<?php

namespace Src\Balance\InfrastructureLayer\Storage;

use Illuminate\Database\ConnectionInterface;
use Src\Balance\DomainLayer\Storage\BalanceStorageInterface;

class BalanceStorage implements BalanceStorageInterface
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

}
