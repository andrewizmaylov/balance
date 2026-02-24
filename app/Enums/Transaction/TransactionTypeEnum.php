<?php

namespace App\Enums\Transaction;

enum TransactionTypeEnum: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
}
