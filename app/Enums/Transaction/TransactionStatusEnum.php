<?php

namespace App\Enums\Transaction;

enum TransactionStatusEnum: string
{
    case Request = 'request';

    case Pending = 'pending';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

    case Failed = 'failed';

    case Refunded = 'refunded';

    case Blocked = 'blocked';

    case Unblocked = 'unblocked';
}
