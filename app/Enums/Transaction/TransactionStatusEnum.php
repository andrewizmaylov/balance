<?php

namespace App\Enums\Transaction;

enum TransactionStatusEnum: string
{
    case Request = 'request';

    case Pending = 'pending';

    case Confirmed = 'confirmed';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

    case Failed = 'failed';

    case Fulfilled = 'fulfilled';

    case Dispute = 'dispute';

    case Unblocked = 'unblocked';
}
