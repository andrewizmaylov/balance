<?php

namespace App\Enums\Transaction;

enum TransactionStatusEnum: string
{
    case Submited = 'submited';

    case Pending = 'pending';

    case Broadcasted = 'broadcasted';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

    case Failed = 'failed';

    case Refunded = 'refunded';

    case Blocked = 'blocked';

    case Unblocked = 'unblocked';
}
