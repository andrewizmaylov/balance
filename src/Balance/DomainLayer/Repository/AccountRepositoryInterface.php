<?php

namespace Src\Balance\DomainLayer\Repository;

use Src\Balance\DomainLayer\Entities\Account;

interface AccountRepositoryInterface
{
    public function getById(int $accountId): ?Account;
}
