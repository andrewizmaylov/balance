<?php

namespace App\Enums\Account;

enum AccountTypeEnum: string
{
    case Saving = 'savings';
    case BTC = 'btc';
    case USDT = 'usdt';
}
